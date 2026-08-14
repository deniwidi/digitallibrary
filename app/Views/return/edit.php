<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN KOREKSI PENGEMBALIAN
 * ---------------------------------------------------------------------
 * Untuk memperbaiki data pengembalian yang sudah tersimpan: tanggal
 * kembali, nominal denda, dan catatan.
 *
 * Kondisi buku (baik/hilang) TIDAK bisa diubah di sini karena
 * menyangkut stok. Untuk mengubahnya: batalkan pengembalian di halaman
 * riwayat, lalu proses ulang dengan kondisi yang benar.
 *
 * @var array $loan  Data transaksi lengkap (hasil JOIN)
 * @var int   $tarif Tarif denda per hari dari tabel settings
 */

$errors  = session()->getFlashdata('errors') ?? [];
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';

// Selisih hari antara tanggal kembali tersimpan dan jatuh tempo
$telat = max(0, (int) floor(
    (strtotime($loan['tanggal_kembali']) - strtotime($loan['tanggal_jatuh_tempo'])) / 86400
));
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('returns/history') ?>" class="dl-btn dl-btn--light">
        <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12 col-xl-8">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Koreksi Data Pengembalian</h2>
                <span class="dl-badge <?= badge_status($loan['status']) ?>">
                    <?= $loan['status'] === 'dikembalikan' ? 'Tepat Waktu' : esc(ucfirst($loan['status'])) ?>
                </span>
            </div>

            <div class="dl-card__body">

                <!-- Data yang tidak bisa diubah di halaman ini -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Kode Transaksi</label>
                        <input type="text" class="form-control" value="<?= esc($loan['kode_transaksi']) ?>" disabled>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Anggota</label>
                        <input type="text" class="form-control"
                               value="<?= esc($loan['kode_anggota'] . ' - ' . $loan['nama_anggota']) ?>" disabled>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Buku</label>
                        <input type="text" class="form-control"
                               value="<?= esc($loan['kode_buku'] . ' - ' . $loan['judul']) ?>" disabled>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Jatuh Tempo</label>
                        <input type="text" class="form-control"
                               value="<?= esc(tanggal_indo($loan['tanggal_jatuh_tempo'], false)) ?>" disabled>
                    </div>
                </div>

                <?php if ($loan['status'] === 'hilang') : ?>
                    <div class="dl-alert dl-alert--danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div class="small">
                            Buku ini berstatus <strong>hilang</strong> dan sudah dikeluarkan dari koleksi.
                            Untuk mengembalikannya ke koleksi, batalkan pengembalian di halaman riwayat
                            lalu proses ulang dengan kondisi "Baik".
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('returns/' . $loan['id'] . '/update') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label for="tanggal_kembali" class="form-label small fw-semibold">
                                Tanggal Kembali <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="tanggal_kembali" name="tanggal_kembali"
                                   class="form-control<?= $invalid('tanggal_kembali') ?>"
                                   value="<?= esc(old('tanggal_kembali', $loan['tanggal_kembali'])) ?>"
                                   min="<?= esc($loan['tanggal_pinjam']) ?>" required>
                            <?php if (isset($errors['tanggal_kembali'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['tanggal_kembali']) ?></div>
                            <?php else : ?>
                                <div class="form-text">
                                    Saat ini terlambat <?= $telat ?> hari dari jatuh tempo.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="denda" class="form-label small fw-semibold">Denda (Rp)</label>
                            <input type="number" id="denda" name="denda" min="0" step="500"
                                   class="form-control<?= $invalid('denda') ?>"
                                   value="<?= esc(old('denda', (string) $loan['denda'])) ?>">
                            <?php if (isset($errors['denda'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['denda']) ?></div>
                            <?php else : ?>
                                <div class="form-text">
                                    Kosongkan untuk dihitung ulang otomatis (<?= esc(rupiah($tarif)) ?>/hari).
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="catatan" class="form-label small fw-semibold">Catatan</label>
                            <textarea id="catatan" name="catatan" rows="2" maxlength="255"
                                      class="form-control<?= $invalid('catatan') ?>"><?= esc(old('catatan', $loan['catatan'] ?? '')) ?></textarea>
                            <?php if (isset($errors['catatan'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['catatan']) ?></div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Koreksi
                        </button>
                        <a href="<?= site_url('returns/history') ?>" class="dl-btn dl-btn--light">Batal</a>

                        <span class="small text-muted ms-auto">
                            Dicatat oleh <?= esc($loan['nama_petugas'] ?? 'sistem') ?>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
