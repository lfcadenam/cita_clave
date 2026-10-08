<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ManageSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:superadmin 
                            {--email=citaclave@gmail.com : Correo electrónico del Super Administrador}
                            {--password= : Nueva contraseña para el Super Admin}
                            {--list : Solo listar los usuarios Super Admin actuales}
                            {--delete-others : Eliminar otros super admins existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta, resetea o crea el usuario Super Administrador global';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('       👑 GESTIÓN DE SUPER ADMIN - CITA CLAVE        ');
        $this->info('====================================================');

        // 1. Listar super admins actuales
        $superAdmins = User::where('role', UserRole::SUPER_ADMIN)->get();

        if ($superAdmins->isNotEmpty()) {
            $this->line("\n📋 Super Administradores registrados actualmente:");
            $tableData = $superAdmins->map(function ($u) {
                return [
                    'ID' => $u->id,
                    'Nombre' => $u->name,
                    'Email' => $u->email,
                    'Activo' => $u->is_active ? 'Sí' : 'No',
                    'Creado' => $u->created_at?->format('Y-m-d H:i'),
                ];
            });
            $this->table(['ID', 'Nombre', 'Email', 'Activo', 'Creado'], $tableData);
        } else {
            $this->warn("\n⚠️ No se encontraron usuarios con rol SUPER_ADMIN en la base de datos.");
        }

        if ($this->option('list')) {
            return Command::SUCCESS;
        }

        $email = trim($this->option('email'));
        $password = $this->option('password') ?: 'Admin2026!*';

        if ($this->option('delete-others')) {
            $deleted = User::where('role', UserRole::SUPER_ADMIN)
                ->where('email', '!=', $email)
                ->delete();
            if ($deleted > 0) {
                $this->warn("🗑️ Se eliminaron {$deleted} super admin(es) anteriores.");
            }
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin Cita Clave',
                'password' => Hash::make($password),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
                'tenant_id' => null,
            ]
        );

        $this->line('');
        $this->info('✅ ¡Usuario Super Admin configurado exitosamente!');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['URL Panel', url('/superadmin/login')],
                ['Email', $user->email],
                ['Contraseña', $password],
                ['Rol', 'SUPER_ADMIN'],
                ['Estado', 'Activo'],
            ]
        );
        $this->warn('🔒 Recuerda cambiar esta contraseña tras tu primer inicio de sesión.');

        return Command::SUCCESS;
    }
}
