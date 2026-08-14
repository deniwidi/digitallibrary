<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\MemberModel;
use App\Models\SettingModel;
use App\Models\TransactionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Loan Controller
 * ---------------------------------------------------------------------
 * Modul Peminjaman: mencatat buku yang keluar dari perpustakaan.
 *
 * Selain CRUD biasa, modul ini memegang aturan main peminjaman:
 *   1. Anggota harus berstatus 'aktif'.
 *   2. Stok buku yang tersedia harus > 0.
 *   3. Satu anggota tidak boleh memegang judul yang sama dua kali.
 *   4. Jumlah pinjaman aktif dibatasi pengaturan `max_pinjam_buku`.
 *   5. Jatuh tempo bawaan = tanggal pinjam + `max_hari_pinjam`.
 *
 * Setiap aksi yang menyentuh stok dibungkus transaksi database
 * (transStart/transComplete) supaya baris transaksi dan angka
 * `books.stok_tersedia` tidak pernah berubah setengah jalan.
 *
 * Akses dijaga AuthFilter lewat group route di app/Config/Routes.php,
 * dan seluruh form POST otomatis diproteksi filter CSRF global.
 */
class Loan extends BaseController
{
    /**
     * Jumlah baris per halaman pada daftar peminjaman.
     */
    private const PER_PAGE = 10;

    /**
     * Jumlah baris yang dikirim per permintaan AJAX Select2.
     * Angka kecil membuat dropdown tetap ringan walau data ribuan;
     * sisanya diambil sambil user menggulir (infinite scroll).
     */
    private const AJAX_LIMIT = 15;

