<?php
declare(strict_types=1);

$sidebarItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => app_url('dashboard.php')],
    ['key' => 'products', 'label' => 'Data Barang', 'url' => app_url('products/index.php')],
    ['key' => 'reports', 'label' => 'Laporan', 'url' => app_url('reports/index.php')],
];
?>
<aside class="app-sidebar" id="appSidebar" aria-label="Navigasi utama">
    <div class="sidebar-head">
        <a class="brand" href="<?= e(app_url('dashboard.php')) ?>">
            Inventaris<span>UKK</span>
        </a>
        <button class="sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu">
            Tutup
        </button>
    </div>

    <div class="sidebar-body">
        <p class="sidebar-label">Menu</p>
        <nav class="sidebar-nav">
            <?php foreach ($sidebarItems as $item): ?>
                <a class="sidebar-link <?= $activePage === $item['key'] ? 'active' : '' ?>"
                   href="<?= e($item['url']) ?>"
                   <?= $activePage === $item['key'] ? 'aria-current="page"' : '' ?>>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="sidebar-foot">
        <div class="sidebar-user">
            <span>Masuk sebagai</span>
            <strong title="<?= e($headerUser['nama'] ?? 'Pengguna') ?>"><?= e($headerUser['nama'] ?? 'Pengguna') ?></strong>
        </div>
        <form action="<?= e(app_url('auth/logout.php')) ?>" method="post">
            <?= csrf_field() ?>
            <button class="sidebar-logout" type="submit">Keluar</button>
        </form>
    </div>
</aside>

<button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Tutup menu" tabindex="-1"></button>
