<?php
/**
 * pages/dashboard.php
 * Menampilkan ringkasan statistik inventori dan grafik transaksi 7 hari terakhir
 */

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

// ===== Total Jenis Barang =====
$totalBarang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang"))['total'];

// ===== Barang Stok Menipis (stok <= stok_minimum) =====
$stokMenipisQuery = mysqli_query($koneksi, "SELECT * FROM barang WHERE stok <= stok_minimum ORDER BY stok ASC");
$stokMenipis = mysqli_fetch_all($stokMenipisQuery, MYSQLI_ASSOC);
$totalStokMenipis = count($stokMenipis);

// ===== Total Transaksi Barang Masuk Hari Ini =====
$today = date('Y-m-d');
$stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah_masuk),0) AS total FROM barang_masuk WHERE tanggal_masuk = ?");
mysqli_stmt_bind_param($stmt, 's', $today);
mysqli_stmt_execute($stmt);
$totalMasukHariIni = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
mysqli_stmt_close($stmt);

// ===== Total Transaksi Barang Keluar Hari Ini =====
$stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah_keluar),0) AS total FROM barang_keluar WHERE tanggal_keluar = ?");
mysqli_stmt_bind_param($stmt, 's', $today);
mysqli_stmt_execute($stmt);
$totalKeluarHariIni = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
mysqli_stmt_close($stmt);

// ===== Data Grafik 7 Hari Terakhir (Barang Masuk vs Keluar) =====
$labels = [];
$dataMasuk = [];
$dataKeluar = [];

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($tgl));

    $stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah_masuk),0) AS total FROM barang_masuk WHERE tanggal_masuk = ?");
    mysqli_stmt_bind_param($stmt, 's', $tgl);
    mysqli_stmt_execute($stmt);
    $dataMasuk[] = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah_keluar),0) AS total FROM barang_keluar WHERE tanggal_keluar = ?");
    mysqli_stmt_bind_param($stmt, 's', $tgl);
    mysqli_stmt_execute($stmt);
    $dataKeluar[] = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    mysqli_stmt_close($stmt);
}

// ===== Transaksi Terbaru (gabungan masuk & keluar, 5 terakhir) =====
$transaksiTerbaru = mysqli_query($koneksi, "
    (SELECT 'Masuk' AS jenis, bm.tanggal_masuk AS tanggal, b.nama_barang, bm.jumlah_masuk AS jumlah, u.nama AS nama_user
     FROM barang_masuk bm
     JOIN barang b ON bm.id_barang = b.id_barang
     JOIN user u ON bm.id_user = u.id_user)
    UNION ALL
    (SELECT 'Keluar' AS jenis, bk.tanggal_keluar AS tanggal, b.nama_barang, bk.jumlah_keluar AS jumlah, u.nama AS nama_user
     FROM barang_keluar bk
     JOIN barang b ON bk.id_barang = b.id_barang
     JOIN user u ON bk.id_user = u.id_user)
    ORDER BY tanggal DESC LIMIT 5
");
?>

<!-- ===================== STAT CARDS ===================== -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card bg-tosca p-3 h-100">
            <i class="bi bi-box-seam stat-icon"></i>
            <div class="small text-white-50">Total Jenis Barang</div>
            <div class="fs-3 fw-bold"><?= $totalBarang ?></div>
            <div class="small">item terdaftar</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card bg-danger-soft p-3 h-100">
            <i class="bi bi-exclamation-triangle stat-icon"></i>
            <div class="small text-white-50">Stok Menipis</div>
            <div class="fs-3 fw-bold"><?= $totalStokMenipis ?></div>
            <div class="small">barang perlu restok</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card bg-navy p-3 h-100">
            <i class="bi bi-box-arrow-in-down stat-icon"></i>
            <div class="small text-white-50">Barang Masuk Hari Ini</div>
            <div class="fs-3 fw-bold"><?= $totalMasukHariIni ?></div>
            <div class="small">unit masuk</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card bg-warning-soft p-3 h-100">
            <i class="bi bi-box-arrow-up stat-icon"></i>
            <div class="small text-white-50">Barang Keluar Hari Ini</div>
            <div class="fs-3 fw-bold"><?= $totalKeluarHariIni ?></div>
            <div class="small">unit keluar</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- ===================== GRAFIK 7 HARI ===================== -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Grafik Transaksi 7 Hari Terakhir
            </div>
            <div class="card-body">
                <canvas id="chartTransaksi" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- ===================== STOK MENIPIS ===================== -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-exclamation-circle text-danger"></i> Barang Stok Menipis
            </div>
            <div class="card-body p-0">
                <?php if (empty($stokMenipis)): ?>
                    <p class="text-muted p-3 mb-0">
                        <i class="bi bi-check-circle text-success"></i> Semua stok aman, tidak ada yang menipis.
                    </p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stokMenipis as $b): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($b['nama_barang']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($b['kode_barang']) ?> &middot; min: <?= $b['stok_minimum'] ?></small>
                                </div>
                                <span class="badge badge-stock-low rounded-pill"><?= $b['stok'] ?> <?= htmlspecialchars($b['satuan']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===================== TRANSAKSI TERBARU ===================== -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Transaksi Terbaru
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Nama Barang</th>
                                <th>Jumlah</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($transaksiTerbaru) === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada transaksi.</td></tr>
                            <?php else: ?>
                                <?php while ($t = mysqli_fetch_assoc($transaksiTerbaru)): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                        <td>
                                            <?php if ($t['jenis'] === 'Masuk'): ?>
                                                <span class="badge bg-success"><i class="bi bi-arrow-down-circle"></i> Masuk</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bi bi-arrow-up-circle"></i> Keluar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($t['nama_barang']) ?></td>
                                        <td><?= $t['jumlah'] ?></td>
                                        <td><?= htmlspecialchars($t['nama_user']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('chartTransaksi');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: 'Barang Masuk',
                    data: <?= json_encode($dataMasuk) ?>,
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13,148,136,0.15)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Barang Keluar',
                    data: <?= json_encode($dataKeluar) ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
