<?php

namespace App\Controllers;

/**
 * Setting Controller (PLACEHOLDER SEMENTARA)
 * ---------------------------------------------------------------------
 * File ini hanya berisi index() yang menampilkan halaman "sedang
 * dibangun", supaya menu "Pengaturan" di sidebar tidak melempar error 404.
 *
 * Akan diganti dengan implementasi CRUD lengkap pada tahap berikutnya.
 */
class Setting extends BaseController
{
    /**
     * Menampilkan halaman placeholder modul Pengaturan.
     *
     * @return string
     */
    public function index(): string
    {
        return view('shared/coming_soon', [
            'title'      => 'Pengaturan',
            'pageTitle'  => 'Pengaturan',
            'modul'      => 'Pengaturan',
            'keterangan' => 'Pengaturan profil admin serta preferensi sistem seperti tarif denda dan lama pinjam.',
        ]);
    }
}
