<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\ServiceCategory;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkingSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Super Admin de Nuvex Tecnologia (Plataforma Global)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@nuvex.co'],
            [
                'name' => 'Super Admin Nuvex',
                'password' => Hash::make('Nuvex2026!*'),
                'role' => UserRole::SUPER_ADMIN,
                'phone' => '3001234567',
                'is_active' => true,
            ]
        );

        // 1. Tenant Principal: Estudio Paola Aguilera
        $tenantPaola = Tenant::firstOrCreate(
            ['slug' => 'paola-aguilera'],
            [
                'name' => 'Paola Andrea Aguilera Camacho',
                'domain' => 'paola.salonesgo.com',
                'phone' => '3106080402',
                'email' => 'paola@nuvex-belleza.com',
                'address' => 'Carrera 15 # 93-75, Chico',
                'city' => 'Bogota',
                'primary_color' => '#0d9488',
                'nequi_phone' => '3106080402',
                'nequi_account_holder' => 'Paola Andrea Aguilera Camacho',
                'nequi_account_type' => 'Ahorros Nequi',
                'subscription_status' => 'active',
                'plan_name' => 'SaaS Enterprise Nuvex',
                'max_appointments_per_month' => 1000,
                'is_active' => true,
            ]
        );

        // 2. Usuario Administrador del Salon (Paola Andrea Aguilera)
        $admin = User::firstOrCreate(
            ['email' => 'paola@nuvex-belleza.com'],
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Paola Andrea Aguilera Camacho',
                'password' => Hash::make('Paola12345!'),
                'role' => UserRole::ADMIN,
                'phone' => '3106080402',
                'is_active' => true,
            ]
        );
        $admin->update(['tenant_id' => $tenantPaola->id]);

        // 3. Horarios Laborales Semanales
        $days = [
            ['day_of_week' => 1, 'day_name' => 'Lunes', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '18:00:00'],
            ['day_of_week' => 2, 'day_name' => 'Martes', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '18:00:00'],
            ['day_of_week' => 3, 'day_name' => 'Miercoles', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '18:00:00'],
            ['day_of_week' => 4, 'day_name' => 'Jueves', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '18:00:00'],
            ['day_of_week' => 5, 'day_name' => 'Viernes', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '18:00:00'],
            ['day_of_week' => 6, 'day_name' => 'Sabado', 'is_working_day' => true, 'open_time' => '08:00:00', 'close_time' => '17:00:00'],
            ['day_of_week' => 0, 'day_name' => 'Domingo', 'is_working_day' => false, 'open_time' => '09:00:00', 'close_time' => '14:00:00'],
        ];

        foreach ($days as $d) {
            WorkingSchedule::updateOrCreate(
                ['tenant_id' => $tenantPaola->id, 'day_of_week' => $d['day_of_week']],
                array_merge($d, ['tenant_id' => $tenantPaola->id])
            );
        }

        // 4. Franja Bloqueada Recurrente para Almuerzo
        BlockedSlot::firstOrCreate(
            ['tenant_id' => $tenantPaola->id, 'title' => 'Hora de Almuerzo & Descanso'],
            [
                'tenant_id' => $tenantPaola->id,
                'start_time' => '13:00:00',
                'end_time' => '14:00:00',
                'is_recurring' => true,
                'reason' => 'Horario habitual de almuerzo',
            ]
        );

        // 5. Catalogo de Servicios
        $services = [
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Limpieza Facial Profunda con Vapor de Ozono e Hidratacion',
                'slug' => 'limpieza-facial-profunda',
                'category' => ServiceCategory::FACIAL,
                'description' => 'Desintoxicacion cutanea, extraccion de impurezas, alta frecuencia, mascarilla hidroplastica y masaje relajante facial.',
                'duration_minutes' => 90,
                'base_price' => 120000,
                'deposit_amount' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=600',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Extensiones de Pestanas Efecto Clasico / Pelo a Pelo',
                'slug' => 'pestanas-pelo-a-pelo',
                'category' => ServiceCategory::PESTANAS_CEJAS,
                'description' => 'Aplicacion minuciosa fibra por fibra con adhesivo hipoalergenico de alta retencion para una mirada natural y elegante.',
                'duration_minutes' => 120,
                'base_price' => 140000,
                'deposit_amount' => 40000,
                'image_url' => 'https://images.unsplash.com/photo-1583001809873-a128495da465?w=600',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Lifting de Pestanas con Keratina + Laminado de Cejas',
                'slug' => 'lifting-laminado-combo',
                'category' => ServiceCategory::PESTANAS_CEJAS,
                'description' => 'Curvatura natural de pestanas con nutricion profunda y peinado semipermanente de cejas con diseno visagista.',
                'duration_minutes' => 75,
                'base_price' => 95000,
                'deposit_amount' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=600',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Microblading & Micropigmentacion de Cejas 3D',
                'slug' => 'microblading-cejas-3d',
                'category' => ServiceCategory::PESTANAS_CEJAS,
                'description' => 'Tecnica hiperrealista de simulacion de vellos con pigmentos biocompatibles para redefinir cejas despobladas.',
                'duration_minutes' => 150,
                'base_price' => 280000,
                'deposit_amount' => 80000,
                'image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=600',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Hidratacion Labial Profunda con Acido Hialuronico (Hyaluron Pen)',
                'slug' => 'hidratacion-labial-acido-hialuronico',
                'category' => ServiceCategory::LABIOS,
                'description' => 'Nutricion intensa, efecto gloss regenerador y suavizado de lineas de expresion sin agujas.',
                'duration_minutes' => 60,
                'base_price' => 110000,
                'deposit_amount' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=600',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Masaje Relajante Corporal con Piedras Calientes y Aromaterapia',
                'slug' => 'masaje-relajante-piedras-calientes',
                'category' => ServiceCategory::CORPORAL_MASAJES,
                'description' => 'Terapia integral anti-estres para aliviar tensiones musculares en espalda, cuello y extremidades.',
                'duration_minutes' => 60,
                'base_price' => 90000,
                'deposit_amount' => 20000,
                'image_url' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Depilacion Facial Completa con Cera de Aloe Vera',
                'slug' => 'depilacion-facial-aloe',
                'category' => ServiceCategory::DEPILACION,
                'description' => 'Cejas, bozo, menton y patillas con cera elastica especial para pieles sensibles.',
                'duration_minutes' => 45,
                'base_price' => 50000,
                'deposit_amount' => 15000,
                'image_url' => 'https://images.unsplash.com/photo-1560750588-73207b1ef5b8?w=600',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'tenant_id' => $tenantPaola->id,
                'name' => 'Peeling Quimico Renovador & Despigmentante',
                'slug' => 'peeling-quimico-anti-manchas',
                'category' => ServiceCategory::FACIAL,
                'description' => 'Renovacion celular profunda con acidos glicolico y mandelico para atenuar manchas y mejorar textura.',
                'duration_minutes' => 60,
                'base_price' => 150000,
                'deposit_amount' => 40000,
                'image_url' => 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?w=600',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($services as $svc) {
            Service::updateOrCreate(
                ['tenant_id' => $tenantPaola->id, 'slug' => $svc['slug']],
                $svc
            );
        }

        // 6. Citas de Ejemplo
        $facialService = Service::where('tenant_id', $tenantPaola->id)->where('slug', 'limpieza-facial-profunda')->first();
        $lashesService = Service::where('tenant_id', $tenantPaola->id)->where('slug', 'pestanas-pelo-a-pelo')->first();

        Appointment::updateOrCreate(
            ['tenant_id' => $tenantPaola->id, 'appointment_number' => 'PA-2609-CF01'],
            [
                'tenant_id' => $tenantPaola->id,
                'service_id' => $facialService->id,
                'client_name' => 'Camila Restrepo',
                'client_phone' => '3124567890',
                'client_email' => 'camila.restrepo@gmail.com',
                'appointment_date' => now()->addDay()->format('Y-m-d'),
                'start_time' => '09:00:00',
                'end_time' => '10:30:00',
                'status' => AppointmentStatus::CONFIRMED,
                'payment_method' => PaymentMethod::BOLD_ONLINE,
                'total_amount' => $facialService->base_price,
                'deposit_amount' => $facialService->deposit_amount,
                'deposit_paid' => $facialService->deposit_amount,
                'balance_due' => $facialService->base_price - $facialService->deposit_amount,
                'verified_at' => now(),
                'client_notes' => 'Piel sensible con tendencia a rojez.',
            ]
        );

        Appointment::updateOrCreate(
            ['tenant_id' => $tenantPaola->id, 'appointment_number' => 'PA-2609-NQ02'],
            [
                'tenant_id' => $tenantPaola->id,
                'service_id' => $lashesService->id,
                'client_name' => 'Valentina Gomez',
                'client_phone' => '3159876543',
                'client_email' => 'valentina.gomez@hotmail.com',
                'appointment_date' => now()->addDay()->format('Y-m-d'),
                'start_time' => '10:30:00',
                'end_time' => '12:30:00',
                'status' => AppointmentStatus::PENDING_VERIFICATION,
                'payment_method' => PaymentMethod::NEQUI_TRANSFER,
                'total_amount' => $lashesService->base_price,
                'deposit_amount' => $lashesService->deposit_amount,
                'deposit_paid' => $lashesService->deposit_amount,
                'balance_due' => $lashesService->base_price - $lashesService->deposit_amount,
                'deposit_proof_image' => 'samples/comprobante_nequi_muestra.png',
                'client_notes' => 'Primera vez realizandose extensiones.',
            ]
        );
    }
}
