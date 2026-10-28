<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? '';
$layout = $layout ?? 'app';
$bodyClass = $bodyClass ?? '';
$flashes = get_flashes();
$headerUser = current_user();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="description" content="Aplikasi inventaris barang sederhana untuk pengelolaan persediaan, kategori, dan laporan PDF.">
    <title><?= e($pageTitle) ?> &middot; <?= e(APP_NAME) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="<?= $layout === 'guest' ? 'auth-body' : 'app-body' ?> <?= e($bodyClass) ?>">

<?php if ($layout === 'guest'): ?>
    <div class="auth-page">
        <div class="auth-wrap">
            <a class="brand" href="<?= e(app_url('index.php')) ?>" aria-label="Beranda <?= e(APP_NAME) ?>">
                Inventaris<span>UKK</span>
            </a>

            <main class="auth-card">
                <?php render_flashes($flashes); ?>
<?php else: ?>
    <div class="app-shell">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <div class="app-shell-main">
            <header class="app-topbar">
                <div class="app-container topbar-inner">
                    <button class="sidebar-toggle"
                            type="button"
                            data-sidebar-toggle
                            aria-controls="appSidebar"
                            aria-expanded="false">
                        Menu
                    </button>
                    <a class="brand" href="<?= e(app_url('dashboard.php')) ?>">
                        Inventaris<span>UKK</span>
                    </a>
                    <span class="topbar-user"><?= e($headerUser['nama'] ?? 'Pengguna') ?></span>
                </div>
            </header>

            <main class="app-main">
                <div class="app-container content-wrap">
                    <?php render_flashes($flashes); ?>
<?php endif; ?>
