<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$role = $_SESSION['role'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        // Check if signature file exists and add a flag
        if (!empty($settings['signature_image'])) {
            $settings['signature_image_exists'] = file_exists(PROJECT_ROOT . '/' . $settings['signature_image']);
        } else {
            $settings['signature_image_exists'] = false;
        }
        // Check if stamp file exists and add a flag
        if (!empty($settings['stamp_image'])) {
            $settings['stamp_image_exists'] = file_exists(PROJECT_ROOT . '/' . $settings['stamp_image']);
        } else {
            $settings['stamp_image_exists'] = false;
        }
        // Check if letterhead file exists and add a flag
        if (!empty($settings['letterhead_image'])) {
            $settings['letterhead_image_exists'] = file_exists(PROJECT_ROOT . '/' . $settings['letterhead_image']);
        } else {
            $settings['letterhead_image_exists'] = false;
        }

        echo json_encode(['status' => 'success', 'data' => $settings]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($role !== 'admin') {
            throw new Exception("Akses ditolak. Hanya admin yang dapat mengubah pengaturan.");
        }

        $conn->begin_transaction();

        // Handle text fields
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($_POST as $key => $value) {
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }
        $stmt->close();

        // Handle file upload for letterhead
        if (isset($_FILES['letterhead_image']) && $_FILES['letterhead_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['letterhead_image'];
            $upload_dir = PROJECT_ROOT . '/uploads/settings/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

            // Validation
            if ($file['size'] > 2 * 1024 * 1024) throw new Exception("Ukuran file kop surat terlalu besar. Maksimal 2MB.");
            $allowed_types = ['image/png', 'image/jpeg'];
            $file_type = mime_content_type($file['tmp_name']);
            if (!in_array($file_type, $allowed_types)) throw new Exception("Tipe file tidak diizinkan. Gunakan file PNG atau JPG.");

            // Delete old letterhead if exists
            $stmt_old = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'letterhead_image'");
            $stmt_old->execute();
            $old_file_path = $stmt_old->get_result()->fetch_assoc()['setting_value'] ?? null;
            if ($old_file_path && file_exists(PROJECT_ROOT . '/' . $old_file_path)) {
                unlink(PROJECT_ROOT . '/' . $old_file_path);
            }
            $stmt_old->close();

            // Create safe filename and move
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safe_filename = 'letterhead_' . uniqid() . '.' . $extension;
            $destination = $upload_dir . $safe_filename;
            $db_path = 'uploads/settings/' . $safe_filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) throw new Exception("Gagal memindahkan file kop surat.");

            $stmt_save_path = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('letterhead_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt_save_path->bind_param("s", $db_path);
            $stmt_save_path->execute();
            $stmt_save_path->close();
        }

        // Handle file upload for signature
        if (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['signature_image'];
            $upload_dir = PROJECT_ROOT . '/uploads/settings/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

            // Validation
            if ($file['size'] > 1 * 1024 * 1024) throw new Exception("Ukuran file tanda tangan terlalu besar. Maksimal 1MB.");
            if ($file['type'] !== 'image/png') throw new Exception("Tipe file tidak diizinkan. Gunakan file PNG dengan latar belakang transparan.");

            // Delete old signature if exists
            $stmt_old = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'signature_image'");
            $stmt_old->execute();
            $old_file_path = $stmt_old->get_result()->fetch_assoc()['setting_value'] ?? null;
            if ($old_file_path && file_exists(PROJECT_ROOT . '/' . $old_file_path)) {
                unlink(PROJECT_ROOT . '/' . $old_file_path);
            }
            $stmt_old->close();

            // Create safe filename and move
            $safe_filename = 'signature_' . uniqid() . '.png';
            $destination = $upload_dir . $safe_filename;
            $db_path = 'uploads/settings/' . $safe_filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) throw new Exception("Gagal memindahkan file tanda tangan.");

            // Save new path to DB
            $stmt_save_path = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('signature_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt_save_path->bind_param("s", $db_path);
            $stmt_save_path->execute();
            $stmt_save_path->close();
        }

        // Handle file upload for stamp
        if (isset($_FILES['stamp_image']) && $_FILES['stamp_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['stamp_image'];
            $upload_dir = PROJECT_ROOT . '/uploads/settings/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

            // Validation
            if ($file['size'] > 1 * 1024 * 1024) throw new Exception("Ukuran file stempel terlalu besar. Maksimal 1MB.");
            if ($file['type'] !== 'image/png') throw new Exception("Tipe file tidak diizinkan. Gunakan file PNG dengan latar belakang transparan.");

            // Delete old stamp if exists
            $stmt_old = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'stamp_image'");
            $stmt_old->execute();
            $old_file_path = $stmt_old->get_result()->fetch_assoc()['setting_value'] ?? null;
            if ($old_file_path && file_exists(PROJECT_ROOT . '/' . $old_file_path)) {
                unlink(PROJECT_ROOT . '/' . $old_file_path);
            }
            $stmt_old->close();

            // Create safe filename and move
            $safe_filename = 'stamp_' . uniqid() . '.png';
            $destination = $upload_dir . $safe_filename;
            $db_path = 'uploads/settings/' . $safe_filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) throw new Exception("Gagal memindahkan file stempel.");

            $stmt_save_path = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('stamp_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt_save_path->bind_param("s", $db_path);
            $stmt_save_path->execute();
            $stmt_save_path->close();
        }

        $conn->commit();
        log_activity($_SESSION['username'], 'Update Pengaturan', 'Pengaturan aplikasi telah diperbarui.');
        echo json_encode(['status' => 'success', 'message' => 'Pengaturan berhasil disimpan.']);

    } else {
        throw new Exception("Metode request tidak valid.");
    }
} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();