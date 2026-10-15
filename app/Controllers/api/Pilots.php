<?php

namespace App\Controllers\Api;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\RESTful\ResourceController;

class Pilots extends ResourceController
{
    protected $modelName = 'App\Models\PilotModel';
    protected $format    = 'json';

    /**
     * Membaca body JSON.
     */
    private function getInput(): ?array
    {
        try {
            $input = $this->request->getJSON(true);
        } catch (HTTPException $e) {
            return null;
        }

        return is_array($input) && ! empty($input)
            ? $input
            : null;
    }

    /**
     * Field yang boleh dikirim melalui API.
     */
    private function allowedInput(array $input): array
    {
        return array_intersect_key($input, array_flip([
            'pilot_id',
            'name',
            'country_code',
            'rank_id',
            'pirep_filed',
            'flight_minutes',
        ]));
    }

    /**
     * Validasi data pilot.
     */
    private function validatePilot(
        array $input,
        bool $creating = true
    ): ?string {
        if ($creating && empty($input['pilot_id'])) {
            return 'pilot_id wajib diisi.';
        }

        if (
            $creating &&
            empty(trim((string) ($input['name'] ?? '')))
        ) {
            return 'name wajib diisi.';
        }

        if (
            $creating &&
            empty(trim((string) ($input['country_code'] ?? '')))
        ) {
            return 'country_code wajib diisi.';
        }

        if (
            $creating &&
            ! isset($input['rank_id'])
        ) {
            return 'rank_id wajib diisi.';
        }

        foreach (['pirep_filed', 'flight_minutes', 'rank_id'] as $field) {
            if (
                array_key_exists($field, $input) &&
                (
                    filter_var($input[$field], FILTER_VALIDATE_INT) === false ||
                    (int) $input[$field] < 0
                )
            ) {
                return "{$field} harus berupa bilangan bulat positif atau nol.";
            }
        }

        if (isset($input['pilot_id']) &&
            strlen(trim((string) $input['pilot_id'])) > 10) {
            return 'pilot_id maksimal 10 karakter.';
        }

        if (isset($input['name']) &&
            trim((string) $input['name']) === '') {
            return 'name tidak boleh kosong.';
        }

        if (isset($input['country_code']) &&
            strlen(trim((string) $input['country_code'])) !== 2) {
            return 'country_code harus terdiri dari 2 karakter.';
        }

        return null;
    }

    /**
     * Mengambil satu pilot beserta nama negara dan rank.
     */
    private function findPilot($id): ?array
    {
        $data = $this->model
            ->withRelations()
            ->where('pilots.pilot_id', $id)
            ->first();

        return $data ?: null;
    }

    /**
     * GET /api/pilots
     * GET /api/pilots?q=kata_kunci
     */
    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));

        $builder = $this->model->withRelations();

        if ($q !== '') {
            $builder->groupStart()
                ->like('pilots.pilot_id', $q)
                ->orLike('pilots.name', $q)
                ->orLike('countries.country_name', $q)
                ->orLike('pilots.country_code', $q)
                ->orLike('ranks.rank_name', $q)
                ->groupEnd();
        }

        $data = $builder
            ->orderBy('pilots.pilot_id', 'ASC')
            ->findAll();

        return $this->respond([
            'status'  => 200,
            'message' => 'Daftar pilot berhasil diambil.',
            'total'   => count($data),
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/pilots/{id}
     */
    public function show($id = null)
    {
        if ($id === null || trim((string) $id) === '') {
            return $this->fail('pilot_id wajib diisi.', 400);
        }

        $data = $this->findPilot($id);

        if (! $data) {
            return $this->failNotFound(
                "Pilot dengan ID {$id} tidak ditemukan."
            );
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Detail pilot berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * POST /api/pilots
     */
    public function create()
    {
        $input = $this->getInput();

        if ($input === null) {
            return $this->fail(
                'Body request harus berupa JSON dan tidak boleh kosong.',
                400
            );
        }

        $input = $this->allowedInput($input);

        $error = $this->validatePilot($input);

        if ($error !== null) {
            return $this->fail($error, 400);
        }

        $input['pilot_id'] = trim((string) $input['pilot_id']);
        $input['name'] = trim((string) $input['name']);
        $input['country_code'] = strtoupper(
            trim((string) $input['country_code'])
        );

        try {
            if (! $this->model->insert($input)) {
                return $this->failValidationErrors(
                    $this->model->errors()
                );
            }
        } catch (DatabaseException $e) {
            return $this->fail(
                'Pilot gagal ditambahkan. Periksa apakah pilot_id sudah digunakan dan country_code serta rank_id tersedia.',
                409
            );
        }

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Pilot berhasil ditambahkan.',
            'data'    => $this->findPilot($input['pilot_id']),
        ]);
    }

    /**
     * PUT /api/pilots/{id}
     * PATCH /api/pilots/{id}
     */
    public function update($id = null)
    {
        if ($id === null || trim((string) $id) === '') {
            return $this->fail('pilot_id wajib diisi.', 400);
        }

        $existing = $this->model->find($id);

        if (! $existing) {
            return $this->failNotFound(
                "Pilot dengan ID {$id} tidak ditemukan."
            );
        }

        $input = $this->getInput();

        if ($input === null) {
            return $this->fail(
                'Body request harus berupa JSON dan tidak boleh kosong.',
                400
            );
        }

        // ID pilot tidak diubah melalui endpoint ini.
        unset($input['pilot_id']);

        $input = $this->allowedInput($input);

        if (empty($input)) {
            return $this->fail(
                'Tidak ada field valid yang dikirim untuk diperbarui.',
                400
            );
        }

        $error = $this->validatePilot($input, false);

        if ($error !== null) {
            return $this->fail($error, 400);
        }

        if (isset($input['name'])) {
            $input['name'] = trim((string) $input['name']);
        }

        if (isset($input['country_code'])) {
            $input['country_code'] = strtoupper(
                trim((string) $input['country_code'])
            );
        }

        try {
            if (! $this->model->update($id, $input)) {
                return $this->failValidationErrors(
                    $this->model->errors()
                );
            }
        } catch (DatabaseException $e) {
            return $this->fail(
                'Pilot gagal diperbarui. Periksa country_code dan rank_id yang dikirim.',
                409
            );
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Pilot berhasil diperbarui.',
            'data'    => $this->findPilot($id),
        ]);
    }

    /**
     * DELETE /api/pilots/{id}
     */
    public function delete($id = null)
    {
        if ($id === null || trim((string) $id) === '') {
            return $this->fail('pilot_id wajib diisi.', 400);
        }

        if (! $this->model->find($id)) {
            return $this->failNotFound(
                "Pilot dengan ID {$id} tidak ditemukan."
            );
        }

        try {
            if (! $this->model->delete($id)) {
                return $this->fail(
                    'Pilot gagal dihapus.',
                    500
                );
            }
        } catch (DatabaseException $e) {
            return $this->fail(
                'Pilot tidak dapat dihapus karena terjadi masalah pada database.',
                409
            );
        }

        return $this->respondDeleted([
            'status'  => 200,
            'message' => "Pilot dengan ID {$id} berhasil dihapus.",
        ]);
    }
}
