<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required.']);
    exit;
}

$conn = Database::getInstance()->getConnection();

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Get total records
    $countResult = $conn->query("SELECT COUNT(id) as total FROM panic_log");
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get paginated data
    $query = "
        SELECT 
            pl.timestamp, pl.ip_address,
            w.nama_lengkap, w.alamat, w.no_telepon
        FROM panic_log pl
        JOIN warga w ON pl.warga_id = w.id
        ORDER BY pl.timestamp DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => $logs,
        'pagination' => [
            'total_records' => (int)$totalRecords,
            'total_pages' => (int)$totalPages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>