<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel
 * ---------------------------------------------------------------------
 * Mengelola seluruh query ke tabel `users`.
 * Semua query memakai Query Builder CodeIgniter yang otomatis melakukan
 * binding/escaping parameter, sehingga aman dari SQL Injection.
 */
class UserModel extends Model
{
    protected $table          = 'users';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true; // isi created_at & updated_at otomatis
    protected $useSoftDeletes = true; // delete() hanya mengisi kolom deleted_at

    /**
     * Kolom yang boleh diisi lewat insert()/update().
     * Kolom di luar daftar ini akan diabaikan (proteksi mass assignment).
     */
    protected $allowedFields = ['nama', 'username', 'email', 'password', 'role', 'foto', 'is_active'];

    /**
     * Ambil satu user berdasarkan username.
     * Dipakai saat proses login untuk mengambil hash password.
     *
     * @param  string     $username Username yang diinput user di form login
     * @return array|null           Data user, atau null bila tidak ditemukan
     */
    public function getByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Simpan user baru dengan password yang sudah di-hash.
     * Password asli TIDAK PERNAH disimpan ke database.
     *
     * @param  string $nama            Nama lengkap
     * @param  string $username        Username unik
     * @param  string $plainPassword   Password mentah dari form
     * @return bool                    true bila berhasil disimpan
     */
    public function createUser(string $nama, string $username, string $plainPassword): bool
    {
        return (bool) $this->insert([
            'nama'     => $nama,
            'username' => $username,
            'password' => password_hash($plainPassword, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * Verifikasi kredensial login.
     * Memakai password_verify() agar perbandingan hash aman terhadap
     * timing attack, bukan perbandingan string biasa (==).
     *
     * @param  string     $username
     * @param  string     $plainPassword
     * @return array|null Data user bila cocok, null bila gagal
     */
    public function verifyCredentials(string $username, string $plainPassword): ?array
    {
        $user = $this->getByUsername($username);

        // User tidak ada ATAU password tidak cocok -> anggap gagal.
        // Pesan error di controller sengaja dibuat sama untuk kedua kasus
        // agar penyerang tidak bisa menebak username mana yang valid.
        if ($user === null || ! password_verify($plainPassword, $user['password'])) {
            return null;
        }

        // Akun yang dinonaktifkan admin tidak boleh masuk
        if (isset($user['is_active']) && (int) $user['is_active'] === 0) {
            return null;
        }

        return $user;
    }
}
