<?php

/**
 * HALAMAN LAPORAN SIAP CETAK
 * ---------------------------------------------------------------------
 * Halaman ini sengaja TIDAK memakai layout dasbor: saat dicetak, sidebar
 * dan topbar hanya membuang tinta. Seluruh gayanya ditulis inline agar
 * berkas ini tetap rapi walau dibuka tanpa koneksi internet (tidak
 * bergantung pada CDN).
 *
 * @var array  $items    Seluruh baris laporan (tanpa paginasi)
 * @var array  $filter   Filter yang sedang aktif
 * @var array  $rekap    Angka rekap
 * @var string $appName  Nama aplikasi dari tabel settings
 * @var string $petugas  Nama petugas yang mencetak
 * @var int    $maksimal Batas baris yang boleh dicetak sekali jalan
 */

$judulJenis = $filter['jenis'] === 'pengembalian' ? 'Pengembalian' : 'Peminjaman';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan <?= esc($judulJenis) ?> <?= esc($filter['dari']) ?> s/d <?= esc($filter['sampai']) ?></title>
    <style>
        /* ---------- Gaya layar ---------- */
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #1E293B;
            margin: 0;
            padding: 24px;
            background: #F3F5F9;
        }
        .lembar {
            max-width: 1000px;
            margin: 0 auto;
            background: #FFFFFF;
            padding: 28px 30px;
            box-shadow: 0 4px 18px rgba(16, 28, 52, .10);
        }

        .kop {
            border-bottom: 2px solid #101C34;
            padding-bottom: 12px;
            margin-bottom: 16px;
            text-align: center;
        }
        .kop h1 { margin: 0 0 2px; font-size: 18px; letter-spacing: 1px; }
        .kop h2 { margin: 0 0 4px; font-size: 14px; font-weight: 600; }
        .kop p  { margin: 0; font-size: 11.5px; color: #64748B; }

        .rekap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }
        .rekap div {
            flex: 1 1 130px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 8px 10px;
        }
        .rekap span { display: block; font-size: 10.5px; color: #64748B; }
        .rekap strong { font-size: 15px; }

        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid #CBD5E1;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #EFF3F9; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; }
        td { font-size: 11.5px; }
        tfoot td { font-weight: 700; background: #F8FAFC; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }

        .ttd { margin-top: 28px; display: flex; justify-content: flex-end; }
        .ttd div { width: 220px; text-align: center; font-size: 11.5px; }
        .ttd .garis { margin-top: 54px; border-top: 1px solid #1E293B; padding-top: 4px; }

        .catatan { margin-top: 14px; font-size: 11px; color: #64748B; }

        .aksi { max-width: 1000px; margin: 0 auto 14px; display: flex; gap: 8px; }
        .aksi button, .aksi a {
            font: inherit;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #CBD5E1;
            background: #FFFFFF;
            color: #1E293B;
            cursor: pointer;
            text-decoration: none;
        }
        .aksi button { background: #2563EB; border-color: #2563EB; color: #FFFFFF; }

        /* ---------- Gaya khusus saat dicetak ---------- */
        @media print {
            body { background: #FFFFFF; padding: 0; font-size: 11px; }
            .lembar { box-shadow: none; padding: 0; max-width: none; }
            .aksi { display: none; }          /* tombol tidak ikut tercetak */
            thead { display: table-header-group; }  /* judul kolom berulang tiap halaman */
            tr { page-break-inside: avoid; }
        }
        @page { size: A4 landscape; margin: 12mm; }
    </style>
</head>
<body>

    <!-- Tombol bantu; otomatis disembunyikan saat mencetak -->
    <div class="aksi">
        <button type="button" onclick="window.print()">Cetak Sekarang</button>
        <a href="<?= site_url('reports') ?>">Kembali ke Laporan</a>
    </div>

    <div class="lembar">

        <div class="kop">
            <h1><?= esc($appName) ?></h1>
            <h2>Laporan <?= esc($judulJenis) ?></h2>
            <p>
                Periode <?= esc(tanggal_indo($filter['dari'], false)) ?>
                s/d <?= esc(tanggal_indo($filter['sampai'], false)) ?>
                <?= $filter['status'] !== '' ? ' &middot; Status: ' . esc(ucfirst($filter['status'])) : '' ?>
                <?= $filter['keyword'] !== '' ? ' &middot; Pencarian: "' . esc($filter['keyword']) . '"' : '' ?>
            </p>
        </div>

        <div class="rekap">
            <div><span>Total Transaksi</span><strong><?= number_format($rekap['total'], 0, ',', '.') ?></strong></div>
            <div><span>Belum Kembali</span><strong><?= number_format($rekap['dipinjam'], 0, ',', '.') ?></strong></div>
            <div><span>Tepat Waktu</span><strong><?= number_format($rekap['dikembalikan'], 0, ',', '.') ?></strong></div>
            <div><span>Terlambat</span><strong><?= number_format($rekap['terlambat'], 0, ',', '.') ?></strong></div>
            <div><span>Hilang</span><strong><?= number_format($rekap['hilang'], 0, ',', '.') ?></strong></div>
            <div><span>Total Denda</span><strong><?= esc(rupiah($rekap['denda'])) ?></strong></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:34px">No</th>
                    <th>Kode Transaksi</th>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Kembali</th>
                    <th>Status</th>
                    <th class="kanan">Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="9" class="tengah" style="padding:18px">
                            Tidak ada transaksi pada rentang dan filter tersebut.
                        </td>
                    </tr>
                <?php else : ?>
                    <?php $nomor = 1; ?>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td class="tengah"><?= $nomor++ ?></td>
                            <td><?= esc($item['kode_transaksi']) ?></td>
                            <td>
                                <?= esc($item['nama_anggota']) ?><br>
                                <small><?= esc($item['kode_anggota']) ?></small>
                            </td>
                            <td>
                                <?= esc($item['judul']) ?><br>
                                <small><?= esc($item['kode_buku']) ?></small>
                            </td>
                            <td><?= esc(tanggal_indo($item['tanggal_pinjam'])) ?></td>
                            <td><?= esc(tanggal_indo($item['tanggal_jatuh_tempo'])) ?></td>
                            <td><?= esc(tanggal_indo($item['tanggal_kembali'])) ?></td>
                            <td><?= $item['status'] === 'dikembalikan' ? 'Tepat Waktu' : esc(ucfirst($item['status'])) ?></td>
                            <td class="kanan"><?= (int) $item['denda'] > 0 ? esc(rupiah($item['denda'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (! empty($items)) : ?>
                <tfoot>
                    <tr>
                        <td colspan="8" class="kanan">Total Denda</td>
                        <td class="kanan"><?= esc(rupiah($rekap['denda'])) ?></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>

        <?php // Peringatan bila jumlah baris menyentuh batas cetak ?>
        <?php if (count($items) >= $maksimal) : ?>
            <p class="catatan">
                <strong>Catatan:</strong> laporan dipotong pada <?= number_format($maksimal, 0, ',', '.') ?>
                baris pertama. Persempit rentang tanggalnya bila ingin data yang lebih lengkap.
            </p>
        <?php endif; ?>

        <div class="ttd">
            <div>
                Dicetak pada <?= esc(tanggal_indo(date('Y-m-d'), false)) ?><br>
                Petugas,
                <div class="garis"><?= esc($petugas) ?></div>
            </div>
        </div>

    </div>

<script>
    /**
     * Dialog cetak dibuka otomatis begitu halaman selesai dimuat, supaya
     * petugas tidak perlu menekan Ctrl+P. Bila dibatalkan, halamannya
     * tetap bisa dibaca dan tombol "Cetak Sekarang" masih tersedia.
     */
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>
