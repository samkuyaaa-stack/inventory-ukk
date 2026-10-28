<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
}

if (!is_post() || !csrf_validate($_POST['csrf_token'] ?? null)) {
    set_flash('danger', 'Logout harus dilakukan melalui tombol keluar yang tersedia.');
    redirect('dashboard.php');
}

logout_user();

// Session baru yang kosong hanya dipakai untuk menampilkan pesan sukses.
mulai_session();
session_regenerate_id(true);
set_flash('success', 'Anda telah berhasil keluar dari aplikasi.');

redirect('auth/login.php');
