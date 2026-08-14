<?php

namespace App\Controllers;

use App\Models\MemberModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Member Controller
 * ---------------------------------------------------------------------
 * Modul Anggota: menampilkan daftar anggota (dengan pencarian dan
 * paginasi) serta menangani proses tambah, ubah, dan hapus data.
 *
 * Pembagian tugas mengikuti pola MVC:
 *   - Controller ini hanya mengatur ALUR (baca input -> panggil model ->
 *     kirim ke view / redirect).
 *   - Seluruh query & aturan validasi berada di App\Models\MemberModel.
 *
 * Akses dijaga AuthFilter lewat group route di app/Config/Routes.php,
 * dan seluruh form POST otomatis diproteksi filter CSRF global.
 */
class Member extends BaseController
{
    /**
     * Jumlah baris per halaman pada daftar anggota.
     */
    private const PER_PAGE = 10;

    protected MemberModel $memberModel;

    public function __construct()
    {
        $this->memberModel = new MemberModel();
    }

    /**
     * GET /members
     * Menampilkan daftar anggota + pencarian + filter status + paginasi.
     *
     * @return string HTML halaman daftar anggota
     */
    public function index(): string
    {
        // Ambil kata kunci & filter dari query string (?keyword=...&status=...)
        // trim() membersihkan spasi agar pencarian " budi " tetap cocok.
        $keyword = trim((string) $this->request->getGet('keyword'));
        $status  = trim((string) $this->request->getGet('status'));

        // Query utama: search + paginate sekaligus (lihat MemberModel::search)
        $members = $this->memberModel->search($keyword, $status, self::PER_PAGE);

        /*
         * Pager perlu tahu parameter apa saja yang harus ikut menempel di
         * link halaman 2, 3, dst. Tanpa only(), keyword & status akan
         * hilang begitu user berpindah halaman.
         */
        $pager = $this->memberModel->pager;
        $pager->only(['keyword', 'status']);

        // Nomor urut baris agar tetap berlanjut di halaman berikutnya
        // (halaman 2 mulai dari 11, halaman 3 dari 21, dan seterusnya).
        $halaman   = (int) ($pager->getCurrentPage('members') ?: 1);
        $nomorAwal = ($halaman - 1) * self::PER_PAGE + 1;

        return view('member/index', [
            'title'        => 'Anggota',
            'pageTitle'    => 'Data Anggota',
            'pageSubtitle' => 'Kelola data anggota perpustakaan: tambah, ubah, hapus, dan cari.',
            'members'      => $members,
            'pager'        => $pager,
            'keyword'      => $keyword,
            'status'       => $status,
            'nomorAwal'    => $nomorAwal,
            'totalData'    => $pager->getTotal('members'),
            'ringkasan'    => $this->memberModel->ringkasanStatus(),
        ]);
    }

    /**
     * GET /members/create
     * Menampilkan form tambah anggota. Kode anggota dan tanggal daftar
     * sudah terisi otomatis, tetapi masih bisa diubah petugas.
     *
     * @return string HTML form tambah
     */
    public function create(): string
    {
        return view('member/create', [
            'title'        => 'Tambah Anggota',
            'pageTitle'    => 'Tambah Anggota',
            'pageSubtitle' => 'Isi data anggota baru, lalu simpan.',
            'kodeBaru'     => $this->memberModel->generateKode(),
        ]);
    }

