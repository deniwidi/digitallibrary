<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN UBAH KATEGORI
 * ---------------------------------------------------------------------
 * Form dikirim POST ke /categories/{id}/update, memakai partial field
 * yang sama dengan halaman tambah (category/_form.php).
 *
 * @var array $category   Data kategori yang sedang diubah
 * @var int   $jumlahBuku Banyaknya buku yang memakai kategori ini
 */
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('categories') ?>" class="dl-btn dl-btn--light">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12 col-xl-8">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Ubah Data Kategori</h2>
                <span class="dl-badge <?= $jumlahBuku > 0 ? 'dl-badge--green' : 'dl-badge--gray' ?>">
                    <?= (int) $jumlahBuku ?> buku
                </span>
            </div>

            <div class="dl-card__body">
                <?php if ($jumlahBuku > 0) : ?>
                    <?php // Peringatan ringan: perubahan nama ikut terlihat di katalog ?>
                    <div class="dl-alert dl-alert--success mb-3">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            Kategori ini dipakai <strong><?= (int) $jumlahBuku ?> buku</strong>.
                            Mengubah namanya akan langsung terlihat pada seluruh buku tersebut.
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('categories/' . $category['id'] . '/update') ?>"
                      method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <?= $this->include('category/_form') ?>

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Perubahan
                        </button>
                        <a href="<?= site_url('categories') ?>" class="dl-btn dl-btn--light">Batal</a>

                        <?php if (! empty($category['updated_at'])) : ?>
                            <span class="small text-muted ms-auto">
                                Diperbarui <?= esc(tanggal_indo($category['updated_at'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
