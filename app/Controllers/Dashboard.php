<?php

namespace App\Controllers;

use App\Models\DashboardModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Dashboard Controller
 * ---------------------------------------------------------------------
 * Halaman beranda panel admin: empat kartu ringkasan, grafik peminjaman
 * bulanan, tabel peminjaman terbaru, grid buku terpopuler, dan daftar
 * anggota baru.
 *
 * Controller ini hanya mengatur alur (mengambil data -> mengirim ke view).
 * Seluruh query berada di DashboardModel sesuai pemisahan MVC.
 *
 * Akses dijaga AuthFilter melalui group route di app/Config/Routes.php.
 */
class Dashboard extends BaseController
{
    /**
     * Jumlah bulan bawaan yang ditampilkan pada grafik.
     */
    private const RENTANG_BULAN = 6;

    protected DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    /**
     * GET /dashboard
     * Merakit seluruh data widget lalu merender view dasbor.
     *
     * @return string HTML halaman dasbor
     */
    public function index(): string
    {
        // Angka untuk empat kartu ringkasan di baris teratas
        $ringkasan = $this->dashboardModel->ringkasan();

        $data = [
            'title'        => 'Dashboard',
            'pageTitle'    => 'Dashboard',
            'pageSubtitle' => 'Ringkasan aktivitas perpustakaan hari ini, '
                              . tanggal_indo(date('Y-m-d'), false) . '.',

            'ringkasan'    => $ringkasan,
            'chart'        => $this->dashboardModel->grafikBulanan(self::RENTANG_BULAN),
            'peminjaman'   => $this->dashboardModel->peminjamanTerbaru(5),
            'terpopuler'   => $this->dashboardModel->bukuTerpopuler(4),
            'anggotaBaru'  => $this->dashboardModel->anggotaTerbaru(5),

            // Badge angka merah pada menu "Pengembalian" di sidebar
            'menuBadge'    => ['returns' => $ringkasan['buku_terlambat']],
        ];

        return view('dashboard/index', $data);
    }

    /**
     * GET /dashboard/chart-data?range=3|6|12
     * Endpoint AJAX untuk dropdown filter di atas grafik. Mengembalikan
     * data grafik dalam format JSON tanpa perlu me-reload halaman.
     *
     * @return ResponseInterface JSON {labels, peminjaman, pengembalian}
     */
    public function chartData(): ResponseInterface
    {
        // (int) memaksa nilai query string menjadi angka; nilai di luar
        // batas nanti dijepit lagi di dalam DashboardModel::grafikBulanan().
        $rentang = (int) ($this->request->getGet('range') ?? self::RENTANG_BULAN);

        return $this->response->setJSON(
            $this->dashboardModel->grafikBulanan($rentang)
        );
    }
}
