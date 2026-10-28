<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_guest();

$errors = [];
$old = [
    'nama' => '',
    'email' => '',
];

if (is_post()) {
    $old['nama'] = post_string('nama');
    $old['email'] = strtolower(post_string('email'));
    $password = post_raw('password');
    $passwordConfirmation = post_raw('password_confirm');

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errors['umum'] = 'Sesi keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    }

    if ($old['nama'] === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (mb_strlen($old['nama']) < 2) {
        $errors['nama'] = 'Nama lengkap minimal 2 karakter.';
    } elseif (mb_strlen($old['nama']) > 100) {
        $errors['nama'] = 'Nama lengkap maksimal 100 karakter.';
    }

    if ($old['email'] === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (mb_strlen($old['email']) > 150 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    } else {
        try {
            $statement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $statement->execute([$old['email']]);

            if ((int) $statement->fetchColumn() > 0) {
                $errors['email'] = 'Email sudah terdaftar. Gunakan email lain atau masuk ke akun Anda.';
            }
        } catch (PDOException $exception) {
            error_log('[REGISTER_CHECK] ' . $exception->getMessage());
            $errors['umum'] = 'Pendaftaran belum dapat diproses karena terjadi kesalahan pada server. Silakan coba lagi.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (str_contains($password, "\0")) {
        $errors['password'] = 'Password mengandung karakter yang tidak diizinkan.';
    } elseif (mb_strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter.';
    } elseif (mb_strlen($password) > 72) {
        $errors['password'] = 'Password maksimal 72 karakter.';
    }

    if ($passwordConfirmation === '') {
        $errors['password_confirm'] = 'Konfirmasi password wajib diisi.';
    } elseif ($password !== $passwordConfirmation) {
        $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $statement = $pdo->prepare(
                'INSERT INTO users (nama, email, password) VALUES (?, ?, ?)'
            );
            $statement->execute([$old['nama'], $old['email'], $hash]);

            set_flash('success', 'Akun berhasil dibuat. Silakan masuk menggunakan email dan password Anda.');
            redirect('auth/login.php');
        } catch (PDOException $exception) {
            error_log('[REGISTER] ' . $exception->getMessage());

            if (is_duplicate_key($exception)) {
                $errors['email'] = 'Email sudah terdaftar. Gunakan email lain atau masuk ke akun Anda.';
            } else {
                $errors['umum'] = 'Akun belum dapat disimpan karena terjadi kesalahan pada server. Silakan coba lagi.';
            }
        }
    }
}

$pageTitle = 'Buat Akun';
$layout = 'guest';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-head">
    <h1 class="auth-title">Buat akun pengguna</h1>
    <p class="auth-subtitle">Daftarkan akun untuk mulai mengelola persediaan barang.</p>
</div>

<?php if ($errors): ?>
    <div class="form-error-box form-error-box--flush" role="alert">
        <strong>Periksa kembali data berikut:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="auth-form" method="post" action="<?= e(app_url('auth/register.php')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="nama" class="form-label">Nama lengkap <span class="required-mark">*</span></label>
        <input type="text"
               class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
               id="nama"
               name="nama"
               value="<?= e($old['nama']) ?>"
               placeholder="Contoh: Samuel Pratama"
               maxlength="100"
               autocomplete="name"
               required>
        <?php if (isset($errors['nama'])): ?>
            <div class="invalid-feedback"><?= e($errors['nama']) ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
        <input type="email"
               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               id="email"
               name="email"
               value="<?= e($old['email']) ?>"
               placeholder="nama@email.com"
               maxlength="150"
               autocomplete="email"
               required>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password <span class="required-mark">*</span></label>
        <div class="password-field">
            <input type="password"
                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   id="password"
                   name="password"
                   placeholder="Minimal 8 karakter"
                   minlength="8"
                   maxlength="72"
                   autocomplete="new-password"
                   required>
            <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
            <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-4">
        <label for="password_confirm" class="form-label">Konfirmasi password <span class="required-mark">*</span></label>
        <div class="password-field">
            <input type="password"
                   class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                   id="password_confirm"
                   name="password_confirm"
                   placeholder="Ulangi password"
                   minlength="8"
                   maxlength="72"
                   autocomplete="new-password"
                   required>
            <button class="password-toggle" type="button" data-password-toggle="password_confirm" aria-label="Tampilkan konfirmasi password" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
            <?php if (isset($errors['password_confirm'])): ?>
                <div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">Buat akun</button>
</form>

<p class="auth-switch">
    Sudah memiliki akun? <a href="<?= e(app_url('auth/login.php')) ?>">Masuk di sini</a>
</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
