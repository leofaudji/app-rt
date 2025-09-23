<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

try {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        throw new Exception("Semua field wajib diisi.");
    }

    if ($new_password !== $confirm_password) {
        throw new Exception("Password baru dan konfirmasi password tidak cocok.");
    }

    if (strlen($new_password) < 6) {
        throw new Exception("Password baru minimal harus 6 karakter.");
    }

    // Get current user's password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception("Pengguna tidak ditemukan.");
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        throw new Exception("Password saat ini salah.");
    }

    // Hash new password and update
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt_update->bind_param("si", $new_password_hash, $user_id);
    $stmt_update->execute();
    $stmt_update->close();

    log_activity($_SESSION['username'], 'Ganti Password', 'Pengguna berhasil mengganti password sendiri.');
    echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>