<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

$q = trim((string) ($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 100);
$categoryFilter = valid_id($_GET['category_id'] ?? null);

$sortMap = [
    'created' => 'p.created_at',
    'kode' => 'p.kode_barang',
    'nama' => 'p.nama_barang',
    'kategori' => 'c.nama_kategori',
    'harga' => 'p.harga',
    'stok' => 'p.stok',
    'nilai' => '(p.harga * p.stok)',
];

$sort = (string) ($_GET['sort'] ?? 'created');
if (!array_key_exists($sort, $sortMap)) {
    $sort = 'created';
}

$dir = strtolower((string) ($_GET['dir'] ?? ''));
if (!in_array($dir, ['asc', 'desc'], true)) {
    $dir = $sort === 'created' ? 'desc' : 'asc';
}

$perPage = 10;
$page = valid_id($_GET['page'] ?? 1) ?? 1;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(p.kode_barang LIKE :search_kode OR p.nama_barang LIKE :search_nama)';
    $params[':search_kode'] = '%' . $q . '%';
    $params[':search_nama'] = '%' . $q . '%';
}

if ($categoryFilter !== null) {
    $where[] = 'p.category_id = :category_id';
    $params[':category_id'] = $categoryFilter;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

try {
    $categories = get_categories($pdo);

    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM products p' . $whereSql);
    foreach ($params as $key => $value) {
        $countStatement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $countStatement->execute();
    $totalProducts = (int) $countStatement->fetchColumn();

    $totalPages = max(1, (int) ceil($totalProducts / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $orderBy = $sortMap[$sort] . ' ' . strtoupper($dir);
    if ($sort !== 'created') {
        $orderBy .= ', p.created_at DESC';
    }
    $orderBy .= ', p.id DESC';

    $sql = 'SELECT p.id, p.kode_barang, p.nama_barang, p.harga, p.stok, p.created_at,
                   c.nama_kategori
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id'
            . $whereSql
            . ' ORDER BY ' . $orderBy
            . ' LIMIT :limit OFFSET :offset';

    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $products = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('[PRODUCTS_INDEX] ' . $exception->getMessage());
    render_error_page(
        500,
        'Data barang belum dapat dimuat',
        'Terjadi kesalahan saat mengambil data barang. Periksa koneksi database lalu coba lagi.',
        'products'
    );
}

$startNumber = $totalProducts > 0 ? (($page - 1) * $perPage) + 1 : 0;
$endNumber = min($page * $perPage, $totalProducts);
$hasFilter = $q !== '' || $categoryFilter !== null;

$sortUrl = static function (string $key) use ($sort, $dir): string {
    $nextDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';

    return query_url([
        'sort' => $key,
        'dir' => $nextDir,
        'page' => null,
    ]);
};

$sortIcon = static function (string $key) use ($sort, $dir): string {
    if ($sort !== $key) {
        return 'bi-arrow-down-up';
    }

    return $dir === 'asc' ? 'bi-sort-up' : 'bi-sort-down';
};

$pageTitle = 'Data Barang';
$activePage = 'products';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1 class="page-title">Data Barang</h1>
        <p class="page-description">Kelola daftar barang, pantau harga, dan periksa sisa stok.</p>
    </div>
    <div class="page-actions">
        <a href="<?= e(app_url('products/create.php')) ?>" class="btn btn-primary">Tambah barang</a>
    </div>
</div>

<section class="app-card filter-card" aria-label="Filter data barang">
    <form class="filter-form" method="get" action="<?= e(app_url('products/index.php')) ?>">
        <div class="filter-search">
            <label class="visually-hidden" for="search">Cari barang</label>
            <input type="search"
                   class="form-control"
                   id="search"
                   name="q"
                   value="<?= e($q) ?>"
                   placeholder="Cari kode atau nama barang..."
                   maxlength="100">
        </div>

        <div>
            <label class="visually-hidden" for="category_filter">Filter kategori</label>
            <select class="form-select" id="category_filter" name="category_id">
                <option value="">Semua kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $categoryFilter === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($hasFilter): ?>
                <a href="<?= e(app_url('products/index.php')) ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="app-card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Daftar barang</h2>
            <p class="card-subtitle">Klik judul kolom untuk mengurutkan data.</p>
        </div>
        <span class="result-count">
            <?php if ($totalProducts > 0): ?>
                Menampilkan <strong><?= e(format_angka($startNumber)) ?>&ndash;<?= e(format_angka($endNumber)) ?></strong>
                dari <strong><?= e(format_angka($totalProducts)) ?></strong> barang
            <?php else: ?>
                Tidak ada barang
            <?php endif; ?>
        </span>
    </div>

    <?php if (!$products): ?>
        <div class="empty-state">
            <h3 class="empty-title"><?= $hasFilter ? 'Barang tidak ditemukan' : 'Belum ada data barang' ?></h3>
            <p class="empty-text">
                <?= $hasFilter
                    ? 'Tidak ada data yang sesuai dengan pencarian atau kategori yang dipilih. Coba kata kunci lain atau reset filter.'
                    : 'Tambahkan barang pertama untuk mulai mencatat persediaan.' ?>
            </p>
            <div class="empty-actions">
                <?php if ($hasFilter): ?>
                    <a href="<?= e(app_url('products/index.php')) ?>" class="btn btn-outline-secondary">Reset filter</a>
                <?php endif; ?>
                <a href="<?= e(app_url('products/create.php')) ?>" class="btn btn-primary">Tambah barang</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="app-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 54px;">No.</th>
                        <th scope="col">
                            <a class="sortable <?= $sort === 'kode' ? 'is-sorted' : '' ?>" href="<?= e($sortUrl('kode')) ?>">
                                Kode <i class="bi <?= e($sortIcon('kode')) ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col">
                            <a class="sortable <?= $sort === 'nama' ? 'is-sorted' : '' ?>" href="<?= e($sortUrl('nama')) ?>">
                                Nama barang <i class="bi <?= e($sortIcon('nama')) ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col">
                            <a class="sortable <?= $sort === 'kategori' ? 'is-sorted' : '' ?>" href="<?= e($sortUrl('kategori')) ?>">
                                Kategori <i class="bi <?= e($sortIcon('kategori')) ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" class="num">
                            <a class="sortable <?= $sort === 'harga' ? 'is-sorted' : '' ?>" href="<?= e($sortUrl('harga')) ?>">
                                Harga <i class="bi <?= e($sortIcon('harga')) ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" class="num">
                            <a class="sortable <?= $sort === 'stok' ? 'is-sorted' : '' ?>" href="<?= e($sortUrl('stok')) ?>">
                                Stok <i class="bi <?= e($sortIcon('stok')) ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" class="num">
                            <a class="sortable <?= $sort === 'nilai' ? 'is-sorted' : '' ?>" href="<?= e($sortUrl('nilai')) ?>">
                                Nilai <i class="bi <?= e($sortIcon('nilai')) ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" class="num"><span class="visually-hidden">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): ?>
                        <tr>
                            <td class="text-muted"><?= e(format_angka($startNumber + $index)) ?></td>
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
                            <td>
                                <div class="table-actions">
                                    <a class="action-link"
                                       href="<?= e(app_url('products/edit.php?id=' . (int) $product['id'])) ?>">
                                        Edit
                                    </a>
                                    <form method="post"
                                          action="<?= e(app_url('products/delete.php')) ?>"
                                          data-confirm="Hapus barang <?= e($product['nama_barang']) ?> (<?= e($product['kode_barang']) ?>)? Tindakan ini tidak dapat dibatalkan."
                                          data-submit-once>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                        <button type="submit" class="action-link is-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="table-footer-info">
                Halaman <?= e(format_angka($page)) ?> dari <?= e(format_angka($totalPages)) ?>
            </span>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Navigasi halaman data barang">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(query_url(['page' => max(1, $page - 1)])) ?>" aria-label="Halaman sebelumnya">&lsaquo;</a>
                        </li>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        ?>

                        <?php if ($startPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(query_url(['page' => 1])) ?>">1</a>
                            </li>
                            <?php if ($startPage > 2): ?>
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                            <li class="page-item <?= $pageNumber === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(query_url(['page' => $pageNumber])) ?>"
                                   <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>>
                                    <?= e(format_angka($pageNumber)) ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(query_url(['page' => $totalPages])) ?>"><?= e(format_angka($totalPages)) ?></a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(query_url(['page' => min($totalPages, $page + 1)])) ?>" aria-label="Halaman berikutnya">&rsaquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
