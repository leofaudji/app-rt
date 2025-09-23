<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // Find warga_id from user's nama_panggilan (username)
    $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
    $stmt_warga->bind_param("s", $_SESSION['username']);
    $stmt_warga->execute();
    $warga = $stmt_warga->get_result()->fetch_assoc();
    $warga_id = $warga['id'] ?? null;
    $stmt_warga->close();

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $query = "
            SELECT p.*, u.nama_lengkap as creator,
            (SELECT COUNT(*) FROM polling_votes pv WHERE pv.polling_id = p.id) as total_votes,
            (SELECT pv.selected_option FROM polling_votes pv WHERE pv.polling_id = p.id AND pv.warga_id = ?) as user_vote
            FROM polling p
            LEFT JOIN users u ON p.created_by = u.id
            ORDER BY p.created_at DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $warga_id);
        $stmt->execute();
        $polls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get vote distribution for each poll
        $stmt_votes = $conn->prepare("SELECT selected_option, COUNT(id) as count FROM polling_votes WHERE polling_id = ? GROUP BY selected_option");
        foreach ($polls as &$poll) {
            $poll['options'] = json_decode($poll['options'], true);
            $poll['results'] = array_fill(0, count($poll['options']), 0);
            
            $stmt_votes->bind_param("i", $poll['id']);
            $stmt_votes->execute();
            $votes_result = $stmt_votes->get_result();
            while ($vote_row = $votes_result->fetch_assoc()) {
                $poll['results'][(int)$vote_row['selected_option']] = (int)$vote_row['count'];
            }
        }
        unset($poll); // Unset reference
        $stmt_votes->close();

        echo json_encode(['status' => 'success', 'data' => $polls]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'create':
                if ($role !== 'admin') throw new Exception("Hanya admin yang dapat membuat polling.");
                $question = $_POST['question'] ?? '';
                $options = $_POST['options'] ?? [];
                if (empty($question) || count($options) < 2) {
                    throw new Exception("Pertanyaan dan minimal 2 opsi jawaban wajib diisi.");
                }
                $options_json = json_encode(array_values($options));

                $stmt = $conn->prepare("INSERT INTO polling (question, options, created_by) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $question, $options_json, $user_id);
                $stmt->execute();

                // Create notification for other admins and bendahara
                $stmt_users = $conn->prepare("SELECT id FROM users WHERE role IN ('admin', 'bendahara') AND id != ?");
                $stmt_users->bind_param("i", $user_id);
                $stmt_users->execute();
                $admins_and_bendahara = $stmt_users->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_users->close();

                if (count($admins_and_bendahara) > 0) {
                    $message = "Polling baru dibuat: '" . substr($question, 0, 50) . "...'";
                    $link = '/polling';
                    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'polling_baru', ?, ?)");
                    foreach ($admins_and_bendahara as $user) {
                        $stmt_notif->bind_param("iss", $user['id'], $message, $link);
                        $stmt_notif->execute();
                    }
                    $stmt_notif->close();
                }

                log_activity($_SESSION['username'], 'Buat Polling', "Membuat polling: {$question}");
                echo json_encode(['status' => 'success', 'message' => 'Jajak pendapat berhasil dibuat.']);
                break;

            case 'vote':
                if (!$warga_id) throw new Exception("Hanya warga terdaftar yang dapat memilih.");
                $polling_id = $_POST['polling_id'] ?? 0;
                $selected_option = $_POST['selected_option'] ?? -1;

                // Check if poll is open
                $stmt_check = $conn->prepare("SELECT status FROM polling WHERE id = ?");
                $stmt_check->bind_param("i", $polling_id);
                $stmt_check->execute();
                $poll_status = $stmt_check->get_result()->fetch_assoc()['status'] ?? 'closed';
                if ($poll_status !== 'open') {
                    throw new Exception("Jajak pendapat ini sudah ditutup.");
                }

                $stmt = $conn->prepare("INSERT INTO polling_votes (polling_id, warga_id, selected_option) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $polling_id, $warga_id, $selected_option);
                try {
                    $stmt->execute();
                    echo json_encode(['status' => 'success', 'message' => 'Terima kasih, suara Anda telah direkam.']);
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) { // Duplicate entry
                        throw new Exception("Anda sudah memberikan suara pada jajak pendapat ini.");
                    }
                    throw $e;
                }
                break;

            case 'update_status':
                if ($role !== 'admin') throw new Exception("Hanya admin yang dapat mengubah status.");
                $polling_id = $_POST['polling_id'];
                $new_status = $_POST['status']; // 'open' or 'closed'
                
                $stmt = $conn->prepare("UPDATE polling SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_status, $polling_id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Polling', "Mengubah status polling ID {$polling_id} menjadi {$new_status}");
                echo json_encode(['status' => 'success', 'message' => "Status jajak pendapat berhasil diubah."]);
                break;

            case 'delete':
                if ($role !== 'admin') throw new Exception("Hanya admin yang dapat menghapus polling.");
                $polling_id = $_POST['polling_id'];
                $stmt = $conn->prepare("DELETE FROM polling WHERE id = ?");
                $stmt->bind_param("i", $polling_id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Polling', "Menghapus polling ID {$polling_id}");
                echo json_encode(['status' => 'success', 'message' => 'Jajak pendapat berhasil dihapus.']);
                break;

            default:
                throw new Exception("Aksi tidak valid.");
        }
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
$conn->close();
?>