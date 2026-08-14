<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Models\TransactionModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Report Controller
 * ---------------------------------------------------------------------
 * Modul Laporan: rekap transaksi peminjaman & pengembalian berdasarkan
 * rentang tanggal.
 *
 * CATATAN PENTING soal CRUD:
 * Modul ini sengaja HANYA membaca data (read-only). Tidak ada Create,
 * Update, maupun Delete, karena laporan bukan entitas tersendiri - ia
 * cuma cara lain memandang tabel `transactions`. Menyediakan tombol
 * ubah/hapus di sini justru berbahaya: satu klik salah bisa mengacaukan
 * rekap sekaligus stok. Perubahan data tetap dilakukan lewat modul
 * Peminjaman dan Pengembalian.
 *
 * Yang disediakan sebagai gantinya:
 *   - filter rentang tanggal + jenis + status + pencarian bebas
 *   - paginasi pada daftar transaksinya
 *   - rekap angka & dua tabel peringkat (buku terpopuler, anggota teraktif)
 *   - halaman siap cetak dan ekspor CSV yang mengikuti filter yang sama
 *
 * Akses dijaga AuthFilter lewat group route di app/Config/Routes.php.
 */
class Report extends BaseController
{
    /**
     * Jumlah baris per halaman pada tabel laporan.
     */
    private const PER_PAGE = 15;

    /**
     * Batas baris untuk halaman cetak & ekspor CSV, supaya rentang yang
     * sangat lebar tidak menghabiskan memori server.
     */
    private const MAKS_CETAK = 2000;

    /**
     * Status yang boleh dipakai sebagai filter (whitelist).
     */
    private const STATUS_VALID = ['dipinjam', 'dikembalikan', 'terlambat', 'hilang'];

