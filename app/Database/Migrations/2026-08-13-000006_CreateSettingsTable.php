<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateSettingsTable
 * ---------------------------------------------------------------------
 * Tabel key-value untuk menu "Pengaturan": nama aplikasi, tarif denda
 * per hari, lama pinjam default, dan batas jumlah pinjam per anggota.
 *
 * Nilai `denda_per_hari` dipakai modul Pengembalian sebagai pengali saat
 * menghitung keterlambatan, jadi tarif bisa diubah tanpa menyentuh kode.
 */
class CreateSettingsTable extends Migration
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
            'key_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Kunci pengaturan, mis. denda_per_hari',
            ],
            'value'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key_name');
        $this->forge->createTable('settings', true);
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->forge->dropTable('settings', true);
    }
}
