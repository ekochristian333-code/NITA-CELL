<?php
/**
 * config/session.php
 * Mengatur session dan fungsi bantu autentikasi
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah pengguna sudah login.
 * Jika belum, redirect ke halaman login.
 */
function cekLogin() {
    if (!isset($_SESSION['id_user'])) {
        header('Location: ../index.php?error=session');
        exit;
    }
}

/**
 * Cek apakah pengguna memiliki role tertentu (misal hanya Admin).
 * Jika tidak sesuai, redirect ke dashboard dengan pesan error.
 *
 * @param string|array $allowedRoles role yang diizinkan, contoh: 'Admin' atau ['Admin','Karyawan']
 */
function cekRole($allowedRoles) {
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: dashboard.php?error=akses');
        exit;
    }
}

/**
 * Mendapatkan nama role dalam format badge warna untuk UI
 */
function badgeRole($role) {
    if ($role === 'Admin') {
        return '<span class="badge bg-primary">Admin</span>';
    }
    return '<span class="badge bg-info text-dark">Karyawan</span>';
}
