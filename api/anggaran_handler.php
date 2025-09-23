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
$action = $_REQUEST['action'] ?? '';
$tahun = $_REQUEST['tahun'] ?? date('Y');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_report') {
            // 1. Get all budget categories for the year
            $stmt_anggaran = $conn->prepare("SELECT id, kategori, jumlah_anggaran FROM anggaran WHERE tahun = ? ORDER BY kategori");
            $stmt_anggaran->bind_param("i", $tahun);
            $stmt_anggaran->execute();
            $anggaran_result = $stmt_anggaran->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_anggaran->close();

            // 2. Get all expense realizations for the year, grouped by category
            $stmt_kas = $conn->prepare("
                SELECT kategori, SUM(jumlah) as total_realisasi 
                FROM kas 
                WHERE jenis = 'keluar' AND YEAR(tanggal) = ? 
                GROUP BY kategori
            ");
            $stmt_kas->bind_param("i", $tahun);
            $stmt_kas->execute();
            $kas_result = $stmt_kas->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_kas->close();

            $realisasi = array_column($kas_result, 'total_realisasi', 'kategori');

            // 3. Combine the data
            $report = [];
            foreach ($anggaran_result as $item) {
                $realisasi_belanja = $realisasi[$item['kategori']] ?? 0.00;
                $sisa_anggaran = $item['jumlah_anggaran'] - $realisasi_belanja;
                $persentase = ($item['jumlah_anggaran'] > 0) ? ($realisasi_belanja / $item['jumlah_anggaran']) * 100 : 0;

                $report[] = [
                    'kategori' => $item['kategori'],
                    'jumlah_anggaran' => (float)$item['jumlah_anggaran'],
                    'realisasi_belanja' => (float)$realisasi_belanja,
                    'sisa_anggaran' => (float)$sisa_anggaran,
                    'persentase' => round($persentase, 2)
                ];
            }

            echo json_encode(['status' => 'success', 'data' => $report]);

        } elseif ($action === 'list_budget') {
            $stmt = $conn->prepare("SELECT id, kategori, jumlah_anggaran FROM anggaran WHERE tahun = ? ORDER BY kategori");
            $stmt->bind_param("i", $tahun);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $result]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'save_budget':
                $id = $_POST['id'] ?? 0;
                $jumlah = $_POST['jumlah'] ?? 0;

                $stmt = $conn->prepare("UPDATE anggaran SET jumlah_anggaran = ? WHERE id = ?");
                $stmt->bind_param("di", $jumlah, $id);
                $stmt->execute();
                
                log_activity($_SESSION['username'], 'Update Anggaran', "Mengubah anggaran ID: {$id}");
                echo json_encode(['status' => 'success', 'message' => 'Anggaran berhasil diperbarui.']);
                break;

            case 'add_budget':
                $kategori = $_POST['kategori'] ?? '';
                $jumlah = $_POST['jumlah'] ?? 0;

                if (empty($kategori) || empty($jumlah)) {
                    throw new Exception("Kategori dan Jumlah tidak boleh kosong.");
                }

                $stmt = $conn->prepare("INSERT INTO anggaran (tahun, kategori, jumlah_anggaran) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $tahun, $kategori, $jumlah);
                $stmt->execute();

                log_activity($_SESSION['username'], 'Tambah Anggaran', "Menambah anggaran {$kategori} untuk tahun {$tahun}");
                echo json_encode(['status' => 'success', 'message' => 'Kategori anggaran baru berhasil ditambahkan.']);
                break;

            case 'delete_budget':
                $id = $_POST['id'] ?? 0;
                $stmt = $conn->prepare("DELETE FROM anggaran WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                log_activity($_SESSION['username'], 'Hapus Anggaran', "Menghapus anggaran ID: {$id}");
                echo json_encode(['status' => 'success', 'message' => 'Kategori anggaran berhasil dihapus.']);
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
?>