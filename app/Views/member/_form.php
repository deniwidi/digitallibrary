<?php

/**
 * PARTIAL: FORM ANGGOTA
 * ---------------------------------------------------------------------
 * Dipakai bersama oleh member/create.php dan member/edit.php supaya
 * daftar field cukup ditulis sekali (kalau ada kolom baru, cukup ubah
 * di file ini).
 *
 * @var array|null $member Data lama saat mode edit; null saat mode tambah
 * @var string     $kodeBaru Kode anggota otomatis (hanya mode tambah)
 */

// Data lama (mode edit). Mode tambah => array kosong.
$member = $member ?? [];

// Daftar pesan error validasi dari controller (flashdata 'errors').
// Dipakai untuk menandai field yang bermasalah satu per satu.
$errors = session()->getFlashdata('errors') ?? [];

/**
 * Ambil nilai field dengan urutan prioritas:
 *   1. old()      -> input terakhir user (saat validasi gagal)
 *   2. $member    -> data dari database (mode edit)
 *   3. $bawaan    -> nilai default
 */
$nilai = static fn (string $field, string $bawaan = ''): string
    => (string) old($field, $member[$field] ?? $bawaan);

/** Kelas Bootstrap untuk menandai input yang gagal validasi. */
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>

<div class="row g-3">

    <!-- Kode anggota -->
    <div class="col-12 col-md-6">
        <label for="kode_anggota" class="form-label small fw-semibold">
            Kode Anggota <span class="text-danger">*</span>
        </label>
        <input type="text" id="kode_anggota" name="kode_anggota"
               class="form-control<?= $invalid('kode_anggota') ?>"
               value="<?= esc($nilai('kode_anggota', $kodeBaru ?? '')) ?>"
               maxlength="20" required>
        <?php if (isset($errors['kode_anggota'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['kode_anggota']) ?></div>
        <?php else : ?>
            <div class="form-text">Terisi otomatis, boleh diubah selama belum dipakai anggota lain.</div>
        <?php endif; ?>
    </div>

    <!-- Nama -->
    <div class="col-12 col-md-6">
        <label for="nama" class="form-label small fw-semibold">
            Nama Lengkap <span class="text-danger">*</span>
        </label>
        <input type="text" id="nama" name="nama"
               class="form-control<?= $invalid('nama') ?>"
               value="<?= esc($nilai('nama')) ?>"
               maxlength="100" required autofocus>
        <?php if (isset($errors['nama'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['nama']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Email -->
    <div class="col-12 col-md-6">
        <label for="email" class="form-label small fw-semibold">Email</label>
        <input type="email" id="email" name="email"
               class="form-control<?= $invalid('email') ?>"
               value="<?= esc($nilai('email')) ?>"
               maxlength="100" placeholder="nama@email.com">
        <?php if (isset($errors['email'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['email']) ?></div>
        <?php else : ?>
            <div class="form-text">Opsional, tetapi tidak boleh sama dengan anggota lain.</div>
        <?php endif; ?>
    </div>

    <!-- Telepon -->
    <div class="col-12 col-md-6">
        <label for="telepon" class="form-label small fw-semibold">No. Telepon</label>
        <input type="text" id="telepon" name="telepon" inputmode="numeric"
               class="form-control<?= $invalid('telepon') ?>"
               value="<?= esc($nilai('telepon')) ?>"
               maxlength="20" placeholder="08xxxxxxxxxx">
        <?php if (isset($errors['telepon'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['telepon']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Jenis kelamin -->
    <div class="col-12 col-md-4">
        <label for="jenis_kelamin" class="form-label small fw-semibold">
            Jenis Kelamin <span class="text-danger">*</span>
        </label>
        <select id="jenis_kelamin" name="jenis_kelamin" class="form-select<?= $invalid('jenis_kelamin') ?>" required>
            <?php $jk = $nilai('jenis_kelamin', 'L'); ?>
            <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select>
        <?php if (isset($errors['jenis_kelamin'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['jenis_kelamin']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Tanggal daftar -->
    <div class="col-12 col-md-4">
        <label for="tanggal_daftar" class="form-label small fw-semibold">
            Tanggal Daftar <span class="text-danger">*</span>
        </label>
        <input type="date" id="tanggal_daftar" name="tanggal_daftar"
               class="form-control<?= $invalid('tanggal_daftar') ?>"
               value="<?= esc($nilai('tanggal_daftar', date('Y-m-d'))) ?>" required>
        <?php if (isset($errors['tanggal_daftar'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['tanggal_daftar']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Status -->
    <div class="col-12 col-md-4">
        <label for="status" class="form-label small fw-semibold">
            Status <span class="text-danger">*</span>
        </label>
        <select id="status" name="status" class="form-select<?= $invalid('status') ?>" required>
            <?php $st = $nilai('status', 'aktif'); ?>
            <?php foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'diblokir' => 'Diblokir'] as $key => $label) : ?>
                <option value="<?= $key ?>" <?= $st === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['status'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['status']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Alamat -->
    <div class="col-12">
        <label for="alamat" class="form-label small fw-semibold">Alamat</label>
        <textarea id="alamat" name="alamat" rows="2"
                  class="form-control<?= $invalid('alamat') ?>"
                  maxlength="255" placeholder="Alamat tempat tinggal"><?= esc($nilai('alamat')) ?></textarea>
        <?php if (isset($errors['alamat'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['alamat']) ?></div>
        <?php endif; ?>
    </div>

</div>
