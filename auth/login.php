<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_guest();

$errors = [];
$email = '';

if (is_post()) {
    $email = strtolower(post_string('email'));
    $password = post_raw('password');

    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    }

    if ($email === '') {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if ($password === '') {
        $errors[] = 'Password wajib diisi.';
    } elseif (str_contains($password, "\0")) {
        $errors[] = 'Email atau password salah.';
    }

    if (!$errors) {
        try {
            $statement = $pdo->prepare(
                'SELECT id, nama, email, password FROM users WHERE email = ? LIMIT 1'
            );
            $statement->execute([$email]);
            $user = $statement->fetch();

            if ($user && password_verify($password, (string) $user['password'])) {
                login_user($user);
                set_flash('success', 'Selamat datang, ' . $user['nama'] . '.');
                redirect('dashboard.php');
            }

            $errors[] = 'Email atau password salah.';
        } catch (PDOException $exception) {
            error_log('[LOGIN] ' . $exception->getMessage());
            $errors[] = 'Login belum dapat diproses karena terjadi kesalahan pada server.';
        }
    }
}

$pageTitle = 'Masuk';
$layout = 'guest';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-head">
    <h1 class="auth-title">Masuk ke akun Anda</h1>
    <p class="auth-subtitle">Gunakan email dan password yang sudah terdaftar untuk melanjutkan.</p>
</div>

<?php if ($errors): ?>
    <div class="app-alert alert-danger" role="alert">
        <div class="alert-message">
            <?php foreach ($errors as $index => $error): ?>
                <?= $index > 0 ? '<br>' : '' ?><?= e($error) ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<form class="auth-form" method="post" action="<?= e(app_url('auth/login.php')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
        <input type="email"
               class="form-control"
               id="email"
               name="email"
               value="<?= e($email) ?>"
               placeholder="nama@email.com"
               maxlength="150"
               autocomplete="email"
               autofocus
               required>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Password <span class="required-mark">*</span></label>
        <div class="password-field">
            <input type="password"
                   class="form-control"
                   id="password"
                   name="password"
                   placeholder="Masukkan password"
                   maxlength="72"
                   autocomplete="current-password"
                   required>
            <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">Masuk</button>
</form>

<p class="auth-switch">
    Belum memiliki akun? <a href="<?= e(app_url('auth/register.php')) ?>">Daftar sekarang</a>
</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
