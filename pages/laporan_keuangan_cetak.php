<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    http_response_code(403);
    die("Akses ditolak.");
}

$conn = Database::getInstance()->getConnection();

// Get filters from URL
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');

// --- Fetch Data ---
$bulan_map = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$bulan_text = $bulan_map[(int)$bulan];
$periode_text = "Bulan " . $bulan_text . " Tahun " . $tahun;
$first_day_of_month = "$tahun-$bulan-01";

// Get housing name
$settings_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'housing_name'");
$housing_name = $settings_result->fetch_assoc()['setting_value'] ?? 'Perumahan Anda';

// Calculate Saldo Awal (balance before the selected month)
$stmt_saldo_awal = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END) as saldo_awal FROM kas WHERE tanggal < ?");
$stmt_saldo_awal->bind_param("s", $first_day_of_month);
$stmt_saldo_awal->execute();
$saldo_awal = $stmt_saldo_awal->get_result()->fetch_assoc()['saldo_awal'] ?? 0;
$stmt_saldo_awal->close();

// Fetch transactions for the selected month
$stmt_transaksi = $conn->prepare("SELECT * FROM kas WHERE YEAR(tanggal) = ? AND MONTH(tanggal) = ? ORDER BY tanggal ASC, created_at ASC");
$stmt_transaksi->bind_param("ii", $tahun, $bulan);
$stmt_transaksi->execute();
$transactions = $stmt_transaksi->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_transaksi->close();

$conn->close();

// Calculate totals for the month
$total_pemasukan = 0;
$total_pengeluaran = 0;
foreach ($transactions as $tx) {
    if ($tx['jenis'] == 'masuk') {
        $total_pemasukan += $tx['jumlah'];
    } else {
        $total_pengeluaran += $tx['jumlah'];
    }
}
$saldo_akhir = $saldo_awal + $total_pemasukan - $total_pengeluaran;

// Helper to format currency
function format_rupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Keuangan - <?= htmlspecialchars($periode_text) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Times New Roman', Times, serif; }
        .report-container { max-width: 800px; margin: 2rem auto; background: white; border: 1px solid #dee2e6; border-radius: .5rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
        .report-header { text-align: center; padding: 1.5rem; border-bottom: 2px solid #000; }
        .report-header h1 { font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem; }
        .report-header p { font-size: 1.1rem; color: #333; margin-bottom: 0; }
        .report-body { padding: 2rem; }
        .report-footer { text-align: center; padding: 1.5rem; border-top: 1px solid #dee2e6; font-size: 0.9rem; }
        .print-button-container { position: fixed; bottom: 20px; right: 20px; z-index: 100; }
        .summary-table td { padding: 0.5rem; }
        .transaction-table th, .transaction-table td { font-size: 0.9rem; }
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
        <h1>LAPORAN KEUANGAN BULANAN</h1>
        <p>PENGURUS RT <?= htmlspecialchars(strtoupper($housing_name)) ?></p>
        <p class="fw-bold">PERIODE: <?= htmlspecialchars(strtoupper($periode_text)) ?></p>
    </div>
    <div class="report-body">
        <h4 class="mb-3">Ringkasan Keuangan</h4>
        <table class="table table-bordered summary-table mb-4">
            <tbody>
                <tr><td style="width: 50%;">Saldo Awal Bulan</td><td class="text-end fw-bold"><?= format_rupiah($saldo_awal) ?></td></tr>
                <tr><td>Total Pemasukan</td><td class="text-end fw-bold text-success"><?= format_rupiah($total_pemasukan) ?></td></tr>
                <tr><td>Total Pengeluaran</td><td class="text-end fw-bold text-danger"><?= format_rupiah($total_pengeluaran) ?></td></tr>
                <tr><td class="table-dark">Saldo Akhir Bulan</td><td class="text-end fw-bold table-dark"><?= format_rupiah($saldo_akhir) ?></td></tr>
            </tbody>
        </table>
        <hr class="my-4">
        <h4 class="mb-3">Rincian Transaksi</h4>
        <table class="table table-striped table-bordered transaction-table">
            <thead class="table-dark"><tr><th style="width: 15%;">Tanggal</th><th>Keterangan</th><th class="text-end" style="width: 20%;">Pemasukan</th><th class="text-end" style="width: 20%;">Pengeluaran</th></tr></thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="4" class="text-center">Tidak ada transaksi pada periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr><td><?= date('d-m-Y', strtotime($tx['tanggal'])) ?></td><td><?= htmlspecialchars($tx['keterangan']) ?></td><td class="text-end"><?= $tx['jenis'] == 'masuk' ? format_rupiah($tx['jumlah']) : '-' ?></td><td class="text-end"><?= $tx['jenis'] == 'keluar' ? format_rupiah($tx['jumlah']) : '-' ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot><tr class="fw-bold"><td colspan="2" class="text-end">Total</td><td class="text-end"><?= format_rupiah($total_pemasukan) ?></td><td class="text-end"><?= format_rupiah($total_pengeluaran) ?></td></tr></tfoot>
        </table>
    </div>
    <div class="report-footer"><small class="text-muted">Dicetak dari Aplikasi RT pada <?= date('d F Y, H:i') ?></small></div>
</div>

<div class="print-button-container"><button class="btn btn-primary btn-lg shadow" onclick="window.print()"><i class="bi bi-printer-fill"></i> Cetak Laporan</button></div>

</body>
</html>