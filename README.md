# Perpustakaan Buku Digital

Aplikasi web manajemen koleksi buku berbasis **CodeIgniter 4.7** + **MySQL/MariaDB**
dengan tampilan bergaya **Glassmorphism**.

## Fitur

| Fitur | Keterangan |
|---|---|
| CRUD Buku | Tambah, lihat, edit, hapus data buku (judul, penulis, penerbit, tahun, kategori, stok) |
| Session Login | Login/logout memakai CodeIgniter Session; halaman buku dijaga `AuthFilter` |
| Searching | Cari buku berdasarkan judul **atau** penulis |
| Pagination | Pager bawaan CodeIgniter, **10 buku per halaman**, tombol « ‹ › », keyword ikut terbawa antar halaman |
| Glassmorphism | `backdrop-filter: blur()`, panel semi-transparan, border tipis, shadow lembut, background gradient + blob aurora |
| Keamanan | Query Builder (anti SQL Injection), `password_hash`/`password_verify`, CSRF filter global, `esc()` di semua output |

## Akun Demo

| Username | Password |
|---|---|
| `admin` | `admin123` |
| `petugas` | `petugas123` |

---

## Struktur File Penting

```
perpustakaan/
├── app/
│   ├── Config/
│   │   ├── Database.php          # Kredensial & port database
│   │   ├── Filters.php           # Registrasi alias filter 'auth' + aktivasi CSRF
│   │   ├── Pager.php             # Registrasi template pagination 'glass_pager'
│   │   └── Routes.php            # Definisi seluruh route
│   ├── Controllers/
│   │   ├── Auth.php              # Login, proses login, logout
│   │   └── Book.php              # CRUD + searching + pagination
│   ├── Database/
│   │   └── Seeds/BookSeeder.php  # Generator 100 buku dummy (Faker)
│   ├── Filters/
│   │   └── AuthFilter.php        # Middleware penjaga halaman privat
│   ├── Models/
│   │   ├── UserModel.php         # Query tabel users + verifikasi password
│   │   └── BookModel.php         # Query tabel books + validasi + paginate
│   └── Views/
│       ├── auth/login.php        # Halaman login (glassmorphism)
│       ├── books/index.php       # Dashboard daftar buku
│       ├── books/form.php        # Form tambah & edit (dipakai bersama)
│       ├── layouts/main.php      # Layout utama (navbar + flash message)
│       └── pager/glass_pager.php # Template pagination custom
├── database/
│   └── perpustakaan.sql          # DDL + dummy data
├── public/
│   ├── assets/css/style.css      # Seluruh CSS glassmorphism
│   └── index.php                 # Front controller
└── .env                          # baseURL, environment, kredensial database
```

---

## Cara Menjalankan

### 1. Nyalakan Apache & MySQL
Buka **XAMPP Control Panel**, klik `Start` pada **Apache** dan **MySQL**.

### 2. Import Database
Buka <http://localhost/phpmyadmin> → tab **Import** → pilih file
`perpustakaan/database/perpustakaan.sql` → klik **Go**.

Alternatif lewat terminal:

```bash
C:/xampp/mysql/bin/mysql.exe -u root -P 3308 -h 127.0.0.1 -e "source C:/xampp/htdocs/perpustakaan/database/perpustakaan.sql"
```

File SQL tersebut sudah berisi **112 buku** (12 buku pilihan + 100 buku dummy).

### 2b. (Alternatif) Isi Data Dummy lewat Seeder

Kalau tabel `books` masih kosong atau ingin menambah 100 buku lagi:

```bash
cd C:/xampp/htdocs/perpustakaan && php spark db:seed BookSeeder
```

Seeder memakai library **Faker** dengan seed dikunci, jadi hasilnya konsisten.
Judul yang sudah ada di database otomatis dilewati sehingga aman dijalankan ulang.

> Jalankan **salah satu** saja — import SQL **atau** seeder — supaya data tidak dobel.

### 3. Sesuaikan Konfigurasi

Cek file `.env` di root project:

```
app.baseURL = 'http://localhost/perpustakaan/public/'
database.default.database = perpustakaan_digital
database.default.username = root
database.default.password =
database.default.port = 3308
```

> **Catatan port:** di mesin ini port `3306` sudah dipakai service MySQL80 lain,
> sehingga MariaDB XAMPP berjalan di port **3308** (lihat `C:\xampp\mysql\bin\my.ini`).
> Pada XAMPP standar, ganti nilainya menjadi `3306`.

### 4. Buka di Browser

<http://localhost/perpustakaan/public/>

Login dengan akun demo di atas.

### 5. (Opsional) Jalankan lewat server bawaan CodeIgniter

```bash
cd C:/xampp/htdocs/perpustakaan && php spark serve
```

Lalu buka <http://localhost:8080>.

---

## Menambah User Baru

Password harus di-hash, jangan disimpan sebagai teks biasa. Buat hash-nya dulu:

```bash
C:/xampp/php/php.exe -r "echo password_hash('passwordbaru', PASSWORD_DEFAULT);"
```

Lalu masukkan hasilnya ke kolom `password` tabel `users`.

---

## Sebelum Deploy ke Production

1. Ubah `CI_ENVIRONMENT = production` di `.env` (debug toolbar & detail error ikut mati).
2. Ganti password default akun demo.
3. Beri password pada user `root` MySQL.
4. Arahkan DocumentRoot / virtual host ke folder `public/` agar folder `app` dan
   `writable` tidak bisa diakses langsung dari browser.
