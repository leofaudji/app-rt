<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    http_response_code(403);
    die("Akses ditolak.");
}

$conn = Database::getInstance()->getConnection();

try {
    $tahun = $_GET['tahun'] ?? date('Y');
    $min_tunggakan = $_GET['min_tunggakan'] ?? 2;
    $search = $_GET['search'] ?? '';

    $iuran_per_bulan = (float)get_setting('monthly_fee', 50000);
    $current_year = (int)date('Y');
    $current_month = (int)date('m');

    if ($tahun < $current_year) {
        $months_due = 12;
        $month_limit = 13;
    } elseif ($tahun > $current_year) {
        $months_due = 0;
        $month_limit = 1;
    } else {
        $months_due = $current_month - 1;
        $month_limit = $current_month;
    }

    $params = [$months_due, $tahun, $month_limit, $min_tunggakan];
    $types = 'iiii';

    $query = "
        SELECT 
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
    $result = $stmt->get_result();

    // --- CSV Generation ---
    $filename = "Laporan_Tunggakan_Iuran_{$tahun}.csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Header row
    fputcsv($output, ['Nama Kepala Keluarga', 'Alamat', 'Jumlah Bulan Menunggak', 'Total Tunggakan (Rp)']);

    // Data rows
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $total_tunggakan = $row['jumlah_tunggakan'] * $iuran_per_bulan;
            fputcsv($output, [
                $row['nama_lengkap'],
                $row['alamat'],
                $row['jumlah_tunggakan'],
                $total_tunggakan
            ]);
        }
    }

    fclose($output);
    $stmt->close();
    $conn->close();
    exit();

} catch (Exception $e) {
    error_log("Export Tunggakan Error: " . $e->getMessage());
    http_response_code(500);
    die("Terjadi kesalahan saat membuat file ekspor.");
}