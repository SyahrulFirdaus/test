<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Logo klien dipotong dari materi "trusted by" milik perusahaan.
     * Merek dan logo tetap milik masing-masing perusahaan.
     */
    public function run(): void
    {
        $clients = [
            ['name' => 'eFishery', 'slug' => 'efishery', 'industry' => 'Aquaculture Technology'],
            ['name' => 'Pindad', 'slug' => 'pindad', 'industry' => 'Manufaktur & Pertahanan'],
            ['name' => 'Mikuni', 'slug' => 'mikuni', 'industry' => 'Komponen Otomotif'],
            ['name' => 'SGT', 'slug' => 'sgt', 'industry' => 'Industri Manufaktur'],
            ['name' => 'Voith', 'slug' => 'voith', 'industry' => 'Teknologi Industri'],
            ['name' => 'FANUC', 'slug' => 'fanuc', 'industry' => 'Robotika & Otomasi'],
            ['name' => 'Proinnov Teknologi Indonesia', 'slug' => 'pti', 'industry' => 'Teknologi Industri'],
            ['name' => 'SLB', 'slug' => 'slb', 'industry' => 'Energi'],
            ['name' => 'ESAutomation', 'slug' => 'esautomation', 'industry' => 'Otomasi Industri'],
            ['name' => 'Dharma Group', 'slug' => 'dharma-group', 'industry' => 'Komponen Otomotif'],
        ];

        foreach ($clients as $index => $client) {
            Client::updateOrCreate(
                ['slug' => $client['slug']],
                $client + [
                    'logo' => 'images/clients/'.$client['slug'].'.png',
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
