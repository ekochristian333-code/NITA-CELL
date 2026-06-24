<?php
/**
 * pages/barang_masuk.php
 * Input transaksi barang masuk + update stok otomatis
 */

$pageTitle  = 'Barang Masuk';
$activeMenu = 'masuk';
require_once __DIR__ . '/../includes/header.php';

$sukses = '';
$error  = '';

/* =========================================================
   PROSES POST: simpan transaksi barang masuk
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan') {
    $id_barang   = (int)($_POST['id_barang']     ?? 0);
    $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
    $jumlah      = (int)($_POST['jumlah_masuk']  ?? 0);
    $harga_beli  = (float)($_POST['harga_beli']  ?? 0);
    $tanggal     = trim($_POST['tanggal_masuk']  ?? '');
    $keterangan  = trim($_POST['keterangan']      ?? '');
    $id_user     = (int)$_SESSION['id_user'];

    if ($id_barang === 0 || $jumlah <= 0 || $tanggal === '') {
        $error = 'Barang, jumlah (> 0), dan tanggal wajib diisi.';
    } else {
        // Mulai transaksi database agar stok & log tersimpan bersamaan (ACID)
        mysqli_begin_transaction($koneksi);
        try {
            // 1. Insert ke tabel barang_masuk
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO barang_masuk (id_barang, id_supplier, id_user, jumlah_masuk, harga_beli, tanggal_masuk, keterangan)
                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'iiidss',
                $id_barang, $id_supplier, $id_user, $jumlah, $harga_beli, $tanggal, $keterangan);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 2. Update stok barang: Stok_Baru = Stok_Sekarang + Jumlah_Masuk
            $stmt = mysqli_prepare($koneksi,
                "UPDATE barang SET stok = stok + ?, harga_beli = ? WHERE id_barang = ?");
            mysqli_stmt_bind_param($stmt, 'idi', $jumlah, $harga_beli, $id_barang);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($koneksi);
            $sukses = "Barang masuk berhasil disimpan. Stok otomatis bertambah $jumlah unit.";
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $error = 'Gagal menyimpan transaksi: ' . $e->getMessage();
        }
    }
}

/* =========================================================
   QUERY: riwayat barang masuk + filter tanggal
   ========================================================= */
$tglDari  = $_GET['dari'] ?? date('Y-m-01');
$tglSampai = $_GET['sampai'] ?? date('Y-m-d');

$stmt = mysqli_prepare($koneksi,
    "SELECT bm.*, b.nama_barang, b.kode_barang, b.satuan, s.nama_supplier, u.nama AS nama_user
     FROM barang_masuk bm
     JOIN barang b ON bm.id_barang = b.id_barang
     LEFT JOIN supplier s ON bm.id_supplier = s.id_supplier
     JOIN user u ON bm.id_user = u.id_user
     WHERE bm.tanggal_masuk BETWEEN ? AND ?
     ORDER BY bm.tanggal_masuk DESC, bm.id_masuk DESC");
mysqli_stmt_bind_param($stmt, 'ss', $tglDari, $tglSampai);
mysqli_stmt_execute($stmt);
$hasilMasuk = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Dropdown barang dan supplier
$barangList   = mysqli_query($koneksi, "SELECT id_barang, kode_barang, nama_barang, stok, satuan, harga_beli FROM barang ORDER BY nama_barang");
$supplierList = mysqli_query($koneksi, "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier");
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

<div class="row g-3">
    <!-- ===== FORM INPUT BARANG MASUK ===== -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-arrow-in-down text-success"></i> Tambah Barang Masuk
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="aksi" value="simpan">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Barang <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_barang" id="pilihBarang" required
                                onchange="updateInfoBarang(this)">
                            <option value="">-- Pilih Barang --</option>
                            <?php while ($b = mysqli_fetch_assoc($barangList)): ?>
                                <option value="<?= $b['id_barang'] ?>"
                                    data-stok="<?= $b['stok'] ?>"
                                    data-satuan="<?= htmlspecialchars($b['satuan']) ?>"
                                    data-harga="<?= $b['harga_beli'] ?>">
                                    <?= htmlspecialchars("[{$b['kode_barang']}] {$b['nama_barang']}") ?>
                                    (Stok: <?= $b['stok'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <!-- Info stok saat ini -->
                        <small id="infoStok" class="text-muted d-none mt-1 d-block">
                            Stok saat ini: <span id="stokSaatIni" class="fw-bold"></span>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplier</label>
                        <select class="form-select" name="id_supplier">
                            <option value="">-- Pilih Supplier --</option>
                            <?php
                            mysqli_data_seek($supplierList, 0);
                            while ($s = mysqli_fetch_assoc($supplierList)):
                            ?>
                                <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jumlah Masuk <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="jumlah_masuk" min="1" required placeholder="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Beli (Rp/unit)</label>
                        <input type="number" class="form-control" name="harga_beli" id="inputHargaBeli" min="0" placeholder="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Masuk <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal_masuk"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Opsional..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Simpan Barang Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== RIWAYAT BARANG MASUK ===== -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span><i class="bi bi-clock-history"></i> Riwayat Barang Masuk</span>
                    <form method="GET" class="d-flex gap-2 ms-auto align-items-center flex-wrap">
                        <input type="date" class="form-control form-control-sm" name="dari"
                               value="<?= htmlspecialchars($tglDari) ?>">
                        <span class="small">s/d</span>
                        <input type="date" class="form-control form-control-sm" name="sampai"
                               value="<?= htmlspecialchars($tglSampai) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Barang</th>
                                <th>Supplier</th>
                                <th class="text-center">Jumlah</th>
                                <th>Harga Beli</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($hasilMasuk) === 0): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                    Tidak ada data di rentang tanggal ini.
                                </td></tr>
                            <?php else: while ($m = mysqli_fetch_assoc($hasilMasuk)): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($m['tanggal_masuk'])) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($m['nama_barang']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($m['kode_barang']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($m['nama_supplier'] ?? '-') ?></td>
                                    <td class="text-center fw-bold text-success">
                                        +<?= $m['jumlah_masuk'] ?> <?= htmlspecialchars($m['satuan']) ?>
                                    </td>
                                    <td>Rp <?= number_format($m['harga_beli'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($m['nama_user']) ?></td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tampilkan info stok saat ini & harga beli default
function updateInfoBarang(sel) {
    const opt = sel.options[sel.selectedIndex];
    const infoStok = document.getElementById('infoStok');
    const stokEl   = document.getElementById('stokSaatIni');
    const hargaEl  = document.getElementById('inputHargaBeli');

    if (sel.value) {
        stokEl.textContent = opt.dataset.stok + ' ' + opt.dataset.satuan;
        hargaEl.value      = opt.dataset.harga;
        infoStok.classList.remove('d-none');
    } else {
        infoStok.classList.add('d-none');
        hargaEl.value = '';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
