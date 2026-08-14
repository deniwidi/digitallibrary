<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN ANTREAN PENGEMBALIAN
 * ---------------------------------------------------------------------
 * Daftar transaksi yang bukunya masih di tangan anggota, diurutkan dari
 * jatuh tempo paling awal supaya yang paling mendesak ada di atas.
 *
 * Variabel dari BookReturn::index():
 * @var array  $items     Baris transaksi + sisa_hari, hari_telat, denda_perkiraan
 * @var object $pager     Objek pager CodeIgniter
 * @var string $keyword   Kata kunci pencarian yang sedang aktif
 * @var string $status    Filter yang sedang aktif
 * @var int    $nomorAwal Nomor urut baris pertama di halaman ini
 * @var int    $totalData Total baris hasil pencarian
 * @var array  $ringkasan Angka ringkasan pengembalian
 * @var int    $tarif     Tarif denda per hari dari tabel settings
 */
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('returns/history') ?>" class="dl-btn dl-btn--light">
        <i class="bi bi-clock-history"></i> Riwayat Pengembalian
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ====================== KARTU RINGKASAN ======================== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--blue"><i class="bi bi-journal-arrow-down"></i></div>
            <div>
                <p class="dl-stat__label">Antrean Kembali</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['antrean'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--red"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <p class="dl-stat__label">Terlambat</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['terlambat'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--green"><i class="bi bi-check2-circle"></i></div>
            <div>
                <p class="dl-stat__label">Kembali Hari Ini</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['hari_ini'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--amber"><i class="bi bi-cash-coin"></i></div>
            <div>
                <p class="dl-stat__label">Denda Bulan Ini</p>
                <p class="dl-stat__value" style="font-size:18px"><?= esc(rupiah($ringkasan['denda_bulan'])) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ======================= TABEL ANTREAN ========================== -->
<div class="dl-card">

    <div class="dl-card__head flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <h2 class="dl-card__title">Buku Belum Kembali</h2>
            <span class="dl-badge dl-badge--blue">Tarif denda <?= esc(rupiah($tarif)) ?>/hari</span>
        </div>

        <?php /* Form pencarian: method GET agar filter tersimpan di URL
                 dan dipakai ulang oleh link paginasi. */ ?>
        <form action="<?= site_url('returns') ?>" method="get" class="d-flex flex-wrap gap-2">
            <div class="dl-search" style="min-width:230px">
                <i class="bi bi-search"></i>
                <input type="search" name="keyword" value="<?= esc($keyword) ?>"
                       placeholder="Cari kode, anggota, atau judul buku...">
            </div>

            <select name="status" class="dl-select">
                <option value="">Semua</option>
                <option value="terlambat" <?= $status === 'terlambat' ? 'selected' : '' ?>>Sudah Terlambat</option>
                <option value="belum_tempo" <?= $status === 'belum_tempo' ? 'selected' : '' ?>>Belum Jatuh Tempo</option>
            </select>

            <button type="submit" class="dl-btn dl-btn--primary dl-btn--sm">Cari</button>

            <?php if ($keyword !== '' || $status !== '') : ?>
                <a href="<?= site_url('returns') ?>" class="dl-btn dl-btn--light dl-btn--sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($keyword !== '' || $status !== '') : ?>
        <div class="px-3 pb-2">
            <span class="dl-badge dl-badge--blue">
                <?= number_format($totalData, 0, ',', '.') ?> transaksi ditemukan
                <?= $keyword !== '' ? 'untuk "' . esc($keyword) . '"' : '' ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="dl-table-wrap">
        <table class="dl-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Kode</th>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Jatuh Tempo</th>
                    <th>Perkiraan Denda</th>
                    <th style="width:150px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="7" class="dl-empty">
                            <i class="bi bi-check2-all d-block mb-2" style="font-size:26px"></i>
                            <?= ($keyword !== '' || $status !== '')
                                ? 'Tidak ada transaksi yang cocok dengan pencarian Anda.'
                                : 'Semua buku sudah kembali. Tidak ada antrean pengembalian.' ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $nomor = $nomorAwal; ?>
                    <?php foreach ($items as $item) : ?>
                        <?php $telat = (int) $item['hari_telat']; ?>
                        <tr>
                            <td><?= $nomor++ ?></td>

                            <td><span class="dl-badge dl-badge--gray"><?= esc($item['kode_transaksi']) ?></span></td>

                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="dl-member__avatar"><?= esc(inisial($item['nama_anggota'])) ?></span>
                                    <div>
                                        <div class="fw-semibold"><?= esc($item['nama_anggota']) ?></div>
                                        <div class="small text-muted"><?= esc($item['kode_anggota']) ?></div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold text-truncate" style="max-width:200px"
                                     title="<?= esc($item['judul']) ?>"><?= esc($item['judul']) ?></div>
                                <div class="small text-muted"><?= esc($item['kode_buku']) ?></div>
                            </td>

                            <td class="text-nowrap">
                                <?= esc(tanggal_indo($item['tanggal_jatuh_tempo'])) ?>
                                <div class="small <?= $telat > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?php // Sisa/telat hari membantu petugas menakar prioritas ?>
                                    <?= $telat > 0
                                        ? 'telat ' . $telat . ' hari'
                                        : ((int) $item['sisa_hari'] === 0 ? 'jatuh tempo hari ini' : 'sisa ' . (int) $item['sisa_hari'] . ' hari') ?>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <?php if ($telat > 0) : ?>
                                    <span class="fw-semibold text-danger"><?= esc(rupiah($item['denda_perkiraan'])) ?></span>
                                    <div class="small text-muted"><?= $telat ?> &times; <?= esc(rupiah($tarif)) ?></div>
                                <?php else : ?>
                                    <span class="dl-badge dl-badge--green">Tanpa denda</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= site_url('returns/' . $item['id'] . '/process') ?>"
                                       class="dl-btn dl-btn--success dl-btn--sm" title="Proses pengembalian">
                                        <i class="bi bi-box-arrow-in-down"></i> Proses
                                    </a>
                                    <a href="<?= site_url('loans/' . $item['id']) ?>"
                                       class="dl-btn dl-btn--light dl-btn--sm" title="Lihat detail peminjaman">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ======================== PAGINASI ========================== -->
    <?php if ($totalData > 0) : ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 border-top">
            <span class="small text-muted">
                Menampilkan <?= count($items) ?> dari <?= number_format($totalData, 0, ',', '.') ?> transaksi
            </span>
            <?= $pager->links('returns', 'dashboard_pager') ?>
        </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
