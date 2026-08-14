<?php

/**
 * PARTIAL: FORM BUKU
 * ---------------------------------------------------------------------
 * Dipakai bersama oleh book/create.php dan book/edit.php supaya daftar
 * field cukup ditulis sekali.
 *
 * PENTING: form pemanggil WAJIB memakai enctype="multipart/form-data"
 * karena partial ini memuat input file sampul.
 *
 * @var array|null $book       Data lama saat mode edit; null saat mode tambah
 * @var array      $categories Daftar kategori [id => nama]
 * @var string     $kodeBaru   Kode buku otomatis (hanya mode tambah)
 * @var int        $dipinjam   Jumlah eksemplar yang sedang dipinjam (mode edit)
 */

// Data lama (mode edit). Mode tambah => array kosong.
$book = $book ?? [];

// Pesan error validasi dari controller (flashdata 'errors'),
// dipakai untuk menandai field yang bermasalah satu per satu.
$errors = session()->getFlashdata('errors') ?? [];

/**
 * Ambil nilai field dengan urutan prioritas:
 *   1. old()   -> input terakhir user (saat validasi gagal)
 *   2. $book   -> data dari database (mode edit)
 *   3. $bawaan -> nilai default
 */
$nilai = static fn (string $field, string $bawaan = ''): string
    => (string) old($field, $book[$field] ?? $bawaan);

/** Kelas Bootstrap untuk menandai input yang gagal validasi. */
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';

// Batas bawah stok saat mode edit: tidak boleh lebih kecil dari
// jumlah eksemplar yang sedang berada di tangan anggota.
$minStok = (int) ($dipinjam ?? 0);
?>

