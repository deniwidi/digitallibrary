<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TransactionModel
 * ---------------------------------------------------------------------
 * Mengelola tabel `transactions` - inti modul Peminjaman & Pengembalian.
 *
 * Aturan baca data yang dipakai konsisten di seluruh aplikasi:
 *   tanggal_kembali IS NULL      => buku masih di tangan anggota
 *   NULL + jatuh tempo < hari ini => terlambat
 *   tanggal_kembali TIDAK NULL    => sudah dikembalikan
 *
 * Kolom `status` tetap disimpan untuk keperluan laporan, tetapi tampilan
 * daftar selalu menghitung ulang dari tanggal supaya tidak pernah basi.
 *
 * Semua query memakai Query Builder => nilai input otomatis di-bind
 * (prepared statement), aman dari SQL Injection.
 */
class TransactionModel extends Model
{
    protected $table         = 'transactions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;   // created_at & updated_at otomatis

    // Tabel ini sengaja TIDAK memakai soft delete: transaksi yang batal
    // memang harus hilang agar tidak mengacaukan rekap laporan.

    protected $allowedFields = [
        'kode_transaksi',
        'member_id',
        'book_id',
        'user_id',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'tanggal_kembali',
        'status',
        'denda',
        'catatan',
    ];

    /**
     * Field 'id' wajib punya aturan sendiri karena dipakai sebagai
     * placeholder {id} pada rule is_unique (ketentuan CI 4.3+).
     */
    protected $validationRules = [
        'id'                  => 'permit_empty|is_natural',
        'kode_transaksi'      => 'required|max_length[25]|is_unique[transactions.kode_transaksi,id,{id}]',
        'member_id'           => 'required|is_natural_no_zero',
        'book_id'             => 'required|is_natural_no_zero',
        'tanggal_pinjam'      => 'required|valid_date[Y-m-d]',
        'tanggal_jatuh_tempo' => 'required|valid_date[Y-m-d]',
        'tanggal_kembali'     => 'permit_empty|valid_date[Y-m-d]',
        'status'              => 'required|in_list[dipinjam,dikembalikan,terlambat,hilang]',
        'denda'               => 'permit_empty|is_natural',
        'catatan'             => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'kode_transaksi' => [
            'required'  => 'Kode transaksi wajib diisi.',
            'is_unique' => 'Kode transaksi tersebut sudah dipakai.',
        ],
        'member_id' => [
            'required'           => 'Anggota peminjam wajib dipilih.',
            'is_natural_no_zero' => 'Anggota yang dipilih tidak valid.',
        ],
        'book_id' => [
            'required'           => 'Buku yang dipinjam wajib dipilih.',
            'is_natural_no_zero' => 'Buku yang dipilih tidak valid.',
        ],
        'tanggal_pinjam' => [
            'required'   => 'Tanggal pinjam wajib diisi.',
            'valid_date' => 'Format tanggal pinjam tidak valid.',
        ],
        'tanggal_jatuh_tempo' => [
            'required'   => 'Tanggal jatuh tempo wajib diisi.',
            'valid_date' => 'Format tanggal jatuh tempo tidak valid.',
        ],
    ];

    /**
     * Daftar transaksi dengan pencarian + filter status + pagination.
     *
     * JOIN ke members, books, dan users dilakukan di sini supaya nama
     * anggota, judul buku, dan nama petugas ikut terbawa dalam satu query
     * (menghindari N+1 query di dalam view).
     *
     * @param  string|null $keyword Kode transaksi / nama-kode anggota / judul-kode buku
     * @param  string|null $status  '', 'dipinjam', 'terlambat', atau 'dikembalikan'
     * @param  int         $perPage Jumlah baris per halaman
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $keyword = null, ?string $status = null, int $perPage = 10): array
    {
        $this->select('transactions.*,
                       members.nama AS nama_anggota, members.kode_anggota,
                       books.judul, books.kode_buku, books.sampul,
                       users.nama AS nama_petugas')
             ->join('members', 'members.id = transactions.member_id')
             ->join('books', 'books.id = transactions.book_id')
             ->join('users', 'users.id = transactions.user_id', 'left');

        if ($keyword !== null && $keyword !== '') {
            // groupStart()/groupEnd() membungkus rangkaian OR dalam tanda
            // kurung agar tidak bercampur dengan filter status.
            $this->groupStart()
                 ->like('transactions.kode_transaksi', $keyword)
                 ->orLike('members.nama', $keyword)
                 ->orLike('members.kode_anggota', $keyword)
                 ->orLike('books.judul', $keyword)
                 ->orLike('books.kode_buku', $keyword)
                 ->groupEnd();
        }

        /*
         * Filter status dihitung dari TANGGAL, bukan dari kolom `status`,
         * supaya transaksi yang baru saja lewat jatuh tempo langsung
         * terbaca sebagai "terlambat" tanpa perlu proses harian.
         */
        $hariIni = date('Y-m-d');

