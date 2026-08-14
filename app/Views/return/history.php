<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN RIWAYAT PENGEMBALIAN
 * ---------------------------------------------------------------------
 * Daftar transaksi yang bukunya sudah kembali (atau dinyatakan hilang),
 * lengkap dengan denda dan tombol koreksi/pembatalan.
 *
 * Variabel dari BookReturn::history():
 * @var array  $items     Baris transaksi yang sudah dikembalikan
 * @var object $pager     Objek pager CodeIgniter
 * @var string $keyword   Kata kunci pencarian yang sedang aktif
 * @var string $status    Filter yang sedang aktif
 * @var int    $nomorAwal Nomor urut baris pertama di halaman ini
 * @var int    $totalData Total baris hasil pencarian
 * @var array  $ringkasan Angka ringkasan pengembalian
 */
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('returns') ?>" class="dl-btn dl-btn--primary">
        <i class="bi bi-box-arrow-in-down"></i> Antrean Pengembalian
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ====================== KARTU RINGKASAN ======================== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--blue"><i class="bi bi-journal-arrow-down"></i></div>
            <div>
                <p class="dl-stat__label">Masih Dipinjam</p>
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

<!-- ======================= TABEL RIWAYAT ========================== -->
<div class="dl-card">

    <div class="dl-card__head flex-wrap">
        <h2 class="dl-card__title">Riwayat Pengembalian</h2>

        <form action="<?= site_url('returns/history') ?>" method="get" class="d-flex flex-wrap gap-2">
            <div class="dl-search" style="min-width:230px">
                <i class="bi bi-search"></i>
                <input type="search" name="keyword" value="<?= esc($keyword) ?>"
                       placeholder="Cari kode, anggota, atau judul buku...">
            </div>

            <select name="status" class="dl-select">
                <option value="">Semua Status</option>
                <option value="tepat_waktu" <?= $status === 'tepat_waktu' ? 'selected' : '' ?>>Tepat Waktu</option>
                <option value="terlambat" <?= $status === 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                <option value="hilang" <?= $status === 'hilang' ? 'selected' : '' ?>>Hilang</option>
            </select>

            <button type="submit" class="dl-btn dl-btn--primary dl-btn--sm">Cari</button>

            <?php if ($keyword !== '' || $status !== '') : ?>
                <a href="<?= site_url('returns/history') ?>" class="dl-btn dl-btn--light dl-btn--sm">Reset</a>
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
                    <th>Tgl Kembali</th>
                    <th>Denda</th>
                    <th>Status</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="8" class="dl-empty">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:26px"></i>
                            <?= ($keyword !== '' || $status !== '')
                                ? 'Tidak ada transaksi yang cocok dengan pencarian Anda.'
                                : 'Belum ada riwayat pengembalian.' ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $nomor = $nomorAwal; ?>
                    <?php foreach ($items as $item) : ?>
                        <?php
                        // Selisih hari antara tanggal kembali dan jatuh tempo,
                        // dipakai sebagai keterangan di bawah tanggal.
                        $telat = (int) floor(
                            (strtotime($item['tanggal_kembali']) - strtotime($item['tanggal_jatuh_tempo'])) / 86400
                        );
                        ?>
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
                                <?= esc(tanggal_indo($item['tanggal_kembali'])) ?>
                                <div class="small <?= $telat > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= $telat > 0 ? 'telat ' . $telat . ' hari' : 'tepat waktu' ?>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <?php if ((int) $item['denda'] > 0) : ?>
                                    <span class="fw-semibold text-danger"><?= esc(rupiah($item['denda'])) ?></span>
                                <?php else : ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="dl-badge <?= badge_status($item['status']) ?>">
                                    <?= $item['status'] === 'dikembalikan' ? 'Tepat Waktu' : esc(ucfirst($item['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= site_url('returns/' . $item['id'] . '/edit') ?>"
                                       class="dl-btn dl-btn--light dl-btn--sm" title="Koreksi data pengembalian">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <?php /* Batalkan pengembalian: mengembalikan transaksi ke
                                             status dipinjam dan menyesuaikan stok lagi. */ ?>
                                    <form action="<?= site_url('returns/' . $item['id'] . '/cancel') ?>"
                                          method="post" class="d-inline"
                                          data-confirm="Batalkan pengembalian <?= esc($item['kode_transaksi']) ?>? Transaksi akan kembali berstatus dipinjam.">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dl-btn dl-btn--danger dl-btn--sm"
                                                title="Batalkan pengembalian">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
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
