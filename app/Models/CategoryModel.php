<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CategoryModel
 * ---------------------------------------------------------------------
 * Mengelola tabel `categories` (master kategori buku): CRUD, pencarian,
 * dan paginasi, sekaligus menjadi sumber data dropdown kategori pada
 * modul Katalog Buku.
 *
 * Semua query dibangun lewat Query Builder CodeIgniter, sehingga nilai
 * dari input user otomatis di-bind/escape (prepared statement) => aman
 * dari SQL Injection.
 */
class CategoryModel extends Model
{
    protected $table          = 'categories';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;  // created_at & updated_at otomatis
    protected $useSoftDeletes = true;  // delete() hanya mengisi deleted_at

    protected $allowedFields = ['nama', 'slug', 'deskripsi'];

    /**
     * Aturan validasi dipusatkan di Model.
     * Field 'id' wajib punya aturan sendiri karena dipakai sebagai
     * placeholder {id} pada rule is_unique (ketentuan CI 4.3+).
     */
    protected $validationRules = [
        'id'        => 'permit_empty|is_natural',
        'nama'      => 'required|min_length[3]|max_length[60]|is_unique[categories.nama,id,{id}]',
        'slug'      => 'required|min_length[3]|max_length[80]|alpha_dash|is_unique[categories.slug,id,{id}]',
        'deskripsi' => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'nama' => [
            'required'   => 'Nama kategori wajib diisi.',
            'min_length' => 'Nama kategori minimal 3 karakter.',
            'max_length' => 'Nama kategori maksimal 60 karakter.',
            'is_unique'  => 'Nama kategori tersebut sudah ada.',
        ],
        'slug' => [
            'required'   => 'Slug kategori wajib diisi.',
            'min_length' => 'Slug minimal 3 karakter.',
            'alpha_dash' => 'Slug hanya boleh berisi huruf, angka, strip (-), dan garis bawah (_).',
            'is_unique'  => 'Slug tersebut sudah dipakai kategori lain.',
        ],
        'deskripsi' => [
            'max_length' => 'Deskripsi maksimal 255 karakter.',
        ],
    ];

    /**
     * Ambil kategori dengan pencarian + pagination.
     *
     * Catatan: jumlah buku per kategori sengaja TIDAK di-JOIN di sini.
     * Query ber-JOIN + GROUP BY membuat countAllResults() milik paginate()
     * menghitung baris hasil join (bukan jumlah kategori), sehingga total
     * halaman jadi salah. Jumlah buku diambil terpisah lewat hitungBuku().
     *
     * @param  string|null $keyword Nama/slug/deskripsi kategori
     * @param  int         $perPage Jumlah baris per halaman
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $keyword = null, int $perPage = 10): array
    {
        if ($keyword !== null && $keyword !== '') {
            // groupStart()/groupEnd() membungkus rangkaian OR di dalam
            // tanda kurung agar tidak bercampur dengan kondisi lain
            // (mis. filter soft delete yang ditambahkan Model).
            $this->groupStart()
                 ->like('nama', $keyword)
                 ->orLike('slug', $keyword)
                 ->orLike('deskripsi', $keyword)
                 ->groupEnd();
        }

        // 'categories' = nama grup pager, dipakai lagi saat merender link
        return $this->orderBy('nama', 'ASC')->paginate($perPage, 'categories');
    }

    /**
     * Hitung jumlah buku aktif untuk sekumpulan kategori sekaligus.
     * Satu query untuk seluruh baris yang tampil di halaman, jadi tidak
     * terjadi N+1 query di dalam view.
     *
     * @param  array<int, int|string> $categoryIds Daftar ID kategori
     * @return array<int|string, int> [category_id => jumlah_buku]
     */
    public function hitungBuku(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $baris = $this->db->table('books')
            ->select('category_id, COUNT(id) AS jumlah')
            ->whereIn('category_id', $categoryIds)
            ->where('deleted_at', null)   // buku yang sudah dihapus tidak dihitung
            ->groupBy('category_id')
            ->get()
            ->getResultArray();

        // Ubah [['category_id'=>3,'jumlah'=>4], ...] menjadi [3 => 4, ...]
        return array_map('intval', array_column($baris, 'jumlah', 'category_id'));
    }

