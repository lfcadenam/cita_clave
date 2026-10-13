<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LaBelleClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::where('slug', 'labellenails')->get();
        if ($tenants->isEmpty()) {
            $tenants = Tenant::where('domain', 'like', '%labellenails%')->get();
        }
        if ($tenants->isEmpty()) {
            $tenants = Tenant::all();
        }
        if ($tenants->isEmpty()) {
            $tenants = collect([
                Tenant::create([
                    'name' => 'La Belle Nails Bogotá • Paola Aguilera',
                    'slug' => 'labellenails',
                    'domain' => 'labellenailsbog.com',
                    'is_active' => true,
                ])
            ]);
        }

        $clients = [
            ['name' => 'Alexandra Bueno', 'phone' => '+573112512847'],
            ['name' => 'Alexandra Toro', 'phone' => '+573154330358'],
            ['name' => 'Ana Beliza Mercado', 'phone' => '+573174041848'],
            ['name' => 'Ana Maria Alvarado', 'phone' => '+573182804631'],
            ['name' => 'Ana Maria Guzman', 'phone' => '+573165384243'],
            ['name' => 'Ana Maria Montoya', 'phone' => '+573178951604'],
            ['name' => 'Andrea Buitrago', 'phone' => '+573112539037'],
            ['name' => 'Andrea Cortes', 'phone' => '+573244235274'],
            ['name' => 'Andrea Marmolejo', 'phone' => '+573183365300'],
            ['name' => 'Andrea Morales', 'phone' => '+525580617221'],
            ['name' => 'Angie Moran', 'phone' => '+573125167410'],
            ['name' => 'Blanca Castro', 'phone' => '+573225278098'],
            ['name' => 'Carolina Arroyo', 'phone' => '+573008743308'],
            ['name' => 'Catalina Cardona', 'phone' => '+573106570070'],
            ['name' => 'Catalina Quiroz', 'phone' => '+573213432171'],
            ['name' => 'Catalina Sanabria', 'phone' => '+573134969288'],
            ['name' => 'Catherien Siachoque', 'phone' => '+17863559136'],
            ['name' => 'Catherine Espinosa', 'phone' => '+573123796059'],
            ['name' => 'Catherine Moggio', 'phone' => '+573134338239'],
            ['name' => 'Daina Garcia', 'phone' => '+573138707744'],
            ['name' => 'Daniela Otalora', 'phone' => '+573112782451'],
            ['name' => 'Daniela Vidal', 'phone' => '+573113530750'],
            ['name' => 'Danna Adame', 'phone' => '+573104834194'],
            ['name' => 'Diana Zambrano', 'phone' => '+573174241667'],
            ['name' => 'Elisa Beltran', 'phone' => '+573176366446'],
            ['name' => 'Elisa Santamaria', 'phone' => '+573176366465'],
            ['name' => 'Elizabeth Proaños', 'phone' => '+573212103501'],
            ['name' => 'Ellen Mendivelso', 'phone' => '+573175105664'],
            ['name' => 'Estefania Corredor', 'phone' => '+573102529704'],
            ['name' => 'Helena Fadul', 'phone' => '+573103956228'],
            ['name' => 'Isabella Yepes', 'phone' => '+573144136052'],
            ['name' => 'Jennifer Valdes', 'phone' => '+573137982400'],
            ['name' => 'Jill San Juan', 'phone' => '+573118495159'],
            ['name' => 'Juan Pablo Socarras', 'phone' => '+573108749904'],
            ['name' => 'Juliana Castro', 'phone' => '+573125170365'],
            ['name' => 'Juliana Velasco', 'phone' => '+573166938847'],
            ['name' => 'Julieht Valdes', 'phone' => '+573102088945'],
            ['name' => 'Karol Perez', 'phone' => '+573003751387'],
            ['name' => 'Kelly Porras', 'phone' => '+573184872101'],
            ['name' => 'Laura Rodriguez', 'phone' => '+573103354319'],
            ['name' => 'Laurie Haimes', 'phone' => '+13057217550'],
            ['name' => 'Leonardo Angulo', 'phone' => '+573152203258'],
            ['name' => 'Lilian Rozo', 'phone' => '+573123789644'],
            ['name' => 'Liliana Herrera', 'phone' => '+573123540399'],
            ['name' => 'Lina Pulido', 'phone' => '+573112673446'],
            ['name' => 'Luisa Morales', 'phone' => '+573143342855'],
            ['name' => 'Manuela Botero', 'phone' => '+573108682511'],
            ['name' => 'Maria Claudia Ricalde', 'phone' => '+573232249395'],
            ['name' => 'Maria Paula Gomez', 'phone' => '+573114816518'],
            ['name' => 'Maritza Arias', 'phone' => '+573057870706'],
            ['name' => 'Marlly Moreno', 'phone' => '+573229492636'],
            ['name' => 'Martha Chavez', 'phone' => '+573114737072'],
            ['name' => 'Nathalie Grossman', 'phone' => '+573156353291'],
            ['name' => 'Nathalie Magno', 'phone' => '+573188211537'],
            ['name' => 'Nina Rodriguez', 'phone' => '+573103216342'],
            ['name' => 'Orian Himelfarb', 'phone' => '+573107601021'],
            ['name' => 'Paula Zabal', 'phone' => '+573142762255'],
            ['name' => 'Renee Cohen', 'phone' => '+573175171971'],
            ['name' => 'Sofia Reyes', 'phone' => '+573103214183'],
            ['name' => 'Stephania Bradford', 'phone' => '+17868326477'],
            ['name' => 'Stephania Mattos', 'phone' => '+573158602270'],
            ['name' => 'Tatiana De Los Rios', 'phone' => '+573137918685'],
            ['name' => 'Thalia Birmaher', 'phone' => '+573128631842'],
            ['name' => 'Valentina Castañeda', 'phone' => '+573124814272'],
            ['name' => 'Victoria Btesh', 'phone' => '+16174802175'],
            ['name' => 'Victoria Holguin', 'phone' => '+573164131913'],
            ['name' => 'Vivian Mohnblatt', 'phone' => '+573138537263'],
        ];

        foreach ($tenants as $tenant) {
            foreach ($clients as $clientData) {
                $cleanPhone = preg_replace('/[^0-9]/', '', (string) $clientData['phone']);

                Customer::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'phone' => $cleanPhone,
                    ],
                    [
                        'name' => trim($clientData['name']),
                        'is_legacy' => true,
                        'notes' => 'Clienta habitual histórica de La Belle Nails Bogotá',
                        'registered_at' => Carbon::parse('2026-01-01 09:00:00'),
                    ]
                );
            }
        }
    }
}
