<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN UBAH ANGGOTA
 * ---------------------------------------------------------------------
 * Form dikirim POST ke /members/{id}/update. Field-nya memakai partial
 * yang sama dengan halaman tambah (member/_form.php); nilai lama diisi
 * otomatis dari variabel $member.
 *
 * @var array $member Data anggota yang sedang diubah
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
                <h2 class="dl-card__title">Ubah Data Anggota</h2>
                <span class="dl-badge dl-badge--gray"><?= esc($member['kode_anggota']) ?></span>
            </div>

            <div class="dl-card__body">
                <form action="<?= site_url('members/' . $member['id'] . '/update') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <?= $this->include('member/_form') ?>

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Perubahan
                        </button>
                        <a href="<?= site_url('members') ?>" class="dl-btn dl-btn--light">Batal</a>

                        <?php // Info kecil: kapan data ini terakhir diperbarui ?>
                        <span class="small text-muted ms-auto">
                            Terdaftar <?= esc(tanggal_indo($member['tanggal_daftar'])) ?>
                            <?php if (! empty($member['updated_at'])) : ?>
                                &middot; diperbarui <?= esc(tanggal_indo($member['updated_at'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
