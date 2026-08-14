<?= $this->extend('layouts/dashboard') ?>

<?php

/**
 * HALAMAN PROSES PENGEMBALIAN
 * ---------------------------------------------------------------------
 * Form konfirmasi sebelum buku dinyatakan kembali. Menampilkan rincian
 * perhitungan denda, dan setelah disimpan controller akan:
 *   - mengisi tanggal_kembali, denda, dan status transaksi;
 *   - menambah kembali `books.stok_tersedia` (atau mengurangi `stok`
 *     bila buku dinyatakan hilang).
 *
 * @var array $loan    Data transaksi lengkap (hasil JOIN)
 * @var int   $tarif   Tarif denda per hari dari tabel settings
 * @var string $hariIni Tanggal hari ini (Y-m-d)
 * @var array $hitung  ['hari' => int, 'denda' => int] bila dikembalikan hari ini
 */

$errors  = session()->getFlashdata('errors') ?? [];
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>

<?= $this->section('pageActions') ?>
    <a href="<?= site_url('returns') ?>" class="dl-btn dl-btn--light">
        <i class="bi bi-arrow-left"></i> Kembali ke Antrean
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row g-3">

    <!-- =================== KOLOM KIRI: FORM ==================== -->
    <div class="col-12 col-lg-7">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Konfirmasi Pengembalian</h2>
                <span class="dl-badge dl-badge--gray"><?= esc($loan['kode_transaksi']) ?></span>
            </div>

            <div class="dl-card__body">

                <!-- Ringkasan buku & peminjam (tidak bisa diubah di sini) -->
                <div class="d-flex gap-3 p-3 border rounded mb-3">
                    <?php if (! empty($loan['sampul'])) : ?>
                        <img class="dl-book__cover" src="<?= base_url('uploads/covers/' . $loan['sampul']) ?>"
                             alt="Sampul <?= esc($loan['judul']) ?>">
                    <?php else : ?>
                        <div class="dl-book__cover"><i class="bi bi-book"></i></div>
                    <?php endif; ?>
                    <div>
                        <div class="fw-semibold"><?= esc($loan['judul']) ?></div>
                        <div class="small text-muted"><?= esc($loan['penulis']) ?></div>
                        <div class="small text-muted mt-1">
                            Dipinjam <strong><?= esc($loan['nama_anggota']) ?></strong>
                            (<?= esc($loan['kode_anggota']) ?>)
                        </div>
                    </div>
                </div>

                <form action="<?= site_url('returns/' . $loan['id'] . '/process') ?>" method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="row g-3">

                        <!-- Tanggal kembali -->
                        <div class="col-12 col-md-6">
                            <label for="tanggal_kembali" class="form-label small fw-semibold">
                                Tanggal Kembali <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="tanggal_kembali" name="tanggal_kembali"
                                   class="form-control<?= $invalid('tanggal_kembali') ?>"
                                   value="<?= esc(old('tanggal_kembali', $hariIni)) ?>"
                                   min="<?= esc($loan['tanggal_pinjam']) ?>" required
                                   <?php // Dipakai skrip di bawah untuk menghitung ulang denda ?>
                                   data-jatuh-tempo="<?= esc($loan['tanggal_jatuh_tempo']) ?>"
                                   data-tarif="<?= (int) $tarif ?>">
                            <?php if (isset($errors['tanggal_kembali'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['tanggal_kembali']) ?></div>
                            <?php else : ?>
                                <div class="form-text">Bawaan hari ini; ubah bila buku diterima di tanggal lain.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Denda -->
                        <div class="col-12 col-md-6">
                            <label for="denda" class="form-label small fw-semibold">Denda (Rp)</label>
                            <input type="number" id="denda" name="denda" min="0" step="500"
                                   class="form-control<?= $invalid('denda') ?>"
                                   value="<?= esc(old('denda', (string) $hitung['denda'])) ?>">
                            <?php if (isset($errors['denda'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['denda']) ?></div>
                            <?php else : ?>
                                <div class="form-text">
                                    Terisi otomatis dari hitungan keterlambatan. Boleh diubah bila ada keringanan.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Kondisi buku -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kondisi Buku</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="kondisi"
                                           id="kondisi_baik" value="baik"
                                           <?= old('kondisi', 'baik') === 'baik' ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="kondisi_baik">
                                        <strong>Baik</strong> - buku kembali ke rak (stok tersedia bertambah)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="kondisi"
                                           id="kondisi_hilang" value="hilang"
                                           <?= old('kondisi') === 'hilang' ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="kondisi_hilang">
                                        <strong>Hilang</strong> - eksemplar dikeluarkan dari koleksi
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan -->
                        <div class="col-12">
                            <label for="catatan" class="form-label small fw-semibold">Catatan</label>
                            <textarea id="catatan" name="catatan" rows="2" maxlength="255"
                                      class="form-control<?= $invalid('catatan') ?>"
                                      placeholder="Mis. kondisi fisik buku saat diterima"><?= esc(old('catatan', $loan['catatan'] ?? '')) ?></textarea>
                            <?php if (isset($errors['catatan'])) : ?>
                                <div class="invalid-feedback"><?= esc($errors['catatan']) ?></div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="dl-btn dl-btn--success">
                            <i class="bi bi-check-lg"></i> Proses Pengembalian
                        </button>
                        <a href="<?= site_url('returns') ?>" class="dl-btn dl-btn--light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================ KOLOM KANAN: RINCIAN DENDA ================ -->
    <div class="col-12 col-lg-5">
        <div class="dl-card">
            <div class="dl-card__head">
                <h2 class="dl-card__title">Rincian Perhitungan</h2>
            </div>
            <div class="dl-card__body">

                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Tanggal Pinjam</span>
                    <span class="fw-semibold"><?= esc(tanggal_indo($loan['tanggal_pinjam'])) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Jatuh Tempo</span>
                    <span class="fw-semibold"><?= esc(tanggal_indo($loan['tanggal_jatuh_tempo'])) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Tarif Denda</span>
                    <span class="fw-semibold"><?= esc(rupiah($tarif)) ?> / hari</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Keterlambatan</span>
                    <?php // Kedua nilai di bawah diperbarui skrip saat tanggal diubah ?>
                    <span class="fw-semibold" id="dlHariTelat"><?= (int) $hitung['hari'] ?> hari</span>
                </div>
                <div class="d-flex justify-content-between py-3">
                    <span class="fw-semibold">Total Denda</span>
                    <span class="fw-bold <?= $hitung['denda'] > 0 ? 'text-danger' : 'text-success' ?>"
                          id="dlTotalDenda" style="font-size:18px"><?= esc(rupiah($hitung['denda'])) ?></span>
                </div>

                <div class="dl-alert dl-alert--success mb-0">
                    <i class="bi bi-info-circle-fill"></i>
                    <div class="small">
                        Denda dihitung <strong>hari keterlambatan &times; tarif</strong>.
                        Buku yang dikembalikan tepat waktu atau lebih awal tidak dikenai denda.
                        Tarif bisa diubah di menu Pengaturan.
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    /**
     * Hitung ulang keterlambatan & denda saat petugas mengubah tanggal
     * kembali, supaya angka di panel kanan dan kolom denda ikut menyesuaikan
     * tanpa perlu reload. Perhitungan final tetap dilakukan ulang di server
     * (BookReturn::process) - skrip ini hanya membantu tampilan.
     */
    (function () {
        const input  = document.getElementById('tanggal_kembali');
        const denda  = document.getElementById('denda');
        const elHari = document.getElementById('dlHariTelat');
        const elTotal = document.getElementById('dlTotalDenda');
        if (!input || !denda) return;

        const tempo = input.dataset.jatuhTempo;
        const tarif = parseInt(input.dataset.tarif, 10) || 0;

        function rupiah(n) {
            return 'Rp' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        input.addEventListener('change', function () {
            if (!input.value) return;

            const selisih = (new Date(input.value) - new Date(tempo)) / 86400000;
            const hari    = Math.max(0, Math.floor(selisih));
            const total   = hari * tarif;

            elHari.textContent  = hari + ' hari';
            elTotal.textContent = rupiah(total);
            elTotal.className   = 'fw-bold ' + (total > 0 ? 'text-danger' : 'text-success');
            denda.value         = total;
        });
    })();
</script>
<?= $this->endSection() ?>
