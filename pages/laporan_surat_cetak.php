<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak.");
}

$conn = Database::getInstance()->getConnection();

// Get filters from URL
$tipe = $_GET['tipe'] ?? 'bulanan';
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');
$status = $_GET['status'] ?? 'semua';

// --- Fetch Data ---
$params = [];
$types = '';
$query = "SELECT st.nama_template AS jenis_surat, COUNT(sp.id) AS jumlah 
          FROM surat_templates st
          LEFT JOIN surat_pengantar sp ON st.nama_template = sp.jenis_surat";

$whereClauses = [];

if ($tipe === 'bulanan') {
    $whereClauses[] = "YEAR(sp.created_at) = ? AND MONTH(sp.created_at) = ?";
    $params[] = $tahun;
    $params[] = $bulan;
    $types .= 'ii';
    $periode_text = "Bulan " . DateTime::createFromFormat('!m', $bulan)->format('F') . " Tahun " . $tahun;
} else { // tahunan
    $whereClauses[] = "YEAR(sp.created_at) = ?";
    $params[] = $tahun;
    $types .= 'i';
    $periode_text = "Tahun " . $tahun;
}

if ($status !== 'semua') {
    $whereClauses[] = "sp.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($whereClauses)) {
    $query .= " AND " . implode(' AND ', $whereClauses);
}

$query .= " GROUP BY st.nama_template ORDER BY jumlah DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total = array_sum(array_column($details, 'jumlah'));
$stmt->close();

$settings_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'housing_name'");
$housing_name = $settings_result->fetch_assoc()['setting_value'] ?? 'Perumahan Anda';

$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Surat - <?= htmlspecialchars($periode_text) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .report-container { max-width: 800px; margin: 2rem auto; background: white; border: 1px solid #dee2e6; border-radius: .5rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
        .report-header { text-align: center; padding: 2rem; border-bottom: 2px solid #000; }
        .report-header h1 { font-size: 1.8rem; font-weight: bold; margin-bottom: 0.5rem; }
        .report-header p { font-size: 1.2rem; color: #6c757d; margin-bottom: 0; }
        .report-body { padding: 2rem; }
        .report-footer { text-align: center; padding: 1.5rem; border-top: 1px solid #dee2e6; }
        .print-button-container { position: fixed; bottom: 20px; right: 20px; z-index: 100; }
        @media print {
            body { background-color: white; }
            .print-button-container { display: none; }
            .report-container { box-shadow: none; border: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="report-container">
    <div class="report-header">
        <h1>Laporan Surat Pengantar</h1>
        <p>Pengurus RT <?= htmlspecialchars($housing_name) ?></p>
        <p class="fw-bold"><?= htmlspecialchars($periode_text) ?></p>
    </div>
    <div class="report-body">
        <h4 class="mb-3">Ringkasan</h4>
        <p>Total surat yang diajukan pada periode ini: <strong><?= $total ?> surat</strong>.</p>
        <p>Status yang ditampilkan: <strong><?= htmlspecialchars(ucfirst($status)) ?></strong>.</p>
        <hr class="my-4">
        <h4 class="mb-3">Grafik Jenis Surat</h4>
        <div class="text-center mb-4"><img id="chart-image" src="" alt="Grafik Laporan" class="img-fluid" style="max-height: 400px; border: 1px solid #ccc; padding: 10px;"></div>
        <hr class="my-4">
        <h4 class="mb-3">Rincian Berdasarkan Jenis Surat</h4>
        <table class="table table-striped table-bordered"><thead class="table-dark"><tr><th>Jenis Surat</th><th class="text-end">Jumlah</th></tr></thead><tbody><?php if (empty($details)): ?><tr><td colspan="2" class="text-center">Tidak ada data.</td></tr><?php else: ?><?php foreach ($details as $item): ?><tr><td><?= htmlspecialchars($item['jenis_surat']) ?></td><td class="text-end"><?= $item['jumlah'] ?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table>
    </div>
    <div class="report-footer"><small class="text-muted">Dicetak dari Aplikasi RT pada <?= date('d-m-Y H:i') ?></small></div>
</div>

<div class="print-button-container"><button class="btn btn-primary btn-lg shadow" onclick="window.print()"><i class="bi bi-printer-fill"></i> Cetak Laporan</button></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartImageData = sessionStorage.getItem('chartImageData');
        const chartImageEl = document.getElementById('chart-image');
        if (chartImageData && chartImageEl) { chartImageEl.src = chartImageData; } else if (chartImageEl) { chartImageEl.style.display = 'none'; const p = document.createElement('p'); p.className = 'text-muted text-center'; p.textContent = 'Grafik tidak tersedia.'; chartImageEl.parentNode.insertBefore(p, chartImageEl); }
    });
</script>

</body>
</html>