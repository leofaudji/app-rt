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
        $query = "SELECT k.*, u.nama_lengkap as pembuat FROM kegiatan k JOIN users u ON k.dibuat_oleh = u.id ORDER BY k.tanggal_kegiatan DESC";
        $result = $conn->query($query);
        $kegiatan = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $kegiatan]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Hanya admin yang bisa melakukan CUD
        if ($_SESSION['role'] !== 'admin') {
            throw new Exception("Akses ditolak. Hanya admin yang dapat mengelola kegiatan.");
        }

        switch ($action) {
            case 'add':
                $stmt = $conn->prepare("INSERT INTO kegiatan (judul, deskripsi, tanggal_kegiatan, lokasi, dibuat_oleh) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $_POST['judul'], $_POST['deskripsi'], $_POST['tanggal_kegiatan'], $_POST['lokasi'], $_SESSION['user_id']);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Tambah Kegiatan', 'Menambahkan kegiatan: ' . $_POST['judul']);

                // Prepare WhatsApp notification URL
                $whatsapp_number = get_setting('whatsapp_notification_number');
                $whatsapp_url = null;

                if ($whatsapp_number) {
                    $app_name = get_setting('app_name', 'Aplikasi RT');
                    $judul = $_POST['judul'];
                    $lokasi = $_POST['lokasi'];
                    $link_kegiatan = base_url('/kegiatan');

                    // Format date to Indonesian
                    $tanggal_kegiatan = new DateTime($_POST['tanggal_kegiatan']);
                    $hari_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
                    $bulan_map = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    $hari = $hari_map[$tanggal_kegiatan->format('l')];
                    $tanggal = $tanggal_kegiatan->format('d') . ' ' . $bulan_map[(int)$tanggal_kegiatan->format('n')] . ' ' . $tanggal_kegiatan->format('Y');
                    $waktu = $tanggal_kegiatan->format('H:i') . ' WIB';

                    $message = "*[UNDANGAN KEGIATAN - {$app_name}]*\n\nDengan hormat,\nKami mengundang seluruh warga untuk berpartisipasi dalam kegiatan:\n\n*{$judul}*\n\nYang akan diselenggarakan pada:\n*Hari/Tanggal:* {$hari}, {$tanggal}\n*Waktu:* {$waktu}\n*Lokasi:* {$lokasi}\n\nUntuk detail lebih lanjut, silakan lihat di aplikasi RT:\n{$link_kegiatan}\n\nAtas perhatian dan partisipasinya, kami ucapkan terima kasih.";
                    
                    $phone_number = preg_replace('/^0/', '62', $whatsapp_number);
                    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);

                    $whatsapp_url = "https://wa.me/{$phone_number}?text=" . urlencode($message);
                }

                echo json_encode(['status' => 'success', 'message' => 'Kegiatan baru berhasil ditambahkan.', 'whatsapp_url' => $whatsapp_url]);
                break;

            case 'update':
                $stmt = $conn->prepare("UPDATE kegiatan SET judul=?, deskripsi=?, tanggal_kegiatan=?, lokasi=? WHERE id=?");
                $stmt->bind_param("ssssi", $_POST['judul'], $_POST['deskripsi'], $_POST['tanggal_kegiatan'], $_POST['lokasi'], $_POST['id']);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Kegiatan', 'Mengubah kegiatan ID: ' . $_POST['id']);
                echo json_encode(['status' => 'success', 'message' => 'Kegiatan berhasil diperbarui.']);
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("DELETE FROM kegiatan WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Kegiatan', 'Menghapus kegiatan ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Kegiatan berhasil dihapus.']);
                break;

            case 'get_single':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("SELECT * FROM kegiatan WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $kegiatan = $stmt->get_result()->fetch_assoc();
                echo json_encode(['status' => 'success', 'data' => $kegiatan]);
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