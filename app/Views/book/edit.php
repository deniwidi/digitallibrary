<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN UBAH BUKU
 * ---------------------------------------------------------------------
 * Form dikirim POST ke /books/{id}/update, memakai partial field yang
 * sama dengan halaman tambah (book/_form.php).
 *
 * @var array $book       Data buku yang sedang diubah
 * @var array $categories Daftar kategori untuk dropdown
 * @var int   $dipinjam   Eksemplar yang sedang dipinjam (batas bawah stok)
 */
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('books') ?>" class="dl-btn dl-btn--light">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12 col-xl-10">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Ubah Data Buku</h2>
                <span class="dl-badge dl-badge--gray"><?= esc($book['kode_buku']) ?></span>
            </div>

            <div class="dl-card__body">
                <form action="<?= site_url('books/' . $book['id'] . '/update') ?>" method="post"
                      enctype="multipart/form-data" autocomplete="off">
                    <?= csrf_field() ?>

                    <?= $this->include('book/_form') ?>

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Perubahan
                        </button>
                        <a href="<?= site_url('books') ?>" class="dl-btn dl-btn--light">Batal</a>

                        <span class="small text-muted ms-auto">
                            <?= (int) $dipinjam ?> eksemplar sedang dipinjam
                            <?php if (! empty($book['updated_at'])) : ?>
                                &middot; diperbarui <?= esc(tanggal_indo($book['updated_at'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