        if ($status === 'dipinjam') {
            $this->where('transactions.tanggal_kembali', null)
                 ->where('transactions.tanggal_jatuh_tempo >=', $hariIni);
        } elseif ($status === 'terlambat') {
            $this->where('transactions.tanggal_kembali', null)
                 ->where('transactions.tanggal_jatuh_tempo <', $hariIni);
        } elseif ($status === 'dikembalikan') {
            $this->where('transactions.tanggal_kembali IS NOT NULL');
        }

        // 'loans' = nama grup pager, dipakai lagi saat merender link
        return $this->orderBy('transactions.tanggal_pinjam', 'DESC')
                    ->orderBy('transactions.id', 'DESC')
                    ->paginate($perPage, 'loans');
    }

    /**
     * Pasang SELECT + JOIN standar untuk daftar transaksi.
     * Dipakai bersama oleh modul Peminjaman dan Pengembalian supaya
     * kolom yang diambil selalu sama.
     *
     * @return void
     */
    private function pasangRelasi(): void
    {
        $this->select('transactions.*,
                       members.nama AS nama_anggota, members.kode_anggota,
                       books.judul, books.kode_buku, books.sampul,
                       users.nama AS nama_petugas')
             ->join('members', 'members.id = transactions.member_id')
             ->join('books', 'books.id = transactions.book_id')
             ->join('users', 'users.id = transactions.user_id', 'left');
    }

    /**
     * Tambahkan kondisi pencarian kata kunci pada daftar transaksi.
     *
     * groupStart()/groupEnd() membungkus rangkaian OR di dalam tanda
     * kurung agar tidak bercampur dengan filter lain. Nilainya di-bind
     * Query Builder => aman dari SQL Injection.
     *
     * @param  string $keyword
     * @return void
     */
    private function pasangKeyword(string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $this->groupStart()
             ->like('transactions.kode_transaksi', $keyword)
             ->orLike('members.nama', $keyword)
             ->orLike('members.kode_anggota', $keyword)
             ->orLike('books.judul', $keyword)
             ->orLike('books.kode_buku', $keyword)
             ->groupEnd();
    }

    /**
     * ANTREAN PENGEMBALIAN - transaksi yang bukunya belum kembali.
     *
     * Diurutkan menurut jatuh tempo paling awal supaya yang paling
     * mendesak berada di baris teratas.
     *
     * @param  string|null $keyword Kode transaksi / anggota / buku
     * @param  string|null $filter  '', 'terlambat', atau 'belum_tempo'
     * @param  int         $perPage
     * @return array<int, array<string, mixed>>
     */
    public function searchAntrean(?string $keyword = null, ?string $filter = null, int $perPage = 10): array
    {
        $this->pasangRelasi();
        $this->where('transactions.tanggal_kembali', null);
        $this->pasangKeyword((string) $keyword);

        $hariIni = date('Y-m-d');

        if ($filter === 'terlambat') {
            $this->where('transactions.tanggal_jatuh_tempo <', $hariIni);
        } elseif ($filter === 'belum_tempo') {
            $this->where('transactions.tanggal_jatuh_tempo >=', $hariIni);
        }

        // 'returns' = nama grup pager khusus modul Pengembalian
        return $this->orderBy('transactions.tanggal_jatuh_tempo', 'ASC')
                    ->orderBy('transactions.id', 'ASC')
                    ->paginate($perPage, 'returns');
    }

    /**
     * RIWAYAT PENGEMBALIAN - transaksi yang bukunya sudah kembali
     * (atau dinyatakan hilang).
     *
     * @param  string|null $keyword
     * @param  string|null $filter  '', 'tepat_waktu', 'terlambat', 'hilang'
     * @param  int         $perPage
     * @return array<int, array<string, mixed>>
     */
    public function searchRiwayat(?string $keyword = null, ?string $filter = null, int $perPage = 10): array
    {
        $this->pasangRelasi();
        $this->where('transactions.tanggal_kembali IS NOT NULL');
        $this->pasangKeyword((string) $keyword);

        if ($filter === 'tepat_waktu') {
            // Kembali tepat waktu = tidak kena denda dan bukunya utuh
            $this->where('transactions.denda', 0)
                 ->where('transactions.status !=', 'hilang');
        } elseif ($filter === 'terlambat') {
            $this->where('transactions.status', 'terlambat');
        } elseif ($filter === 'hilang') {
            $this->where('transactions.status', 'hilang');
        }

        return $this->orderBy('transactions.tanggal_kembali', 'DESC')
                    ->orderBy('transactions.id', 'DESC')
                    ->paginate($perPage, 'returns');
    }

    /**
     * Hitung keterlambatan dan dendanya.
     *
     * Rumus: (tanggal kembali - jatuh tempo) hari x tarif per hari.
     * Kembali sebelum/tepat jatuh tempo => 0 hari, 0 rupiah.
     *
     * @param  string $jatuhTempo     Tanggal jatuh tempo (Y-m-d)
     * @param  string $tanggalKembali Tanggal pengembalian (Y-m-d)
     * @param  int    $tarifPerHari   Nilai `denda_per_hari` dari tabel settings
     * @return array{hari:int, denda:int}
     */
    public function hitungDenda(string $jatuhTempo, string $tanggalKembali, int $tarifPerHari): array
    {
        $selisih = (strtotime($tanggalKembali) - strtotime($jatuhTempo)) / 86400;

        // max(0, ...) memastikan pengembalian lebih awal tidak menghasilkan
        // denda negatif (yang berarti "dapat uang" - jelas keliru).
        $hari = max(0, (int) floor($selisih));

        return [
            'hari'  => $hari,
            'denda' => $hari * $tarifPerHari,
        ];
    }

    /**
     * Angka ringkasan untuk kartu di halaman Pengembalian.
     *
     * @return array{antrean:int, terlambat:int, hari_ini:int, denda_bulan:int}
     */
    public function ringkasanPengembalian(): array
    {
        $hariIni = date('Y-m-d');

        $antrean = $this->db->table($this->table)
            ->where('tanggal_kembali', null)
            ->countAllResults();

        $terlambat = $this->db->table($this->table)
            ->where('tanggal_kembali', null)
            ->where('tanggal_jatuh_tempo <', $hariIni)
            ->countAllResults();

        $hariIniCount = $this->db->table($this->table)
            ->where('tanggal_kembali', $hariIni)
            ->countAllResults();

        // Total denda yang terkumpul sepanjang bulan berjalan
        $baris = $this->db->table($this->table)
            ->selectSum('denda', 'total')
            ->where('tanggal_kembali >=', date('Y-m-01'))
            ->get()
            ->getRowArray();

        return [
            'antrean'     => $antrean,
            'terlambat'   => $terlambat,
            'hari_ini'    => $hariIniCount,
            'denda_bulan' => (int) ($baris['total'] ?? 0),
        ];
    }

    /* =================================================================
       BAGIAN LAPORAN
       Seluruh query laporan memakai satu set filter yang sama, yang
       diterapkan oleh terapkanFilterLaporan(). Bentuk $filter:
         [
           'dari'    => 'YYYY-MM-DD',
           'sampai'  => 'YYYY-MM-DD',
           'jenis'   => 'peminjaman' | 'pengembalian',
           'status'  => '' | 'dipinjam' | 'dikembalikan' | 'terlambat' | 'hilang',
           'keyword' => 'teks bebas',
         ]
       ================================================================= */

    /**
     * Terapkan filter laporan ke sebuah query.
     *
     * Parameter $q sengaja bertipe bebas karena dipakai untuk DUA hal:
     *  - $this (Model)      -> untuk daftar transaksi yang dipaginasi;
     *  - BaseBuilder        -> untuk query agregat (rekap, top 5).
     * Keduanya punya method where()/like()/groupStart() dengan tanda
     * tangan yang sama, jadi logika filternya cukup ditulis sekali.
     *
     * @param  object $q      Model atau Query Builder
     * @param  array  $filter Lihat keterangan di atas
     * @return void
     */
    private function terapkanFilterLaporan(object $q, array $filter): void
    {
        /*
         * Rentang tanggal diterapkan pada kolom yang berbeda tergantung
         * jenis laporan: laporan peminjaman memakai tanggal_pinjam,
         * laporan pengembalian memakai tanggal_kembali.
         */
        $kolom = ($filter['jenis'] ?? 'peminjaman') === 'pengembalian'
            ? 'transactions.tanggal_kembali'
            : 'transactions.tanggal_pinjam';

        $q->where($kolom . ' >=', $filter['dari']);
        $q->where($kolom . ' <=', $filter['sampai']);

        // Laporan pengembalian otomatis mengabaikan yang belum kembali
        if (($filter['jenis'] ?? '') === 'pengembalian') {
            $q->where('transactions.tanggal_kembali IS NOT NULL');
        }

        /*
         * Filter status memakai kolom `status` (catatan historis), kecuali
         * 'dipinjam' yang lebih tepat dibaca dari tanggal_kembali NULL.
         */
        switch ($filter['status'] ?? '') {
            case 'dipinjam':
                $q->where('transactions.tanggal_kembali', null);
                break;
            case 'dikembalikan':
            case 'terlambat':
            case 'hilang':
                $q->where('transactions.status', $filter['status']);
                break;
        }

        // Pencarian bebas pada kode transaksi, anggota, dan buku
        $keyword = trim((string) ($filter['keyword'] ?? ''));

        if ($keyword !== '') {
            $q->groupStart()
              ->like('transactions.kode_transaksi', $keyword)
              ->orLike('members.nama', $keyword)
              ->orLike('members.kode_anggota', $keyword)
              ->orLike('books.judul', $keyword)
              ->orLike('books.kode_buku', $keyword)
              ->groupEnd();
        }
    }

    /**
     * Builder dasar untuk query agregat laporan (rekap & top 5).
     * JOIN selalu dipasang karena filter keyword menyentuh kolom milik
     * tabel members dan books.
     *
     * @return \CodeIgniter\Database\BaseBuilder
     */
    private function builderLaporan()
    {
        return $this->db->table('transactions')
            ->join('members', 'members.id = transactions.member_id')
            ->join('books', 'books.id = transactions.book_id');
    }

    /**
     * Daftar transaksi untuk halaman laporan (dengan paginasi).
     *
     * @param  array $filter
     * @param  int   $perPage
     * @return array<int, array<string, mixed>>
     */
    public function laporanPaginasi(array $filter, int $perPage = 15): array
    {
        $this->pasangRelasi();
        $this->terapkanFilterLaporan($this, $filter);

        // 'reports' = nama grup pager khusus modul Laporan
        return $this->orderBy('transactions.tanggal_pinjam', 'DESC')
                    ->orderBy('transactions.id', 'DESC')
                    ->paginate($perPage, 'reports');
    }

    /**
     * Seluruh baris laporan tanpa paginasi - dipakai halaman cetak dan
     * ekspor CSV.
     *
     * Diberi batas atas ($maks) agar satu klik "Cetak" pada rentang yang
     * sangat lebar tidak menghabiskan memori server.
     *
     * @param  array $filter
     * @param  int   $maks
     * @return array<int, array<string, mixed>>
     */
    public function laporanSemua(array $filter, int $maks = 2000): array
    {
        $this->pasangRelasi();
        $this->terapkanFilterLaporan($this, $filter);

        return $this->orderBy('transactions.tanggal_pinjam', 'ASC')
                    ->orderBy('transactions.id', 'ASC')
                    ->findAll($maks);
    }

    /**
     * Rekap angka laporan dalam satu query agregat.
     *
     * SUM(CASE WHEN ...) dipakai agar seluruh angka didapat sekali jalan,
     * bukan lima query terpisah.
     *
     * @param  array $filter
     * @return array{total:int, dipinjam:int, dikembalikan:int, terlambat:int, hilang:int, denda:int}
     */
    public function laporanRekap(array $filter): array
    {
        $builder = $this->builderLaporan()->select(
            'COUNT(transactions.id) AS total,
             SUM(CASE WHEN transactions.tanggal_kembali IS NULL THEN 1 ELSE 0 END) AS dipinjam,
             SUM(CASE WHEN transactions.status = "dikembalikan" THEN 1 ELSE 0 END) AS dikembalikan,
             SUM(CASE WHEN transactions.status = "terlambat" THEN 1 ELSE 0 END) AS terlambat,
             SUM(CASE WHEN transactions.status = "hilang" THEN 1 ELSE 0 END) AS hilang,
             COALESCE(SUM(transactions.denda), 0) AS denda',
            false
        );

        $this->terapkanFilterLaporan($builder, $filter);

        $baris = $builder->get()->getRowArray();

        return [
            'total'        => (int) ($baris['total'] ?? 0),
            'dipinjam'     => (int) ($baris['dipinjam'] ?? 0),
            'dikembalikan' => (int) ($baris['dikembalikan'] ?? 0),
            'terlambat'    => (int) ($baris['terlambat'] ?? 0),
            'hilang'       => (int) ($baris['hilang'] ?? 0),
            'denda'        => (int) ($baris['denda'] ?? 0),
        ];
    }

    /**
     * Buku yang paling sering dipinjam dalam rentang laporan.
     *
     * @param  array $filter
     * @param  int   $limit
     * @return array<int, array<string, mixed>>
     */
    public function laporanBukuTerpopuler(array $filter, int $limit = 5): array
    {
        $builder = $this->builderLaporan()
            ->select('books.id, books.judul, books.kode_buku, COUNT(transactions.id) AS jumlah');

        $this->terapkanFilterLaporan($builder, $filter);

        return $builder->groupBy('books.id')
            ->orderBy('jumlah', 'DESC')
            ->orderBy('books.judul', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Anggota paling aktif meminjam dalam rentang laporan.
     *
     * @param  array $filter
     * @param  int   $limit
     * @return array<int, array<string, mixed>>
     */
    public function laporanAnggotaTeraktif(array $filter, int $limit = 5): array
    {
        $builder = $this->builderLaporan()
            ->select('members.id, members.nama, members.kode_anggota,
                      COUNT(transactions.id) AS jumlah,
                      COALESCE(SUM(transactions.denda), 0) AS denda');

        $this->terapkanFilterLaporan($builder, $filter);

        return $builder->groupBy('members.id')
            ->orderBy('jumlah', 'DESC')
            ->orderBy('members.nama', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Ambil satu transaksi lengkap dengan data relasinya.
     *
     * @param  int        $id
     * @return array|null
     */
    public function detail(int $id): ?array
    {
        return $this->select('transactions.*,
                              members.nama AS nama_anggota, members.kode_anggota,
                              members.email, members.telepon, members.status AS status_anggota,
                              books.judul, books.kode_buku, books.penulis, books.sampul,
                              categories.nama AS nama_kategori,
                              users.nama AS nama_petugas')
            ->join('members', 'members.id = transactions.member_id')
            ->join('books', 'books.id = transactions.book_id')
            ->join('categories', 'categories.id = books.category_id', 'left')
            ->join('users', 'users.id = transactions.user_id', 'left')
            ->where('transactions.id', $id)
            ->first();
    }

    /**
     * Hasilkan kode transaksi harian: TRX-20260814-0001.
     *
     * Nomor urut dihitung dari transaksi yang kodenya berawalan tanggal
     * yang sama, sehingga tiap hari kembali mulai dari 0001.
     *
     * @param  string|null $tanggal Tanggal acuan (Y-m-d), default hari ini
     * @return string
     */
    public function generateKode(?string $tanggal = null): string
    {
        $tanggal = $tanggal ?? date('Y-m-d');
        $prefix  = 'TRX-' . date('Ymd', strtotime($tanggal)) . '-';

        // Ambil nomor terbesar untuk prefix hari tersebut, lalu +1.
        // SUBSTRING dimulai setelah panjang prefix.
        $baris = $this->db->table($this->table)
            ->select('MAX(CAST(SUBSTRING(kode_transaksi, ' . (strlen($prefix) + 1) . ') AS UNSIGNED)) AS nomor', false)
            ->like('kode_transaksi', $prefix, 'after')
            ->get()
            ->getRowArray();

        $nomor = (int) ($baris['nomor'] ?? 0) + 1;

        return $prefix . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung berapa buku yang sedang dipegang seorang anggota.
     * Dipakai untuk membatasi jumlah pinjaman per anggota.
     *
     * @param  int $memberId
     * @return int
     */
    public function jumlahPinjamanAktif(int $memberId): int
    {
        return $this->db->table($this->table)
            ->where('member_id', $memberId)
            ->where('tanggal_kembali', null)
            ->countAllResults();
    }

    /**
     * Cek apakah anggota masih memegang buku tertentu (belum dikembalikan).
     * Mencegah satu anggota meminjam judul yang sama dua kali sekaligus.
     *
     * @param  int $memberId
     * @param  int $bookId
     * @return bool
     */
    public function sedangMeminjamBuku(int $memberId, int $bookId): bool
    {
        return $this->db->table($this->table)
            ->where('member_id', $memberId)
            ->where('book_id', $bookId)
            ->where('tanggal_kembali', null)
            ->countAllResults() > 0;
    }

    /**
     * Angka ringkasan untuk kartu di halaman Peminjaman.
     *
     * @return array{aktif:int, terlambat:int, hari_ini:int, selesai:int}
     */
    public function ringkasan(): array
    {
        $hariIni = date('Y-m-d');

        $aktif = $this->db->table($this->table)
            ->where('tanggal_kembali', null)
            ->countAllResults();

        $terlambat = $this->db->table($this->table)
            ->where('tanggal_kembali', null)
            ->where('tanggal_jatuh_tempo <', $hariIni)
            ->countAllResults();

        $hariIniCount = $this->db->table($this->table)
            ->where('tanggal_pinjam', $hariIni)
            ->countAllResults();

        $selesai = $this->db->table($this->table)
            ->where('tanggal_kembali IS NOT NULL')
            ->countAllResults();

        return [
            'aktif'     => $aktif,
            'terlambat' => $terlambat,
            'hari_ini'  => $hariIniCount,
            'selesai'   => $selesai,
        ];
    }

    /**
     * Tentukan status tampilan sebuah baris transaksi berdasarkan tanggal.
     *
     * @param  array $trx Baris transaksi
     * @return string     'dikembalikan' | 'terlambat' | 'dipinjam'
     */
    public function statusTampilan(array $trx): string
    {
        if (! empty($trx['tanggal_kembali'])) {
            // Sudah kembali: bedakan yang sempat telat (ada denda/status telat)
            return $trx['status'] === 'terlambat' ? 'terlambat' : 'dikembalikan';
        }

        return $trx['tanggal_jatuh_tempo'] < date('Y-m-d') ? 'terlambat' : 'dipinjam';
    }

    /**
     * Hitung selisih hari terhadap jatuh tempo.
     *
     * @param  string $jatuhTempo Tanggal jatuh tempo (Y-m-d)
     * @return int    Positif = masih ada sisa hari, negatif = telat sekian hari
     */
    public function sisaHari(string $jatuhTempo): int
    {
        $tempo = strtotime($jatuhTempo);
        $kini  = strtotime(date('Y-m-d'));

        return (int) floor(($tempo - $kini) / 86400);
    }
}
