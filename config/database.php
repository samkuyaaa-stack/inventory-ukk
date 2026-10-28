<?php
declare(strict_types=1);

/*
 * Konfigurasi aplikasi dan koneksi database.
 * Sesuaikan nilai konstanta di bawah dengan pengaturan MySQL lokal.
 * Pada XAMPP bawaan, user root biasanya tidak memiliki password.
 */

const APP_NAME = 'Inventaris UKK';
const APP_TIMEZONE = 'Asia/Jakarta';
const APP_DEBUG = false;

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'inventory_ukk';
const DB_USER = 'root';
const DB_PASS = '';

const LOW_STOCK_THRESHOLD = 5;

define('APP_ROOT', dirname(__DIR__));

date_default_timezone_set(APP_TIMEZONE);
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', APP_DEBUG ? '1' : '0');

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    DB_HOST,
    DB_PORT,
    DB_NAME
);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Samakan time zone session MySQL dengan time zone aplikasi agar
    // created_at dan updated_at konsisten saat ditampilkan.
    try {
        $offset = (new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE)))->format('P');
        $pdo->exec('SET time_zone = ' . $pdo->quote($offset));
    } catch (PDOException $exception) {
        error_log('[DATABASE] Time zone tidak dapat diatur: ' . $exception->getMessage());
    }
} catch (PDOException $exception) {
    error_log('[DATABASE] Koneksi gagal: ' . $exception->getMessage());
    http_response_code(500);
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Database Tidak Tersedia - <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 24px;
                background: #f6f6f9;
                color: #171717;
                font-family: "Segoe UI", Arial, sans-serif;
            }
            .card {
                width: 100%;
                max-width: 560px;
                padding: 32px;
                background: #ffffff;
                border: 1px solid #d9d9de;
                border-radius: 4px;
            }
            .mark {
                display: grid;
                place-items: center;
                width: 44px;
                height: 44px;
                margin-bottom: 18px;
                border-radius: 4px;
                background: #6C3CE9;
                color: #ffffff;
                font-size: 22px;
                font-weight: 700;
            }
            h1 { margin: 0 0 10px; font-size: 24px; }
            p { margin: 0 0 18px; color: #4b5563; line-height: 1.65; }
            ol { margin: 0; padding-left: 22px; color: #4b5563; line-height: 1.9; }
            code {
                padding: 1px 5px;
                border-radius: 3px;
                background: #f1f0f7;
                color: #5526C7;
                font-family: Consolas, monospace;
            }
            .note {
                margin-top: 20px;
                padding: 12px 14px;
                border-left: 3px solid #6C3CE9;
                background: #f7f4ff;
                color: #4b3a7a;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <main class="card">
            <div class="mark">!</div>
            <h1>Aplikasi belum dapat terhubung ke database</h1>
            <p>
                Koneksi ke MySQL gagal. Periksa langkah berikut, lalu muat ulang halaman ini.
            </p>
            <ol>
                <li>Pastikan modul <strong>MySQL</strong> pada XAMPP sudah berjalan.</li>
                <li>Impor file <code>database.sql</code> melalui phpMyAdmin.</li>
                <li>Periksa host, port, nama database, user, dan password di <code>config/database.php</code>.</li>
            </ol>
            <div class="note">
                Detail teknis sudah dicatat ke log PHP dan tidak ditampilkan di halaman ini
                untuk menjaga keamanan informasi.
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}
