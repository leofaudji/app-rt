<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$username = $_SESSION['username']; // This is the nama_panggilan

try {
    // Find warga_id from user's nama_panggilan (username)
    $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
    $stmt_warga->bind_param("s", $username);
    $stmt_warga->execute();
    $warga = $stmt_warga->get_result()->fetch_assoc();
    $stmt_warga->close();

    if (!$warga) {
        throw new Exception("Profil warga Anda tidak ditemukan. Pastikan Anda memiliki Nama Panggilan yang valid.");
    }
    $warga_id = $warga['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare("SELECT no_kk, nik, nama_lengkap, alamat, no_telepon, pekerjaan FROM warga WHERE id = ?");
        $stmt->bind_param("i", $warga_id);
        $stmt->execute();
        $profile_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$profile_data) {
            throw new Exception("Data profil warga tidak ditemukan.");
        }
        echo json_encode(['status' => 'success', 'data' => $profile_data]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $no_telepon = $_POST['no_telepon'] ?? '';
        $pekerjaan = $_POST['pekerjaan'] ?? '';

        $stmt_update = $conn->prepare("UPDATE warga SET no_telepon = ?, pekerjaan = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $no_telepon, $pekerjaan, $warga_id);
        $stmt_update->execute();
        $stmt_update->close();

        log_activity($_SESSION['username'], 'Update Profil', 'Pengguna memperbarui profilnya sendiri.');
        echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui.']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>