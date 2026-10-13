<?php

namespace Database\Seeders;

use App\Enums\ServiceCategory;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LaBelleServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first() ?? Tenant::create([
            'name' => 'Paola Andrea Aguilera Camacho',
            'slug' => 'labellenails',
            'domain' => 'labellenailsbog.com',
            'is_active' => true,
        ]);

        $services = [
            [
                'name' => 'Limpieza Manicura Rusa',
                'category' => ServiceCategory::MANICURA,
                'description' => 'Técnica en seco de alta precisión para cutículas limpias y acabado pulido impecable.',
                'duration_minutes' => 50,
                'base_price' => 60000,
                'deposit_amount' => 50000,
                'image_url' => 'https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 1,
            ],
            [
                'name' => 'Limpieza Pedicura',
                'category' => ServiceCategory::PEDICURA,
                'description' => 'Limpieza profunda, exfoliación suave y cuidado integral para pies descansados y uñas saludables.',
                'duration_minutes' => 50,
                'base_price' => 55000,
                'deposit_amount' => 50000,
                'image_url' => 'https://images.unsplash.com/photo-1519415510236-718bdfcd89c8?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 2,
            ],
            [
                'name' => 'Pedicura Semipermanente',
                'category' => ServiceCategory::PEDICURA,
                'description' => 'Pedicura completa con esmaltado semipermanente de máxima duración y secado inmediato en lámpara.',
                'duration_minutes' => 60,
                'base_price' => 85000,
                'deposit_amount' => 50000,
                'image_url' => 'https://images.unsplash.com/photo-1664643411326-6c589531be3c?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 3,
            ],
            [
                'name' => 'Aplicación Semi',
                'category' => ServiceCategory::MANICURA,
                'description' => 'Esmaltado semipermanente profesional en manos con brillo espejo de larga duración.',
                'duration_minutes' => 30,
                'base_price' => 55000,
                'deposit_amount' => 50000,
                'image_url' => 'https://images.unsplash.com/photo-1519014816548-bf5fe059798b?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 4,
            ],
            [
                'name' => 'Aplicación Rubber',
                'category' => ServiceCategory::MANICURA,
                'description' => 'Base Rubber niveladora y reforzadora para aportar resistencia, volumen y flexibilidad a la uña natural.',
                'duration_minutes' => 30,
                'base_price' => 60000,
                'deposit_amount' => 50000,
                'image_url' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 5,
            ],
            [
                'name' => 'Remoción Semi',
                'category' => ServiceCategory::REPARACION_RETIRO,
                'description' => 'Retiro profesional y seguro de esmaltado semipermanente sin desgastar la lámina ungueal.',
                'duration_minutes' => 15,
                'base_price' => 16000,
                'deposit_amount' => 0,
                'image_url' => 'https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 6,
            ],
            [
                'name' => 'Pedicura Spa',
                'category' => ServiceCategory::PEDICURA,
                'description' => 'Experiencia relajante con exfoliación profunda, mascarilla nutritiva, masaje podal y esmaltado.',
                'duration_minutes' => 60,
                'base_price' => 65000,
                'deposit_amount' => 50000,
                'image_url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 7,
            ],
            [
                'name' => 'Remiendo',
                'category' => ServiceCategory::REPARACION_RETIRO,
                'description' => 'Reparación exprés de uña rota, fisurada o debilitada para restaurar su estructura.',
                'duration_minutes' => 5,
                'base_price' => 5000,
                'deposit_amount' => 0,
                'image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 8,
            ],
            [
                'name' => 'Uña Individual Polygel',
                'category' => ServiceCategory::SISTEMAS_ARTIFICIALES,
                'description' => 'Reconstrucción o extensión de una uña individual mediante técnica esculpida en Polygel.',
                'duration_minutes' => 5,
                'base_price' => 20000,
                'deposit_amount' => 0,
                'image_url' => 'https://images.unsplash.com/photo-1596704017254-9b121068fb31?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 9,
            ],
            [
                'name' => 'Uñas Polygel',
                'category' => ServiceCategory::SISTEMAS_ARTIFICIALES,
                'description' => 'Set completo esculpido en Polygel: combinación perfecta de ligereza, resistencia superior y elegancia.',
                'duration_minutes' => 120,
                'base_price' => 210000,
                'deposit_amount' => 50000,
                'image_url' => 'https://images.unsplash.com/photo-1601244668565-afba8cbd2b51?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 10,
            ],
            [
                'name' => 'Mantenimiento Polygel',
                'category' => ServiceCategory::SISTEMAS_ARTIFICIALES,
                'description' => 'Retoque y nivelación del crecimiento en uñas de Polygel para prolongar su durabilidad y aspecto nuevo.',
                'duration_minutes' => 90,
                'base_price' => 155000,
                'deposit_amount' => 50000,
                'image_url' => 'https://images.unsplash.com/photo-1607779097040-26e80aa78e66?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 11,
            ],
        ];

        foreach ($services as $srv) {
            $slug = Str::slug($srv['name']);
            Service::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => $slug,
                ],
                array_merge($srv, [
                    'tenant_id' => $tenant->id,
                    'slug' => $slug,
                    'is_active' => true,
                ])
            );
        }
    }
}
