<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeEntityIds extends Migration
{
    public function up(): void
    {
        $this->dropForeignKeysForColumn('pilots', 'country_code');
        $this->dropForeignKeysForColumn('pilots', 'rank_id');

        if ($this->db->DBDriver === 'Postgre') {
            $this->upPostgre();
        } else {
            $this->upMySQL();
        }
    }

    public function down(): void
    {
        $this->dropForeignKeysForColumn('pilots', 'country_id');
        $this->dropForeignKeysForColumn('pilots', 'rank_id');

        if ($this->db->DBDriver === 'Postgre') {
            $this->downPostgre();
        } else {
            $this->downMySQL();
        }
    }

    private function upMySQL(): void
    {
        $this->db->query(
            'ALTER TABLE countries
                DROP PRIMARY KEY,
                ADD COLUMN id INT NOT NULL AUTO_INCREMENT FIRST,
                ADD PRIMARY KEY (id),
                ADD UNIQUE KEY countries_country_code_unique (country_code)'
        );
        $this->db->query(
            'ALTER TABLE ranks
                CHANGE rank_id id INT NOT NULL AUTO_INCREMENT'
        );
        $this->db->query(
            'ALTER TABLE pilots
                ADD COLUMN country_id INT NULL AFTER pilot_id'
        );
        $this->db->query(
            'UPDATE pilots
                INNER JOIN countries ON countries.country_code = pilots.country_code
                SET pilots.country_id = countries.id'
        );
        $this->db->query(
            'ALTER TABLE pilots
                DROP PRIMARY KEY,
                ADD COLUMN id INT NOT NULL AUTO_INCREMENT FIRST,
                ADD PRIMARY KEY (id),
                ADD UNIQUE KEY pilots_pilot_id_unique (pilot_id),
                ADD KEY pilots_country_id_index (country_id),
                DROP COLUMN country_code,
                MODIFY country_id INT NOT NULL'
        );
        $this->addCurrentForeignKeys();
    }

    private function upPostgre(): void
    {
        $this->db->query('ALTER TABLE countries ADD COLUMN id SERIAL');
        $this->db->query(
            'ALTER TABLE countries
                DROP CONSTRAINT pk_countries,
                ADD CONSTRAINT pk_countries_id PRIMARY KEY (id),
                ADD CONSTRAINT countries_country_code_unique UNIQUE (country_code)'
        );
        $this->db->query('ALTER TABLE ranks RENAME COLUMN rank_id TO id');
        $this->db->query('ALTER TABLE pilots ADD COLUMN country_id INTEGER');
        $this->db->query(
            'UPDATE pilots
                SET country_id = countries.id
                FROM countries
                WHERE countries.country_code = pilots.country_code'
        );
        $this->db->query('ALTER TABLE pilots ALTER COLUMN country_id SET NOT NULL');
        $this->db->query('ALTER TABLE pilots ADD COLUMN id SERIAL');
        $this->db->query(
            'ALTER TABLE pilots
                DROP CONSTRAINT pk_pilots,
                ADD CONSTRAINT pk_pilots_id PRIMARY KEY (id),
                ADD CONSTRAINT pilots_pilot_id_unique UNIQUE (pilot_id),
                DROP COLUMN country_code'
        );
        $this->addCurrentForeignKeys();
    }

    private function addCurrentForeignKeys(): void
    {
        $this->db->query(
            'ALTER TABLE pilots
                ADD CONSTRAINT fk_pilots_country_id
                    FOREIGN KEY (country_id) REFERENCES countries (id)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                ADD CONSTRAINT fk_pilots_rank_id
                    FOREIGN KEY (rank_id) REFERENCES ranks (id)
                    ON UPDATE CASCADE ON DELETE RESTRICT'
        );
    }

    private function downMySQL(): void
    {
        $this->db->query(
            'ALTER TABLE pilots
                ADD COLUMN country_code CHAR(2) NULL AFTER pilot_id'
        );
        $this->db->query(
            'UPDATE pilots
                INNER JOIN countries ON countries.id = pilots.country_id
                SET pilots.country_code = countries.country_code'
        );
        $this->db->query(
            'ALTER TABLE pilots
                DROP PRIMARY KEY,
                DROP INDEX pilots_pilot_id_unique,
                DROP INDEX pilots_country_id_index,
                ADD PRIMARY KEY (pilot_id),
                DROP COLUMN id,
                DROP COLUMN country_id,
                MODIFY country_code CHAR(2) NOT NULL'
        );

        $this->db->query(
            'ALTER TABLE ranks
                CHANGE id rank_id INT NOT NULL AUTO_INCREMENT'
        );

        $this->db->query(
            'ALTER TABLE countries
                DROP PRIMARY KEY,
                DROP INDEX countries_country_code_unique,
                DROP COLUMN id,
                ADD PRIMARY KEY (country_code)'
        );

        $this->db->query(
            'ALTER TABLE pilots
                ADD CONSTRAINT fk_pilots_country_code
                    FOREIGN KEY (country_code) REFERENCES countries (country_code)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                ADD CONSTRAINT fk_pilots_rank_id
                    FOREIGN KEY (rank_id) REFERENCES ranks (rank_id)
                    ON UPDATE CASCADE ON DELETE RESTRICT'
        );
    }

    private function downPostgre(): void
    {
        $this->db->query('ALTER TABLE pilots ADD COLUMN country_code CHAR(2)');
        $this->db->query(
            'UPDATE pilots
                SET country_code = countries.country_code
                FROM countries
                WHERE countries.id = pilots.country_id'
        );
        $this->db->query(
            'ALTER TABLE pilots
                DROP CONSTRAINT pk_pilots_id,
                DROP CONSTRAINT pilots_pilot_id_unique,
                ADD CONSTRAINT pk_pilots PRIMARY KEY (pilot_id),
                DROP COLUMN id,
                DROP COLUMN country_id,
                ALTER COLUMN country_code SET NOT NULL'
        );
        $this->db->query('ALTER TABLE ranks RENAME COLUMN id TO rank_id');
        $this->db->query(
            'ALTER TABLE countries
                DROP CONSTRAINT pk_countries_id,
                DROP CONSTRAINT countries_country_code_unique,
                ADD CONSTRAINT pk_countries PRIMARY KEY (country_code),
                DROP COLUMN id'
        );
        $this->db->query(
            'ALTER TABLE pilots
                ADD CONSTRAINT fk_pilots_country_code
                    FOREIGN KEY (country_code) REFERENCES countries (country_code)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                ADD CONSTRAINT fk_pilots_rank_id
                    FOREIGN KEY (rank_id) REFERENCES ranks (rank_id)
                    ON UPDATE CASCADE ON DELETE RESTRICT'
        );
    }

    private function dropForeignKeysForColumn(string $table, string $column): void
    {
        if ($this->db->DBDriver === 'Postgre' && $table === 'pilots') {
            $constraints = [
                'country_code' => 'pilots_country_code_foreign',
                'rank_id'      => 'pilots_rank_id_foreign',
                'country_id'   => 'fk_pilots_country_id',
            ];

            if (isset($constraints[$column])) {
                $this->forge->dropForeignKey($table, $constraints[$column]);
            }

            return;
        }

        foreach ($this->db->getForeignKeyData($table) as $foreignKey) {
            if (in_array($column, (array) $foreignKey->column_name, true)) {
                $this->forge->dropForeignKey($table, $foreignKey->constraint_name);
            }
        }
    }
}
