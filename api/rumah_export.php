<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak. Silakan login sebagai admin.");
}

$conn = Database::getInstance()->getConnection();

// Get filter parameters from URL
$status_kepemilikan_filter = $_GET['status_kepemilikan'] ?? 'semua';
$search_term = $_GET['search'] ?? '';

// --- Build Query (same logic as in rumah_handler.php) ---
$query = "
    SELECT 
        r.blok, r.nomor, r.pemilik,
        w.nama_lengkap as kepala_keluarga,
        w.status_tinggal,
        (SELECT COUNT(id) FROM warga w_count WHERE w_count.no_kk = r.no_kk_penghuni) as jumlah_anggota,
        (SELECT h.tanggal_masuk FROM rumah_penghuni_history h WHERE h.rumah_id = r.id AND h.tanggal_keluar IS NULL ORDER BY h.tanggal_masuk DESC LIMIT 1) as tanggal_masuk
    FROM rumah r
    LEFT JOIN warga w ON r.no_kk_penghuni = w.no_kk AND w.status_dalam_keluarga = 'Kepala Keluarga'
";

$where_clauses = [];
$params = [];
$types = '';

if ($status_kepemilikan_filter !== 'semua') {
    if ($status_kepemilikan_filter === 'kosong') {
        $where_clauses[] = "(r.no_kk_penghuni IS NULL OR r.no_kk_penghuni = '')";
    } else { // 'tetap' or 'kontrak'
        $where_clauses[] = "w.status_tinggal = ?";
        $params[] = $status_kepemilikan_filter;
        $types .= 's';
    }
}

if (!empty($search_term)) {
    $where_clauses[] = "(r.pemilik LIKE ? OR w.nama_lengkap LIKE ?)";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

$query .= " ORDER BY r.blok, r.nomor";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- Generate CSV ---
$filename = "data_rumah_" . date('Ymd') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add header row
fputcsv($output, ['Blok', 'Nomor', 'Pemilik', 'Kepala Keluarga Penghuni', 'Status Penghuni', 'Tanggal Masuk', 'Jumlah Anggota']);

// Add data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['blok'], $row['nomor'], $row['pemilik'], $row['kepala_keluarga'] ?? 'Tidak Berpenghuni', $row['status_tinggal'] ?? '-', $row['tanggal_masuk'] ? date('d-m-Y', strtotime($row['tanggal_masuk'])) : '-', $row['jumlah_anggota'] ?? 0]);
    }
}

fclose($output);
$stmt->close();
$conn->close();
exit();