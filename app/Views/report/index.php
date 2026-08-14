<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN LAPORAN TRANSAKSI
 * ---------------------------------------------------------------------
 * Susunan: form filter -> kartu rekap -> tabel transaksi (paginasi) ->
 * dua tabel peringkat (buku terpopuler & anggota teraktif).
 *
 * Variabel dari Report::index():
 * @var array       $items       Baris transaksi pada halaman aktif
 * @var object      $pager       Objek pager CodeIgniter
 * @var array       $filter      Nilai filter yang sedang aktif
 * @var int         $nomorAwal   Nomor urut baris pertama
 * @var int         $totalData   Total baris hasil filter
 * @var array       $rekap       Angka rekap
 * @var array       $topBuku     5 buku paling sering dipinjam
 * @var array       $topAnggota  5 anggota paling aktif
 * @var string      $queryFilter Filter dalam bentuk query string
 * @var string|null $peringatan  Pesan bila rentang tanggal ditukar
 */
?>

<?= $this->section('pageActions') ?>
    <div class="d-flex gap-2">
        <?php // Kedua tombol membawa filter yang sedang aktif ?>
        <a href="<?= site_url('reports/print') ?>?<?= esc($queryFilter) ?>"
           class="dl-btn dl-btn--light" target="_blank" rel="noopener">
            <i class="bi bi-printer"></i> Cetak
        </a>
        <a href="<?= site_url('reports/export') ?>?<?= esc($queryFilter) ?>" class="dl-btn dl-btn--primary">
            <i class="bi bi-file-earmark-spreadsheet"></i> Ekspor CSV
        </a>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php if ($peringatan !== null) : ?>
    <div class="dl-alert dl-alert--danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div><?= esc($peringatan) ?></div>
    </div>
<?php endif; ?>

