<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();

try {
    // Find no_kk from user's nama_panggilan (username)
    $stmt_warga = $conn->prepare("SELECT no_kk FROM warga WHERE nama_panggilan = ?");
    $stmt_warga->bind_param("s", $_SESSION['username']);
    $stmt_warga->execute();
    $warga = $stmt_warga->get_result()->fetch_assoc();
    $stmt_warga->close();

    if (!$warga || empty($warga['no_kk'])) {
        // If the user is an admin/bendahara but not a warga, or has no KK, return empty.
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }
    $no_kk = $warga['no_kk'];

    // Get Iuran History for this KK
    $stmt_history = $conn->prepare("
        SELECT i.*, u.nama_lengkap as pencatat
        FROM iuran i
        LEFT JOIN users u ON i.dicatat_oleh = u.id
        WHERE i.no_kk = ? 
        ORDER BY i.periode_tahun DESC, i.periode_bulan DESC
    ");
    $stmt_history->bind_param("s", $no_kk);
    $stmt_history->execute();
    $history = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_history->close();

    echo json_encode(['status' => 'success', 'data' => $history]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>