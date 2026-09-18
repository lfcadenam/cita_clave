<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * En producción únicamente inicializa el Super Administrador global.
     * En testing o desarrollo local complementa con los datos demo para pruebas.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
        ]);

        if (app()->environment('testing')) {
            $this->call([
                DemoDataSeeder::class,
            ]);
        }
    }
}