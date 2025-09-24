<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/bootstrap.php';

// Middleware check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin atau Bendahara access required.']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_REQUEST['action'] ?? 'list';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $tahun = $_GET['tahun'] ?? 0;
        $bulan = $_GET['bulan'] ?? 0;
        $status = $_GET['status'] ?? 'semua';
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit_str = $_GET['limit'] ?? '10';
        $use_limit = $limit_str !== 'all';
        $limit = (int)$limit_str;
        $offset = ($page - 1) * $limit;

        if (empty($tahun) || empty($bulan)) {
            throw new Exception("Periode tahun dan bulan wajib diisi.");
        }

        $params = [];
        $types = '';

        $base_query = "
            SELECT 
                r.no_kk_penghuni as no_kk,
                CONCAT(r.blok, ' / ', r.nomor) as alamat,
                w.nama_lengkap as kepala_keluarga,
                i.id as iuran_id, i.tanggal_bayar, i.jumlah
            FROM rumah r
            LEFT JOIN warga w ON r.no_kk_penghuni = w.no_kk AND w.status_dalam_keluarga = 'Kepala Keluarga'
            LEFT JOIN iuran i ON r.no_kk_penghuni = i.no_kk AND i.periode_tahun = ? AND i.periode_bulan = ?
            WHERE r.no_kk_penghuni IS NOT NULL AND r.no_kk_penghuni != ''
        ";
        $count_query = "SELECT COUNT(r.id) as total FROM rumah r LEFT JOIN warga w ON r.no_kk_penghuni = w.no_kk AND w.status_dalam_keluarga = 'Kepala Keluarga' WHERE r.no_kk_penghuni IS NOT NULL AND r.no_kk_penghuni != ''";
        $data_query = $base_query;

        $params = [$tahun, $bulan];
        $types = 'ii';
        
        if (!empty($search)) {
            $data_query .= " AND w.nama_lengkap LIKE ?";
            $count_query .= " AND w.nama_lengkap LIKE ?";
            $params[] = "%{$search}%";
            $types .= 's';
        }

        $having_clause = '';
        if ($status === 'lunas') {
            $having_clause = " HAVING iuran_id IS NOT NULL";
        } elseif ($status === 'belum_lunas') {
            $having_clause = " HAVING iuran_id IS NULL";
        }
        $data_query .= $having_clause;

        // Get total records for pagination (this is tricky with HAVING, so we count differently)
        $stmt_total = $conn->prepare($base_query . $having_clause);
        $stmt_total->bind_param($types, ...$params);
        $stmt_total->execute();
        $total_records = $stmt_total->get_result()->num_rows;
        $stmt_total->close();

        $data_query .= " ORDER BY r.blok, r.nomor";
        if ($use_limit) {
            $data_query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
        }

        $stmt = $conn->prepare($data_query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $total_pages = $use_limit ? ceil($total_records / $limit) : 1;

        echo json_encode(['status' => 'success', 'data' => $result, 'pagination' => [
            'total_records' => (int)$total_records, 'total_pages' => (int)$total_pages, 'current_page' => $page, 'limit' => $limit
        ]]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bayar') {
        $no_kk = $_POST['no_kk'] ?? '';
        $tahun = $_POST['periode_tahun'] ?? 0;
        $bulan = $_POST['periode_bulan'] ?? 0;
        $jumlah = $_POST['jumlah'] ?? 0;
        $tanggal_bayar = $_POST['tanggal_bayar'] ?? '';
        $catatan = !empty($_POST['catatan']) ? trim($_POST['catatan']) : null;
        $user_id = $_SESSION['user_id'];

        if (empty($no_kk) || empty($tahun) || empty($bulan) || empty($jumlah) || empty($tanggal_bayar)) {
            throw new Exception("Semua field wajib diisi.");
        }

        // Gunakan INSERT ... ON DUPLICATE KEY UPDATE untuk mencegah duplikasi
        $stmt = $conn->prepare("
            INSERT INTO iuran (no_kk, periode_tahun, periode_bulan, jumlah, tanggal_bayar, dicatat_oleh, catatan)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE jumlah=VALUES(jumlah), tanggal_bayar=VALUES(tanggal_bayar), dicatat_oleh=VALUES(dicatat_oleh), catatan=VALUES(catatan)
        ");
        $stmt->bind_param("siidsis", $no_kk, $tahun, $bulan, $jumlah, $tanggal_bayar, $user_id, $catatan);
        $stmt->execute();
        $stmt->close();

        // --- Integrasi Otomatis ke Kas ---
        // Ambil nama warga untuk keterangan kas
        $stmt_warga = $conn->prepare("SELECT nama_lengkap FROM warga WHERE no_kk = ? AND status_dalam_keluarga = 'Kepala Keluarga' LIMIT 1");
        $stmt_warga->bind_param("s", $no_kk);
        $stmt_warga->execute();
        $nama_warga = $stmt_warga->get_result()->fetch_assoc()['nama_lengkap'] ?? 'KK: ' . $no_kk;
        $stmt_warga->close();

        // Buat entri kas baru dengan kategori 'Iuran Warga'
        $keterangan_kas = "Pembayaran iuran {$nama_warga} - " . date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun));
        $kategori_kas = 'Iuran Warga';
        $stmt_kas = $conn->prepare("INSERT INTO kas (tanggal, jenis, kategori, keterangan, jumlah, dicatat_oleh) VALUES (?, 'masuk', ?, ?, ?, ?)");
        $stmt_kas->bind_param("sssdi", $tanggal_bayar, $kategori_kas, $keterangan_kas, $jumlah, $user_id);
        $stmt_kas->execute();
        $stmt_kas->close();
        // --- Akhir Integrasi ---
        
        log_activity($_SESSION['username'], 'Catat Iuran', "Mencatat iuran untuk No. KK: {$no_kk}, periode: {$tahun}-{$bulan}");
        echo json_encode(['status' => 'success', 'message' => 'Pembayaran iuran berhasil disimpan.']);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_summary') {
        $tahun = $_GET['tahun'] ?? 0;
        $bulan = $_GET['bulan'] ?? 0;
        $search = $_GET['search'] ?? '';

        if (empty($tahun) || empty($bulan)) {
            throw new Exception("Periode tahun dan bulan wajib diisi untuk ringkasan.");
        }

        $params = [];
        $types = '';

        // Base query to get all relevant KKs and their payment status for the period
        $base_query = "
            FROM rumah r
            LEFT JOIN warga w ON r.no_kk_penghuni = w.no_kk AND w.status_dalam_keluarga = 'Kepala Keluarga'
            LEFT JOIN iuran i ON r.no_kk_penghuni = i.no_kk AND i.periode_tahun = ? AND i.periode_bulan = ?
            WHERE r.no_kk_penghuni IS NOT NULL AND r.no_kk_penghuni != ''
        ";
        $params = [$tahun, $bulan];
        $types = 'ii';

        if (!empty($search)) {
            $base_query .= " AND w.nama_lengkap LIKE ?";
            $params[] = "%{$search}%";
            $types .= 's';
        }

        // Calculate Total Pemasukan and Belum Bayar from the filtered set
        $query_summary = "
            SELECT 
                SUM(CASE WHEN i.id IS NOT NULL THEN i.jumlah ELSE 0 END) as total_pemasukan,
                COUNT(CASE WHEN i.id IS NULL THEN r.id ELSE NULL END) as total_belum_bayar
            " . $base_query;
        
        $stmt_summary = $conn->prepare($query_summary);
        $stmt_summary->bind_param($types, ...$params);
        $stmt_summary->execute();
        $summary_data = $stmt_summary->get_result()->fetch_assoc();
        $stmt_summary->close();

        $total_pemasukan = $summary_data['total_pemasukan'] ?? 0;
        $kk_belum_bayar = $summary_data['total_belum_bayar'] ?? 0;

        $setting_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'monthly_fee'");
        $jumlah_iuran_per_kk = (float)($setting_result->fetch_assoc()['setting_value'] ?? 50000);
        $jumlah_belum_bayar = $kk_belum_bayar * $jumlah_iuran_per_kk;

        echo json_encode(['status' => 'success', 'data' => [
            'total_pemasukan' => $total_pemasukan,
            'jumlah_belum_bayar' => $jumlah_belum_bayar
        ]]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_history') {
        $no_kk = $_GET['no_kk'] ?? '';
        if (empty($no_kk)) {
            throw new Exception("No. KK wajib diisi.");
        }

        // Get Kepala Keluarga Info
        $stmt_warga = $conn->prepare("SELECT nama_lengkap, alamat FROM warga WHERE no_kk = ? AND status_dalam_keluarga = 'Kepala Keluarga' LIMIT 1");
        $stmt_warga->bind_param("s", $no_kk);
        $stmt_warga->execute();
        $warga = $stmt_warga->get_result()->fetch_assoc();
        $stmt_warga->close();

        if (!$warga) {
            throw new Exception("Kepala Keluarga untuk No. KK tersebut tidak ditemukan.");
        }

        // Get Iuran History
        $stmt_history = $conn->prepare("SELECT * FROM iuran WHERE no_kk = ? ORDER BY periode_tahun DESC, periode_bulan DESC");
        $stmt_history->bind_param("s", $no_kk);
        $stmt_history->execute();
        $history = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_history->close();

        echo json_encode(['status' => 'success', 'data' => ['warga' => $warga, 'history' => $history]]);

    } else {
        throw new Exception("Aksi tidak valid.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>