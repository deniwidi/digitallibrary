<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateBooksTable
 * ---------------------------------------------------------------------
 * Membuat tabel `books` (katalog buku) beserta relasinya ke `categories`.
 *
 * Catatan desain:
 *  - `stok`          = jumlah eksemplar total milik perpustakaan
 *  - `stok_tersedia` = eksemplar yang masih ada di rak (berkurang saat
 *                      dipinjam, bertambah lagi saat dikembalikan)
 */
class CreateBooksTable extends Migration
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
            'kode_buku' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'Kode unik buku, mis. BK-0001',
            ],
            'judul'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'penulis'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'penerbit' => ['type' => 'VARCHAR', 'constraint' => 100],
            'tahun_terbit' => [
                'type'    => 'YEAR',
                'comment' => 'Tipe YEAR: rentang valid 1901-2155',
            ],
            'isbn' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'comment'    => 'FK -> categories.id',
            ],
            'stok' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'stok_tersedia' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'sampul' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Nama file di public/uploads/covers',
            ],
            'sinopsis' => ['type' => 'TEXT', 'null' => true],
            'rating'   => [
                'type'       => 'DECIMAL',
                'constraint' => '2,1',
                'default'    => 0.0,
                'comment'    => 'Rata-rata rating 0.0 - 5.0',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_buku');
        // Index non-unique: mempercepat WHERE ... LIKE pada fitur pencarian
        $this->forge->addKey('judul');
        $this->forge->addKey('penulis');

        // RESTRICT: kategori tidak boleh dihapus selama masih dipakai buku
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('books', true);
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->forge->dropTable('books', true);
    }
}
