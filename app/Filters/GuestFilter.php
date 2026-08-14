<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * GuestFilter
 * ---------------------------------------------------------------------
 * Kebalikan dari AuthFilter: hanya mengizinkan TAMU (belum login).
 * Dipasang pada route /login supaya user yang sudah punya session aktif
 * tidak melihat form login lagi, melainkan langsung masuk dashboard.
 */
class GuestFilter implements FilterInterface
{
    /**
     * Dijalankan sebelum controller.
     *
     * @param  RequestInterface $request
     * @param  array|null       $arguments Argumen tambahan dari route (tidak dipakai)
     * @return ResponseInterface|void      Redirect bila user sudah login
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to(site_url('dashboard'));
        }
    }

    /**
     * Dijalankan setelah controller. Tidak dipakai.
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // sengaja dikosongkan
    }
}
