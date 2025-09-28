<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$data = [];

// Get filter parameters or default to current month/year
$filter_bulan = $_GET['bulan'] ?? date('m');
$filter_tahun = $_GET['tahun'] ?? date('Y');

try {
    // Total Warga
    $result = $conn->query("SELECT COUNT(id) as total FROM warga");
    $data['total_warga'] = $result->fetch_assoc()['total'];

    // Saldo Kas
    $result = $conn->query("SELECT (SELECT IFNULL(SUM(jumlah), 0) FROM kas WHERE jenis = 'masuk') - (SELECT IFNULL(SUM(jumlah), 0) FROM kas WHERE jenis = 'keluar') as saldo");
    $saldo = $result->fetch_assoc()['saldo'];
    $data['saldo_kas'] = 'Rp ' . number_format($saldo, 0, ',', '.');

    // Saldo Tabungan Warga
    $result_tabungan = $conn->query("SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) as saldo FROM tabungan_warga");
    $saldo_tabungan = $result_tabungan->fetch_assoc()['saldo'] ?? 0;
    $data['saldo_tabungan'] = 'Rp ' . number_format($saldo_tabungan, 0, ',', '.');

    // --- Admin Tasks Widget Data ---
    $data['admin_tasks'] = [];
    if (in_array($_SESSION['role'], ['admin', 'bendahara'])) {
        $tasks = [
            'laporan' => ['query' => "SELECT COUNT(id) as total FROM laporan_warga WHERE status = 'baru'", 'label' => 'Laporan Warga Baru', 'link' => '/laporan', 'roles' => ['admin', 'bendahara']],
            'booking' => ['query' => "SELECT COUNT(id) as total FROM booking_fasilitas WHERE status = 'pending'", 'label' => 'Booking Fasilitas Pending', 'link' => '/booking', 'roles' => ['admin']],
            'surat' => ['query' => "SELECT COUNT(id) as total FROM surat_pengantar WHERE status = 'pending'", 'label' => 'Permintaan Surat Baru', 'link' => '/surat-pengantar', 'roles' => ['admin']],
            'usaha' => ['query' => "SELECT COUNT(id) as total FROM usaha_warga WHERE status = 'pending'", 'label' => 'Pendaftaran Usaha Baru', 'link' => '/info-usaha', 'roles' => ['admin']],
        ];

        foreach ($tasks as $key => $task) {
            if (in_array($_SESSION['role'], $task['roles'])) {
                $result = $conn->query($task['query']);
                $count = $result->fetch_assoc()['total'];
                if ($count > 0) {
                    $data['admin_tasks'][] = ['label' => $task['label'], 'count' => (int)$count, 'link' => $task['link']];
                }
            }
        }
    }

    // --- Ringkasan Status Rumah untuk Chart ---
    $stmt_tetap = $conn->query("SELECT COUNT(r.id) as total FROM rumah r JOIN warga w ON r.no_kk_penghuni = w.no_kk WHERE w.status_dalam_keluarga = 'Kepala Keluarga' AND w.status_tinggal = 'tetap'");
    $total_tetap = $stmt_tetap->fetch_assoc()['total'];

    $stmt_kontrak = $conn->query("SELECT COUNT(r.id) as total FROM rumah r JOIN warga w ON r.no_kk_penghuni = w.no_kk WHERE w.status_dalam_keluarga = 'Kepala Keluarga' AND w.status_tinggal = 'kontrak'");
    $total_kontrak = $stmt_kontrak->fetch_assoc()['total'];

    $stmt_kosong = $conn->query("SELECT COUNT(id) as total FROM rumah WHERE no_kk_penghuni IS NULL OR no_kk_penghuni = ''");
    $total_kosong = $stmt_kosong->fetch_assoc()['total'];

    $data['status_rumah'] = [
        'labels' => ['Milik Sendiri (Dihuni)', 'Sewa/Kontrak (Dihuni)', 'Tidak Berpenghuni'],
        'data' => [(int)$total_tetap, (int)$total_kontrak, (int)$total_kosong]
    ];

    // --- Warga Ulang Tahun Bulan Ini ---
    $stmt_ultah = $conn->prepare("SELECT nama_lengkap, tgl_lahir FROM warga WHERE MONTH(tgl_lahir) = ? ORDER BY DAY(tgl_lahir) ASC");
    $stmt_ultah->bind_param("s", $filter_bulan);
    $stmt_ultah->execute();
    $data['ulang_tahun_bulan_ini'] = $stmt_ultah->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_ultah->close();

    // --- 5 Pengumuman Terbaru ---
    $stmt_pengumuman = $conn->query("SELECT id, judul, created_at FROM pengumuman ORDER BY created_at DESC LIMIT 5");
    $data['pengumuman_terbaru'] = $stmt_pengumuman->fetch_all(MYSQLI_ASSOC);

    // --- 3 Kegiatan Akan Datang ---
    $stmt_kegiatan = $conn->query("SELECT id, judul, tanggal_kegiatan FROM kegiatan WHERE tanggal_kegiatan >= NOW() ORDER BY tanggal_kegiatan ASC LIMIT 3");
    $data['kegiatan_akan_datang'] = $stmt_kegiatan->fetch_all(MYSQLI_ASSOC);

    // --- Demografi Warga ---
    $stmt_demografi = $conn->query("
        SELECT 
            SUM(CASE WHEN jenis_kelamin = 'Laki-laki' THEN 1 ELSE 0 END) as total_laki,
            SUM(CASE WHEN jenis_kelamin = 'Perempuan' THEN 1 ELSE 0 END) as total_perempuan,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) >= 17 THEN 1 ELSE 0 END) as total_dewasa,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) < 17 THEN 1 ELSE 0 END) as total_anak
        FROM warga
    ");
    $demografi = $stmt_demografi->fetch_assoc();
    $data['demografi'] = [
        'labels' => ['Laki-laki', 'Perempuan', 'Dewasa', 'Anak-anak'],
        'data' => [(int)$demografi['total_laki'], (int)$demografi['total_perempuan'], (int)$demografi['total_dewasa'], (int)$demografi['total_anak']]
    ];

    // --- Pemasukan vs Pengeluaran Bulan Ini ---
    $stmt_kas_bulanan = $conn->prepare("
        SELECT 
            SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) as total_pemasukan,
            SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) as total_pengeluaran
        FROM kas 
        WHERE YEAR(tanggal) = ? AND MONTH(tanggal) = ?
    ");
    $stmt_kas_bulanan->bind_param("ii", $filter_tahun, $filter_bulan);
    $stmt_kas_bulanan->execute();
    $kas_bulanan = $stmt_kas_bulanan->get_result()->fetch_assoc();
    $stmt_kas_bulanan->close();

    $data['kas_bulanan'] = [
        'labels' => ['Pemasukan', 'Pengeluaran'],
        'data' => [(float)($kas_bulanan['total_pemasukan'] ?? 0), (float)($kas_bulanan['total_pengeluaran'] ?? 0)]
    ];

    // --- Iuran Summary This Month ---
    if (in_array($_SESSION['role'], ['admin', 'bendahara'])) {
        $total_kk_result = $conn->query("SELECT COUNT(id) as total FROM rumah WHERE no_kk_penghuni IS NOT NULL AND no_kk_penghuni != ''");
        $total_kk = (int)($total_kk_result->fetch_assoc()['total'] ?? 0);

        $stmt_iuran = $conn->prepare("SELECT COUNT(id) as total FROM iuran WHERE periode_tahun = ? AND periode_bulan = ?");
        $stmt_iuran->bind_param("ii", $filter_tahun, $filter_bulan);
        $stmt_iuran->execute();
        $kk_lunas = (int)($stmt_iuran->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt_iuran->close();

        $data['iuran_summary'] = [ 'total_kk' => $total_kk, 'kk_lunas' => $kk_lunas, 'persentase' => ($total_kk > 0) ? round(($kk_lunas / $total_kk) * 100) : 0 ];
    }

    // --- Saldo Kas Trend (Last 6 Months) ---
    $trend_labels = [];
    $trend_balances = [];

    // Get the start date (6 months ago, beginning of that month)
    $start_date = date('Y-m-01', strtotime('-5 months'));

    // 1. Get the balance before this period
    $stmt_saldo_awal_trend = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END) as saldo FROM kas WHERE tanggal < ?");
    $stmt_saldo_awal_trend->bind_param("s", $start_date);
    $stmt_saldo_awal_trend->execute();
    $running_balance = (float)($stmt_saldo_awal_trend->get_result()->fetch_assoc()['saldo'] ?? 0);
    $stmt_saldo_awal_trend->close();

    // 2. Get monthly income/expense for the last 6 months
    $stmt_trend = $conn->prepare("
        SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan,
               SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) as pemasukan,
               SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) as pengeluaran
        FROM kas WHERE tanggal >= ?
        GROUP BY YEAR(tanggal), MONTH(tanggal) ORDER BY tahun, bulan
    ");
    $stmt_trend->bind_param("s", $start_date);
    $stmt_trend->execute();
    $monthly_transactions = $stmt_trend->get_result()->fetch_all(MYSQLI_ASSOC);
    $transactions_map = [];
    foreach ($monthly_transactions as $tx) { $transactions_map["{$tx['tahun']}-{$tx['bulan']}"] = $tx; }
    $stmt_trend->close();

    // 3. Calculate running balance for each of the last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime("first day of -$i months");
        $trend_labels[] = $date->format('M \'y');
        $key = $date->format('Y-n');
        if (isset($transactions_map[$key])) { $running_balance += (float)$transactions_map[$key]['pemasukan'] - (float)$transactions_map[$key]['pengeluaran']; }
        $trend_balances[] = $running_balance;
    }
    $data['saldo_trend'] = ['labels' => $trend_labels, 'data' => $trend_balances];

    // --- Saldo Tabungan Trend (Last 6 Months) ---
    $trend_labels_tabungan = [];
    $trend_balances_tabungan = [];

    // 1. Get the balance before this period
    $stmt_saldo_awal_tabungan = $conn->prepare("SELECT SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END) as saldo FROM tabungan_warga WHERE tanggal < ?");
    $stmt_saldo_awal_tabungan->bind_param("s", $start_date);
    $stmt_saldo_awal_tabungan->execute();
    $running_balance_tabungan = (float)($stmt_saldo_awal_tabungan->get_result()->fetch_assoc()['saldo'] ?? 0);
    $stmt_saldo_awal_tabungan->close();

    // 2. Get monthly deposits/withdrawals for the last 6 months
    $stmt_trend_tabungan = $conn->prepare("
        SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan,
               SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE 0 END) as setoran,
               SUM(CASE WHEN jenis = 'tarik' THEN jumlah ELSE 0 END) as penarikan
        FROM tabungan_warga WHERE tanggal >= ?
        GROUP BY YEAR(tanggal), MONTH(tanggal) ORDER BY tahun, bulan
    ");
    $stmt_trend_tabungan->bind_param("s", $start_date);
    $stmt_trend_tabungan->execute();
    $monthly_transactions_tabungan = $stmt_trend_tabungan->get_result()->fetch_all(MYSQLI_ASSOC);
    $transactions_map_tabungan = [];
    foreach ($monthly_transactions_tabungan as $tx) { $transactions_map_tabungan["{$tx['tahun']}-{$tx['bulan']}"] = $tx; }
    $stmt_trend_tabungan->close();

    // 3. Calculate running balance for each of the last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime("first day of -$i months");
        $key = $date->format('Y-n');
        if (isset($transactions_map_tabungan[$key])) { $running_balance_tabungan += (float)$transactions_map_tabungan[$key]['setoran'] - (float)$transactions_map_tabungan[$key]['penarikan']; }
        $trend_balances_tabungan[] = $running_balance_tabungan;
    }
    $data['saldo_tabungan_trend'] = ['labels' => $trend_labels, 'data' => $trend_balances_tabungan];

    // --- New Residents Widget ---
    $stmt_warga_baru = $conn->query("
        SELECT w.id, w.nama_lengkap, w.foto_profil, r.blok, r.nomor, w.created_at
        FROM warga w
        LEFT JOIN rumah r ON w.no_kk = r.no_kk_penghuni
        WHERE w.status_dalam_keluarga = 'Kepala Keluarga'
        AND w.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ORDER BY w.created_at DESC 
        LIMIT 5
    ");
    $data['warga_baru'] = $stmt_warga_baru->fetch_all(MYSQLI_ASSOC);

    // --- Log Keuangan Terbaru ---
    if (in_array($_SESSION['role'], ['admin', 'bendahara'])) {
        $stmt_log_kas = $conn->query("
            SELECT tanggal, jenis, keterangan, jumlah 
            FROM kas 
            ORDER BY tanggal DESC, id DESC 
            LIMIT 5
        ");
        $data['log_keuangan_terbaru'] = $stmt_log_kas->fetch_all(MYSQLI_ASSOC);
    }

    // --- Warga Menunggak Iuran (> 2 bulan di tahun berjalan) ---
    if (in_array($_SESSION['role'], ['admin', 'bendahara'])) {
        // Bulan yang sudah seharusnya dibayar (tidak termasuk bulan yang difilter)
        $months_due = $filter_bulan - 1;

        $query_tunggakan = "
            SELECT 
                w.no_kk, w.nama_lengkap,
                (GREATEST(0, ? - (SELECT COUNT(i.id) FROM iuran i WHERE i.no_kk = w.no_kk AND i.periode_tahun = ? AND i.periode_bulan < ?))) as jumlah_tunggakan
            FROM warga w
            WHERE w.status_dalam_keluarga = 'Kepala Keluarga'
            HAVING jumlah_tunggakan >= 2
            ORDER BY jumlah_tunggakan DESC, w.nama_lengkap ASC
        ";
        $stmt_tunggakan = $conn->prepare($query_tunggakan);
        $stmt_tunggakan->bind_param("iii", $months_due, $filter_tahun, $filter_bulan);
        $stmt_tunggakan->execute();
        $data['iuran_menunggak'] = $stmt_tunggakan->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_tunggakan->close();
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error: ' . $e->getMessage()]);
}

$conn->close();