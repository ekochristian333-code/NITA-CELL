<?php
/**
 * pages/data_supplier.php
 * Halaman CRUD Data Supplier
 */

$pageTitle  = 'Data Supplier';
$activeMenu = 'supplier';
require_once __DIR__ . '/../includes/header.php';

$sukses = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_supplier'] ?? 0);
        $stmt = mysqli_prepare($koneksi, "DELETE FROM supplier WHERE id_supplier = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $sukses = 'Data supplier berhasil dihapus.';
    }

    if (in_array($aksi, ['tambah', 'edit'])) {
        $id    = (int)($_POST['id_supplier'] ?? 0);
        $nama  = trim($_POST['nama_supplier'] ?? '');
        $hp    = trim($_POST['no_hp']         ?? '');
        $alamat = trim($_POST['alamat']       ?? '');

        if ($nama === '') {
            $error = 'Nama supplier wajib diisi.';
        } else {
            if ($aksi === 'tambah') {
                $stmt = mysqli_prepare($koneksi,
                    "INSERT INTO supplier (nama_supplier, no_hp, alamat) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'sss', $nama, $hp, $alamat);
            } else {
                $stmt = mysqli_prepare($koneksi,
                    "UPDATE supplier SET nama_supplier=?, no_hp=?, alamat=? WHERE id_supplier=?");
                mysqli_stmt_bind_param($stmt, 'sssi', $nama, $hp, $alamat, $id);
            }
            if (mysqli_stmt_execute($stmt)) {
                $sukses = $aksi === 'tambah' ? 'Supplier berhasil ditambahkan.' : 'Supplier berhasil diperbarui.';
            } else {
                $error = 'Gagal menyimpan: ' . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$hasilSupplier = mysqli_query($koneksi, "SELECT * FROM supplier ORDER BY nama_supplier ASC");
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
    <?php if ($_SESSION['role'] === 'Admin'): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSupplier" onclick="resetForm()">
        <i class="bi bi-plus-circle"></i> + Tambah Supplier
    </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Supplier</th>
                        <th>No. HP</th>
                        <th>Alamat</th>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <th class="text-center">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if (mysqli_num_rows($hasilSupplier) === 0):
                    ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i> Belum ada data supplier.
                        </td></tr>
                    <?php else: while ($s = mysqli_fetch_assoc($hasilSupplier)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($s['nama_supplier']) ?></td>
                            <td><?= htmlspecialchars($s['no_hp'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($s['alamat'] ?: '-') ?></td>
                            <?php if ($_SESSION['role'] === 'Admin'): ?>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    onclick="editSupplier(<?= htmlspecialchars(json_encode($s)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('Yakin hapus supplier ini?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_supplier" value="<?= $s['id_supplier'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Supplier -->
<div class="modal fade" id="modalSupplier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSupplierTitle">Tambah Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="aksi" id="inputAksi" value="tambah">
                    <input type="hidden" name="id_supplier" id="inputId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_supplier" id="inputNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. HP</label>
                        <input type="text" class="form-control" name="no_hp" id="inputHp" placeholder="08xx...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat</label>
                        <textarea class="form-control" name="alamat" id="inputAlamat" rows="3"></textarea>
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
    document.getElementById('modalSupplierTitle').textContent = 'Tambah Supplier';
    document.getElementById('inputAksi').value = 'tambah';
    document.getElementById('inputId').value   = '';
    document.getElementById('inputNama').value  = '';
    document.getElementById('inputHp').value    = '';
    document.getElementById('inputAlamat').value = '';
}
function editSupplier(data) {
    document.getElementById('modalSupplierTitle').textContent = 'Edit Supplier';
    document.getElementById('inputAksi').value  = 'edit';
    document.getElementById('inputId').value    = data.id_supplier;
    document.getElementById('inputNama').value  = data.nama_supplier;
    document.getElementById('inputHp').value    = data.no_hp ?? '';
    document.getElementById('inputAlamat').value = data.alamat ?? '';
    new bootstrap.Modal(document.getElementById('modalSupplier')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
