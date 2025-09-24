<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$action = $_REQUEST['action'] ?? '';
$role = $_SESSION['role'] ?? 'warga';
$user_id = $_SESSION['user_id'];

// Find warga_id from user's nama_panggilan (username)
$stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
$stmt_warga->bind_param("s", $_SESSION['username']);
$stmt_warga->execute();
$warga = $stmt_warga->get_result()->fetch_assoc();
$warga_id = $warga['id'] ?? null;
$stmt_warga->close();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list_albums') {
            $query = "
                SELECT 
                    a.id, a.judul, a.deskripsi, a.created_at, u.nama_lengkap as pembuat,
                    (SELECT f.path_file FROM galeri_foto f WHERE f.album_id = a.id ORDER BY f.uploaded_at DESC LIMIT 1) as thumbnail,
                    (SELECT COUNT(f.id) FROM galeri_foto f WHERE f.album_id = a.id) as jumlah_foto
                FROM galeri_album a
                LEFT JOIN users u ON a.created_by = u.id
                ORDER BY a.created_at DESC
            ";
            $result = $conn->query($query);
            $albums = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $albums]);

        } elseif ($action === 'get_album') {
            $album_id = $_GET['id'] ?? 0;
            $stmt_album = $conn->prepare("SELECT * FROM galeri_album WHERE id = ?");
            $stmt_album->bind_param("i", $album_id);
            $stmt_album->execute();
            $album_info = $stmt_album->get_result()->fetch_assoc();
            $stmt_album->close();

            if (!$album_info) throw new Exception("Album tidak ditemukan.");

            $stmt_fotos = $conn->prepare("SELECT * FROM galeri_foto WHERE album_id = ? ORDER BY uploaded_at DESC");
            $stmt_fotos->bind_param("i", $album_id);
            $stmt_fotos->execute();
            $fotos = $stmt_fotos->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_fotos->close();

            echo json_encode(['status' => 'success', 'data' => ['info' => $album_info, 'photos' => $fotos]]);

        } elseif ($action === 'get_photo_details') {
            $foto_id = $_GET['id'] ?? 0;
            if (empty($foto_id)) throw new Exception("ID Foto tidak valid.");

            // Get photo info
            $stmt_foto = $conn->prepare("SELECT * FROM galeri_foto WHERE id = ?");
            $stmt_foto->bind_param("i", $foto_id);
            $stmt_foto->execute();
            $foto_info = $stmt_foto->get_result()->fetch_assoc();
            $stmt_foto->close();

            if (!$foto_info) throw new Exception("Foto tidak ditemukan.");

            // Get comments
            $stmt_komentar = $conn->prepare("SELECT k.*, w.nama_lengkap, w.foto_profil FROM galeri_komentar k JOIN warga w ON k.warga_id = w.id WHERE k.foto_id = ? ORDER BY k.created_at ASC");
            $stmt_komentar->bind_param("i", $foto_id);
            $stmt_komentar->execute();
            $komentar = $stmt_komentar->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_komentar->close();

            // Add can_delete flag
            foreach ($komentar as &$k) {
                $k['can_delete'] = ($role === 'admin' || $k['warga_id'] == $warga_id);
            }
            unset($k);

            echo json_encode(['status' => 'success', 'data' => ['info' => $foto_info, 'comments' => $komentar]]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Actions that are strictly for admins
        if (in_array($action, ['create_album', 'get_single_album', 'update_album', 'delete_album', 'upload_photos', 'delete_photo'])) {
            if ($role !== 'admin') {
                throw new Exception("Akses ditolak. Hanya admin yang dapat melakukan aksi ini.");
            }
        }

        switch ($action) {
            case 'create_album':
                $deskripsi = $_POST['deskripsi'] ?? null;
                $kegiatan_id = !empty($_POST['kegiatan_id']) ? $_POST['kegiatan_id'] : null;

                $stmt = $conn->prepare("INSERT INTO galeri_album (judul, deskripsi, kegiatan_id, created_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $judul, $deskripsi, $kegiatan_id, $user_id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Buat Album Galeri', "Membuat album: {$judul}");
                echo json_encode(['status' => 'success', 'message' => 'Album berhasil dibuat.']);
                break;

            case 'get_single_album':
                $id = $_POST['id'] ?? 0;
                if (empty($id)) {
                    throw new Exception("ID Album tidak valid.");
                }
                $stmt = $conn->prepare("SELECT id, judul, deskripsi, kegiatan_id FROM galeri_album WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $album = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$album) throw new Exception("Album tidak ditemukan.");
                echo json_encode(['status' => 'success', 'data' => $album]);
                break;

            case 'update_album':
                $id = $_POST['id'] ?? 0;
                $judul = $_POST['judul'] ?? '';
                $deskripsi = $_POST['deskripsi'] ?? null;
                $kegiatan_id = !empty($_POST['kegiatan_id']) ? $_POST['kegiatan_id'] : null;

                $stmt = $conn->prepare("UPDATE galeri_album SET judul = ?, deskripsi = ?, kegiatan_id = ? WHERE id = ?");
                $stmt->bind_param("ssii", $judul, $deskripsi, $kegiatan_id, $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Album Galeri', "Mengubah album ID: {$id}");
                echo json_encode(['status' => 'success', 'message' => 'Album berhasil diperbarui.']);
                break;

            case 'delete_album':
                $id = $_POST['id'] ?? 0;
                // Also delete physical files
                $stmt_photos = $conn->prepare("SELECT path_file FROM galeri_foto WHERE album_id = ?");
                $stmt_photos->bind_param("i", $id);
                $stmt_photos->execute();
                $photos = $stmt_photos->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($photos as $photo) {
                    if (file_exists(PROJECT_ROOT . '/' . $photo['path_file'])) {
                        unlink(PROJECT_ROOT . '/' . $photo['path_file']);
                    }
                }
                $stmt_photos->close();

                $stmt = $conn->prepare("DELETE FROM galeri_album WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Album Galeri', "Menghapus album ID: {$id}");
                echo json_encode(['status' => 'success', 'message' => 'Album dan semua fotonya berhasil dihapus.']);
                break;

            case 'upload_photos':
                $album_id = $_POST['album_id'] ?? 0;
                if (empty($album_id) || !isset($_FILES['photos'])) {
                    throw new Exception("ID Album dan file foto wajib diisi.");
                }

                $upload_dir = PROJECT_ROOT . '/uploads/galeri/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

                $stmt = $conn->prepare("INSERT INTO galeri_foto (album_id, path_file) VALUES (?, ?)");
                $count = 0;
                foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_OK) continue;

                    $file_info = new SplFileInfo($_FILES['photos']['name'][$key]);
                    $extension = strtolower($file_info->getExtension());
                    $safe_filename = 'foto_' . uniqid() . '.' . $extension;
                    $destination = $upload_dir . $safe_filename;
                    $db_path = 'uploads/galeri/' . $safe_filename;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        $stmt->bind_param("is", $album_id, $db_path);
                        $stmt->execute();
                        $count++;
                    }
                }
                $stmt->close();
                log_activity($_SESSION['username'], 'Unggah Foto Galeri', "Mengunggah {$count} foto ke album ID: {$album_id}");
                echo json_encode(['status' => 'success', 'message' => "Berhasil mengunggah {$count} foto."]);
                break;

            case 'delete_photo':
                $id = $_POST['id'] ?? 0;
                $stmt_select = $conn->prepare("SELECT path_file FROM galeri_foto WHERE id = ?");
                $stmt_select->bind_param("i", $id);
                $stmt_select->execute();
                $photo = $stmt_select->get_result()->fetch_assoc();
                if ($photo && file_exists(PROJECT_ROOT . '/' . $photo['path_file'])) {
                    unlink(PROJECT_ROOT . '/' . $photo['path_file']);
                }
                $stmt_select->close();

                $stmt = $conn->prepare("DELETE FROM galeri_foto WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Foto Galeri', "Menghapus foto ID: {$id}");
                echo json_encode(['status' => 'success', 'message' => 'Foto berhasil dihapus.']);
                break;

            case 'add_comment':
                if (!$warga_id) throw new Exception("Hanya warga terdaftar yang dapat berkomentar.");
                $foto_id = $_POST['foto_id'] ?? 0;
                $komentar = trim($_POST['komentar'] ?? '');

                if (empty($foto_id) || empty($komentar)) {
                    throw new Exception("Komentar tidak boleh kosong.");
                }

                $stmt = $conn->prepare("INSERT INTO galeri_komentar (foto_id, warga_id, komentar) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $foto_id, $warga_id, $komentar);
                $stmt->execute();
                $new_comment_id = $stmt->insert_id;
                $stmt->close();

                // Fetch the newly created comment to return to the client
                $stmt_new = $conn->prepare("SELECT k.*, w.nama_lengkap, w.foto_profil FROM galeri_komentar k JOIN warga w ON k.warga_id = w.id WHERE k.id = ?");
                $stmt_new->bind_param("i", $new_comment_id);
                $stmt_new->execute();
                $new_comment_data = $stmt_new->get_result()->fetch_assoc();
                $stmt_new->close();

                log_activity($_SESSION['username'], 'Komentar Galeri', "Memberi komentar pada foto ID: {$foto_id}");
                echo json_encode(['status' => 'success', 'message' => 'Komentar berhasil ditambahkan.', 'data' => $new_comment_data]);
                break;

            case 'delete_comment':
                $comment_id = $_POST['comment_id'] ?? 0;
                if (empty($comment_id)) throw new Exception("ID Komentar tidak valid.");

                // Check ownership or admin role
                $stmt_check = $conn->prepare("SELECT warga_id FROM galeri_komentar WHERE id = ?");
                $stmt_check->bind_param("i", $comment_id);
                $stmt_check->execute();
                $comment_owner = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if (!$comment_owner) throw new Exception("Komentar tidak ditemukan.");

                if ($role !== 'admin' && $comment_owner['warga_id'] != $warga_id) {
                    throw new Exception("Anda tidak berhak menghapus komentar ini.");
                }

                $stmt = $conn->prepare("DELETE FROM galeri_komentar WHERE id = ?");
                $stmt->bind_param("i", $comment_id);
                $stmt->execute();

                log_activity($_SESSION['username'], 'Hapus Komentar Galeri', "Menghapus komentar ID: {$comment_id}");
                echo json_encode(['status' => 'success', 'message' => 'Komentar berhasil dihapus.']);
                break;

            default:
                throw new Exception("Aksi tidak valid.");
        }
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();