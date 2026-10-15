<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\RESTful\ResourceController;

class Countries extends ResourceController
{
    protected $modelName = 'App\Models\CountryModel';
    protected $format    = 'json';

    /**
     * Membaca body JSON.
     * Mengembalikan null jika JSON kosong atau tidak valid.
     */
    private function getInput(): ?array
    {
        try {
            $input = $this->request->getJSON(true);
        } catch (HTTPException $e) {
            return null;
        }

        if (! is_array($input) || empty($input)) {
            return null;
        }

        return $input;
    }

    /**
     * GET /api/countries
     * GET /api/countries?q=indo
     *
     * Menampilkan semua negara atau mencari berdasarkan
     * country_code dan country_name.
     */
    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));

        $builder = $this->model;

        if ($q !== '') {
            $builder->groupStart()
                ->like('country_code', $q)
                ->orLike('country_name', $q)
                ->groupEnd();
        }

        $data = $builder
            ->orderBy('country_name', 'ASC')
            ->findAll();

        return $this->respond([
            'status'  => 200,
            'message' => 'Daftar negara berhasil diambil.',
            'total'   => count($data),
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/countries/{id}
     *
     * ID menggunakan country_code, misalnya ID atau US.
     */
    public function show($id = null)
    {
        if ($id === null || trim((string) $id) === '') {
            return $this->fail(
                'Kode negara wajib diisi.',
                400
            );
        }

        $data = $this->model->find($id);

        if (! $data) {
            return $this->failNotFound(
                "Negara dengan kode {$id} tidak ditemukan."
            );
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Detail negara berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * POST /api/countries
     *
     * Menambahkan negara baru.
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

        // Hanya izinkan field yang digunakan tabel countries.
        $input = array_intersect_key($input, array_flip([
            'country_code',
            'country_name',
        ]));

        if (
            ! isset($input['country_code']) ||
            ! isset($input['country_name']) ||
            trim((string) $input['country_code']) === '' ||
            trim((string) $input['country_name']) === ''
        ) {
            return $this->fail(
                'country_code dan country_name wajib diisi.',
                400
            );
        }

        $input['country_code'] = strtoupper(
            trim((string) $input['country_code'])
        );

        $input['country_name'] = trim(
            (string) $input['country_name']
        );

        if (! $this->model->insert($input)) {
            return $this->failValidationErrors(
                $this->model->errors()
            );
        }

        $code = $input['country_code'];

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Negara berhasil ditambahkan.',
            'data'    => $this->model->find($code),
        ]);
    }

    /**
     * PUT /api/countries/{id}
     * PATCH /api/countries/{id}
     *
     * Memperbarui data negara.
     * country_code sebagai primary key tidak dapat diubah.
     */
    public function update($id = null)
    {
        if ($id === null || trim((string) $id) === '') {
            return $this->fail(
                'Kode negara wajib diisi.',
                400
            );
        }

        $existing = $this->model->find($id);

        if (! $existing) {
            return $this->failNotFound(
                "Negara dengan kode {$id} tidak ditemukan."
            );
        }

        $input = $this->getInput();

        if ($input === null) {
            return $this->fail(
                'Body request harus berupa JSON dan tidak boleh kosong.',
                400
            );
        }

        // Jangan izinkan perubahan primary key.
        unset($input['country_code']);

        // Abaikan field yang tidak dikenal.
        $input = array_intersect_key($input, array_flip([
            'country_name',
        ]));

        if (empty($input)) {
            return $this->fail(
                'Masukkan country_name yang ingin diperbarui.',
                400
            );
        }

        if (isset($input['country_name'])) {
            $input['country_name'] = trim(
                (string) $input['country_name']
            );

            if ($input['country_name'] === '') {
                return $this->fail(
                    'country_name tidak boleh kosong.',
                    400
                );
            }
        }

        if (! $this->model->update($id, $input)) {
            return $this->failValidationErrors(
                $this->model->errors()
            );
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Negara berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    /**
     * DELETE /api/countries/{id}
     *
     * Menghapus negara berdasarkan country_code.
     */
    public function delete($id = null)
    {
        if ($id === null || trim((string) $id) === '') {
            return $this->fail(
                'Kode negara wajib diisi.',
                400
            );
        }

        if (! $this->model->find($id)) {
            return $this->failNotFound(
                "Negara dengan kode {$id} tidak ditemukan."
            );
        }

        try {
            $deleted = $this->model->delete($id);

            if (! $deleted) {
                return $this->fail(
                    'Negara gagal dihapus.',
                    500
                );
            }
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // Negara mungkin masih direferensikan oleh tabel pilots.
            return $this->fail(
                'Negara tidak dapat dihapus. Pastikan negara ini tidak digunakan oleh data pilot.',
                409
            );
        }

        return $this->respondDeleted([
            'status'  => 200,
            'message' => "Negara dengan kode {$id} berhasil dihapus.",
        ]);
    }
}
