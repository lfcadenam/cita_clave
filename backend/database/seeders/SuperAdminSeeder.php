<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Inicializa el Super Administrador global de la plataforma Cita Clave SaaS.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@nuvex-tecnologia.com'],
            [
                'name' => 'Super Admin Nuvex',
                'password' => Hash::make('C3be7x33ygh'),
                'role' => UserRole::SUPER_ADMIN,
                'phone' => '3001234567',
                'is_active' => true,
                'email_verified_at' => now(),
                'tenant_id' => null,
            ]
        );
    }
}
