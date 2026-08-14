<?php

/**
 * Template Pagination — gaya dasbor (Bootstrap 5)
 * ---------------------------------------------------------------------
 * Dipakai dengan: <?= $pager->links('grup', 'dashboard_pager') ?>
 *
 * Variabel yang disediakan CodeIgniter secara otomatis:
 * @var CodeIgniter\Pager\PagerRenderer $pager
 */

$pager->setSurroundCount(2); // tampilkan 2 nomor halaman di kiri & kanan halaman aktif
?>
<nav aria-label="Navigasi halaman" class="dl-pager">
    <ul class="pagination pagination-sm mb-0">

        <?php if ($pager->hasPrevious()) : ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="Halaman pertama">&laquo;</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getPrevious() ?>" aria-label="Sebelumnya">&lsaquo;</a>
            </li>
        <?php endif; ?>

        <?php foreach ($pager->links() as $link) : ?>
            <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
            </li>
        <?php endforeach; ?>

        <?php if ($pager->hasNext()) : ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getNext() ?>" aria-label="Berikutnya">&rsaquo;</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Halaman terakhir">&raquo;</a>
            </li>
        <?php endif; ?>

    </ul>
</nav>
