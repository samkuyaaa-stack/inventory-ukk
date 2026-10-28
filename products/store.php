<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

if (!is_post()) {
    redirect('products/create.php');
}

csrf_require('products/create.php');

try {
    $validation = validate_product_data($pdo, $_POST);
} catch (PDOException $exception) {
    error_log('[PRODUCT_STORE_VALIDATE] ' . $exception->getMessage());
    flash_form(
        ['umum' => 'Data barang belum dapat divalidasi karena terjadi kesalahan pada database. Silakan coba lagi.'],
        $_POST,
        'product_create'
    );
    redirect('products/create.php');
}

$errors = $validation['errors'];
$data = $validation['data'];

if ($errors) {
    flash_form($errors, $data, 'product_create');
    redirect('products/create.php');
}

try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO products (kode_barang, nama_barang, category_id, harga, stok)
         VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([
        'TMP-' . strtoupper(bin2hex(random_bytes(8))),
        $data['nama_barang'],
        (int) $data['category_id'],
        $data['harga'],
        (int) $data['stok'],
    ]);

    $productId = (int) $pdo->lastInsertId();
    $kodeBarang = generate_kode_barang($pdo, $productId);

    $statement = $pdo->prepare('UPDATE products SET kode_barang = ? WHERE id = ?');
    $statement->execute([$kodeBarang, $productId]);

    $pdo->commit();

    set_flash('success', 'Barang ' . $data['nama_barang'] . ' berhasil ditambahkan dengan kode ' . $kodeBarang . '.');
    redirect('products/index.php');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[PRODUCT_STORE] ' . $exception->getMessage());
    $errors['umum'] = 'Barang belum dapat disimpan karena terjadi kesalahan pada server. Silakan coba lagi.';

    flash_form($errors, $data, 'product_create');
    redirect('products/create.php');
}
