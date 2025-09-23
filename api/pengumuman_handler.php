<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_REQUEST['action'] ?? 'list';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $query = "SELECT p.*, u.nama_lengkap as pembuat FROM pengumuman p LEFT JOIN users u ON p.dibuat_oleh = u.id";
        
        // Admin sees all, warga only sees published
        if ($_SESSION['role'] !== 'admin') {
            $query .= " WHERE (p.tanggal_terbit IS NULL OR p.tanggal_terbit <= NOW())";
        }
        $query .= " ORDER BY COALESCE(p.tanggal_terbit, p.created_at) DESC";
        $result = $conn->query($query);
        $pengumuman = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $pengumuman]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Hanya admin yang bisa melakukan CUD
        if ($_SESSION['role'] !== 'admin') {
            throw new Exception("Akses ditolak. Hanya admin yang dapat mengelola pengumuman.");
        }

        switch ($action) {
            case 'add':
                $tanggal_terbit = !empty($_POST['tanggal_terbit']) ? $_POST['tanggal_terbit'] : null;
                $stmt = $conn->prepare("INSERT INTO pengumuman (judul, isi_pengumuman, tanggal_terbit, dibuat_oleh) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $_POST['judul'], $_POST['isi_pengumuman'], $tanggal_terbit, $_SESSION['user_id']);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Tambah Pengumuman', 'Menambahkan pengumuman: ' . $_POST['judul']);

                // Prepare WhatsApp notification URL
                $whatsapp_number = get_setting('whatsapp_notification_number');
                $whatsapp_url = null;
                $is_published_now = ($tanggal_terbit === null || strtotime($tanggal_terbit) <= time());

                if ($whatsapp_number && $is_published_now) {
                    $app_name = get_setting('app_name', 'Aplikasi RT');
                    $judul = $_POST['judul'];
                    $link_pengumuman = base_url('/pengumuman');

                    $message = "*[INFO {$app_name}]*\n\nAda pengumuman baru:\n\n*{$judul}*\n\nSilakan cek detailnya di aplikasi RT atau klik link berikut:\n" . $link_pengumuman;
                    
                    $phone_number = preg_replace('/^0/', '62', $whatsapp_number);
                    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);

                    $whatsapp_url = "https://wa.me/{$phone_number}?text=" . urlencode($message);
                }

                echo json_encode(['status' => 'success', 'message' => 'Pengumuman baru berhasil ditambahkan.', 'whatsapp_url' => $whatsapp_url]);
                break;

            case 'update':
                $tanggal_terbit = !empty($_POST['tanggal_terbit']) ? $_POST['tanggal_terbit'] : null;
                $stmt = $conn->prepare("UPDATE pengumuman SET judul=?, isi_pengumuman=?, tanggal_terbit=? WHERE id=?");
                $stmt->bind_param("sssi", $_POST['judul'], $_POST['isi_pengumuman'], $tanggal_terbit, $_POST['id']);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Pengumuman', 'Mengubah pengumuman ID: ' . $_POST['id']);
                echo json_encode(['status' => 'success', 'message' => 'Pengumuman berhasil diperbarui.']);
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("DELETE FROM pengumuman WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Pengumuman', 'Menghapus pengumuman ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Pengumuman berhasil dihapus.']);
                break;

            case 'get_single':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("SELECT * FROM pengumuman WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $pengumuman = $stmt->get_result()->fetch_assoc();
                echo json_encode(['status' => 'success', 'data' => $pengumuman]);
                break;

            default:
                throw new Exception("Aksi tidak valid.");
        }
        if (isset($stmt)) $stmt->close();
    } else {
        throw new Exception("Metode request tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>