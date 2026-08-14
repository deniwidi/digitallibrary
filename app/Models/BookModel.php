<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BookModel
 * ---------------------------------------------------------------------
 * Mengelola seluruh query ke tabel `books` (CRUD + Search + Pagination),
 * termasuk relasinya ke tabel `categories`.
 *
 * Semua query dibangun lewat Query Builder CodeIgniter, sehingga nilai
 * dari input user otomatis di-bind/escape (prepared statement) => aman
 * dari SQL Injection.
 *
 * Catatan kolom stok:
 *   - `stok`          = jumlah eksemplar total milik perpustakaan
 *   - `stok_tersedia` = eksemplar yang masih ada di rak (berkurang saat
 *                       dipinjam, bertambah lagi saat dikembalikan)
 */
class BookModel extends Model
{
    protected $table          = 'books';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;  // created_at & updated_at otomatis
    protected $useSoftDeletes = true;  // delete() hanya mengisi deleted_at

    /**
     * Kolom yang boleh diisi lewat insert()/update().
     * Kolom di luar daftar ini diabaikan => proteksi mass assignment.
     */
    protected $allowedFields = [
        'kode_buku',
        'judul',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'isbn',
        'category_id',
        'stok',
        'stok_tersedia',
        'sampul',
        'sinopsis',
        'rating',
    ];

    /**
     * Aturan validasi dipusatkan di Model agar Controller tetap ramping.
     *
     * Catatan:
     *  - Field 'id' wajib punya aturan sendiri karena dipakai sebagai
     *    placeholder {id} pada rule is_unique (aturan CI 4.3+).
     *  - Batas tahun 2155 mengikuti rentang tipe kolom YEAR di MySQL
     *    (1901-2155).
     */
    protected $validationRules = [
        'id'            => 'permit_empty|is_natural',
        'kode_buku'     => 'required|max_length[20]|is_unique[books.kode_buku,id,{id}]',
        'judul'         => 'required|min_length[3]|max_length[150]',
        'penulis'       => 'required|min_length[3]|max_length[100]',
        'penerbit'      => 'required|min_length[2]|max_length[100]',
        'tahun_terbit'  => 'required|integer|greater_than[1900]|less_than_equal_to[2155]',
        'isbn'          => 'permit_empty|max_length[20]',
        'category_id'   => 'required|is_natural_no_zero',
        'stok'          => 'required|is_natural',
        'stok_tersedia' => 'permit_empty|is_natural',
        'sinopsis'      => 'permit_empty|max_length[2000]',
        'rating'        => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[5]',
    ];

    protected $validationMessages = [
        'kode_buku' => [
            'required'  => 'Kode buku wajib diisi.',
            'is_unique' => 'Kode buku tersebut sudah dipakai buku lain.',
        ],
        'judul' => [
            'required'   => 'Judul buku wajib diisi.',
            'min_length' => 'Judul buku minimal 3 karakter.',
            'max_length' => 'Judul buku maksimal 150 karakter.',
        ],
        'penulis' => [
            'required'   => 'Nama penulis wajib diisi.',
            'min_length' => 'Nama penulis minimal 3 karakter.',
        ],
        'penerbit' => [
            'required'   => 'Nama penerbit wajib diisi.',
            'min_length' => 'Nama penerbit minimal 2 karakter.',
        ],
        'tahun_terbit' => [
            'required'           => 'Tahun terbit wajib diisi.',
            'integer'            => 'Tahun terbit harus berupa angka.',
            'greater_than'       => 'Tahun terbit minimal 1901.',
            'less_than_equal_to' => 'Tahun terbit maksimal 2155.',
        ],
        'category_id' => [
            'required'            => 'Kategori wajib dipilih.',
            'is_natural_no_zero'  => 'Kategori yang dipilih tidak valid.',
        ],
        'stok' => [
            'required'   => 'Jumlah stok wajib diisi.',
            'is_natural' => 'Stok harus berupa angka dan tidak boleh negatif.',
        ],
        'rating' => [
            'decimal'             => 'Rating harus berupa angka, contoh: 4.5',
            'greater_than_equal_to' => 'Rating minimal 0.',
            'less_than_equal_to'  => 'Rating maksimal 5.',
        ],
    ];