    protected TransactionModel $trxModel;
    protected MemberModel $memberModel;
    protected BookModel $bookModel;
    protected SettingModel $settingModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->trxModel     = new TransactionModel();
        $this->memberModel  = new MemberModel();
        $this->bookModel    = new BookModel();
        $this->settingModel = new SettingModel();
        $this->db           = db_connect();
    }

    /**
     * GET /loans
     * Menampilkan daftar transaksi + pencarian + filter status + paginasi.
     *
     * @return string HTML halaman daftar peminjaman
     */
    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('keyword'));
        $status  = trim((string) $this->request->getGet('status'));

        $loans = $this->trxModel->search($keyword, $status, self::PER_PAGE);

        /*
         * Status tampilan & sisa hari dihitung di sini (bukan di view)
         * supaya view cukup menampilkan data yang sudah jadi - sesuai
         * pembagian tugas MVC.
         */
        foreach ($loans as $i => $baris) {
            $loans[$i]['status_tampil'] = $this->trxModel->statusTampilan($baris);
            $loans[$i]['sisa_hari']     = $this->trxModel->sisaHari($baris['tanggal_jatuh_tempo']);
        }

        // Tanpa only(), keyword & filter hilang saat pindah halaman
        $pager = $this->trxModel->pager;
        $pager->only(['keyword', 'status']);

        $halaman   = (int) ($pager->getCurrentPage('loans') ?: 1);
        $nomorAwal = ($halaman - 1) * self::PER_PAGE + 1;

        $ringkasan = $this->trxModel->ringkasan();

        return view('loan/index', [
            'title'        => 'Peminjaman',
            'pageTitle'    => 'Transaksi Peminjaman',
            'pageSubtitle' => 'Catat buku keluar, pantau jatuh tempo, dan kelola perpanjangan.',
            'loans'        => $loans,
            'pager'        => $pager,
            'keyword'      => $keyword,
            'status'       => $status,
            'nomorAwal'    => $nomorAwal,
            'totalData'    => $pager->getTotal('loans'),
            'ringkasan'    => $ringkasan,
            'menuBadge'    => ['returns' => $ringkasan['terlambat']],
        ]);
    }

    /**
     * GET /loans/create
     * Menampilkan form transaksi baru. Tanggal pinjam terisi hari ini dan
     * jatuh tempo dihitung dari pengaturan `max_hari_pinjam`.
     *
     * @return string HTML form tambah
     */
    public function create(): string
    {
        $lamaPinjam = $this->settingModel->ambilAngka('max_hari_pinjam', 7);

        /*
         * Dropdown anggota & buku memakai Select2 AJAX, jadi daftar isinya
         * TIDAK di-render ke HTML. Yang dikirim ke view hanya:
         *  - penanda ketersediaan data (untuk peringatan bila kosong);
         *  - satu opsi terpilih bila form dikembalikan karena gagal
         *    validasi, supaya pilihan user sebelumnya tidak hilang
         *    (Select2 butuh <option> tersebut ada di DOM).
         */
        return view('loan/create', [
            'title'          => 'Peminjaman Baru',
            'pageTitle'      => 'Peminjaman Baru',
            'pageSubtitle'   => 'Pilih anggota dan buku, lalu tentukan tanggal jatuh temponya.',
            'kodeBaru'       => $this->trxModel->generateKode(),
            'adaAnggota'     => $this->memberModel->where('status', 'aktif')->countAllResults() > 0,
            'adaBuku'        => $this->bookModel->where('stok_tersedia >', 0)->countAllResults() > 0,
            'memberTerpilih' => $this->opsiTerpilihAnggota((int) old('member_id')),
            'bookTerpilih'   => $this->opsiTerpilihBuku((int) old('book_id')),
            'lamaPinjam'     => $lamaPinjam,
            'maxPinjam'      => $this->settingModel->ambilAngka('max_pinjam_buku', 3),
            'tanggalPinjam'  => date('Y-m-d'),
            'jatuhTempo'     => date('Y-m-d', strtotime('+' . $lamaPinjam . ' day')),
        ]);
    }

    /**
     * GET /loans/search-members?q=...&page=1
     * Endpoint AJAX Select2: mencari anggota aktif berdasarkan ketikan user.
     *
     * Format balasan mengikuti yang dipahami Select2:
     *   { "results": [ {id, text, ...}, ... ], "pagination": { "more": bool } }
     *
     * @return ResponseInterface JSON
     */
    public function searchMembers(): ResponseInterface
    {
        // Endpoint ini hanya untuk dipanggil dari halaman form via AJAX.
        // Diakses langsung lewat address bar -> anggap tidak ada.
        if (! $this->request->isAJAX()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $keyword = trim((string) $this->request->getGet('q'));
        $halaman = max(1, (int) $this->request->getGet('page'));
        $offset  = ($halaman - 1) * self::AJAX_LIMIT;

        $baris = $this->memberModel->cariUntukSelect2($keyword, self::AJAX_LIMIT, $offset);

        // Model mengambil LIMIT+1 baris; kelebihannya jadi penanda
        // "masih ada data berikutnya", lalu dibuang dari hasil.
        $adaLagi = count($baris) > self::AJAX_LIMIT;
        $baris   = array_slice($baris, 0, self::AJAX_LIMIT);

        $hasil = [];

        foreach ($baris as $row) {
            $hasil[] = [
                // 'id' & 'text' adalah dua field wajib Select2
                'id'      => (int) $row['id'],
                'text'    => $row['kode_anggota'] . ' - ' . $row['nama'],
                // Field tambahan untuk tampilan dua baris di dropdown
                'kode'    => $row['kode_anggota'],
                'nama'    => $row['nama'],
                'kontak'  => $row['email'] ?? $row['telepon'] ?? '-',
            ];
        }

        return $this->response->setJSON([
            'results'    => $hasil,
            'pagination' => ['more' => $adaLagi],
        ]);
    }

    /**
     * GET /loans/search-books?q=...&page=1
     * Endpoint AJAX Select2: mencari buku yang stoknya masih tersedia.
     *
     * @return ResponseInterface JSON
     */
    public function searchBooks(): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $keyword = trim((string) $this->request->getGet('q'));
        $halaman = max(1, (int) $this->request->getGet('page'));
        $offset  = ($halaman - 1) * self::AJAX_LIMIT;

        $baris = $this->bookModel->cariUntukSelect2($keyword, self::AJAX_LIMIT, $offset);

        $adaLagi = count($baris) > self::AJAX_LIMIT;
        $baris   = array_slice($baris, 0, self::AJAX_LIMIT);

        $hasil = [];

        foreach ($baris as $row) {
            $hasil[] = [
                'id'       => (int) $row['id'],
                'text'     => $row['kode_buku'] . ' - ' . $row['judul'],
                'kode'     => $row['kode_buku'],
                'judul'    => $row['judul'],
                'penulis'  => $row['penulis'],
                'kategori' => $row['nama_kategori'] ?? '-',
                'tersedia' => (int) $row['stok_tersedia'],
            ];
        }

        return $this->response->setJSON([
            'results'    => $hasil,
            'pagination' => ['more' => $adaLagi],
        ]);
    }

    /**
     * Susun satu opsi anggota terpilih untuk dirender ulang ke <select>
     * saat form kembali karena gagal validasi.
     *
     * @param  int $id
     * @return array{id:int, text:string}|null
     */
    private function opsiTerpilihAnggota(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $member = $this->memberModel->find($id);

        return $member === null
            ? null
            : ['id' => (int) $member['id'], 'text' => $member['kode_anggota'] . ' - ' . $member['nama']];
    }

    /**
     * Susun satu opsi buku terpilih untuk dirender ulang ke <select>
     * saat form kembali karena gagal validasi.
     *
     * @param  int $id
     * @return array{id:int, text:string}|null
     */
    private function opsiTerpilihBuku(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $book = $this->bookModel->find($id);

        return $book === null
            ? null
            : ['id' => (int) $book['id'], 'text' => $book['kode_buku'] . ' - ' . $book['judul']];
    }

    /**
     * POST /loans/store
     * Mencatat peminjaman baru sekaligus mengurangi stok tersedia.
     *
     * @return RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $memberId = (int) $this->request->getPost('member_id');
        $bookId   = (int) $this->request->getPost('book_id');

        $data = [
            'id'                  => 0, // placeholder {id} untuk rule is_unique
            'kode_transaksi'      => trim((string) $this->request->getPost('kode_transaksi')),
            'member_id'           => $memberId,
            'book_id'             => $bookId,
            'user_id'             => session()->get('user_id'),
            'tanggal_pinjam'      => $this->request->getPost('tanggal_pinjam'),
            'tanggal_jatuh_tempo' => $this->request->getPost('tanggal_jatuh_tempo'),
            'tanggal_kembali'     => null,
            'status'              => 'dipinjam',
            'denda'               => 0,
            'catatan'             => $this->kosongJadiNull($this->request->getPost('catatan')),
        ];

        // 1) Validasi bentuk data (format tanggal, field wajib, dll.)
        if (! $this->trxModel->validate($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->trxModel->errors());
        }

        // 2) Validasi aturan main peminjaman
        $pelanggaran = $this->periksaAturan($memberId, $bookId, $data['tanggal_pinjam'], $data['tanggal_jatuh_tempo']);

        if ($pelanggaran !== null) {
            return redirect()->back()->withInput()->with('error', $pelanggaran);
        }

        /*
         * 3) Simpan transaksi + kurangi stok dalam SATU transaksi database.
         *    Bila salah satu gagal, keduanya dibatalkan (rollback) sehingga
         *    stok tidak pernah berkurang tanpa ada catatan transaksinya.
         */
        $this->db->transStart();

        $this->trxModel->insert($data);
        $this->bookModel->ubahStokTersedia($bookId, -1);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Transaksi gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(site_url('loans'))
            ->with('success', 'Peminjaman ' . $data['kode_transaksi'] . ' berhasil dicatat. '
                . 'Jatuh tempo ' . tanggal_indo($data['tanggal_jatuh_tempo']) . '.');
    }

    /**
     * GET /loans/{id}
     * Menampilkan detail satu transaksi.
     *
     * @param  int $id
     * @return RedirectResponse|string
     */
    public function show(int $id)
    {
        $loan = $this->trxModel->detail($id);

        if ($loan === null) {
            return redirect()->to(site_url('loans'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        return view('loan/show', [
            'title'         => 'Detail Peminjaman',
            'pageTitle'     => 'Detail Peminjaman',
            'pageSubtitle'  => 'Kode transaksi ' . $loan['kode_transaksi'] . '.',
            'loan'          => $loan,
            'statusTampil'  => $this->trxModel->statusTampilan($loan),
            'sisaHari'      => $this->trxModel->sisaHari($loan['tanggal_jatuh_tempo']),
            'dendaPerHari'  => $this->settingModel->ambilAngka('denda_per_hari', 1000),
        ]);
    }

    /**
     * GET /loans/{id}/edit
     * Form ubah transaksi.
     *
     * Anggota dan buku sengaja TIDAK bisa diganti di sini: stok sudah
     * terlanjur dipesan atas nama buku tersebut. Bila salah pilih,
     * batalkan transaksinya lalu buat yang baru.
     *
     * @param  int $id
     * @return RedirectResponse|string
     */
    public function edit(int $id)
    {
        $loan = $this->trxModel->detail($id);

        if ($loan === null) {
            return redirect()->to(site_url('loans'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        if (! empty($loan['tanggal_kembali'])) {
            return redirect()->to(site_url('loans/' . $id))
                ->with('error', 'Transaksi yang sudah dikembalikan tidak bisa diubah.');
        }

        return view('loan/edit', [
            'title'        => 'Ubah Peminjaman',
            'pageTitle'    => 'Ubah Peminjaman',
            'pageSubtitle' => 'Perbarui jatuh tempo atau catatan transaksi ' . $loan['kode_transaksi'] . '.',
            'loan'         => $loan,
        ]);
    }

    /**
     * POST /loans/{id}/update
     * Menyimpan perubahan jatuh tempo & catatan.
     *
     * @param  int $id
     * @return RedirectResponse
     */
    public function update(int $id): RedirectResponse
    {
        $loan = $this->trxModel->find($id);

        if ($loan === null) {
            return redirect()->to(site_url('loans'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        if (! empty($loan['tanggal_kembali'])) {
            return redirect()->to(site_url('loans/' . $id))
                ->with('error', 'Transaksi yang sudah dikembalikan tidak bisa diubah.');
        }

        $jatuhTempo = (string) $this->request->getPost('tanggal_jatuh_tempo');

        // Jatuh tempo tidak boleh mendahului tanggal pinjam
        if ($jatuhTempo < $loan['tanggal_pinjam']) {
            return redirect()->back()->withInput()
                ->with('error', 'Tanggal jatuh tempo tidak boleh lebih awal dari tanggal pinjam ('
                    . tanggal_indo($loan['tanggal_pinjam']) . ').');
        }

        $data = [
            'id'                  => $id,
            'kode_transaksi'      => $loan['kode_transaksi'],
            'member_id'           => $loan['member_id'],
            'book_id'             => $loan['book_id'],
            'tanggal_pinjam'      => $loan['tanggal_pinjam'],
            'tanggal_jatuh_tempo' => $jatuhTempo,
            // Status disegarkan mengikuti jatuh tempo yang baru
            'status'              => $jatuhTempo < date('Y-m-d') ? 'terlambat' : 'dipinjam',
            'catatan'             => $this->kosongJadiNull($this->request->getPost('catatan')),
        ];

        if (! $this->trxModel->update($id, $data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->trxModel->errors());
        }

        return redirect()->to(site_url('loans'))
            ->with('success', 'Transaksi ' . $loan['kode_transaksi'] . ' berhasil diperbarui.');
    }

    /**
     * POST /loans/{id}/extend
     * Perpanjangan cepat: menambah jatuh tempo sebanyak `max_hari_pinjam`
     * hari dari tanggal jatuh tempo saat ini.
     *
     * @param  int $id
     * @return RedirectResponse
     */
    public function extend(int $id): RedirectResponse
    {
        $loan = $this->trxModel->find($id);

        if ($loan === null) {
            return redirect()->to(site_url('loans'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        if (! empty($loan['tanggal_kembali'])) {
            return redirect()->to(site_url('loans'))
                ->with('error', 'Transaksi yang sudah dikembalikan tidak bisa diperpanjang.');
        }

        $tambahHari = $this->settingModel->ambilAngka('max_hari_pinjam', 7);
        $tempoBaru  = date('Y-m-d', strtotime($loan['tanggal_jatuh_tempo'] . ' +' . $tambahHari . ' day'));

        $this->trxModel->update($id, [
            'id'                  => $id,
            'kode_transaksi'      => $loan['kode_transaksi'],
            'member_id'           => $loan['member_id'],
            'book_id'             => $loan['book_id'],
            'tanggal_pinjam'      => $loan['tanggal_pinjam'],
            'tanggal_jatuh_tempo' => $tempoBaru,
            'status'              => $tempoBaru < date('Y-m-d') ? 'terlambat' : 'dipinjam',
        ]);

        return redirect()->to(site_url('loans'))
            ->with('success', 'Jatuh tempo ' . $loan['kode_transaksi'] . ' diperpanjang '
                . $tambahHari . ' hari menjadi ' . tanggal_indo($tempoBaru) . '.');
    }

    /**
     * POST /loans/{id}/delete
     * Membatalkan/menghapus transaksi.
     *
     * Tabel transactions tidak memakai soft delete, jadi penghapusan ini
     * permanen. Bila bukunya belum kembali, stok tersedia dikembalikan
     * dulu agar angka katalog tetap benar.
     *
     * @param  int $id
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        $loan = $this->trxModel->find($id);

        if ($loan === null) {
            return redirect()->to(site_url('loans'))
                ->with('error', 'Data transaksi tidak ditemukan.');
        }

        $masihDipinjam = empty($loan['tanggal_kembali']);

        $this->db->transStart();

        $this->trxModel->delete($id);

        if ($masihDipinjam) {
            // Buku belum kembali -> stok yang tadi dikurangi dipulihkan
            $this->bookModel->ubahStokTersedia((int) $loan['book_id'], +1);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->to(site_url('loans'))
                ->with('error', 'Transaksi gagal dihapus. Silakan coba lagi.');
        }

        return redirect()->to(site_url('loans'))
            ->with('success', 'Transaksi ' . $loan['kode_transaksi'] . ' berhasil dihapus'
                . ($masihDipinjam ? ' dan stok buku dikembalikan.' : '.'));
    }

    /**
     * Periksa seluruh aturan main peminjaman.
     *
     * @param  int    $memberId
     * @param  int    $bookId
     * @param  string $tanggalPinjam
     * @param  string $jatuhTempo
     * @return string|null Pesan pelanggaran, atau null bila semua aturan lolos
     */
    private function periksaAturan(int $memberId, int $bookId, string $tanggalPinjam, string $jatuhTempo): ?string
    {
        // Aturan 1: anggota harus ada dan berstatus aktif
        $member = $this->memberModel->find($memberId);

        if ($member === null) {
            return 'Anggota yang dipilih tidak ditemukan.';
        }

        if ($member['status'] !== 'aktif') {
            return 'Anggota "' . $member['nama'] . '" berstatus ' . $member['status']
                . ' sehingga belum boleh meminjam.';
        }

        // Aturan 2: buku harus ada dan stoknya tersedia
        $book = $this->bookModel->find($bookId);

        if ($book === null) {
            return 'Buku yang dipilih tidak ditemukan.';
        }

        if ((int) $book['stok_tersedia'] < 1) {
            return 'Stok buku "' . $book['judul'] . '" sedang habis dipinjam.';
        }

        // Aturan 3: tidak boleh memegang judul yang sama dua kali
        if ($this->trxModel->sedangMeminjamBuku($memberId, $bookId)) {
            return 'Anggota "' . $member['nama'] . '" masih memegang buku "' . $book['judul'] . '".';
        }

        // Aturan 4: batas jumlah pinjaman aktif per anggota
        $maxPinjam = $this->settingModel->ambilAngka('max_pinjam_buku', 3);
        $aktif     = $this->trxModel->jumlahPinjamanAktif($memberId);

        if ($aktif >= $maxPinjam) {
            return 'Anggota "' . $member['nama'] . '" sudah meminjam ' . $aktif
                . ' buku (batas maksimal ' . $maxPinjam . ').';
        }

        // Aturan 5: urutan tanggal harus masuk akal
        if ($jatuhTempo < $tanggalPinjam) {
            return 'Tanggal jatuh tempo tidak boleh lebih awal dari tanggal pinjam.';
        }

        return null;
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
