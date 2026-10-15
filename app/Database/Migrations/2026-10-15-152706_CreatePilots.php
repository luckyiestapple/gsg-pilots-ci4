<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePilots extends Migration
{
    public function up(): void
    {
        // Tabel countries
        $this->forge->addField([
            'country_code' => [
                'type'       => 'CHAR',
                'constraint' => 2,
                'null'       => false,
            ],
            'country_name' => [
                'type' => 'TEXT',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('country_code', true);
        $this->forge->createTable('countries');

        // Tabel ranks
        $this->forge->addField([
            'rank_id' => [
                'type'           => 'INT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'rank_name' => [
                'type' => 'TEXT',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('rank_id', true);
        $this->forge->createTable('ranks');

        // Tabel pilots
        $this->forge->addField([
            'pilot_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
            ],
            'name' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'country_code' => [
                'type'       => 'CHAR',
                'constraint' => 2,
                'null'       => false,
            ],
            'pirep_filed' => [
                'type' => 'INT',
                'null' => false,
            ],
            'flight_minutes' => [
                'type' => 'INT',
                'null' => false,
            ],
            'rank_id' => [
                'type' => 'INT',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('pilot_id', true);
        $this->forge->addKey('country_code');
        $this->forge->addKey('rank_id');

        $this->forge->addForeignKey(
            'country_code',
            'countries',
            'country_code',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->addForeignKey(
            'rank_id',
            'ranks',
            'rank_id',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->createTable('pilots');
    }

    public function down(): void
    {
        // Hapus tabel anak terlebih dahulu
        $this->forge->dropTable('pilots');
        $this->forge->dropTable('ranks');
        $this->forge->dropTable('countries');
    }
}