    /**
     * POST /members/store
     * Memproses penyimpanan anggota baru.
     *
     * Validasi dijalankan otomatis oleh Model saat insert(); bila gagal,
     * user dikembalikan ke form dengan input lama (withInput) dan daftar
     * pesan error.
     *
     * @return RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $data = $this->ambilInput();

        // id = 0 dipakai untuk mengisi placeholder {id} pada aturan
        // is_unique. Kolom id tidak ada di allowedFields, jadi nilai ini
        // otomatis dibuang Model dan tidak ikut ter-INSERT.
        $data['id'] = 0;

        if (! $this->memberModel->insert($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->memberModel->errors());
        }

        return redirect()->to(site_url('members'))
            ->with('success', 'Anggota "' . $data['nama'] . '" berhasil ditambahkan.');
    }

    /**
     * GET /members/{id}/edit
     * Menampilkan form ubah data anggota.
     *
     * @param  int $id ID anggota yang akan diubah
     * @return RedirectResponse|string Form edit, atau redirect bila data tidak ada
     */
    public function edit(int $id)
    {
        $member = $this->memberModel->find($id);

        // Jaga-jaga bila ID dikarang lewat URL atau datanya sudah dihapus
        if ($member === null) {
            return redirect()->to(site_url('members'))
                ->with('error', 'Data anggota tidak ditemukan.');
        }

        return view('member/edit', [
            'title'        => 'Ubah Anggota',
            'pageTitle'    => 'Ubah Anggota',
            'pageSubtitle' => 'Perbarui data anggota ' . $member['nama'] . '.',
            'member'       => $member,
        ]);
    }

    /**
     * POST /members/{id}/update
     * Memproses perubahan data anggota.
     *
     * @param  int $id ID anggota yang diubah
     * @return RedirectResponse
     */
    public function update(int $id): RedirectResponse
    {
        if ($this->memberModel->find($id) === null) {
            return redirect()->to(site_url('members'))
                ->with('error', 'Data anggota tidak ditemukan.');
        }

        $data = $this->ambilInput();

        // Placeholder {id} diisi ID baris ini, supaya aturan is_unique
        // tidak menganggap email/kode miliknya sendiri sebagai duplikat.
        $data['id'] = $id;

        if (! $this->memberModel->update($id, $data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->memberModel->errors());
        }

        return redirect()->to(site_url('members'))
            ->with('success', 'Data anggota "' . $data['nama'] . '" berhasil diperbarui.');
    }

    /**
     * POST /members/{id}/delete
     * Menghapus anggota (soft delete: baris hanya ditandai `deleted_at`,
     * sehingga riwayat transaksinya tetap utuh).
     *
     * @param  int $id ID anggota yang dihapus
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        $member = $this->memberModel->find($id);

        if ($member === null) {
            return redirect()->to(site_url('members'))
                ->with('error', 'Data anggota tidak ditemukan.');
        }

        // Tolak penghapusan bila anggota masih memegang buku perpustakaan
        if ($this->memberModel->punyaPinjamanAktif($id)) {
            return redirect()->to(site_url('members'))
                ->with('error', 'Anggota "' . $member['nama'] . '" masih memiliki pinjaman aktif. '
                    . 'Proses pengembalian bukunya terlebih dahulu.');
        }

        $this->memberModel->delete($id);

        return redirect()->to(site_url('members'))
            ->with('success', 'Anggota "' . $member['nama'] . '" berhasil dihapus.');
    }

    /**
     * Kumpulkan field anggota dari request POST.
     * Dipakai bersama oleh store() dan update() agar daftar field tidak
     * ditulis dua kali (dan tidak mudah lupa disamakan).
     *
     * @return array<string, mixed> Data siap dikirim ke Model
     */
    private function ambilInput(): array
    {
        // Kolom opsional yang dikosongkan disimpan sebagai NULL, bukan
        // string kosong. Penting untuk `email` yang berindeks UNIQUE:
        // MySQL mengizinkan banyak baris NULL, tetapi menolak dua baris
        // yang sama-sama berisi ''.
        $kosongJadiNull = static fn (?string $nilai): ?string => ($nilai === null || trim($nilai) === '')
            ? null
            : trim($nilai);

        return [
            'kode_anggota'   => trim((string) $this->request->getPost('kode_anggota')),
            'nama'           => trim((string) $this->request->getPost('nama')),
            'email'          => $kosongJadiNull($this->request->getPost('email')),
            'telepon'        => $kosongJadiNull($this->request->getPost('telepon')),
            'alamat'         => $kosongJadiNull($this->request->getPost('alamat')),
            'jenis_kelamin'  => $this->request->getPost('jenis_kelamin'),
            'tanggal_daftar' => $this->request->getPost('tanggal_daftar'),
            'status'         => $this->request->getPost('status'),
        ];
    }
}
