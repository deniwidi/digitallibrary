-- =====================================================================
-- DATABASE : digilibrary_db  (SKEMA v2 - LENGKAP)
-- Aplikasi : Sistem Manajemen Perpustakaan Buku Digital
-- Stack    : CodeIgniter 4 + MySQL / MariaDB (InnoDB, utf8mb4)
--
-- Cara pakai:
--   1) phpMyAdmin -> tab Import -> pilih file ini -> Go
--   2) atau lewat CLI:  mysql -u root -p < database/digilibrary_db.sql
--
-- CATATAN PENTING:
--   Jalankan SALAH SATU saja: file SQL ini ATAU migration CI4
--   (`php spark migrate`), jangan keduanya, supaya data tidak dobel.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `digilibrary_db`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `digilibrary_db`;

-- Matikan pengecekan FK sementara supaya urutan DROP tidak error
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `members`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- TABEL 1: users
-- Akun yang boleh masuk ke panel admin. Kolom `password` menyimpan HASH
-- bcrypt hasil password_hash(), BUKAN password asli.
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama`       VARCHAR(100) NOT NULL                COMMENT 'Nama lengkap, tampil di sidebar & topbar',
    `username`   VARCHAR(50)  NOT NULL                COMMENT 'Identitas saat login (unik)',
    `email`      VARCHAR(100) NULL DEFAULT NULL       COMMENT 'Dipakai di halaman Pengaturan profil',
    `password`   VARCHAR(255) NOT NULL                COMMENT 'Hash bcrypt dari password_hash()',
    `role`       ENUM('admin','petugas') NOT NULL DEFAULT 'petugas' COMMENT 'Hak akses; admin = akses penuh',
    `foto`       VARCHAR(255) NULL DEFAULT NULL       COMMENT 'Nama file avatar di public/uploads/avatars',
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1      COMMENT '1 = boleh login, 0 = dinonaktifkan',
    `created_at` DATETIME     NULL DEFAULT NULL,
    `updated_at` DATETIME     NULL DEFAULT NULL,
    `deleted_at` DATETIME     NULL DEFAULT NULL       COMMENT 'Soft delete CI4',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- TABEL 2: categories
-- Master kategori buku. Dipakai sebagai induk (parent) dari `books`.
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama`       VARCHAR(60)  NOT NULL                COMMENT 'Nama kategori, mis. Novel / Teknologi',
    `slug`       VARCHAR(80)  NOT NULL                COMMENT 'Versi URL-friendly dari nama',
    `deskripsi`  VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME     NULL DEFAULT NULL,
    `updated_at` DATETIME     NULL DEFAULT NULL,
    `deleted_at` DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- TABEL 3: books
