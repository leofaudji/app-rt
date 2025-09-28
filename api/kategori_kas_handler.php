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

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if (empty($id)) {
                    throw new Exception("ID kategori tidak valid.");
                }

                // Ambil nama kategori terlebih dahulu
                $stmt_get_name = $conn->prepare("SELECT nama_kategori FROM kas_kategori WHERE id = ?");
                $stmt_get_name->bind_param("i", $id);
                $stmt_get_name->execute();
                $category = $stmt_get_name->get_result()->fetch_assoc();
                $stmt_get_name->close();

                if (!$category) {
                    throw new Exception("Kategori yang akan dihapus tidak ditemukan.");
                }
                $nama_kategori = $category['nama_kategori'];

                // Cek apakah kategori sudah digunakan di tabel kas
                $stmt_check = $conn->prepare("SELECT COUNT(id) as usage_count FROM kas WHERE kategori = ?");
                $stmt_check->bind_param("s", $nama_kategori);
                $stmt_check->execute();
                $usage_count = $stmt_check->get_result()->fetch_assoc()['usage_count'];
                $stmt_check->close();

                if ($usage_count > 0) {
                    throw new Exception("Kategori '{$nama_kategori}' tidak dapat dihapus karena telah digunakan dalam {$usage_count} transaksi.");
                }

                // Jika tidak digunakan, lanjutkan penghapusan
                $stmt_delete = $conn->prepare("DELETE FROM kas_kategori WHERE id = ?");
                $stmt_delete->bind_param("i", $id);
                $stmt_delete->execute();
                $stmt_delete->close();

                log_activity($_SESSION['username'], 'Hapus Kategori Kas', "Menghapus kategori kas ID: {$id} ({$nama_kategori})");
                echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil dihapus.']);
                break;

            case 'get_single':
                $id = (int)($_POST['id'] ?? 0);
                if (empty($id)) {
                    throw new Exception("ID kategori tidak valid.");
                }
                $stmt = $conn->prepare("SELECT * FROM kas_kategori WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $kategori = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                echo json_encode(['status' => 'success', 'data' => $kategori]);
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