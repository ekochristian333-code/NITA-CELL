<?php
/**
 * pages/laporan_stok.php
 * Laporan stok barang dengan filter tanggal + kategori
 * Export PDF (inline print) dan Export Excel (CSV)
 */

$pageTitle  = 'Laporan Stok';
$activeMenu = 'laporan';
require_once __DIR__ . '/../includes/header.php';

/* =========================================================
   QUERY dengan filter
   ========================================================= */
$tglDari      = $_GET['dari']      ?? date('Y-m-01');
$tglSampai    = $_GET['sampai']    ?? date('Y-m-d');
$kategoriFilter = trim($_GET['kategori'] ?? '');
$jenisFilter    = trim($_GET['jenis']    ?? 'semua');

// Ambil semua kategori untuk dropdown
$kategoriList = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM barang ORDER BY kategori");

// Bangun query laporan gabungan barang masuk & keluar per barang
$whereArr = ['1=1'];
$params   = [];
$types    = '';

if ($kategoriFilter !== '') {
    $whereArr[] = "b.kategori = ?";
    $params[]   = $kategoriFilter;
    $types     .= 's';
}

$whereSql = implode(' AND ', $whereArr);

$sql = "SELECT
    b.kode_barang,
    b.nama_barang,
    b.kategori,
    b.satuan,
    b.stok AS stok_akhir,
    b.stok_minimum,
    COALESCE(SUM(CASE WHEN bm.tanggal_masuk BETWEEN ? AND ? THEN bm.jumlah_masuk ELSE 0 END), 0) AS total_masuk,
    COALESCE(SUM(CASE WHEN bk.tanggal_keluar BETWEEN ? AND ? THEN bk.jumlah_keluar ELSE 0 END), 0) AS total_keluar,
    b.harga_jual
FROM barang b
LEFT JOIN barang_masuk bm ON b.id_barang = bm.id_barang
LEFT JOIN barang_keluar bk ON b.id_barang = bk.id_barang
WHERE $whereSql
GROUP BY b.id_barang
ORDER BY b.kategori, b.nama_barang";

// Bind: tambahkan parameter tanggal di awal
$allTypes  = 'ssss' . $types;
$allParams = [$tglDari, $tglSampai, $tglDari, $tglSampai, ...$params];

$stmt = mysqli_prepare($koneksi, $sql);
if ($allParams) {
    mysqli_stmt_bind_param($stmt, $allTypes, ...$allParams);
}
mysqli_stmt_execute($stmt);
$hasilLaporan = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Simpan ke array untuk export
$rows = [];
while ($r = mysqli_fetch_assoc($hasilLaporan)) {
    // Filter jenis stok
    if ($jenisFilter === 'menipis' && $r['stok_akhir'] > $r['stok_minimum']) continue;
    if ($jenisFilter === 'aman'    && $r['stok_akhir'] <= $r['stok_minimum']) continue;
    $rows[] = $r;
}

/* ===== Export Excel (CSV) ===== */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_stok_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    // BOM untuk Excel UTF-8
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['Kode Barang', 'Nama Barang', 'Kategori', 'Satuan',
                   'Total Masuk', 'Total Keluar', 'Stok Akhir', 'Min Stok', 'Harga Jual', 'Status'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['kode_barang'], $r['nama_barang'], $r['kategori'], $r['satuan'],
            $r['total_masuk'], $r['total_keluar'], $r['stok_akhir'], $r['stok_minimum'],
            $r['harga_jual'],
            $r['stok_akhir'] <= $r['stok_minimum'] ? 'Menipis' : 'Aman'
        ], ';');
    }
    fclose($out);
    exit;
}
?>

