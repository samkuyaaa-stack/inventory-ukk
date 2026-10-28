-- ============================================================
-- Inventaris UKK - Skema Database
-- Impor file ini melalui phpMyAdmin atau CLI MySQL.
--
-- PERINGATAN:
-- File ini menghapus tabel users, categories, dan products
-- pada database inventory_ukk agar struktur dapat dibuat ulang
-- secara bersih. Jangan impor ulang jika sudah ada data penting.
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS inventory_ukk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE inventory_ukk;

DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- Tabel pengguna
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel kategori
-- ------------------------------------------------------------
CREATE TABLE categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_kategori VARCHAR(80) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_nama (nama_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel barang
-- category_id menggunakan ON DELETE RESTRICT agar kategori
-- yang masih dipakai tidak dapat dihapus secara tidak sengaja.
-- ------------------------------------------------------------
CREATE TABLE products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_barang VARCHAR(30) NOT NULL,
    nama_barang VARCHAR(150) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    harga DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    stok INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_kode (kode_barang),
    KEY idx_products_category (category_id),
    KEY idx_products_nama (nama_barang),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_products_harga CHECK (harga >= 0),
    CONSTRAINT chk_products_stok CHECK (stok >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Kategori awal (diperlukan agar form barang dapat digunakan)
-- ------------------------------------------------------------
INSERT INTO categories (id, nama_kategori) VALUES
    (1, 'Elektronik'),
    (2, 'Alat Tulis Kantor'),
    (3, 'Aksesoris Komputer'),
    (4, 'Peralatan Pendukung');

-- ------------------------------------------------------------
-- DATA CONTOH OPSIONAL
-- Hapus blok INSERT di bawah jika ingin memulai dengan
-- database barang yang kosong. Data ini bukan data hardcoded
-- di dalam aplikasi; semuanya tetap dibaca dari database.
-- ------------------------------------------------------------
INSERT INTO products
    (kode_barang, nama_barang, category_id, harga, stok, created_at, updated_at)
VALUES
    ('BRG-001', 'Laptop Lenovo ThinkPad E14', 1, 11500000.00, 8, NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 12 DAY),
    ('BRG-002', 'Monitor Dell 24 Inch', 1, 2350000.00, 12, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY),
    ('BRG-003', 'Keyboard Mechanical Rexus', 3, 475000.00, 25, NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY),
    ('BRG-004', 'Mouse Logitech M331', 3, 210000.00, 4, NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
    ('BRG-005', 'Kertas HVS A4 80gsm', 2, 55000.00, 120, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
    ('BRG-006', 'Tinta Printer Epson 003 Hitam', 2, 89000.00, 2, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
    ('BRG-007', 'Proyektor Epson EB-X06', 1, 6200000.00, 3, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
    ('BRG-008', 'Kabel HDMI 2 Meter', 4, 65000.00, 30, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);
