<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN PEMINJAMAN BARU
 * ---------------------------------------------------------------------
 * Form dikirim POST ke /loans/store. Setelah tersimpan, controller
 * otomatis mengurangi `books.stok_tersedia` sebanyak 1.
 *
 * Dropdown Anggota & Buku memakai Select2 dengan sumber data AJAX, jadi
 * daftar isinya TIDAK ikut dirender ke HTML - halaman tetap ringan
 * walaupun data anggota/buku sudah ribuan. Datanya diambil sedikit demi
 * sedikit lewat endpoint /loans/search-members dan /loans/search-books.
 *
 * @var string     $kodeBaru       Kode transaksi otomatis (TRX-YYYYMMDD-NNNN)
 * @var bool       $adaAnggota     Apakah ada anggota berstatus aktif
 * @var bool       $adaBuku        Apakah ada buku bersisa stok
 * @var array|null $memberTerpilih Opsi anggota yang tadi dipilih (setelah gagal validasi)
 * @var array|null $bookTerpilih   Opsi buku yang tadi dipilih
 * @var int        $lamaPinjam     Lama pinjam bawaan (hari) dari tabel settings
 * @var int        $maxPinjam      Batas buku per anggota dari tabel settings
 * @var string     $tanggalPinjam  Tanggal hari ini
 * @var string     $jatuhTempo     Tanggal jatuh tempo bawaan
 */

// Pesan error validasi dari controller
$errors = session()->getFlashdata('errors') ?? [];

/** Kelas Bootstrap untuk menandai input yang gagal validasi. */
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>

