<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'nama' => (string) ($_SESSION['user_nama'] ?? ''),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
    ];
}

function login_user(array $user): void
{
    // Mencegah session fixation dengan ID session baru setelah login.
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_nama'] = (string) $user['nama'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['login_at'] = time();

    // Token CSRF lama tidak dipakai lagi setelah pergantian session.
    unset($_SESSION['csrf_token']);
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan login terlebih dahulu untuk membuka halaman tersebut.');
        redirect('auth/login.php');
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}
