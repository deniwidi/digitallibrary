<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MemberModel
 * ---------------------------------------------------------------------
 * Mengelola seluruh query ke tabel `members` (CRUD + Search + Pagination).
 *
 * Semua query dibangun lewat Query Builder CodeIgniter, sehingga nilai
 * yang berasal dari input user otomatis di-bind/escape (prepared
 * statement) => aman dari SQL Injection.
 */
class MemberModel extends Model
{
    protected $table          = 'members';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;  // created_at & updated_at diisi otomatis
    protected $useSoftDeletes = true;  // delete() hanya mengisi kolom deleted_at

    /**
     * Kolom yang boleh diisi lewat insert()/update().
     * Kolom di luar daftar ini diabaikan => proteksi mass assignment.
     */
    protected $allowedFields = [
        'kode_anggota',
        'nama',
        'email',
        'telepon',
        'alamat',
        'jenis_kelamin',
        'tanggal_daftar',
        'status',
        'foto',
    ];

    /**
     * Aturan validasi dipusatkan di Model agar Controller tetap ramping
     * dan aturan yang sama otomatis berlaku untuk insert maupun update.
     *
     * Catatan placeholder {id}:
     *   is_unique[members.email,id,{id}] artinya "email harus unik,
     *   KECUALI pada baris yang id-nya sama dengan {id}". Nilai {id}
     *   diambil CodeIgniter dari data yang dikirim ke save()/update(),
     *   sehingga saat mengedit, email milik sendiri tidak dianggap kembar.
     */
    protected $validationRules = [
        // Sejak CI 4.3, setiap field yang dipakai sebagai placeholder
        // (di sini {id}) WAJIB punya aturan validasi sendiri, walaupun
        // hanya sekadar "boleh kosong / harus angka".
        'id'             => 'permit_empty|is_natural',
        'kode_anggota'   => 'required|max_length[20]|is_unique[members.kode_anggota,id,{id}]',
        'nama'           => 'required|min_length[3]|max_length[100]',
        'email'          => 'permit_empty|valid_email|max_length[100]|is_unique[members.email,id,{id}]',
        'telepon'        => 'permit_empty|numeric|min_length[8]|max_length[20]',
        'alamat'         => 'permit_empty|max_length[255]',
        'jenis_kelamin'  => 'required|in_list[L,P]',
        'tanggal_daftar' => 'required|valid_date[Y-m-d]',
        'status'         => 'required|in_list[aktif,nonaktif,diblokir]',
    ];

    protected $validationMessages = [
        'kode_anggota' => [
            'required'   => 'Kode anggota wajib diisi.',
            'is_unique'  => 'Kode anggota tersebut sudah dipakai anggota lain.',
            'max_length' => 'Kode anggota maksimal 20 karakter.',
        ],
        'nama' => [
            'required'   => 'Nama anggota wajib diisi.',
            'min_length' => 'Nama anggota minimal 3 karakter.',
            'max_length' => 'Nama anggota maksimal 100 karakter.',
        ],
        'email' => [
            'valid_email' => 'Format email tidak valid.',
            'is_unique'   => 'Email tersebut sudah terdaftar pada anggota lain.',
        ],
        'telepon' => [
            'numeric'    => 'Nomor telepon hanya boleh berisi angka.',
            'min_length' => 'Nomor telepon minimal 8 digit.',
        ],
        'jenis_kelamin' => [
            'required' => 'Jenis kelamin wajib dipilih.',
            'in_list'  => 'Jenis kelamin tidak valid.',
        ],
        'tanggal_daftar' => [
            'required'   => 'Tanggal daftar wajib diisi.',
            'valid_date' => 'Format tanggal daftar tidak valid.',
        ],
        'status' => [
            'required' => 'Status anggota wajib dipilih.',
            'in_list'  => 'Status anggota tidak valid.',
        ],
    ];

