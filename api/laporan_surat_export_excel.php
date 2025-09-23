<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak.");
}

$conn = Database::getInstance()->getConnection();

$tipe = $_GET['tipe'] ?? 'bulanan';
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');
$status = $_GET['status'] ?? 'semua';

$params = [$tahun];
$types = "i";
$whereClause = "WHERE YEAR(created_at) = ?";

if ($tipe === 'bulanan') {
    $whereClause .= " AND MONTH(created_at) = ?";
    $params[] = $bulan;
    $types .= "i";
}

if ($status !== 'semua' && in_array($status, ['pending', 'approved', 'rejected'])) {
    $whereClause .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

// --- Fetch Data ---
$stmt_details = $conn->prepare("SELECT jenis_surat, COUNT(*) as jumlah FROM surat_pengantar $whereClause GROUP BY jenis_surat ORDER BY jumlah DESC");
$stmt_details->bind_param($types, ...$params);
$stmt_details->execute();
$details = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_details->close();
$conn->close();

// --- CSV Generation ---
$filename_part_periode = "Tahun_{$tahun}";
if ($tipe === 'bulanan') {
    $months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    $bulan_nama = $months[$bulan - 1];
    $filename_part_periode = "{$bulan_nama}_{$tahun}";
}

$filename_part_status = ($status !== 'semua') ? '_' . ucfirst($status) : '';
$filename = "Laporan_Surat_{$filename_part_periode}{$filename_part_status}.csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['Jenis Surat', 'Jumlah']);

// Data rows
if (count($details) > 0) {
    foreach ($details as $row) {
        fputcsv($output, [$row['jenis_surat'], $row['jumlah']]);
    }
}

fclose($output);
exit();