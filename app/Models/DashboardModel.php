<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * DashboardModel
 * ---------------------------------------------------------------------
 * Khusus menyediakan data agregat untuk halaman Dashboard: kartu
 * statistik, grafik bulanan, tabel peminjaman terbaru, buku terpopuler,
 * dan daftar anggota baru.
 *
 * Model ini tidak melakukan CRUD — operasi tulis ditangani model masing-
 * masing modul (BookModel, MemberModel, TransactionModel).
 *
 * Seluruh query dibangun memakai Query Builder sehingga nilai yang masuk
 * otomatis di-escape (prepared statement) => aman dari SQL Injection.
 */
class DashboardModel extends Model
{
    protected $table      = 'transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /**
     * Kumpulkan seluruh angka untuk empat kartu ringkasan sekaligus.
     *
     * @return array{total_buku:int, anggota_aktif:int, buku_dipinjam:int, buku_terlambat:int}
     */
    public function ringkasan(): array
    {
        return [
            'total_buku'     => $this->totalBuku(),
            'anggota_aktif'  => $this->anggotaAktif(),
            'buku_dipinjam'  => $this->bukuDipinjam(),
            'buku_terlambat' => $this->bukuTerlambat(),
        ];
    }

    /**
     * Jumlah judul buku yang masih aktif (belum di-soft-delete).
     *
     * @return int
     */
    public function totalBuku(): int
    {
        return $this->db->table('books')
            ->where('deleted_at', null)
            ->countAllResults();
    }

    /**
     * Jumlah anggota berstatus 'aktif'.
     *
     * @return int
     */
    public function anggotaAktif(): int
    {
        return $this->db->table('members')
            ->where('status', 'aktif')
            ->where('deleted_at', null)
            ->countAllResults();
    }

    /**
     * Jumlah eksemplar yang sedang berada di tangan anggota.
     * Ditandai oleh tanggal_kembali yang masih NULL.
     *
     * @return int
     */
    public function bukuDipinjam(): int
    {
        return $this->db->table('transactions')
            ->where('tanggal_kembali', null)
            ->countAllResults();
    }

    /**
     * Jumlah pinjaman yang belum kembali DAN sudah lewat jatuh tempo.
     *
     * @return int
     */
    public function bukuTerlambat(): int
    {
        return $this->db->table('transactions')
            ->where('tanggal_kembali', null)
            ->where('tanggal_jatuh_tempo <', date('Y-m-d'))
            ->countAllResults();
    }

    /**
     * Data grafik "Ringkasan Peminjaman Bulanan".
     *
     * Cara kerja:
     *  1. Bangun dulu kerangka N bulan terakhir berisi nilai 0, supaya
     *     bulan tanpa transaksi tetap muncul di grafik (tidak bolong).
     *  2. Ambil hasil GROUP BY dari database, lalu timpakan ke kerangka.
     *
     * @param  int $jumlahBulan Berapa bulan ke belakang yang ditampilkan
     * @return array{labels:list<string>, peminjaman:list<int>, pengembalian:list<int>}
     */
    public function grafikBulanan(int $jumlahBulan = 6): array
    {
        // Batasi input supaya tidak ada permintaan rentang tak masuk akal
        $jumlahBulan = max(1, min(12, $jumlahBulan));

        $namaBulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        // 1) Kerangka bulan: kunci 'YYYY-MM' => nilai 0
        $kerangka = [];
        $labels   = [];

        for ($i = $jumlahBulan - 1; $i >= 0; $i--) {
            $waktu               = strtotime("-{$i} month", strtotime(date('Y-m-01')));
            $kunci               = date('Y-m', $waktu);
            $kerangka[$kunci]    = 0;
            $labels[]            = $namaBulan[(int) date('n', $waktu)];
        }

        // Tanggal awal rentang = hari pertama bulan paling lama
        $tanggalAwal = array_key_first($kerangka) . '-01';

        $peminjaman   = $kerangka;
        $pengembalian = $kerangka;

        // 2a) Hitung peminjaman per bulan berdasarkan tanggal_pinjam
        $barisPinjam = $this->db->table('transactions')
            ->select("DATE_FORMAT(tanggal_pinjam, '%Y-%m') AS bulan, COUNT(id) AS jumlah")
            ->where('tanggal_pinjam >=', $tanggalAwal)
            ->groupBy('bulan')
            ->get()
            ->getResultArray();

        foreach ($barisPinjam as $baris) {
            // isset() menjaga agar data di luar rentang kerangka diabaikan
            if (isset($peminjaman[$baris['bulan']])) {
                $peminjaman[$baris['bulan']] = (int) $baris['jumlah'];
            }
        }

        // 2b) Hitung pengembalian per bulan berdasarkan tanggal_kembali
        $barisKembali = $this->db->table('transactions')
            ->select("DATE_FORMAT(tanggal_kembali, '%Y-%m') AS bulan, COUNT(id) AS jumlah")
            ->where('tanggal_kembali >=', $tanggalAwal)
            ->groupBy('bulan')
            ->get()
            ->getResultArray();

        foreach ($barisKembali as $baris) {
            if (isset($pengembalian[$baris['bulan']])) {
                $pengembalian[$baris['bulan']] = (int) $baris['jumlah'];
            }
        }

        return [
            'labels'       => $labels,
            // array_values() membuang kunci 'YYYY-MM' agar siap dipakai Chart.js
            'peminjaman'   => array_values($peminjaman),
            'pengembalian' => array_values($pengembalian),
        ];
    }

    /**
     * Tabel "Daftar Peminjaman Terbaru".
     * JOIN ke members dan books agar nama anggota & judul buku ikut terbawa
     * dalam satu query (menghindari N+1 query di dalam view).
     *
     * @param  int $limit Jumlah baris yang diambil
     * @return array<int, array<string, mixed>>
     */
    public function peminjamanTerbaru(int $limit = 5): array
    {
        return $this->db->table('transactions t')
            ->select('t.id, t.kode_transaksi, t.tanggal_pinjam, t.tanggal_jatuh_tempo,
                      t.tanggal_kembali, t.status, b.judul, m.nama AS nama_anggota')
            ->join('books b', 'b.id = t.book_id')
            ->join('members m', 'm.id = t.member_id')
            ->orderBy('t.tanggal_pinjam', 'DESC')
            ->orderBy('t.id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Grid "Buku Terpopuler Bulan Ini".
     * Popularitas diukur dari banyaknya transaksi peminjaman sepanjang masa;
     * LEFT JOIN dipakai supaya buku yang belum pernah dipinjam tetap bisa
     * muncul bila datanya masih sedikit.
     *
     * @param  int $limit
     * @return array<int, array<string, mixed>>
     */
    public function bukuTerpopuler(int $limit = 4): array
    {
        return $this->db->table('books b')
            ->select('b.id, b.judul, b.penulis, b.sampul, b.rating, COUNT(t.id) AS jumlah_pinjam')
            ->join('transactions t', 't.book_id = b.id', 'left')
            ->where('b.deleted_at', null)
            ->groupBy('b.id')
            ->orderBy('jumlah_pinjam', 'DESC')
            ->orderBy('b.rating', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * List "Anggota Baru Terdaftar".
     *
     * @param  int $limit
     * @return array<int, array<string, mixed>>
     */
    public function anggotaTerbaru(int $limit = 5): array
    {
        return $this->db->table('members')
            ->select('id, kode_anggota, nama, email, tanggal_daftar, status')
            ->where('deleted_at', null)
            ->orderBy('tanggal_daftar', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
