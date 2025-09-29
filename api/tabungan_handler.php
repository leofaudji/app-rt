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
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_warga_id = null;

// Get warga_id for the logged-in user if their role is 'warga'
if ($user_role === 'warga') {
    $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
    $stmt_warga->bind_param("s", $_SESSION['username']);
    $stmt_warga->execute();
    $user_warga_id = $stmt_warga->get_result()->fetch_assoc()['id'] ?? null;
    $stmt_warga->close();
}
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        switch ($action) {
            case 'summary':
                if (!in_array($user_role, ['admin', 'bendahara'])) throw new Exception("Akses ditolak.");
                $search = $_GET['search'] ?? '';
                $query = "SELECT w.id as warga_id, w.nama_lengkap, w.no_kk, r.blok, r.nomor, 
                                 (SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) FROM tabungan_warga tw WHERE tw.warga_id = w.id) as saldo
                          FROM warga w
                          JOIN rumah r ON w.no_kk = r.no_kk_penghuni
                          WHERE w.status_dalam_keluarga = 'Kepala Keluarga'";
                if (!empty($search)) {
                    $query .= " AND w.nama_lengkap LIKE ?";
                    $stmt = $conn->prepare($query);
                    $searchTerm = "%{$search}%";
                    $stmt->bind_param("s", $searchTerm);
                } else {
                    $stmt = $conn->prepare($query);
                }
                $stmt->execute();
                $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $data]);
                break;

            case 'detail':
                $warga_id = $_GET['warga_id'] ?? 0;
                if ($user_role === 'warga' && $warga_id != $user_warga_id) {
                    throw new Exception("Akses ditolak. Anda hanya dapat melihat detail tabungan Anda sendiri.");
                }

                $stmt_warga = $conn->prepare("SELECT nama_lengkap, no_kk FROM warga WHERE id = ?");
                $stmt_warga->bind_param("i", $warga_id);
                $stmt_warga->execute();
                $warga = $stmt_warga->get_result()->fetch_assoc();

                $stmt_tx = $conn->prepare("SELECT t.*, u.nama_lengkap as pencatat, c.nama_kategori FROM tabungan_warga t LEFT JOIN users u ON t.dicatat_oleh = u.id JOIN tabungan_kategori c ON t.kategori_id = c.id WHERE t.warga_id = ? ORDER BY t.tanggal DESC, t.id DESC");
                $stmt_tx->bind_param("i", $warga_id);
                $stmt_tx->execute();
                $transactions = $stmt_tx->get_result()->fetch_all(MYSQLI_ASSOC);

                $stmt_saldo = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) as saldo FROM tabungan_warga WHERE warga_id = ?");
                $stmt_saldo->bind_param("i", $warga_id);
                $stmt_saldo->execute();
                $saldo = $stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0;

                $stmt_goals = $conn->prepare("SELECT * FROM tabungan_goals WHERE warga_id = ? ORDER BY tanggal_target ASC");
                $stmt_goals->bind_param("i", $warga_id);
                $stmt_goals->execute();
                $goals_raw = $stmt_goals->get_result()->fetch_all(MYSQLI_ASSOC);
                $goals = [];
                foreach ($goals_raw as $goal) {
                    $stmt_progress = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) as terkumpul FROM tabungan_warga WHERE goal_id = ?");
                    $stmt_progress->bind_param("i", $goal['id']);
                    $stmt_progress->execute();
                    $terkumpul = $stmt_progress->get_result()->fetch_assoc()['terkumpul'] ?? 0;
                    $goal['terkumpul'] = (float)$terkumpul;
                    $goals[] = $goal;
                    $stmt_progress->close();
                }

                echo json_encode(['status' => 'success', 'data' => ['warga' => $warga, 'transactions' => $transactions, 'saldo' => $saldo, 'goals' => $goals]]);
                break;

            default:
                throw new Exception("Aksi GET tidak valid.");
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'add_transaction':
                if (!in_array($user_role, ['admin', 'bendahara'])) throw new Exception("Akses ditolak.");
                $goal_id = !empty($_POST['goal_id']) ? (int)$_POST['goal_id'] : null;
                $stmt = $conn->prepare("INSERT INTO tabungan_warga (warga_id, tanggal, jenis, kategori_id, jumlah, keterangan, dicatat_oleh, goal_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issidsii", $_POST['warga_id'], $_POST['tanggal'], $_POST['jenis'], $_POST['kategori_id'], $_POST['jumlah'], $_POST['keterangan'], $user_id, $goal_id);
                if (!$stmt->execute()) {
                    throw new Exception("Gagal menyimpan transaksi tabungan: " . $stmt->error);
                }
                $new_tabungan_id = $stmt->insert_id;

                // --- Integrasi Otomatis ke Kas RT ---
                $stmt_warga_nama = $conn->prepare("SELECT nama_lengkap FROM warga WHERE id = ?");
                $stmt_warga_nama->bind_param("i", $_POST['warga_id']);
                $stmt_warga_nama->execute();
                $nama_warga = $stmt_warga_nama->get_result()->fetch_assoc()['nama_lengkap'] ?? 'Warga ID: ' . $_POST['warga_id'];
                $stmt_warga_nama->close();

                $jenis_kas = ($_POST['jenis'] === 'setor') ? 'masuk' : 'keluar';
                $keterangan_kas = ($_POST['jenis'] === 'setor' ? 'Setoran Tabungan: ' : 'Penarikan Tabungan: ') . $nama_warga . " (Ref Tabungan ID: {$new_tabungan_id})";
                $kategori_kas = 'Tabungan Warga';

                $stmt_kas = $conn->prepare("INSERT INTO kas (tanggal, jenis, kategori, keterangan, jumlah, dicatat_oleh) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_kas->bind_param("ssssdi", $_POST['tanggal'], $jenis_kas, $kategori_kas, $keterangan_kas, $_POST['jumlah'], $user_id);
                $stmt_kas->execute();
                $stmt_kas->close();
                // --- Akhir Integrasi ---

                log_activity($_SESSION['username'], 'Tambah Transaksi Tabungan', 'Menambah transaksi untuk warga ID: ' . $_POST['warga_id']);
                echo json_encode(['status' => 'success', 'message' => 'Transaksi tabungan berhasil ditambahkan.']);
                break;

            case 'delete_transaction':
                if (!in_array($user_role, ['admin', 'bendahara'])) throw new Exception("Akses ditolak.");
                $stmt = $conn->prepare("DELETE FROM tabungan_warga WHERE id = ?");
                $id_to_delete = $_POST['id'];
                $stmt->bind_param("i", $id_to_delete);
                if (!$stmt->execute()) {
                    throw new Exception("Gagal menghapus transaksi tabungan: " . $stmt->error);
                }

                // --- Hapus juga entri yang sesuai di tabel kas ---
                $keterangan_ref = "%(Ref Tabungan ID: {$id_to_delete})%";
                $stmt_delete_kas = $conn->prepare("DELETE FROM kas WHERE keterangan LIKE ?");
                $stmt_delete_kas->bind_param("s", $keterangan_ref);
                $stmt_delete_kas->execute();
                $stmt_delete_kas->close();
                // --- Akhir Hapus Integrasi ---

                log_activity($_SESSION['username'], 'Hapus Transaksi Tabungan', 'Menghapus transaksi tabungan ID: ' . $_POST['id']);
                echo json_encode(['status' => 'success', 'message' => 'Transaksi tabungan berhasil dihapus.']);
                break;

            case 'add_goal':
                if ($user_role !== 'warga' || !$user_warga_id) throw new Exception("Hanya warga yang dapat menambah target.");
                $tanggal_target = !empty($_POST['tanggal_target']) ? $_POST['tanggal_target'] : null;
                $stmt = $conn->prepare("INSERT INTO tabungan_goals (warga_id, nama_goal, target_jumlah, tanggal_target) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isds", $user_warga_id, $_POST['nama_goal'], $_POST['target_jumlah'], $tanggal_target);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Tambah Target Tabungan', 'Menambah target: ' . $_POST['nama_goal']);
                echo json_encode(['status' => 'success', 'message' => 'Target tabungan berhasil ditambahkan.']);
                break;

            case 'update_goal':
                if ($user_role !== 'warga' || !$user_warga_id) throw new Exception("Hanya warga yang dapat mengubah target.");
                $goal_id = $_POST['id'];
                // Security check: ensure the goal belongs to the logged-in user
                $stmt_check = $conn->prepare("SELECT id FROM tabungan_goals WHERE id = ? AND warga_id = ?");
                $stmt_check->bind_param("ii", $goal_id, $user_warga_id);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows === 0) throw new Exception("Akses ditolak. Target tidak ditemukan.");
                $stmt_check->close();

                $tanggal_target = !empty($_POST['tanggal_target']) ? $_POST['tanggal_target'] : null;
                $stmt = $conn->prepare("UPDATE tabungan_goals SET nama_goal = ?, target_jumlah = ?, tanggal_target = ? WHERE id = ?");
                $stmt->bind_param("sdsi", $_POST['nama_goal'], $_POST['target_jumlah'], $tanggal_target, $goal_id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Update Target Tabungan', 'Mengubah target ID: ' . $goal_id);
                echo json_encode(['status' => 'success', 'message' => 'Target tabungan berhasil diperbarui.']);
                break;

            case 'delete_goal':
                if ($user_role !== 'warga' || !$user_warga_id) throw new Exception("Hanya warga yang dapat menghapus target.");
                $goal_id = $_POST['id'];
                // Security check: ensure the goal belongs to the logged-in user
                $stmt_check = $conn->prepare("SELECT id FROM tabungan_goals WHERE id = ? AND warga_id = ?");
                $stmt_check->bind_param("ii", $goal_id, $user_warga_id);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows === 0) throw new Exception("Akses ditolak. Target tidak ditemukan.");
                $stmt_check->close();

                $stmt = $conn->prepare("DELETE FROM tabungan_goals WHERE id = ?");
                $stmt->bind_param("i", $goal_id);
                $stmt->execute();
                log_activity($_SESSION['username'], 'Hapus Target Tabungan', 'Menghapus target ID: ' . $goal_id);
                echo json_encode(['status' => 'success', 'message' => 'Target tabungan berhasil dihapus.']);
                break;

            default:
                throw new Exception("Aksi POST tidak valid.");
        }
    } else {
        throw new Exception("Metode request tidak valid.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

if (isset($stmt)) $stmt->close();
$conn->close();
?>