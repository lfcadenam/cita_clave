<?php

namespace App\Console\Commands;

use Database\Seeders\LaBelleServicesSeeder;
use Illuminate\Console\Command;

class SeedNailServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nuvex:seed-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra o sincroniza los 11 servicios oficiales de La Belle Nails Bogotá (Manicura, Pedicura y Polygel)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sincronizando el catálogo de servicios de La Belle Nails Bogotá...');

        $seeder = new LaBelleServicesSeeder();
        $seeder->run();

        $this->info('✓ Catálogo de 11 servicios actualizado exitosamente en la base de datos.');

        return Command::SUCCESS;
    }
}
