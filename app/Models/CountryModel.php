<?php

namespace App\Models;

use CodeIgniter\Model;

class CountryModel extends Model
{
    protected $table            = 'countries';
    protected $primaryKey       = 'country_code';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['country_code', 'country_name'];
    protected $validationRules = [
        'country_code' => 'required|alpha|exact_length[2]|',
        'country_name' => 'required',
    ];
 }