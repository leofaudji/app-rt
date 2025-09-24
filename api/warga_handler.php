<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_REQUEST['action'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';
        switch ($action) {
            case 'list':
                $searchTerm = $_GET['search'] ?? '';
                $sortBy = $_GET['sort_by'] ?? 'nama_lengkap';
                $sortDir = $_GET['sort_dir'] ?? 'asc';
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit_str = $_GET['limit'] ?? '10';
                $use_limit = $limit_str !== 'all';
                $limit = (int)$limit_str;
                $offset = ($page - 1) * $limit;

                // Whitelist for sortable columns to prevent SQL injection
                $allowedSortColumns = ['nama_lengkap', 'nik', 'no_kk', 'alamat', 'status_tinggal', 'pekerjaan', 'tgl_lahir', 'nama_panggilan', 'jenis_kelamin', 'agama', 'golongan_darah'];
                if (!in_array($sortBy, $allowedSortColumns)) {
                    $sortBy = 'nama_lengkap'; // Default to a safe column
                }

                // Whitelist for sort direction
                $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

                $params = [];
                $types = '';
                $countQuery = "SELECT COUNT(id) as total FROM warga WHERE 1=1";
                $dataQuery = "SELECT * FROM warga WHERE 1=1";

                if (!empty($searchTerm)) {
                    $whereClause = " AND (nama_lengkap LIKE ? OR nik LIKE ? OR alamat LIKE ? OR pekerjaan LIKE ?)";
                    $countQuery .= $whereClause;
                    $dataQuery .= $whereClause;
                    $likeTerm = "%{$searchTerm}%";
                    $params = [$likeTerm, $likeTerm, $likeTerm, $likeTerm];
                    $types = 'ssss';
                }

                // Dapatkan total data untuk paginasi
                $stmtCount = $conn->prepare($countQuery);
                if (!empty($params)) {
                    $stmtCount->bind_param($types, ...$params);
                }
                $stmtCount->execute();
                $totalRecords = $stmtCount->get_result()->fetch_assoc()['total'];
                $totalPages = ceil($totalRecords / $limit);
                $stmtCount->close();

                // Dapatkan data per halaman
                $dataQuery .= " ORDER BY {$sortBy} {$sortDir}";
                if ($use_limit) {
                    $dataQuery .= " LIMIT ? OFFSET ?";
                    $params[] = $limit;
                    $params[] = $offset;
                    $types .= 'ii';
                }

                $stmt = $conn->prepare($dataQuery);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $totalPages = $use_limit ? ceil($totalRecords / $limit) : 1;

                echo json_encode([
                    'status' => 'success', 
                    'data' => $result,
                    'pagination' => [
                        'total_records' => (int)$totalRecords,
                        'total_pages' => (int)$totalPages,
                        'current_page' => $page,
                        'limit' => $limit
                    ]
                ]);
                break;
            case 'get_kk_list':
                $query = "
                    SELECT
                        w.no_kk,
                        (SELECT w_head.nama_lengkap FROM warga w_head WHERE w_head.no_kk = w.no_kk AND w_head.status_dalam_keluarga = 'Kepala Keluarga' LIMIT 1) as kepala_keluarga
                    FROM warga w
                    WHERE w.no_kk IS NOT NULL AND w.no_kk != ''
                    GROUP BY w.no_kk
                    ORDER BY w.no_kk ASC
                ";
                $result = $conn->query($query);
                $kk_list = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $kk_list]);
                break;
            case 'get_my_family':
                if (!isset($_SESSION['username'])) {
                    throw new Exception("Sesi tidak valid.");
                }
                // First, get the user's no_kk
                $stmt_get_kk = $conn->prepare("SELECT no_kk FROM warga WHERE nama_panggilan = ?");
                $stmt_get_kk->bind_param("s", $_SESSION['username']);
                $stmt_get_kk->execute();
                $user_data = $stmt_get_kk->get_result()->fetch_assoc();
                $stmt_get_kk->close();

                if (!$user_data || empty($user_data['no_kk'])) {
                    echo json_encode(['status' => 'success', 'data' => [], 'no_kk' => '']);
                    break;
                }

                $no_kk = $user_data['no_kk'];

                // Then, get all family members, ordered logically
                $stmt_family = $conn->prepare("SELECT nama_lengkap, status_dalam_keluarga, tgl_lahir, pekerjaan, nama_panggilan FROM warga WHERE no_kk = ? ORDER BY FIELD(status_dalam_keluarga, 'Kepala Keluarga', 'Istri', 'Anak', 'Lainnya'), tgl_lahir ASC");
                $stmt_family->bind_param("s", $no_kk);
                $stmt_family->execute();
                $family_members = $stmt_family->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_family->close();

                echo json_encode(['status' => 'success', 'data' => $family_members, 'no_kk' => $no_kk]);
                break;
            case 'get_public_profile':
                $warga_id = $_GET['id'] ?? 0;
                if (empty($warga_id)) {
                    throw new Exception("ID Warga tidak valid.");
                }

                $query = "
                    SELECT 
                        w.*, r.blok, r.nomor
                    FROM warga w
                    LEFT JOIN rumah r ON w.no_kk = r.no_kk_penghuni
                    WHERE w.id = ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $warga_id);
                $stmt->execute();
                $profile_data = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                echo json_encode(['status' => 'success', 'data' => $profile_data]);
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? null;
        // Admin-only actions

        // Helper function for file upload
        function handle_profile_picture_upload($file_input_name, $current_photo_path = null) {
            if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
                $upload_dir = PROJECT_ROOT . '/uploads/profil/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0775, true);
                }

                $file_info = new SplFileInfo($_FILES[$file_input_name]['name']);
                $extension = strtolower($file_info->getExtension());
                $allowed_extensions = ['jpg', 'jpeg', 'png'];

                if (!in_array($extension, $allowed_extensions)) {
                    throw new Exception("Format file tidak diizinkan. Hanya JPG, JPEG, PNG.");
                }
                if ($_FILES[$file_input_name]['size'] > 2 * 1024 * 1024) { // 2MB limit
                    throw new Exception("Ukuran file terlalu besar. Maksimal 2MB.");
                }

                // Delete old photo if it exists
                if ($current_photo_path && file_exists(PROJECT_ROOT . '/' . $current_photo_path)) {
                    unlink(PROJECT_ROOT . '/' . $current_photo_path);
                }

                $safe_filename = uniqid('profil-', true) . '.' . $extension;
                $destination = $upload_dir . $safe_filename;

                if (move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $destination)) {
                    return 'uploads/profil/' . $safe_filename;
                } else {
                    throw new Exception("Gagal memindahkan file yang diunggah.");
                }
            }
            return $current_photo_path; // Return old path if no new file is uploaded
        }

        if (in_array($action, ['new', 'update', 'delete']) && $_SESSION['role'] !== 'admin') {
            throw new Exception("Akses ditolak. Hanya admin yang dapat melakukan aksi ini.");
        }

        switch ($action) {
            case 'add_family_member':
                // This action is for logged-in warga to add their own family members.
                // No admin check needed here, just auth.

                $conn->begin_transaction();

                // 1. Get the current user's (head of family) data
                $stmt_head = $conn->prepare("SELECT id, no_kk, alamat, status_tinggal FROM warga WHERE nama_panggilan = ?");
                $stmt_head->bind_param("s", $_SESSION['username']);
                $stmt_head->execute();
                $head_of_family = $stmt_head->get_result()->fetch_assoc();
                $stmt_head->close();

                if (!$head_of_family || empty($head_of_family['no_kk'])) {
                    throw new Exception("Data keluarga Anda tidak ditemukan. Tidak dapat menambah anggota.");
                }

                $no_kk = $head_of_family['no_kk'];
                $alamat = $head_of_family['alamat'];
                $status_tinggal = $head_of_family['status_tinggal'];

                // 2. Get new member data from POST
                $tgl_lahir = !empty($_POST['tgl_lahir']) ? $_POST['tgl_lahir'] : null;
                $nama_panggilan = trim($_POST['nama_panggilan'] ?? '');
                $nik = trim($_POST['nik'] ?? '');
                $nama_lengkap = $_POST['nama_lengkap'];
                $status_dalam_keluarga = $_POST['status_dalam_keluarga'];
                $jenis_kelamin = $_POST['jenis_kelamin'];
                $pekerjaan = $_POST['pekerjaan'];

                // 3. Validation
                if (empty($nik) || empty($nama_lengkap) || empty($nama_panggilan) || empty($tgl_lahir)) {
                    throw new Exception("Nama, NIK, Nama Panggilan, dan Tanggal Lahir wajib diisi.");
                }

                // Check NIK uniqueness
                $stmt_check_nik = $conn->prepare("SELECT id FROM warga WHERE nik = ?");
                $stmt_check_nik->bind_param("s", $nik);
                $stmt_check_nik->execute();
                if ($stmt_check_nik->get_result()->num_rows > 0) {
                    throw new Exception("NIK sudah terdaftar.");
                }
                $stmt_check_nik->close();

                // Check Nama Panggilan uniqueness
                $stmt_check_nama = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_check_nama->bind_param("s", $nama_panggilan);
                $stmt_check_nama->execute();
                if ($stmt_check_nama->get_result()->num_rows > 0) {
                    throw new Exception("Nama Panggilan sudah digunakan.");
                }
                $stmt_check_nama->close();

                // 4. Insert new family member into 'warga' table
                $stmt_warga = $conn->prepare("INSERT INTO warga (no_kk, nik, nama_lengkap, nama_panggilan, alamat, status_tinggal, pekerjaan, tgl_lahir, status_dalam_keluarga, jenis_kelamin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_warga->bind_param("ssssssssss", $no_kk, $nik, $nama_lengkap, $nama_panggilan, $alamat, $status_tinggal, $pekerjaan, $tgl_lahir, $status_dalam_keluarga, $jenis_kelamin);
                $stmt_warga->execute();
                $stmt_warga->close();

                // 5. Create a user account for the new member
                $password_to_hash = date('dmY', strtotime($tgl_lahir));
                $password_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);
                $stmt_user = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'warga')");
                $stmt_user->bind_param("sss", $nama_panggilan, $password_hash, $nama_lengkap);
                $stmt_user->execute();
                $stmt_user->close();

                $conn->commit();

                log_activity($_SESSION['username'], 'Tambah Anggota Keluarga', 'Menambahkan anggota keluarga baru: ' . $nama_lengkap);
                echo json_encode(['status' => 'success', 'message' => 'Anggota keluarga baru berhasil ditambahkan.']);
                break;
            case 'new':
                $conn->begin_transaction();
                $tgl_lahir = !empty($_POST['tgl_lahir']) ? $_POST['tgl_lahir'] : null;
                $nama_panggilan = trim($_POST['nama_panggilan'] ?? '');
                $nik = trim($_POST['nik'] ?? '');

                // --- START: Validasi Keunikan Data ---
                // Cek NIK unik
                $stmt_check_nik = $conn->prepare("SELECT id FROM warga WHERE nik = ?");
                $stmt_check_nik->bind_param("s", $nik);
                $stmt_check_nik->execute();
                if ($stmt_check_nik->get_result()->num_rows > 0) {
                    throw new Exception("NIK sudah terdaftar.");
                }
                $stmt_check_nik->close();

                // Cek Nama Panggilan unik
                $stmt_check_nama = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_check_nama->bind_param("s", $nama_panggilan);
                $stmt_check_nama->execute();
                if ($stmt_check_nama->get_result()->num_rows > 0) {
                    throw new Exception("Nama Panggilan sudah digunakan.");
                }
                $stmt_check_nama->close();

                $foto_profil_path = handle_profile_picture_upload('foto_profil');

                $stmt = $conn->prepare("INSERT INTO warga (no_kk, nik, nama_lengkap, nama_panggilan, alamat, no_telepon, status_tinggal, pekerjaan, tgl_lahir, status_dalam_keluarga, jenis_kelamin, agama, golongan_darah, foto_profil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssssss", $_POST['no_kk'], $nik, $_POST['nama_lengkap'], $nama_panggilan, $_POST['alamat'], $_POST['no_telepon'], $_POST['status_tinggal'], $_POST['pekerjaan'], $tgl_lahir, $_POST['status_dalam_keluarga'], $_POST['jenis_kelamin'], $_POST['agama'], $_POST['golongan_darah'], $foto_profil_path);
                $stmt->execute();
                $stmt->close();

                // Also create a user account for the warga
                if (!empty($nama_panggilan) && !empty($tgl_lahir)) {
                    $password_to_hash = date('dmY', strtotime($tgl_lahir));
                    $password_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'warga') ON DUPLICATE KEY UPDATE password = VALUES(password), nama_lengkap = VALUES(nama_lengkap)");
                    $stmt_user->bind_param("sss", $nama_panggilan, $password_hash, $_POST['nama_lengkap']);
                    $stmt_user->execute();
                    $stmt_user->close();
                }
                $conn->commit();

                log_activity($_SESSION['username'], 'Tambah Warga', 'Menambahkan warga baru: ' . $_POST['nama_lengkap']);
                echo json_encode(['status' => 'success', 'message' => 'Warga baru berhasil ditambahkan.']);
                break;

            case 'update':
                $conn->begin_transaction();
                $tgl_lahir = !empty($_POST['tgl_lahir']) ? $_POST['tgl_lahir'] : null;
                $nama_panggilan = trim($_POST['nama_panggilan'] ?? '');
                $nik = trim($_POST['nik'] ?? '');
                $id = $_POST['id'] ?? 0;

                // --- START: Validasi Keunikan Data ---
                // Cek NIK unik, kecuali untuk diri sendiri
                $stmt_check_nik = $conn->prepare("SELECT id FROM warga WHERE nik = ? AND id != ?");
                $stmt_check_nik->bind_param("si", $nik, $id);
                $stmt_check_nik->execute();
                if ($stmt_check_nik->get_result()->num_rows > 0) {
                    throw new Exception("NIK sudah terdaftar untuk warga lain.");
                }
                $stmt_check_nik->close();

                // Cek Nama Panggilan unik, kecuali untuk diri sendiri
                $stmt_check_nama = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ? AND id != ?");
                $stmt_check_nama->bind_param("si", $nama_panggilan, $id);
                $stmt_check_nama->execute();
                if ($stmt_check_nama->get_result()->num_rows > 0) {
                    throw new Exception("Nama Panggilan sudah digunakan oleh warga lain.");
                }
                $stmt_check_nama->close();

                // Get old data (nama_panggilan and foto_profil) before update
                $stmt_old_data = $conn->prepare("SELECT nama_panggilan, foto_profil FROM warga WHERE id = ?");
                $stmt_old_data->bind_param("i", $_POST['id']);
                $stmt_old_data->execute();
                $old_data = $stmt_old_data->get_result()->fetch_assoc();
                $old_nama_panggilan = $old_data['nama_panggilan'];
                $old_foto_profil = $old_data['foto_profil'];
                $stmt_old_data->close();

                $foto_profil_path = handle_profile_picture_upload('foto_profil', $old_foto_profil);

                $stmt = $conn->prepare("UPDATE warga SET no_kk=?, nik=?, nama_lengkap=?, nama_panggilan=?, alamat=?, no_telepon=?, status_tinggal=?, pekerjaan=?, tgl_lahir=?, status_dalam_keluarga=?, jenis_kelamin=?, agama=?, golongan_darah=?, foto_profil=? WHERE id=?");
                $stmt->bind_param("ssssssssssssssi", $_POST['no_kk'], $nik, $_POST['nama_lengkap'], $nama_panggilan, $_POST['alamat'], $_POST['no_telepon'], $_POST['status_tinggal'], $_POST['pekerjaan'], $tgl_lahir, $_POST['status_dalam_keluarga'], $_POST['jenis_kelamin'], $_POST['agama'], $_POST['golongan_darah'], $foto_profil_path, $id);
                $stmt->execute();
                $stmt->close();

                // Update user account
                if (!empty($nama_panggilan) && !empty($tgl_lahir)) {
                    $password_to_hash = date('dmY', strtotime($tgl_lahir));
                    $password_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ? WHERE username = ?");
                    $stmt_user->bind_param("ssss", $nama_panggilan, $password_hash, $_POST['nama_lengkap'], $old_nama_panggilan);
                    $stmt_user->execute();
                    $stmt_user->close();
                }
                $conn->commit();

                log_activity($_SESSION['username'], 'Update Warga', 'Mengubah data warga ID: ' . $_POST['id']);
                echo json_encode(['status' => 'success', 'message' => 'Data warga berhasil diperbarui.']);
                break;

            case 'delete':
                $conn->begin_transaction();
                $id = $_POST['id'] ?? 0;
                // Get data before deleting
                $stmt_get_data = $conn->prepare("SELECT nama_panggilan, foto_profil FROM warga WHERE id = ?");
                $stmt_get_data->bind_param("i", $id);
                $stmt_get_data->execute();
                $data_to_delete = $stmt_get_data->get_result()->fetch_assoc();
                $nama_panggilan_to_delete = $data_to_delete['nama_panggilan'];
                $foto_profil_to_delete = $data_to_delete['foto_profil'];
                $stmt_get_data->close();

                $stmt = $conn->prepare("DELETE FROM warga WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                // Also delete the user account
                if ($nama_panggilan_to_delete) {
                    $stmt_user = $conn->prepare("DELETE FROM users WHERE username = ? AND role = 'warga'");
                    $stmt_user->bind_param("s", $nama_panggilan_to_delete);
                    $stmt_user->execute();
                    $stmt_user->close();
                }

                // Also delete the profile picture file
                if ($foto_profil_to_delete && file_exists(PROJECT_ROOT . '/' . $foto_profil_to_delete)) {
                    unlink(PROJECT_ROOT . '/' . $foto_profil_to_delete);
                }
                $conn->commit();

                log_activity($_SESSION['username'], 'Hapus Warga', 'Menghapus warga ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Data warga berhasil dihapus.']);
                break;

            case 'get_single':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("SELECT * FROM warga WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $warga = $stmt->get_result()->fetch_assoc();
                echo json_encode(['status' => 'success', 'data' => $warga]);
                break;

            case 'get_keluarga':
                $no_kk = $_POST['no_kk'] ?? '';
                if (empty($no_kk)) {
                    echo json_encode(['status' => 'success', 'data' => []]);
                    break;
                }
                $stmt = $conn->prepare("SELECT nik, nama_lengkap FROM warga WHERE no_kk = ? ORDER BY nama_lengkap");
                $stmt->bind_param("s", $no_kk);
                $stmt->execute();
                $keluarga = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $keluarga]);
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
?>