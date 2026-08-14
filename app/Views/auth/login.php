<?php

/**
 * HALAMAN LOGIN
 * ---------------------------------------------------------------------
 * Halaman ini TIDAK memakai layout dasbor karena belum ada sidebar/topbar
 * (user belum terautentikasi). Tampilannya kartu putih di atas latar
 * gradasi navy, selaras dengan warna sidebar aplikasi.
 *
 * Diakses lewat route /login yang dijaga GuestFilter — user yang sudah
 * login otomatis dialihkan ke dashboard.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Login') ?> &middot; DIGI-LIBRARY</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
</head>
<body>

<div class="dl-login">
    <div class="dl-login__card">

        <!-- Identitas aplikasi -->
        <div class="text-center mb-4">
            <div class="d-inline-grid mb-2"
                 style="width:52px;height:52px;place-items:center;border-radius:14px;background:#101C34;color:#3B82F6;font-size:24px">
                <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <h1 class="h5 fw-bold mb-1">DIGI-LIBRARY</h1>
            <p class="text-muted small mb-0">Masuk untuk mengelola perpustakaan digital</p>
        </div>

        <!-- Notifikasi: gagal login, berhasil logout, atau belum login -->
        <?php if (session()->getFlashdata('error')) : ?>
            <div class="dl-alert dl-alert--danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= esc(session()->getFlashdata('error')) ?></div>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')) : ?>
            <div class="dl-alert dl-alert--success">
                <i class="bi bi-check-circle-fill"></i>
                <div><?= esc(session()->getFlashdata('success')) ?></div>
            </div>
        <?php endif; ?>

        <!-- Daftar error validasi form -->
        <?php if (session()->getFlashdata('errors')) : ?>
            <div class="dl-alert dl-alert--danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <ul class="mb-0">
                        <?php foreach (session()->getFlashdata('errors') as $pesan) : ?>
                            <li><?= esc($pesan) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- csrf_field() wajib: filter 'csrf' aktif global di Config/Filters.php -->
        <form action="<?= site_url('login') ?>" method="post" autocomplete="off">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label small fw-semibold" for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="Masukkan username"
                           value="<?= esc(old('username'), 'attr') ?>" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" class="dl-btn dl-btn--primary w-100 justify-content-center">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
            </button>
        </form>

        <p class="text-center text-muted small mt-4 mb-0">
            Akun demo: <code>admin</code> / <code>admin123</code>
        </p>

    </div>
</div>

</body>
</html>
