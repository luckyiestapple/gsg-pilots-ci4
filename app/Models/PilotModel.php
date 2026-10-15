<?php

namespace App\Models;

use CodeIgniter\Model;

class PilotModel extends Model
{
    protected $table            = 'pilots';
    protected $primaryKey       = 'pilot_id';
    protected $useAutoIncrement = false; // pilot_id berupa teks, misalnya GVG024
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'pilot_id',
        'name',
        'country_code',
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
        return $this->select('pilots.*, countries.country_name, ranks.rank_name')
            ->join('countries', 'countries.country_code = pilots.country_code', 'left')
            ->join('ranks', 'ranks.rank_id = pilots.rank_id', 'left');
    }
}
