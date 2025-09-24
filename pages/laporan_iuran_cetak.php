<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    http_response_code(403);
    die("Akses ditolak.");
}

$conn = Database::getInstance()->getConnection();

// Get filters from URL
$tahun = $_GET['tahun'] ?? date('Y');
$min_tunggakan = $_GET['min_tunggakan'] ?? 2;
$search = $_GET['search'] ?? '';

// --- Fetch Data (Logic copied from api/laporan_iuran_handler.php for consistency) ---
$iuran_per_bulan = (float)get_setting('monthly_fee', 50000);
$current_year = (int)date('Y');
$current_month = (int)date('m');

if ($tahun < $current_year) {
    $months_due = 12;
    $month_limit = 13;
} elseif ($tahun > $current_year) {
    $months_due = 0;
    $month_limit = 1;
} else {
    $months_due = $current_month - 1;
    $month_limit = $current_month;
}

$params = [$months_due, $tahun, $month_limit, $min_tunggakan];
$types = 'iiii';

$query = "
    SELECT 
        w.nama_lengkap,
        CONCAT(r.blok, ' / ', r.nomor) as alamat,
        (GREATEST(0, ? - (SELECT COUNT(i.id) FROM iuran i WHERE i.no_kk = w.no_kk AND i.periode_tahun = ? AND i.periode_bulan < ?))) as jumlah_tunggakan
    FROM warga w
    JOIN rumah r ON w.no_kk = r.no_kk_penghuni
    WHERE w.status_dalam_keluarga = 'Kepala Keluarga'
";

if (!empty($search)) {
    $query .= " AND w.nama_lengkap LIKE ?";
    $params[] = "%{$search}%";
    $types .= 's';
}

$query .= " HAVING jumlah_tunggakan >= ? ORDER BY jumlah_tunggakan DESC, w.nama_lengkap ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tunggakan_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch settings
$housing_name = get_setting('housing_name', 'Perumahan Sejahtera');
$rt_head_name = get_setting('rt_head_name', 'Ketua RT');
$letterhead_image_path = get_setting('letterhead_image');

$letterhead_image_url = null;
if ($letterhead_image_path && file_exists(PROJECT_ROOT . '/' . $letterhead_image_path)) {
    $letterhead_image_url = base_url($letterhead_image_path);
}

$conn->close();

$periode_text = "Tahun " . $tahun;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Tunggakan Iuran - <?= htmlspecialchars($periode_text) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/print.css') ?>">
</head>
<body>

<div class="report-container">
    <?php if ($letterhead_image_url): ?>
    <div class="text-center mb-4"><img src="<?= $letterhead_image_url ?>" alt="Kop Surat" class="img-fluid"></div>
    <?php endif; ?>
    <div class="report-header">
        <h1>Laporan Tunggakan Iuran Warga</h1>
        <p>Pengurus RT <?= htmlspecialchars($housing_name) ?></p>
        <p class="fw-bold">PERIODE <?= htmlspecialchars(strtoupper($periode_text)) ?></p>
    </div>
    <div class="report-body">
        <p><strong>Filter Aktif:</strong> Tunggakan >= <?= htmlspecialchars($min_tunggakan) ?> bulan, Pencarian Nama: <?= !empty($search) ? htmlspecialchars($search) : '-' ?></p>
        <table class="table table-bordered table-sm">
            <thead class="table-dark"><tr><th>No.</th><th>Kepala Keluarga</th><th>Alamat</th><th>Jml. Tunggakan</th><th>Total Tunggakan</th></tr></thead>
            <tbody>
                <?php if (empty($tunggakan_data)): ?>
                    <tr><td colspan="5" class="text-center">Tidak ada data tunggakan ditemukan.</td></tr>
                <?php else: ?>
                    <?php foreach ($tunggakan_data as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($item['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($item['alamat']) ?></td>
                            <td class="text-center"><?= $item['jumlah_tunggakan'] ?> bulan</td>
                            <td class="text-end"><?= 'Rp ' . number_format($item['jumlah_tunggakan'] * $iuran_per_bulan, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="row mt-5"><div class="col-6"></div><div class="col-6 text-center"><p>................, <?= date('d F Y') ?></p><p>Bendahara RT</p><br><br><br><p><strong>( ................... )</strong></p></div></div>
    </div>
</div>

<div class="print-button-container"><button class="btn btn-primary btn-lg shadow" onclick="window.print()"><i class="bi bi-printer-fill"></i> Cetak Laporan</button></div>

</body>
</html>