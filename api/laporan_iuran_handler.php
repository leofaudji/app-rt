<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin atau Bendahara access required.']);
    exit;
}

$conn = Database::getInstance()->getConnection();

try {
    $tahun = $_GET['tahun'] ?? date('Y');
    $min_tunggakan = $_GET['min_tunggakan'] ?? 2;
    $search = $_GET['search'] ?? '';

    // Ambil jumlah iuran bulanan dari settings
    $iuran_per_bulan = (float)get_setting('monthly_fee', 50000);

    // Bulan yang sudah seharusnya dibayar (tidak termasuk bulan ini)
    $current_year = (int)date('Y');
    $current_month = (int)date('m');

    // Jika tahun yang difilter bukan tahun ini, maka 12 bulan harus dibayar
    if ($tahun < $current_year) {
        $months_due = 12;
        $month_limit = 13; // to include up to month 12
    } elseif ($tahun > $current_year) {
        $months_due = 0;
        $month_limit = 1; // to include no months
    } else { // current year
        $months_due = $current_month - 1;
        $month_limit = $current_month;
    }

    $params = [$months_due, $tahun, $month_limit, $min_tunggakan];
    $types = 'iiii';

    $query = "
        SELECT 
            w.no_kk, 
            w.nama_lengkap,
            CONCAT(r.blok, ' / ', r.nomor) as alamat,
            (GREATEST(0, ? - (SELECT COUNT(i.id) FROM iuran i WHERE i.no_kk = w.no_kk AND i.periode_tahun = ? AND i.periode_bulan < ?))) as jumlah_tunggakan
        FROM warga w
        JOIN rumah r ON w.no_kk = r.no_kk_penghuni
        WHERE w.status_dalam_keluarga = 'Kepala Keluarga'
    ";

    if (!empty($search)) {
        $query .= " AND w.nama_lengkap LIKE ?";
        $params[] = "%{$search}%";
        $types .= 's';
    }

    $query .= " HAVING jumlah_tunggakan >= ? ORDER BY jumlah_tunggakan DESC, w.nama_lengkap ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total_potensi = 0;
    foreach ($result as &$row) {
        $row['total_tunggakan'] = $row['jumlah_tunggakan'] * $iuran_per_bulan;
        $total_potensi += $row['total_tunggakan'];
    }
    unset($row);

    echo json_encode([
        'status' => 'success',
        'data' => $result,
        'summary' => [
            'total_warga' => count($result),
            'total_potensi' => $total_potensi
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error: ' . $e->getMessage()]);
}

$conn->close();