<?= $this->section('styles') ?>
    <?php /* Select2 + tema Bootstrap 5 agar tampilannya menyatu dengan
             form lain. Hanya dimuat di halaman ini, bukan di semua
             halaman dasbor. */ ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        /* Samakan tinggi & radius kotak Select2 dengan .form-control milik Bootstrap */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            border-radius: .375rem;
            border-color: #dee2e6;
        }
        /* Tandai merah bila field-nya gagal validasi */
        .is-invalid + .select2-container--bootstrap-5 .select2-selection {
            border-color: var(--dl-red);
        }
        /* Tampilan dua baris pada tiap opsi di dalam dropdown */
        .dl-s2-title { font-size: 13px; font-weight: 600; color: var(--dl-text); }
        .dl-s2-sub   { font-size: 11.5px; color: var(--dl-text-muted); margin-top: 1px; }
        .dl-s2-stok  { font-size: 11px; font-weight: 600; color: #15803D; }
    </style>
<?= $this->endSection() ?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('loans') ?>" class="dl-btn dl-btn--light">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Formulir Peminjaman</h2>
                <span class="dl-badge dl-badge--gray"><?= esc($kodeBaru) ?></span>
            </div>

            <div class="dl-card__body">

                <?php if (! $adaAnggota || ! $adaBuku) : ?>
                    <?php // Cegah petugas mengisi form yang pasti gagal ?>
                    <div class="dl-alert dl-alert--danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <?= ! $adaAnggota
                                ? 'Belum ada anggota berstatus aktif. Tambahkan anggota terlebih dahulu.'
                                : 'Tidak ada buku dengan stok tersedia saat ini.' ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('loans/store') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="row g-3">

                        <!-- Kode transaksi -->
                        <div class="col-12 col-md-6">
                            <label for="kode_transaksi" class="form-label small fw-semibold">
                                Kode Transaksi <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="kode_transaksi" name="kode_transaksi"
                                   class="form-control<?= $invalid('kode_transaksi') ?>"
                                   value="<?= esc(old('kode_transaksi', $kodeBaru)) ?>"
                                   maxlength="25" required>
                            <?php if (isset($errors['kode_transaksi'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['kode_transaksi']) ?></div>
                            <?php else : ?>
                                <div class="form-text">Terisi otomatis berdasarkan tanggal hari ini.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Petugas (hanya info) -->
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Petugas</label>
                            <input type="text" class="form-control" value="<?= esc(session()->get('nama')) ?>" disabled>
                            <div class="form-text">Diambil otomatis dari akun yang sedang login.</div>
                        </div>

                        <!-- Anggota (Select2 AJAX) -->
                        <div class="col-12">
                            <label for="member_id" class="form-label small fw-semibold">
                                Anggota Peminjam <span class="text-danger">*</span>
                            </label>
                            <?php /* Isi <option> sengaja dikosongkan: daftarnya diambil
                                     lewat AJAX. Satu-satunya <option> yang dirender adalah
                                     pilihan sebelumnya (bila form gagal validasi), karena
                                     Select2 hanya bisa menampilkan nilai yang ada di DOM. */ ?>
                            <select id="member_id" name="member_id"
                                    class="form-select<?= $invalid('member_id') ?>" required>
                                <option value=""></option>
                                <?php if ($memberTerpilih !== null) : ?>
                                    <option value="<?= (int) $memberTerpilih['id'] ?>" selected>
                                        <?= esc($memberTerpilih['text']) ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <?php if (isset($errors['member_id'])) : ?>
                                <div class="invalid-feedback d-block"><?= esc($errors['member_id']) ?></div>
                            <?php else : ?>
                                <div class="form-text">
                                    Ketik nama (boleh nama tengah/belakang), kode anggota, email, atau nomor telepon.
                                    Hanya anggota aktif yang muncul. Maksimal <?= (int) $maxPinjam ?> buku per anggota.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Buku (Select2 AJAX) -->
                        <div class="col-12">
                            <label for="book_id" class="form-label small fw-semibold">
                                Buku yang Dipinjam <span class="text-danger">*</span>
                            </label>
                            <select id="book_id" name="book_id"
                                    class="form-select<?= $invalid('book_id') ?>" required>
                                <option value=""></option>
                                <?php if ($bookTerpilih !== null) : ?>
                                    <option value="<?= (int) $bookTerpilih['id'] ?>" selected>
                                        <?= esc($bookTerpilih['text']) ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <?php if (isset($errors['book_id'])) : ?>
                                <div class="invalid-feedback d-block"><?= esc($errors['book_id']) ?></div>
                            <?php else : ?>
                                <div class="form-text">
                                    Ketik judul, penulis, kode buku, atau ISBN. Hanya buku dengan stok tersedia yang muncul.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tanggal pinjam -->
                        <div class="col-12 col-md-6">
                            <label for="tanggal_pinjam" class="form-label small fw-semibold">
                                Tanggal Pinjam <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="tanggal_pinjam" name="tanggal_pinjam"
                                   class="form-control<?= $invalid('tanggal_pinjam') ?>"
                                   value="<?= esc(old('tanggal_pinjam', $tanggalPinjam)) ?>" required>
                            <?php if (isset($errors['tanggal_pinjam'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['tanggal_pinjam']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Jatuh tempo -->
                        <div class="col-12 col-md-6">
                            <label for="tanggal_jatuh_tempo" class="form-label small fw-semibold">
                                Jatuh Tempo <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="tanggal_jatuh_tempo" name="tanggal_jatuh_tempo"
                                   class="form-control<?= $invalid('tanggal_jatuh_tempo') ?>"
                                   value="<?= esc(old('tanggal_jatuh_tempo', $jatuhTempo)) ?>" required>
                            <?php if (isset($errors['tanggal_jatuh_tempo'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['tanggal_jatuh_tempo']) ?></div>
                            <?php else : ?>
                                <div class="form-text">Bawaan <?= (int) $lamaPinjam ?> hari dari tanggal pinjam.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Catatan -->
                        <div class="col-12">
                            <label for="catatan" class="form-label small fw-semibold">Catatan</label>
                            <textarea id="catatan" name="catatan" rows="2" maxlength="255"
                                      class="form-control<?= $invalid('catatan') ?>"
                                      placeholder="Catatan tambahan, mis. kondisi buku saat dipinjam"><?= esc(old('catatan')) ?></textarea>
                            <?php if (isset($errors['catatan'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['catatan']) ?></div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--primary">
                            <i class="bi bi-check-lg"></i> Simpan Peminjaman
                        </button>
                        <a href="<?= site_url('loans') ?>" class="dl-btn dl-btn--light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Panel bantuan: aturan yang diberlakukan controller -->
    <div class="col-12 col-xl-4">
        <div class="dl-card h-100">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Aturan Peminjaman</h2>
            </div>
            <div class="dl-card__body">
                <ol class="small text-muted ps-3 mb-0" style="line-height:1.9">
                    <li>Anggota harus berstatus <strong>aktif</strong>.</li>
                    <li>Stok buku yang tersedia harus lebih dari 0.</li>
                    <li>Satu anggota tidak boleh memegang judul yang sama dua kali.</li>
                    <li>Maksimal <strong><?= (int) $maxPinjam ?> buku</strong> per anggota.</li>
                    <li>Lama pinjam bawaan <strong><?= (int) $lamaPinjam ?> hari</strong>.</li>
                </ol>
                <hr>
                <p class="small text-muted mb-0">
                    Angka batas di atas diambil dari tabel <code>settings</code> dan bisa diubah
                    lewat menu Pengaturan tanpa mengubah kode.
                </p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <?php /* jQuery wajib dimuat SEBELUM Select2 karena Select2 adalah plugin jQuery. */ ?>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
jQuery(function ($) {
    'use strict';

    /* ================================================================
       PESAN BAHASA INDONESIA UNTUK SELECT2
       Dipakai bersama oleh kedua dropdown.
       ================================================================ */
    const bahasa = {
        searching:   function () { return 'Mencari...'; },
        noResults:   function () { return 'Data tidak ditemukan'; },
        errorLoading: function () { return 'Gagal memuat data'; },
        loadingMore: function () { return 'Memuat data lainnya...'; },
        inputTooShort: function () { return 'Ketik kata kunci untuk mencari'; }
    };

    /**
     * Susunan parameter yang dikirim ke server pada tiap ketikan.
     * params.term = teks yang diketik user, params.page = halaman
     * berikutnya saat dropdown di-scroll sampai bawah.
     */
    function dataAjax(params) {
        return { q: params.term || '', page: params.page || 1 };
    }

    /**
     * Terjemahkan balasan server ke bentuk yang dimengerti Select2.
     * Server sudah mengirim {results, pagination:{more}}, jadi tinggal
     * diteruskan apa adanya.
     */
    function olahHasil(data, params) {
        params.page = params.page || 1;
        return {
            results: data.results,
            pagination: { more: data.pagination.more }
        };
    }

    /* ================================================================
       DROPDOWN ANGGOTA
       ================================================================ */
    $('#member_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Cari anggota: nama, kode, email, atau telepon...',
        allowClear: true,
        language: bahasa,
        // 0 = dropdown langsung menampilkan 15 anggota pertama saat dibuka
        minimumInputLength: 0,
        ajax: {
            url: '<?= site_url('loans/search-members') ?>',
            dataType: 'json',
            // Tunggu 250 ms setelah user berhenti mengetik supaya tidak
            // membanjiri server dengan request per huruf.
            delay: 250,
            data: dataAjax,
            processResults: olahHasil,
            cache: true
        },
        // Tampilan tiap baris di dalam dropdown (dua baris)
        templateResult: function (item) {
            if (item.loading) { return item.text; }

            // Dibangun dengan .text() (bukan HTML mentah) supaya data dari
            // database tidak bisa menyisipkan skrip -> aman dari XSS.
            const $baris = $('<div></div>');
            $baris.append($('<div class="dl-s2-title"></div>').text(item.nama || item.text));
            $baris.append($('<div class="dl-s2-sub"></div>').text(
                (item.kode || '') + (item.kontak && item.kontak !== '-' ? ' • ' + item.kontak : '')
            ));
            return $baris;
        },
        // Teks yang tampil di kotak setelah dipilih
        templateSelection: function (item) { return item.text || item.id; }
    });

    /* ================================================================
       DROPDOWN BUKU
       ================================================================ */
    $('#book_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Cari buku: judul, penulis, kode, atau ISBN...',
        allowClear: true,
        language: bahasa,
        minimumInputLength: 0,
        ajax: {
            url: '<?= site_url('loans/search-books') ?>',
            dataType: 'json',
            delay: 250,
            data: dataAjax,
            processResults: olahHasil,
            cache: true
        },
        templateResult: function (item) {
            if (item.loading) { return item.text; }

            const $baris = $('<div></div>');
            $baris.append($('<div class="dl-s2-title"></div>').text(item.judul || item.text));
            $baris.append($('<div class="dl-s2-sub"></div>').text(
                (item.kode || '') +
                (item.penulis ? ' • ' + item.penulis : '') +
                (item.kategori && item.kategori !== '-' ? ' • ' + item.kategori : '')
            ));
            if (typeof item.tersedia !== 'undefined') {
                $baris.append($('<div class="dl-s2-stok"></div>').text(item.tersedia + ' eksemplar tersedia'));
            }
            return $baris;
        },
        templateSelection: function (item) { return item.text || item.id; }
    });

    /* ================================================================
       JATUH TEMPO OTOMATIS
       Tanggal pinjam + lama pinjam bawaan (dari tabel settings).
       Begitu petugas mengubah jatuh tempo sendiri, sinkronisasi berhenti.
       ================================================================ */
    const $pinjam = $('#tanggal_pinjam');
    const $tempo  = $('#tanggal_jatuh_tempo');
    const lama    = <?= (int) $lamaPinjam ?>;
    let manual    = false;

    $tempo.on('change', function () { manual = true; });

    $pinjam.on('change', function () {
        if (manual || !$pinjam.val()) { return; }
        const d = new Date($pinjam.val() + 'T00:00:00');
        d.setDate(d.getDate() + lama);
        $tempo.val(d.toISOString().slice(0, 10));
    });
});
</script>
<?= $this->endSection() ?>
