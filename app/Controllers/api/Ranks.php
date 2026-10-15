<?php

namespace App\Controllers\Api;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\RESTful\ResourceController;

class Ranks extends ResourceController
{
    protected $modelName = 'App\Models\RankModel';
    protected $format    = 'json';

    private function idFirst(array $rank): array
    {
        $id = $rank['id'];
        unset($rank['id']);

        return ['id' => $id] + $rank;
    }

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
     * GET /api/ranks
     * GET /api/ranks?q=captain
     *
     * Mengambil semua rank atau mencari berdasarkan ID/nama.
     */
    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));

        $builder = $this->model;

        if ($q !== '') {
            $builder->groupStart()
                ->like('id', $q)
                ->orLike('rank_name', $q)
                ->groupEnd();
        }

        $data = $builder
            ->select('id, rank_name')
            ->orderBy('id', 'ASC')
            ->findAll();
        $data = array_map($this->idFirst(...), $data);

        return $this->respond([
            'status'  => 200,
            'message' => 'Daftar rank berhasil diambil.',
            'total'   => count($data),
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/ranks/{id}
     */
    public function show($id = null)
    {
        if (
            $id === null ||
            filter_var($id, FILTER_VALIDATE_INT) === false ||
            (int) $id < 1
        ) {
            return $this->fail(
                'id harus berupa bilangan bulat positif.',
                400
            );
        }

        $data = $this->model
            ->select('id, rank_name')
            ->find((int) $id);

        if (! $data) {
            return $this->failNotFound(
                "Rank dengan ID {$id} tidak ditemukan."
            );
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Detail rank berhasil diambil.',
            'data'    => $this->idFirst($data),
        ]);
    }

    /**
     * POST /api/ranks
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

        // ID dibuat otomatis oleh database.
        $rankName = trim((string) ($input['rank_name'] ?? ''));

        if ($rankName === '') {
            return $this->fail(
                'rank_name wajib diisi.',
                400
            );
        }

        // Abaikan field lain yang tidak diperlukan.
        $data = [
            'rank_name' => $rankName,
        ];

        if (! $this->model->insert($data)) {
            return $this->failValidationErrors(
                $this->model->errors()
            );
        }

        $id = $this->model->getInsertID();

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Rank berhasil ditambahkan.',
            'data'    => $this->idFirst(
                $this->model->select('id, rank_name')->find($id)
            ),
        ]);
    }

    /**
     * PUT /api/ranks/{id}
     * PATCH /api/ranks/{id}
     */
    public function update($id = null)
    {
        if (
            $id === null ||
            filter_var($id, FILTER_VALIDATE_INT) === false ||
            (int) $id < 1
        ) {
            return $this->fail(
                'id harus berupa bilangan bulat positif.',
                400
            );
        }

        $id = (int) $id;

        if (! $this->model->select('id')->find($id)) {
            return $this->failNotFound(
                "Rank dengan ID {$id} tidak ditemukan."
            );
        }

        $input = $this->getInput();

        if ($input === null) {
            return $this->fail(
                'Body request harus berupa JSON dan tidak boleh kosong.',
                400
            );
        }

        $rankName = trim((string) ($input['rank_name'] ?? ''));

        if ($rankName === '') {
            return $this->fail(
                'rank_name wajib diisi dan tidak boleh kosong.',
                400
            );
        }

        // Hanya rank_name yang boleh diperbarui.
        if (! $this->model->update($id, [
            'rank_name' => $rankName,
        ])) {
            return $this->failValidationErrors(
                $this->model->errors()
            );
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Rank berhasil diperbarui.',
            'data'    => $this->idFirst(
                $this->model->select('id, rank_name')->find($id)
            ),
        ]);
    }

    /**
     * DELETE /api/ranks/{id}
     */
    public function delete($id = null)
    {
        if (
            $id === null ||
            filter_var($id, FILTER_VALIDATE_INT) === false ||
            (int) $id < 1
        ) {
            return $this->fail(
                'id harus berupa bilangan bulat positif.',
                400
            );
        }

        $id = (int) $id;

        if (! $this->model->select('id')->find($id)) {
            return $this->failNotFound(
                "Rank dengan ID {$id} tidak ditemukan."
            );
        }

        try {
            if (! $this->model->delete($id)) {
                return $this->fail(
                    'Rank gagal dihapus.',
                    500
                );
            }
        } catch (DatabaseException $e) {
            return $this->fail(
                'Rank tidak dapat dihapus karena mungkin masih digunakan oleh data pilot.',
                409
            );
        }

        return $this->respondDeleted([
            'status'  => 200,
            'message' => "Rank dengan ID {$id} berhasil dihapus.",
        ]);
    }
}
