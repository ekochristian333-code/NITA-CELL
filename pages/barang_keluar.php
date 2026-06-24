<?php
/**
 * pages/barang_keluar.php
 * Input transaksi barang keluar + validasi stok + update otomatis
 */

$pageTitle  = 'Barang Keluar';
$activeMenu = 'keluar';
require_once __DIR__ . '/../includes/header.php';

$sukses = '';
$error  = '';

/* =========================================================
   PROSES POST: simpan transaksi barang keluar
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan') {
    $id_barang  = (int)($_POST['id_barang']    ?? 0);
    $jumlah     = (int)($_POST['jumlah_keluar'] ?? 0);
    $tanggal    = trim($_POST['tanggal_keluar'] ?? '');
    $keterangan = trim($_POST['keterangan']      ?? '');
    $id_user    = (int)$_SESSION['id_user'];

    if ($id_barang === 0 || $jumlah <= 0 || $tanggal === '') {
        $error = 'Barang, jumlah (> 0), dan tanggal wajib diisi.';
    } else {
        // Cek stok saat ini (VALIDASI)
        $stmtCek = mysqli_prepare($koneksi, "SELECT stok, nama_barang, satuan FROM barang WHERE id_barang = ?");
        mysqli_stmt_bind_param($stmtCek, 'i', $id_barang);
        mysqli_stmt_execute($stmtCek);
        $dataBarang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCek));
        mysqli_stmt_close($stmtCek);

        if (!$dataBarang) {
            $error = 'Barang tidak ditemukan.';
        } elseif ($dataBarang['stok'] < $jumlah) {
            // *** VALIDASI BISNIS: stok tidak mencukupi ***
            $error = "Stok tidak mencukupi! Stok tersedia: {$dataBarang['stok']} {$dataBarang['satuan']}, diminta: $jumlah.";
        } else {
            // Transaksi database agar atomik
            mysqli_begin_transaction($koneksi);
            try {
                // 1. Insert ke tabel barang_keluar
                $stmt = mysqli_prepare($koneksi,
                    "INSERT INTO barang_keluar (id_barang, id_user, jumlah_keluar, tanggal_keluar, keterangan)
                     VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'iiiss', $id_barang, $id_user, $jumlah, $tanggal, $keterangan);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // 2. Kurangi stok: Stok_Baru = Stok_Sekarang - Jumlah_Keluar
                $stmt = mysqli_prepare($koneksi,
                    "UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $jumlah, $id_barang);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($koneksi);
                $sukses = "Barang keluar berhasil disimpan. Stok {$dataBarang['nama_barang']} berkurang $jumlah unit.";
            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                $error = 'Gagal menyimpan transaksi: ' . $e->getMessage();
            }
        }
    }
}

/* =========================================================
   QUERY: riwayat barang keluar + filter tanggal
   ========================================================= */
$tglDari   = $_GET['dari']   ?? date('Y-m-01');
$tglSampai = $_GET['sampai'] ?? date('Y-m-d');

$stmt = mysqli_prepare($koneksi,
    "SELECT bk.*, b.nama_barang, b.kode_barang, b.satuan, u.nama AS nama_user
     FROM barang_keluar bk
     JOIN barang b ON bk.id_barang = b.id_barang
     JOIN user u ON bk.id_user = u.id_user
     WHERE bk.tanggal_keluar BETWEEN ? AND ?
     ORDER BY bk.tanggal_keluar DESC, bk.id_keluar DESC");
mysqli_stmt_bind_param($stmt, 'ss', $tglDari, $tglSampai);
mysqli_stmt_execute($stmt);
$hasilKeluar = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Dropdown barang beserta stok tersedia
$barangList = mysqli_query($koneksi,
    "SELECT id_barang, kode_barang, nama_barang, stok, satuan FROM barang ORDER BY nama_barang");
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
    <!-- ===== FORM INPUT BARANG KELUAR ===== -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-arrow-up text-danger"></i> Tambah Barang Keluar
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return validasiForm()">
                    <input type="hidden" name="aksi" value="simpan">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Barang <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_barang" id="pilihBarang" required
                                onchange="updateInfoBarang(this)">
                            <option value="">-- Pilih Barang --</option>
                            <?php while ($b = mysqli_fetch_assoc($barangList)): ?>
                                <option value="<?= $b['id_barang'] ?>"
                                    data-stok="<?= $b['stok'] ?>"
                                    data-satuan="<?= htmlspecialchars($b['satuan']) ?>">
                                    <?= htmlspecialchars("[{$b['kode_barang']}] {$b['nama_barang']}") ?>
                                    (Stok: <?= $b['stok'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <!-- Info stok -->
                        <div id="infoStokBox" class="d-none mt-2 p-2 rounded border">
                            <small>Stok tersedia:
                                <span id="stokTersedia" class="fw-bold text-success"></span>
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jumlah Keluar <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="jumlah_keluar" id="inputJumlah"
                               min="1" required placeholder="0"
                               oninput="cekJumlah(this.value)">
                        <div id="pesanStok" class="text-danger small mt-1 d-none">
                            <i class="bi bi-exclamation-triangle"></i> Jumlah melebihi stok tersedia!
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Keluar <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal_keluar"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Opsional..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-save"></i> Simpan Barang Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== RIWAYAT BARANG KELUAR ===== -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span><i class="bi bi-clock-history"></i> Riwayat Barang Keluar</span>
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
                                <th class="text-center">Jumlah</th>
                                <th>Keterangan</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($hasilKeluar) === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                    Tidak ada data di rentang tanggal ini.
                                </td></tr>
                            <?php else: while ($k = mysqli_fetch_assoc($hasilKeluar)): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($k['tanggal_keluar'])) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($k['nama_barang']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($k['kode_barang']) ?></small>
                                    </td>
                                    <td class="text-center fw-bold text-danger">
                                        -<?= $k['jumlah_keluar'] ?> <?= htmlspecialchars($k['satuan']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($k['keterangan'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($k['nama_user']) ?></td>
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
let stokTersediaAngka = 0;

function updateInfoBarang(sel) {
    const opt = sel.options[sel.selectedIndex];
    const infoBox = document.getElementById('infoStokBox');
    const stokEl  = document.getElementById('stokTersedia');
    const jumlahEl = document.getElementById('inputJumlah');

    if (sel.value) {
        stokTersediaAngka = parseInt(opt.dataset.stok) || 0;
        stokEl.textContent = stokTersediaAngka + ' ' + opt.dataset.satuan;
        stokEl.className = stokTersediaAngka > 0 ? 'fw-bold text-success' : 'fw-bold text-danger';
        infoBox.classList.remove('d-none');
        // Reset jumlah
        jumlahEl.value = '';
        document.getElementById('pesanStok').classList.add('d-none');
    } else {
        infoBox.classList.add('d-none');
        stokTersediaAngka = 0;
    }
}

function cekJumlah(val) {
    const pesan = document.getElementById('pesanStok');
    if (parseInt(val) > stokTersediaAngka) {
        pesan.classList.remove('d-none');
    } else {
        pesan.classList.add('d-none');
    }
}

function validasiForm() {
    const jumlah = parseInt(document.getElementById('inputJumlah').value) || 0;
    if (jumlah > stokTersediaAngka) {
        alert('Stok tidak mencukupi! Stok tersedia: ' + stokTersediaAngka);
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
