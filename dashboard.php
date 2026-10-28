<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

try {
    $stats = get_dashboard_stats($pdo);
    $latestProducts = get_latest_products($pdo, 5);
    $lowStockProducts = get_low_stock_products($pdo, LOW_STOCK_THRESHOLD, 5);
} catch (PDOException $exception) {
    error_log('[DASHBOARD] ' . $exception->getMessage());
    render_error_page(
        500,
        'Dashboard belum dapat dimuat',
        'Terjadi kesalahan saat mengambil data persediaan. Periksa koneksi database lalu coba lagi.',
        'dashboard'
    );
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-description">
            Ringkasan persediaan barang yang tercatat di database.
        </p>
    </div>
    <div class="page-actions">
        <a href="<?= e(app_url('reports/index.php')) ?>" class="btn btn-outline-secondary">Lihat laporan</a>
        <a href="<?= e(app_url('products/create.php')) ?>" class="btn btn-primary">Tambah barang</a>
    </div>
</div>

<section class="stats-grid" aria-label="Statistik persediaan">
    <article class="stat-card">
        <p class="stat-label">Jenis barang</p>
        <p class="stat-value"><?= e(format_angka($stats['total_jenis'])) ?></p>
        <p class="stat-meta">Barang terdaftar</p>
    </article>

    <article class="stat-card">
        <p class="stat-label">Total stok</p>
        <p class="stat-value"><?= e(format_angka($stats['total_stok'])) ?></p>
        <p class="stat-meta">Unit di seluruh persediaan</p>
    </article>

    <article class="stat-card">
        <p class="stat-label">Total nilai persediaan</p>
        <p class="stat-value is-currency"><?= e(format_rupiah($stats['total_nilai'])) ?></p>
        <p class="stat-meta">Harga dikali stok</p>
    </article>

    <article class="stat-card">
        <p class="stat-label">Kategori barang</p>
        <p class="stat-value"><?= e(format_angka($stats['total_kategori'])) ?></p>
        <p class="stat-meta">Klasifikasi tersedia</p>
    </article>
</section>

<div class="dashboard-grid">
    <section class="app-card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Barang terbaru</h2>
                <p class="card-subtitle">Lima data yang terakhir ditambahkan.</p>
            </div>
            <a href="<?= e(app_url('products/index.php')) ?>" class="card-link">Lihat semua</a>
        </div>

        <?php if (!$latestProducts): ?>
            <div class="empty-state">
                <h3 class="empty-title">Belum ada data barang</h3>
                <p class="empty-text">Tambahkan barang pertama untuk mulai mengisi persediaan.</p>
                <div class="empty-actions">
                    <a href="<?= e(app_url('products/create.php')) ?>" class="btn btn-primary btn-sm">Tambah barang</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th scope="col">Barang</th>
                            <th scope="col">Kategori</th>
                            <th scope="col" class="num">Harga</th>
                            <th scope="col" class="num">Stok</th>
                            <th scope="col"><span class="visually-hidden">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestProducts as $product): ?>
                            <tr>
                                <td>
                                    <span class="product-name"><?= e($product['nama_barang']) ?></span>
                                    <span class="product-code"><?= e($product['kode_barang']) ?></span>
                                </td>
                                <td><?= e($product['nama_kategori']) ?></td>
                                <td class="num"><?= e(format_rupiah($product['harga'])) ?></td>
                                <td class="num">
                                    <span class="stock-badge <?= e(kelas_stok((int) $product['stok'])) ?>">
                                        <?= e(format_angka($product['stok'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a class="action-link" href="<?= e(app_url('products/edit.php?id=' . (int) $product['id'])) ?>">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="app-card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Stok rendah</h2>
                <p class="card-subtitle">Barang dengan stok <?= (int) LOW_STOCK_THRESHOLD ?> unit atau kurang.</p>
            </div>
            <a href="<?= e(app_url('products/index.php?sort=stok&dir=asc')) ?>" class="card-link">Periksa</a>
        </div>

        <?php if (!$lowStockProducts): ?>
            <div class="empty-state low-stock-empty">
                <h3 class="empty-title">Stok dalam kondisi aman</h3>
                <p class="empty-text">Tidak ada barang yang menyentuh batas stok rendah saat ini.</p>
            </div>
        <?php else: ?>
            <ul class="low-stock-list">
                <?php foreach ($lowStockProducts as $product): ?>
                    <?php $stok = (int) $product['stok']; ?>
                    <li class="low-stock-item">
                        <div class="low-stock-info">
                            <strong><?= e($product['nama_barang']) ?></strong>
                            <small><?= e($product['kode_barang']) ?> &middot; <?= e($product['nama_kategori']) ?></small>
                        </div>
                        <span class="stock-badge <?= e(kelas_stok($stok)) ?>"><?= e(format_angka($stok)) ?></span>
                        <a class="action-link" href="<?= e(app_url('products/edit.php?id=' . (int) $product['id'])) ?>">Edit</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