<!-- ========================= FORM FILTER ========================== -->
<div class="dl-card mb-3">
    <div class="dl-card__head">
        <h2 class="dl-card__title">Filter Laporan</h2>
        <span class="dl-badge dl-badge--blue">
            <?= esc(tanggal_indo($filter['dari'])) ?> &ndash; <?= esc(tanggal_indo($filter['sampai'])) ?>
        </span>
    </div>

    <div class="dl-card__body">
        <?php /* Method GET agar filter tersimpan di URL: bisa di-bookmark,
                 dibagikan, dan dipakai ulang oleh link paginasi serta
                 tombol Cetak/Ekspor. */ ?>
        <form action="<?= site_url('reports') ?>" method="get" class="row g-2 align-items-end">

            <div class="col-6 col-md-3 col-xl-2">
                <label for="dari" class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" id="dari" name="dari" class="form-control"
                       value="<?= esc($filter['dari']) ?>">
            </div>

            <div class="col-6 col-md-3 col-xl-2">
                <label for="sampai" class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" id="sampai" name="sampai" class="form-control"
                       value="<?= esc($filter['sampai']) ?>">
            </div>

            <div class="col-6 col-md-3 col-xl-2">
                <label for="jenis" class="form-label small fw-semibold">Jenis Laporan</label>
                <select id="jenis" name="jenis" class="form-select">
                    <option value="peminjaman" <?= $filter['jenis'] === 'peminjaman' ? 'selected' : '' ?>>
                        Peminjaman
                    </option>
                    <option value="pengembalian" <?= $filter['jenis'] === 'pengembalian' ? 'selected' : '' ?>>
                        Pengembalian
                    </option>
                </select>
            </div>

            <div class="col-6 col-md-3 col-xl-2">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <?php
                    $opsiStatus = [
                        'dipinjam'     => 'Belum Kembali',
                        'dikembalikan' => 'Tepat Waktu',
                        'terlambat'    => 'Terlambat',
                        'hilang'       => 'Hilang',
                    ];
                    ?>
                    <?php foreach ($opsiStatus as $key => $label) : ?>
                        <option value="<?= $key ?>" <?= $filter['status'] === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label for="keyword" class="form-label small fw-semibold">Pencarian</label>
                <input type="search" id="keyword" name="keyword" class="form-control"
                       value="<?= esc($filter['keyword']) ?>"
                       placeholder="Kode, anggota, atau judul buku...">
            </div>

            <div class="col-12 col-xl-1 d-flex gap-2">
                <button type="submit" class="dl-btn dl-btn--primary w-100 justify-content-center">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
            </div>

            <div class="col-12">
                <div class="form-text">
                    <?= $filter['jenis'] === 'pengembalian'
                        ? 'Rentang tanggal dihitung dari <strong>tanggal kembali</strong>; transaksi yang belum kembali tidak ikut.'
                        : 'Rentang tanggal dihitung dari <strong>tanggal pinjam</strong>.' ?>
                    <a href="<?= site_url('reports') ?>" class="ms-2">Reset filter</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================== KARTU REKAP ========================= -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--blue"><i class="bi bi-receipt"></i></div>
            <div>
                <p class="dl-stat__label">Total Transaksi</p>
                <p class="dl-stat__value"><?= number_format($rekap['total'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--amber"><i class="bi bi-journal-arrow-up"></i></div>
            <div>
                <p class="dl-stat__label">Belum Kembali</p>
                <p class="dl-stat__value"><?= number_format($rekap['dipinjam'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--red"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <p class="dl-stat__label">Terlambat</p>
                <p class="dl-stat__value"><?= number_format($rekap['terlambat'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--green"><i class="bi bi-cash-coin"></i></div>
            <div>
                <p class="dl-stat__label">Total Denda</p>
                <p class="dl-stat__value" style="font-size:18px"><?= esc(rupiah($rekap['denda'])) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ======================= TABEL TRANSAKSI ======================== -->
<div class="dl-card mb-3">

    <div class="dl-card__head flex-wrap">
        <h2 class="dl-card__title">
            Rincian <?= $filter['jenis'] === 'pengembalian' ? 'Pengembalian' : 'Peminjaman' ?>
        </h2>
        <span class="small text-muted">
            Tepat waktu <?= number_format($rekap['dikembalikan'], 0, ',', '.') ?> &middot;
            Hilang <?= number_format($rekap['hilang'], 0, ',', '.') ?>
        </span>
    </div>

    <div class="dl-table-wrap">
        <table class="dl-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Kode</th>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Kembali</th>
                    <th>Status</th>
                    <th>Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="9" class="dl-empty">
                            <i class="bi bi-clipboard-x d-block mb-2" style="font-size:26px"></i>
                            Tidak ada transaksi pada rentang dan filter tersebut.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $nomor = $nomorAwal; ?>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td><?= $nomor++ ?></td>
                            <td>
                                <?php // Kode transaksi ditautkan ke detail peminjamannya ?>
                                <a href="<?= site_url('loans/' . $item['id']) ?>" class="dl-badge dl-badge--gray">
                                    <?= esc($item['kode_transaksi']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= esc($item['nama_anggota']) ?></div>
                                <div class="small text-muted"><?= esc($item['kode_anggota']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width:190px"
                                     title="<?= esc($item['judul']) ?>"><?= esc($item['judul']) ?></div>
                                <div class="small text-muted"><?= esc($item['kode_buku']) ?></div>
                            </td>
                            <td class="text-nowrap"><?= esc(tanggal_indo($item['tanggal_pinjam'])) ?></td>
                            <td class="text-nowrap"><?= esc(tanggal_indo($item['tanggal_jatuh_tempo'])) ?></td>
                            <td class="text-nowrap"><?= esc(tanggal_indo($item['tanggal_kembali'])) ?></td>
                            <td>
                                <span class="dl-badge <?= badge_status($item['status']) ?>">
                                    <?= $item['status'] === 'dikembalikan' ? 'Tepat Waktu' : esc(ucfirst($item['status'])) ?>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <?= (int) $item['denda'] > 0
                                    ? '<span class="fw-semibold text-danger">' . esc(rupiah($item['denda'])) . '</span>'
                                    : '<span class="text-muted">-</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalData > 0) : ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 border-top">
            <span class="small text-muted">
                Menampilkan <?= count($items) ?> dari <?= number_format($totalData, 0, ',', '.') ?> transaksi
            </span>
            <?= $pager->links('reports', 'dashboard_pager') ?>
        </div>
    <?php endif; ?>

</div>

<!-- ========================== PERINGKAT =========================== -->
<div class="row g-3">

    <div class="col-12 col-lg-6">
        <div class="dl-card h-100">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Buku Paling Sering Dipinjam</h2>
            </div>
            <div class="dl-table-wrap">
                <table class="dl-table">
                    <thead>
                        <tr><th style="width:50px">#</th><th>Judul</th><th>Jumlah</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topBuku)) : ?>
                            <tr><td colspan="3" class="dl-empty">Belum ada data pada rentang ini.</td></tr>
                        <?php else : ?>
                            <?php foreach ($topBuku as $i => $buku) : ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($buku['judul']) ?></div>
                                        <div class="small text-muted"><?= esc($buku['kode_buku']) ?></div>
                                    </td>
                                    <td><span class="dl-badge dl-badge--blue"><?= (int) $buku['jumlah'] ?>x</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="dl-card h-100">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Anggota Paling Aktif</h2>
            </div>
            <div class="dl-table-wrap">
                <table class="dl-table">
                    <thead>
                        <tr><th style="width:50px">#</th><th>Nama</th><th>Pinjam</th><th>Denda</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topAnggota)) : ?>
                            <tr><td colspan="4" class="dl-empty">Belum ada data pada rentang ini.</td></tr>
                        <?php else : ?>
                            <?php foreach ($topAnggota as $i => $anggota) : ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="dl-member__avatar"><?= esc(inisial($anggota['nama'])) ?></span>
                                            <div>
                                                <div class="fw-semibold"><?= esc($anggota['nama']) ?></div>
                                                <div class="small text-muted"><?= esc($anggota['kode_anggota']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="dl-badge dl-badge--blue"><?= (int) $anggota['jumlah'] ?>x</span></td>
                                    <td class="text-nowrap small">
                                        <?= (int) $anggota['denda'] > 0
                                            ? '<span class="text-danger">' . esc(rupiah($anggota['denda'])) . '</span>'
                                            : '<span class="text-muted">-</span>' ?>
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

<?= $this->endSection() ?>
