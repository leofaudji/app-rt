<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/bootstrap.php';

// Pastikan user sudah login dan adalah admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required.']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_POST['action'] ?? 'list';
$current_user_id = $_SESSION['user_id'] ?? 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // --- GET: Mengambil daftar pengguna ---
        $result = $conn->query("SELECT id, username, nama_lengkap, role, created_at FROM users ORDER BY username ASC");
        $users = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $users]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- POST: Aksi untuk CUD (Create, Update, Delete) ---
        switch ($action) {
            case 'add':
                if (empty($_POST['username']) || empty($_POST['password'])) {
                    throw new Exception("Username dan password wajib diisi untuk pengguna baru.");
                }

                // Cek duplikasi username
                $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt_check->bind_param("s", $_POST['username']);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    throw new Exception("Username sudah digunakan.");
                }
                $stmt_check->close();

                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, nama_lengkap, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $_POST['username'], $_POST['nama_lengkap'], $password_hash, $_POST['role']);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Tambah Pengguna', 'Menambahkan pengguna baru: ' . $_POST['username']);
                echo json_encode(['status' => 'success', 'message' => 'Pengguna baru berhasil ditambahkan.']);
                break;

            case 'update':
                $id = $_POST['id'] ?? 0;
                if (empty($id)) throw new Exception("ID pengguna tidak valid.");

                $password_sql = "";
                $params = [$_POST['username'], $_POST['nama_lengkap'], $_POST['role']];
                $types = "sssi";

                if (!empty($_POST['password'])) {
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $password_sql = ", password = ?";
                    $params[] = $password_hash;
                    $types .= "s";
                }
                
                $params[] = $id; // Add id at the end

                $stmt = $conn->prepare("UPDATE users SET username = ?, nama_lengkap = ?, role = ? {$password_sql} WHERE id = ?");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Pengguna', 'Mengubah data pengguna: ' . $_POST['username']);
                echo json_encode(['status' => 'success', 'message' => 'Data pengguna berhasil diperbarui.']);
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;

                // Validasi keamanan
                if ($id == $current_user_id) {
                    throw new Exception("Anda tidak dapat menghapus akun Anda sendiri.");
                }
                
                // Cek apakah ini satu-satunya admin
                $stmt_check = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt_check->bind_param("i", $id);
                $stmt_check->execute();
                $user_to_delete = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if ($user_to_delete && $user_to_delete['role'] === 'admin') {
                    $admin_count_result = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'admin'");
                    $admin_count = $admin_count_result->fetch_assoc()['total'];
                    if ($admin_count <= 1) {
                        throw new Exception("Tidak dapat menghapus satu-satunya admin yang tersisa.");
                    }
                }

                $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Pengguna', 'Menghapus pengguna ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Pengguna berhasil dihapus.']);
                break;
            
            case 'get_single':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("SELECT id, username, nama_lengkap, role FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                if ($user) {
                    echo json_encode(['status' => 'success', 'data' => $user]);
                } else {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Data pengguna tidak ditemukan.']);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
                break;
        }
        if (isset($stmt)) $stmt->close();
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>