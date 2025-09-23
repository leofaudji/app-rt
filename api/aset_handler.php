<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required.']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_REQUEST['action'] ?? 'list';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $result = $conn->query("SELECT * FROM aset_rt ORDER BY nama_aset ASC");
        $aset = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $aset]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'create':
                $stmt = $conn->prepare("INSERT INTO aset_rt (nama_aset, jumlah, kondisi, lokasi_simpan) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siss", $_POST['nama_aset'], $_POST['jumlah'], $_POST['kondisi'], $_POST['lokasi_simpan']);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Tambah Aset', 'Menambahkan aset: ' . $_POST['nama_aset']);
                echo json_encode(['status' => 'success', 'message' => 'Aset baru berhasil ditambahkan.']);
                break;

            case 'update':
                $stmt = $conn->prepare("UPDATE aset_rt SET nama_aset=?, jumlah=?, kondisi=?, lokasi_simpan=? WHERE id=?");
                $stmt->bind_param("sissi", $_POST['nama_aset'], $_POST['jumlah'], $_POST['kondisi'], $_POST['lokasi_simpan'], $_POST['id']);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Aset', 'Mengubah data aset ID: ' . $_POST['id']);
                echo json_encode(['status' => 'success', 'message' => 'Data aset berhasil diperbarui.']);
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("DELETE FROM aset_rt WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Aset', 'Menghapus aset ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Aset berhasil dihapus.']);
                break;

            case 'get_single':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("SELECT * FROM aset_rt WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $aset = $stmt->get_result()->fetch_assoc();
                if ($aset) {
                    echo json_encode(['status' => 'success', 'data' => $aset]);
                } else {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Aset tidak ditemukan.']);
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
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>