<?php

/**
 * PARTIAL: FORM KATEGORI
 * ---------------------------------------------------------------------
 * Dipakai bersama oleh category/create.php dan category/edit.php.
 *
 * @var array|null $category Data lama saat mode edit; null saat mode tambah
 */

// Data lama (mode edit). Mode tambah => array kosong.
$category = $category ?? [];

// Pesan error validasi dari controller (flashdata 'errors')
$errors = session()->getFlashdata('errors') ?? [];

/**
 * Ambil nilai field dengan urutan prioritas:
 *   1. old()     -> input terakhir user (saat validasi gagal)
 *   2. $category -> data dari database (mode edit)
 *   3. $bawaan   -> nilai default
 */
$nilai = static fn (string $field, string $bawaan = ''): string
    => (string) old($field, $category[$field] ?? $bawaan);

/** Kelas Bootstrap untuk menandai input yang gagal validasi. */
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>

<div class="row g-3">

    <!-- Nama kategori -->
    <div class="col-12 col-md-6">
        <label for="nama" class="form-label small fw-semibold">
            Nama Kategori <span class="text-danger">*</span>
        </label>
        <input type="text" id="nama" name="nama"
               class="form-control<?= $invalid('nama') ?>"
               value="<?= esc($nilai('nama')) ?>"
               maxlength="60" required autofocus>
        <?php if (isset($errors['nama'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['nama']) ?></div>
        <?php else : ?>
            <div class="form-text">Contoh: Novel, Teknologi, Self Improvement.</div>
        <?php endif; ?>
    </div>

    <!-- Slug -->
    <div class="col-12 col-md-6">
        <label for="slug" class="form-label small fw-semibold">Slug</label>
        <input type="text" id="slug" name="slug"
               class="form-control<?= $invalid('slug') ?>"
               value="<?= esc($nilai('slug')) ?>"
               maxlength="80" placeholder="otomatis dari nama">
        <?php if (isset($errors['slug'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['slug']) ?></div>
        <?php else : ?>
            <div class="form-text">
                Versi URL dari nama (huruf kecil &amp; strip). Biarkan kosong untuk diisi otomatis.
            </div>
        <?php endif; ?>
    </div>

    <!-- Deskripsi -->
    <div class="col-12">
        <label for="deskripsi" class="form-label small fw-semibold">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" rows="3"
                  class="form-control<?= $invalid('deskripsi') ?>"
                  maxlength="255"
                  placeholder="Penjelasan singkat isi kategori ini"><?= esc($nilai('deskripsi')) ?></textarea>
        <?php if (isset($errors['deskripsi'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['deskripsi']) ?></div>
        <?php else : ?>
            <div class="form-text">Opsional, maksimal 255 karakter.</div>
        <?php endif; ?>
    </div>

</div>
