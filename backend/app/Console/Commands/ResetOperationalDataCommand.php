<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Waitlist;
use App\Models\WorkingSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ResetOperationalDataCommand extends Command
{
    /**
     * El nombre y la firma del comando Artisan.
     *
     * @var string
     */
    protected $signature = 'nuvex:reset-operational-data 
                            {--force : Ejecutar sin solicitar confirmación interactiva}
                            {--keep-services : Conservar el catálogo de servicios intacto}';

    /**
     * Descripción del comando en Artisan.
     *
     * @var string
     */
    protected $description = 'Limpia la base de datos de pruebas (citas, clientes, comprobantes y servicios opcionalmente) preservando tenants, usuarios y horarios de atención.';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $keepServices = $this->option('keep-services');

        $this->warn('=============================================================');
        $this->warn('  NUVEX — LIMPIEZA DE DATOS OPERATIVOS PARA PRODUCCIÓN       ');
        $this->warn('=============================================================');
        $this->line('Esta acción eliminará de forma irreversible:');
        $this->line('  • Todas las citas registradas');
        $this->line('  • Todas las clientas registradas');
        if (!$keepServices) {
            $this->line('  • Todos los servicios del catálogo');
        } else {
            $this->info('  • (Los servicios del catálogo serán PRESERVADOS)');
        }
        $this->line('  • Lista de espera y bloqueos temporales');
        $this->line('  • Comprobantes de pago en almacenamiento');
        $this->line('');
        $this->info('Se mantendrán intactos: Tenants (Salones), Usuarios Administradores y Horarios de Atención.');
        $this->line('=============================================================');

        if (!$this->option('force')) {
            if (!$this->confirm('¿Estás 100% seguro de que deseas proceder con la limpieza?', false)) {
                $this->comment('Operación cancelada por el usuario. No se realizaron cambios.');
                return Command::SUCCESS;
            }
        }

        $this->info('Iniciando limpieza controlada...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('appointments')->truncate();
        $this->line('  ✓ Tabla appointments truncada.');

        DB::table('customers')->truncate();
        $this->line('  ✓ Tabla customers truncada.');

        if (!$keepServices) {
            DB::table('services')->truncate();
            $this->line('  ✓ Tabla services truncada.');
        }

        DB::table('waitlists')->truncate();
        $this->line('  ✓ Tabla waitlists truncada.');

        DB::table('blocked_slots')->where('is_recurring', false)->delete();
        $this->line('  ✓ Bloqueos puntuales temporales eliminados.');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Limpieza de archivos de comprobante
        $receiptsDir = storage_path('app/public/receipts');
        $deletedReceipts = 0;
        if (File::isDirectory($receiptsDir)) {
            foreach (File::files($receiptsDir) as $file) {
                File::delete($file);
                $deletedReceipts++;
            }
        }
        $this->line("  ✓ {$deletedReceipts} archivos de comprobantes eliminados de disco.");

        $this->newLine();
        $this->info('=============================================================');
        $this->info('  LIMPIEZA COMPLETADA CON ÉXITO                             ');
        $this->info('=============================================================');
        $this->table(
            ['Entidad / Tabla', 'Registros Restantes'],
            [
                ['Citas (appointments)', Appointment::count()],
                ['Clientes (customers)', Customer::count()],
                ['Servicios (services)', Service::count()],
                ['Lista de Espera (waitlists)', Waitlist::count()],
                ['Bloqueos (blocked_slots)', BlockedSlot::count()],
                ['Horarios (working_schedules)', WorkingSchedule::count()],
                ['Usuarios (users)', User::count()],
                ['Salones / Tenants (tenants)', Tenant::count()],
            ]
        );

        return Command::SUCCESS;
    }
}
