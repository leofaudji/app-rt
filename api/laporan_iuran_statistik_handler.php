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
$tahun = $_GET['tahun'] ?? date('Y');

try {
    // --- Data Pemasukan Iuran per Bulan ---
    $pemasukan_per_bulan = array_fill(1, 12, 0);
    $stmt_pemasukan = $conn->prepare("
        SELECT periode_bulan, SUM(jumlah) as total 
        FROM iuran 
        WHERE periode_tahun = ? 
        GROUP BY periode_bulan
    ");
    $stmt_pemasukan->bind_param("i", $tahun);
    $stmt_pemasukan->execute();
    $result_pemasukan = $stmt_pemasukan->get_result();
    while ($row = $result_pemasukan->fetch_assoc()) {
        $pemasukan_per_bulan[(int)$row['periode_bulan']] = (float)$row['total'];
    }
    $stmt_pemasukan->close();

    // --- Data Kepatuhan per Bulan ---
    $kepatuhan_per_bulan = array_fill(1, 12, 0);

    // Get total KK aktif
    $result_total_kk = $conn->query("SELECT COUNT(id) as total FROM rumah WHERE no_kk_penghuni IS NOT NULL AND no_kk_penghuni != ''");
    $total_kk = (int)($result_total_kk->fetch_assoc()['total'] ?? 0);

    if ($total_kk > 0) {
        // Get jumlah KK yang sudah bayar per bulan
        $stmt_lunas = $conn->prepare("
            SELECT periode_bulan, COUNT(id) as jumlah_lunas 
            FROM iuran 
            WHERE periode_tahun = ? 
            GROUP BY periode_bulan
        ");
        $stmt_lunas->bind_param("i", $tahun);
        $stmt_lunas->execute();
        $result_lunas = $stmt_lunas->get_result();
        while ($row = $result_lunas->fetch_assoc()) {
            $persentase = ((int)$row['jumlah_lunas'] / $total_kk) * 100;
            $kepatuhan_per_bulan[(int)$row['periode_bulan']] = round($persentase, 2);
        }
        $stmt_lunas->close();
    }

    $bulan_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'labels' => $bulan_labels,
            'pemasukan' => [
                'label' => 'Total Pemasukan Iuran',
                'data' => array_values($pemasukan_per_bulan)
            ],
            'kepatuhan' => [
                'label' => 'Tingkat Kepatuhan (%)',
                'data' => array_values($kepatuhan_per_bulan)
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error: ' . $e->getMessage()]);
}

$conn->close();