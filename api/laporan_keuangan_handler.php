<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');

try {
    if ($action === 'monthly_summary') {
        // Data for annual bar chart
        $pemasukan = array_fill(0, 12, 0);
        $pengeluaran = array_fill(0, 12, 0);

        $stmt = $conn->prepare("SELECT MONTH(tanggal) as bulan, jenis, SUM(jumlah) as total FROM kas WHERE YEAR(tanggal) = ? GROUP BY MONTH(tanggal), jenis");
        $stmt->bind_param("i", $tahun);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $month_index = $row['bulan'] - 1;
            if ($row['jenis'] == 'masuk') {
                $pemasukan[$month_index] = (float)$row['total'];
            } else {
                $pengeluaran[$month_index] = (float)$row['total'];
            }
        }
        $stmt->close();

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        echo json_encode(['status' => 'success', 'data' => ['labels' => $labels, 'pemasukan' => $pemasukan, 'pengeluaran' => $pengeluaran]]);

    } elseif ($action === 'expense_categories') {
        // Data for annual pie chart
        $stmt = $conn->prepare("SELECT kategori, SUM(jumlah) as total FROM kas WHERE jenis = 'keluar' AND YEAR(tanggal) = ? GROUP BY kategori");
        $stmt->bind_param("i", $tahun);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[$row['kategori']] = (float)$row['total'];
        }
        $stmt->close();
        echo json_encode(['status' => 'success', 'data' => $categories]);

    } elseif ($action === 'get_monthly_summary_details') {
        // Data for the new summary cards
        $first_day_of_month = "$tahun-$bulan-01";

        // Calculate Saldo Awal
        $stmt_saldo_awal = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END) as saldo_awal FROM kas WHERE tanggal < ?");
        $stmt_saldo_awal->bind_param("s", $first_day_of_month);
        $stmt_saldo_awal->execute();
        $saldo_awal = (float)($stmt_saldo_awal->get_result()->fetch_assoc()['saldo_awal'] ?? 0);
        $stmt_saldo_awal->close();

        // Calculate Pemasukan & Pengeluaran for the month
        $stmt_monthly = $conn->prepare("
            SELECT 
                SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) as total_pemasukan,
                SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) as total_pengeluaran
            FROM kas 
            WHERE YEAR(tanggal) = ? AND MONTH(tanggal) = ?
        ");
        $stmt_monthly->bind_param("ii", $tahun, $bulan);
        $stmt_monthly->execute();
        $monthly_totals = $stmt_monthly->get_result()->fetch_assoc();
        $total_pemasukan = (float)($monthly_totals['total_pemasukan'] ?? 0);
        $total_pengeluaran = (float)($monthly_totals['total_pengeluaran'] ?? 0);
        $stmt_monthly->close();

        $saldo_akhir = $saldo_awal + $total_pemasukan - $total_pengeluaran;

        $summary = [
            'saldo_awal' => $saldo_awal,
            'total_pemasukan' => $total_pemasukan,
            'total_pengeluaran' => $total_pengeluaran,
            'saldo_akhir' => $saldo_akhir
        ];

        echo json_encode(['status' => 'success', 'data' => $summary]);

    } else {
        throw new Exception("Aksi tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>