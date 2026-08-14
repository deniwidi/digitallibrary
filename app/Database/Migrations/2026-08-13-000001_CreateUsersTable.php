<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateUsersTable
 * ---------------------------------------------------------------------
 * Membuat tabel `users` — akun yang boleh masuk ke panel admin.
 * Tabel ini dibuat PALING AWAL karena `transactions` merujuk ke sini.
 */
class CreateUsersTable extends Migration
{
    /**
     * Dijalankan saat `php spark migrate`.
     *
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
                'constraint' => 100,
                'comment'    => 'Nama lengkap, tampil di sidebar & topbar',
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Identitas saat login (unik)',
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Hash bcrypt dari password_hash()',
            ],
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'petugas'],
                'default'    => 'petugas',
            ],
            'foto' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);              // PRIMARY KEY
        $this->forge->addUniqueKey('username');        // tidak boleh kembar
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);      // true = IF NOT EXISTS
    }

    /**
     * Dijalankan saat `php spark migrate:rollback`.
     *
     * @return void
     */
    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}