-- Katalog buku. `stok` = jumlah fisik total, `stok_tersedia` = sisa yang
-- boleh dipinjam saat ini (berkurang saat peminjaman, bertambah saat
-- pengembalian). Index pada judul/penulis mempercepat fitur pencarian.
-- ---------------------------------------------------------------------
CREATE TABLE `books` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_buku`     VARCHAR(20)  NOT NULL             COMMENT 'Kode unik buku, mis. BK-0001',
    `judul`         VARCHAR(150) NOT NULL,
    `penulis`       VARCHAR(100) NOT NULL,
    `penerbit`      VARCHAR(100) NOT NULL,
    `tahun_terbit`  YEAR         NOT NULL             COMMENT 'Tipe YEAR: rentang valid 1901-2155',
    `isbn`          VARCHAR(20)  NULL DEFAULT NULL,
    `category_id`   INT UNSIGNED NOT NULL             COMMENT 'FK -> categories.id',
    `stok`          INT UNSIGNED NOT NULL DEFAULT 0   COMMENT 'Total eksemplar dimiliki perpustakaan',
    `stok_tersedia` INT UNSIGNED NOT NULL DEFAULT 0   COMMENT 'Eksemplar yang sedang ada di rak',
    `sampul`        VARCHAR(255) NULL DEFAULT NULL    COMMENT 'Nama file di public/uploads/covers',
    `sinopsis`      TEXT         NULL DEFAULT NULL,
    `rating`        DECIMAL(2,1) NOT NULL DEFAULT 0.0 COMMENT 'Rata-rata rating 0.0 - 5.0 (widget Buku Terpopuler)',
    `created_at`    DATETIME     NULL DEFAULT NULL,
    `updated_at`    DATETIME     NULL DEFAULT NULL,
    `deleted_at`    DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_books_kode` (`kode_buku`),
    KEY `idx_books_judul` (`judul`),
    KEY `idx_books_penulis` (`penulis`),
    KEY `idx_books_category` (`category_id`),
    -- RESTRICT: kategori tidak boleh dihapus selama masih dipakai buku
    CONSTRAINT `fk_books_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- TABEL 4: members
-- Data anggota perpustakaan (peminjam).
-- ---------------------------------------------------------------------
CREATE TABLE `members` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_anggota`   VARCHAR(20)  NOT NULL            COMMENT 'ID Anggota unik, mis. AGT-0001',
    `nama`           VARCHAR(100) NOT NULL,
    `email`          VARCHAR(100) NULL DEFAULT NULL,
    `telepon`        VARCHAR(20)  NULL DEFAULT NULL,
    `alamat`         VARCHAR(255) NULL DEFAULT NULL,
    `jenis_kelamin`  ENUM('L','P') NOT NULL DEFAULT 'L',
    `tanggal_daftar` DATE         NOT NULL            COMMENT 'Dipakai widget "Anggota Baru Terdaftar"',
    `status`         ENUM('aktif','nonaktif','diblokir') NOT NULL DEFAULT 'aktif',
    `foto`           VARCHAR(255) NULL DEFAULT NULL,
    `created_at`     DATETIME     NULL DEFAULT NULL,
    `updated_at`     DATETIME     NULL DEFAULT NULL,
    `deleted_at`     DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_members_kode` (`kode_anggota`),
    UNIQUE KEY `uq_members_email` (`email`),
    KEY `idx_members_nama` (`nama`),
    KEY `idx_members_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- TABEL 5: transactions
-- Satu baris = satu transaksi peminjaman. Kolom `tanggal_kembali` masih
-- NULL berarti buku belum dikembalikan (masih dipinjam / terlambat).
-- Relasi: member (peminjam), book (buku), user (petugas yang melayani).
-- ---------------------------------------------------------------------
CREATE TABLE `transactions` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_transaksi`      VARCHAR(25)  NOT NULL       COMMENT 'Kode unik, mis. TRX-20260813-0001',
    `member_id`           INT UNSIGNED NOT NULL       COMMENT 'FK -> members.id',
    `book_id`             INT UNSIGNED NOT NULL       COMMENT 'FK -> books.id',
    `user_id`             INT UNSIGNED NULL DEFAULT NULL COMMENT 'FK -> users.id (petugas pencatat)',
    `tanggal_pinjam`      DATE         NOT NULL,
    `tanggal_jatuh_tempo` DATE         NOT NULL       COMMENT 'Batas kembali; lewat tanggal ini = denda',
    `tanggal_kembali`     DATE         NULL DEFAULT NULL COMMENT 'NULL = buku masih di tangan anggota',
    `status`              ENUM('dipinjam','dikembalikan','terlambat','hilang') NOT NULL DEFAULT 'dipinjam',
    `denda`               INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nominal rupiah hasil hitung keterlambatan',
    `catatan`             VARCHAR(255) NULL DEFAULT NULL,
    `created_at`          DATETIME     NULL DEFAULT NULL,
    `updated_at`          DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_trx_kode` (`kode_transaksi`),
    KEY `idx_trx_member` (`member_id`),
    KEY `idx_trx_book` (`book_id`),
    KEY `idx_trx_user` (`user_id`),
    KEY `idx_trx_status` (`status`),
    KEY `idx_trx_tgl_pinjam` (`tanggal_pinjam`),
    -- RESTRICT pada member & book: histori transaksi harus tetap utuh
    CONSTRAINT `fk_trx_member`
        FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_trx_book`
        FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    -- SET NULL: kalau akun petugas dihapus, transaksinya tetap tersimpan
    CONSTRAINT `fk_trx_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- TABEL 6 (pendukung): settings
