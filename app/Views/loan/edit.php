<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN UBAH PEMINJAMAN
 * ---------------------------------------------------------------------
 * Hanya jatuh tempo dan catatan yang bisa diubah. Anggota dan buku
 * dikunci karena stoknya sudah terlanjur dipesan atas nama transaksi ini;
 * bila salah pilih, transaksinya dibatalkan lalu dibuat ulang.
 *
 * @var array $loan Data transaksi (sudah di-JOIN anggota & buku)
 */

$errors  = session()->getFlashdata('errors') ?? [];
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('loans') ?>" class="dl-btn dl-btn--light">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12 col-xl-8">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Ubah Transaksi</h2>
                <span class="dl-badge dl-badge--gray"><?= esc($loan['kode_transaksi']) ?></span>
            </div>

            <div class="dl-card__body">

                <!-- Ringkasan data yang tidak bisa diubah -->
                <div class="row g-3 mb-3">
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
                </div>

                <form action="<?= site_url('loans/' . $loan['id'] . '/update') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Pinjam</label>
                            <input type="text" class="form-control"
                                   value="<?= esc(tanggal_indo($loan['tanggal_pinjam'], false)) ?>" disabled>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="tanggal_jatuh_tempo" class="form-label small fw-semibold">
                                Jatuh Tempo <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="tanggal_jatuh_tempo" name="tanggal_jatuh_tempo"
                                   class="form-control<?= $invalid('tanggal_jatuh_tempo') ?>"
                                   value="<?= esc(old('tanggal_jatuh_tempo', $loan['tanggal_jatuh_tempo'])) ?>"
                                   min="<?= esc($loan['tanggal_pinjam']) ?>" required>
                            <?php if (isset($errors['tanggal_jatuh_tempo'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['tanggal_jatuh_tempo']) ?></div>
                            <?php else : ?>
                                <div class="form-text">Tidak boleh lebih awal dari tanggal pinjam.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="catatan" class="form-label small fw-semibold">Catatan</label>
                            <textarea id="catatan" name="catatan" rows="2" maxlength="255"
                                      class="form-control<?= $invalid('catatan') ?>"
                                      placeholder="Catatan tambahan"><?= esc(old('catatan', $loan['catatan'] ?? '')) ?></textarea>
                            <?php if (isset($errors['catatan'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['catatan']) ?></div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Perubahan
                        </button>
                        <a href="<?= site_url('loans/' . $loan['id']) ?>" class="dl-btn dl-btn--light">Lihat Detail</a>

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
