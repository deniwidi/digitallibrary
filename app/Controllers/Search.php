<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\MemberModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Search Controller
 * ---------------------------------------------------------------------
 * Melayani "Global Search" pada kotak pencarian di topbar dasbor.
 *
 * Satu permintaan AJAX mencari ke dua sumber sekaligus (buku & anggota),
 * lalu hasilnya dikelompokkan dan dikembalikan sebagai JSON. Alamat tujuan
 * tiap hasil (URL detail) juga dibentuk di sini memakai site_url(),
 * sehingga JavaScript di sisi klien tidak perlu tahu struktur route.
 *
 * Akses dijaga AuthFilter lewat group route di app/Config/Routes.php.
 */
class Search extends BaseController
{
    /**
     * Jumlah hasil maksimal per kelompok agar dropdown tidak kepanjangan.
     */
    private const LIMIT_PER_GRUP = 5;

    /**
     * Panjang keyword minimal sebelum query dijalankan. Mencegah satu huruf
     * menarik ribuan baris dari database.
     */
    private const MIN_KEYWORD = 2;

    protected BookModel $bookModel;
    protected MemberModel $memberModel;

    public function __construct()
    {
        $this->bookModel   = new BookModel();
        $this->memberModel = new MemberModel();
    }

    /**
     * GET /search?q=...
     * Mencari buku dan anggota sekaligus, lalu mengembalikan JSON.
     *
     * Bentuk balasan:
     * {
     *   "keyword": "laskar",
     *   "total": 1,
     *   "groups": [
     *     { "label": "Buku", "icon": "bi-book", "url": "...", "items": [
     *        { "title": "...", "subtitle": "...", "meta": "...",
     *          "badge": "...", "badgeClass": "...", "url": "..." } ] }
     *   ]
     * }
     *
     * @return ResponseInterface JSON
     */
    public function globalSearch(): ResponseInterface
    {
        // Endpoint ini hanya untuk dipanggil dari topbar via AJAX.
        // Diakses langsung lewat address bar -> anggap tidak ada.
        if (! $this->request->isAJAX()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $keyword = trim((string) $this->request->getGet('q'));

        // Keyword terlalu pendek: balas kosong tanpa menyentuh database
        if (mb_strlen($keyword) < self::MIN_KEYWORD) {
            return $this->response->setJSON([
                'keyword' => $keyword,
                'total'   => 0,
                'groups'  => [],
            ]);
        }

        $groups = [];

        // ---------- Kelompok 1: Buku ----------
        $books = $this->bookModel->cariGlobal($keyword, self::LIMIT_PER_GRUP);

        if ($books !== []) {
            $groups[] = [
                'label' => 'Buku',
                'icon'  => 'bi-book',
                // Link "lihat semua" mengarah ke katalog dengan keyword yang sama
                'url'   => site_url('books') . '?keyword=' . rawurlencode($keyword),
                'items' => array_map([$this, 'bentukItemBuku'], $books),
            ];
        }

        // ---------- Kelompok 2: Anggota ----------
        $members = $this->memberModel->cariGlobal($keyword, self::LIMIT_PER_GRUP);

        if ($members !== []) {
            $groups[] = [
                'label' => 'Anggota',
                'icon'  => 'bi-people',
                'url'   => site_url('members') . '?keyword=' . rawurlencode($keyword),
                'items' => array_map([$this, 'bentukItemAnggota'], $members),
            ];
        }

        // Total dipakai JavaScript untuk menampilkan pesan "tidak ditemukan"
        $total = array_sum(array_map(static fn (array $g): int => count($g['items']), $groups));

        return $this->response->setJSON([
            'keyword' => $keyword,
            'total'   => $total,
            'groups'  => $groups,
        ]);
    }

    /**
     * Ubah satu baris buku menjadi item hasil pencarian.
     *
     * Catatan: aplikasi ini belum punya halaman detail buku tersendiri,
     * jadi tujuan klik diarahkan ke form ubah - di situlah seluruh data
     * buku bisa dilihat sekaligus disunting.
     *
     * @param  array<string, mixed> $book
     * @return array<string, mixed>
     */
    private function bentukItemBuku(array $book): array
    {
        $tersedia = (int) $book['stok_tersedia'];

        return [
            'title'    => $book['judul'],
            'subtitle' => $book['penulis'],
            'meta'     => $book['kode_buku']
                . (empty($book['nama_kategori']) ? '' : ' - ' . $book['nama_kategori']),
            'badge'      => $tersedia . '/' . (int) $book['stok'] . ' tersedia',
            'badgeClass' => $tersedia === 0 ? 'dl-badge--red'
                : ($tersedia <= 2 ? 'dl-badge--amber' : 'dl-badge--green'),
            'url'      => site_url('books/' . $book['id'] . '/edit'),
        ];
    }

    /**
     * Ubah satu baris anggota menjadi item hasil pencarian.
     *
     * @param  array<string, mixed> $member
     * @return array<string, mixed>
     */
    private function bentukItemAnggota(array $member): array
    {
        return [
            'title'    => $member['nama'],
            'subtitle' => $member['email'] ?? $member['telepon'] ?? '-',
            'meta'     => $member['kode_anggota'],
            'badge'    => ucfirst($member['status']),
            // badge_status() dari app_helper memetakan status ke warna pill
            'badgeClass' => badge_status($member['status']),
            'url'      => site_url('members/' . $member['id'] . '/edit'),
        ];
    }
}
