<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN DETAIL PEMINJAMAN
 * ---------------------------------------------------------------------
 * Menampilkan seluruh informasi satu transaksi: identitas anggota, data
 * buku, linimasa tanggal, status terkini, dan perkiraan denda bila telat.
 *
 * @var array  $loan         Data transaksi lengkap (hasil JOIN)
 * @var string $statusTampil Status hasil hitung tanggal
 * @var int    $sisaHari     Sisa hari menuju jatuh tempo (negatif = telat)
 * @var int    $dendaPerHari Tarif denda dari tabel settings
 */

$masihPinjam = empty($loan['tanggal_kembali']);

// Perkiraan denda bila buku dikembalikan hari ini (hanya untuk yang telat)
$perkiraanDenda = ($masihPinjam && $sisaHari < 0) ? abs($sisaHari) * $dendaPerHari : 0;
?>

<?= $this->section('pageActions') ?>
    <div class="d-flex gap-2">
        <?php if ($masihPinjam) : ?>
            <a href="<?= site_url('loans/' . $loan['id'] . '/edit') ?>" class="dl-btn dl-btn--light">
                <i class="bi bi-pencil"></i> Ubah
            </a>
        <?php endif; ?>
        <a href="<?= site_url('loans') ?>" class="dl-btn dl-btn--light">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row g-3">

    <!-- ==================== KOLOM KIRI: DETAIL ==================== -->
    <div class="col-12 col-lg-8">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title"><?= esc($loan['kode_transaksi']) ?></h2>
                <span class="dl-badge <?= badge_status($statusTampil) ?>">
                    <?= $statusTampil === 'dikembalikan' ? 'Sudah Kembali' : ucfirst($statusTampil) ?>
                </span>
            </div>

            <div class="dl-card__body">

                <!-- Data buku -->
                <div class="d-flex gap-3 p-3 border rounded mb-3">
                    <?php if (! empty($loan['sampul'])) : ?>
                        <img class="dl-book__cover" src="<?= base_url('uploads/covers/' . $loan['sampul']) ?>"
                             alt="Sampul <?= esc($loan['judul']) ?>">
                    <?php else : ?>
                        <div class="dl-book__cover"><i class="bi bi-book"></i></div>
                    <?php endif; ?>

                    <div>
                        <div class="fw-semibold"><?= esc($loan['judul']) ?></div>
                        <div class="small text-muted"><?= esc($loan['penulis']) ?></div>
                        <div class="small text-muted mt-1">
                            <span class="dl-badge dl-badge--gray"><?= esc($loan['kode_buku']) ?></span>
                            <span class="dl-badge dl-badge--blue"><?= esc($loan['nama_kategori'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Linimasa tanggal -->
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="small text-muted">Tanggal Pinjam</div>
                        <div class="fw-semibold"><?= esc(tanggal_indo($loan['tanggal_pinjam'])) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="small text-muted">Jatuh Tempo</div>
                        <div class="fw-semibold"><?= esc(tanggal_indo($loan['tanggal_jatuh_tempo'])) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="small text-muted">Tanggal Kembali</div>
                        <div class="fw-semibold"><?= esc(tanggal_indo($loan['tanggal_kembali'])) ?></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="small text-muted">Denda Tercatat</div>
                        <div class="fw-semibold <?= (int) $loan['denda'] > 0 ? 'text-danger' : '' ?>">
                            <?= esc(rupiah($loan['denda'])) ?>
                        </div>
                    </div>
                </div>

                <?php if (! empty($loan['catatan'])) : ?>
                    <hr>
                    <div class="small text-muted">Catatan</div>
                    <div><?= esc($loan['catatan']) ?></div>
                <?php endif; ?>

                <hr>
                <div class="small text-muted">
                    Dicatat oleh <strong><?= esc($loan['nama_petugas'] ?? 'sistem') ?></strong>
                    pada <?= esc(tanggal_indo($loan['created_at'], false)) ?>
                </div>

            </div>
        </div>
    </div>

    <!-- =============== KOLOM KANAN: ANGGOTA & STATUS =============== -->
    <div class="col-12 col-lg-4">

        <!-- Kartu anggota -->
        <div class="dl-card mb-3">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Peminjam</h2>
            </div>
            <div class="dl-card__body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="dl-member__avatar"><?= esc(inisial($loan['nama_anggota'])) ?></span>
                    <div>
                        <div class="fw-semibold"><?= esc($loan['nama_anggota']) ?></div>
                        <div class="small text-muted"><?= esc($loan['kode_anggota']) ?></div>
                    </div>
                </div>

                <div class="small text-muted">Email</div>
                <div class="mb-2"><?= esc($loan['email'] ?? '-') ?></div>

                <div class="small text-muted">Telepon</div>
                <div class="mb-2"><?= esc($loan['telepon'] ?? '-') ?></div>

                <div class="small text-muted">Status Anggota</div>
                <span class="dl-badge <?= badge_status($loan['status_anggota']) ?>">
                    <?= esc(ucfirst($loan['status_anggota'])) ?>
                </span>
            </div>
        </div>

        <!-- Kartu status jatuh tempo -->
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Status Jatuh Tempo</h2>
            </div>
            <div class="dl-card__body">
                <?php if (! $masihPinjam) : ?>
                    <p class="mb-2">
                        Buku sudah dikembalikan pada
                        <strong><?= esc(tanggal_indo($loan['tanggal_kembali'], false)) ?></strong>.
                    </p>
                    <?php if ((int) $loan['denda'] > 0) : ?>
                        <p class="text-danger mb-0">Denda yang tercatat: <?= esc(rupiah($loan['denda'])) ?>.</p>
                    <?php else : ?>
                        <p class="text-success mb-0">Tidak ada denda.</p>
                    <?php endif; ?>

                <?php elseif ($sisaHari < 0) : ?>
                    <?php // Perkiraan denda dihitung dari tarif di tabel settings ?>
                    <p class="mb-2 text-danger">
                        Terlambat <strong><?= abs($sisaHari) ?> hari</strong>.
                    </p>
                    <p class="mb-2">
                        Perkiraan denda bila dikembalikan hari ini:<br>
                        <strong class="text-danger"><?= esc(rupiah($perkiraanDenda)) ?></strong>
                        <span class="small text-muted">
                            (<?= abs($sisaHari) ?> hari &times; <?= esc(rupiah($dendaPerHari)) ?>)
                        </span>
                    </p>
                    <p class="small text-muted mb-0">
                        Nilai final dihitung ulang saat proses pengembalian.
                    </p>

                <?php else : ?>
                    <p class="mb-0">
                        <?= $sisaHari === 0
                            ? 'Jatuh tempo <strong>hari ini</strong>.'
                            : 'Masih ada <strong>' . $sisaHari . ' hari</strong> sebelum jatuh tempo.' ?>
                    </p>
                <?php endif; ?>

                <?php if ($masihPinjam) : ?>
                    <hr>
                    <form action="<?= site_url('loans/' . $loan['id'] . '/extend') ?>" method="post"
                          data-confirm="Perpanjang jatuh tempo transaksi ini?">
                        <?= csrf_field() ?>
                        <button type="submit" class="dl-btn dl-btn--success w-100 justify-content-center">
                            <i class="bi bi-arrow-clockwise"></i> Perpanjang Jatuh Tempo
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
