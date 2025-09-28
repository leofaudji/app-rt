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
$role = $_SESSION['role'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'summary' && in_array($role, ['admin', 'bendahara'])) {
            $search = $_GET['search'] ?? '';
            $query = "
                SELECT 
                    w.id as warga_id, w.nama_lengkap, w.no_kk, r.blok, r.nomor,
                    (SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) FROM tabungan_warga tw WHERE tw.warga_id = w.id) as saldo
                FROM warga w
                LEFT JOIN rumah r ON w.no_kk = r.no_kk_penghuni
                WHERE w.status_dalam_keluarga = 'Kepala Keluarga'
            ";
            if (!empty($search)) {
                $query .= " AND (w.nama_lengkap LIKE ? OR w.no_kk LIKE ?)";
                $like_search = "%{$search}%";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $like_search, $like_search);
            } else {
                $stmt = $conn->prepare($query);
            }
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['status' => 'success', 'data' => $result]);

        } elseif ($action === 'detail') {
            $warga_id = $_GET['warga_id'] ?? 0;

            // Security check: Warga can only see their own details
            if ($role === 'warga') {
                $stmt_check = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_check->bind_param("s", $_SESSION['username']);
                $stmt_check->execute();
                $user_warga_id = $stmt_check->get_result()->fetch_assoc()['id'] ?? 0;
                if ($warga_id != $user_warga_id) {
                    throw new Exception("Akses ditolak.");
                }
            }

            $stmt_warga = $conn->prepare("SELECT id, nama_lengkap, no_kk FROM warga WHERE id = ?");
            $stmt_warga->bind_param("i", $warga_id);
            $stmt_warga->execute();
            $warga_info = $stmt_warga->get_result()->fetch_assoc();
            $stmt_warga->close();
            if (!$warga_info) throw new Exception("Warga tidak ditemukan.");

            $stmt_transaksi = $conn->prepare("
                SELECT t.*, k.nama_kategori, u.nama_lengkap as pencatat 
                FROM tabungan_warga t 
                JOIN tabungan_kategori k ON t.kategori_id = k.id
                LEFT JOIN users u ON t.dicatat_oleh = u.id
                WHERE t.warga_id = ? 
                ORDER BY t.tanggal DESC, t.created_at DESC
            ");
            $stmt_transaksi->bind_param("i", $warga_id);
            $stmt_transaksi->execute();
            $transactions = $stmt_transaksi->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_transaksi->close();

            $saldo = 0;
            foreach ($transactions as $tx) {
                $saldo += ($tx['jenis'] === 'setor' ? $tx['jumlah'] : -$tx['jumlah']);
            }

            // Get savings goals
            $stmt_goals = $conn->prepare("SELECT * FROM tabungan_goals WHERE warga_id = ? ORDER BY status, tanggal_target ASC");
            $stmt_goals->bind_param("i", $warga_id);
            $stmt_goals->execute();
            $goals = $stmt_goals->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_goals->close();

            echo json_encode([
                'status' => 'success', 
                'data' => [
                    'warga' => $warga_info,
                    'transactions' => $transactions,
                    'saldo' => $saldo,
                    'goals' => $goals
                ]
            ]);
        } else {
            throw new Exception("Aksi GET tidak valid.");
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'add_transaction':
                if (!in_array($role, ['admin', 'bendahara'])) {
                    throw new Exception("Hanya admin atau bendahara yang dapat melakukan aksi ini.");
                }
                $warga_id = (int)($_POST['warga_id'] ?? 0);
                $tanggal = $_POST['tanggal'] ?? '';
                $jenis = $_POST['jenis'] ?? '';
                $kategori_id = (int)($_POST['kategori_id'] ?? 0);
                $jumlah = (float)($_POST['jumlah'] ?? 0);
                $keterangan = $_POST['keterangan'] ?? null;
                $dicatat_oleh = $_SESSION['user_id'];

                if (empty($warga_id) || empty($tanggal) || empty($jenis) || empty($kategori_id) || empty($jumlah)) {
                    throw new Exception("Semua field wajib diisi.");
                }

                // Check saldo if withdrawal
                if ($jenis === 'tarik') {
                    $stmt_saldo = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) as saldo FROM tabungan_warga WHERE warga_id = ?");
                    $stmt_saldo->bind_param("i", $warga_id);
                    $stmt_saldo->execute();
                    $current_saldo = (float)($stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0);
                    $stmt_saldo->close();
                    if ($jumlah > $current_saldo) {
                        throw new Exception("Penarikan gagal. Saldo tidak mencukupi (Saldo saat ini: " . number_format($current_saldo) . ").");
                    }
                }

                $stmt = $conn->prepare("INSERT INTO tabungan_warga (warga_id, tanggal, jenis, kategori_id, jumlah, keterangan, dicatat_oleh) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issidsi", $warga_id, $tanggal, $jenis, $kategori_id, $jumlah, $keterangan, $dicatat_oleh);
                $stmt->execute();
                $stmt->close();

                log_activity($_SESSION['username'], 'Transaksi Tabungan', "Menambah transaksi {$jenis} Rp {$jumlah} untuk warga ID {$warga_id}");
                echo json_encode(['status' => 'success', 'message' => 'Transaksi tabungan berhasil disimpan.']);
                break;

            case 'delete_transaction':
                if (!in_array($role, ['admin', 'bendahara'])) {
                    throw new Exception("Hanya admin atau bendahara yang dapat melakukan aksi ini.");
                }
                $id = (int)($_POST['id'] ?? 0);
                if (empty($id)) throw new Exception("ID transaksi tidak valid.");

                // Get transaction details before deleting for logging
                $stmt_get = $conn->prepare("SELECT * FROM tabungan_warga WHERE id = ?");
                $stmt_get->bind_param("i", $id);
                $stmt_get->execute();
                $tx = $stmt_get->get_result()->fetch_assoc();
                $stmt_get->close();

                if (!$tx) throw new Exception("Transaksi tidak ditemukan.");

                // Re-check balance constraint upon deletion
                $stmt_saldo = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) as saldo FROM tabungan_warga WHERE warga_id = ?");
                $stmt_saldo->bind_param("i", $tx['warga_id']);
                $stmt_saldo->execute();
                $current_saldo = (float)($stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0);
                $stmt_saldo->close();

                $saldo_after_delete = $current_saldo - ($tx['jenis'] === 'setor' ? $tx['jumlah'] : -$tx['jumlah']);
                if ($saldo_after_delete < 0) {
                    throw new Exception("Transaksi tidak dapat dihapus karena akan menyebabkan saldo menjadi negatif.");
                }

                $stmt = $conn->prepare("DELETE FROM tabungan_warga WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                log_activity($_SESSION['username'], 'Hapus Transaksi Tabungan', "Menghapus transaksi tabungan ID: {$id}");
                echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil dihapus.']);
                break;

            case 'add_goal':
                // Find warga_id from user's nama_panggilan (username)
                $stmt_warga_check = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_warga_check->bind_param("s", $_SESSION['username']);
                $stmt_warga_check->execute();
                $warga = $stmt_warga_check->get_result()->fetch_assoc();
                $warga_id = $warga['id'] ?? null;
                $stmt_warga_check->close();
                if (!$warga_id) {
                    throw new Exception("Profil warga Anda tidak ditemukan.");
                }
                if ($role !== 'warga') throw new Exception("Hanya warga yang dapat menambah target tabungan.");
                $nama_goal = trim($_POST['nama_goal'] ?? '');
                $target_jumlah = (float)($_POST['target_jumlah'] ?? 0);
                $tanggal_target = !empty($_POST['tanggal_target']) ? $_POST['tanggal_target'] : null;

                if (empty($nama_goal) || empty($target_jumlah)) {
                    throw new Exception("Nama target dan jumlah target wajib diisi.");
                }

                $stmt = $conn->prepare("INSERT INTO tabungan_goals (warga_id, nama_goal, target_jumlah, tanggal_target) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isds", $warga_id, $nama_goal, $target_jumlah, $tanggal_target);
                $stmt->execute();
                $stmt->close();
                log_activity($_SESSION['username'], 'Tambah Target Tabungan', "Menambah target: {$nama_goal}");
                echo json_encode(['status' => 'success', 'message' => 'Target tabungan berhasil ditambahkan.']);
                break;

            case 'update_goal':
                // Find warga_id from user's nama_panggilan (username)
                $stmt_warga_check = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_warga_check->bind_param("s", $_SESSION['username']);
                $stmt_warga_check->execute();
                $warga = $stmt_warga_check->get_result()->fetch_assoc();
                $warga_id = $warga['id'] ?? null;
                $stmt_warga_check->close();
                if (!$warga_id) {
                    throw new Exception("Profil warga Anda tidak ditemukan.");
                }
                if ($role !== 'warga') throw new Exception("Hanya warga yang dapat mengubah target tabungan.");
                $goal_id = (int)($_POST['id'] ?? 0);
                $nama_goal = trim($_POST['nama_goal'] ?? '');
                $target_jumlah = (float)($_POST['target_jumlah'] ?? 0);
                $tanggal_target = !empty($_POST['tanggal_target']) ? $_POST['tanggal_target'] : null;

                if (empty($goal_id) || empty($nama_goal) || empty($target_jumlah)) {
                    throw new Exception("Semua field wajib diisi.");
                }

                $stmt = $conn->prepare("UPDATE tabungan_goals SET nama_goal = ?, target_jumlah = ?, tanggal_target = ? WHERE id = ? AND warga_id = ?");
                $stmt->bind_param("sdsii", $nama_goal, $target_jumlah, $tanggal_target, $goal_id, $warga_id);
                $stmt->execute();
                $stmt->close();
                log_activity($_SESSION['username'], 'Update Target Tabungan', "Mengubah target ID: {$goal_id}");
                echo json_encode(['status' => 'success', 'message' => 'Target tabungan berhasil diperbarui.']);
                break;

            case 'delete_goal':
                // Find warga_id from user's nama_panggilan (username)
                $stmt_warga_check = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
                $stmt_warga_check->bind_param("s", $_SESSION['username']);
                $stmt_warga_check->execute();
                $warga = $stmt_warga_check->get_result()->fetch_assoc();
                $warga_id = $warga['id'] ?? null;
                $stmt_warga_check->close();
                if (!$warga_id) {
                    throw new Exception("Profil warga Anda tidak ditemukan.");
                }
                if ($role !== 'warga') throw new Exception("Hanya warga yang dapat menghapus target tabungan.");
                $goal_id = (int)($_POST['id'] ?? 0);
                if (empty($goal_id)) {
                    throw new Exception("ID target tidak valid.");
                }
                $stmt = $conn->prepare("DELETE FROM tabungan_goals WHERE id = ? AND warga_id = ?");
                $stmt->bind_param("ii", $goal_id, $warga_id);
                $stmt->execute();
                $stmt->close();
                log_activity($_SESSION['username'], 'Hapus Target Tabungan', "Menghapus target ID: {$goal_id}");
                echo json_encode(['status' => 'success', 'message' => 'Target tabungan berhasil dihapus.']);
                break;

            default: throw new Exception("Aksi POST tidak valid.");
        }
    } else { throw new Exception("Metode request tidak valid."); }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
$conn->close();