    /**
     * Ambil data anggota dengan filter pencarian + pagination.
     *
     * Alur:
     *  1. Bila ada keyword, tambahkan kondisi LIKE pada nama / kode /
     *     email / telepon. groupStart()-groupEnd() membungkus rangkaian
     *     OR di dalam tanda kurung supaya tidak "bocor" ke kondisi lain
     *     (mis. filter status di bawahnya).
     *  2. Bila ada filter status, tambahkan kondisi AND status = ...
     *  3. paginate() menjalankan query LIMIT/OFFSET sekaligus menghitung
     *     total baris untuk komponen Pager.
     *
     * @param  string|null $keyword Kata kunci nama/kode/email/telepon
     * @param  string|null $status  Filter status: aktif|nonaktif|diblokir
     * @param  int         $perPage Jumlah baris per halaman
     * @return array<int, array<string, mixed>> Baris anggota halaman aktif
     */
    public function search(?string $keyword = null, ?string $status = null, int $perPage = 10): array
    {
        if ($keyword !== null && $keyword !== '') {
            $this->groupStart()
                 ->like('nama', $keyword)
                 ->orLike('kode_anggota', $keyword)
                 ->orLike('email', $keyword)
                 ->orLike('telepon', $keyword)
                 ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $this->where('status', $status);
        }

        // 'members' = nama grup pager, dipakai lagi saat merender link
        return $this->orderBy('id', 'DESC')->paginate($perPage, 'members');
    }

