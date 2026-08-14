<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN TAMBAH BUKU
 * ---------------------------------------------------------------------
 * Form dikirim POST ke /books/store. Field-nya berada di partial
 * book/_form.php agar sama persis dengan halaman edit.
 *
 * @var string $kodeBaru   Kode buku otomatis dari BookModel::generateKode()
 * @var array  $categories Daftar kategori untuk dropdown
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
                <h2 class="dl-card__title">Formulir Buku Baru</h2>
            </div>

            <div class="dl-card__body">
                <?php /* enctype multipart WAJIB agar file sampul ikut terkirim.
                         csrf_field() wajib karena filter 'csrf' aktif global. */ ?>
                <form action="<?= site_url('books/store') ?>" method="post"
                      enctype="multipart/form-data" autocomplete="off">
                    <?= csrf_field() ?>

                    <?= $this->include('book/_form') ?>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Buku
                        </button>
                        <a href="<?= site_url('books') ?>" class="dl-btn dl-btn--light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
