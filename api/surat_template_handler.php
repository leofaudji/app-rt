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
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET requests are allowed for all authenticated users
        if ($action === 'list') {
            $result = $conn->query("SELECT id, nama_template, judul_surat, requires_parent_data FROM surat_templates ORDER BY nama_template ASC");
            $templates = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $templates]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // POST requests (CUD) are restricted to admins
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            throw new Exception("Forbidden: Admin access required for this action.");
        }

        switch ($action) {
            case 'create':
                $stmt = $conn->prepare("INSERT INTO surat_templates (nama_template, judul_surat, konten, requires_parent_data) VALUES (?, ?, ?, ?)");
                $requires_parent = isset($_POST['requires_parent_data']) ? 1 : 0;
                $stmt->bind_param("sssi", $_POST['nama_template'], $_POST['judul_surat'], $_POST['konten'], $requires_parent);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Buat Template Surat', 'Membuat template: ' . $_POST['nama_template']);
                echo json_encode(['status' => 'success', 'message' => 'Template surat berhasil dibuat.']);
                break;

            case 'update':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("UPDATE surat_templates SET nama_template=?, judul_surat=?, konten=?, requires_parent_data=? WHERE id=?");
                $requires_parent = isset($_POST['requires_parent_data']) ? 1 : 0;
                $stmt->bind_param("sssii", $_POST['nama_template'], $_POST['judul_surat'], $_POST['konten'], $requires_parent, $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Template Surat', 'Mengubah template ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Template surat berhasil diperbarui.']);
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                // Check if template is in use
                $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM surat_pengantar WHERE jenis_surat = (SELECT nama_template FROM surat_templates WHERE id = ?)");
                $stmt_check->bind_param("i", $id);
                $stmt_check->execute();
                $in_use = $stmt_check->get_result()->fetch_assoc()['total'] > 0;
                $stmt_check->close();

                if ($in_use) {
                    throw new Exception("Template tidak dapat dihapus karena sedang digunakan oleh permintaan surat yang sudah ada.");
                }

                $stmt = $conn->prepare("DELETE FROM surat_templates WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Template Surat', 'Menghapus template ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Template surat berhasil dihapus.']);
                break;

            case 'get_single':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("SELECT * FROM surat_templates WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $template = $stmt->get_result()->fetch_assoc();
                if ($template) {
                    echo json_encode(['status' => 'success', 'data' => $template]);
                } else {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Template tidak ditemukan.']);
                }
                break;

            default:
                throw new Exception("Aksi tidak valid.");
        }
        if (isset($stmt)) $stmt->close();
    } else {
        throw new Exception("Metode request tidak valid.");
    }
} catch (Exception $e) {
    $code = $e->getCode() === 1062 ? 409 : 400; // 409 Conflict for duplicate entry
    http_response_code($code);
    $error_message = $code === 409 ? 'Nama template sudah ada. Harap gunakan nama lain.' : $e->getMessage();
    echo json_encode(['status' => 'error', 'message' => $error_message]);
}

$conn->close();
?>