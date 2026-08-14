<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateCategoriesTable
 * ---------------------------------------------------------------------
 * Membuat tabel `categories` — master kategori buku.
 * Harus dibuat SEBELUM `books` karena menjadi tabel induk (parent) FK.
 */
class CreateCategoriesTable extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'comment'    => 'Nama kategori, mis. Novel / Teknologi',
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'comment'    => 'Versi URL-friendly dari nama',
            ],
            'deskripsi' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('categories', true);
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->forge->dropTable('categories', true);
    }
}
