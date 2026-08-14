<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\SettingModel;
use App\Models\TransactionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * BookReturn Controller
 * ---------------------------------------------------------------------
 * Modul Pengembalian: memproses buku yang dikembalikan anggota.
 *
 * Nama kelas memakai "BookReturn", bukan "Return", karena `return`
 * adalah kata kunci PHP dan tidak boleh dipakai sebagai nama kelas.
 *
 * Alur utama:
 *   1. index()   - antrean buku yang belum kembali (search + paginasi)
 *   2. form()    - konfirmasi pengembalian + rincian denda
 *   3. process() - simpan tanggal kembali, denda, status, dan pulihkan stok
 *   4. history() - riwayat pengembalian (search + paginasi)
 *   5. edit()/update() - koreksi tanggal kembali, denda, atau catatan
 *   6. cancel()  - batalkan pengembalian bila salah proses
 *
 * Semua aksi yang menyentuh stok dibungkus transaksi database
 * (transStart/transComplete) supaya baris transaksi dan angka stok
 * tidak pernah berubah setengah jalan.
 *
 * Akses dijaga AuthFilter lewat group route di app/Config/Routes.php,
 * dan seluruh form POST otomatis diproteksi filter CSRF global.
 */
class BookReturn extends BaseController
{
    /**
     * Jumlah baris per halaman pada daftar antrean & riwayat.
     */
    private const PER_PAGE = 10;

