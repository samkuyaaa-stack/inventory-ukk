<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

try {
    $products = get_report_products($pdo);
} catch (PDOException $exception) {
    error_log('[REPORT_INDEX] ' . $exception->getMessage());
    render_error_page(
        500,
        'Laporan belum dapat dimuat',
        'Terjadi kesalahan saat mengambil data laporan. Periksa koneksi database lalu coba lagi.',
        'reports'
    );
}

$totalJenis = count($products);
$totalStok = 0;
$totalNilai = 0.0;

foreach ($products as $product) {
    $totalStok += (int) $product['stok'];
    $totalNilai += nilai_persediaan($product['harga'], $product['stok']);
}

$fpdfAvailable = is_file(__DIR__ . '/../vendor/fpdf/fpdf.php');
$generatedAt = date('Y-m-d H:i:s');

$pageTitle = 'Laporan Persediaan';
$activePage = 'reports';
$bodyClass = 'report-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1 class="page-title">Laporan Persediaan</h1>
        <p class="page-description">
            Ringkasan seluruh barang beserta nilai persediaan berdasarkan data terbaru di database.
        </p>
    </div>
    <div class="page-actions">
        <?php if ($fpdfAvailable): ?>
            <a href="<?= e(app_url('reports/pdf.php')) ?>"
               class="btn btn-primary"
               target="_blank"
               rel="noopener">Cetak PDF</a>
        <?php else: ?>
            <button type="button" class="btn btn-primary" disabled title="Library FPDF belum tersedia">Cetak PDF</button>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-secondary" data-print>Cetak halaman</button>
    </div>
</div>

<?php if (!$fpdfAvailable): ?>
    <div class="app-alert alert-warning" role="alert">
        <div class="alert-message">
            Library FPDF belum ditemukan di <code>vendor/fpdf/fpdf.php</code>.
            Letakkan FPDF 1.8.6 pada folder tersebut agar tombol cetak PDF dapat digunakan.
        </div>
    </div>
<?php endif; ?>

<div class="print-only print-report-head">
    <h1><?= e(APP_NAME) ?> &mdash; Laporan Persediaan Barang</h1>
    <p>Dicetak pada <?= e(format_tanggal_panjang(date('Y-m-d'))) ?> pukul <?= e(date('H:i')) ?> WIB</p>
</div>

<section class="report-summary" aria-label="Ringkasan laporan">
    <div class="report-stat">
        <span>Total jenis barang</span>
        <strong><?= e(format_angka($totalJenis)) ?></strong>
    </div>
    <div class="report-stat">
        <span>Total stok</span>
        <strong><?= e(format_angka($totalStok)) ?> unit</strong>
    </div>
    <div class="report-stat">
        <span>Total nilai persediaan</span>
        <strong><?= e(format_rupiah($totalNilai)) ?></strong>
    </div>
</section>

<section class="app-card report-table">
    <div class="card-head">
        <div>
            <h2 class="card-title">Rincian persediaan</h2>
            <p class="card-subtitle">Seluruh barang diurutkan berdasarkan kategori dan nama barang.</p>
        </div>
        <span class="report-meta">Dibuat <?= e(format_tanggal($generatedAt, true)) ?></span>
    </div>

    <?php if (!$products): ?>
        <div class="empty-state">
            <h3 class="empty-title">Belum ada data untuk dilaporkan</h3>
            <p class="empty-text">
                Laporan akan terisi setelah ada barang yang dicatat. Tambahkan barang terlebih dahulu
                melalui halaman data barang.
            </p>
            <div class="empty-actions no-print">
                <a href="<?= e(app_url('products/create.php')) ?>" class="btn btn-primary">Tambah barang</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="app-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 54px;">No.</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama barang</th>
                        <th scope="col">Kategori</th>
                        <th scope="col" class="num">Harga</th>
                        <th scope="col" class="num">Stok</th>
                        <th scope="col" class="num">Nilai persediaan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): ?>
                        <tr>
                            <td class="text-muted"><?= e(format_angka($index + 1)) ?></td>
                            <td><span class="code-badge"><?= e($product['kode_barang']) ?></span></td>
                            <td><span class="product-name"><?= e($product['nama_barang']) ?></span></td>
                            <td><?= e($product['nama_kategori']) ?></td>
                            <td class="num"><?= e(format_rupiah($product['harga'])) ?></td>
                            <td class="num">
                                <span class="stock-badge <?= e(kelas_stok((int) $product['stok'])) ?>">
                                    <?= e(format_angka($product['stok'])) ?>
                                </span>
                            </td>
                            <td class="num"><?= e(format_rupiah(nilai_persediaan($product['harga'], $product['stok']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">Total keseluruhan</td>
                        <td class="num"><?= e(format_angka($totalStok)) ?></td>
                        <td class="num"><?= e(format_rupiah($totalNilai)) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