<div class="row g-3">

    <!-- Kode buku -->
    <div class="col-12 col-md-4">
        <label for="kode_buku" class="form-label small fw-semibold">
            Kode Buku <span class="text-danger">*</span>
        </label>
        <input type="text" id="kode_buku" name="kode_buku"
               class="form-control<?= $invalid('kode_buku') ?>"
               value="<?= esc($nilai('kode_buku', $kodeBaru ?? '')) ?>"
               maxlength="20" required>
        <?php if (isset($errors['kode_buku'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['kode_buku']) ?></div>
        <?php else : ?>
            <div class="form-text">Terisi otomatis, boleh diubah selama belum dipakai.</div>
        <?php endif; ?>
    </div>

    <!-- Judul -->
    <div class="col-12 col-md-8">
        <label for="judul" class="form-label small fw-semibold">
            Judul Buku <span class="text-danger">*</span>
        </label>
        <input type="text" id="judul" name="judul"
               class="form-control<?= $invalid('judul') ?>"
               value="<?= esc($nilai('judul')) ?>"
               maxlength="150" required autofocus>
        <?php if (isset($errors['judul'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['judul']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Penulis -->
    <div class="col-12 col-md-6">
        <label for="penulis" class="form-label small fw-semibold">
            Penulis <span class="text-danger">*</span>
        </label>
        <input type="text" id="penulis" name="penulis"
               class="form-control<?= $invalid('penulis') ?>"
               value="<?= esc($nilai('penulis')) ?>"
               maxlength="100" required>
        <?php if (isset($errors['penulis'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['penulis']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Penerbit -->
    <div class="col-12 col-md-6">
        <label for="penerbit" class="form-label small fw-semibold">
            Penerbit <span class="text-danger">*</span>
        </label>
        <input type="text" id="penerbit" name="penerbit"
               class="form-control<?= $invalid('penerbit') ?>"
               value="<?= esc($nilai('penerbit')) ?>"
               maxlength="100" required>
        <?php if (isset($errors['penerbit'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['penerbit']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Kategori -->
    <div class="col-12 col-md-4">
        <label for="category_id" class="form-label small fw-semibold">
            Kategori <span class="text-danger">*</span>
        </label>
        <select id="category_id" name="category_id" class="form-select<?= $invalid('category_id') ?>" required>
            <option value="">-- Pilih Kategori --</option>
            <?php $kategoriTerpilih = $nilai('category_id'); ?>
            <?php foreach ($categories as $id => $nama) : ?>
                <option value="<?= $id ?>" <?= $kategoriTerpilih === (string) $id ? 'selected' : '' ?>>
                    <?= esc($nama) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['category_id'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['category_id']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Tahun terbit -->
    <div class="col-6 col-md-4">
        <label for="tahun_terbit" class="form-label small fw-semibold">
            Tahun Terbit <span class="text-danger">*</span>
        </label>
        <input type="number" id="tahun_terbit" name="tahun_terbit"
               class="form-control<?= $invalid('tahun_terbit') ?>"
               value="<?= esc($nilai('tahun_terbit', date('Y'))) ?>"
               min="1901" max="2155" required>
        <?php if (isset($errors['tahun_terbit'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['tahun_terbit']) ?></div>
        <?php endif; ?>
    </div>

    <!-- ISBN -->
    <div class="col-6 col-md-4">
        <label for="isbn" class="form-label small fw-semibold">ISBN</label>
        <input type="text" id="isbn" name="isbn"
               class="form-control<?= $invalid('isbn') ?>"
               value="<?= esc($nilai('isbn')) ?>"
               maxlength="20" placeholder="9786020000000">
        <?php if (isset($errors['isbn'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['isbn']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Stok -->
    <div class="col-6 col-md-4">
        <label for="stok" class="form-label small fw-semibold">
            Jumlah Stok <span class="text-danger">*</span>
        </label>
        <input type="number" id="stok" name="stok"
               class="form-control<?= $invalid('stok') ?>"
               value="<?= esc($nilai('stok', '1')) ?>"
               min="<?= $minStok ?>" required>
        <?php if (isset($errors['stok'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['stok']) ?></div>
        <?php elseif ($minStok > 0) : ?>
            <div class="form-text">Minimal <?= $minStok ?> karena sedang dipinjam.</div>
        <?php else : ?>
            <div class="form-text">Total eksemplar yang dimiliki perpustakaan.</div>
        <?php endif; ?>
    </div>

    <!-- Rating -->
    <div class="col-6 col-md-4">
        <label for="rating" class="form-label small fw-semibold">Rating</label>
        <input type="number" id="rating" name="rating" step="0.1" min="0" max="5"
               class="form-control<?= $invalid('rating') ?>"
               value="<?= esc($nilai('rating', '0')) ?>">
        <?php if (isset($errors['rating'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['rating']) ?></div>
        <?php else : ?>
            <div class="form-text">Skala 0 - 5, contoh: 4.5</div>
        <?php endif; ?>
    </div>

    <!-- Sampul -->
    <div class="col-12 col-md-4">
        <label for="sampul" class="form-label small fw-semibold">Sampul Buku</label>
        <input type="file" id="sampul" name="sampul" accept="image/png,image/jpeg,image/webp"
               class="form-control<?= $invalid('sampul') ?>">
        <?php if (isset($errors['sampul'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['sampul']) ?></div>
        <?php else : ?>
            <div class="form-text">JPG/PNG/WEBP, maksimal 1 MB.</div>
        <?php endif; ?>
    </div>

    <?php // Pratinjau + opsi hapus sampul: hanya relevan pada mode edit ?>
    <?php if (! empty($book['sampul'])) : ?>
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 p-2 border rounded">
                <img src="<?= base_url('uploads/covers/' . $book['sampul']) ?>"
                     alt="Sampul saat ini" style="width:46px;height:64px;object-fit:cover;border-radius:6px">
                <div>
                    <div class="small fw-semibold">Sampul saat ini</div>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" value="1"
                               id="hapus_sampul" name="hapus_sampul">
                        <label class="form-check-label small text-danger" for="hapus_sampul">
                            Hapus sampul ini
                        </label>
                    </div>
                    <div class="form-text">Mengunggah file baru otomatis menimpa sampul lama.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sinopsis -->
    <div class="col-12">
        <label for="sinopsis" class="form-label small fw-semibold">Sinopsis</label>
        <textarea id="sinopsis" name="sinopsis" rows="3"
                  class="form-control<?= $invalid('sinopsis') ?>"
                  maxlength="2000" placeholder="Ringkasan isi buku"><?= esc($nilai('sinopsis')) ?></textarea>
        <?php if (isset($errors['sinopsis'])) : ?>
            <div class="invalid-feedback"><?= esc($errors['sinopsis']) ?></div>
        <?php endif; ?>
    </div>

</div>
