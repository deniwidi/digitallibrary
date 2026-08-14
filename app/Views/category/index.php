<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN DAFTAR KATEGORI
 * ---------------------------------------------------------------------
 * Berisi: kartu ringkasan, form pencarian, tabel kategori beserta jumlah
 * buku pemakainya, tombol aksi (Edit/Hapus), dan navigasi paginasi.
 *
 * Variabel dari Category::index():
 * @var array  $categories Baris kategori pada halaman aktif
 * @var object $pager      Objek pager CodeIgniter
 * @var string $keyword    Kata kunci pencarian yang sedang aktif
 * @var int    $nomorAwal  Nomor urut baris pertama di halaman ini
 * @var int    $totalData  Total baris hasil pencarian (semua halaman)
 * @var array  $ringkasan  Angka ringkasan kategori
 * @var array  $jumlahBuku [category_id => jumlah buku]
 */
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('categories/create') ?>" class="dl-btn dl-btn--primary">
        <i class="bi bi-plus-lg"></i> Tambah Kategori
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ====================== KARTU RINGKASAN ======================== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--blue"><i class="bi bi-tags"></i></div>
            <div>
                <p class="dl-stat__label">Total Kategori</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['total'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--green"><i class="bi bi-check2-circle"></i></div>
            <div>
                <p class="dl-stat__label">Terpakai</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['terpakai'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--amber"><i class="bi bi-inbox"></i></div>
            <div>
                <p class="dl-stat__label">Belum Terpakai</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['kosong'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--red"><i class="bi bi-book-half"></i></div>
            <div>
                <p class="dl-stat__label">Buku Terkategori</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['buku'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ======================== TABEL KATEGORI ======================== -->
<div class="dl-card">

    <div class="dl-card__head flex-wrap">
        <h2 class="dl-card__title">Daftar Kategori</h2>

        <?php /* Form pencarian: method GET agar keyword tersimpan di URL
                 dan bisa dipakai ulang oleh link paginasi. */ ?>
        <form action="<?= site_url('categories') ?>" method="get" class="d-flex flex-wrap gap-2">
            <div class="dl-search" style="min-width:230px">
                <i class="bi bi-search"></i>
                <input type="search" name="keyword" value="<?= esc($keyword) ?>"
                       placeholder="Cari nama, slug, atau deskripsi...">
            </div>

            <button type="submit" class="dl-btn dl-btn--primary dl-btn--sm">Cari</button>

            <?php if ($keyword !== '') : ?>
                <a href="<?= site_url('categories') ?>" class="dl-btn dl-btn--light dl-btn--sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($keyword !== '') : ?>
        <div class="px-3 pb-2">
            <span class="dl-badge dl-badge--blue">
                <?= number_format($totalData, 0, ',', '.') ?> hasil ditemukan untuk "<?= esc($keyword) ?>"
            </span>
        </div>
    <?php endif; ?>

    <div class="dl-table-wrap">
        <table class="dl-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Nama Kategori</th>
                    <th>Slug</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Buku</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)) : ?>
                    <tr>
                        <td colspan="6" class="dl-empty">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:26px"></i>
                            <?= $keyword !== ''
                                ? 'Tidak ada kategori yang cocok dengan pencarian Anda.'
                                : 'Belum ada kategori. Klik "Tambah Kategori" untuk mulai.' ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $nomor = $nomorAwal; ?>
                    <?php foreach ($categories as $category) : ?>
                        <?php // Jumlah buku diambil dari array hasil satu query di controller ?>
                        <?php $dipakai = (int) ($jumlahBuku[$category['id']] ?? 0); ?>
                        <tr>
                            <td><?= $nomor++ ?></td>

                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="dl-stat__icon dl-stat__icon--blue"
                                          style="width:32px;height:32px;border-radius:9px;font-size:14px">
                                        <i class="bi bi-tag-fill"></i>
                                    </span>
                                    <span class="fw-semibold"><?= esc($category['nama']) ?></span>
                                </div>
                            </td>

                            <td><code class="small"><?= esc($category['slug']) ?></code></td>

                            <td class="small text-muted">
                                <?= $category['deskripsi'] !== null && $category['deskripsi'] !== ''
                                    ? esc($category['deskripsi'])
                                    : '<span class="text-muted">-</span>' ?>
                            </td>

                            <td>
                                <?php // Kategori kosong ditandai abu-abu agar mudah dikenali ?>
                                <span class="dl-badge <?= $dipakai > 0 ? 'dl-badge--green' : 'dl-badge--gray' ?>">
                                    <?= $dipakai ?> buku
                                </span>
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= site_url('categories/' . $category['id'] . '/edit') ?>"
                                       class="dl-btn dl-btn--light dl-btn--sm" title="Ubah data">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <?php if ($dipakai > 0) : ?>
                                        <?php /* Kategori yang masih dipakai tidak bisa dihapus.
                                                 Tombolnya dinonaktifkan agar user tahu sejak awal
                                                 (server tetap menolak lewat Category::delete). */ ?>
                                        <button type="button" class="dl-btn dl-btn--light dl-btn--sm" disabled
                                                title="Tidak bisa dihapus: masih dipakai <?= $dipakai ?> buku">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php else : ?>
                                        <form action="<?= site_url('categories/' . $category['id'] . '/delete') ?>"
                                              method="post" class="d-inline"
                                              data-confirm="Hapus kategori &quot;<?= esc($category['nama']) ?>&quot;?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="dl-btn dl-btn--danger dl-btn--sm" title="Hapus data">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
                Menampilkan <?= count($categories) ?> dari <?= number_format($totalData, 0, ',', '.') ?> kategori
            </span>
            <?= $pager->links('categories', 'dashboard_pager') ?>
        </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