    protected TransactionModel $trxModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->trxModel     = new TransactionModel();
        $this->settingModel = new SettingModel();
    }

    /**
     * GET /reports
     * Halaman laporan: filter, rekap, tabel transaksi, dan peringkat.
     *
     * @return string HTML halaman laporan
     */
    public function index(): string
    {
        $filter = $this->ambilFilter();

        $items = $this->trxModel->laporanPaginasi($filter, self::PER_PAGE);

        // Tanpa only(), seluruh filter hilang saat berpindah halaman
        $pager = $this->trxModel->pager;
        $pager->only(['dari', 'sampai', 'jenis', 'status', 'keyword']);

        $halaman   = (int) ($pager->getCurrentPage('reports') ?: 1);
        $nomorAwal = ($halaman - 1) * self::PER_PAGE + 1;

        return view('report/index', [
            'title'        => 'Laporan',
            'pageTitle'    => 'Laporan Transaksi',
            'pageSubtitle' => 'Rekap peminjaman dan pengembalian berdasarkan rentang tanggal.',
            'items'        => $items,
            'pager'        => $pager,
            'filter'       => $filter,
            'nomorAwal'    => $nomorAwal,
            'totalData'    => $pager->getTotal('reports'),
            'rekap'        => $this->trxModel->laporanRekap($filter),
            'topBuku'      => $this->trxModel->laporanBukuTerpopuler($filter, 5),
            'topAnggota'   => $this->trxModel->laporanAnggotaTeraktif($filter, 5),
            /*
             * Query string yang sama dipakai tombol Cetak & Ekspor.
             * array_intersect_key() menyaring agar kunci internal seperti
             * '_peringatan' tidak ikut menempel di URL.
             */
            'queryFilter'  => http_build_query(array_intersect_key(
                $filter,
                array_flip(['dari', 'sampai', 'jenis', 'status', 'keyword'])
            )),
            'peringatan'   => $filter['_peringatan'] ?? null,
        ]);
    }

    /**
     * GET /reports/print
     * Versi siap cetak: tanpa sidebar/topbar, langsung memicu dialog
     * cetak browser. Filternya sama persis dengan halaman laporan.
     *
     * @return string HTML halaman cetak
     */
    public function printView(): string
    {
        $filter = $this->ambilFilter();

        return view('report/print', [
            'items'    => $this->trxModel->laporanSemua($filter, self::MAKS_CETAK),
            'filter'   => $filter,
            'rekap'    => $this->trxModel->laporanRekap($filter),
            'appName'  => $this->settingModel->ambil('app_name', 'DIGI-LIBRARY'),
            'petugas'  => session()->get('nama') ?? '-',
            'maksimal' => self::MAKS_CETAK,
        ]);
    }

    /**
     * GET /reports/export
     * Unduh laporan sebagai berkas CSV, mengikuti filter yang sedang aktif.
     *
     * @return ResponseInterface Berkas CSV
     */
    public function exportCsv(): ResponseInterface
    {
        $filter = $this->ambilFilter();
        $items  = $this->trxModel->laporanSemua($filter, self::MAKS_CETAK);

        $judulKolom = [
            'No', 'Kode Transaksi', 'Kode Anggota', 'Nama Anggota',
            'Kode Buku', 'Judul Buku', 'Tanggal Pinjam', 'Jatuh Tempo',
            'Tanggal Kembali', 'Status', 'Denda', 'Petugas', 'Catatan',
        ];

        $baris = [$this->barisCsv($judulKolom)];
        $nomor = 1;

        foreach ($items as $item) {
            $baris[] = $this->barisCsv([
                $nomor++,
                $item['kode_transaksi'],
                $item['kode_anggota'],
                $item['nama_anggota'],
                $item['kode_buku'],
                $item['judul'],
                $item['tanggal_pinjam'],
                $item['tanggal_jatuh_tempo'],
                $item['tanggal_kembali'] ?? '',
                $item['status'],
                $item['denda'],
                $item['nama_petugas'] ?? '',
                $item['catatan'] ?? '',
            ]);
        }

        /*
         * BOM UTF-8 di awal berkas membuat Microsoft Excel mengenali
         * encoding-nya, sehingga huruf beraksen tidak berubah jadi simbol.
         */
        $csv = "\xEF\xBB\xBF" . implode("\r\n", $baris);

        $namaFile = 'laporan-' . $filter['jenis'] . '-' . $filter['dari'] . '-sd-' . $filter['sampai'] . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->setBody($csv);
    }

    /**
     * Baca dan bersihkan seluruh parameter filter dari query string.
     *
     * Semua nilai divalidasi/di-whitelist di sini, sehingga method lain
     * tinggal memakainya tanpa memeriksa ulang.
     *
     * @return array<string, string|null>
     */
    private function ambilFilter(): array
    {
        // Bawaan: dari awal bulan berjalan sampai hari ini
        $dari   = $this->tanggalValid($this->request->getGet('dari'), date('Y-m-01'));
        $sampai = $this->tanggalValid($this->request->getGet('sampai'), date('Y-m-d'));

        $peringatan = null;

        // Rentang terbalik ditukar otomatis, lalu user diberi tahu
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
            $peringatan = 'Tanggal awal lebih besar dari tanggal akhir, jadi urutannya kami tukar.';
        }

        $jenis = $this->request->getGet('jenis') === 'pengembalian' ? 'pengembalian' : 'peminjaman';

        $status = (string) $this->request->getGet('status');
        // Nilai di luar daftar yang dikenal diperlakukan sebagai "semua"
        $status = in_array($status, self::STATUS_VALID, true) ? $status : '';

        return [
            'dari'        => $dari,
            'sampai'      => $sampai,
            'jenis'       => $jenis,
            'status'      => $status,
            'keyword'     => trim((string) $this->request->getGet('keyword')),
            '_peringatan' => $peringatan,
        ];
    }

    /**
     * Pastikan sebuah nilai benar-benar tanggal berformat Y-m-d.
     *
     * @param  mixed  $nilai
     * @param  string $bawaan Dipakai bila nilainya kosong/tidak valid
     * @return string
     */
    private function tanggalValid($nilai, string $bawaan): string
    {
        $teks = trim((string) $nilai);

        if ($teks === '') {
            return $bawaan;
        }

        // checkdate() menolak tanggal mustahil seperti 2026-02-31
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $teks, $c)
            && checkdate((int) $c[2], (int) $c[3], (int) $c[1])) {
            return $teks;
        }

        return $bawaan;
    }

    /**
     * Susun satu baris CSV dari sebuah array nilai.
     *
     * Dua hal yang ditangani di sini:
     *  1. Tanda kutip di dalam nilai digandakan ("") sesuai aturan CSV.
     *  2. Nilai yang diawali = + - @ diberi kutip tunggal di depan.
     *     Tanpa itu, Excel menganggapnya rumus - celah klasik bernama
     *     "CSV/formula injection".
     *
     * Pemisah kolom memakai titik koma (;) karena itu yang dipakai Excel
     * pada Windows berlokal Indonesia.
     *
     * @param  array<int, mixed> $kolom
     * @return string
     */
    private function barisCsv(array $kolom): string
    {
        $aman = array_map(static function ($nilai): string {
            $teks = (string) $nilai;

            if ($teks !== '' && strpos("=+-@\t\r", $teks[0]) !== false) {
                $teks = "'" . $teks;
            }

            return '"' . str_replace('"', '""', $teks) . '"';
        }, $kolom);

        return implode(';', $aman);
    }
}
