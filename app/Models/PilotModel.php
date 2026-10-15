<?php

namespace App\Models;

use CodeIgniter\Model;

class PilotModel extends Model
{
    protected $table            = 'pilots';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'pilot_id',
        'name',
        'country_id',
        'rank_id',
        'pirep_filed',
        'flight_minutes',
    ];

    /**
     * Pilot + nama negara + nama rank (JOIN ke countries dan ranks).
     * Bisa disambung dengan ->orderBy(), ->like(), ->findAll(), dst.
     */
    public function withRelations(): static
    {
        return $this->select(
            'pilots.id, pilots.pilot_id, pilots.name, pilots.country_id, '
                . 'pilots.rank_id, pilots.pirep_filed, pilots.flight_minutes, '
                . 'countries.country_code, countries.country_name, ranks.rank_name'
        )
            ->join('countries', 'countries.id = pilots.country_id', 'left')
            ->join('ranks', 'ranks.id = pilots.rank_id', 'left');
    }
}