    /**
     * Cek apakah kategori masih dipakai oleh buku.
     *
     * Buku yang sudah di-soft-delete IKUT dihitung, karena barisnya masih
     * ada di tabel `books` dan tetap menunjuk ke kategori ini (foreign key
     * fk_books_category memakai ON DELETE RESTRICT).
     *
     * @param  int $categoryId
     * @return int Jumlah buku yang memakai kategori tersebut
     */
    public function jumlahPemakai(int $categoryId): int
    {
        return $this->db->table('books')
            ->where('category_id', $categoryId)
            ->countAllResults();
    }

    /**
     * Buat slug unik dari nama kategori.
     * Bila slug hasil konversi sudah dipakai, ditambahkan akhiran angka
     * (novel, novel-2, novel-3, ...).
     *
     * @param  string   $nama      Nama kategori
     * @param  int|null $kecualiId ID yang diabaikan saat cek unik (mode edit)
     * @return string
     */
    public function generateSlug(string $nama, ?int $kecualiId = null): string
    {
        // url_title() dari helper 'url': "Self Improvement" => "self-improvement"
        $dasar = url_title($nama, '-', true);
        $slug  = $dasar;
        $angka = 2;

        // Ulangi sampai menemukan slug yang belum terpakai
        while ($this->slugDipakai($slug, $kecualiId)) {
            $slug = $dasar . '-' . $angka;
            $angka++;
        }

        return $slug;
    }

    /**
     * Cek apakah sebuah slug sudah dipakai kategori lain.
     *
     * @param  string   $slug
     * @param  int|null $kecualiId ID yang diabaikan (baris milik sendiri)
     * @return bool
     */
    private function slugDipakai(string $slug, ?int $kecualiId = null): bool
    {
        $builder = $this->db->table('categories')->where('slug', $slug);

        if ($kecualiId !== null) {
            $builder->where('id !=', $kecualiId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Angka ringkasan untuk kartu di halaman Kategori.
     *
     * @return array{total:int, terpakai:int, kosong:int, buku:int}
     */
    public function ringkasan(): array
    {
        $totalKategori = $this->db->table('categories')
            ->where('deleted_at', null)
            ->countAllResults();

        /*
         * Berapa kategori yang punya minimal satu buku aktif.
         * Dipakai COUNT(DISTINCT ...) - BUKAN countAllResults() + groupBy -
         * karena countAllResults() pada query ber-GROUP BY mengembalikan
         * jumlah baris grup pertama, bukan banyaknya grup.
         */
        $baris = $this->db->table('books')
            ->select('COUNT(DISTINCT category_id) AS jumlah', false)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $terpakai = (int) ($baris['jumlah'] ?? 0);

        $totalBuku = $this->db->table('books')
            ->where('deleted_at', null)
            ->countAllResults();

        return [
            'total'    => $totalKategori,
            'terpakai' => $terpakai,
            'kosong'   => max(0, $totalKategori - $terpakai),
            'buku'     => $totalBuku,
        ];
    }

    /**
     * Daftar kategori siap pakai untuk elemen <select>.
     *
     * @return array<int, string> [id => nama], diurutkan menurut nama
     */
    public function dropdown(): array
    {
        $baris = $this->select('id, nama')->orderBy('nama', 'ASC')->findAll();

        // array_column() mengubah [['id'=>1,'nama'=>'Novel'], ...]
        // menjadi [1 => 'Novel', ...] supaya mudah dipakai di view.
        return array_column($baris, 'nama', 'id');
    }
}
