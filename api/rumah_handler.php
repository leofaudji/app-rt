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
        switch ($action) {
            case 'list':
                $status_kepemilikan_filter = $_GET['status_kepemilikan'] ?? 'semua';
                $search_term = $_GET['search'] ?? '';

                $query = "
                    SELECT 
                        r.id, r.blok, r.nomor, r.pemilik, r.no_kk_penghuni,
                        w.nama_lengkap as kepala_keluarga,
                        w.status_tinggal,
                        (SELECT COUNT(id) FROM warga w_count WHERE w_count.no_kk = r.no_kk_penghuni) as jumlah_anggota,
                        (SELECT h.tanggal_masuk FROM rumah_penghuni_history h WHERE h.rumah_id = r.id AND h.tanggal_keluar IS NULL ORDER BY h.tanggal_masuk DESC LIMIT 1) as tanggal_masuk
                    FROM rumah r
                    LEFT JOIN warga w ON r.no_kk_penghuni = w.no_kk AND w.status_dalam_keluarga = 'Kepala Keluarga'
                ";

                $where_clauses = [];
                $params = [];
                $types = '';

                if ($status_kepemilikan_filter !== 'semua') {
                    if ($status_kepemilikan_filter === 'kosong') {
                        $where_clauses[] = "(r.no_kk_penghuni IS NULL OR r.no_kk_penghuni = '')";
                    } else { // 'tetap' or 'kontrak'
                        $where_clauses[] = "w.status_tinggal = ?";
                        $params[] = $status_kepemilikan_filter;
                        $types .= 's';
                    }
                }

                if (!empty($search_term)) {
                    $where_clauses[] = "(r.pemilik LIKE ? OR w.nama_lengkap LIKE ?)";
                    $search_param = "%" . $search_term . "%";
                    $params[] = $search_param;
                    $params[] = $search_param;
                    $types .= 'ss';
                }

                if (!empty($where_clauses)) {
                    $query .= " WHERE " . implode(' AND ', $where_clauses);
                }

                $stmt = $conn->prepare($query . " ORDER BY r.blok, r.nomor");
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $rumah = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $rumah]);
                break;

            case 'get_kk_list':
                $query = "
                    SELECT
                        w.no_kk,
                        (SELECT w_head.nama_lengkap FROM warga w_head WHERE w_head.no_kk = w.no_kk AND w_head.status_dalam_keluarga = 'Kepala Keluarga' LIMIT 1) as nama_lengkap
                    FROM warga w
                    WHERE w.no_kk IS NOT NULL AND w.no_kk != ''
                    GROUP BY w.no_kk ORDER BY nama_lengkap ASC
                ";
                $result = $conn->query($query);
                $kk_list = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $kk_list]);
                break;

            case 'get_anggota_keluarga':
                $no_kk = $_GET['no_kk'] ?? '';
                if (empty($no_kk)) {
                    throw new Exception("No. KK tidak boleh kosong.");
                }
                $stmt = $conn->prepare("SELECT nik, nama_lengkap, status_dalam_keluarga FROM warga WHERE no_kk = ? ORDER BY FIELD(status_dalam_keluarga, 'Kepala Keluarga', 'Istri', 'Anak', 'Lainnya'), tgl_lahir ASC");
                $stmt->bind_param("s", $no_kk);
                $stmt->execute();
                $anggota = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                echo json_encode(['status' => 'success', 'data' => $anggota]);
                break;

            case 'get_occupant_history':
                $rumah_id = $_GET['rumah_id'] ?? 0;
                if (empty($rumah_id)) {
                    throw new Exception("ID Rumah tidak boleh kosong.");
                }
                $stmt = $conn->prepare("
                    SELECT 
                        h.id, h.tanggal_masuk, h.tanggal_keluar, h.catatan,
                        w.nama_lengkap as kepala_keluarga
                    FROM rumah_penghuni_history h
                    LEFT JOIN warga w ON h.no_kk_penghuni = w.no_kk AND w.status_dalam_keluarga = 'Kepala Keluarga'
                    WHERE h.rumah_id = ? ORDER BY h.tanggal_masuk DESC
                ");
                $stmt->bind_param("i", $rumah_id);
                $stmt->execute();
                $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                echo json_encode(['status' => 'success', 'data' => $history]);
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Hanya admin yang bisa melakukan CUD
        if ($_SESSION['role'] !== 'admin') {
            throw new Exception("Akses ditolak.");
        }

        switch ($action) {
            case 'add':
                $no_kk = !empty($_POST['no_kk_penghuni']) ? $_POST['no_kk_penghuni'] : null;

                // Validasi: Cek apakah KK sudah menempati rumah lain
                if ($no_kk) {
                    $stmt_check = $conn->prepare("SELECT blok, nomor FROM rumah WHERE no_kk_penghuni = ?");
                    $stmt_check->bind_param("s", $no_kk);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    if ($result_check->num_rows > 0) {
                        $existing_house = $result_check->fetch_assoc();
                        throw new Exception("Gagal: KK tersebut sudah terdaftar sebagai penghuni di Blok " . $existing_house['blok'] . " No. " . $existing_house['nomor'] . ".");
                    }
                    $stmt_check->close();
                }

                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare("INSERT INTO rumah (blok, nomor, pemilik, no_kk_penghuni) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $_POST['blok'], $_POST['nomor'], $_POST['pemilik'], $no_kk);
                    $stmt->execute();
                    $new_rumah_id = $stmt->insert_id;
                    $stmt->close();

                    // Jika ada penghuni, catat di histori
                    if ($no_kk) {
                        $today = date('Y-m-d');
                        $stmt_history = $conn->prepare("INSERT INTO rumah_penghuni_history (rumah_id, no_kk_penghuni, tanggal_masuk) VALUES (?, ?, ?)");
                        $stmt_history->bind_param("iss", $new_rumah_id, $no_kk, $today);
                        $stmt_history->execute();
                        $stmt_history->close();
                    }

                    log_activity($_SESSION['username'], 'Tambah Rumah', 'Menambahkan rumah: Blok ' . $_POST['blok'] . ' No. ' . $_POST['nomor']);
                    $conn->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Rumah baru berhasil ditambahkan.']);
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e; // Re-throw to be caught by the outer handler
                }
                break;

            case 'update':
                $id = $_POST['id'];
                $new_no_kk = !empty($_POST['no_kk_penghuni']) ? $_POST['no_kk_penghuni'] : null;

                // Validasi: Cek apakah KK baru sudah menempati rumah lain
                if ($new_no_kk) {
                    $stmt_check = $conn->prepare("SELECT blok, nomor FROM rumah WHERE no_kk_penghuni = ? AND id != ?");
                    $stmt_check->bind_param("si", $new_no_kk, $id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    if ($result_check->num_rows > 0) {
                        $existing_house = $result_check->fetch_assoc();
                        throw new Exception("Gagal: KK tersebut sudah terdaftar sebagai penghuni di Blok " . $existing_house['blok'] . " No. " . $existing_house['nomor'] . ".");
                    }
                    $stmt_check->close();
                }

                $conn->begin_transaction();
                try {
                    // 1. Get current occupant
                    $stmt_get = $conn->prepare("SELECT no_kk_penghuni FROM rumah WHERE id = ?");
                    $stmt_get->bind_param("i", $id);
                    $stmt_get->execute();
                    $current_data = $stmt_get->get_result()->fetch_assoc();
                    $stmt_get->close();
                    $current_no_kk = $current_data['no_kk_penghuni'] ?? null;

                    // 2. If occupant changed, update history
                    if ($current_no_kk !== $new_no_kk) {
                        $today = date('Y-m-d');
                        // Mark old occupant's move-out date
                        if ($current_no_kk) {
                            $stmt_update_history = $conn->prepare("UPDATE rumah_penghuni_history SET tanggal_keluar = ? WHERE rumah_id = ? AND no_kk_penghuni = ? AND tanggal_keluar IS NULL");
                            $stmt_update_history->bind_param("sis", $today, $id, $current_no_kk);
                            $stmt_update_history->execute();
                            $stmt_update_history->close();
                        }
                        // Add new occupant's move-in record
                        if ($new_no_kk) {
                            $stmt_insert_history = $conn->prepare("INSERT INTO rumah_penghuni_history (rumah_id, no_kk_penghuni, tanggal_masuk) VALUES (?, ?, ?)");
                            $stmt_insert_history->bind_param("iss", $id, $new_no_kk, $today);
                            $stmt_insert_history->execute();
                            $stmt_insert_history->close();
                        }
                    }

                    // 3. Update the house data
                    $stmt = $conn->prepare("UPDATE rumah SET blok=?, nomor=?, pemilik=?, no_kk_penghuni=? WHERE id=?");
                    $stmt->bind_param("ssssi", $_POST['blok'], $_POST['nomor'], $_POST['pemilik'], $new_no_kk, $id);
                    $stmt->execute();
                    $stmt->close();

                    log_activity($_SESSION['username'], 'Update Rumah', 'Mengubah data rumah ID: ' . $_POST['id']);
                    $conn->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Data rumah berhasil diperbarui.']);
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e; // Re-throw to be caught by the outer handler
                }
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("DELETE FROM rumah WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                log_activity($_SESSION['username'], 'Hapus Rumah', 'Menghapus rumah ID: ' . $id);
                echo json_encode(['status' => 'success', 'message' => 'Data rumah berhasil dihapus.']);
                break;

            case 'get_single':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("SELECT * FROM rumah WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $rumah = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($rumah) {
                    echo json_encode(['status' => 'success', 'data' => $rumah]);
                } else {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Data rumah tidak ditemukan.']);
                }
                break;

            case 'update_history_note':
                if ($_SESSION['role'] !== 'admin') {
                    throw new Exception("Hanya admin yang dapat mengubah catatan histori.");
                }
                $history_id = $_POST['history_id'] ?? 0;
                $catatan = !empty($_POST['catatan']) ? trim($_POST['catatan']) : null;

                if (empty($history_id)) {
                    throw new Exception("ID Histori tidak valid.");
                }

                $stmt = $conn->prepare("UPDATE rumah_penghuni_history SET catatan = ? WHERE id = ?");
                $stmt->bind_param("si", $catatan, $history_id);
                $stmt->execute();
                $stmt->close();

                log_activity($_SESSION['username'], 'Update Histori Rumah', 'Mengubah catatan histori ID: ' . $history_id);
                echo json_encode(['status' => 'success', 'message' => 'Catatan histori berhasil diperbarui.']);
                break;

            default:
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
                break;
        }
    }
} catch (Exception $e) {
    $code = $e->getCode() ?: 400;
    http_response_code($code);
    $error_message = (strpos($e->getMessage(), 'Duplicate entry') !== false) ? 'Error: Blok dan nomor rumah sudah ada.' : $e->getMessage();
    echo json_encode(['status' => 'error', 'message' => $error_message]);
}

$conn->close();
?>