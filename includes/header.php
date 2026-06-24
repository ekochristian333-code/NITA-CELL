<?php
/**
 * includes/header.php
 * Header bersama: sidebar, topbar, dan pembuka layout
 * Variabel $pageTitle dan $activeMenu harus di-set sebelum include file ini.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/assets.php';
cekLogin();

if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
}
if (!isset($activeMenu)) {
    $activeMenu = '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Nita Cell</title>

    <!-- Favicon (logo Nita Cell, embedded base64) -->
    <link rel="icon" type="image/png" href="<?= FAVICON_URI ?>">
    <link rel="shortcut icon" type="image/png" href="<?= FAVICON_URI ?>">
    <link rel="apple-touch-icon" href="<?= FAVICON_URI ?>">

    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/font/bootstrap-icons.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app-wrapper">

    <!-- ===================== SIDEBAR ===================== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?= LOGO_URI ?>" alt="Logo Nita Cell" class="sidebar-logo">
            <h5 class="mb-0 fw-bold">NITA CELL</h5>
            <small class="text-white-50">Inventory System</small>
        </div>

        <nav class="nav flex-column flex-grow-1 py-2">
            <a href="dashboard.php" class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="nav-section-title">Master Data</div>
            <a href="data_barang.php" class="nav-link <?= $activeMenu === 'barang' ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> Data Barang
            </a>
            <a href="data_supplier.php" class="nav-link <?= $activeMenu === 'supplier' ? 'active' : '' ?>">
                <i class="bi bi-truck"></i> Data Supplier
            </a>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
            <a href="data_pengguna.php" class="nav-link <?= $activeMenu === 'pengguna' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Data Pengguna
            </a>
            <?php endif; ?>

            <div class="nav-section-title">Transaksi</div>
            <a href="barang_masuk.php" class="nav-link <?= $activeMenu === 'masuk' ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
            </a>
            <a href="barang_keluar.php" class="nav-link <?= $activeMenu === 'keluar' ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-up"></i> Barang Keluar
            </a>

            <div class="nav-section-title">Laporan</div>
            <a href="laporan_stok.php" class="nav-link <?= $activeMenu === 'laporan' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan Stok
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-2">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold small"><?= htmlspecialchars($_SESSION['nama']) ?></div>
                    <div class="small text-white-50"><?= htmlspecialchars($_SESSION['role']) ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-light w-100 mt-2">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ===================== MAIN CONTENT ===================== -->
    <div class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="sidebar-toggle-btn" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0 page-title"><?= htmlspecialchars($pageTitle) ?></h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small d-none d-md-inline">
                    <i class="bi bi-calendar3"></i> <?= date('d F Y') ?>
                </span>
                <?= badgeRole($_SESSION['role']) ?>
            </div>
        </header>

        <main class="content-area">
