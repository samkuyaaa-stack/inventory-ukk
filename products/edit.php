<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

$productId = valid_id($_GET['id'] ?? null);
if ($productId === null) {
    render_error_page(
        400,
        'ID barang tidak valid',
        'Parameter ID barang tidak valid. Pilih barang dari halaman data barang untuk mengeditnya.',
        'products'
    );
}

try {
    $product = get_product_by_id($pdo, $productId);
    $categories = get_categories($pdo);
} catch (PDOException $exception) {
    error_log('[PRODUCT_EDIT] ' . $exception->getMessage());
    render_error_page(
        500,
        'Data barang belum dapat dimuat',
        'Terjadi kesalahan saat mengambil data barang. Periksa koneksi database lalu coba lagi.',
        'products'
    );
}

if (!$product) {
    render_error_page(
        404,
        'Barang tidak ditemukan',
        'Barang dengan ID tersebut tidak tersedia. Mungkin data sudah dihapus atau ID yang digunakan keliru.',
        'products'
    );
}

$state = pull_form('product_edit_' . $productId);
$errors = $state['errors'];
$old = $state['old'];

$formData = [
    'id' => (int) $product['id'],
    'kode_barang' => (string) $product['kode_barang'],
    'nama_barang' => old_input($old, 'nama_barang', (string) $product['nama_barang']),
    'category_id' => old_input($old, 'category_id', (string) $product['category_id']),
    'harga' => old_input($old, 'harga', (string) $product['harga']),
    'stok' => old_input($old, 'stok', (string) $product['stok']),
];

$formAction = app_url('products/update.php');
$submitLabel = 'Simpan perubahan';
$cancelUrl = app_url('products/index.php');
$isEdit = true;

$pageTitle = 'Edit Barang';
$activePage = 'products';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1 class="page-title">Edit Barang</h1>
        <p class="page-description">
            Perbarui data <strong><?= e($product['nama_barang']) ?></strong>
            dengan kode <?= e($product['kode_barang']) ?>.
        </p>
    </div>
    <div class="page-actions">
        <a href="<?= e(app_url('products/index.php')) ?>" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<?php require __DIR__ . '/_form.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
