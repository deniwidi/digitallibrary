<?php

/**
 * PARTIAL: FLASH MESSAGE
 * ---------------------------------------------------------------------
 * Menampilkan notifikasi sekali-pakai yang dikirim controller lewat
 * ->with('success'|'error'|'errors', ...). Data flash otomatis dihapus
 * CodeIgniter setelah request berikutnya, jadi tidak perlu dibersihkan
 * secara manual.
 *
 * 'errors' berisi array hasil $this->validator->getErrors().
 */
?>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="dl-alert dl-alert--success">
        <i class="bi bi-check-circle-fill"></i>
        <div><?= esc(session()->getFlashdata('success')) ?></div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')) : ?>
    <div class="dl-alert dl-alert--danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div><?= esc(session()->getFlashdata('error')) ?></div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')) : ?>
    <div class="dl-alert dl-alert--danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Periksa kembali isian berikut:</strong>
            <ul>
                <?php foreach (session()->getFlashdata('errors') as $pesan) : ?>
                    <li><?= esc($pesan) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
