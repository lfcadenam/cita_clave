<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Waitlist;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ClearAppointmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:clear
                            {--all : Elimina todas las citas registradas en la base de datos}
                            {--status= : Eliminar únicamente citas con un estado específico (ej: CANCELLED, PENDING_DEPOSIT)}
                            {--before= : Eliminar citas anteriores a una fecha (formato YYYY-MM-DD)}
                            {--clean-receipts : Eliminar también las imágenes de comprobantes de pago huérfanos del disco}
                            {--force : Ejecutar sin solicitar confirmación interactiva}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia de forma segura los datos de citas en la base de datos, con opciones de filtrado por estado o fecha.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $all = $this->option('all');
        $status = $this->option('status');
        $beforeDate = $this->option('before');
        $cleanReceipts = $this->option('clean-receipts');

        if (!$all && empty($status) && empty($beforeDate)) {
            $this->error('❌ Debes especificar un criterio de eliminación:');
            $this->line('  • Para limpiar TODAS las citas: php artisan appointments:clear --all');
            $this->line('  • Por estado: php artisan appointments:clear --status=CANCELLED');
            $this->line('  • Anteriores a una fecha: php artisan appointments:clear --before=2026-10-01');
            return Command::FAILURE;
        }

        $query = Appointment::query();

        if (!empty($status)) {
            $query->where('status', strtoupper($status));
        }

        if (!empty($beforeDate)) {
            $query->whereDate('appointment_date', '<', $beforeDate);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('ℹ️ No se encontraron citas que coincidan con los criterios especificados.');
            return Command::SUCCESS;
        }

        $this->warn('=============================================================');
        $this->warn('  NUVEX — LIMPIEZA CONTROLADA DE CITAS (APPOINTMENTS)        ');
        $this->warn('=============================================================');
        $this->line("Se eliminarán <fg=red;options=bold>{$count}</> citas registradas.");
        if ($all) {
            $this->line('  • Criterio: TODAS las citas registradas.');
        } else {
            if ($status) {
                $this->line("  • Estado: {$status}");
            }
            if ($beforeDate) {
                $this->line("  • Fecha anterior a: {$beforeDate}");
            }
        }
        $this->line('=============================================================');

        if (!$this->option('force')) {
            if (!$this->confirm('¿Estás seguro de que deseas proceder con la eliminación?', false)) {
                $this->comment('Operación cancelada por el usuario. No se realizaron cambios.');
                return Command::SUCCESS;
            }
        }

        $this->info('Eliminando citas...');

        DB::beginTransaction();

        try {
            if ($all) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('appointments')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } else {
                $query->delete();
            }

            DB::commit();
            $this->info("✓ Se eliminaron correctamente {$count} citas de la base de datos.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("❌ Error al limpiar las citas: {$e->getMessage()}");
            return Command::FAILURE;
        }

        // Limpieza opcional de comprobantes de pago
        if ($cleanReceipts) {
            $receiptsDir = storage_path('app/public/receipts');
            $deletedFiles = 0;
            if (File::isDirectory($receiptsDir)) {
                foreach (File::files($receiptsDir) as $file) {
                    File::delete($file);
                    $deletedFiles++;
                }
            }
            $this->line("✓ Se eliminaron {$deletedFiles} comprobantes de pago almacenados en disco.");
        }

        $this->newLine();
        $this->info('Citas restantes en el sistema: ' . Appointment::count());

        return Command::SUCCESS;
    }
}
