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
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $searchTerm = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit_str = $_GET['limit'] ?? '15';
        $use_limit = $limit_str !== 'all';
        $limit = (int)$limit_str;
        $offset = ($page - 1) * $limit;

        $params = [];
        $types = '';
        $countQuery = "SELECT COUNT(id) as total FROM activity_log WHERE 1=1";
        $dataQuery = "SELECT * FROM activity_log WHERE 1=1";

        if (!empty($searchTerm)) {
            $whereClause = " AND (username LIKE ? OR action LIKE ? OR details LIKE ?)";
            $countQuery .= $whereClause;
            $dataQuery .= $whereClause;
            $likeTerm = "%{$searchTerm}%";
            $params = [$likeTerm, $likeTerm, $likeTerm];
            $types = 'sss';
        }

        // Get total data for pagination
        $stmtCount = $conn->prepare($countQuery);
        if (!empty($params)) {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $totalRecords = $stmtCount->get_result()->fetch_assoc()['total'];
        $totalPages = ceil($totalRecords / $limit);
        $stmtCount->close();

        // Get data per page
        $dataQuery .= " ORDER BY timestamp DESC";
        if ($use_limit) {
            $dataQuery .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
        }

        $stmt = $conn->prepare($dataQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $totalPages = $use_limit ? ceil($totalRecords / $limit) : 1;

        echo json_encode([
            'status' => 'success', 
            'data' => $result,
            'pagination' => [
                'total_records' => (int)$totalRecords,
                'total_pages' => (int)$totalPages,
                'current_page' => $page,
                'limit' => $limit
            ]
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'clear_old':
                $days = (int)get_setting('log_cleanup_interval_days', 180);
                $stmt = $conn->prepare("DELETE FROM activity_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->bind_param("i", $days);
                $stmt->execute();
                $affected_rows = $stmt->affected_rows;
                $stmt->close();

                $months = round($days / 30);
                log_activity($_SESSION['username'], 'Bersihkan Log', "Membersihkan {$affected_rows} log yang lebih lama dari {$months} bulan.");
                echo json_encode(['status' => 'success', 'message' => "Berhasil membersihkan {$affected_rows} log lama."]);
                break;
            default:
                throw new Exception("Aksi POST tidak valid.");
        }
    } else {
        throw new Exception("Metode request tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>