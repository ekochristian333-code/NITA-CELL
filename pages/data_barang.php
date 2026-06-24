<?php
/**
 * pages/data_barang.php
 * Halaman CRUD Data Barang - tambah, edit, hapus, tampilkan daftar barang
 */

$pageTitle  = 'Data Barang';
$activeMenu = 'barang';
require_once __DIR__ . '/../includes/header.php';

$sukses = '';
$error  = '';

/* =========================================================
   PROSES POST: tambah / edit / hapus
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    /* ---------- HAPUS ---------- */
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_barang'] ?? 0);
        $stmt = mysqli_prepare($koneksi, "DELETE FROM barang WHERE id_barang = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $sukses = 'Data barang berhasil dihapus.';
    }

    /* ---------- SIMPAN (tambah/edit) ---------- */
    if (in_array($aksi, ['tambah', 'edit'])) {
        $id           = (int)($_POST['id_barang']    ?? 0);
        $kode         = trim($_POST['kode_barang']   ?? '');
        $nama         = trim($_POST['nama_barang']   ?? '');
        $kategori     = trim($_POST['kategori']      ?? '');
        $satuan       = trim($_POST['satuan']        ?? 'Pcs');
        $harga_beli   = (float)($_POST['harga_beli'] ?? 0);
        $harga_jual   = (float)($_POST['harga_jual'] ?? 0);
        $stok_awal    = (int)($_POST['stok_awal']    ?? 0);
        $stok_min     = (int)($_POST['stok_minimum'] ?? 5);
        $id_supplier  = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;

        if ($kode === '' || $nama === '' || $kategori === '') {
            $error = 'Kode, nama barang, dan kategori wajib diisi.';
        } else {
            if ($aksi === 'tambah') {
                $stmt = mysqli_prepare($koneksi,
                    "INSERT INTO barang (kode_barang, nama_barang, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum, id_supplier)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'ssssddiis',
                    $kode, $nama, $kategori, $satuan, $harga_beli, $harga_jual, $stok_awal, $stok_min, $id_supplier);
            } else {
                $stmt = mysqli_prepare($koneksi,
                    "UPDATE barang SET kode_barang=?, nama_barang=?, kategori=?, satuan=?,
                     harga_beli=?, harga_jual=?, stok_minimum=?, id_supplier=?
                     WHERE id_barang=?");
                mysqli_stmt_bind_param($stmt, 'ssssddiis',
                    $kode, $nama, $kategori, $satuan, $harga_beli, $harga_jual, $stok_min, $id_supplier, $id);
            }

            if (mysqli_stmt_execute($stmt)) {
                $sukses = $aksi === 'tambah'
                    ? 'Data Barang Berhasil Ditambahkan.'
                    : 'Data Barang Berhasil Diperbarui.';
            } else {
                $error = 'Gagal menyimpan: ' . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

/* =========================================================
   QUERY: ambil data barang + filter pencarian
   ========================================================= */
$cari = trim($_GET['cari'] ?? '');
$kategoriFilter = trim($_GET['kategori'] ?? '');

$whereArr = [];
$params   = [];
$types    = '';

if ($cari !== '') {
    $whereArr[] = "(b.nama_barang LIKE ? OR b.kode_barang LIKE ?)";
    $params[]   = "%$cari%";
    $params[]   = "%$cari%";
    $types     .= 'ss';
}
if ($kategoriFilter !== '') {
    $whereArr[] = "b.kategori = ?";
    $params[]   = $kategoriFilter;
    $types     .= 's';
}

$whereSql = $whereArr ? 'WHERE ' . implode(' AND ', $whereArr) : '';
$sql = "SELECT b.*, s.nama_supplier FROM barang b
        LEFT JOIN supplier s ON b.id_supplier = s.id_supplier
        $whereSql
        ORDER BY b.nama_barang ASC";

$stmt = mysqli_prepare($koneksi, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$hasilBarang = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Ambil semua kategori unik untuk filter dropdown
$kategoriList = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM barang ORDER BY kategori");

// Ambil daftar supplier untuk form dropdown
$supplierList = mysqli_query($koneksi, "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier");
$suppArr = [];
while ($s = mysqli_fetch_assoc($supplierList)) { $suppArr[] = $s; }
?>

<!-- Alert sukses / error -->
<?php if ($sukses): ?>
    <div class="alert alert-success alert-dismissible alert-auto-hide" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($sukses) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Toolbar: Filter + Tombol Tambah -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-5 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="cari" placeholder="Cari nama / kode barang..."
                           value="<?= htmlspecialchars($cari) ?>">
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <select class="form-select form-select-sm" name="kategori">
                    <option value="">Semua Kategori</option>
                    <?php
                    mysqli_data_seek($kategoriList, 0);
                    while ($k = mysqli_fetch_assoc($kategoriList)):
                    ?>
                        <option value="<?= htmlspecialchars($k['kategori']) ?>"
                            <?= $kategoriFilter === $k['kategori'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['kategori']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-6 col-sm-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                <a href="data_barang.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
            <div class="col-sm-auto ms-sm-auto">
                <?php if ($_SESSION['role'] === 'Admin'): ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBarang" onclick="resetForm()">
                    <i class="bi bi-plus-circle"></i> + Tambah Barang
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Data Barang -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <th class="text-center">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if (mysqli_num_rows($hasilBarang) === 0):
                    ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                Tidak ada data barang<?= $cari || $kategoriFilter ? ' yang sesuai filter.' : '.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($b = mysqli_fetch_assoc($hasilBarang)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($b['kode_barang']) ?></span></td>
                                <td class="fw-semibold"><?= htmlspecialchars($b['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($b['kategori']) ?></td>
                                <td><?= htmlspecialchars($b['satuan']) ?></td>
                                <td>Rp <?= number_format($b['harga_beli'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($b['harga_jual'], 0, ',', '.') ?></td>
                                <td class="fw-bold <?= $b['stok'] <= $b['stok_minimum'] ? 'text-danger' : 'text-success' ?>">
                                    <?= $b['stok'] ?>
                                </td>
                                <td>
                                    <?php if ($b['stok'] <= $b['stok_minimum']): ?>
                                        <span class="badge badge-stock-low">Menipis</span>
                                    <?php else: ?>
                                        <span class="badge badge-stock-ok">Aman</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="editBarang(<?= htmlspecialchars(json_encode($b)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin hapus barang ini?')">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_barang" value="<?= $b['id_barang'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== MODAL TAMBAH/EDIT BARANG ===================== -->
<div class="modal fade" id="modalBarang" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBarangTitle">Tambah Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="aksi" id="inputAksi" value="tambah">
                    <input type="hidden" name="id_barang" id="inputId">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_barang" id="inputKode"
                                   placeholder="Contoh: BRG009" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_barang" id="inputNama"
                                   placeholder="Nama lengkap barang" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kategori" id="inputKategori"
                                   placeholder="Aksesori / Voucher & Pulsa" required list="listKategori">
                            <datalist id="listKategori">
                                <option value="Aksesori">
                                <option value="Voucher &amp; Pulsa">
                                <option value="Top Up &amp; Game">
                                <option value="Servis Handphone">
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Satuan</label>
                            <input type="text" class="form-control" name="satuan" id="inputSatuan"
                                   placeholder="Pcs / Lusin / Rim" value="Pcs">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select class="form-select" name="id_supplier" id="inputSupplier">
                                <option value="">-- Pilih Supplier --</option>
                                <?php foreach ($suppArr as $s): ?>
                                    <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Harga Beli (Rp)</label>
                            <input type="number" class="form-control" name="harga_beli" id="inputHargaBeli"
                                   min="0" placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Harga Jual (Rp)</label>
                            <input type="number" class="form-control" name="harga_jual" id="inputHargaJual"
                                   min="0" placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Stok Minimum</label>
                            <input type="number" class="form-control" name="stok_minimum" id="inputStokMin"
                                   min="0" value="5">
                        </div>
                        <div class="col-md-4" id="wrapperStokAwal">
                            <label class="form-label fw-semibold">Stok Awal</label>
                            <input type="number" class="form-control" name="stok_awal" id="inputStokAwal"
                                   min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalBarangTitle').textContent = 'Tambah Barang';
    document.getElementById('inputAksi').value   = 'tambah';
    document.getElementById('inputId').value     = '';
    document.getElementById('inputKode').value   = '';
    document.getElementById('inputNama').value   = '';
    document.getElementById('inputKategori').value = '';
    document.getElementById('inputSatuan').value   = 'Pcs';
    document.getElementById('inputSupplier').value = '';
    document.getElementById('inputHargaBeli').value = '';
    document.getElementById('inputHargaJual').value = '';
    document.getElementById('inputStokMin').value   = '5';
    document.getElementById('inputStokAwal').value  = '0';
    document.getElementById('wrapperStokAwal').style.display = '';
    document.getElementById('inputKode').removeAttribute('readonly');
}

function editBarang(data) {
    document.getElementById('modalBarangTitle').textContent = 'Edit Barang';
    document.getElementById('inputAksi').value   = 'edit';
    document.getElementById('inputId').value     = data.id_barang;
    document.getElementById('inputKode').value   = data.kode_barang;
    document.getElementById('inputKode').setAttribute('readonly', true);
    document.getElementById('inputNama').value   = data.nama_barang;
    document.getElementById('inputKategori').value = data.kategori;
    document.getElementById('inputSatuan').value   = data.satuan;
    document.getElementById('inputSupplier').value = data.id_supplier ?? '';
    document.getElementById('inputHargaBeli').value = data.harga_beli;
    document.getElementById('inputHargaJual').value = data.harga_jual;
    document.getElementById('inputStokMin').value   = data.stok_minimum;
    document.getElementById('wrapperStokAwal').style.display = 'none';

    const modal = new bootstrap.Modal(document.getElementById('modalBarang'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
