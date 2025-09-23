<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak. Silakan login sebagai admin.");
}

// Check for ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("ID Rumah tidak valid.");
}

$rumah_id = (int)$_GET['id'];
$conn = Database::getInstance()->getConnection();

// Fetch house details
$stmt_rumah = $conn->prepare("SELECT blok, nomor FROM rumah WHERE id = ?");
$stmt_rumah->bind_param("i", $rumah_id);
$stmt_rumah->execute();
$rumah = $stmt_rumah->get_result()->fetch_assoc();
$stmt_rumah->close();

if (!$rumah) {
    http_response_code(404);
    die("Data rumah tidak ditemukan.");
}

// Fetch history details
$stmt_history = $conn->prepare("
    SELECT 
        h.tanggal_masuk, h.tanggal_keluar,
        w.nama_lengkap as kepala_keluarga
    FROM rumah_penghuni_history h
    LEFT JOIN warga w ON h.no_kk_penghuni = w.no_kk AND w.status_dalam_keluarga = 'Kepala Keluarga'
    WHERE h.rumah_id = ? ORDER BY h.tanggal_masuk ASC
");
$stmt_history->bind_param("i", $rumah_id);
$stmt_history->execute();
$history_data = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();

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

$rumah_info_text = "Blok " . htmlspecialchars($rumah['blok']) . " Nomor " . htmlspecialchars($rumah['nomor']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Histori Penghuni - <?= $rumah_info_text ?></title>
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
        <h1>Histori Penghuni Rumah</h1>
        <p>Pengurus RT <?= htmlspecialchars($housing_name) ?></p>
        <p class="fw-bold"><?= $rumah_info_text ?></p>
    </div>
    <div class="report-body">
        <table class="table table-bordered table-sm">
            <thead class="table-dark"><tr><th>No.</th><th>Kepala Keluarga</th><th>Tanggal Masuk</th><th>Tanggal Keluar</th></tr></thead>
            <tbody>
                <?php if (empty($history_data)): ?><tr><td colspan="4" class="text-center">Tidak ada data histori ditemukan.</td></tr><?php else: ?><?php foreach ($history_data as $index => $item): ?><tr><td><?= $index + 1 ?></td><td><?= htmlspecialchars($item['kepala_keluarga'] ?: '(Data KK tidak ditemukan)') ?></td><td><?= date('d-m-Y', strtotime($item['tanggal_masuk'])) ?></td><td><?= $item['tanggal_keluar'] ? date('d-m-Y', strtotime($item['tanggal_keluar'])) : '<span class="fw-bold">Penghuni Saat Ini</span>' ?></td></tr><?php endforeach; ?><?php endif; ?>
            </tbody>
        </table>
        <div class="row mt-5"><div class="col-6"></div><div class="col-6 text-center"><p>................, <?= date('d F Y') ?></p><p>Ketua RT</p><br><br><br><p><strong>( ................... )</strong></p></div></div>
    </div>
</div>

<div class="print-button-container"><button class="btn btn-primary btn-lg shadow" onclick="window.print()"><i class="bi bi-printer-fill"></i> Cetak Histori</button></div>

</body>
</html>