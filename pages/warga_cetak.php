<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak. Silakan login sebagai admin.");
}

$conn = Database::getInstance()->getConnection();

// Get filter parameters from URL
$searchTerm = $_GET['search'] ?? '';

// --- Build Query ---
$params = [];
$types = '';
$query = "SELECT nik, nama_lengkap, alamat, no_telepon, status_tinggal, pekerjaan, tgl_lahir FROM warga WHERE 1=1";

if (!empty($searchTerm)) {
    $query .= " AND (nama_lengkap LIKE ? OR nik LIKE ? OR alamat LIKE ?)";
    $likeTerm = "%{$searchTerm}%";
    $params = [$likeTerm, $likeTerm, $likeTerm];
    $types = 'sss';
}

$query .= " ORDER BY nama_lengkap ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$warga_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch settings
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
$app_settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $app_settings[$row['setting_key']] = $row['setting_value'];
}
$housing_name = $app_settings['housing_name'] ?? 'Perumahan Sejahtera';
$letterhead_image_path = $app_settings['letterhead_image'] ?? null;

$letterhead_image_url = null;
if ($letterhead_image_path && file_exists(PROJECT_ROOT . '/' . $letterhead_image_path)) {
    $letterhead_image_url = base_url($letterhead_image_path);
}

$conn->close();

function calculateAge($birthDate) {
    if (!$birthDate) return '-';
    $birth = new DateTime($birthDate);
    $today = new DateTime('today');
    return $birth->diff($today)->y;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Data Warga</title>
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
        <h1>Laporan Data Warga</h1>
        <p>Pengurus RT <?= htmlspecialchars($housing_name) ?></p>
    </div>
    <div class="report-body">
        <p><strong>Filter Aktif:</strong> Pencarian: <?= !empty($searchTerm) ? htmlspecialchars($searchTerm) : '-' ?></p>
        <table class="table table-bordered table-sm">
            <thead class="table-dark"><tr><th>No.</th><th>Nama Lengkap</th><th>NIK</th><th>Alamat</th><th>Status Tinggal</th><th>Umur</th></tr></thead>
            <tbody>
                <?php if (empty($warga_data)): ?><tr><td colspan="6" class="text-center">Tidak ada data ditemukan.</td></tr><?php else: ?><?php foreach ($warga_data as $index => $item): ?><tr><td><?= $index + 1 ?></td><td><?= htmlspecialchars($item['nama_lengkap']) ?></td><td>'<?= htmlspecialchars($item['nik']) ?></td><td><?= htmlspecialchars($item['alamat']) ?></td><td><?= htmlspecialchars(ucfirst($item['status_tinggal'])) ?></td><td><?= calculateAge($item['tgl_lahir']) ?></td></tr><?php endforeach; ?><?php endif; ?>
            </tbody>
        </table>
        <div class="row mt-5"><div class="col-6"></div><div class="col-6 text-center"><p>................, <?= date('d F Y') ?></p><p>Ketua RT</p><br><br><br><p><strong>( ................... )</strong></p></div></div>
    </div>
</div>

<div class="print-button-container"><button class="btn btn-primary btn-lg shadow" onclick="window.print()"><i class="bi bi-printer-fill"></i> Cetak Laporan</button></div>

</body>
</html>