    protected TransactionModel $trxModel;
    protected BookModel $bookModel;
    protected SettingModel $settingModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->trxModel     = new TransactionModel();
        $this->bookModel    = new BookModel();
        $this->settingModel = new SettingModel();
        $this->db           = db_connect();
    }

    /**
     * GET /returns
     * Antrean pengembalian: transaksi yang bukunya belum kembali,
     * diurutkan dari jatuh tempo paling awal.
     *
     * @return string HTML halaman antrean
     */
    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('keyword'));
        $filter  = trim((string) $this->request->getGet('status'));

        $items = $this->trxModel->searchAntrean($keyword, $filter, self::PER_PAGE);

        // Tanpa only(), keyword & filter hilang saat pindah halaman
        $pager = $this->trxModel->pager;
        $pager->only(['keyword', 'status']);

        $halaman   = (int) ($pager->getCurrentPage('returns') ?: 1);
        $nomorAwal = ($halaman - 1) * self::PER_PAGE + 1;

        $tarif = $this->settingModel->ambilAngka('denda_per_hari', 1000);

        /*
         * Sisa hari & perkiraan denda dihitung di controller (bukan di
         * view) sesuai pembagian tugas MVC: view cukup menampilkan.
         */
        foreach ($items as $i => $baris) {
            $sisa                       = $this->trxModel->sisaHari($baris['tanggal_jatuh_tempo']);
            $items[$i]['sisa_hari']     = $sisa;
            $items[$i]['hari_telat']    = max(0, -$sisa);
            $items[$i]['denda_perkiraan'] = max(0, -$sisa) * $tarif;
        }

        $ringkasan = $this->trxModel->ringkasanPengembalian();

        return view('return/index', [
            'title'        => 'Pengembalian',
            'pageTitle'    => 'Antrean Pengembalian',
            'pageSubtitle' => 'Daftar buku yang masih berada di tangan anggota.',
            'items'        => $items,
            'pager'        => $pager,
            'keyword'      => $keyword,
            'status'       => $filter,
            'nomorAwal'    => $nomorAwal,
            'totalData'    => $pager->getTotal('returns'),
            'ringkasan'    => $ringkasan,
            'tarif'        => $tarif,
            'menuBadge'    => ['returns' => $ringkasan['terlambat']],
        ]);
    }

    /**
     * GET /returns/history
     * Riwayat pengembalian: transaksi yang bukunya sudah kembali.
     *
     * @return string HTML halaman riwayat
     */
    public function history(): string
    {
        $keyword = trim((string) $this->request->getGet('keyword'));
        $filter  = trim((string) $this->request->getGet('status'));

        $items = $this->trxModel->searchRiwayat($keyword, $filter, self::PER_PAGE);

        $pager = $this->trxModel->pager;
        $pager->only(['keyword', 'status']);

        $halaman   = (int) ($pager->getCurrentPage('returns') ?: 1);
        $nomorAwal = ($halaman - 1) * self::PER_PAGE + 1;

        $ringkasan = $this->trxModel->ringkasanPengembalian();

        return view('return/history', [
            'title'        => 'Riwayat Pengembalian',
            'pageTitle'    => 'Riwayat Pengembalian',
            'pageSubtitle' => 'Catatan buku yang sudah kembali beserta dendanya.',
            'items'        => $items,
            'pager'        => $pager,
            'keyword'      => $keyword,
            'status'       => $filter,
            'nomorAwal'    => $nomorAwal,
            'totalData'    => $pager->getTotal('returns'),
            'ringkasan'    => $ringkasan,
            'menuBadge'    => ['returns' => $ringkasan['terlambat']],
        ]);
    }

    /**
     * GET /returns/{id}/process
     * Form konfirmasi pengembalian, lengkap dengan rincian perhitungan
     * denda bila terlambat.
     *
     * @param  int $id ID transaksi
     * @return RedirectResponse|string
     */
    public function form(int $id)
    {
        $loan = $this->trxModel->detail($id);

        if ($loan === null) {
            return redirect()->to(site_url('returns'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        // Transaksi yang bukunya sudah kembali tidak boleh diproses lagi
        if (! empty($loan['tanggal_kembali'])) {
            return redirect()->to(site_url('returns/history'))
                ->with('error', 'Transaksi ' . $loan['kode_transaksi'] . ' sudah dikembalikan pada '
                    . tanggal_indo($loan['tanggal_kembali']) . '.');
        }

        $tarif   = $this->settingModel->ambilAngka('denda_per_hari', 1000);
        $hariIni = date('Y-m-d');
        $hitung  = $this->trxModel->hitungDenda($loan['tanggal_jatuh_tempo'], $hariIni, $tarif);

        return view('return/form', [
            'title'        => 'Proses Pengembalian',
            'pageTitle'    => 'Proses Pengembalian',
            'pageSubtitle' => 'Konfirmasi pengembalian transaksi ' . $loan['kode_transaksi'] . '.',
            'loan'         => $loan,
            'tarif'        => $tarif,
            'hariIni'      => $hariIni,
            'hitung'       => $hitung,
        ]);
    }

    /**
     * POST /returns/{id}/process
     * Menyimpan pengembalian: mengisi tanggal kembali, denda, status,
     * lalu memulihkan stok buku.
     *
     * @param  int $id ID transaksi
     * @return RedirectResponse
     */
    public function process(int $id): RedirectResponse
    {
        $loan = $this->trxModel->find($id);

        if ($loan === null) {
            return redirect()->to(site_url('returns'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        if (! empty($loan['tanggal_kembali'])) {
            return redirect()->to(site_url('returns/history'))
                ->with('error', 'Transaksi ini sudah pernah dikembalikan.');
        }

        $tanggalKembali = (string) $this->request->getPost('tanggal_kembali');
        $kondisi        = $this->request->getPost('kondisi') === 'hilang' ? 'hilang' : 'baik';

        // Tanggal kembali tidak mungkin mendahului tanggal pinjam
        if ($tanggalKembali < $loan['tanggal_pinjam']) {
            return redirect()->back()->withInput()
                ->with('error', 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam ('
                    . tanggal_indo($loan['tanggal_pinjam']) . ').');
        }

        $tarif  = $this->settingModel->ambilAngka('denda_per_hari', 1000);
        $hitung = $this->trxModel->hitungDenda($loan['tanggal_jatuh_tempo'], $tanggalKembali, $tarif);

        /*
         * Denda hasil hitungan otomatis boleh disesuaikan petugas (mis.
         * pemberian keringanan). Bila field dikosongkan, dipakai hasil
         * hitungan sistem.
         */
        $dendaInput = $this->request->getPost('denda');
        $denda      = ($dendaInput === null || $dendaInput === '')
            ? $hitung['denda']
            : max(0, (int) $dendaInput);

        // Status akhir: hilang > terlambat > dikembalikan
        $status = 'dikembalikan';
        if ($kondisi === 'hilang') {
            $status = 'hilang';
        } elseif ($hitung['hari'] > 0) {
            $status = 'terlambat';
        }

        $data = [
            'id'                  => $id,
            'kode_transaksi'      => $loan['kode_transaksi'],
            'member_id'           => $loan['member_id'],
            'book_id'             => $loan['book_id'],
            'tanggal_pinjam'      => $loan['tanggal_pinjam'],
            'tanggal_jatuh_tempo' => $loan['tanggal_jatuh_tempo'],
            'tanggal_kembali'     => $tanggalKembali,
            'status'              => $status,
            'denda'               => $denda,
            'catatan'             => $this->kosongJadiNull($this->request->getPost('catatan')),
        ];

        if (! $this->trxModel->validate($data)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->trxModel->errors());
        }

        /*
         * Simpan transaksi + sesuaikan stok dalam SATU transaksi database.
         * Bila salah satu gagal, keduanya dibatalkan (rollback).
         */
        $this->db->transStart();

        $this->trxModel->update($id, $data);

        if ($kondisi === 'hilang') {
            // Buku tidak kembali ke rak: eksemplarnya dicoret dari koleksi.
            // stok_tersedia tidak ditambah karena memang tidak ada yang kembali.
            $this->bookModel->ubahStok((int) $loan['book_id'], -1);
        } else {
            // Buku kembali ke rak -> stok tersedia bertambah lagi
            $this->bookModel->ubahStokTersedia((int) $loan['book_id'], +1);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->withInput()
                ->with('error', 'Pengembalian gagal disimpan. Silakan coba lagi.');
        }

        $pesan = 'Pengembalian ' . $loan['kode_transaksi'] . ' berhasil diproses';
        $pesan .= $kondisi === 'hilang'
            ? '. Buku ditandai HILANG dan dikeluarkan dari koleksi.'
            : ($denda > 0 ? ' dengan denda ' . rupiah($denda) . '.' : ' tanpa denda.');

        return redirect()->to(site_url('returns'))->with('success', $pesan);
    }

    /**
     * GET /returns/{id}/edit
     * Form koreksi data pengembalian yang sudah tersimpan.
     *
     * Kondisi buku (baik/hilang) sengaja TIDAK bisa diubah di sini karena
     * menyangkut stok. Untuk mengubahnya, batalkan pengembalian lalu
     * proses ulang.
     *
     * @param  int $id ID transaksi
     * @return RedirectResponse|string
     */
    public function edit(int $id)
    {
        $loan = $this->trxModel->detail($id);

        if ($loan === null) {
            return redirect()->to(site_url('returns/history'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        if (empty($loan['tanggal_kembali'])) {
            return redirect()->to(site_url('returns/' . $id . '/process'))
                ->with('error', 'Transaksi ini belum dikembalikan, silakan proses dulu.');
        }

        return view('return/edit', [
            'title'        => 'Koreksi Pengembalian',
            'pageTitle'    => 'Koreksi Pengembalian',
            'pageSubtitle' => 'Perbaiki data pengembalian transaksi ' . $loan['kode_transaksi'] . '.',
            'loan'         => $loan,
            'tarif'        => $this->settingModel->ambilAngka('denda_per_hari', 1000),
        ]);
    }

    /**
     * POST /returns/{id}/update
     * Menyimpan koreksi tanggal kembali, denda, dan catatan.
     *
     * @param  int $id ID transaksi
     * @return RedirectResponse
     */
    public function update(int $id): RedirectResponse
    {
        $loan = $this->trxModel->find($id);

        if ($loan === null) {
            return redirect()->to(site_url('returns/history'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        if (empty($loan['tanggal_kembali'])) {
            return redirect()->to(site_url('returns'))
                ->with('error', 'Transaksi ini belum dikembalikan.');
        }

        $tanggalKembali = (string) $this->request->getPost('tanggal_kembali');

        if ($tanggalKembali < $loan['tanggal_pinjam']) {
            return redirect()->back()->withInput()
                ->with('error', 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam ('
                    . tanggal_indo($loan['tanggal_pinjam']) . ').');
        }

        $tarif  = $this->settingModel->ambilAngka('denda_per_hari', 1000);
        $hitung = $this->trxModel->hitungDenda($loan['tanggal_jatuh_tempo'], $tanggalKembali, $tarif);

        $dendaInput = $this->request->getPost('denda');
        $denda      = ($dendaInput === null || $dendaInput === '')
            ? $hitung['denda']
            : max(0, (int) $dendaInput);

        // Status buku hilang dipertahankan; selain itu ikut hasil hitungan
        $status = $loan['status'] === 'hilang'
            ? 'hilang'
            : ($hitung['hari'] > 0 ? 'terlambat' : 'dikembalikan');

        $data = [
            'id'                  => $id,
            'kode_transaksi'      => $loan['kode_transaksi'],
            'member_id'           => $loan['member_id'],
            'book_id'             => $loan['book_id'],
            'tanggal_pinjam'      => $loan['tanggal_pinjam'],
            'tanggal_jatuh_tempo' => $loan['tanggal_jatuh_tempo'],
            'tanggal_kembali'     => $tanggalKembali,
            'status'              => $status,
            'denda'               => $denda,
            'catatan'             => $this->kosongJadiNull($this->request->getPost('catatan')),
        ];

        if (! $this->trxModel->update($id, $data)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->trxModel->errors());
        }

        return redirect()->to(site_url('returns/history'))
            ->with('success', 'Data pengembalian ' . $loan['kode_transaksi'] . ' berhasil diperbarui.');
    }

    /**
     * POST /returns/{id}/cancel
     * Membatalkan pengembalian: transaksi dikembalikan ke status dipinjam
     * dan stok disesuaikan lagi. Berguna bila petugas salah klik.
     *
     * @param  int $id ID transaksi
     * @return RedirectResponse
     */
    public function cancel(int $id): RedirectResponse
    {
        $loan = $this->trxModel->find($id);

        if ($loan === null) {
            return redirect()->to(site_url('returns/history'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        if (empty($loan['tanggal_kembali'])) {
            return redirect()->to(site_url('returns'))
                ->with('error', 'Transaksi ini memang belum dikembalikan.');
        }

        $sebelumnyaHilang = $loan['status'] === 'hilang';

        $this->db->transStart();

        $this->trxModel->update($id, [
            'id'                  => $id,
            'kode_transaksi'      => $loan['kode_transaksi'],
            'member_id'           => $loan['member_id'],
            'book_id'             => $loan['book_id'],
            'tanggal_pinjam'      => $loan['tanggal_pinjam'],
            'tanggal_jatuh_tempo' => $loan['tanggal_jatuh_tempo'],
            'tanggal_kembali'     => null,
            'denda'               => 0,
            // Status disegarkan mengikuti jatuh tempo
            'status'              => $loan['tanggal_jatuh_tempo'] < date('Y-m-d') ? 'terlambat' : 'dipinjam',
        ]);

        if ($sebelumnyaHilang) {
            // Buku ternyata tidak hilang -> kembalikan ke jumlah koleksi.
            // stok_tersedia tetap, karena bukunya dianggap masih dipinjam.
            $this->bookModel->ubahStok((int) $loan['book_id'], +1);
        } else {
            // Buku dianggap keluar lagi -> stok tersedia berkurang
            $this->bookModel->ubahStokTersedia((int) $loan['book_id'], -1);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->to(site_url('returns/history'))
                ->with('error', 'Pembatalan gagal. Silakan coba lagi.');
        }

        return redirect()->to(site_url('returns'))
            ->with('success', 'Pengembalian ' . $loan['kode_transaksi']
                . ' dibatalkan. Transaksi kembali berstatus dipinjam.');
    }

    /**
     * Ubah string kosong menjadi NULL agar kolom opsional tidak menyimpan ''.
     *
     * @param  string|null $nilai
     * @return string|null
     */
    private function kosongJadiNull(?string $nilai): ?string
    {
        return ($nilai === null || trim($nilai) === '') ? null : trim($nilai);
    }
}
