<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN TAMBAH KATEGORI
 * ---------------------------------------------------------------------
 * Form dikirim POST ke /categories/store. Field-nya berada di partial
 * category/_form.php agar sama persis dengan halaman edit.
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
                <h2 class="dl-card__title">Formulir Kategori Baru</h2>
            </div>

            <div class="dl-card__body">
                <?php // csrf_field() wajib: filter 'csrf' aktif global ?>
                <form action="<?= site_url('categories/store') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <?= $this->include('category/_form') ?>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Kategori
                        </button>
                        <a href="<?= site_url('categories') ?>" class="dl-btn dl-btn--light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    /**
     * Isi slug secara otomatis mengikuti nama yang diketik.
     * Begitu user mengubah slug sendiri, sinkronisasi dihentikan agar
     * ketikannya tidak tertimpa. Server tetap membuat slug sendiri bila
     * field ini dikosongkan, jadi skrip ini murni kenyamanan tampilan.
     */
    (function () {
        const nama = document.getElementById('nama');
        const slug = document.getElementById('slug');
        if (!nama || !slug) return;

        let manual = slug.value.trim() !== '';

        slug.addEventListener('input', function () { manual = true; });

        nama.addEventListener('input', function () {
            if (manual) return;
            slug.value = nama.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')   // selain huruf/angka jadi strip
                .replace(/^-+|-+$/g, '');      // buang strip di awal & akhir
        });
    })();
</script>
<?= $this->endSection() ?>
