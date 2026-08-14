<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN DAFTAR PEMINJAMAN
 * ---------------------------------------------------------------------
 * Berisi: kartu ringkasan, pencarian + filter status, tabel transaksi
 * beserta sisa hari menuju jatuh tempo, tombol aksi, dan paginasi.
 *
 * Variabel dari Loan::index():
 * @var array  $loans     Baris transaksi (sudah di-JOIN anggota, buku, petugas)
 * @var object $pager     Objek pager CodeIgniter
 * @var string $keyword   Kata kunci pencarian yang sedang aktif
 * @var string $status    Filter status yang sedang aktif
 * @var int    $nomorAwal Nomor urut baris pertama di halaman ini
 * @var int    $totalData Total baris hasil pencarian
 * @var array  $ringkasan Angka ringkasan transaksi
 */
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('loans/create') ?>" class="dl-btn dl-btn--primary">
        <i class="bi bi-plus-lg"></i> Peminjaman Baru
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ====================== KARTU RINGKASAN ======================== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--blue"><i class="bi bi-journal-arrow-up"></i></div>
            <div>
                <p class="dl-stat__label">Sedang Dipinjam</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['aktif'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--red"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <p class="dl-stat__label">Terlambat</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['terlambat'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--amber"><i class="bi bi-calendar-check"></i></div>
            <div>
                <p class="dl-stat__label">Pinjam Hari Ini</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['hari_ini'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dl-stat">
            <div class="dl-stat__icon dl-stat__icon--green"><i class="bi bi-check2-circle"></i></div>
            <div>
                <p class="dl-stat__label">Sudah Kembali</p>
                <p class="dl-stat__value"><?= number_format($ringkasan['selesai'], 0, ',', '.') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ====================== TABEL TRANSAKSI ========================= -->
<div class="dl-card">

    <div class="dl-card__head flex-wrap">
        <h2 class="dl-card__title">Daftar Peminjaman</h2>

        <?php /* Form pencarian: method GET agar filter tersimpan di URL
                 dan dipakai ulang oleh link paginasi. */ ?>
        <form action="<?= site_url('loans') ?>" method="get" class="d-flex flex-wrap gap-2">
            <div class="dl-search" style="min-width:230px">
                <i class="bi bi-search"></i>
                <input type="search" name="keyword" value="<?= esc($keyword) ?>"
                       placeholder="Cari kode, anggota, atau judul buku...">
            </div>

            <select name="status" class="dl-select">
                <option value="">Semua Status</option>
                <?php
                // Nilai filter ini dihitung dari tanggal di TransactionModel,
                // bukan sekadar membaca kolom `status`.
                $opsiStatus = [
                    'dipinjam'     => 'Sedang Dipinjam',
                    'terlambat'    => 'Terlambat',
                    'dikembalikan' => 'Sudah Kembali',
                ];
                ?>
                <?php foreach ($opsiStatus as $key => $label) : ?>
                    <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="dl-btn dl-btn--primary dl-btn--sm">Cari</button>

            <?php if ($keyword !== '' || $status !== '') : ?>
                <a href="<?= site_url('loans') ?>" class="dl-btn dl-btn--light dl-btn--sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($keyword !== '' || $status !== '') : ?>
        <div class="px-3 pb-2">
            <span class="dl-badge dl-badge--blue">
                <?= number_format($totalData, 0, ',', '.') ?> transaksi ditemukan
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
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th style="width:150px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($loans)) : ?>
                    <tr>
                        <td colspan="8" class="dl-empty">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:26px"></i>
                            <?= ($keyword !== '' || $status !== '')
                                ? 'Tidak ada transaksi yang cocok dengan pencarian Anda.'
                                : 'Belum ada transaksi. Klik "Peminjaman Baru" untuk mulai.' ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $nomor = $nomorAwal; ?>
                    <?php foreach ($loans as $loan) : ?>
                        <?php
                        // Nilai-nilai turunan sudah dihitung di controller
                        $statusTampil = $loan['status_tampil'];
                        $sisaHari     = (int) $loan['sisa_hari'];
                        $masihPinjam  = empty($loan['tanggal_kembali']);
                        ?>
                        <tr>
                            <td><?= $nomor++ ?></td>

                            <td><span class="dl-badge dl-badge--gray"><?= esc($loan['kode_transaksi']) ?></span></td>

                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="dl-member__avatar"><?= esc(inisial($loan['nama_anggota'])) ?></span>
                                    <div>
                                        <div class="fw-semibold"><?= esc($loan['nama_anggota']) ?></div>
                                        <div class="small text-muted"><?= esc($loan['kode_anggota']) ?></div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold text-truncate" style="max-width:200px"
                                     title="<?= esc($loan['judul']) ?>"><?= esc($loan['judul']) ?></div>
                                <div class="small text-muted"><?= esc($loan['kode_buku']) ?></div>
                            </td>

                            <td class="text-nowrap"><?= esc(tanggal_indo($loan['tanggal_pinjam'])) ?></td>

                            <td class="text-nowrap">
                                <?= esc(tanggal_indo($loan['tanggal_jatuh_tempo'])) ?>
                                <?php if ($masihPinjam) : ?>
                                    <div class="small <?= $sisaHari < 0 ? 'text-danger' : 'text-muted' ?>">
                                        <?php // Sisa hari membantu petugas menakar prioritas penagihan ?>
                                        <?= $sisaHari < 0
                                            ? 'telat ' . abs($sisaHari) . ' hari'
                                            : ($sisaHari === 0 ? 'jatuh tempo hari ini' : 'sisa ' . $sisaHari . ' hari') ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="dl-badge <?= badge_status($statusTampil) ?>">
                                    <?= $statusTampil === 'dikembalikan' ? 'Sudah Kembali' : ucfirst($statusTampil) ?>
                                </span>
                                <?php if (! $masihPinjam && (int) $loan['denda'] > 0) : ?>
                                    <div class="small text-danger mt-1">Denda <?= esc(rupiah($loan['denda'])) ?></div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= site_url('loans/' . $loan['id']) ?>"
                                       class="dl-btn dl-btn--light dl-btn--sm" title="Lihat detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if ($masihPinjam) : ?>
                                        <a href="<?= site_url('loans/' . $loan['id'] . '/edit') ?>"
                                           class="dl-btn dl-btn--light dl-btn--sm" title="Ubah jatuh tempo">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <?php // Perpanjangan cepat: POST agar ikut proteksi CSRF ?>
                                        <form action="<?= site_url('loans/' . $loan['id'] . '/extend') ?>"
                                              method="post" class="d-inline"
                                              data-confirm="Perpanjang jatuh tempo transaksi <?= esc($loan['kode_transaksi']) ?>?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="dl-btn dl-btn--success dl-btn--sm"
                                                    title="Perpanjang jatuh tempo">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php /* Hapus permanen: tabel transactions tidak memakai
                                             soft delete, jadi konfirmasinya dibuat tegas. */ ?>
                                    <form action="<?= site_url('loans/' . $loan['id'] . '/delete') ?>"
                                          method="post" class="d-inline"
                                          data-confirm="Hapus permanen transaksi <?= esc($loan['kode_transaksi']) ?>?<?= $masihPinjam ? ' Stok buku akan dikembalikan.' : '' ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dl-btn dl-btn--danger dl-btn--sm" title="Hapus transaksi">
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
                Menampilkan <?= count($loans) ?> dari <?= number_format($totalData, 0, ',', '.') ?> transaksi
            </span>
            <?= $pager->links('loans', 'dashboard_pager') ?>
        </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
