<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN KATALOG BUKU
 * ---------------------------------------------------------------------
 * Berisi: kartu ringkasan koleksi, form pencarian + filter kategori,
 * tabel data, tombol aksi (Edit/Hapus), dan navigasi paginasi.
 *
 * Variabel dari Book::index():
 * @var array  $books      Baris buku pada halaman aktif (sudah di-JOIN kategori)
 * @var object $pager      Objek pager CodeIgniter
 * @var string $keyword    Kata kunci pencarian yang sedang aktif
 * @var int    $categoryId Filter kategori yang sedang aktif (0 = semua)
 * @var array  $categories Daftar kategori [id => nama] untuk dropdown
 * @var int    $nomorAwal  Nomor urut baris pertama di halaman ini
 * @var int    $totalData  Total baris hasil pencarian (semua halaman)
 * @var array  $ringkasan  Angka ringkasan koleksi
 */
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('books/create') ?>" class="dl-btn dl-btn--primary">
        <i class="bi bi-plus-lg"></i> Tambah Buku
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ==================== KARTU RINGKASAN KOLEKSI =================== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--blue"><i class="bi bi-book-half"></i></div>
            <div>
                <p class="dl-stat__label">Judul Buku</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['judul'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--green"><i class="bi bi-stack"></i></div>
            <div>
                <p class="dl-stat__label">Total Eksemplar</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['eksemplar'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--amber"><i class="bi bi-journal-arrow-up"></i></div>
            <div>
                <p class="dl-stat__label">Sedang Dipinjam</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['dipinjam'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--red"><i class="bi bi-bookshelf"></i></div>
            <div>
                <p class="dl-stat__label">Tersedia di Rak</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['tersedia'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ========================== TABEL BUKU ========================== -->
<div class="dl-card">

    <div class="dl-card__head flex-wrap">
        <h2 class="dl-card__title">Daftar Buku</h2>

        <?php /* Form pencarian: method GET agar keyword & filter tersimpan
                 di URL, sehingga bisa di-bookmark dan dipakai ulang oleh
                 link paginasi. */ ?>
        <form action="<?= site_url('books') ?>" method="get" class="d-flex flex-wrap gap-2">
            <div class="dl-search" style="min-width:230px">
                <i class="bi bi-search"></i>
                <input type="search" name="keyword" value="<?= esc($keyword) ?>"
                       placeholder="Cari judul, penulis, penerbit, ISBN...">
            </div>

            <select name="category_id" class="dl-select">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $id => $nama) : ?>
                    <option value="<?= $id ?>" <?= $categoryId === (int) $id ? 'selected' : '' ?>>
                        <?= esc($nama) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="dl-btn dl-btn--primary dl-btn--sm">Cari</button>

            <?php if ($keyword !== '' || $categoryId > 0) : ?>
                <a href="<?= site_url('books') ?>" class="dl-btn dl-btn--light dl-btn--sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($keyword !== '' || $categoryId > 0) : ?>
        <div class="px-3 pb-2">
            <span class="dl-badge dl-badge--blue">
                <?= number_format($totalData, 0, ',', '.') ?> hasil ditemukan
                <?= $keyword !== '' ? 'untuk "' . esc($keyword) . '"' : '' ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="dl-table-wrap">
        <table class="dl-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th style="width:60px">Sampul</th>
                    <th>Judul &amp; Penulis</th>
                    <th>Kategori</th>
                    <th>Tahun</th>
                    <th>Stok</th>
                    <th>Rating</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)) : ?>
                    <tr>
                        <td colspan="8" class="dl-empty">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:26px"></i>
                            <?= ($keyword !== '' || $categoryId > 0)
                                ? 'Tidak ada buku yang cocok dengan pencarian Anda.'
                                : 'Belum ada data buku. Klik "Tambah Buku" untuk mulai.' ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $nomor = $nomorAwal; ?>
                    <?php foreach ($books as $book) : ?>
                        <tr>
                            <td><?= $nomor++ ?></td>

                            <td>
                                <?php // Tampilkan sampul bila ada, jika tidak pakai placeholder ikon ?>
                                <?php if (! empty($book['sampul'])) : ?>
                                    <img class="dl-book__cover"
                                         src="<?= base_url('uploads/covers/' . $book['sampul']) ?>"
                                         alt="Sampul <?= esc($book['judul']) ?>">
                                <?php else : ?>
                                    <div class="dl-book__cover"><i class="bi bi-book"></i></div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="fw-semibold"><?= esc($book['judul']) ?></div>
                                <div class="small text-muted"><?= esc($book['penulis']) ?></div>
                                <div class="small text-muted">
                                    <?= esc($book['kode_buku']) ?>
                                    <?= ! empty($book['isbn']) ? ' &middot; ISBN ' . esc($book['isbn']) : '' ?>
                                </div>
                            </td>

                            <td>
                                <span class="dl-badge dl-badge--blue">
                                    <?= esc($book['nama_kategori'] ?? '-') ?>
                                </span>
                            </td>

                            <td><?= esc($book['tahun_terbit']) ?></td>

                            <td class="text-nowrap">
                                <?php /* Warna angka mengikuti sisa stok: merah bila habis,
                                         kuning bila tinggal sedikit. */ ?>
                                <?php
                                $tersedia = (int) $book['stok_tersedia'];
                                $warna    = $tersedia === 0 ? 'dl-badge--red'
                                    : ($tersedia <= 2 ? 'dl-badge--amber' : 'dl-badge--green');
                                ?>
                                <span class="dl-badge <?= $warna ?>"><?= $tersedia ?> tersedia</span>
                                <div class="small text-muted mt-1">dari <?= (int) $book['stok'] ?> eksemplar</div>
                            </td>

                            <td class="text-nowrap">
                                <span class="dl-stars"><?= bintang($book['rating']) ?></span>
                                <div class="small text-muted"><?= esc($book['rating']) ?></div>
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= site_url('books/' . $book['id'] . '/edit') ?>"
                                       class="dl-btn dl-btn--light dl-btn--sm" title="Ubah data">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <?php /* Hapus memakai POST + csrf_field() supaya tidak bisa
                                             dipicu lewat link dari situs lain (CSRF).
                                             data-confirm ditangani dashboard.js. */ ?>
                                    <form action="<?= site_url('books/' . $book['id'] . '/delete') ?>"
                                          method="post" class="d-inline"
                                          data-confirm="Hapus buku &quot;<?= esc($book['judul']) ?>&quot;?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dl-btn dl-btn--danger dl-btn--sm" title="Hapus data">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ======================== PAGINASI ========================== -->
    <?php if ($totalData > 0) : ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 border-top">
            <span class="small text-muted">
                Menampilkan <?= count($books) ?> dari <?= number_format($totalData, 0, ',', '.') ?> judul
            </span>
            <?= $pager->links('books', 'dashboard_pager') ?>
        </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
