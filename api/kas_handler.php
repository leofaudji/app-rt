<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/bootstrap.php';

// Pastikan user sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Asumsi user_id disimpan di session saat login
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id === 0) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Sesi pengguna tidak valid.']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_POST['action'] ?? 'list';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // --- GET: Mengambil daftar transaksi kas ---
        $search = $_GET['search'] ?? '';
        $jenis = $_GET['jenis'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit_str = $_GET['limit'] ?? '10';
        $use_limit = $limit_str !== 'all';
        $limit = (int)$limit_str;
        $offset = ($page - 1) * $limit;

        $params = [];
        $types = '';

        $base_query = "FROM kas k JOIN users u ON k.dicatat_oleh = u.id WHERE 1=1";
        $count_query = "SELECT COUNT(k.id) as total " . $base_query;
        $data_query = "SELECT k.*, u.nama_lengkap as pencatat " . $base_query;
        
        if (!empty($search)) {
            $data_query .= " AND k.keterangan LIKE ?";
            $count_query .= " AND k.keterangan LIKE ?";
            $params[] = "%{$search}%";
            $types .= 's';
        }
        if (!empty($jenis)) {
            $data_query .= " AND k.jenis = ?";
            $count_query .= " AND k.jenis = ?";
            $params[] = $jenis;
            $types .= 's';
        }

        // Get total records for pagination
        $stmt_count = $conn->prepare($count_query);
        if (!empty($params)) {
            $stmt_count->bind_param($types, ...$params);
        }
        $stmt_count->execute();
        $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
        $stmt_count->close();

        $data_query .= " ORDER BY k.tanggal DESC, k.created_at DESC";
        if ($use_limit) {
            $data_query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
        }

        $stmt_data = $conn->prepare($data_query);
        if (!empty($params)) {
            $stmt_data->bind_param($types, ...$params);
        }
        $stmt_data->execute();
        $kas = $stmt_data->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_data->close();

        $total_pages = $use_limit ? ceil($total_records / $limit) : 1;

        echo json_encode(['status' => 'success', 'data' => $kas, 'pagination' => ['total_records' => (int)$total_records, 'total_pages' => (int)$total_pages, 'current_page' => $page, 'limit' => $limit]]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- POST: Aksi untuk CUD (Create, Update, Delete) ---
        switch ($action) {
            case 'add':
                $stmt = $conn->prepare("INSERT INTO kas (tanggal, jenis, kategori, keterangan, jumlah, dicatat_oleh) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdi", $_POST['tanggal'], $_POST['jenis'], $_POST['kategori'], $_POST['keterangan'], $_POST['jumlah'], $user_id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Tambah Transaksi Kas', 'Menambahkan transaksi: ' . $_POST['keterangan']);
                echo json_encode(['status' => 'success', 'message' => 'Transaksi baru berhasil ditambahkan.']);
                break;

            case 'update':
                $stmt = $conn->prepare("UPDATE kas SET tanggal=?, jenis=?, kategori=?, keterangan=?, jumlah=? WHERE id=?");
                $stmt->bind_param("ssssdi", $_POST['tanggal'], $_POST['jenis'], $_POST['kategori'], $_POST['keterangan'], $_POST['jumlah'], $_POST['id']);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Transaksi Kas', 'Mengubah transaksi ID: ' . $_POST['id']);
                echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil diperbarui.']);
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("DELETE FROM kas WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Transaksi Kas', 'Menghapus transaksi ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil dihapus.']);
                break;
            
            case 'get_single':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("SELECT * FROM kas WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $kas = $result->fetch_assoc();
                if ($kas) {
                    echo json_encode(['status' => 'success', 'data' => $kas]);
                } else {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Data transaksi tidak ditemukan.']);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
                break;
        }
        if (isset($stmt)) $stmt->close();
    }

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log("Kas API Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada database.']);
}

$conn->close();
?>