# Inventaris UKK

Aplikasi web inventory barang berbasis **PHP Native**, **MySQL**, **PDO**, **Bootstrap 5**, dan **FPDF**. Aplikasi dibuat untuk latihan dan persiapan UKK Junior Web Developer, dengan tampilan administrasi minimalis berwarna hitam, putih, dan ungu.

## Fitur

- Registrasi akun dengan validasi server dan password hash.
- Login, logout, proteksi halaman privat, dan regenerasi session ID.
- Dashboard dengan statistik dinamis dari database:
  - jumlah jenis barang,
  - total stok,
  - total nilai persediaan,
  - jumlah kategori,
  - daftar barang terbaru,
  - ringkasan stok rendah.
- CRUD barang lengkap dengan validasi, pencarian, filter kategori, pengurutan aman, dan pagination.
- Kode barang dibuat otomatis dari primary key setelah barang disimpan, dengan format `BRG-001`, `BRG-002`, dan seterusnya.
- Kode barang tidak dapat diisi manual dan tidak berubah saat data barang diedit.
- Konfirmasi sebelum menghapus barang.
- Laporan persediaan yang dapat dicetak sebagai halaman atau PDF.
- Total laporan dihitung dari data database terbaru.
- Token CSRF untuk registrasi, login, logout, tambah, edit, dan hapus data.
- Pencegahan pengiriman formulir ganda serta pola POST-Redirect-GET.
- Tampilan responsif dengan sidebar tetap di desktop dan sidebar geser di layar kecil.

## Teknologi

- PHP Native **8.1+** (diuji pada PHP 8.2.12 bawaan XAMPP).
- MySQL/MariaDB (diuji pada MariaDB 10.4.32 bawaan XAMPP).
- PDO MySQL dengan prepared statements.
- Bootstrap 5.3 dan Bootstrap Icons melalui CDN.
- Font sistem, tanpa Google Fonts.
- FPDF 1.8.6 yang sudah disertakan di `vendor/fpdf`.
- Session PHP.

Koneksi internet diperlukan untuk memuat Bootstrap dan Bootstrap Icons dari CDN. Font memakai font sistem sehingga tidak bergantung pada Google Fonts. Tanpa internet, fungsi PHP tetap berjalan, tetapi tampilan Bootstrap tidak akan optimal. FPDF bersifat lokal sehingga pembuatan PDF tidak bergantung pada CDN.

## Struktur Folder

```text
inventory-ukk/
|-- assets/
|   |-- css/style.css
|   `-- js/script.js
|-- auth/
|   |-- login.php
|   |-- register.php
|   `-- logout.php
|-- config/
|   `-- database.php
|-- includes/
|   |-- auth.php
|   |-- csrf.php
|   |-- footer.php
|   |-- functions.php
|   |-- header.php
|   `-- sidebar.php
|-- products/
|   |-- _form.php
|   |-- create.php
|   |-- delete.php
|   |-- edit.php
|   |-- index.php
|   |-- store.php
|   `-- update.php
|-- reports/
|   |-- index.php
|   `-- pdf.php
|-- vendor/fpdf/
|-- dashboard.php
|-- database.sql
|-- index.php
|-- .gitignore
|-- .htaccess
`-- README.md
```

Navigasi utama berada di `includes/sidebar.php`. Di layar kecil, sidebar dibuka melalui tombol **Menu** pada topbar.

## Instalasi di XAMPP

1. Letakkan folder `inventory-ukk` di dalam `C:\xampp\htdocs`. Jika proyek ini sudah berada di lokasi tersebut, lewati langkah ini.
2. Buka **XAMPP Control Panel**, lalu jalankan **Apache** dan **MySQL**.
3. Buka `http://localhost/phpmyadmin`.
4. Pilih menu **Import**, pilih file `database.sql` dari folder aplikasi, lalu klik **Import**.
   - File SQL akan membuat database `inventory_ukk`.
   - File SQL juga menghapus tabel lama dengan nama yang sama. Jangan impor ulang jika sudah ada data penting.
5. Periksa konfigurasi koneksi di `config/database.php`:
   ```php
   const DB_HOST = '127.0.0.1';
   const DB_PORT = 3306;
   const DB_NAME = 'inventory_ukk';
   const DB_USER = 'root';
   const DB_PASS = '';
   ```
   Sesuaikan `DB_USER` dan `DB_PASS` jika MySQL lokal memakai password.
