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
$role = $_SESSION['role'] ?? 'warga';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list' && in_array($role, ['admin', 'bendahara'])) {
            $status = $_GET['status'] ?? '';
            $params = [];
            $types = '';

            $query = "
                SELECT l.*, w.nama_lengkap as pelapor 
                FROM laporan_warga l 
                JOIN warga w ON l.warga_pelapor_id = w.id 
                WHERE 1=1
            ";
            
            if (!empty($status)) {
                $query .= " AND l.status = ?";
                $params[] = $status;
                $types .= 's';
            }
            $query .= " ORDER BY l.created_at DESC";

            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $laporan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $laporan]);
        } elseif ($action === 'list_own') {
            // Find warga_id based on logged-in user's nama_panggilan (username)
            $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
            $stmt_warga->bind_param("s", $_SESSION['username']);
            $stmt_warga->execute();
            $warga = $stmt_warga->get_result()->fetch_assoc();
            $stmt_warga->close();

            if (!$warga) {
                echo json_encode(['status' => 'success', 'data' => []]); // No warga profile found for this user
                exit;
            }
            $warga_pelapor_id = $warga['id'];

            $stmt = $conn->prepare("SELECT * FROM laporan_warga WHERE warga_pelapor_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $warga_pelapor_id);
            $stmt->execute();
            $laporan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $laporan]);
        } else {
            throw new Exception("Aksi tidak valid atau akses ditolak.");
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'add':
                $kategori = $_POST['kategori'] ?? '';
                $deskripsi = $_POST['deskripsi'] ?? '';
                $foto_path = null;

                if (empty($kategori) || empty($deskripsi)) {
                    throw new Exception("Kategori dan deskripsi wajib diisi.");
                }

                // Find warga_id based on logged-in user's nama_panggilan (username)
                $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_warga->bind_param("s", $_SESSION['username']);
                $stmt_warga->execute();
                $warga = $stmt_warga->get_result()->fetch_assoc();
                $stmt_warga->close();

                if (!$warga) {
                    throw new Exception("Profil warga Anda tidak ditemukan. Pastikan Anda memiliki Nama Panggilan yang valid.");
                }
                $warga_pelapor_id = $warga['id'];

                // Handle file upload
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = PROJECT_ROOT . '/uploads/laporan/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0775, true);
                    }

                    $file_info = new SplFileInfo($_FILES['foto']['name']);
                    $extension = strtolower($file_info->getExtension());
                    $allowed_extensions = ['jpg', 'jpeg', 'png'];

                    if (!in_array($extension, $allowed_extensions)) {
                        throw new Exception("Format file tidak diizinkan. Hanya JPG, JPEG, PNG.");
                    }

                    $safe_filename = uniqid('laporan-', true) . '.' . $extension;
                    $destination = $upload_dir . $safe_filename;

                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
                        $foto_path = 'uploads/laporan/' . $safe_filename;
                    } else {
                        throw new Exception("Gagal mengunggah file.");
                    }
                }

                $stmt = $conn->prepare("INSERT INTO laporan_warga (warga_pelapor_id, kategori, deskripsi, foto) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $warga_pelapor_id, $kategori, $deskripsi, $foto_path);
                $stmt->execute();

                // Create notification for admins and bendahara
                $stmt_users = $conn->query("SELECT id FROM users WHERE role IN ('admin', 'bendahara')");
                $admins_and_bendahara = $stmt_users->fetch_all(MYSQLI_ASSOC);
                $stmt_users->close();

                if (count($admins_and_bendahara) > 0) {
                    $message = "Laporan baru: '{$kategori}' oleh " . ($_SESSION['nama_lengkap'] ?? $_SESSION['username']);
                    $link = '/laporan';
                    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'laporan_baru', ?, ?)");
                    foreach ($admins_and_bendahara as $user) {
                        $stmt_notif->bind_param("iss", $user['id'], $message, $link);
                        $stmt_notif->execute();
                    }
                    $stmt_notif->close();
                }

                log_activity($_SESSION['username'], 'Buat Laporan', "Membuat laporan baru kategori: {$kategori}");
                echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dikirim.']);
                break;

            case 'update_status':
                if (!in_array($role, ['admin', 'bendahara'])) {
                    throw new Exception("Akses ditolak.");
                }
                $id = $_POST['id'] ?? 0;
                $status = $_POST['status'] ?? '';

                $stmt = $conn->prepare("UPDATE laporan_warga SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();
                $stmt->close();

                // --- Kirim Notifikasi ke Warga ---
                $stmt_get_warga = $conn->prepare("SELECT warga_pelapor_id, kategori FROM laporan_warga WHERE id = ?");
                $stmt_get_warga->bind_param("i", $id);
                $stmt_get_warga->execute();
                $laporan_info = $stmt_get_warga->get_result()->fetch_assoc();
                $stmt_get_warga->close();

                if ($laporan_info) {
                    $status_text = ucfirst($status);
                    $message = "Laporan Anda ('{$laporan_info['kategori']}') telah diperbarui menjadi status '{$status_text}'.";
                    send_notification_to_warga($laporan_info['warga_pelapor_id'], 'laporan_status', $message, '/laporan');
                }
                // --- Akhir Notifikasi ---

                log_activity($_SESSION['username'], 'Update Status Laporan', "Mengubah status laporan ID: {$id} menjadi {$status}");
                echo json_encode(['status' => 'success', 'message' => 'Status laporan berhasil diperbarui.']);
                break;

            default:
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