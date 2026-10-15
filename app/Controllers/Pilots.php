<?php

namespace App\Controllers;

use App\Models\PilotModel;

class Pilots extends BaseController
{
    // GET /dbtest -> cek koneksi database
    public function dbtest()
    {
        try {
            $db = \Config\Database::connect();

            $info   = $db->query('SELECT current_database() AS db, current_schema() AS skema, inet_server_port() AS port')->getRowArray();
            $tables = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")->getResultArray();

            return $this->response->setJSON([
                'connected' => true,
                'info'      => $info,
                'tables'    => array_column($tables, 'table_name'),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'connected' => false,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // GET /pilots?country=ID&q=adit&limit=3
    public function index()
    {
        $model = new PilotModel();
        $query = $model->withRelations();

        $country = $this->request->getGet('country');
        $keyword = $this->request->getGet('q');
        $limit   = (int) $this->request->getGet('limit');

        if ($country) {
            $query->where('pilots.country_code', strtoupper($country));
        }

        if ($keyword) {
            $query->groupStart()
                ->like('pilots.name', $keyword, 'both', null, true)
                ->orLike('pilots.pilot_id', $keyword, 'both', null, true)
                ->groupEnd();
        }

        $data = $query->orderBy('pilots.flight_minutes', 'DESC')
                      ->findAll($limit > 0 ? $limit : 0);

        return $this->response->setJSON($data);
    }

    // GET /pilots/GVG024
    public function show($id)
    {
        $pilot = (new PilotModel())
            ->withRelations()
            ->where('pilots.pilot_id', strtoupper($id))
            ->first();

        if (! $pilot) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Pilot tidak ditemukan']);
        }

        return $this->response->setJSON($pilot);
    }
}