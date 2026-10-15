<?php

namespace App\Controllers\Api;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\RESTful\ResourceController;

class Countries extends ResourceController
{
    protected $modelName = 'App\Models\CountryModel';
    protected $format    = 'json';

    private function parseId($id): ?int
    {
        if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
            return null;
        }

        return (int) $id;
    }

    private function idFirst(array $country): array
    {
        $id = $country['id'];
        unset($country['id']);

        return ['id' => $id] + $country;
    }

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
     * Menampilkan semua negara atau mencari berdasarkan id, kode, dan nama.
     */
    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));

        $builder = $this->model;

        if ($q !== '') {
            $builder->groupStart()
                ->like('id', $q)
                ->orLike('country_code', $q)
                ->orLike('country_name', $q)
                ->groupEnd();
        }

        $data = $builder
            ->select('id, country_code, country_name')
            ->orderBy('country_name', 'ASC')
            ->findAll();
        $data = array_map($this->idFirst(...), $data);

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
     * ID menggunakan primary key angka.
     */
    public function show($id = null)
    {
        $id = $this->parseId($id);

        if ($id === null) {
            return $this->fail('id harus berupa bilangan bulat positif.', 400);
        }

        $data = $this->model
            ->select('id, country_code, country_name')
            ->find($id);

        if (! $data) {
            return $this->failNotFound(
                "Negara dengan ID {$id} tidak ditemukan."
            );
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Detail negara berhasil diambil.',
            'data'    => $this->idFirst($data),
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

        if (
            strlen($input['country_code']) !== 2 ||
            ! ctype_alpha($input['country_code'])
        ) {
            return $this->fail(
                'country_code harus terdiri dari 2 huruf.',
                400
            );
        }

        $input['country_name'] = trim(
            (string) $input['country_name']
        );

        try {
            if (! $this->model->insert($input)) {
                return $this->failValidationErrors(
                    $this->model->errors()
                );
            }
        } catch (DatabaseException $e) {
            return $this->fail(
                'Negara gagal ditambahkan. country_code mungkin sudah digunakan.',
                409
            );
        }

        $id = (int) $this->model->getInsertID();

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Negara berhasil ditambahkan.',
            'data'    => $this->idFirst(
                $this->model->select('id, country_code, country_name')->find($id)
            ),
        ]);
    }

    /**
     * PUT /api/countries/{id}
     * PATCH /api/countries/{id}
     *
     * Memperbarui data negara.
     * ID dan country_code tidak dapat diubah.
     */
    public function update($id = null)
    {
        $id = $this->parseId($id);

        if ($id === null) {
            return $this->fail('id harus berupa bilangan bulat positif.', 400);
        }

        $existing = $this->model
            ->select('id, country_code, country_name')
            ->find($id);

        if (! $existing) {
            return $this->failNotFound(
                "Negara dengan ID {$id} tidak ditemukan."
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
        unset($input['id'], $input['country_code']);

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
            'data'    => $this->idFirst(
                $this->model->select('id, country_code, country_name')->find($id)
            ),
        ]);
    }

    /**
     * DELETE /api/countries/{id}
     *
     * Menghapus negara berdasarkan id.
     */
    public function delete($id = null)
    {
        $id = $this->parseId($id);

        if ($id === null) {
            return $this->fail('id harus berupa bilangan bulat positif.', 400);
        }

        if (! $this->model->select('id')->find($id)) {
            return $this->failNotFound(
                "Negara dengan ID {$id} tidak ditemukan."
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
                'Negara tidak dapat dihapus karena masih digunakan oleh data pilot.',
                409
            );
        }

        return $this->respondDeleted([
            'status'  => 200,
            'message' => "Negara dengan ID {$id} berhasil dihapus.",
        ]);
    }
}
