<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
 * =====================================================================
 * PETA ROUTE APLIKASI DIGI-LIBRARY
 * =====================================================================
 * Konvensi yang dipakai tiap modul CRUD:
 *   GET  modul                -> index()   Daftar data (+ search & pagination)
 *   GET  modul/create         -> create()  Tampilkan form tambah
 *   POST modul/store          -> store()   Simpan data baru
 *   GET  modul/(:num)/edit    -> edit()    Tampilkan form ubah
 *   POST modul/(:num)/update  -> update()  Simpan perubahan
 *   POST modul/(:num)/delete  -> delete()  Hapus data
 *
 * Method POST dipakai (bukan PUT/DELETE) karena form HTML biasa hanya
 * mendukung GET & POST, sekaligus otomatis ikut proteksi filter CSRF.
 */

/*
 * ---------------------------------------------------------------------
 * 1. ROUTE PUBLIK (tanpa login)
 * ---------------------------------------------------------------------
 * Filter 'guest' mencegah user yang SUDAH login membuka halaman login
 * lagi - ia langsung dilempar ke dashboard.
 */
$routes->get('/', 'Auth::login', ['filter' => 'guest']);
$routes->get('login', 'Auth::login', ['filter' => 'guest']);
$routes->post('login', 'Auth::processLogin', ['filter' => 'guest']);
$routes->get('logout', 'Auth::logout');

/*
 * ---------------------------------------------------------------------
 * 2. ROUTE TERPROTEKSI (wajib login)
 * ---------------------------------------------------------------------
 * Semua route di dalam group besar ini dijaga AuthFilter (alias 'auth'),
 * yang didaftarkan pada app/Config/Filters.php. Dengan membungkusnya
 * dalam satu group, tidak ada halaman dasbor yang lupa diproteksi.
 */
$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {

    // ----- 2.1 Dashboard ---------------------------------------------
    $routes->get('dashboard', 'Dashboard::index');
    // Endpoint AJAX: data grafik peminjaman bulanan (dipakai Chart.js)
    $routes->get('dashboard/chart-data', 'Dashboard::chartData');

    /*
     * Endpoint AJAX "Global Search" milik kotak pencarian di topbar.
     * Berada di dalam group ber-filter 'auth' sehingga hanya bisa
     * diakses petugas yang sudah login.
     */
    $routes->get('search', 'Search::globalSearch');

    // ----- 2.2 Anggota (SELESAI) -------------------------------------
    $routes->group('members', static function (RouteCollection $routes): void {
        $routes->get('/', 'Member::index');                   // daftar + search + pagination
        $routes->get('create', 'Member::create');             // form tambah
        $routes->post('store', 'Member::store');              // simpan baru
        $routes->get('(:num)/edit', 'Member::edit/$1');       // form ubah
        $routes->post('(:num)/update', 'Member::update/$1');  // simpan perubahan
        $routes->post('(:num)/delete', 'Member::delete/$1');  // hapus (soft delete)
    });

    // ----- 2.3 Katalog Buku (menyusul) -------------------------------
    $routes->group('books', static function (RouteCollection $routes): void {
        $routes->get('/', 'Book::index');
        $routes->get('create', 'Book::create');
        $routes->post('store', 'Book::store');
        $routes->get('(:num)/edit', 'Book::edit/$1');
        $routes->post('(:num)/update', 'Book::update/$1');
        $routes->post('(:num)/delete', 'Book::delete/$1');
    });

    // ----- 2.4 Kategori (menyusul) -----------------------------------
    $routes->group('categories', static function (RouteCollection $routes): void {
        $routes->get('/', 'Category::index');
        $routes->get('create', 'Category::create');
        $routes->post('store', 'Category::store');
        $routes->get('(:num)/edit', 'Category::edit/$1');
        $routes->post('(:num)/update', 'Category::update/$1');
        $routes->post('(:num)/delete', 'Category::delete/$1');
    });

    // ----- 2.5 Peminjaman (SELESAI) ----------------------------------
    $routes->group('loans', static function (RouteCollection $routes): void {
        $routes->get('/', 'Loan::index');                     // daftar + search + pagination
        $routes->get('create', 'Loan::create');               // form transaksi baru

        /*
         * Endpoint AJAX untuk Select2 pada form peminjaman.
         * Didaftarkan SEBELUM route '(:num)' agar tidak tertukar, dan
         * tetap berada di dalam group ber-filter 'auth' sehingga hanya
         * bisa diakses petugas yang sudah login.
         */
        $routes->get('search-members', 'Loan::searchMembers'); // JSON daftar anggota
        $routes->get('search-books', 'Loan::searchBooks');     // JSON daftar buku

        $routes->post('store', 'Loan::store');                // simpan + kurangi stok
        $routes->get('(:num)', 'Loan::show/$1');              // detail transaksi
        $routes->get('(:num)/edit', 'Loan::edit/$1');         // form ubah jatuh tempo/catatan
        $routes->post('(:num)/update', 'Loan::update/$1');    // simpan perubahan
        $routes->post('(:num)/extend', 'Loan::extend/$1');    // perpanjang cepat
        $routes->post('(:num)/delete', 'Loan::delete/$1');    // batalkan + kembalikan stok
    });

    // ----- 2.6 Pengembalian (SELESAI) --------------------------------
    // Controller sengaja dinamai BookReturn (bukan Return) karena
    // `return` adalah kata kunci PHP dan tidak boleh jadi nama kelas.
    $routes->group('returns', static function (RouteCollection $routes): void {
        $routes->get('/', 'BookReturn::index');                    // antrean buku belum kembali
        $routes->get('history', 'BookReturn::history');            // riwayat pengembalian
        $routes->get('(:num)/process', 'BookReturn::form/$1');     // form konfirmasi + rincian denda
        $routes->post('(:num)/process', 'BookReturn::process/$1'); // eksekusi pengembalian
        $routes->get('(:num)/edit', 'BookReturn::edit/$1');        // koreksi data pengembalian
        $routes->post('(:num)/update', 'BookReturn::update/$1');   // simpan koreksi
        $routes->post('(:num)/cancel', 'BookReturn::cancel/$1');   // batalkan pengembalian
    });

    // ----- 2.7 Laporan (menyusul) ------------------------------------
    $routes->group('reports', static function (RouteCollection $routes): void {
        $routes->get('/', 'Report::index');
        $routes->get('print', 'Report::printView');
        $routes->get('export', 'Report::exportCsv');
    });

    // ----- 2.8 Pengaturan (menyusul) ---------------------------------
    $routes->group('settings', static function (RouteCollection $routes): void {
        $routes->get('/', 'Setting::index');
        $routes->post('profile', 'Setting::updateProfile');
        $routes->post('password', 'Setting::updatePassword');
        $routes->post('system', 'Setting::updateSystem');
    });
});
