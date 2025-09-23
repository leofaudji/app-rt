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
        $query = "SELECT d.*, u.nama_lengkap as pengunggah FROM dokumen d LEFT JOIN users u ON d.diunggah_oleh = u.id ORDER BY d.created_at DESC";
        $result = $conn->query($query);
        $dokumen = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $dokumen]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Hanya admin yang bisa melakukan CUD
        if ($_SESSION['role'] !== 'admin') {
            throw new Exception("Akses ditolak. Hanya admin yang dapat mengelola dokumen.");
        }

        switch ($action) {
            case 'upload':
                $nama_dokumen = $_POST['nama_dokumen'] ?? '';
                $kategori = $_POST['kategori'] ?? 'Lain-lain';
                $deskripsi = $_POST['deskripsi'] ?? '';
                $user_id = $_SESSION['user_id'];

                if (empty($nama_dokumen) || !isset($_FILES['file_dokumen']) || $_FILES['file_dokumen']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Nama dokumen dan file wajib diisi.");
                }

                $file = $_FILES['file_dokumen'];
                $upload_dir = PROJECT_ROOT . '/uploads/dokumen/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0775, true);
                }

                // Validation
                $max_size = 5 * 1024 * 1024; // 5MB
                if ($file['size'] > $max_size) {
                    throw new Exception("Ukuran file terlalu besar. Maksimal 5MB.");
                }

                $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
                $file_info = new SplFileInfo($file['name']);
                $extension = strtolower($file_info->getExtension());
                if (!in_array($extension, $allowed_extensions)) {
                    throw new Exception("Tipe file tidak diizinkan. Hanya: " . implode(', ', $allowed_extensions));
                }

                // Create safe filename and move
                $original_filename = $file['name'];
                $safe_filename = uniqid(date('Y-m-d_H-i-s_'), true) . '.' . $extension;
                $destination = $upload_dir . $safe_filename;
                $db_path = 'uploads/dokumen/' . $safe_filename;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception("Gagal memindahkan file yang diunggah.");
                }

                // Insert into DB
                $stmt = $conn->prepare("INSERT INTO dokumen (nama_dokumen, deskripsi, kategori, nama_file, path_file, diunggah_oleh) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $nama_dokumen, $deskripsi, $kategori, $original_filename, $db_path, $user_id);
                $stmt->execute();
                
                log_activity($_SESSION['username'], 'Unggah Dokumen', "Mengunggah dokumen: {$nama_dokumen}");
                echo json_encode(['status' => 'success', 'message' => 'Dokumen berhasil diunggah.']);
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                
                // Get file path before deleting DB record
                $stmt_select = $conn->prepare("SELECT path_file FROM dokumen WHERE id = ?");
                $stmt_select->bind_param("i", $id);
                $stmt_select->execute();
                $doc = $stmt_select->get_result()->fetch_assoc();
                $stmt_select->close();

                if ($doc && !empty($doc['path_file'])) {
                    $file_to_delete = PROJECT_ROOT . '/' . $doc['path_file'];
                    if (file_exists($file_to_delete)) {
                        unlink($file_to_delete);
                    }
                }

                // Delete DB record
                $stmt_delete = $conn->prepare("DELETE FROM dokumen WHERE id=?");
                $stmt_delete->bind_param("i", $id);
                $stmt_delete->execute();
                
                log_activity($_SESSION['username'], 'Hapus Dokumen', 'Menghapus dokumen ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Dokumen berhasil dihapus.']);
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