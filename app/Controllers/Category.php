<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Category Controller
 * ---------------------------------------------------------------------
 * Modul Kategori: master kategori buku yang menjadi induk (parent) dari
 * tabel `books`. Menyediakan daftar dengan pencarian & paginasi, serta
 * proses tambah, ubah, dan hapus.
 *
 * Pembagian tugas mengikuti pola MVC:
 *   - Controller hanya mengatur ALUR (baca input -> panggil model ->
 *     kirim ke view / redirect).
 *   - Query & aturan validasi berada di App\Models\CategoryModel.
 *
 * Akses dijaga AuthFilter lewat group route di app/Config/Routes.php,
 * dan seluruh form POST otomatis diproteksi filter CSRF global.
 */
class Category extends BaseController
{
    /**
     * Jumlah baris per halaman pada daftar kategori.
     */
    private const PER_PAGE = 10;

    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    /**
     * GET /categories
     * Menampilkan daftar kategori + pencarian + paginasi, lengkap dengan
     * jumlah buku yang memakai tiap kategori.
     *
     * @return string HTML halaman daftar kategori
     */
    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('keyword'));

        $categories = $this->categoryModel->search($keyword, self::PER_PAGE);

        /*
         * Pager perlu tahu parameter apa yang harus ikut menempel di link
         * halaman 2, 3, dst. Tanpa only(), keyword hilang saat pindah halaman.
         */
        $pager = $this->categoryModel->pager;
        $pager->only(['keyword']);

        // Nomor urut baris agar berlanjut di halaman berikutnya
        $halaman   = (int) ($pager->getCurrentPage('categories') ?: 1);
        $nomorAwal = ($halaman - 1) * self::PER_PAGE + 1;

        return view('category/index', [
            'title'        => 'Kategori',
            'pageTitle'    => 'Kategori Buku',
            'pageSubtitle' => 'Kelola pengelompokan koleksi: tambah, ubah, hapus, dan cari kategori.',
            'categories'   => $categories,
            'pager'        => $pager,
            'keyword'      => $keyword,
            'nomorAwal'    => $nomorAwal,
            'totalData'    => $pager->getTotal('categories'),
            'ringkasan'    => $this->categoryModel->ringkasan(),
            // Satu query untuk seluruh baris di halaman ini (bukan N+1)
            'jumlahBuku'   => $this->categoryModel->hitungBuku(array_column($categories, 'id')),
        ]);
    }

    /**
     * GET /categories/create
     * Menampilkan form tambah kategori.
     *
     * @return string HTML form tambah
     */
    public function create(): string
    {
        return view('category/create', [
            'title'        => 'Tambah Kategori',
            'pageTitle'    => 'Tambah Kategori',
            'pageSubtitle' => 'Buat kelompok baru untuk koleksi buku.',
        ]);
    }

    /**
     * POST /categories/store
     * Memproses penyimpanan kategori baru.
     *
     * @return RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $data       = $this->ambilInput();
        $data['id'] = 0; // mengisi placeholder {id} pada rule is_unique

        if (! $this->categoryModel->insert($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->categoryModel->errors());
        }

        return redirect()->to(site_url('categories'))
            ->with('success', 'Kategori "' . $data['nama'] . '" berhasil ditambahkan.');
    }

    /**
     * GET /categories/{id}/edit
     * Menampilkan form ubah kategori.
     *
     * @param  int $id ID kategori
     * @return RedirectResponse|string
     */
    public function edit(int $id)
    {
        $category = $this->categoryModel->find($id);

        if ($category === null) {
            return redirect()->to(site_url('categories'))
                ->with('error', 'Data kategori tidak ditemukan.');
        }

        return view('category/edit', [
            'title'        => 'Ubah Kategori',
            'pageTitle'    => 'Ubah Kategori',
            'pageSubtitle' => 'Perbarui data kategori ' . $category['nama'] . '.',
            'category'     => $category,
            // Ditampilkan sebagai info: berapa buku yang terdampak perubahan ini
            'jumlahBuku'   => $this->categoryModel->jumlahPemakai($id),
        ]);
    }

    /**
     * POST /categories/{id}/update
     * Memproses perubahan data kategori.
     *
     * @param  int $id ID kategori
     * @return RedirectResponse
     */
    public function update(int $id): RedirectResponse
    {
        if ($this->categoryModel->find($id) === null) {
            return redirect()->to(site_url('categories'))
                ->with('error', 'Data kategori tidak ditemukan.');
        }

        $data       = $this->ambilInput($id);
        $data['id'] = $id; // agar is_unique tidak menganggap datanya sendiri duplikat

        if (! $this->categoryModel->update($id, $data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->categoryModel->errors());
        }

        return redirect()->to(site_url('categories'))
            ->with('success', 'Kategori "' . $data['nama'] . '" berhasil diperbarui.');
    }

    /**
     * POST /categories/{id}/delete
     * Menghapus kategori (soft delete).
     *
     * Kategori yang masih dipakai buku TIDAK boleh dihapus: relasi
     * fk_books_category memakai ON DELETE RESTRICT, dan membiarkan buku
     * menunjuk kategori yang hilang akan membuat katalog tidak konsisten.
     *
     * @param  int $id ID kategori
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        $category = $this->categoryModel->find($id);

        if ($category === null) {
            return redirect()->to(site_url('categories'))
                ->with('error', 'Data kategori tidak ditemukan.');
        }

        $pemakai = $this->categoryModel->jumlahPemakai($id);

        if ($pemakai > 0) {
            return redirect()->to(site_url('categories'))
                ->with('error', 'Kategori "' . $category['nama'] . '" tidak bisa dihapus karena masih '
                    . 'dipakai oleh ' . $pemakai . ' buku. Pindahkan dulu buku tersebut ke kategori lain.');
        }

        $this->categoryModel->delete($id);

        return redirect()->to(site_url('categories'))
            ->with('success', 'Kategori "' . $category['nama'] . '" berhasil dihapus.');
    }

    /**
     * Kumpulkan field kategori dari request POST.
     * Dipakai bersama oleh store() dan update().
     *
     * Bila slug dikosongkan user, slug dibuat otomatis dari nama
     * ("Self Improvement" => "self-improvement") dan dijamin unik.
     *
     * @param  int|null $id ID baris saat mode edit (agar slug miliknya
     *                      sendiri tidak dianggap bentrok)
     * @return array<string, mixed>
     */
    private function ambilInput(?int $id = null): array
    {
        $nama = trim((string) $this->request->getPost('nama'));
        $slug = trim((string) $this->request->getPost('slug'));

        if ($slug === '' && $nama !== '') {
            $slug = $this->categoryModel->generateSlug($nama, $id);
        }

        $deskripsi = trim((string) $this->request->getPost('deskripsi'));

        return [
            'nama'      => $nama,
            'slug'      => $slug,
            // Kolom opsional yang dikosongkan disimpan sebagai NULL, bukan ''
            'deskripsi' => $deskripsi === '' ? null : $deskripsi,
        ];
    }
}
