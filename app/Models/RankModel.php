<?php

namespace App\Models;

use CodeIgniter\Model;

class RankModel extends Model
{
    protected $table            = 'ranks';
    protected $primaryKey       = 'rank_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['rank_name'];
}