<!-- Toolbar filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label form-label-sm mb-1">Dari</label>
                <input type="date" class="form-control form-control-sm" name="dari" value="<?= htmlspecialchars($tglDari) ?>">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label form-label-sm mb-1">Sampai</label>
                <input type="date" class="form-control form-control-sm" name="sampai" value="<?= htmlspecialchars($tglSampai) ?>">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label form-label-sm mb-1">Kategori</label>
                <select class="form-select form-select-sm" name="kategori">
                    <option value="">Semua</option>
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
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label form-label-sm mb-1">Status Stok</label>
                <select class="form-select form-select-sm" name="jenis">
                    <option value="semua"   <?= $jenisFilter==='semua'   ? 'selected':'' ?>>Semua</option>
                    <option value="aman"    <?= $jenisFilter==='aman'    ? 'selected':'' ?>>Aman</option>
                    <option value="menipis" <?= $jenisFilter==='menipis' ? 'selected':'' ?>>Menipis</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="laporan_stok.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap ms-sm-auto">
                <!-- Export Excel (CSV) -->
                <a href="?dari=<?= urlencode($tglDari) ?>&sampai=<?= urlencode($tglSampai) ?>&kategori=<?= urlencode($kategoriFilter) ?>&jenis=<?= urlencode($jenisFilter) ?>&export=excel"
                   class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </a>
                <!-- Cetak PDF (print halaman) -->
                <button onclick="cetakPDF()" class="btn btn-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan -->
<?php
$totalBarang = count($rows);
$totalMasuk  = array_sum(array_column($rows, 'total_masuk'));
$totalKeluar = array_sum(array_column($rows, 'total_keluar'));
$totalMenipis = count(array_filter($rows, fn($r) => $r['stok_akhir'] <= $r['stok_minimum']));
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-primary"><?= $totalBarang ?></div>
                <small class="text-muted">Jenis Barang</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-success"><?= number_format($totalMasuk) ?></div>
                <small class="text-muted">Total Masuk</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-danger"><?= number_format($totalKeluar) ?></div>
                <small class="text-muted">Total Keluar</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-warning"><?= $totalMenipis ?></div>
                <small class="text-muted">Stok Menipis</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Laporan -->
<div class="card" id="tabelLaporan">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table"></i> Laporan Stok Barang</span>
        <small class="text-muted">
            Periode: <?= date('d/m/Y', strtotime($tglDari)) ?> &ndash; <?= date('d/m/Y', strtotime($tglSampai)) ?>
        </small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableReport">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th class="text-center">Masuk</th>
                        <th class="text-center">Keluar</th>
                        <th class="text-center">Stok Akhir</th>
                        <th class="text-center">Min</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i> Tidak ada data.
                        </td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($rows as $r): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['kode_barang']) ?></span></td>
                                <td class="fw-semibold"><?= htmlspecialchars($r['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($r['kategori']) ?></td>
                                <td><?= htmlspecialchars($r['satuan']) ?></td>
                                <td class="text-center text-success fw-bold">+<?= $r['total_masuk'] ?></td>
                                <td class="text-center text-danger fw-bold">-<?= $r['total_keluar'] ?></td>
                                <td class="text-center fw-bold <?= $r['stok_akhir'] <= $r['stok_minimum'] ? 'text-danger' : 'text-success' ?>">
                                    <?= $r['stok_akhir'] ?>
                                </td>
                                <td class="text-center text-muted"><?= $r['stok_minimum'] ?></td>
                                <td class="text-center">
                                    <?php if ($r['stok_akhir'] <= $r['stok_minimum']): ?>
                                        <span class="badge badge-stock-low">Menipis</span>
                                    <?php else: ?>
                                        <span class="badge badge-stock-ok">Aman</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CSS & Script untuk Print PDF -->
<style>
@media print {
    .sidebar, .topbar, .card-header form, form.row, .btn, .alert,
    .sidebar-overlay, button, a.btn { display: none !important; }
    .main-content { margin-left: 0 !important; width: 100% !important; }
    body { background: white; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    #tabelLaporan { break-inside: avoid; }
    .table thead th { background-color: #e0f7f5 !important; print-color-adjust: exact; }
}
</style>
<script>
function cetakPDF() {
    window.print();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
