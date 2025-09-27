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
$kategori = $_GET['kategori'] ?? '';

try {
    if ($action === 'monthly_summary') {
        // Data for annual bar chart
        $pemasukan = array_fill(0, 12, 0);
        $pengeluaran = array_fill(0, 12, 0);
        
        $query = "SELECT MONTH(tanggal) as bulan, jenis, SUM(jumlah) as total FROM kas WHERE YEAR(tanggal) = ?";
        $params = [$tahun];
        $types = 'i';

        if (!empty($kategori)) {
            $query .= " AND kategori = ?";
            $params[] = $kategori;
            $types .= 's';
        }

        $query .= " GROUP BY MONTH(tanggal), jenis";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $month_index = $row['bulan'] - 1;
            if ($row['jenis'] === 'masuk') {
                $pemasukan[$month_index] = (float)$row['total'];
            } else {
                $pengeluaran[$month_index] = (float)$row['total'];
            }
        }
        $stmt->close();

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        echo json_encode(['status' => 'success', 'data' => ['labels' => $labels, 'pemasukan' => $pemasukan, 'pengeluaran' => $pengeluaran]]);

    } elseif ($action === 'expense_categories') {
        // Data for annual pie chart. This chart should not be affected by the category filter.
        $stmt = $conn->prepare("SELECT kategori, SUM(jumlah) as total FROM kas WHERE jenis = 'keluar' AND YEAR(tanggal) = ? GROUP BY kategori ORDER BY total DESC");
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
        $kategori_filter_saldo = '';
        $kategori_filter_bulanan = '';
        $params_saldo = [$first_day_of_month];
        $types_saldo = 's';
        $params_bulanan = [$tahun, $bulan];
        $types_bulanan = 'ii';

        if (!empty($kategori)) {
            $kategori_filter_saldo = " AND kategori = ?";
            $params_saldo[] = $kategori;
            $types_saldo .= 's';
            $kategori_filter_bulanan = " AND kategori = ?";
            $params_bulanan[] = $kategori;
            $types_bulanan .= 's';
        }

        // Calculate Saldo Awal
        $stmt_saldo_awal = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END) as saldo_awal FROM kas WHERE tanggal < ? $kategori_filter_saldo");
        $stmt_saldo_awal->bind_param($types_saldo, ...$params_saldo);
        $stmt_saldo_awal->execute();
        $saldo_awal = (float)($stmt_saldo_awal->get_result()->fetch_assoc()['saldo_awal'] ?? 0);
        $stmt_saldo_awal->close();

        // Calculate Pemasukan & Pengeluaran for the month
        $stmt_monthly = $conn->prepare("
            SELECT 
                SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) as total_pemasukan,
                SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) as total_pengeluaran
            FROM kas 
            WHERE YEAR(tanggal) = ? AND MONTH(tanggal) = ? $kategori_filter_bulanan
        ");
        $stmt_monthly->bind_param($types_bulanan, ...$params_bulanan);
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