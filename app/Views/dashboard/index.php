<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN DASHBOARD
 * ---------------------------------------------------------------------
 * Susunan sesuai mockup:
 *   Baris 1 : 4 kartu ringkasan (Total Buku, Anggota Aktif,
 *             Buku Dipinjam, Buku Terlambat)
 *   Baris 2 : grafik peminjaman bulanan + tabel peminjaman terbaru
 *   Baris 3 : grid buku terpopuler + daftar anggota baru
 *
 * Variabel dari Dashboard::index():
 * @var array $ringkasan   Angka empat kartu ringkasan
 * @var array $chart       ['labels' => [], 'peminjaman' => [], 'pengembalian' => []]
 * @var array $peminjaman  Baris transaksi terbaru (sudah di-JOIN)
 * @var array $terpopuler  Buku dengan jumlah peminjaman terbanyak
 * @var array $anggotaBaru Anggota yang paling baru mendaftar
 */
?>

<?= $this->section('content') ?>

<!-- ================== BARIS 1: KARTU RINGKASAN ==================== -->
<div class="row g-3 mb-3">

    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--blue"><i class="bi bi-book-half"></i></div>
            <div>
                <p class="dl-stat__label">Total Buku</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['total_buku'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--green"><i class="bi bi-people"></i></div>
            <div>
                <p class="dl-stat__label">Anggota Aktif</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['anggota_aktif'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--amber"><i class="bi bi-journal-arrow-up"></i></div>
            <div>
                <p class="dl-stat__label">Buku Dipinjam</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['buku_dipinjam'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--red"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <p class="dl-stat__label">Buku Terlambat</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['buku_terlambat'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

</div>

<!-- =========== BARIS 2: GRAFIK + PEMINJAMAN TERBARU =============== -->
<div class="row g-3 mb-3">

    <!-- Grafik garis (Chart.js) -->
    <div class="col-12 col-lg-7">
        <div class="dl-card h-100">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Ringkasan Peminjaman Bulanan</h2>
                <!-- Dropdown filter; JS mengambil data baru lewat AJAX -->
                <select class="dl-select" id="dlChartRange"
                        data-url="<?= site_url('dashboard/chart-data') ?>">
                    <option value="6" selected>6 Bulan Terakhir</option>
                    <option value="3">3 Bulan Terakhir</option>
                    <option value="12">12 Bulan Terakhir</option>
                </select>
            </div>
            <div class="dl-card__body">
                <div class="dl-chart-box">
                    <canvas id="dlChartPeminjaman"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel peminjaman terbaru -->
    <div class="col-12 col-lg-5">
        <div class="dl-card h-100">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Daftar Peminjaman Terbaru</h2>
                <a href="<?= site_url('loans') ?>" class="small text-primary">Lihat semua</a>
            </div>

            <div class="dl-table-wrap">
                <table class="dl-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Judul Buku</th>
                            <th>Nama Anggota</th>
                            <th>Tgl Pinjam</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($peminjaman)) : ?>
                            <tr>
                                <td colspan="5" class="dl-empty">Belum ada transaksi peminjaman.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($peminjaman as $i => $trx) : ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="text-truncate" style="max-width:150px"
                                        title="<?= esc($trx['judul'], 'attr') ?>"><?= esc($trx['judul']) ?></td>
                                    <td class="text-truncate" style="max-width:120px"><?= esc($trx['nama_anggota']) ?></td>
                                    <td class="text-nowrap"><?= esc(tanggal_indo($trx['tanggal_pinjam'])) ?></td>
                                    <td>
                                        <?php // badge_status() memetakan status ke warna pill yang sesuai ?>
                                        <span class="dl-badge <?= badge_status($trx['status']) ?>">
                                            <?= esc(ucfirst($trx['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ======== BARIS 3: BUKU TERPOPULER + ANGGOTA BARU =============== -->
<div class="row g-3">

    <!-- Grid buku terpopuler -->
    <div class="col-12 col-lg-7">
        <div class="dl-card h-100">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Buku Terpopuler Bulan Ini</h2>
                <a href="<?= site_url('books') ?>" class="small text-primary">Katalog</a>
            </div>
            <div class="dl-card__body">
                <div class="row g-2">
                    <?php if (empty($terpopuler)) : ?>
                        <div class="col-12"><p class="dl-empty">Belum ada data buku.</p></div>
                    <?php else : ?>
                        <?php foreach ($terpopuler as $buku) : ?>
                            <div class="col-12 col-md-6">
                                <div class="dl-book">
                                    <?php if (! empty($buku['sampul'])) : ?>
                                        <img class="dl-book__cover"
                                             src="<?= base_url('uploads/covers/' . $buku['sampul']) ?>"
                                             alt="Sampul <?= esc($buku['judul'], 'attr') ?>">
                                    <?php else : ?>
                                        <?php // Placeholder gradient bila buku belum punya file sampul ?>
                                        <div class="dl-book__cover"><i class="bi bi-book"></i></div>
                                    <?php endif; ?>

                                    <div class="overflow-hidden">
                                        <p class="dl-book__title"><?= esc($buku['judul']) ?></p>
                                        <p class="dl-book__meta"><?= esc($buku['penulis']) ?></p>
                                        <p class="dl-book__meta">
                                            <span class="dl-stars"><?= bintang($buku['rating']) ?></span>
                                            <span>(<?= esc($buku['rating']) ?>)</span>
                                        </p>
                                        <p class="dl-book__meta">
                                            <i class="bi bi-people-fill"></i>
                                            <?= esc(angka_ringkas($buku['jumlah_pinjam'])) ?> peminjaman
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar anggota baru -->
    <div class="col-12 col-lg-5">
        <div class="dl-card h-100">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Anggota Baru Terdaftar</h2>
                <a href="<?= site_url('members') ?>" class="small text-primary">Lihat semua</a>
            </div>
            <div class="dl-card__body">
                <?php if (empty($anggotaBaru)) : ?>
                    <p class="dl-empty">Belum ada anggota terdaftar.</p>
                <?php else : ?>
                    <?php foreach ($anggotaBaru as $anggota) : ?>
                        <div class="dl-member">
                            <div class="dl-member__avatar"><?= esc(inisial($anggota['nama'])) ?></div>
                            <div class="overflow-hidden">
                                <p class="dl-member__name"><?= esc($anggota['nama']) ?></p>
                                <p class="dl-member__meta"><?= esc($anggota['kode_anggota']) ?></p>
                            </div>
                            <span class="dl-member__date"><?= esc(tanggal_indo($anggota['tanggal_daftar'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php
/*
 * Data grafik dititipkan sebagai JSON di dalam tag <script type="application/json">.
 * dashboard.js membacanya lewat JSON.parse(). Cara ini menghindari
 * penyisipan PHP langsung ke dalam kode JavaScript.
 */
?>
<script id="dlChartData" type="application/json">
    <?= json_encode($chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>
</script>

<?= $this->endSection() ?>
