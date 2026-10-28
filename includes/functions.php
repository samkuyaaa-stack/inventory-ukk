<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

/*
 * Session aplikasi.
 * Cookie tidak dapat dibaca JavaScript, tidak dikirim lintas situs,
 * dan otomatis memakai HTTPS bila tersedia.
 */
function mulai_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';

    // Tolak ID session yang tidak pernah dibuat oleh server.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('INVENTORYUKK');
    session_start();
}

mulai_session();

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/*
 * Escape semua output yang berasal dari input pengguna atau database.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/*
 * Membentuk URL absolut terhadap folder aplikasi, sehingga aplikasi
 * tetap berjalan baik di htdocs/inventory-ukk maupun di root domain.
 */
function app_url(string $path = ''): string
{
    static $basePath = null;

    if ($basePath === null) {
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
        $appRoot = realpath(APP_ROOT);
        $basePath = '';

        if ($documentRoot && $appRoot && strncmp($appRoot, $documentRoot, strlen($documentRoot)) === 0) {
            $basePath = rtrim(str_replace('\\', '/', substr($appRoot, strlen($documentRoot))), '/');
        }
    }

    return $basePath . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

/*
 * Dipakai untuk password: nilai tidak boleh di-trim, tetapi tetap
 * harus berupa string agar input berbentuk array tidak memicu warning.
 */
function post_raw(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? $value : '';
}

/*
 * Mengembalikan ID positif atau null jika tidak valid.
 */
function valid_id(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    return $id === false ? null : (int) $id;
}

/* ------------------------------------------------------------------
 * Flash message dan state formulir
 * ------------------------------------------------------------------ */

function set_flash(string $type, string $message): void
{
    $allowed = ['success', 'danger', 'warning', 'info'];
    if (!in_array($type, $allowed, true)) {
        $type = 'info';
    }

    $_SESSION['_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return is_array($flashes) ? $flashes : [];
}

function render_flashes(array $flashes): void
{
    $types = ['success', 'danger', 'warning', 'info'];

    foreach ($flashes as $flash) {
        $type = (string) ($flash['type'] ?? 'info');
        if (!in_array($type, $types, true)) {
            $type = 'info';
        }

        $message = (string) ($flash['message'] ?? '');

        if ($message === '') {
            continue;
        }
        ?>
        <div class="alert alert-<?= e($type) ?> app-alert alert-dismissible fade show" role="alert">
            <div class="alert-message"><?= e($message) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
        <?php
    }
}

function flash_form(array $errors, array $old, string $key = 'form'): void
{
    $_SESSION['_form'][$key] = [
        'errors' => $errors,
        'old' => $old,
    ];
}

function pull_form(string $key = 'form'): array
{
    $state = $_SESSION['_form'][$key] ?? null;
    unset($_SESSION['_form'][$key]);

    return [
        'errors' => is_array($state['errors'] ?? null) ? $state['errors'] : [],
        'old' => is_array($state['old'] ?? null) ? $state['old'] : [],
    ];
}

function old_input(array $old, string $key, string $default = ''): string
{
    return array_key_exists($key, $old) ? (string) $old[$key] : $default;
}

/* ------------------------------------------------------------------
 * Format tampilan
 * ------------------------------------------------------------------ */

function format_rupiah(mixed $value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function format_angka(mixed $value): string
{
    return number_format((float) $value, 0, ',', '.');
}

function nilai_persediaan(mixed $harga, mixed $stok): float
{
    return (float) $harga * (int) $stok;
}

function kelas_stok(int $stok): string
{
    if ($stok === 0) {
        return 'is-empty';
    }

    if ($stok <= LOW_STOCK_THRESHOLD) {
        return 'is-low';
    }

    return '';
}

function format_tanggal(?string $datetime, bool $denganWaktu = false): string
{
    if (!$datetime) {
        return '-';
    }

    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return '-';
    }

    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    $hasil = date('d', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);

    if ($denganWaktu) {
        $hasil .= ', ' . date('H:i', $timestamp);
    }

    return $hasil;
}

function format_tanggal_panjang(?string $datetime): string
{
    $timestamp = $datetime ? strtotime($datetime) : time();

    if ($timestamp === false) {
        $timestamp = time();
    }

    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return date('d', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

function inisial_nama(string $nama): string
{
    $bagian = preg_split('/\s+/u', trim($nama)) ?: [];
    $inisial = '';

    foreach (array_slice($bagian, 0, 2) as $kata) {
        $inisial .= mb_strtoupper(mb_substr($kata, 0, 1));
    }

    return $inisial !== '' ? $inisial : 'U';
}

/* ------------------------------------------------------------------
 * Query kategori dan barang
 * ------------------------------------------------------------------ */

function get_categories(PDO $pdo): array
{
    return $pdo->query('SELECT id, nama_kategori FROM categories ORDER BY nama_kategori ASC')->fetchAll();
}

function find_category(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare('SELECT id, nama_kategori FROM categories WHERE id = ? LIMIT 1');
    $statement->execute([$id]);
    $category = $statement->fetch();

    return $category ?: null;
}

function get_product_by_id(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare(
        'SELECT p.id, p.kode_barang, p.nama_barang, p.category_id, p.harga, p.stok,
                p.created_at, p.updated_at, c.nama_kategori
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         WHERE p.id = ?
         LIMIT 1'
    );
    $statement->execute([$id]);
    $product = $statement->fetch();

    return $product ?: null;
}

function format_kode_barang(int $id): string
{
    return 'BRG-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

function kode_barang_exists(PDO $pdo, string $kode, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM products WHERE kode_barang = ?';
    $params = [$kode];

    if ($ignoreId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $ignoreId;
    }

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return (int) $statement->fetchColumn() > 0;
}

function generate_kode_barang(PDO $pdo, int $id): string
{
    $base = format_kode_barang($id);
    $kode = $base;
    $suffix = 0;

    while (kode_barang_exists($pdo, $kode, $id)) {
        $suffix++;
        $kode = $base . '-' . str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
    }

    return $kode;
}

function get_dashboard_stats(PDO $pdo): array
{
    $ringkasan = $pdo->query(
        'SELECT COUNT(*) AS total_jenis,
                COALESCE(SUM(stok), 0) AS total_stok,
                COALESCE(SUM(harga * stok), 0) AS total_nilai
         FROM products'
    )->fetch();

    $totalKategori = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

    return [
        'total_jenis' => (int) ($ringkasan['total_jenis'] ?? 0),
        'total_stok' => (int) ($ringkasan['total_stok'] ?? 0),
        'total_nilai' => (float) ($ringkasan['total_nilai'] ?? 0),
        'total_kategori' => $totalKategori,
    ];
}

function get_latest_products(PDO $pdo, int $limit = 5): array
{
    $limit = max(1, min($limit, 20));

    $statement = $pdo->prepare(
        'SELECT p.id, p.kode_barang, p.nama_barang, p.harga, p.stok, p.created_at,
                c.nama_kategori
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         ORDER BY p.created_at DESC, p.id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function get_low_stock_products(PDO $pdo, int $threshold = LOW_STOCK_THRESHOLD, int $limit = 5): array
{
    $limit = max(1, min($limit, 20));

    $statement = $pdo->prepare(
        'SELECT p.id, p.kode_barang, p.nama_barang, p.stok, c.nama_kategori
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         WHERE p.stok <= :threshold
         ORDER BY p.stok ASC, p.nama_barang ASC
         LIMIT :limit'
    );
    $statement->bindValue(':threshold', max(0, $threshold), PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function get_report_products(PDO $pdo): array
{
    return $pdo->query(
        'SELECT p.id, p.kode_barang, p.nama_barang, p.harga, p.stok,
                c.nama_kategori
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         ORDER BY c.nama_kategori ASC, p.nama_barang ASC'
    )->fetchAll();
}

/* ------------------------------------------------------------------
 * Validasi formulir barang
 * ------------------------------------------------------------------ */

function validate_product_data(PDO $pdo, array $input): array
{
    $raw = static function (string $key) use ($input): string {
        $value = $input[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    };

    $data = [
        'nama_barang' => $raw('nama_barang'),
        'category_id' => $raw('category_id'),
        'harga' => $raw('harga'),
        'stok' => $raw('stok'),
    ];

    $errors = [];

    if ($data['nama_barang'] === '') {
        $errors['nama_barang'] = 'Nama barang wajib diisi.';
    } elseif (mb_strlen($data['nama_barang']) < 2) {
        $errors['nama_barang'] = 'Nama barang minimal 2 karakter.';
    } elseif (mb_strlen($data['nama_barang']) > 150) {
        $errors['nama_barang'] = 'Nama barang maksimal 150 karakter.';
    }

    $categoryId = valid_id($data['category_id']);
    if ($categoryId === null) {
        $errors['category_id'] = 'Kategori wajib dipilih.';
    } elseif (!find_category($pdo, $categoryId)) {
        $errors['category_id'] = 'Kategori yang dipilih tidak ditemukan.';
    }

    $harga = filter_var($data['harga'], FILTER_VALIDATE_FLOAT);
    if ($data['harga'] === '') {
        $errors['harga'] = 'Harga wajib diisi.';
    } elseif ($harga === false) {
        $errors['harga'] = 'Harga harus berupa angka yang valid.';
    } elseif ($harga < 0) {
        $errors['harga'] = 'Harga tidak boleh negatif.';
    } elseif ($harga > 9999999999999.99) {
        $errors['harga'] = 'Harga melebihi batas maksimum.';
    }

    $stok = filter_var($data['stok'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'max_range' => 4294967295],
    ]);
    if ($data['stok'] === '') {
        $errors['stok'] = 'Stok wajib diisi.';
    } elseif ($stok === false) {
        $errors['stok'] = 'Stok harus berupa bilangan bulat nol atau lebih.';
    }

    return [
        'errors' => $errors,
        'data' => $data,
    ];
}

function is_duplicate_key(PDOException $exception): bool
{
    return $exception->getCode() === '23000'
        || (int) ($exception->errorInfo[1] ?? 0) === 1062;
}

/* ------------------------------------------------------------------
 * Navigasi dan URL
 * ------------------------------------------------------------------ */

function query_string(array $overrides = []): string
{
    $params = $_GET;

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    return http_build_query($params);
}

function query_url(array $overrides = []): string
{
    $query = query_string($overrides);

    return $query === '' ? '?' : '?' . $query;
}

/*
 * Halaman error yang tetap memakai layout aplikasi.
 */
function render_error_page(int $statusCode, string $title, string $message, string $activePage = ''): never
{
    http_response_code($statusCode);

    $pageTitle = $title;
    $activePage = $activePage;
    $layout = 'app';

    require __DIR__ . '/header.php';
    ?>
    <div class="page-heading">
        <div>
            <h1 class="page-title"><?= e($title) ?></h1>
            <p class="page-description">Kesalahan <?= (int) $statusCode ?></p>
        </div>
    </div>

    <section class="app-card">
        <div class="empty-state">
            <h2 class="empty-title">Permintaan tidak dapat diproses</h2>
            <p class="empty-text"><?= e($message) ?></p>
            <div class="empty-actions">
                <a href="<?= e(app_url('dashboard.php')) ?>" class="btn btn-primary">Kembali ke dashboard</a>
                <a href="<?= e(app_url('products/index.php')) ?>" class="btn btn-outline-secondary">Lihat data barang</a>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . '/footer.php';
    exit;
}