    /**
     * Hasilkan kode anggota berikutnya secara otomatis (AGT-0001, AGT-0002, ...).
     *
     * withDeleted() dipakai agar kode milik anggota yang sudah dihapus
     * (soft delete) tidak dipakai ulang dan memicu error duplikat.
     *
     * @return string Kode anggota baru yang belum terpakai
     */
    public function generateKode(): string
    {
        /*
         * Query dijalankan lewat db->table() (bukan lewat Model) agar:
         *  - baris yang sudah di-soft-delete IKUT terhitung, sehingga kode
         *    bekas tidak dipakai ulang dan memicu error duplikat;
         *  - filter/urutan bawaan Model tidak ikut campur ke fungsi agregat.
         *
         * SUBSTRING(kode_anggota, 5) mengambil bagian angka setelah "AGT-",
         * lalu CAST ke UNSIGNED supaya urutannya dihitung sebagai bilangan
         * (bukan teks) - aman walau nanti nomornya melewati 4 digit.
         */
        $baris = $this->db->table($this->table)
            ->select('MAX(CAST(SUBSTRING(kode_anggota, 5) AS UNSIGNED)) AS nomor', false)
            ->get()
            ->getRowArray();

        $nomor = (int) ($baris['nomor'] ?? 0) + 1;

        return 'AGT-' . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Cek apakah anggota masih memegang buku yang belum dikembalikan.
     * Dipakai controller sebelum menghapus data, supaya riwayat pinjam
     * yang masih berjalan tidak ditinggalkan tanpa pemiliknya.
     *
     * @param  int  $memberId
     * @return bool true bila masih ada pinjaman aktif
     */
    public function punyaPinjamanAktif(int $memberId): bool
    {
        return $this->db->table('transactions')
            ->where('member_id', $memberId)
            ->where('tanggal_kembali', null)   // NULL = buku belum kembali
            ->countAllResults() > 0;
    }

    /**
     * Pencarian anggota untuk Global Search di topbar.
     *
     * Berbeda dengan cariUntukSelect2(), fungsi ini TIDAK menyaring status
     * karena pencarian global memang harus bisa menemukan anggota mana pun,
     * termasuk yang nonaktif atau diblokir.
     *
     * Keyword dipecah per kata dan setiap kata wajib cocok di salah satu
     * kolom, sehingga "widi" menemukan "Deni Widi Alfian". Nilainya di-bind
     * Query Builder => aman dari SQL Injection.
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

        $builder = $this->select('id, kode_anggota, nama, email, telepon, status');

        foreach (preg_split('/\s+/', $keyword) as $kata) {
            if ($kata === '') {
                continue;
            }

            $builder->groupStart()
                    ->like('nama', $kata)
                    ->orLike('kode_anggota', $kata)
                    ->orLike('email', $kata)
                    ->orLike('telepon', $kata)
                    ->groupEnd();
        }

        return $builder->orderBy('nama', 'ASC')->findAll($limit);
    }

    /**
     * Pencarian anggota aktif untuk dropdown AJAX (Select2).
     *
     * Keyword dipecah per kata, lalu SETIAP kata wajib cocok pada salah
     * satu kolom (nama / kode / email / telepon). Dengan begitu:
     *   "widi"        -> cocok dengan "Deni Widi Alfian" (nama tengah)
     *   "widi alfian" -> tetap cocok walau urutannya tidak berdampingan
     *   "AGT-0003"    -> cocok lewat kolom kode_anggota
     *
     * like() memakai wildcard '%kata%' di kedua sisi dan nilainya di-bind
     * oleh Query Builder => aman dari SQL Injection.
     *
     * Baris yang diambil sengaja $limit + 1: kelebihan satu baris itu
     * dipakai controller sebagai penanda "masih ada halaman berikutnya"
     * tanpa perlu query COUNT terpisah.
     *
     * @param  string $keyword Kata kunci ketikan user (boleh kosong)
     * @param  int    $limit   Jumlah baris per permintaan AJAX
     * @param  int    $offset  Lompatan baris untuk infinite scroll
     * @return array<int, array<string, mixed>>
     */
    public function cariUntukSelect2(string $keyword = '', int $limit = 15, int $offset = 0): array
    {
        $builder = $this->select('id, kode_anggota, nama, email, telepon, status')
            ->where('status', 'aktif');

        if ($keyword !== '') {
            // preg_split memecah "widi  alfian" (spasi berapa pun) jadi 2 kata
            foreach (preg_split('/\s+/', $keyword) as $kata) {
                if ($kata === '') {
                    continue;
                }

                // Tiap groupStart()...groupEnd() digabung dengan AND,
                // sedangkan di dalamnya memakai OR antar kolom.
                $builder->groupStart()
                        ->like('nama', $kata)
                        ->orLike('kode_anggota', $kata)
                        ->orLike('email', $kata)
                        ->orLike('telepon', $kata)
                        ->groupEnd();
            }
        }

        return $builder->orderBy('nama', 'ASC')
            ->findAll($limit + 1, $offset);
    }

    /**
     * Daftar anggota berstatus 'aktif' untuk dropdown biasa (non-AJAX).
     * Disimpan sebagai cadangan bila suatu saat form ingin memakai
     * <select> polos; form peminjaman sendiri sudah memakai Select2 AJAX.
     *
     * @return array<int, string> [id => "AGT-0001 - Budi Santoso"]
     */
    public function dropdownAktif(): array
    {
        $baris = $this->select('id, kode_anggota, nama')
            ->where('status', 'aktif')
            ->orderBy('nama', 'ASC')
            ->findAll();

        $hasil = [];
        foreach ($baris as $row) {
            $hasil[$row['id']] = $row['kode_anggota'] . ' - ' . $row['nama'];
        }

        return $hasil;
    }

    /**
     * Hitung jumlah anggota per status untuk kartu ringkasan di halaman
     * daftar anggota.
     *
     * @return array{total:int, aktif:int, nonaktif:int, diblokir:int}
     */
    public function ringkasanStatus(): array
    {
        $baris = $this->db->table('members')
            ->select('status, COUNT(id) AS jumlah')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $hasil = ['total' => 0, 'aktif' => 0, 'nonaktif' => 0, 'diblokir' => 0];

        foreach ($baris as $row) {
            $hasil[$row['status']] = (int) $row['jumlah'];
            $hasil['total'] += (int) $row['jumlah'];
        }

        return $hasil;
    }
}
