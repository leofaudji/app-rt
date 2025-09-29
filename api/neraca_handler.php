<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

$conn = Database::getInstance()->getConnection();

try {
    // 1. Hitung Total Saldo Kas RT (Aset)
    $result_kas = $conn->query("SELECT SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END) as total FROM kas");
    $total_kas = (float)($result_kas->fetch_assoc()['total'] ?? 0);

    // 2. Hitung Total Saldo Tabungan Warga (Liabilitas)
    $result_tabungan = $conn->query("SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) as total FROM tabungan_warga");
    $total_tabungan = (float)($result_tabungan->fetch_assoc()['total'] ?? 0);

    // 3. Hitung Posisi Bersih (Ekuitas)
    // Ini adalah dana yang benar-benar milik RT setelah dikurangi dana titipan warga.
    $posisi_bersih = $total_kas - $total_tabungan;

    $data = [
        'total_kas' => $total_kas,
        'total_tabungan' => $total_tabungan,
        'posisi_bersih' => $posisi_bersih
    ];

    echo json_encode(['status' => 'success', 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error: ' . $e->getMessage()]);
}

$conn->close();
?>