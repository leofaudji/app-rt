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

        $params = [];
        $types = '';

        $query = "SELECT k.*, u.nama_lengkap as pencatat FROM kas k JOIN users u ON k.dicatat_oleh = u.id WHERE 1=1";
        
        if (!empty($search)) {
            $query .= " AND k.keterangan LIKE ?";
            $params[] = "%{$search}%";
            $types .= 's';
        }
        if (!empty($jenis)) {
            $query .= " AND k.jenis = ?";
            $params[] = $jenis;
            $types .= 's';
        }
        $query .= " ORDER BY k.tanggal DESC, k.created_at DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $kas = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['status' => 'success', 'data' => $kas]);

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