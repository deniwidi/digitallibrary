<?php

/**
 * app_helper
 * ---------------------------------------------------------------------
 * Kumpulan fungsi bantu tampilan yang dipakai di banyak view.
 * Di-autoload lewat app/Config/Autoload.php ($helpers), jadi bisa
 * langsung dipanggil dari view mana pun tanpa helper('app').
 *
 * Setiap fungsi dibungkus function_exists() agar aman bila helper
 * ter-load lebih dari sekali.
 */

if (! function_exists('rupiah')) {
    /**
     * Format angka menjadi mata uang Rupiah.
     *
     * @param  int|float|string|null $angka Nilai nominal, mis. 15000
     * @return string                       Contoh keluaran: "Rp15.000"
     */
    function rupiah($angka): string
    {
        return 'Rp' . number_format((float) $angka, 0, ',', '.');
    }
}

if (! function_exists('angka_ringkas')) {
    /**
     * Persingkat angka besar agar muat di kartu statistik.
     * 14000 => "14K", 1250000 => "1,3M".
     *
     * @param  int|float|string|null $angka
     * @return string
     */
    function angka_ringkas($angka): string
    {
        $n = (float) $angka;

        if ($n >= 1000000) {
            return number_format($n / 1000000, 1, ',', '.') . 'M';
        }

        if ($n >= 1000) {
            return number_format($n / 1000, 0, ',', '.') . 'K';
        }

        return number_format($n, 0, ',', '.');
    }
}

if (! function_exists('tanggal_indo')) {
    /**
     * Ubah tanggal database (Y-m-d / datetime) menjadi format Indonesia.
     *
     * @param  string|null $tanggal Nilai dari kolom DATE/DATETIME
     * @param  bool        $pendek  true => "13 Agu 2026", false => "13 Agustus 2026"
     * @return string               Tanda "-" bila tanggal kosong/tidak valid
     */
    function tanggal_indo(?string $tanggal, bool $pendek = true): string
    {
        if ($tanggal === null || $tanggal === '' || str_starts_with($tanggal, '0000')) {
            return '-';
        }

        $waktu = strtotime($tanggal);
        if ($waktu === false) {
            return '-';
        }

        $bulanPendek = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $bulanPanjang = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $bulan = $pendek ? $bulanPendek : $bulanPanjang;

        return date('j', $waktu) . ' ' . $bulan[(int) date('n', $waktu)] . ' ' . date('Y', $waktu);
    }
}

if (! function_exists('inisial')) {
    /**
     * Ambil inisial dari sebuah nama untuk avatar teks.
     * "Budi Santoso" => "BS".
     *
     * @param  string|null $nama
     * @return string             Maksimal 2 huruf kapital
     */
    function inisial(?string $nama): string
    {
        $nama = trim((string) $nama);
        if ($nama === '') {
            return '?';
        }

        $kata = preg_split('/\s+/', $nama);
        $hasil = mb_strtoupper(mb_substr($kata[0], 0, 1));

        if (count($kata) > 1) {
            $hasil .= mb_strtoupper(mb_substr($kata[count($kata) - 1], 0, 1));
        }

        return $hasil;
    }
}

if (! function_exists('badge_status')) {
    /**
     * Petakan status transaksi ke kelas CSS badge.
     *
     * @param  string|null $status Nilai kolom transactions.status
     * @return string              Nama kelas CSS, mis. "dl-badge--amber"
     */
    function badge_status(?string $status): string
    {
        return match ($status) {
            'dipinjam'     => 'dl-badge--amber',
            'dikembalikan' => 'dl-badge--green',
            'terlambat'    => 'dl-badge--red',
            'hilang'       => 'dl-badge--gray',
            'aktif'        => 'dl-badge--green',
            'nonaktif'     => 'dl-badge--gray',
            'diblokir'     => 'dl-badge--red',
            default        => 'dl-badge--blue',
        };
    }
}

if (! function_exists('bintang')) {
    /**
     * Render rating angka menjadi deretan bintang penuh/kosong.
     *
     * @param  float|string|null $rating Nilai 0.0 - 5.0
     * @return string                    Rangkaian ikon Bootstrap Icons
     */
    function bintang($rating): string
    {
        $nilai = (int) round((float) $rating);          // dibulatkan ke bintang terdekat
        $nilai = max(0, min(5, $nilai));                // jaga tetap di rentang 0-5

        return str_repeat('<i class="bi bi-star-fill"></i>', $nilai)
             . str_repeat('<i class="bi bi-star"></i>', 5 - $nilai);
    }
}

if (! function_exists('menu_aktif')) {
    /**
     * Tentukan apakah sebuah menu sidebar sedang aktif, dengan
     * membandingkan segmen pertama URL saat ini.
     *
     * @param  string $segmen Segmen yang diwakili menu, mis. "books"
     * @return string         "active" bila cocok, string kosong bila tidak
     */
    function menu_aktif(string $segmen): string
    {
        // uri_string() mengembalikan path relatif terhadap baseURL,
        // mis. "books/12/edit" -> segmen pertama "books".
        $sekarang = explode('/', trim(uri_string(), '/'))[0] ?? '';

        return $sekarang === $segmen ? 'active' : '';
    }
}