6. Buka aplikasi melalui:
   ```text
   http://localhost/inventory-ukk/
   ```
   Membuka alamat folder tersebut akan otomatis menjalankan `index.php`. Jika belum login, pengguna diarahkan ke halaman login; jika sudah login, diarahkan ke dashboard. Jangan membuka folder `C:\xampp\htdocs\inventory-ukk` langsung dari Windows Explorer karena PHP hanya berjalan melalui Apache.
7. Klik **Daftar sekarang**, lalu buat akun pertama. Tidak ada akun administrator atau akun uji bawaan di dalam database.
8. Setelah registrasi berhasil, masuk menggunakan email dan password tersebut.

### Impor database melalui terminal (opsional)

Jalankan dari folder proyek jika ingin memakai CLI MySQL:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root < database.sql
```

Jika user MySQL memiliki password, tambahkan opsi `-p` dan masukkan password saat diminta.

## Konfigurasi FPDF

FPDF **sudah tersedia** di `vendor/fpdf` dan tidak perlu dipasang ulang. Aplikasi memuat file berikut:

```text
vendor/fpdf/fpdf.php
```

Jika folder tersebut hilang, unduh rilis resmi FPDF 1.8.6 dari `http://www.fpdf.org`, ekstrak, lalu letakkan `fpdf.php` beserta folder `font` di `vendor/fpdf`. Tidak diperlukan Composer.

Jika library tidak ditemukan:

- halaman laporan menampilkan peringatan,
- tombol PDF dinonaktifkan,
- endpoint `reports/pdf.php` menampilkan halaman error yang jelas, bukan PDF palsu.

## Database

Tabel utama yang dibuat oleh `database.sql`:

| Tabel | Keterangan |
| --- | --- |
| `users` | Akun pengguna. Email unik, password disimpan sebagai hash. |
| `categories` | Kategori barang. Nama kategori unik. |
| `products` | Barang dengan kode unik, harga `DECIMAL(15,2)`, dan stok bilangan bulat nonnegatif. |

Relasi `products.category_id` menggunakan foreign key dengan aturan `ON DELETE RESTRICT`, sehingga kategori yang masih dipakai tidak dapat dihapus tanpa sengaja.

Kode barang dibuat otomatis di server. Saat barang baru disimpan, aplikasi memakai kode sementara di dalam transaksi, lalu menggantinya dengan `BRG-` diikuti primary key minimal tiga digit. Jika kode hasil format sudah dipakai, aplikasi menambahkan suffix `-01`, `-02`, dan seterusnya.

Kategori awal yang disertakan: Elektronik, Alat Tulis Kantor, Aksesoris Komputer, dan Peralatan Pendukung.

File SQL juga menyertakan **data contoh opsional** berupa delapan barang dengan kode `BRG-001` sampai `BRG-008`. Hapus blok `INSERT INTO products` di `database.sql` sebelum impor jika ingin memulai dengan daftar barang kosong. Data contoh tetap dibaca dari database, bukan ditulis langsung di halaman.

## Keamanan yang Diterapkan

- Seluruh query menggunakan PDO prepared statements.
- Password menggunakan `password_hash()` dan `password_verify()`.
- Output dari database dan input pengguna di-escape dengan `htmlspecialchars()`.
- Token CSRF divalidasi pada semua operasi yang mengubah data.
- Session ID diregenerasi setelah login; cookie `HttpOnly` dan `SameSite=Lax`.
- Halaman privat memeriksa status login di server.
- Logout menghancurkan session dan cookie session.
- ID dan data yang tidak ditemukan ditangani tanpa menampilkan detail database.
- Penghapusan barang hanya menerima metode POST.
- Detail error database dicatat ke log server dan tidak ditampilkan ke pengguna.

Registrasi bersifat terbuka agar mudah dipakai untuk latihan. Untuk penggunaan produksi, tambahkan verifikasi email, pembatasan percobaan login, peran administrator, dan HTTPS.

## Pengujian yang Sudah Dilakukan

Pengujian dijalankan pada Apache, PHP 8.2.12, dan MariaDB 10.4.32:

