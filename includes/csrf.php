<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_validate(mixed $token): bool
{
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/*
 * Dipakai pada endpoint yang mengubah data (POST).
 * Jika token tidak valid, pengguna dikembalikan dengan pesan aman.
 */
function csrf_require(string $fallback = 'dashboard.php'): void
{
    if (!is_post() || !csrf_validate($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Permintaan ditolak karena token keamanan tidak valid. Silakan coba lagi.');
        redirect($fallback);
    }
}
