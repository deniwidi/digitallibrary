<?php

/**
 * PARTIAL: SIDEBAR NAVIGASI
 * ---------------------------------------------------------------------
 * Menu kiri berlatar navy sesuai mockup. Item yang sedang dibuka ditandai
 * kelas "active" — ditentukan helper menu_aktif() yang membandingkan
 * segmen pertama URL berjalan dengan segmen milik menu.
 *
 * @var array $menuBadge Opsional, angka badge per menu. Contoh dari
 *                       controller: ['returns' => 9] untuk jumlah buku
 *                       yang telat dikembalikan.
 */

$menuBadge = $menuBadge ?? [];

// Definisi menu dikumpulkan dalam array supaya penambahan menu baru
// cukup satu baris dan markup-nya tidak berulang.
$menuUtama = [
    ['segmen' => 'dashboard',  'label' => 'Dashboard',    'ikon' => 'bi-grid-1x2-fill', 'url' => 'dashboard'],
    ['segmen' => 'books',      'label' => 'Katalog Buku', 'ikon' => 'bi-book-fill',     'url' => 'books'],
    ['segmen' => 'members',    'label' => 'Anggota',      'ikon' => 'bi-people-fill',   'url' => 'members'],
    ['segmen' => 'categories', 'label' => 'Kategori',     'ikon' => 'bi-tags-fill',     'url' => 'categories'],
];

$menuTransaksi = [
    ['segmen' => 'loans',   'label' => 'Peminjaman',   'ikon' => 'bi-arrow-right-circle-fill', 'url' => 'loans'],
    ['segmen' => 'returns', 'label' => 'Pengembalian', 'ikon' => 'bi-arrow-left-circle-fill',  'url' => 'returns'],
    ['segmen' => 'reports', 'label' => 'Laporan',      'ikon' => 'bi-file-earmark-bar-graph-fill', 'url' => 'reports'],
];

$menuSistem = [
    ['segmen' => 'settings', 'label' => 'Pengaturan', 'ikon' => 'bi-gear-fill', 'url' => 'settings'],
];
?>
<aside class="dl-sidebar" id="dlSidebar">

    <!-- Logo / nama aplikasi -->
    <a href="<?= site_url('dashboard') ?>" class="dl-brand">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span>DIGI-LIBRARY</span>
    </a>

    <!-- Identitas admin yang sedang login (diambil dari session) -->
    <div class="dl-user">
        <div class="dl-user__avatar"><?= esc(inisial(session()->get('nama'))) ?></div>
        <div>
            <div class="dl-user__name"><?= esc(session()->get('nama') ?? 'Admin') ?></div>
            <div class="dl-user__role"><?= esc(ucfirst(session()->get('role') ?? 'Pustakawan')) ?></div>
        </div>
    </div>

    <nav class="dl-nav">

        <div class="dl-nav__heading">Menu Utama</div>
        <?php foreach ($menuUtama as $item) : ?>
            <a href="<?= site_url($item['url']) ?>" class="dl-nav__link <?= menu_aktif($item['segmen']) ?>">
                <i class="bi <?= $item['ikon'] ?>"></i>
                <span><?= esc($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <div class="dl-nav__heading">Transaksi</div>
        <?php foreach ($menuTransaksi as $item) : ?>
            <a href="<?= site_url($item['url']) ?>" class="dl-nav__link <?= menu_aktif($item['segmen']) ?>">
                <i class="bi <?= $item['ikon'] ?>"></i>
                <span><?= esc($item['label']) ?></span>
                <?php // Badge hanya muncul bila controller mengirim angkanya ?>
                <?php if (! empty($menuBadge[$item['segmen']])) : ?>
                    <span class="dl-nav__badge"><?= esc($menuBadge[$item['segmen']]) ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <div class="dl-nav__heading">Sistem</div>
        <?php foreach ($menuSistem as $item) : ?>
            <a href="<?= site_url($item['url']) ?>" class="dl-nav__link <?= menu_aktif($item['segmen']) ?>">
                <i class="bi <?= $item['ikon'] ?>"></i>
                <span><?= esc($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <a href="<?= site_url('logout') ?>" class="dl-nav__link">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    </nav>
</aside>
