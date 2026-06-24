<?php
/**
 * pages/data_pengguna.php
 * Manajemen akun pengguna - hanya bisa diakses oleh Admin
 */

$pageTitle  = 'Data Pengguna';
$activeMenu = 'pengguna';
require_once __DIR__ . '/../includes/header.php';
cekRole('Admin');  // Hanya Admin yang boleh masuk

$sukses = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    /* Hapus pengguna */
    if ($aksi === 'hapus') {
        $id = (int)$_POST['id_user'];
        if ($id === (int)$_SESSION['id_user']) {
            $error = 'Tidak dapat menghapus akun sendiri.';
        } else {
            $stmt = mysqli_prepare($koneksi, "DELETE FROM user WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $sukses = 'Pengguna berhasil dihapus.';
        }
    }

    /* Tambah / Edit */
    if (in_array($aksi, ['tambah', 'edit'])) {
        $id       = (int)($_POST['id_user']  ?? 0);
        $username = trim($_POST['username']   ?? '');
        $nama     = trim($_POST['nama']       ?? '');
        $role     = $_POST['role']            ?? 'Karyawan';
        $password = $_POST['password']        ?? '';
        $passConf = $_POST['password_confirm'] ?? '';

        if ($username === '' || $nama === '') {
            $error = 'Username dan nama wajib diisi.';
        } elseif ($aksi === 'tambah' && $password === '') {
            $error = 'Password wajib diisi untuk akun baru.';
        } elseif ($password !== '' && $password !== $passConf) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            if ($aksi === 'tambah') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($koneksi,
                    "INSERT INTO user (username, password, nama, role) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'ssss', $username, $hash, $nama, $role);
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($koneksi,
                        "UPDATE user SET username=?, nama=?, role=?, password=? WHERE id_user=?");
                    mysqli_stmt_bind_param($stmt, 'ssssi', $username, $nama, $role, $hash, $id);
                } else {
                    $stmt = mysqli_prepare($koneksi,
                        "UPDATE user SET username=?, nama=?, role=? WHERE id_user=?");
                    mysqli_stmt_bind_param($stmt, 'sssi', $username, $nama, $role, $id);
                }
            }
            if (mysqli_stmt_execute($stmt)) {
                $sukses = $aksi === 'tambah' ? 'Akun berhasil dibuat.' : 'Akun berhasil diperbarui.';
            } else {
                $error = 'Gagal: ' . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$hasilUser = mysqli_query($koneksi, "SELECT id_user, username, nama, role, created_at FROM user ORDER BY role, nama");
?>

<?php if ($sukses): ?>
    <div class="alert alert-success alert-dismissible alert-auto-hide">
        <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($sukses) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="resetForm()">
        <i class="bi bi-person-plus"></i> + Tambah Pengguna
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Terdaftar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($u = mysqli_fetch_assoc($hasilUser)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                            <td><?= htmlspecialchars($u['nama']) ?></td>
                            <td><?= badgeRole($u['role']) ?></td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('Yakin hapus pengguna ini?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal User -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUserTitle">Tambah Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="aksi" id="inputAksi" value="tambah">
                    <input type="hidden" name="id_user" id="inputId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" id="inputUsername" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama" id="inputNama" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Role</label>
                            <select class="form-select" name="role" id="inputRole">
                                <option value="Karyawan">Karyawan</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password <small id="passNote" class="text-muted">(wajib)</small></label>
                            <input type="password" class="form-control" name="password" id="inputPass">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <input type="password" class="form-control" name="password_confirm" id="inputPassConf">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function resetForm() {
    document.getElementById('modalUserTitle').textContent = 'Tambah Pengguna';
    document.getElementById('inputAksi').value = 'tambah';
    document.getElementById('inputId').value = '';
    document.getElementById('inputUsername').value = '';
    document.getElementById('inputNama').value = '';
    document.getElementById('inputRole').value = 'Karyawan';
    document.getElementById('inputPass').value = '';
    document.getElementById('inputPassConf').value = '';
    document.getElementById('passNote').textContent = '(wajib)';
}
function editUser(data) {
    document.getElementById('modalUserTitle').textContent = 'Edit Pengguna';
    document.getElementById('inputAksi').value = 'edit';
    document.getElementById('inputId').value = data.id_user;
    document.getElementById('inputUsername').value = data.username;
    document.getElementById('inputNama').value = data.nama;
    document.getElementById('inputRole').value = data.role;
    document.getElementById('inputPass').value = '';
    document.getElementById('inputPassConf').value = '';
    document.getElementById('passNote').textContent = '(kosongkan jika tidak diubah)';
    new bootstrap.Modal(document.getElementById('modalUser')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
