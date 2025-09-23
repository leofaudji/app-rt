<?php
// api/warga_export.php

require_once __DIR__ . '/../includes/bootstrap.php';

// Security check: only admin can access
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak. Hanya admin yang dapat mengakses fitur ini.");
}

$conn = Database::getInstance()->getConnection();

try {
    $searchTerm = $_GET['search'] ?? '';

    $params = [];
    $types = '';
    $query = "SELECT no_kk, nik, nama_lengkap, alamat, no_telepon, status_tinggal, pekerjaan FROM warga WHERE 1=1";

    if (!empty($searchTerm)) {
        $query .= " AND (nama_lengkap LIKE ? OR nik LIKE ? OR alamat LIKE ? OR pekerjaan LIKE ?)";
        $likeTerm = "%{$searchTerm}%";
        $params = [$likeTerm, $likeTerm, $likeTerm, $likeTerm];
        $types = 'ssss';
    }

    $query .= " ORDER BY nama_lengkap ASC";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $filename = "data-warga-" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Add header row
    fputcsv($output, ['No. KK', 'NIK', 'Nama Lengkap', 'Alamat', 'No. Telepon', 'Status Tinggal', 'Pekerjaan']);

    // Add data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    $stmt->close();
    $conn->close();
    exit();

} catch (Exception $e) {
    error_log("Export Warga Error: " . $e->getMessage());
    http_response_code(500);
    die("Terjadi kesalahan saat membuat file ekspor.");
}