    /**
     * Ambil data buku dengan pencarian + filter kategori + pagination.
     *
     * JOIN ke `categories` dilakukan di sini supaya nama kategori ikut
     * terbawa dalam satu query (menghindari N+1 query di dalam view).
     *
     * Semua nama kolom diberi awalan nama tabel (books.id, dst.) karena
     * setelah JOIN ada kolom yang namanya sama di kedua tabel.
     *
     * @param  string|null $keyword    Judul/penulis/penerbit/kode/ISBN
     * @param  int|null    $categoryId Filter kategori (null = semua)
     * @param  int         $perPage    Jumlah baris per halaman
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $keyword = null, ?int $categoryId = null, int $perPage = 10): array
    {
        $this->select('books.*, categories.nama AS nama_kategori')
             ->join('categories', 'categories.id = books.category_id', 'left');

        if ($keyword !== null && $keyword !== '') {
            // groupStart()/groupEnd() membungkus rangkaian OR di dalam
            // tanda kurung agar tidak "bocor" ke filter kategori.
            $this->groupStart()
                 ->like('books.judul', $keyword)
                 ->orLike('books.penulis', $keyword)
                 ->orLike('books.penerbit', $keyword)
                 ->orLike('books.kode_buku', $keyword)
                 ->orLike('books.isbn', $keyword)
                 ->groupEnd();
        }

        if ($categoryId !== null && $categoryId > 0) {
            $this->where('books.category_id', $categoryId);
        }

        // 'books' = nama grup pager, dipakai lagi saat merender link
        return $this->orderBy('books.id', 'DESC')->paginate($perPage, 'books');
    }

    /**
     * Ambil satu buku beserta nama kategorinya.
     *
     * @param  int        $id
     * @return array|null Data buku, atau null bila tidak ditemukan
     */
    public function getWithCategory(int $id): ?array
    {
        return $this->select('books.*, categories.nama AS nama_kategori')
            ->join('categories', 'categories.id = books.category_id', 'left')
            ->where('books.id', $id)
            ->first();
    }

