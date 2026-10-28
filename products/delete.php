<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

if (!is_post()) {
    set_flash('danger', 'Penghapusan barang hanya dapat dilakukan melalui tombol hapus.');
    redirect('products/index.php');
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    set_flash('danger', 'Permintaan hapus ditolak karena token keamanan tidak valid. Silakan coba lagi.');
    redirect('products/index.php');
}

$productId = valid_id($_POST['id'] ?? null);
if ($productId === null) {
    set_flash('danger', 'ID barang tidak valid. Penghapusan tidak diproses.');
    redirect('products/index.php');
}

try {
    $product = get_product_by_id($pdo, $productId);

    if (!$product) {
        set_flash('warning', 'Barang tidak ditemukan. Mungkin data sudah dihapus sebelumnya.');
        redirect('products/index.php');
    }

    $statement = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $statement->execute([$productId]);

    set_flash('success', 'Barang ' . $product['nama_barang'] . ' berhasil dihapus.');
} catch (PDOException $exception) {
    error_log('[PRODUCT_DELETE] ' . $exception->getMessage());
    set_flash('danger', 'Barang belum dapat dihapus karena terjadi kesalahan pada server. Silakan coba lagi.');
}

redirect('products/index.php');
