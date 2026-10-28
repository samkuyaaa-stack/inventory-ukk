<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

if (!is_post()) {
    redirect('products/index.php');
}

$productId = valid_id($_POST['id'] ?? null);
if ($productId === null) {
    set_flash('danger', 'ID barang tidak valid. Perubahan tidak dapat diproses.');
    redirect('products/index.php');
}

csrf_require('products/index.php');

try {
    $existingProduct = get_product_by_id($pdo, $productId);
} catch (PDOException $exception) {
    error_log('[PRODUCT_UPDATE_CHECK] ' . $exception->getMessage());
    set_flash('danger', 'Data barang belum dapat diperiksa karena terjadi kesalahan pada database.');
    redirect('products/index.php');
}

if (!$existingProduct) {
    set_flash('warning', 'Barang tidak ditemukan. Mungkin data sudah dihapus sebelumnya.');
    redirect('products/index.php');
}

try {
    $validation = validate_product_data($pdo, $_POST);
} catch (PDOException $exception) {
    error_log('[PRODUCT_UPDATE_VALIDATE] ' . $exception->getMessage());
    flash_form(
        ['umum' => 'Perubahan belum dapat divalidasi karena terjadi kesalahan pada database. Silakan coba lagi.'],
        $_POST,
        'product_edit_' . $productId
    );
    redirect('products/edit.php?id=' . $productId);
}

$errors = $validation['errors'];
$data = $validation['data'];

if ($errors) {
    flash_form($errors, $data, 'product_edit_' . $productId);
    redirect('products/edit.php?id=' . $productId);
}

try {
    $statement = $pdo->prepare(
        'UPDATE products
         SET nama_barang = ?, category_id = ?, harga = ?, stok = ?
         WHERE id = ?'
    );
    $statement->execute([
        $data['nama_barang'],
        (int) $data['category_id'],
        $data['harga'],
        (int) $data['stok'],
        $productId,
    ]);

    set_flash('success', 'Data barang ' . $data['nama_barang'] . ' berhasil diperbarui.');
    redirect('products/index.php');
} catch (PDOException $exception) {
    error_log('[PRODUCT_UPDATE] ' . $exception->getMessage());
    $errors['umum'] = 'Perubahan belum dapat disimpan karena terjadi kesalahan pada server. Silakan coba lagi.';

    flash_form($errors, $data, 'product_edit_' . $productId);
    redirect('products/edit.php?id=' . $productId);
}