    /**
     * Hasilkan kode buku berikutnya secara otomatis (BK-0001, BK-0002, ...).
     *
     * Query lewat db->table() (bukan Model) supaya baris yang sudah
     * di-soft-delete IKUT terhitung, sehingga kode bekas tidak dipakai
     * ulang dan memicu error duplikat.
     *
     * @return string Kode buku baru yang belum terpakai
     */
    public function generateKode(): string
    {
        // SUBSTRING(kode_buku, 4) mengambil angka setelah "BK-", lalu
        // di-CAST ke UNSIGNED agar diurutkan sebagai bilangan.
        $baris = $this->db->table($this->table)
            ->select('MAX(CAST(SUBSTRING(kode_buku, 4) AS UNSIGNED)) AS nomor', false)
            ->get()
            ->getRowArray();

        $nomor = (int) ($baris['nomor'] ?? 0) + 1;

        return 'BK-' . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung berapa eksemplar buku ini yang sedang dipinjam
     * (transaksi dengan tanggal_kembali masih NULL).
     *
     * Dipakai untuk dua hal:
     *  - menolak penghapusan buku yang masih beredar;
     *  - menjaga agar stok tidak diturunkan di bawah jumlah yang dipinjam.
     *
     * @param  int $bookId
     * @return int
     */
    public function jumlahDipinjam(int $bookId): int
    {
        return $this->db->table('transactions')
            ->where('book_id', $bookId)
            ->where('tanggal_kembali', null)
            ->countAllResults();
    }

    /**
     * Pencarian buku untuk Global Search di topbar.
     *
     * Berbeda dengan cariUntukSelect2(), fungsi ini TIDAK menyaring stok
     * karena pencarian global harus bisa menemukan buku apa pun - termasuk
     * yang sedang habis dipinjam.
     *
     * Keyword dipecah per kata dan setiap kata wajib cocok di salah satu
     * kolom (judul/penulis/penerbit/kode/ISBN). Nilainya di-bind Query
     * Builder => aman dari SQL Injection.
     *
     * @param  string $keyword Kata kunci dari kotak pencarian
     * @param  int    $limit   Batas hasil agar dropdown tidak kepanjangan
     * @return array<int, array<string, mixed>>
     */
    public function cariGlobal(string $keyword, int $limit = 5): array
    {
        if ($keyword === '') {
            return [];
        }

        $builder = $this->select('books.id, books.kode_buku, books.judul, books.penulis,
                                  books.sampul, books.stok, books.stok_tersedia,
                                  categories.nama AS nama_kategori')
            ->join('categories', 'categories.id = books.category_id', 'left');

        foreach (preg_split('/\s+/', $keyword) as $kata) {
            if ($kata === '') {
                continue;
            }

            $builder->groupStart()
                    ->like('books.judul', $kata)
                    ->orLike('books.penulis', $kata)
                    ->orLike('books.penerbit', $kata)
                    ->orLike('books.kode_buku', $kata)
                    ->orLike('books.isbn', $kata)
                    ->groupEnd();
        }

        return $builder->orderBy('books.judul', 'ASC')->findAll($limit);
    }

    /**
     * Pencarian buku bersisa stok untuk dropdown AJAX (Select2).
     *
     * Sama seperti pencarian anggota: keyword dipecah per kata dan SETIAP
     * kata wajib cocok pada salah satu kolom (judul / penulis / kode /
     * ISBN), sehingga "pelangi" tetap menemukan "Laskar Pelangi" dan
     * "hirata laskar" pun tetap ketemu meski urutannya terbalik.
     *
     * JOIN ke categories dilakukan agar nama kategori bisa ditampilkan
     * sebagai keterangan di bawah judul pada dropdown.
     *
     * Nilai keyword di-bind Query Builder => aman dari SQL Injection.
     *
     * @param  string $keyword Kata kunci ketikan user (boleh kosong)
     * @param  int    $limit   Jumlah baris per permintaan AJAX
     * @param  int    $offset  Lompatan baris untuk infinite scroll
     * @return array<int, array<string, mixed>>
     */
    public function cariUntukSelect2(string $keyword = '', int $limit = 15, int $offset = 0): array
    {
        $builder = $this->select('books.id, books.kode_buku, books.judul, books.penulis,
                                  books.stok_tersedia, categories.nama AS nama_kategori')
            ->join('categories', 'categories.id = books.category_id', 'left')
            ->where('books.stok_tersedia >', 0);

        if ($keyword !== '') {
            foreach (preg_split('/\s+/', $keyword) as $kata) {
                if ($kata === '') {
                    continue;
                }

                $builder->groupStart()
                        ->like('books.judul', $kata)
                        ->orLike('books.penulis', $kata)
                        ->orLike('books.kode_buku', $kata)
                        ->orLike('books.isbn', $kata)
                        ->groupEnd();
            }
        }

        // Ambil $limit + 1 baris: kelebihan satu baris menjadi penanda
        // "masih ada halaman berikutnya" bagi controller.
        return $builder->orderBy('books.judul', 'ASC')
            ->findAll($limit + 1, $offset);
    }

    /**
     * Daftar buku bersisa stok untuk dropdown biasa (non-AJAX).
     * Disimpan sebagai cadangan bila suatu saat form ingin memakai
     * <select> polos; form peminjaman sendiri sudah memakai Select2 AJAX.
     *
     * @return array<int, string> [id => "BK-0001 - Laskar Pelangi (5 tersedia)"]
     */
    public function dropdownTersedia(): array
    {
        $baris = $this->select('id, kode_buku, judul, stok_tersedia')
            ->where('stok_tersedia >', 0)
            ->orderBy('judul', 'ASC')
            ->findAll();

        $hasil = [];
        foreach ($baris as $row) {
            $hasil[$row['id']] = $row['kode_buku'] . ' - ' . $row['judul']
                . ' (' . $row['stok_tersedia'] . ' tersedia)';
        }

        return $hasil;
    }

    /**
     * Ubah stok tersedia sebuah buku sebesar $selisih (+1 saat buku
     * kembali, -1 saat dipinjam).
     *
     * Perubahan dilakukan lewat ekspresi SQL (stok_tersedia = stok_tersedia + n)
     * agar aman dari race condition bila ada dua petugas memproses
     * bersamaan - nilainya dihitung oleh database, bukan oleh PHP.
     *
     * @param  int $bookId
     * @param  int $selisih Boleh negatif
     * @return void
     */
    public function ubahStokTersedia(int $bookId, int $selisih): void
    {
        $this->db->table($this->table)
            ->set('stok_tersedia', 'stok_tersedia + ' . (int) $selisih, false)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->where('id', $bookId)
            // Jaga-jaga agar stok tidak pernah melebihi total eksemplar
            ->where('stok_tersedia + ' . (int) $selisih . ' >= 0', null, false)
            ->update();
    }

    /**
     * Ubah jumlah eksemplar TOTAL sebuah buku sebesar $selisih.
     *
     * Dipakai modul Pengembalian saat buku dinyatakan hilang: eksemplarnya
     * dicoret dari koleksi (stok berkurang), sementara `stok_tersedia`
     * tidak ditambah karena bukunya memang tidak kembali ke rak.
     *
     * Sama seperti ubahStokTersedia(), perhitungan dilakukan database
     * lewat ekspresi SQL agar aman dari race condition.
     *
     * @param  int $bookId
     * @param  int $selisih Boleh negatif
     * @return void
     */
    public function ubahStok(int $bookId, int $selisih): void
    {
        $this->db->table($this->table)
            ->set('stok', 'stok + ' . (int) $selisih, false)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->where('id', $bookId)
            // Jaga agar stok tidak pernah menjadi negatif
            ->where('stok + ' . (int) $selisih . ' >= 0', null, false)
            ->update();
    }

    /**
     * Angka ringkasan untuk kartu di halaman Katalog Buku.
     *
     * @return array{judul:int, eksemplar:int, tersedia:int, dipinjam:int}
     */
    public function ringkasan(): array
    {
        $baris = $this->db->table('books')
            ->select('COUNT(id) AS judul, COALESCE(SUM(stok),0) AS eksemplar,
                      COALESCE(SUM(stok_tersedia),0) AS tersedia')
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $eksemplar = (int) ($baris['eksemplar'] ?? 0);
        $tersedia  = (int) ($baris['tersedia'] ?? 0);

        return [
            'judul'     => (int) ($baris['judul'] ?? 0),
            'eksemplar' => $eksemplar,
            'tersedia'  => $tersedia,
            // Selisihnya = eksemplar yang sedang berada di tangan anggota
            'dipinjam'  => max(0, $eksemplar - $tersedia),
        ];
    }
}
