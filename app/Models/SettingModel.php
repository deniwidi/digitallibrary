<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SettingModel
 * ---------------------------------------------------------------------
 * Membaca/menulis tabel `settings` yang berbentuk key-value:
 *   denda_per_hari  -> tarif denda keterlambatan (Rupiah/hari)
 *   max_hari_pinjam -> lama pinjam bawaan (hari)
 *   max_pinjam_buku -> batas buku yang boleh dipegang satu anggota
 *   app_name        -> nama aplikasi
 *
 * Dipakai modul Peminjaman & Pengembalian agar aturannya bisa diubah
 * lewat menu Pengaturan tanpa menyentuh kode.
 */
class SettingModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // tabel ini hanya punya updated_at

    protected $allowedFields = ['key_name', 'value', 'keterangan', 'updated_at'];

    /**
     * Cache sederhana agar satu request tidak berkali-kali query tabel
     * yang isinya cuma beberapa baris.
     *
     * @var array<string, string>|null
     */
    private ?array $cache = null;

    /**
     * Ambil seluruh pengaturan sebagai pasangan [key => value].
     *
     * @return array<string, string>
     */
    public function semua(): array
    {
        if ($this->cache === null) {
            $baris       = $this->select('key_name, value')->findAll();
            $this->cache = array_column($baris, 'value', 'key_name');
        }

        return $this->cache;
    }

    /**
     * Ambil satu nilai pengaturan.
     *
     * @param  string $key     Nama kunci, mis. 'denda_per_hari'
     * @param  string $bawaan  Nilai cadangan bila kunci belum ada di tabel
     * @return string
     */
    public function ambil(string $key, string $bawaan = ''): string
    {
        return $this->semua()[$key] ?? $bawaan;
    }

    /**
     * Versi angka dari ambil(), untuk pengaturan yang berupa bilangan.
     *
     * @param  string $key
     * @param  int    $bawaan
     * @return int
     */
    public function ambilAngka(string $key, int $bawaan = 0): int
    {
        $nilai = $this->ambil($key, (string) $bawaan);

        return is_numeric($nilai) ? (int) $nilai : $bawaan;
    }
}
