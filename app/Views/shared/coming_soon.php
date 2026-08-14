<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN PLACEHOLDER "MODUL BELUM TERSEDIA"
 * ---------------------------------------------------------------------
 * Dipakai sementara oleh modul yang controller-nya belum digarap, supaya
 * menu sidebar tidak melempar error 404 saat diklik. Setiap modul yang
 * sudah selesai akan mengganti view ini dengan halaman aslinya.
 *
 * @var string $modul     Nama modul, mis. "Katalog Buku"
 * @var string $keterangan Penjelasan singkat isi modul nanti
 */
?>

<?= $this->section('content') ?>

<div class="dl-card">
    <div class="dl-card__body text-center py-5">

        <div class="d-inline-grid mb-3"
             style="width:64px;height:64px;place-items:center;border-radius:16px;background:#EFF4FF;color:#3B82F6;font-size:28px">
            <i class="bi bi-cone-striped"></i>
        </div>

        <h2 class="h5 fw-bold mb-2">Modul <?= esc($modul) ?> sedang dibangun</h2>
        <p class="text-muted small mb-4" style="max-width:460px;margin-inline:auto">
            <?= esc($keterangan) ?>
        </p>

        <div class="d-flex justify-content-center gap-2">
            <a href="<?= site_url('dashboard') ?>" class="dl-btn dl-btn--light">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
            <a href="<?= site_url('members') ?>" class="dl-btn dl-btn--primary">
                <i class="bi bi-people"></i> Buka Modul Anggota
            </a>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
