<?php
/**
 * config/database.php
 * Koneksi ke database MySQL menggunakan mysqli (prepared statement ready)
 */

// ==== KONFIGURASI DATABASE ====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_nitacell');

// Membuat koneksi
$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

// Set karakter set ke utf8mb4 agar mendukung karakter khusus
mysqli_set_charset($koneksi, 'utf8mb4');
