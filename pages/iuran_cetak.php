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
$bulan = $_GET['bulan'] ?? date('m');
$status = $_GET['status'] ?? 'semua';
$search = $_GET['search'] ?? '';

// --- Fetch Data ---
$params = [];
$types = '';
$query = "
    SELECT 
        r.no_kk_penghuni as no_kk,
        CONCAT(r.blok, ' / ', r.nomor) as alamat,
        w.nama_lengkap as kepala_keluarga,
        i.id as iuran_id, i.tanggal_bayar, i.jumlah
    FROM rumah r
    LEFT JOIN warga w ON r.no_kk_penghuni = w.no_kk AND w.status_dalam_keluarga = 'Kepala Keluarga'
    LEFT JOIN iuran i ON r.no_kk_penghuni = i.no_kk AND i.periode_tahun = ? AND i.periode_bulan = ?
    WHERE r.no_kk_penghuni IS NOT NULL AND r.no_kk_penghuni != ''
";
$params = [$tahun, $bulan];
$types = 'ii';

if (!empty($search)) {
    $query .= " AND w.nama_lengkap LIKE ?";
    $params[] = "%{$search}%";
    $types .= 's';
}

if ($status === 'lunas') {
    $query .= " HAVING iuran_id IS NOT NULL";
} elseif ($status === 'belum_lunas') {
    $query .= " HAVING iuran_id IS NULL";
}

$query .= " ORDER BY r.blok, r.nomor";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$iuran_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

// Prepare text for period
$periode_text = "Periode " . DateTime::createFromFormat('!m', $bulan)->format('F') . " " . $tahun;
$status_text = ucfirst(str_replace('_', ' ', $status));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Iuran - <?= htmlspecialchars($periode_text) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .report-container { max-width: 800px; margin: 2rem auto; background: white; border: 1px solid #dee2e6; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); padding: 2rem; }
        .report-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 1rem; margin-bottom: 2rem; }
        .report-header h1 { font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem; }
        .report-header p { font-size: 1.1rem; margin-bottom: 0; }
        .print-button-container { position: fixed; bottom: 20px; right: 20px; z-index: 100; }
        @media print {
            body { background-color: white; }
            .print-button-container { display: none; }
            .report-container { box-shadow: none; border: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="report-container">
    <?php if ($letterhead_image_url): ?>
    <div class="text-center mb-4"><img src="<?= $letterhead_image_url ?>" alt="Kop Surat" class="img-fluid"></div>
    <?php endif; ?>
    <div class="report-header">
        <h1>Laporan Iuran Warga</h1>
        <p>Pengurus RT <?= htmlspecialchars($housing_name) ?></p>
        <p class="fw-bold"><?= htmlspecialchars($periode_text) ?></p>
    </div>
    <div class="report-body">
        <p><strong>Filter Aktif:</strong> Status Pembayaran: <?= htmlspecialchars($status_text) ?>, Pencarian Nama: <?= !empty($search) ? htmlspecialchars($search) : '-' ?></p>
        <table class="table table-bordered table-sm">
            <thead class="table-dark"><tr><th>No.</th><th>Kepala Keluarga</th><th>Alamat</th><th>Status</th><th>Tgl. Bayar</th></tr></thead>
            <tbody>
                <?php if (empty($iuran_data)): ?>
                    <tr><td colspan="5" class="text-center">Tidak ada data ditemukan.</td></tr>
                <?php else: ?>
                    <?php foreach ($iuran_data as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($item['kepala_keluarga'] ?: '(Tidak ada nama)') ?></td>
                            <td><?= htmlspecialchars($item['alamat']) ?></td>
                            <td><?= $item['iuran_id'] ? 'Lunas' : 'Belum Lunas' ?></td>
                            <td><?= $item['tanggal_bayar'] ? date('d-m-Y', strtotime($item['tanggal_bayar'])) : '-' ?></td>
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