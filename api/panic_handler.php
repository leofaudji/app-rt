<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Define get_client_ip function if it doesn't exist. 
// Ideally, this should be in a global functions file included by bootstrap.php
if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? $_SESSION['username'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Find warga_id from user's nama_panggilan (username)
        $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
        $stmt_warga->bind_param("s", $_SESSION['username']);
        $stmt_warga->execute();
        $warga = $stmt_warga->get_result()->fetch_assoc();
        $warga_id = $warga['id'] ?? null;
        $stmt_warga->close();

        if (!$warga_id) {
            throw new Exception("Profil warga Anda tidak ditemukan untuk mencatat log panik.");
        }
        
        // Log the panic event to the new panic_log table
        $ip_address = get_client_ip();
        $stmt_log = $conn->prepare("INSERT INTO panic_log (warga_id, ip_address) VALUES (?, ?)");
        $stmt_log->bind_param("is", $warga_id, $ip_address);
        $stmt_log->execute();
        $stmt_log->close();

        // Log the panic event to activity_log
        log_activity($_SESSION['username'], 'Tombol Panik', "Tombol panik diaktifkan oleh {$nama_lengkap}.");

        // Create notification for all admins and bendahara
        $stmt_users = $conn->query("SELECT id FROM users WHERE role IN ('admin', 'bendahara')");
        $admins_and_bendahara = $stmt_users->fetch_all(MYSQLI_ASSOC);
        $stmt_users->close();

        if (count($admins_and_bendahara) > 0) {
            $message = "DARURAT: Tombol Panik diaktifkan oleh {$nama_lengkap}!";
            $link = '/log-panik'; 
            
            $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'panic_button', ?, ?)");
            foreach ($admins_and_bendahara as $user) {
                $stmt_notif->bind_param("iss", $user['id'], $message, $link);
                $stmt_notif->execute();
            }
            $stmt_notif->close();
        }

        echo json_encode(['status' => 'success', 'message' => 'Sinyal darurat telah dikirim ke pengurus RT.']);

    } else {
        throw new Exception("Metode request tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>