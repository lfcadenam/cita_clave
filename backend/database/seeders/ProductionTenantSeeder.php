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
                'domain' => 'labellenailsbog.com',
                'phone' => '3103248385',
                'email' => 'contacto@labellenailsbog.com',
                'address' => 'Carrera 15 # 93-75, Chicó',
                'city' => 'Bogotá',
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

        // 5. Catálogo de Servicios Inicial para La Belle Nails Bogotá
        $services = [
            [
                'name' => 'Lifting de Pestañas con Keratina + Laminado de Cejas',
                'slug' => 'lifting-keratina-laminado-cejas',
                'category' => ServiceCategory::PESTANAS_CEJAS,
                'description' => 'Curvatura natural de pestañas con nutrición profunda y peinado semipermanente de cejas con diseño visagista profesional.',
                'duration_minutes' => 75,
                'base_price' => 95000,
                'deposit_amount' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=800&q=80',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Extensiones de Pestañas Efecto Clásico / Pelo a Pelo',
                'slug' => 'pestanas-pelo-a-pelo-clasico',
                'category' => ServiceCategory::PESTANAS_CEJAS,
                'description' => 'Aplicación minuciosa fibra por fibra con adhesivo hipoalergénico de alta retención para una mirada natural, densa y elegante.',
                'duration_minutes' => 120,
                'base_price' => 140000,
                'deposit_amount' => 40000,
                'image_url' => 'https://images.unsplash.com/photo-1583001809873-a128495da465?w=800&q=80',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Diseño de Cejas con Henna Orgánica & Depilación Visagista',
                'slug' => 'diseno-cejas-henna-organica',
                'category' => ServiceCategory::PESTANAS_CEJAS,
                'description' => 'Mapeo facial milimétrico según tu estructura ósea, epilación con hilo/cera hipoalergénica y sombreado temporal en henna.',
                'duration_minutes' => 45,
                'base_price' => 55000,
                'deposit_amount' => 20000,
                'image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&q=80',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Limpieza Facial Profunda con Hidrodermoabrasión e Hidratación',
                'slug' => 'limpieza-facial-profunda-hidro',
                'category' => ServiceCategory::FACIAL,
                'description' => 'Extracción indolora de impurezas, peeling ultrasónico, ampolleta de ácido hialurónico, alta frecuencia y mascarilla hidroplástica calmante.',
                'duration_minutes' => 90,
                'base_price' => 120000,
                'deposit_amount' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Hidratación Labial Profunda con Ácido Hialurónico (Baby Lips)',
                'slug' => 'hidratacion-labial-baby-lips',
                'category' => ServiceCategory::LABIOS,
                'description' => 'Regeneración labial intensiva, efecto volumen sutil sin agujas, perfilado hidratante y nutrición duradera contra resequedad.',
                'duration_minutes' => 60,
                'base_price' => 110000,
                'deposit_amount' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=800&q=80',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Masaje Relajante Descontracturante con Aromaterapia y Piedras Calientes',
                'slug' => 'masaje-relajante-descontracturante',
                'category' => ServiceCategory::CORPORAL_MASAJES,
                'description' => 'Terapia corporal integral de descarga muscular, liberación de tensiones en espalda y cuello, ambientación con aceites esenciales botánicos.',
                'duration_minutes' => 60,
                'base_price' => 100000,
                'deposit_amount' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800&q=80',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Depilación Facial Completa con Cera Suave de Manzanilla',
                'slug' => 'depilacion-facial-completa',
                'category' => ServiceCategory::DEPILACION,
                'description' => 'Eliminación delicada del vello en bozo, mentón, patillas y frente con cera vegetal tibia para pieles sensibles y mascarilla descongestiva.',
                'duration_minutes' => 30,
                'base_price' => 45000,
                'deposit_amount' => 15000,
                'image_url' => 'https://images.unsplash.com/photo-1560750588-73207b1ef5b8?w=800&q=80',
                'is_active' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($services as $svc) {
            Service::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $svc['slug']],
                array_merge($svc, ['tenant_id' => $tenant->id])
            );
        }
    }
}
