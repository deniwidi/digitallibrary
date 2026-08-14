<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter
 * ---------------------------------------------------------------------
 * Filter (middleware) penjaga seluruh halaman dasbor.
 * Dipasang pada group route besar di app/Config/Routes.php sehingga
 * Dashboard, Buku, Anggota, Kategori, Peminjaman, Pengembalian, Laporan,
 * dan Pengaturan hanya bisa diakses bila session 'isLoggedIn' = true.
 */
class AuthFilter implements FilterInterface
{
    /**
     * Dijalankan SEBELUM controller diproses.
     *
     * Bila user belum login:
     *  1. URL yang tadi dituju disimpan ke session 'redirect_url' supaya
     *     setelah login berhasil user dikembalikan ke halaman tersebut.
     *  2. User dialihkan ke halaman login beserta flash message.
     *
     * @param  RequestInterface $request
     * @param  array|null       $arguments Argumen tambahan dari route (tidak dipakai)
     * @return ResponseInterface|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            // current_url() = alamat lengkap yang sedang diminta user
            session()->set('redirect_url', current_url());

            return redirect()->to(site_url('login'))
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
        }
    }

    /**
     * Dijalankan SETELAH controller. Tidak dipakai di aplikasi ini.
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // sengaja dikosongkan
    }
}
