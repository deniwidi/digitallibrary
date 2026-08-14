<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN DAFTAR ANGGOTA
 * ---------------------------------------------------------------------
 * Berisi: kartu ringkasan status, form pencarian + filter, tabel data,
 * tombol aksi (Edit/Hapus), dan navigasi paginasi.
 *
 * Variabel dari Member::index():
 * @var array  $members   Baris anggota pada halaman aktif
 * @var object $pager     Objek pager CodeIgniter
 * @var string $keyword   Kata kunci pencarian yang sedang aktif
 * @var string $status    Filter status yang sedang aktif
 * @var int    $nomorAwal Nomor urut baris pertama di halaman ini
 * @var int    $totalData Total baris hasil pencarian (semua halaman)
 * @var array  $ringkasan Jumlah anggota per status
 */
?>

<?php // Tombol aksi di samping judul halaman (disediakan layout) ?>
<?= $this->section('pageActions') ?>
    <a href="<?= site_url('members/create') ?>" class="dl-btn dl-btn--primary">
        <i class="bi bi-plus-lg"></i> Tambah Anggota
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ==================== KARTU RINGKASAN STATUS ==================== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--blue"><i class="bi bi-people"></i></div>
            <div>
                <p class="dl-stat__label">Total Anggota</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['total'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--green"><i class="bi bi-person-check"></i></div>
            <div>
                <p class="dl-stat__label">Aktif</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['aktif'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--amber"><i class="bi bi-person-dash"></i></div>
            <div>
                <p class="dl-stat__label">Nonaktif</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['nonaktif'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--red"><i class="bi bi-person-slash"></i></div>
            <div>
                <p class="dl-stat__label">Diblokir</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['diblokir'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ========================= TABEL ANGGOTA ========================= -->
<div class="dl-card">

    <div class="dl-card__head flex-wrap">
        <h2 class="dl-card__title">Daftar Anggota</h2>

        <?php /* Form pencarian: method GET agar keyword tersimpan di URL
                 sehingga hasil pencarian bisa di-bookmark & di-share,
                 sekaligus dipakai ulang oleh link paginasi. */ ?>
        <form action="<?= site_url('members') ?>" method="get" class="d-flex flex-wrap gap-2">
            <div class="dl-search" style="min-width:230px">
                <i class="bi bi-search"></i>
                <input type="search" name="keyword" value="<?= esc($keyword) ?>"
                       placeholder="Cari nama, kode, email, telepon...">
            </div>

            <select name="status" class="dl-select">
                <option value="">Semua Status</option>
                <?php foreach (['aktif', 'nonaktif', 'diblokir'] as $opsi) : ?>
                    <option value="<?= $opsi ?>" <?= $status === $opsi ? 'selected' : '' ?>>
                        <?= ucfirst($opsi) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="dl-btn dl-btn--primary dl-btn--sm">Cari</button>

            <?php // Tombol reset hanya muncul kalau ada filter yang aktif ?>
            <?php if ($keyword !== '' || $status !== '') : ?>
                <a href="<?= site_url('members') ?>" class="dl-btn dl-btn--light dl-btn--sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($keyword !== '' || $status !== '') : ?>
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
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Jenis Kelamin</th>
                    <th>Tgl Daftar</th>
                    <th>Status</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)) : ?>
                    <tr>
                        <td colspan="8" class="dl-empty">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:26px"></i>
                            <?= ($keyword !== '' || $status !== '')
                                ? 'Tidak ada anggota yang cocok dengan pencarian Anda.'
                                : 'Belum ada data anggota. Klik "Tambah Anggota" untuk mulai.' ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $nomor = $nomorAwal; ?>
                    <?php foreach ($members as $member) : ?>
                        <tr>
                            <td><?= $nomor++ ?></td>
                            <td><span class="dl-badge dl-badge--gray"><?= esc($member['kode_anggota']) ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="dl-member__avatar"><?= esc(inisial($member['nama'])) ?></span>
                                    <span class="fw-semibold"><?= esc($member['nama']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php // Tampilkan "-" bila kontak memang dikosongkan ?>
                                <div class="small"><?= esc($member['email'] ?? '-') ?></div>
                                <div class="small text-muted"><?= esc($member['telepon'] ?? '-') ?></div>
                            </td>
                            <td><?= $member['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                            <td class="text-nowrap"><?= esc(tanggal_indo($member['tanggal_daftar'])) ?></td>
                            <td>
                                <span class="dl-badge <?= badge_status($member['status']) ?>">
                                    <?= esc(ucfirst($member['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= site_url('members/' . $member['id'] . '/edit') ?>"
                                       class="dl-btn dl-btn--light dl-btn--sm" title="Ubah data">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <?php /* Hapus memakai POST + csrf_field() supaya tidak bisa
                                             dipicu lewat link/gambar dari situs lain (CSRF).
                                             data-confirm ditangani dashboard.js. */ ?>
                                    <form action="<?= site_url('members/' . $member['id'] . '/delete') ?>"
                                          method="post" class="d-inline"
                                          data-confirm="Hapus anggota &quot;<?= esc($member['nama']) ?>&quot;?">
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
                Menampilkan <?= count($members) ?> dari <?= number_format($totalData, 0, ',', '.') ?> anggota
            </span>
            <?php // Grup pager 'members' + template dari app/Config/Pager.php ?>
            <?= $pager->links('members', 'dashboard_pager') ?>
        </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