-- Key-value sederhana untuk menu "Pengaturan": nama aplikasi, tarif denda
-- per hari, lama pinjam default. Nilai denda WAJIB ada karena dipakai
-- modul Pengembalian saat menghitung keterlambatan.
-- ---------------------------------------------------------------------
CREATE TABLE `settings` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key_name`   VARCHAR(50)  NOT NULL COMMENT 'Kunci pengaturan, mis. denda_per_hari',
    `value`      VARCHAR(255) NOT NULL,
    `keterangan` VARCHAR(255) NULL DEFAULT NULL,
    `updated_at` DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- DATA AWAL (SEED)
-- =====================================================================

-- --- users -----------------------------------------------------------
-- Kredensial uji coba:  admin / admin123   dan   petugas / petugas123
INSERT INTO `users` (`nama`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
('Administrator',  'admin',   'admin@digilibrary.test',   '$2y$10$Sora1WPE1VQDpQ1xHcgZwu6Hbmz96Fb5e1x9ZZERu8YwvEcNIi4nW', 'admin',   NOW(), NOW()),
('Petugas Perpus', 'petugas', 'petugas@digilibrary.test', '$2y$10$tn8s.gTNx8tudMgwFEtbL.w31mZzAoW3/jTmeD06VRbKoXr613jiS', 'petugas', NOW(), NOW());

-- --- categories ------------------------------------------------------
INSERT INTO `categories` (`id`, `nama`, `slug`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Novel',            'novel',            'Fiksi naratif panjang',                 NOW(), NOW()),
(2, 'Sejarah',          'sejarah',          'Peristiwa dan tokoh masa lalu',         NOW(), NOW()),
(3, 'Teknologi',        'teknologi',        'Komputer, pemrograman, dan rekayasa',   NOW(), NOW()),
(4, 'Self Improvement', 'self-improvement', 'Pengembangan diri dan produktivitas',   NOW(), NOW()),
(5, 'Sains',            'sains',            'Ilmu pengetahuan alam populer',         NOW(), NOW()),
(6, 'Bisnis',           'bisnis',           'Manajemen, keuangan, kewirausahaan',    NOW(), NOW()),
(7, 'Anak',             'anak',             'Bacaan untuk pembaca usia dini',        NOW(), NOW()),
(8, 'Biografi',         'biografi',         'Kisah hidup tokoh',                     NOW(), NOW());

-- --- books -----------------------------------------------------------
INSERT INTO `books` (`id`, `kode_buku`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `isbn`, `category_id`, `stok`, `stok_tersedia`, `rating`, `created_at`, `updated_at`) VALUES
( 1, 'BK-0001', 'Laskar Pelangi',              'Andrea Hirata',            'Bentang Pustaka',      2005, '9789793062792', 1, 12, 12, 4.8, NOW(), NOW()),
( 2, 'BK-0002', 'Bumi Manusia',                'Pramoedya Ananta Toer',    'Hasta Mitra',          1980, '9789799731234', 2,  8,  8, 4.7, NOW(), NOW()),
( 3, 'BK-0003', 'Hujan',                       'Tere Liye',                'Gramedia',             2016, '9786020324783', 1, 10, 10, 4.5, NOW(), NOW()),
( 4, 'BK-0004', 'Pulang',                      'Tere Liye',                'Republika Penerbit',   2015, '9786020822129', 1,  9,  9, 4.4, NOW(), NOW()),
( 5, 'BK-0005', 'Filosofi Teras',              'Henry Manampiring',        'Kompas',               2018, '9786024126988', 4, 15, 15, 4.6, NOW(), NOW()),
( 6, 'BK-0006', 'Atomic Habits',               'James Clear',              'Gramedia',             2018, '9786020637167', 4, 20, 20, 4.9, NOW(), NOW()),
( 7, 'BK-0007', 'Sapiens',                     'Yuval Noah Harari',        'Kepustakaan Populer',  2011, '9786024246945', 2,  6,  6, 4.7, NOW(), NOW()),
( 8, 'BK-0008', 'Clean Code',                  'Robert C. Martin',         'Prentice Hall',        2008, '9780132350884', 3,  5,  5, 4.8, NOW(), NOW()),
( 9, 'BK-0009', 'Pemrograman Web dengan PHP',  'Abdul Kadir',              'Andi Publisher',       2019, '9789792975123', 3,  9,  9, 4.2, NOW(), NOW()),
(10, 'BK-0010', 'Negeri 5 Menara',             'Ahmad Fuadi',              'Gramedia',             2009, '9789792248616', 1, 11, 11, 4.5, NOW(), NOW()),
(11, 'BK-0011', 'Perahu Kertas',               'Dee Lestari',              'Bentang Pustaka',      2009, '9789791227780', 1,  7,  7, 4.3, NOW(), NOW()),
(12, 'BK-0012', 'Rich Dad Poor Dad',           'Robert T. Kiyosaki',       'Gramedia',             1997, '9786020331652', 6, 14, 14, 4.4, NOW(), NOW()),
(13, 'BK-0013', 'Algoritma dan Struktur Data', 'Rinaldi Munir',            'Informatika',          2016, '9786026232014', 3,  4,  4, 4.1, NOW(), NOW()),
(14, 'BK-0014', 'Cantik Itu Luka',             'Eka Kurniawan',            'Gramedia',             2002, '9786020317458', 1,  6,  6, 4.3, NOW(), NOW()),
(15, 'BK-0015', 'Laut Bercerita',              'Leila S. Chudori',         'Kepustakaan Populer',  2017, '9786024246945', 1,  9,  9, 4.6, NOW(), NOW()),
(16, 'BK-0016', 'Sejarah Nusantara Ringkas',   'Marwati Djoened',          'Balai Pustaka',        2008, '9789794071236', 2,  5,  5, 4.0, NOW(), NOW()),
(17, 'BK-0017', 'Belajar Laravel dari Nol',    'Rahmat Hidayat',           'Elex Media',           2021, '9786230023451', 3,  8,  8, 4.2, NOW(), NOW()),
(18, 'BK-0018', 'Fisika Kuantum Populer',      'Carlo Rovelli',            'Mizan',                2016, '9786024411237', 5,  6,  6, 4.4, NOW(), NOW()),
(19, 'BK-0019', 'Kosmos',                      'Carl Sagan',               'Gramedia',             1980, '9786020648712', 5,  7,  7, 4.7, NOW(), NOW()),
(20, 'BK-0020', 'Marketing 5.0',               'Philip Kotler',            'Gramedia',             2021, '9786020654321', 6,  6,  6, 4.1, NOW(), NOW()),
(21, 'BK-0021', 'Petualangan Si Kancil',       'Murti Bunanta',            'Noura Books',          2012, '9786021234567', 7, 13, 13, 4.0, NOW(), NOW()),
(22, 'BK-0022', 'Dongeng Sebelum Tidur',       'Nurul Ihsan',              'Bhuana Ilmu Populer',  2015, '9786022497654', 7, 10, 10, 3.9, NOW(), NOW()),
(23, 'BK-0023', 'Biografi B.J. Habibie',       'A. Makmur Makka',          'Edelweiss',            2013, '9786029762013', 8,  5,  5, 4.5, NOW(), NOW()),
(24, 'BK-0024', 'Chairul Tanjung Si Anak Singkong', 'Tjahja Gunawan',      'Kompas',               2012, '9789797099176', 8,  6,  6, 4.2, NOW(), NOW());

-- --- members ---------------------------------------------------------
-- tanggal_daftar dibuat relatif terhadap hari ini agar widget
-- "Anggota Baru Terdaftar" selalu terlihat wajar tanpa perlu diedit.
INSERT INTO `members` (`id`, `kode_anggota`, `nama`, `email`, `telepon`, `alamat`, `jenis_kelamin`, `tanggal_daftar`, `status`, `created_at`, `updated_at`) VALUES
( 1, 'AGT-0001', 'Budi Santoso',    'budi.santoso@mail.test',  '081234567801', 'Jl. Melati No. 10, Jakarta',  'L', DATE_SUB(CURDATE(), INTERVAL   2 DAY), 'aktif',    NOW(), NOW()),
( 2, 'AGT-0002', 'Siti Nurhaliza',  'siti.n@mail.test',        '081234567802', 'Jl. Kenanga No. 5, Bandung',  'P', DATE_SUB(CURDATE(), INTERVAL   4 DAY), 'aktif',    NOW(), NOW()),
( 3, 'AGT-0003', 'Rina Marlina',    'rina.m@mail.test',        '081234567803', 'Jl. Anggrek No. 22, Bekasi',  'P', DATE_SUB(CURDATE(), INTERVAL   7 DAY), 'aktif',    NOW(), NOW()),
( 4, 'AGT-0004', 'Doni Kusuma',     'doni.k@mail.test',        '081234567804', 'Jl. Mawar No. 3, Depok',      'L', DATE_SUB(CURDATE(), INTERVAL  12 DAY), 'aktif',    NOW(), NOW()),
( 5, 'AGT-0005', 'Ahmad Fauzi',     'ahmad.f@mail.test',       '081234567805', 'Jl. Cempaka No. 8, Bogor',    'L', DATE_SUB(CURDATE(), INTERVAL  20 DAY), 'aktif',    NOW(), NOW()),
( 6, 'AGT-0006', 'Dewi Lestari',    'dewi.l@mail.test',        '081234567806', 'Jl. Dahlia No. 14, Jakarta',  'P', DATE_SUB(CURDATE(), INTERVAL  35 DAY), 'aktif',    NOW(), NOW()),
( 7, 'AGT-0007', 'Eko Prasetyo',    'eko.p@mail.test',         '081234567807', 'Jl. Flamboyan No. 2, Tangerang','L', DATE_SUB(CURDATE(), INTERVAL 48 DAY), 'aktif',    NOW(), NOW()),
( 8, 'AGT-0008', 'Fitri Handayani', 'fitri.h@mail.test',       '081234567808', 'Jl. Teratai No. 9, Bandung',  'P', DATE_SUB(CURDATE(), INTERVAL  60 DAY), 'aktif',    NOW(), NOW()),
( 9, 'AGT-0009', 'Gilang Ramadhan', 'gilang.r@mail.test',      '081234567809', 'Jl. Sakura No. 17, Surabaya', 'L', DATE_SUB(CURDATE(), INTERVAL  75 DAY), 'nonaktif', NOW(), NOW()),
(10, 'AGT-0010', 'Hesti Purwanti',  'hesti.p@mail.test',       '081234567810', 'Jl. Kamboja No. 6, Semarang', 'P', DATE_SUB(CURDATE(), INTERVAL  90 DAY), 'aktif',    NOW(), NOW()),
(11, 'AGT-0011', 'Indra Wijaya',    'indra.w@mail.test',       '081234567811', 'Jl. Bougenville No. 4, Malang','L', DATE_SUB(CURDATE(), INTERVAL 110 DAY), 'aktif',    NOW(), NOW()),
(12, 'AGT-0012', 'Jasmine Aulia',   'jasmine.a@mail.test',     '081234567812', 'Jl. Tulip No. 11, Yogyakarta','P', DATE_SUB(CURDATE(), INTERVAL 130 DAY), 'diblokir', NOW(), NOW());

-- --- transactions ----------------------------------------------------
-- Campuran status supaya dashboard punya data nyata:
--   * tanggal_kembali NULL  -> masih dipinjam (sebagian sudah lewat tempo)
--   * tanggal_kembali diisi -> sudah dikembalikan (sebagian kena denda)
INSERT INTO `transactions` (`kode_transaksi`, `member_id`, `book_id`, `user_id`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `tanggal_kembali`, `status`, `denda`, `created_at`, `updated_at`) VALUES
-- Masih dipinjam (belum jatuh tempo)
('TRX-0001', 1,  1, 1, DATE_SUB(CURDATE(), INTERVAL  2 DAY), DATE_ADD(CURDATE(), INTERVAL  5 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
('TRX-0002', 2,  2, 1, DATE_SUB(CURDATE(), INTERVAL  3 DAY), DATE_ADD(CURDATE(), INTERVAL  4 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
('TRX-0003', 3,  3, 2, DATE_SUB(CURDATE(), INTERVAL  1 DAY), DATE_ADD(CURDATE(), INTERVAL  6 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
('TRX-0004', 4,  6, 2, DATE_SUB(CURDATE(), INTERVAL  4 DAY), DATE_ADD(CURDATE(), INTERVAL  3 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
('TRX-0005', 5,  5, 1, DATE_SUB(CURDATE(), INTERVAL  5 DAY), DATE_ADD(CURDATE(), INTERVAL  2 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
('TRX-0006', 6,  8, 1, DATE_SUB(CURDATE(), INTERVAL  6 DAY), DATE_ADD(CURDATE(), INTERVAL  1 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
-- Masih dipinjam TAPI sudah lewat jatuh tempo (kartu "Buku Terlambat")
('TRX-0007', 7,  4, 2, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL  8 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
('TRX-0008', 8,  9, 2, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 13 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
('TRX-0009', 1, 12, 1, DATE_SUB(CURDATE(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 11 DAY), NULL, 'dipinjam', 0, NOW(), NOW()),
-- Sudah dikembalikan tepat waktu
('TRX-0010', 2,  1, 1, DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 33 DAY), DATE_SUB(CURDATE(), INTERVAL 34 DAY), 'dikembalikan',    0, NOW(), NOW()),
('TRX-0011', 3,  6, 1, DATE_SUB(CURDATE(), INTERVAL 38 DAY), DATE_SUB(CURDATE(), INTERVAL 31 DAY), DATE_SUB(CURDATE(), INTERVAL 31 DAY), 'dikembalikan',    0, NOW(), NOW()),
('TRX-0012', 4,  5, 2, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 53 DAY), DATE_SUB(CURDATE(), INTERVAL 55 DAY), 'dikembalikan',    0, NOW(), NOW()),
('TRX-0013', 5,  1, 2, DATE_SUB(CURDATE(), INTERVAL 75 DAY), DATE_SUB(CURDATE(), INTERVAL 68 DAY), DATE_SUB(CURDATE(), INTERVAL 70 DAY), 'dikembalikan',    0, NOW(), NOW()),
('TRX-0014', 6,  3, 1, DATE_SUB(CURDATE(), INTERVAL 90 DAY), DATE_SUB(CURDATE(), INTERVAL 83 DAY), DATE_SUB(CURDATE(), INTERVAL 84 DAY), 'dikembalikan',    0, NOW(), NOW()),
-- Sudah dikembalikan tapi TERLAMBAT (denda Rp1.000/hari)
('TRX-0015', 7,  2, 1, DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_SUB(CURDATE(), INTERVAL 43 DAY), DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'terlambat',    3000, NOW(), NOW()),
('TRX-0016', 8,  7, 2, DATE_SUB(CURDATE(), INTERVAL 55 DAY), DATE_SUB(CURDATE(), INTERVAL 48 DAY), DATE_SUB(CURDATE(), INTERVAL 43 DAY), 'terlambat',    5000, NOW(), NOW()),
('TRX-0017', 10, 4, 1, DATE_SUB(CURDATE(), INTERVAL 70 DAY), DATE_SUB(CURDATE(), INTERVAL 63 DAY), DATE_SUB(CURDATE(), INTERVAL 61 DAY), 'terlambat',    2000, NOW(), NOW()),
('TRX-0018', 11, 1, 2, DATE_SUB(CURDATE(), INTERVAL 100 DAY), DATE_SUB(CURDATE(), INTERVAL 93 DAY), DATE_SUB(CURDATE(), INTERVAL 89 DAY), 'terlambat',   4000, NOW(), NOW()),
-- Data lama lintas bulan untuk mengisi grafik "Ringkasan Peminjaman Bulanan"
('TRX-0019', 1,  3, 1, DATE_SUB(CURDATE(), INTERVAL 120 DAY), DATE_SUB(CURDATE(), INTERVAL 113 DAY), DATE_SUB(CURDATE(), INTERVAL 114 DAY), 'dikembalikan', 0, NOW(), NOW()),
('TRX-0020', 2,  6, 1, DATE_SUB(CURDATE(), INTERVAL 135 DAY), DATE_SUB(CURDATE(), INTERVAL 128 DAY), DATE_SUB(CURDATE(), INTERVAL 130 DAY), 'dikembalikan', 0, NOW(), NOW()),
('TRX-0021', 3,  5, 2, DATE_SUB(CURDATE(), INTERVAL 150 DAY), DATE_SUB(CURDATE(), INTERVAL 143 DAY), DATE_SUB(CURDATE(), INTERVAL 145 DAY), 'dikembalikan', 0, NOW(), NOW()),
('TRX-0022', 4,  1, 2, DATE_SUB(CURDATE(), INTERVAL 165 DAY), DATE_SUB(CURDATE(), INTERVAL 158 DAY), DATE_SUB(CURDATE(), INTERVAL 159 DAY), 'dikembalikan', 0, NOW(), NOW()),
('TRX-0023', 5, 10, 1, DATE_SUB(CURDATE(), INTERVAL 175 DAY), DATE_SUB(CURDATE(), INTERVAL 168 DAY), DATE_SUB(CURDATE(), INTERVAL 168 DAY), 'dikembalikan', 0, NOW(), NOW()),
('TRX-0024', 6, 15, 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 23 DAY), DATE_SUB(CURDATE(), INTERVAL 24 DAY), 'dikembalikan', 0, NOW(), NOW()),
('TRX-0025', 7, 17, 2, DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_SUB(CURDATE(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 19 DAY), 'dikembalikan', 0, NOW(), NOW());

-- --- settings --------------------------------------------------------
INSERT INTO `settings` (`key_name`, `value`, `keterangan`, `updated_at`) VALUES
('app_name',        'DIGI-LIBRARY', 'Nama aplikasi yang tampil di sidebar',        NOW()),
('denda_per_hari',  '1000',         'Tarif denda keterlambatan per hari (Rupiah)', NOW()),
('max_hari_pinjam', '7',            'Lama pinjam default dalam hari',              NOW()),
('max_pinjam_buku', '3',            'Batas buku yang boleh dipinjam per anggota',  NOW());

-- =====================================================================
-- SINKRONISASI DATA TURUNAN
-- Dijalankan setelah seed agar angka stok & status selalu konsisten
-- dengan isi tabel transactions (tidak perlu dihitung manual).
-- =====================================================================

-- 1) Tandai transaksi yang belum kembali dan sudah lewat tempo => 'terlambat'
UPDATE `transactions`
SET `status` = 'terlambat'
WHERE `tanggal_kembali` IS NULL
  AND `tanggal_jatuh_tempo` < CURDATE();

-- 2) Hitung ulang stok_tersedia = stok - jumlah eksemplar yang sedang keluar
UPDATE `books` b
SET b.`stok_tersedia` = b.`stok` - (
    SELECT COUNT(*)
    FROM `transactions` t
    WHERE t.`book_id` = b.`id`
      AND t.`tanggal_kembali` IS NULL
);

-- =====================================================================
-- SELESAI
-- Verifikasi cepat (opsional):
--   SELECT COUNT(*) FROM books;         -- 24
--   SELECT COUNT(*) FROM members;       -- 12
--   SELECT COUNT(*) FROM transactions;  -- 25
-- =====================================================================
