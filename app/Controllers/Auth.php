<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Auth Controller
 * ---------------------------------------------------------------------
 * Menangani proses Login dan Logout memakai CodeIgniter Session.
 */
class Auth extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * GET /login
     * Menampilkan form login. Bila user sudah login, langsung lempar
     * ke dashboard agar tidak melihat form login lagi.
     */
    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/login', ['title' => 'Login - Perpustakaan Digital']);
    }

    /**
     * POST /login
     * Memproses kredensial: validasi input -> cek ke database ->
     * simpan data user ke session bila cocok.
     */
    public function processLogin()
    {
        // 1) Validasi input dasar sebelum menyentuh database
        $rules = [
            'username' => [
                'rules'  => 'required|min_length[3]',
                'errors' => [
                    'required'   => 'Username wajib diisi.',
                    'min_length' => 'Username minimal 3 karakter.',
                ],
            ],
            'password' => [
                'rules'  => 'required',
                'errors' => ['required' => 'Password wajib diisi.'],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // 2) Cek kredensial (password_verify dilakukan di dalam Model)
        $user = $this->userModel->verifyCredentials($username, $password);

        if ($user === null) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Username atau password salah.');
        }

        // 3) Simpan identitas user ke session.
        //    Password TIDAK ikut disimpan ke session.
        session()->set([
            'user_id'    => $user['id'],
            'nama'       => $user['nama'],
            'username'   => $user['username'],
            'role'       => $user['role'] ?? 'petugas',
            'isLoggedIn' => true,
        ]);

        // 4) Regenerate session ID untuk mencegah session fixation
        session()->regenerate();

        // 5) Kembalikan user ke halaman yang tadi ia tuju sebelum diminta
        //    login (disimpan AuthFilter). Bila tidak ada, ke dashboard.
        $tujuan = session()->get('redirect_url') ?? site_url('dashboard');
        session()->remove('redirect_url');

        return redirect()->to($tujuan)
            ->with('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
    }

    /**
     * GET /logout
     * Menghapus seluruh data session lalu kembali ke halaman login.
     */
    public function logout()
    {
        session()->destroy();

        return redirect()->to(site_url('login'))
            ->with('success', 'Anda telah berhasil logout.');
    }
}
