<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

try {
    $categories = get_categories($pdo);
} catch (PDOException $exception) {
    error_log('[PRODUCT_CREATE] ' . $exception->getMessage());
    render_error_page(
        500,
        'Form barang belum dapat dibuka',
        'Kategori barang tidak dapat dimuat karena terjadi kesalahan pada database.',
        'products'
    );
}

if (!$categories) {
    render_error_page(
        503,
        'Kategori belum tersedia',
        'Form barang membutuhkan minimal satu kategori. Impor file database.sql melalui phpMyAdmin untuk membuat kategori awal, lalu muat ulang halaman ini.',
        'products'
    );
}

$state = pull_form('product_create');
$errors = $state['errors'];
$old = $state['old'];

$formData = [
    'id' => null,
    'kode_barang' => '',
    'nama_barang' => old_input($old, 'nama_barang'),
    'category_id' => old_input($old, 'category_id'),
    'harga' => old_input($old, 'harga'),
    'stok' => old_input($old, 'stok'),
];

$formAction = app_url('products/store.php');
$submitLabel = 'Simpan barang';
$cancelUrl = app_url('products/index.php');
$isEdit = false;

$pageTitle = 'Tambah Barang';
$activePage = 'products';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1 class="page-title">Tambah Barang</h1>
        <p class="page-description">Catat barang baru beserta kategori, harga, dan jumlah stok awal.</p>
    </div>
    <div class="page-actions">
        <a href="<?= e(app_url('products/index.php')) ?>" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<?php require __DIR__ . '/_form.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
