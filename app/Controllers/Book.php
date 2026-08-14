<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\CategoryModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Book Controller
 * ---------------------------------------------------------------------
 * Modul Katalog Buku: menampilkan daftar buku (dengan pencarian, filter
 * kategori, dan paginasi) serta menangani tambah, ubah, dan hapus data,
 * termasuk unggah file sampul.
 *
 * Pembagian tugas mengikuti pola MVC:
 *   - Controller hanya mengatur ALUR (baca input -> panggil model ->
 *     kirim ke view / redirect).
 *   - Query & aturan validasi data berada di App\Models\BookModel.
 *
 * Akses dijaga AuthFilter lewat group route di app/Config/Routes.php,
 * dan seluruh form POST otomatis diproteksi filter CSRF global.
 */
class Book extends BaseController
{
    /**
     * Jumlah baris per halaman pada daftar buku.
     */
    private const PER_PAGE = 10;

    /**
     * Lokasi penyimpanan file sampul (di dalam folder public/).
     */
    private const FOLDER_SAMPUL = 'uploads/covers';

    protected BookModel $bookModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->bookModel     = new BookModel();
        $this->categoryModel = new CategoryModel();
    }

    /**
     * GET /books
     * Menampilkan daftar buku + pencarian + filter kategori + paginasi.
     *
     * @return string HTML halaman katalog buku
     */
    public function index(): string
    {
        // Ambil kata kunci & filter dari query string (?keyword=...&category_id=...)
        $keyword    = trim((string) $this->request->getGet('keyword'));
        $categoryId = (int) $this->request->getGet('category_id');

        // Query utama: search + join kategori + paginate (lihat BookModel::search)
        $books = $this->bookModel->search($keyword, $categoryId, self::PER_PAGE);

        /*
         * Pager perlu tahu parameter apa saja yang harus ikut menempel di
         * link halaman 2, 3, dst. Tanpa only(), keyword & filter kategori
         * akan hilang begitu user berpindah halaman.
         */
        $pager = $this->bookModel->pager;
        $pager->only(['keyword', 'category_id']);

        // Nomor urut baris agar tetap berlanjut di halaman berikutnya
        $halaman   = (int) ($pager->getCurrentPage('books') ?: 1);
        $nomorAwal = ($halaman - 1) * self::PER_PAGE + 1;

        return view('book/index', [
            'title'        => 'Katalog Buku',
            'pageTitle'    => 'Katalog Buku',
            'pageSubtitle' => 'Kelola koleksi buku perpustakaan: tambah, ubah, hapus, dan cari.',
            'books'        => $books,
            'pager'        => $pager,
            'keyword'      => $keyword,
            'categoryId'   => $categoryId,
            'categories'   => $this->categoryModel->dropdown(),
            'nomorAwal'    => $nomorAwal,
            'totalData'    => $pager->getTotal('books'),
            'ringkasan'    => $this->bookModel->ringkasan(),
        ]);
    }

    /**
     * GET /books/create
     * Menampilkan form tambah buku dengan kode buku terisi otomatis.
     *
     * @return string HTML form tambah
     */
    public function create(): string
    {
        return view('book/create', [
            'title'        => 'Tambah Buku',
            'pageTitle'    => 'Tambah Buku',
            'pageSubtitle' => 'Isi data buku baru, lalu simpan ke katalog.',
            'kodeBaru'     => $this->bookModel->generateKode(),
            'categories'   => $this->categoryModel->dropdown(),
        ]);
    }

    /**
     * POST /books/store
     * Memproses penyimpanan buku baru.
     *
     * Urutan sengaja dibuat: validasi file -> validasi data -> pindahkan
     * file -> simpan. Dengan begitu, kalau ada data yang tidak lolos
     * validasi, tidak ada file sampul yang terlanjur menumpuk di server.
     *
     * @return RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $file    = $this->request->getFile('sampul');
        $adaFile = $file !== null && $file->isValid() && ! $file->hasMoved();

        // 1) Validasi file sampul (hanya bila user benar-benar mengunggah)
        if ($adaFile && ! $this->validate($this->aturanSampul())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // 2) Validasi data teks lewat aturan milik Model
        $data       = $this->ambilInput();
        $data['id'] = 0; // mengisi placeholder {id} pada rule is_unique

        // Buku baru: seluruh eksemplar masih berada di rak
        $data['stok_tersedia'] = $data['stok'];

        if (! $this->bookModel->validate($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->bookModel->errors());
        }

        // 3) Semua valid -> pindahkan file sampul ke public/uploads/covers
        if ($adaFile) {
            $data['sampul'] = $this->pindahkanSampul($file);
        }

        if (! $this->bookModel->insert($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->bookModel->errors());
        }

        return redirect()->to(site_url('books'))
            ->with('success', 'Buku "' . $data['judul'] . '" berhasil ditambahkan ke katalog.');
    }

    /**
     * GET /books/{id}/edit
     * Menampilkan form ubah data buku.
     *
     * @param  int $id ID buku
     * @return RedirectResponse|string
     */
    public function edit(int $id)
    {
        $book = $this->bookModel->find($id);

        if ($book === null) {
            return redirect()->to(site_url('books'))
                ->with('error', 'Data buku tidak ditemukan.');
        }

        return view('book/edit', [
            'title'        => 'Ubah Buku',
            'pageTitle'    => 'Ubah Buku',
            'pageSubtitle' => 'Perbarui data buku ' . $book['judul'] . '.',
            'book'         => $book,
            'categories'   => $this->categoryModel->dropdown(),
            // Dipakai view untuk memberi tahu batas minimal stok
            'dipinjam'     => $this->bookModel->jumlahDipinjam($id),
        ]);
    }

    /**
     * POST /books/{id}/update
     * Memproses perubahan data buku.
     *
     * @param  int $id ID buku
     * @return RedirectResponse
     */
    public function update(int $id): RedirectResponse
    {
        $book = $this->bookModel->find($id);

        if ($book === null) {
            return redirect()->to(site_url('books'))
                ->with('error', 'Data buku tidak ditemukan.');
        }

        $file    = $this->request->getFile('sampul');
        $adaFile = $file !== null && $file->isValid() && ! $file->hasMoved();

        if ($adaFile && ! $this->validate($this->aturanSampul())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data       = $this->ambilInput();
        $data['id'] = $id; // placeholder {id} agar kode buku sendiri tidak dianggap duplikat

        /*
         * Stok tidak boleh diturunkan di bawah jumlah eksemplar yang
         * sedang dipinjam - kalau tidak, stok_tersedia jadi negatif dan
         * angka di dashboard ikut kacau.
         */
        $dipinjam = $this->bookModel->jumlahDipinjam($id);

        if ($data['stok'] < $dipinjam) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Stok tidak boleh kurang dari ' . $dipinjam
                    . ' karena sebanyak itulah eksemplar yang sedang dipinjam.');
        }

        // Sisa di rak = total stok - yang sedang dipinjam
        $data['stok_tersedia'] = $data['stok'] - $dipinjam;

        if (! $this->bookModel->validate($data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->bookModel->errors());
        }

        // Tangani file sampul: ganti dengan yang baru, atau hapus bila diminta
        $sampulLama = $book['sampul'];

        if ($adaFile) {
            $data['sampul'] = $this->pindahkanSampul($file);
            $this->hapusFileSampul($sampulLama);
        } elseif ($this->request->getPost('hapus_sampul') === '1') {
            $data['sampul'] = null;
            $this->hapusFileSampul($sampulLama);
        }

        if (! $this->bookModel->update($id, $data)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->bookModel->errors());
        }

        return redirect()->to(site_url('books'))
            ->with('success', 'Data buku "' . $data['judul'] . '" berhasil diperbarui.');
    }

    /**
     * POST /books/{id}/delete
     * Menghapus buku (soft delete: baris hanya ditandai `deleted_at`,
     * sehingga riwayat transaksinya tetap utuh).
     *
     * @param  int $id ID buku
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        $book = $this->bookModel->find($id);

        if ($book === null) {
            return redirect()->to(site_url('books'))
                ->with('error', 'Data buku tidak ditemukan.');
        }

        // Tolak penghapusan bila masih ada eksemplar yang belum kembali
        $dipinjam = $this->bookModel->jumlahDipinjam($id);

        if ($dipinjam > 0) {
            return redirect()->to(site_url('books'))
                ->with('error', 'Buku "' . $book['judul'] . '" tidak bisa dihapus karena '
                    . $dipinjam . ' eksemplar masih dipinjam.');
        }

        /*
         * File sampul sengaja TIDAK ikut dihapus. Penghapusan di sini
         * bersifat soft delete, jadi barisnya masih bisa dipulihkan lewat
         * database bila ternyata terhapus karena salah klik.
         */
        $this->bookModel->delete($id);

        return redirect()->to(site_url('books'))
            ->with('success', 'Buku "' . $book['judul'] . '" berhasil dihapus dari katalog.');
    }

    /**
     * Kumpulkan field buku dari request POST.
     * Dipakai bersama oleh store() dan update() agar daftar field tidak
     * ditulis dua kali.
     *
     * @return array<string, mixed>
     */
    private function ambilInput(): array
    {
        // Kolom opsional yang dikosongkan disimpan sebagai NULL, bukan ''
        $kosongJadiNull = static fn (?string $nilai): ?string => ($nilai === null || trim($nilai) === '')
            ? null
            : trim($nilai);

        return [
            'kode_buku'    => trim((string) $this->request->getPost('kode_buku')),
            'judul'        => trim((string) $this->request->getPost('judul')),
            'penulis'      => trim((string) $this->request->getPost('penulis')),
            'penerbit'     => trim((string) $this->request->getPost('penerbit')),
            'tahun_terbit' => (int) $this->request->getPost('tahun_terbit'),
            'isbn'         => $kosongJadiNull($this->request->getPost('isbn')),
            'category_id'  => (int) $this->request->getPost('category_id'),
            'stok'         => (int) $this->request->getPost('stok'),
            'sinopsis'     => $kosongJadiNull($this->request->getPost('sinopsis')),
            'rating'       => (float) $this->request->getPost('rating'),
        ];
    }

    /**
     * Aturan validasi file sampul.
     * Dipisah agar store() dan update() memakai batasan yang sama persis.
     *
     * @return array<string, array<string, mixed>>
     */
    private function aturanSampul(): array
    {
        return [
            'sampul' => [
                'rules' => 'is_image[sampul]'
                    . '|mime_in[sampul,image/jpg,image/jpeg,image/png,image/webp]'
                    . '|max_size[sampul,1024]',   // maksimal 1 MB
                'errors' => [
                    'is_image' => 'File sampul harus berupa gambar.',
                    'mime_in'  => 'Format sampul harus JPG, PNG, atau WEBP.',
                    'max_size' => 'Ukuran sampul maksimal 1 MB.',
                ],
            ],
        ];
    }

    /**
     * Pindahkan file sampul ke public/uploads/covers dengan nama acak.
     *
     * getRandomName() mencegah dua hal sekaligus: nama file yang bentrok,
     * dan nama asli dari user yang bisa saja mengandung karakter aneh.
     *
     * @param  \CodeIgniter\HTTP\Files\UploadedFile $file
     * @return string Nama file hasil simpan
     */
    private function pindahkanSampul($file): string
    {
        $namaFile = $file->getRandomName();
        $file->move(FCPATH . self::FOLDER_SAMPUL, $namaFile);

        return $namaFile;
    }

    /**
     * Hapus file sampul lama dari disk (dipanggil saat diganti/dihapus).
     *
     * @param  string|null $namaFile
     * @return void
     */
    private function hapusFileSampul(?string $namaFile): void
    {
        if (empty($namaFile)) {
            return;
        }

        $path = FCPATH . self::FOLDER_SAMPUL . DIRECTORY_SEPARATOR . $namaFile;

        if (is_file($path)) {
            unlink($path);
        }
    }
}
