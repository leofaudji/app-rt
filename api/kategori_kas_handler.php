<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin atau Bendahara access required.']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_REQUEST['action'] ?? 'list';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $result = $conn->query("SELECT id, nama_kategori, jenis FROM kas_kategori ORDER BY jenis, nama_kategori");
        $kategori = $result->fetch_all(MYSQLI_ASSOC);

        $grouped = [
            'masuk' => [],
            'keluar' => []
        ];
        foreach ($kategori as $item) {
            $grouped[$item['jenis']][] = $item;
        }

        echo json_encode(['status' => 'success', 'data' => $grouped]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'add':
                $nama_kategori = trim($_POST['nama_kategori'] ?? '');
                $jenis = $_POST['jenis'] ?? '';

                if (empty($nama_kategori) || !in_array($jenis, ['masuk', 'keluar'])) {
                    throw new Exception("Nama kategori dan jenis wajib diisi.");
                }

                $stmt = $conn->prepare("INSERT INTO kas_kategori (nama_kategori, jenis) VALUES (?, ?)");
                $stmt->bind_param("ss", $nama_kategori, $jenis);
                $stmt->execute();
                $stmt->close();

                log_activity($_SESSION['username'], 'Tambah Kategori Kas', "Menambah kategori '{$nama_kategori}' ({$jenis})");
                echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil ditambahkan.']);
                break;

            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $nama_kategori = trim($_POST['nama_kategori'] ?? '');

                if (empty($id) || empty($nama_kategori)) {
                    throw new Exception("ID dan nama kategori tidak boleh kosong.");
                }

                $stmt = $conn->prepare("UPDATE kas_kategori SET nama_kategori = ? WHERE id = ?");
                $stmt->bind_param("si", $nama_kategori, $id);
                $stmt->execute();
                $stmt->close();

                log_activity($_SESSION['username'], 'Update Kategori Kas', "Mengubah kategori ID {$id} menjadi '{$nama_kategori}'");
                echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil diperbarui.']);
                break;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if (empty($id)) {
                    throw new Exception("ID kategori tidak valid.");
                }

                $stmt = $conn->prepare("DELETE FROM kas_kategori WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                log_activity($_SESSION['username'], 'Hapus Kategori Kas', "Menghapus kategori kas ID: {$id}");
                echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil dihapus.']);
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