<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PilotSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data countries
        $countries = [
            ['country_code' => 'IT', 'country_name' => 'Italy'],
            ['country_code' => 'ID', 'country_name' => 'Indonesia'],
            ['country_code' => 'DE', 'country_name' => 'Germany'],
        ];

        $this->db->table('countries')->insertBatch($countries);

        // 2. Data ranks
        $ranks = [
            ['rank_id' => 1, 'rank_name' => 'Garuda Supreme'],
        ];

        $this->db->table('ranks')->insertBatch($ranks);

        // 3. Data pilots
        $pilots = [
            [
                'pilot_id' => 'GVG024',
                'name' => 'Raiii',
                'country_code' => 'IT',
                'pirep_filed' => 1096,
                'flight_minutes' => 961255,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG114',
                'name' => 'Rulizal Al Hafizh',
                'country_code' => 'ID',
                'pirep_filed' => 1205,
                'flight_minutes' => 574190,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG122',
                'name' => 'Farel Akasya',
                'country_code' => 'ID',
                'pirep_filed' => 206,
                'flight_minutes' => 522750,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG132',
                'name' => 'Mochamad fachri',
                'country_code' => 'ID',
                'pirep_filed' => 368,
                'flight_minutes' => 403526,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG008',
                'name' => 'Astralis',
                'country_code' => 'DE',
                'pirep_filed' => 161,
                'flight_minutes' => 401444,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG012',
                'name' => 'Fadzlan Ferdiansyah',
                'country_code' => 'ID',
                'pirep_filed' => 277,
                'flight_minutes' => 393687,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG104',
                'name' => 'Muhammad Fauzan',
                'country_code' => 'ID',
                'pirep_filed' => 246,
                'flight_minutes' => 383765,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG002',
                'name' => 'Aditprtm',
                'country_code' => 'ID',
                'pirep_filed' => 142,
                'flight_minutes' => 324841,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG174',
                'name' => 'I Gede Wirya',
                'country_code' => 'ID',
                'pirep_filed' => 288,
                'flight_minutes' => 303105,
                'rank_id' => 1,
            ],
            [
                'pilot_id' => 'GVG137',
                'name' => 'Darmawan',
                'country_code' => 'ID',
                'pirep_filed' => 673,
                'flight_minutes' => 293724,
                'rank_id' => 1,
            ],
        ];

        $this->db->table('pilots')->insertBatch($pilots);
    }
}
