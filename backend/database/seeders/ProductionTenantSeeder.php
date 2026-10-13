<?php

namespace Database\Seeders;

use App\Enums\ServiceCategory;
use App\Enums\UserRole;
use App\Models\BlockedSlot;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkingSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionTenantSeeder extends Seeder
{
    /**
     * Aprovisiona el salón principal de producción "La Belle Nails Bogotá" (Paola Aguilera)
     * para el dominio https://labellenailsbog.com con su configuración inicial completa.
     */
    public function run(): void
    {
        // 1. Tenant Principal de Producción
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'labellenails'],
            [
                'name' => 'La Belle Nails Bogotá • Paola Aguilera',
                'short_name' => 'La Belle Nails',
                'business_type' => 'ESTUDIO DE BELLEZA & NAILS',
                'domain' => 'labellenailsbog.com',
                'phone' => '3103248385',
                'whatsapp_number' => '3103248385',
                'email' => 'contacto@labellenailsbog.com',
                'address' => 'Carrera 15 # 93-75, Chicó',
                'city' => 'Bogotá',
                'specialties' => 'Uñas Esculpidas · Pestañas · Cejas · Cuidado Facial',
                'schedule_summary' => 'Lunes a Sábado 8:00 AM - 6:00 PM | Almuerzo 1:00 PM - 2:00 PM',
                'primary_color' => '#0d9488',
                'nequi_phone' => '3103248385',
                'nequi_account_holder' => 'Paola Andrea Aguilera Camacho',
                'nequi_account_type' => 'Ahorros Nequi',
                'subscription_status' => 'active',
                'plan_name' => 'SaaS Enterprise Nuvex',
                'max_appointments_per_month' => 2000,
                'is_active' => true,
            ]
        );

        // 2. Administradora del Salón
        User::updateOrCreate(
            ['email' => 'paola@labellenailsbog.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Paola Andrea Aguilera Camacho',
                'password' => Hash::make(env('INITIAL_SALON_ADMIN_PASSWORD', 'Paola2026!LaBelle')),
                'role' => UserRole::ADMIN,
                'phone' => '3103248385',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Horarios Laborales Semanales (Lunes a Sábado de 08:00 a 19:00)
        $schedules = [
            ['day_of_week' => 1, 'day_name' => 'Lunes', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '19:00:00'],
            ['day_of_week' => 2, 'day_name' => 'Martes', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '19:00:00'],
            ['day_of_week' => 3, 'day_name' => 'Miércoles', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '19:00:00'],
            ['day_of_week' => 4, 'day_name' => 'Jueves', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '19:00:00'],
            ['day_of_week' => 5, 'day_name' => 'Viernes', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '19:00:00'],
            ['day_of_week' => 6, 'day_name' => 'Sábado', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '18:00:00'],
            ['day_of_week' => 0, 'day_name' => 'Domingo', 'is_working_day' => false, 'open_time' => '09:00:00', 'close_time' => '14:00:00'],
        ];

        foreach ($schedules as $sched) {
            WorkingSchedule::updateOrCreate(
                ['tenant_id' => $tenant->id, 'day_of_week' => $sched['day_of_week']],
                array_merge($sched, ['tenant_id' => $tenant->id])
            );
        }

        // 4. Franja de Almuerzo Habitual (Bloqueo recurrente 13:00 - 14:00)
        BlockedSlot::firstOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Almuerzo & Descanso'],
            [
                'tenant_id' => $tenant->id,
                'start_time' => '13:00:00',
                'end_time' => '14:00:00',
                'is_recurring' => true,
                'reason' => 'Horario habitual de almuerzo',
            ]
        );

        // 5. Catálogo Oficial de Servicios (11 servicios oficiales de La Belle Nails Bogotá)
        $this->call(LaBelleServicesSeeder::class);
    }
}
