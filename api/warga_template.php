<?php
// api/warga_template.php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak.");
}

$filename = "template-impor-warga.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['no_kk', 'nik', 'nama_lengkap', 'nama_panggilan', 'alamat', 'no_telepon', 'status_tinggal', 'pekerjaan', 'tgl_lahir']);

// Example row
fputcsv($output, ['3201010101010001', '3201010101900001', 'Budi Santoso', 'budi', 'Blok A No. 1', '081234567890', 'tetap', 'Karyawan Swasta', '1990-01-01']);
fputcsv($output, ['3201010101010002', '3201010202910002', 'Siti Aminah', 'siti', 'Blok A No. 2', '081234567891', 'kontrak', 'Wiraswasta', '1991-02-02']);

fclose($output);
exit();
?>