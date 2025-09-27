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

    // Tentukan bulan-bulan yang harus sudah dibayar pada tahun yang dipilih
    $current_year = (int)date('Y');
    $current_month = (int)date('m');
    $months_due_list = [];

    if ($tahun < $current_year) {
        $months_due_list = range(1, 12);
    } elseif ($tahun > $current_year) {
        $months_due_list = []; // Tidak ada tunggakan untuk tahun depan
    } else { // Tahun ini, tunggakan dihitung sampai bulan kemarin
        if ($current_month > 1) {
            $months_due_list = range(1, $current_month - 1);
        }
    }

    // Ambil semua kepala keluarga yang aktif
    $warga_query = "
        SELECT w.no_kk, w.nama_lengkap, CONCAT(r.blok, ' / ', r.nomor) as alamat
        FROM warga w
        JOIN rumah r ON w.no_kk = r.no_kk_penghuni
        WHERE w.status_dalam_keluarga = 'Kepala Keluarga'
    ";
    $params = [];
    $types = '';
    if (!empty($search)) {
        $warga_query .= " AND w.nama_lengkap LIKE ?";
        $params[] = "%{$search}%";
        $types .= 's';
    }
    $warga_query .= " ORDER BY w.nama_lengkap ASC";
    $stmt_warga = $conn->prepare($warga_query);
    if (!empty($params)) {
        $stmt_warga->bind_param($types, ...$params);
    }
    $stmt_warga->execute();
    $all_warga = $stmt_warga->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_warga->close();

    // Ambil semua data iuran untuk tahun yang dipilih untuk efisiensi
    $stmt_iuran = $conn->prepare("SELECT no_kk, periode_bulan FROM iuran WHERE periode_tahun = ?");
    $stmt_iuran->bind_param('i', $tahun);
    $stmt_iuran->execute();
    $paid_iuran_raw = $stmt_iuran->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_iuran->close();

    // Kelompokkan iuran yang sudah dibayar per no_kk untuk pencarian cepat
    $paid_iuran_map = [];
    foreach ($paid_iuran_raw as $iuran) {
        $paid_iuran_map[$iuran['no_kk']][] = (int)$iuran['periode_bulan'];
    }

    $result = [];
    $total_potensi = 0;

    foreach ($all_warga as $warga) {
        $paid_months = $paid_iuran_map[$warga['no_kk']] ?? [];
        $unpaid_months = array_diff($months_due_list, $paid_months);
        
        $jumlah_tunggakan = count($unpaid_months);
        if ($jumlah_tunggakan < $min_tunggakan) {
            continue;
        }

        $total_tunggakan_amount = 0;
        foreach ($unpaid_months as $unpaid_month) {
            // Gunakan fungsi baru untuk mendapatkan iuran yang benar untuk setiap bulan
            $total_tunggakan_amount += get_fee_for_period($tahun, $unpaid_month);
        }

        $warga['jumlah_tunggakan'] = $jumlah_tunggakan;
        $warga['total_tunggakan'] = $total_tunggakan_amount;
        $result[] = $warga;
        $total_potensi += $total_tunggakan_amount;
    }

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