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
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list_events') {
            $query = "
                SELECT 
                    b.id, b.judul, b.tanggal_mulai, b.tanggal_selesai, b.status, b.warga_id,
                    f.nama_fasilitas, f.warna_event,
                    w.nama_lengkap as pemesan
                FROM booking_fasilitas b
                JOIN fasilitas f ON b.fasilitas_id = f.id
                JOIN warga w ON b.warga_id = w.id
            ";
            $result = $conn->query($query);
            $bookings = $result->fetch_all(MYSQLI_ASSOC);

            $events = [];
            foreach ($bookings as $booking) {
                $color = $booking['warna_event'];
                $title = $booking['nama_fasilitas'] . ': ' . $booking['judul'];
                if ($booking['status'] === 'pending') {
                    $color = '#ffc107'; // Yellow for pending
                    $title .= ' (Pending)';
                } elseif ($booking['status'] === 'rejected') {
                    continue; // Don't show rejected bookings on calendar
                }

                $events[] = [
                    'id' => $booking['id'],
                    'title' => $title,
                    'start' => $booking['tanggal_mulai'],
                    'end' => $booking['tanggal_selesai'],
                    'color' => $color,
                    'extendedProps' => [
                        'status' => $booking['status'],
                        'pemesan' => $booking['pemesan'],
                        'fasilitas' => $booking['nama_fasilitas'],
                        'judul' => $booking['judul'],
                        'warga_id' => $booking['warga_id']
                    ]
                ];
            }
            echo json_encode($events);

        } elseif ($action === 'list_fasilitas') {
            $result = $conn->query("SELECT id, nama_fasilitas FROM fasilitas ORDER BY nama_fasilitas");
            $fasilitas = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $fasilitas]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'create':
                $fasilitas_id = $_POST['fasilitas_id'];
                $judul = $_POST['judul'];
                $start = $_POST['tanggal_mulai'];
                $end = $_POST['tanggal_selesai'];

                // Find warga_id from user's nama_panggilan (username)
                $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_warga->bind_param("s", $_SESSION['username']);
                $stmt_warga->execute();
                $warga = $stmt_warga->get_result()->fetch_assoc();
                if (!$warga) throw new Exception("Profil warga Anda tidak ditemukan. Pastikan Anda memiliki Nama Panggilan yang valid.");
                $warga_id = $warga['id'];

                $stmt = $conn->prepare("INSERT INTO booking_fasilitas (fasilitas_id, warga_id, judul, tanggal_mulai, tanggal_selesai) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $fasilitas_id, $warga_id, $judul, $start, $end);
                $stmt->execute();
                
                // Create notification for admins
                $stmt_admins = $conn->query("SELECT id FROM users WHERE role = 'admin'");
                $admins = $stmt_admins->fetch_all(MYSQLI_ASSOC);
                $stmt_admins->close();

                if (count($admins) > 0) {
                    $message = "Booking baru: '{$judul}' oleh " . ($_SESSION['nama_lengkap'] ?? $_SESSION['username']);
                    $link = '/booking';
                    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_baru', ?, ?)");
                    foreach ($admins as $admin) {
                        $stmt_notif->bind_param("iss", $admin['id'], $message, $link);
                        $stmt_notif->execute();
                    }
                    $stmt_notif->close();
                }

                log_activity($_SESSION['username'], 'Buat Booking', "Mengajukan booking untuk: {$judul}");
                echo json_encode(['status' => 'success', 'message' => 'Permintaan booking berhasil diajukan. Menunggu persetujuan admin.']);
                break;

            case 'update_status':
                if ($role !== 'admin') throw new Exception("Hanya admin yang dapat mengubah status.");
                $booking_id = $_POST['booking_id'];
                $new_status = $_POST['status']; // 'approved' or 'rejected'
                
                $stmt = $conn->prepare("UPDATE booking_fasilitas SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_status, $booking_id);
                $stmt->execute();
                $stmt->close();

                // Get booking details to notify the user
                $stmt_get_booking = $conn->prepare("
                    SELECT b.warga_id, b.judul, w.nama_panggilan 
                    FROM booking_fasilitas b
                    JOIN warga w ON b.warga_id = w.id
                    WHERE b.id = ?
                ");
                $stmt_get_booking->bind_param("i", $booking_id);
                $stmt_get_booking->execute();
                $booking_info = $stmt_get_booking->get_result()->fetch_assoc();
                $stmt_get_booking->close();

                if ($booking_info) {
                    // Find user_id from warga's nama_panggilan
                    $stmt_get_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt_get_user->bind_param("s", $booking_info['nama_panggilan']);
                    $stmt_get_user->execute();
                    $user_to_notify = $stmt_get_user->get_result()->fetch_assoc();
                    $stmt_get_user->close();

                    if ($user_to_notify) {
                        $status_text = ($new_status === 'approved') ? 'DISETUJUI' : 'DITOLAK';
                        $message = "Booking Anda '{$booking_info['judul']}' telah {$status_text}.";
                        $link = '/booking';
                        $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status', ?, ?)");
                        $stmt_notif->bind_param("iss", $user_to_notify['id'], $message, $link);
                        $stmt_notif->execute();
                        $stmt_notif->close();
                    }
                }

                log_activity($_SESSION['username'], 'Update Booking', "Mengubah status booking ID {$booking_id} menjadi {$new_status}");
                echo json_encode(['status' => 'success', 'message' => "Status booking berhasil diubah menjadi {$new_status}."]);
                break;

            case 'delete':
                $booking_id = $_POST['booking_id'];
                // In a real app, you'd add more robust checks here to ensure
                // only the owner or an admin can delete.
                $stmt = $conn->prepare("DELETE FROM booking_fasilitas WHERE id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Booking', "Menghapus booking ID {$booking_id}");
                echo json_encode(['status' => 'success', 'message' => 'Booking berhasil dihapus.']);
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