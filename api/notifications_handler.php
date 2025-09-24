<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

// Dapatkan warga_id yang terkait dengan user yang login
$stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
$stmt_warga->bind_param("s", $_SESSION['username']);
$stmt_warga->execute();
$warga = $stmt_warga->get_result()->fetch_assoc();
$warga_id = $warga['id'] ?? 0;
$stmt_warga->close();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list') {
            // Get unread notifications and count
            $stmt_count = $conn->prepare("SELECT COUNT(id) as unread_count FROM notifications WHERE (user_id = ? OR warga_id = ?) AND is_read = 0");
            $stmt_count->bind_param("ii", $user_id, $warga_id);
            $stmt_count->execute();
            $unread_count = $stmt_count->get_result()->fetch_assoc()['unread_count'] ?? 0;
            $stmt_count->close();

            // Get latest 7 unread or recently read notifications for display
            $stmt_list = $conn->prepare("SELECT id, message, link, created_at, is_read FROM notifications WHERE (user_id = ? OR warga_id = ?) ORDER BY created_at DESC LIMIT 7");
            $stmt_list->bind_param("ii", $user_id, $warga_id);
            $stmt_list->execute();
            $notifications = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_list->close();

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'unread_count' => (int)$unread_count,
                    'notifications' => $notifications
                ]
            ]);
        } else {
            throw new Exception("Aksi tidak valid.");
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'mark_all_read') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR warga_id = ?) AND is_read = 0");
            $stmt->bind_param("ii", $user_id, $warga_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success', 'message' => 'Semua notifikasi ditandai telah dibaca.']);
        } else {
            throw new Exception("Aksi tidak valid.");
        }
    } else {
        throw new Exception("Metode request tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>