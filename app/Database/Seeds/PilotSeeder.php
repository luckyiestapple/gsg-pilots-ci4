<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PilotSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['country_code' => 'IT', 'country_name' => 'Italy'],
            ['country_code' => 'ID', 'country_name' => 'Indonesia'],
            ['country_code' => 'DE', 'country_name' => 'Germany'],
        ];
        $this->db->table('countries')->insertBatch($countries);

        $countryIds = array_column(
            $this->db->table('countries')->select('id, country_code')->get()->getResultArray(),
            'id',
            'country_code'
        );

        $this->db->table('ranks')->insert([
            'rank_name' => 'Garuda Supreme',
        ]);
        $rankId = (int) $this->db->insertID();

        $pilots = [
            ['GVG024', 'Raiii', 'IT', 1096, 961255],
            ['GVG114', 'Rulizal Al Hafizh', 'ID', 1205, 574190],
            ['GVG122', 'Farel Akasya', 'ID', 206, 522750],
            ['GVG132', 'Mochamad fachri', 'ID', 368, 403526],
            ['GVG008', 'Astralis', 'DE', 161, 401444],
            ['GVG012', 'Fadzlan Ferdiansyah', 'ID', 277, 393687],
            ['GVG104', 'Muhammad Fauzan', 'ID', 246, 383765],
            ['GVG002', 'Aditprtm', 'ID', 142, 324841],
            ['GVG174', 'I Gede Wirya', 'ID', 288, 303105],
            ['GVG137', 'Darmawan', 'ID', 673, 293724],
        ];

        $rows = [];
        foreach ($pilots as [$pilotId, $name, $countryCode, $pireps, $minutes]) {
            $rows[] = [
                'pilot_id'       => $pilotId,
                'name'           => $name,
                'country_id'     => (int) $countryIds[$countryCode],
                'pirep_filed'    => $pireps,
                'flight_minutes' => $minutes,
                'rank_id'        => $rankId,
            ];
        }

        $this->db->table('pilots')->insertBatch($rows);
    }
}
