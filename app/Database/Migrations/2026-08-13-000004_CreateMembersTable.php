<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateMembersTable
 * ---------------------------------------------------------------------
 * Membuat tabel `members` — data anggota perpustakaan (peminjam).
 */
class CreateMembersTable extends Migration
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
            'kode_anggota' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'ID Anggota unik, mis. AGT-0001',
            ],
            'nama'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'telepon' => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
            'alamat'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'jenis_kelamin' => [
                'type'       => 'ENUM',
                'constraint' => ['L', 'P'],
                'default'    => 'L',
            ],
            'tanggal_daftar' => [
                'type'    => 'DATE',
                'comment' => 'Dipakai widget "Anggota Baru Terdaftar"',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['aktif', 'nonaktif', 'diblokir'],
                'default'    => 'aktif',
            ],
            'foto'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_anggota');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('nama');    // percepat pencarian nama anggota
        $this->forge->addKey('status');  // percepat filter status
        $this->forge->createTable('members', true);
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->forge->dropTable('members', true);
    }
}
