<?php
// api/warga_import.php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$conn = Database::getInstance()->getConnection();

$successCount = 0;
$errorCount = 0;
$errors = [];

try {
    if (!isset($_FILES['warga_csv_file']) || $_FILES['warga_csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Gagal mengunggah file atau tidak ada file yang dipilih.");
    }

    $file = $_FILES['warga_csv_file']['tmp_name'];
    $file_info = new SplFileInfo($_FILES['warga_csv_file']['name']);
    if (strtolower($file_info->getExtension()) !== 'csv') {
        throw new Exception("Tipe file tidak valid. Harap unggah file CSV.");
    }

    $handle = fopen($file, "r");
    if ($handle === FALSE) {
        throw new Exception("Gagal membuka file CSV.");
    }

    // Prepare statement for INSERT ... ON DUPLICATE KEY UPDATE
    // This assumes `nik` is a UNIQUE key in the `warga` table.
    $stmt_warga = $conn->prepare("
        INSERT INTO warga (no_kk, nik, nama_lengkap, nama_panggilan, alamat, no_telepon, status_tinggal, pekerjaan, tgl_lahir)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        no_kk = VALUES(no_kk),
        nama_lengkap = VALUES(nama_lengkap),
        nama_panggilan = VALUES(nama_panggilan),
        alamat = VALUES(alamat),
        no_telepon = VALUES(no_telepon),
        status_tinggal = VALUES(status_tinggal),
        pekerjaan = VALUES(pekerjaan),
        tgl_lahir = VALUES(tgl_lahir)
    ");

    $stmt_user = $conn->prepare("
        INSERT INTO users (username, password, nama_lengkap, role)
        VALUES (?, ?, ?, 'warga')
        ON DUPLICATE KEY UPDATE
        password = VALUES(password),
        nama_lengkap = VALUES(nama_lengkap),
        role = VALUES(role)
    ");

    // Skip header row
    fgetcsv($handle, 1000, ",");
    
    $rowNumber = 1;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowNumber++;
        // Expected columns: no_kk, nik, nama_lengkap, nama_panggilan, alamat, no_telepon, status_tinggal, pekerjaan, tgl_lahir
        if (count($data) < 9) {
            $errors[] = "Baris {$rowNumber}: Jumlah kolom tidak sesuai (diharapkan 9).";
            $errorCount++;
            continue;
        }

        list($no_kk, $nik, $nama_lengkap, $nama_panggilan, $alamat, $no_telepon, $status_tinggal, $pekerjaan, $tgl_lahir) = array_map('trim', $data);

        // Basic validation
        if (empty($nik) || empty($nama_lengkap) || empty($no_kk)) {
            $errors[] = "Baris {$rowNumber}: NIK, Nama Lengkap, dan No. KK tidak boleh kosong.";
            $errorCount++;
            continue;
        }
        if (empty($nama_panggilan)) {
            $errors[] = "Baris {$rowNumber}: Nama Panggilan tidak boleh kosong untuk NIK {$nik}.";
            $errorCount++;
            continue;
        }
        $tgl_lahir_db = !empty($tgl_lahir) ? date('Y-m-d', strtotime($tgl_lahir)) : null;

        if (!in_array(strtolower($status_tinggal), ['tetap', 'kontrak'])) {
            $status_tinggal = 'tetap'; // Default value if invalid
        }

        $stmt_warga->bind_param("sssssssss", $no_kk, $nik, $nama_lengkap, $nama_panggilan, $alamat, $no_telepon, $status_tinggal, $pekerjaan, $tgl_lahir_db);
        if ($stmt_warga->execute()) {
            $successCount++;
            // If warga is inserted/updated successfully, also create/update user
            if ($tgl_lahir_db && !empty($nama_panggilan)) {
                $password_to_hash = date('dmY', strtotime($tgl_lahir_db));
                $password_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);
                $stmt_user->bind_param("sss", $nama_panggilan, $password_hash, $nama_lengkap);
                if (!$stmt_user->execute()) {
                    $errors[] = "Baris {$rowNumber}: Gagal membuat akun login untuk Nama Panggilan {$nama_panggilan}.";
                    $errorCount++;
                    $successCount--; // Revert success count
                }
            }
        } else {
            $errors[] = "Baris {$rowNumber}: Gagal menyimpan data untuk NIK {$nik}. Error: " . $stmt_warga->error;
            $errorCount++;
        }
    }

    fclose($handle);
    $stmt_warga->close();
    $stmt_user->close();

    $message = "Proses impor selesai. Berhasil: {$successCount}, Gagal: {$errorCount}.";
    log_activity($_SESSION['username'], 'Impor Warga', $message);
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'data' => [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => array_slice($errors, 0, 10) // Return max 10 errors to prevent flooding UI
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>