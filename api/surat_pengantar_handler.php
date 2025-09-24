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
        $get_action = $_GET['action'] ?? 'list';

        switch ($get_action) {
            case 'get_report':
                if ($role !== 'admin') throw new Exception("Hanya admin yang dapat melihat laporan.");

                $tipe = $_GET['tipe'] ?? 'bulanan';
                $tahun = $_GET['tahun'] ?? date('Y');
                $bulan = $_GET['bulan'] ?? date('m');
                $status = $_GET['status'] ?? 'semua';

                $params = [$tahun];
                $types = "i";
                $whereClause = "WHERE YEAR(created_at) = ?";

                if ($tipe === 'bulanan') {
                    $whereClause .= " AND MONTH(created_at) = ?";
                    $params[] = $bulan;
                    $types .= "i";
                }

                if ($status !== 'semua' && in_array($status, ['pending', 'approved', 'rejected'])) {
                    $whereClause .= " AND status = ?";
                    $params[] = $status;
                    $types .= "s";
                }

                // Get total
                $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM surat_pengantar $whereClause");
                $stmt_total->bind_param($types, ...$params);
                $stmt_total->execute();
                $total = $stmt_total->get_result()->fetch_assoc()['total'];
                $stmt_total->close();

                // Get details
                $stmt_details = $conn->prepare("SELECT jenis_surat, COUNT(*) as jumlah FROM surat_pengantar $whereClause GROUP BY jenis_surat ORDER BY jumlah DESC");
                $stmt_details->bind_param($types, ...$params);
                $stmt_details->execute();
                $details = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_details->close();

                echo json_encode(['status' => 'success', 'data' => ['total' => $total, 'details' => $details]]);
                break;

            case 'list':
            default:
                if ($role === 'admin') {
                    $query = "
                        SELECT s.*, w.nama_lengkap as pemohon, u_proc.username as pemroses 
                        FROM surat_pengantar s 
                        JOIN warga w ON s.warga_id = w.id 
                        LEFT JOIN users u_proc ON s.processed_by_id = u_proc.id
                        ORDER BY s.created_at DESC
                    ";
                    $stmt = $conn->prepare($query);
                } else {
                    // Warga can only see their own requests
                    $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                    $stmt_warga->bind_param("s", $_SESSION['username']);
                    $stmt_warga->execute();
                    $warga = $stmt_warga->get_result()->fetch_assoc();
                    if (!$warga) {
                        echo json_encode(['status' => 'success', 'data' => []]);
                        exit;
                    }
                    $warga_id = $warga['id'];

                    $query = "SELECT * FROM surat_pengantar WHERE warga_id = ? ORDER BY created_at DESC";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $warga_id);
                }
                $stmt->execute();
                $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $result]);
                break;
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'create':
                if ($role !== 'warga') throw new Exception("Hanya warga yang dapat mengajukan surat.");
                
                $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_warga->bind_param("s", $_SESSION['username']);
                $stmt_warga->execute();
                $warga = $stmt_warga->get_result()->fetch_assoc();
                if (!$warga) throw new Exception("Profil warga Anda tidak ditemukan.");
                $warga_id = $warga['id'];

                $stmt = $conn->prepare("INSERT INTO surat_pengantar (warga_id, jenis_surat, keperluan) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $warga_id, $_POST['jenis_surat'], $_POST['keperluan']);
                $stmt->execute();

                // Notify admins
                $stmt_admins = $conn->query("SELECT id FROM users WHERE role = 'admin'");
                $admins = $stmt_admins->fetch_all(MYSQLI_ASSOC);
                if (count($admins) > 0) {
                    $message = "Permintaan surat baru dari " . $_SESSION['nama_lengkap'];
                    $link = '/surat-pengantar';
                    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'surat_baru', ?, ?)");
                    foreach ($admins as $admin) {
                        $stmt_notif->bind_param("iss", $admin['id'], $message, $link);
                        $stmt_notif->execute();
                    }
                }

                log_activity($_SESSION['username'], 'Ajukan Surat', "Mengajukan surat: " . $_POST['jenis_surat']);
                echo json_encode(['status' => 'success', 'message' => 'Permintaan surat berhasil diajukan.']);
                break;

            case 'update_status':
                if ($role !== 'admin') {
                    throw new Exception("Hanya admin yang dapat mengubah status.");
                }
                $id = $_POST['id'] ?? $_POST['surat_id'] ?? 0;
                $status = $_POST['status'] ?? '';
                $nomor_surat = $_POST['nomor_surat'] ?? null;
                $keterangan_admin = $_POST['keterangan_admin'] ?? null;
                $processed_by_id = $_SESSION['user_id'];

                $stmt = $conn->prepare("UPDATE surat_pengantar SET status = ?, nomor_surat = ?, keterangan_admin = ?, processed_by_id = ?, processed_at = NOW() WHERE id = ?");
                $stmt->bind_param("sssii", $status, $nomor_surat, $keterangan_admin, $processed_by_id, $id);
                $stmt->execute();
                $stmt->close();

                // --- Kirim Notifikasi ke Warga ---
                $stmt_get_surat = $conn->prepare("SELECT warga_id, jenis_surat FROM surat_pengantar WHERE id = ?");
                $stmt_get_surat->bind_param("i", $id);
                $stmt_get_surat->execute();
                $surat_info = $stmt_get_surat->get_result()->fetch_assoc();
                $stmt_get_surat->close();

                if ($surat_info) {
                    $status_text = ($status === 'approved') ? 'DISETUJUI' : 'DITOLAK';
                    $message = "Permintaan surat Anda ('{$surat_info['jenis_surat']}') telah {$status_text}.";
                    send_notification_to_warga($surat_info['warga_id'], 'surat_status', $message, '/surat-pengantar');
                }

                log_activity($_SESSION['username'], 'Update Status Surat', "Mengubah status surat ID {$id} menjadi {$status}");
                echo json_encode(['status' => 'success', 'message' => 'Status permintaan surat berhasil diperbarui.']);
                break;

            case 'cancel_request':
                if ($role !== 'warga') throw new Exception("Hanya warga yang dapat membatalkan permintaan.");

                $surat_id = $_POST['surat_id'];

                // Get warga_id for the current user
                $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_warga->bind_param("s", $_SESSION['username']);
                $stmt_warga->execute();
                $warga = $stmt_warga->get_result()->fetch_assoc();
                if (!$warga) throw new Exception("Profil warga Anda tidak ditemukan.");
                $warga_id = $warga['id'];
                $stmt_warga->close();

                // Get the letter to verify ownership and status
                $stmt_surat = $conn->prepare("SELECT warga_id, status FROM surat_pengantar WHERE id = ?");
                $stmt_surat->bind_param("i", $surat_id);
                $stmt_surat->execute();
                $surat = $stmt_surat->get_result()->fetch_assoc();
                $stmt_surat->close();

                if (!$surat) throw new Exception("Permintaan surat tidak ditemukan.");
                if ($surat['warga_id'] != $warga_id) throw new Exception("Anda tidak berhak membatalkan permintaan ini.");
                if ($surat['status'] !== 'pending') throw new Exception("Permintaan ini tidak dapat dibatalkan lagi karena sudah diproses.");

                // All checks passed, proceed with deletion
                $stmt_delete = $conn->prepare("DELETE FROM surat_pengantar WHERE id = ?");
                $stmt_delete->bind_param("i", $surat_id);
                $stmt_delete->execute();
                $stmt_delete->close();

                // Notify admins about the cancellation
                $stmt_admins = $conn->query("SELECT id FROM users WHERE role = 'admin'");
                $admins = $stmt_admins->fetch_all(MYSQLI_ASSOC);
                if (count($admins) > 0) {
                    $message = "Permintaan surat dari " . ($_SESSION['nama_lengkap'] ?? $_SESSION['username']) . " telah dibatalkan.";
                    $link = '/surat-pengantar';
                    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'surat_batal', ?, ?)");
                    foreach ($admins as $admin) {
                        $stmt_notif->bind_param("iss", $admin['id'], $message, $link);
                        $stmt_notif->execute();
                    }
                    $stmt_notif->close();
                }

                log_activity($_SESSION['username'], 'Batal Surat', "Membatalkan permintaan surat ID {$surat_id}");
                echo json_encode(['status' => 'success', 'message' => "Permintaan surat berhasil dibatalkan."]);
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