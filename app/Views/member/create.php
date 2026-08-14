<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN TAMBAH ANGGOTA
 * ---------------------------------------------------------------------
 * Form dikirim POST ke /members/store. Field-nya sendiri berada di
 * partial member/_form.php agar sama persis dengan halaman edit.
 *
 * @var string $kodeBaru Kode anggota otomatis dari MemberModel::generateKode()
 */
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('members') ?>" class="dl-btn dl-btn--light">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12 col-xl-9">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Formulir Anggota Baru</h2>
            </div>

            <div class="dl-card__body">
                <?php // csrf_field() wajib: filter 'csrf' aktif global ?>
                <form action="<?= site_url('members/store') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <?= $this->include('member/_form') ?>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Anggota
                        </button>
                        <a href="<?= site_url('members') ?>" class="dl-btn dl-btn--light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
