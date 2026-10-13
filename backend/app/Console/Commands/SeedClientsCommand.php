<?php

namespace App\Console\Commands;

use Database\Seeders\LaBelleClientsSeeder;
use Illuminate\Console\Command;

class SeedClientsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nuvex:seed-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa y sincroniza la lista oficial de 67 clientas reales de La Belle Nails Bogotá';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando importación de clientas para La Belle Nails Bogotá...');

        $seeder = new LaBelleClientsSeeder();
        $seeder->run();

        $this->info('✓ 67 clientas sincronizadas exitosamente en el panel y base de datos.');

        return Command::SUCCESS;
    }
}
