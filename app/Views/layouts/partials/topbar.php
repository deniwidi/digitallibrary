<?php

/**
 * PARTIAL: TOPBAR
 * ---------------------------------------------------------------------
 * Bar atas: tombol hamburger (mobile), Global Search, ikon pesan,
 * ikon notifikasi, dan dropdown profil admin.
 *
 * GLOBAL SEARCH
 * Kotak pencarian punya dua jalur yang saling melengkapi:
 *   1. Instan  - tiap ketikan (dengan jeda/debounce) memanggil endpoint
 *                /search lewat AJAX, hasilnya muncul sebagai dropdown
 *                melayang di bawah input. Logikanya ada di dashboard.js.
 *   2. Cadangan- menekan Enter tetap mengirim form ke halaman Katalog
 *                Buku (?keyword=...), sehingga fitur pencarian tetap
 *                berfungsi walau JavaScript mati.
 */
?>
<header class="dl-topbar">

    <!-- Tombol buka/tutup sidebar, hanya tampil di layar < 992px -->
    <button type="button" class="dl-icon-btn dl-toggle" id="dlSidebarToggle" aria-label="Buka menu">
        <i class="bi bi-list"></i>
    </button>

    <!-- ===================== GLOBAL SEARCH ====================== -->
    <div class="dl-search-wrap" id="dlGlobalSearch">

        <form class="dl-search" action="<?= site_url('books') ?>" method="get" role="search" autocomplete="off">
            <i class="bi bi-search"></i>
            <input type="search"
                   id="dlSearchInput"
                   name="keyword"
                   value="<?= esc($keyword ?? '') ?>"
                   placeholder="Cari Buku, Anggota..."
                   aria-label="Pencarian global"
                   aria-controls="search-results"
                   aria-expanded="false"
                   aria-autocomplete="list"
                   role="combobox"
                   <?php // Alamat endpoint dititipkan lewat data-attribute agar
                         // dashboard.js tidak perlu menebak-nebak base URL. ?>
                   data-url="<?= site_url('search') ?>">

            <?php // Indikator kecil saat permintaan AJAX sedang berjalan ?>
            <span class="dl-search-spinner" id="dlSearchSpinner" hidden>
                <i class="bi bi-arrow-repeat"></i>
            </span>
        </form>

        <?php // Wadah hasil pencarian - diisi oleh dashboard.js ?>
        <div class="dl-search-results" id="search-results" role="listbox" aria-label="Hasil pencarian"></div>
    </div>

    <div class="dl-topbar__actions">

        <?php /* Ikon pesan: diarahkan ke daftar peminjaman berjalan, karena
                 aplikasi ini belum punya modul pesan tersendiri. */ ?>
        <a href="<?= site_url('loans') ?>" class="dl-icon-btn dl-hide-xs"
           aria-label="Peminjaman berjalan" title="Peminjaman berjalan">
            <i class="bi bi-envelope"></i>
        </a>

        <?php /* Ikon notifikasi: membuka daftar Pengembalian (buku telat).
                 Titik merah hanya muncul bila controller mengirim
                 $menuBadge['returns'] > 0. */ ?>
        <a href="<?= site_url('returns') ?>" class="dl-icon-btn"
           aria-label="Notifikasi keterlambatan"
           title="<?= ! empty($menuBadge['returns'])
               ? esc($menuBadge['returns'], 'attr') . ' buku terlambat dikembalikan'
               : 'Tidak ada keterlambatan' ?>">
            <i class="bi bi-bell"></i>
            <?php if (! empty($menuBadge['returns'])) : ?>
                <span class="dl-icon-btn__dot"></span>
            <?php endif; ?>
        </a>

        <!-- Profil admin -->
        <div class="dropdown">
            <button class="btn btn-link p-0 d-flex align-items-center gap-2 text-decoration-none border-0"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="dl-avatar"><?= esc(inisial(session()->get('nama'))) ?></span>
                <i class="bi bi-chevron-down text-secondary small dl-hide-xs"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li class="dropdown-header">
                    <?= esc(session()->get('nama') ?? 'Admin') ?><br>
                    <small class="text-muted"><?= esc(session()->get('username') ?? '') ?></small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= site_url('settings') ?>">
                    <i class="bi bi-person-gear me-2"></i>Pengaturan Profil</a></li>
                <li><a class="dropdown-item text-danger" href="<?= site_url('logout') ?>">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>

    </div>
</header>
