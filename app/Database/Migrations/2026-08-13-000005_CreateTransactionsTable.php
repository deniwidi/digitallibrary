<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateTransactionsTable
 * ---------------------------------------------------------------------
 * Membuat tabel `transactions` — inti modul Peminjaman & Pengembalian.
 *
 * Satu baris = satu eksemplar buku yang dipinjam oleh satu anggota.
 * `tanggal_kembali` NULL artinya buku masih berada di tangan anggota,
 * sehingga query "sedang dipinjam" cukup memeriksa kolom tersebut.
 *
 * Tabel ini dibuat TERAKHIR karena bergantung pada members, books, users.
 */
class CreateTransactionsTable extends Migration
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
            'kode_transaksi' => [
                'type'       => 'VARCHAR',
                'constraint' => 25,
                'comment'    => 'Kode unik, mis. TRX-20260813-0001',
            ],
            'member_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'comment'    => 'FK -> members.id (peminjam)',
            ],
            'book_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'comment'    => 'FK -> books.id (buku yang dipinjam)',
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'FK -> users.id (petugas pencatat)',
            ],
            'tanggal_pinjam'      => ['type' => 'DATE'],
            'tanggal_jatuh_tempo' => [
                'type'    => 'DATE',
                'comment' => 'Lewat tanggal ini dihitung denda',
            ],
            'tanggal_kembali' => [
                'type'    => 'DATE',
                'null'    => true,
                'comment' => 'NULL = belum dikembalikan',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['dipinjam', 'dikembalikan', 'terlambat', 'hilang'],
                'default'    => 'dipinjam',
            ],
            'denda' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => 'Nominal rupiah hasil hitung keterlambatan',
            ],
            'catatan'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_transaksi');
        $this->forge->addKey('status');          // percepat filter dashboard
        $this->forge->addKey('tanggal_pinjam');  // percepat laporan per rentang tanggal

        // RESTRICT pada member & book supaya histori transaksi tidak hilang
        $this->forge->addForeignKey('member_id', 'members', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('book_id', 'books', 'id', 'CASCADE', 'RESTRICT');
        // SET NULL: akun petugas boleh dihapus, transaksinya tetap tersimpan
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');

        $this->forge->createTable('transactions', true);
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->forge->dropTable('transactions', true);
    }
}
