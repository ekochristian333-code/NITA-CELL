<?php
/**
 * index.php
 * Halaman Login Sistem Informasi Inventory Nita Cell
 * - Validasi username & password menggunakan password_verify()
 * - Session-based authentication
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/assets.php';

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['id_user'])) {
    header('Location: pages/dashboard.php');
    exit;
}

$error = '';

// Pesan jika redirect karena session habis
if (isset($_GET['error']) && $_GET['error'] === 'session') {
    $error = 'Sesi Anda telah berakhir. Silakan login kembali.';
}

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan Password wajib diisi.';
    } else {
        // Ambil data user berdasarkan username (prepared statement -> aman dari SQL Injection)
        $stmt = mysqli_prepare($koneksi, "SELECT id_user, username, password, nama, role FROM user WHERE username = ?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil -> set session
            $_SESSION['id_user']  = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['role']     = $user['role'];

            header('Location: pages/dashboard.php');
            exit;
        } else {
            $error = 'Username atau Password salah, silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Inventory Nita Cell</title>

    <!-- Favicon (logo Nita Cell, embedded base64) -->
    <link rel="icon" type="image/png" href="<?= FAVICON_URI ?>">
    <link rel="shortcut icon" type="image/png" href="<?= FAVICON_URI ?>">
    <link rel="apple-touch-icon" href="<?= FAVICON_URI ?>">

    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/font/bootstrap-icons.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

    <div class="login-wrapper">
        <div class="login-card shadow-lg">

            <div class="login-brand text-center">
                <div class="brand-logo">
                    <img src="<?= LOGO_URI ?>" alt="Logo Nita Cell" class="img-fluid">
                </div>
                <h3 class="mt-2 mb-0 fw-bold">NITA CELL</h3>
                <p class="text-muted mb-0">Sistem Informasi Inventory</p>
            </div>

            <hr class="my-4">

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Masukkan username" required autofocus
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Masukkan password" required>
                        <button type="button" class="input-group-text toggle-password" tabindex="-1">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-tosca w-100 mt-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> LOGIN MASUK
                </button>
            </form>

            <hr class="my-4">

            <div class="text-center small text-muted">
                <p class="mb-1"><i class="bi bi-info-circle"></i> Akun demo:</p>
                <p class="mb-0">Admin &nbsp;: <code>admin</code> / <code>admin123</code></p>
                <p class="mb-0">Karyawan : <code>budi01</code> / <code>admin123</code></p>
            </div>

        </div>

        <p class="text-center text-white mt-3 mb-0 small">
            &copy; <?= date('Y') ?> Nita Cell - Jl. Raya Pengasinan, Sawangan, Depok
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle tampil/sembunyi password
        document.querySelector('.toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            }
        });
    </script>
</body>
</html>