- `php -l` untuk seluruh file PHP: tidak ada syntax error.
- Pengujian alur HTTP terotomatisasi: 54 pemeriksaan lulus, mencakup registrasi, login, proteksi halaman, statistik dashboard, pencarian, filter, pengurutan, CRUD, validasi, CSRF, kode barang otomatis, halaman error 400/404, laporan, PDF, dan logout.
- Pengujian khusus kode barang otomatis: 11 pemeriksaan lulus, termasuk kode berbasis primary key, kode unik, kode read-only saat edit, dan kode yang tidak berubah saat update.
- 6 pemeriksaan pagination multi-halaman, termasuk koreksi nomor halaman di luar batas.
- 8 pemeriksaan kondisi database kosong: dashboard nol, empty state barang, laporan kosong, dan PDF tanpa data.
- 5 pemeriksaan ketika FPDF tidak tersedia: tombol dinonaktifkan dan endpoint PDF memberi error yang jelas.
- PDF diuji dengan unduhan nyata, valid diawali header `%PDF`, termasuk laporan multi-halaman untuk 108 barang.
- Tampilan desktop dan mobile diperiksa melalui tangkapan layar Chrome headless.
- Sidebar mobile diuji: tombol menu membuka sidebar dan mengubah `aria-expanded` menjadi `true`.
- Pemeriksaan console browser: tidak ada JavaScript exception, error console, atau respons lokal gagal pada halaman utama.
- Konfirmasi batal hapus diuji dan tidak mengirim formulir.
- Data uji dihapus kembali setelah pengujian agar data contoh tetap utuh.

Pengujian browser interaktif tetap disarankan sebelum presentasi UKK, terutama untuk mencoba tampilan pada perangkat dan ukuran layar yang akan digunakan.

## Alur Penggunaan Singkat

1. Buka aplikasi, lalu registrasi akun.
2. Login dan lihat statistik pada dashboard.
3. Buka **Data Barang** untuk mencari, memfilter, dan mengurutkan data.
4. Tambah barang; kode barang akan dibuat otomatis oleh aplikasi.
5. Edit atau hapus barang. Kode barang tidak berubah saat edit.
6. Buka **Laporan** untuk melihat total terbaru.
7. Klik **Cetak PDF** atau **Cetak halaman**.
8. Keluar melalui tombol **Keluar** di bagian bawah sidebar.

## Penanganan Error Umum

| Masalah | Penyebab dan solusi |
| --- | --- |
| "Aplikasi belum dapat terhubung ke database" | MySQL belum berjalan atau konfigurasi salah. Jalankan MySQL di XAMPP dan periksa `config/database.php`. |
| `Unknown database 'inventory_ukk'` | Database belum dibuat. Impor `database.sql` melalui phpMyAdmin. |
| `Table ... doesn't exist` | Impor database gagal atau tidak lengkap. Impor ulang `database.sql` pada database yang benar. |
| Halaman login selalu kembali ke login | Cookie session diblokir atau session gagal dimulai. Pastikan membuka aplikasi melalui `http://localhost`, bukan file langsung. |
| Tombol PDF nonaktif | File `vendor/fpdf/fpdf.php` tidak ditemukan. Letakkan FPDF pada folder tersebut. |
| Laporan kosong | Database belum memiliki barang. Tambahkan barang melalui menu Data Barang. |
| Error 404 saat membuka halaman | Periksa URL. Aplikasi berada di `http://localhost/inventory-ukk/`, bukan di root `localhost`. |
| Kode barang tidak dapat diisi | Kode dibuat otomatis setelah barang disimpan. Tidak perlu dan tidak bisa mengisi kode secara manual. |
| Harga atau stok ditolak | Harga dan stok harus berupa angka nol atau lebih. Stok harus bilangan bulat. |

## Catatan untuk Latihan UKK

- Semua perhitungan dashboard dan laporan berasal dari query database.
- Perubahan barang langsung memengaruhi dashboard dan laporan.
- Aplikasi tidak memakai framework PHP, React, Node.js, atau library frontend selain Bootstrap dan ikon melalui CDN.
- Data contoh tidak wajib dipertahankan; hapus melalui antarmuka atau hapus blok insert pada `database.sql` sebelum impor.

