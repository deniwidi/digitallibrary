<?php

/**
 * LAYOUT UTAMA PANEL ADMIN
 * ---------------------------------------------------------------------
 * Kerangka halaman dasbor: sidebar kiri (navy) + area konten kanan.
 * Semua halaman modul cukup menulis:
 *
 *     <?= $this->extend('layouts/dashboard') ?>
 *     <?= $this->section('content') ?>  ...isi halaman...  <?= $this->endSection() ?>
 *
 * Section yang tersedia:
 *   - 'content' : isi halaman (wajib)
 *   - 'styles'  : <style>/<link> tambahan khusus halaman (opsional)
 *   - 'scripts' : <script> tambahan khusus halaman (opsional)
 *
 * Variabel yang dikenali (semuanya opsional, ada nilai bawaan):
 * @var string      $title       Judul tab browser
 * @var string      $pageTitle   Judul besar di atas konten
 * @var string      $pageSubtitle Keterangan singkat di bawah judul
 * @var array       $menuBadge   Angka badge sidebar, mis. ['returns' => 5]
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?> &middot; DIGI-LIBRARY</title>

    <!-- Bootstrap 5: sistem grid, tabel, modal, dan utilitas -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons: seluruh ikon sidebar, topbar, dan kartu -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Font Inter agar tampilan mendekati mockup -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <!-- Stylesheet khusus aplikasi (dimuat TERAKHIR supaya bisa menimpa Bootstrap) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">

    <?= $this->renderSection('styles') ?>
</head>
<body>

    <!-- ============================ SIDEBAR ============================ -->
    <?= $this->include('layouts/partials/sidebar') ?>

    <!-- Lapisan gelap saat sidebar terbuka di layar kecil -->
    <div class="dl-backdrop" id="dlBackdrop"></div>

    <!-- ========================= AREA KONTEN =========================== -->
    <div class="dl-main">

        <?= $this->include('layouts/partials/topbar') ?>

        <main class="dl-content">

            <!-- Judul halaman (hanya dirender bila controller mengirimkannya) -->
            <?php if (! empty($pageTitle)) : ?>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h1 class="dl-page-title"><?= esc($pageTitle) ?></h1>
                        <?php if (! empty($pageSubtitle)) : ?>
                            <p class="dl-page-sub"><?= esc($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <?= $this->renderSection('pageActions') ?>
                </div>
            <?php endif; ?>

            <!-- Notifikasi sukses/gagal (flash message) -->
            <?= $this->include('layouts/partials/flash') ?>

            <!-- Isi halaman dari masing-masing view modul -->
            <?= $this->renderSection('content') ?>

        </main>

        <?= $this->include('layouts/partials/footer') ?>
    </div>

    <!-- ============================ SCRIPT ============================= -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js dipakai grafik "Ringkasan Peminjaman Bulanan" -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="<?= base_url('assets/js/dashboard.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
