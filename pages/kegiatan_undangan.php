<?php
// This is a standalone page for printing, so we don't use the SPA header/footer.
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check: only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak.");
}

// Check for ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("ID Kegiatan tidak valid.");
}

$kegiatan_id = (int)$_GET['id'];
$conn = Database::getInstance()->getConnection();

// Fetch activity details
$stmt = $conn->prepare("SELECT * FROM kegiatan WHERE id = ?");
$stmt->bind_param("i", $kegiatan_id);
$stmt->execute();
$result = $stmt->get_result();
$kegiatan = $result->fetch_assoc();

if (!$kegiatan) {
    $stmt->close();
    $conn->close();
    http_response_code(404);
    die("Kegiatan tidak ditemukan.");
}

// Fetch app settings
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
$app_settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $app_settings[$row['setting_key']] = $row['setting_value'];
}
$housing_name = $app_settings['housing_name'] ?? 'Perumahan Sejahtera';
$letterhead_image_path = $app_settings['letterhead_image'] ?? null;

$stmt->close();
$conn->close();

$letterhead_image_url = null;
if ($letterhead_image_path && file_exists(PROJECT_ROOT . '/' . $letterhead_image_path)) {
    $letterhead_image_url = base_url($letterhead_image_path);
}

// Format date to Indonesian
$tanggal_kegiatan = new DateTime($kegiatan['tanggal_kegiatan']);
$hari = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
$bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$tanggal_formatted = $hari[$tanggal_kegiatan->format('l')] . ', ' . $tanggal_kegiatan->format('d') . ' ' . $bulan[(int)$tanggal_kegiatan->format('n')] . ' ' . $tanggal_kegiatan->format('Y');
$waktu_formatted = $tanggal_kegiatan->format('H:i') . ' WIB';

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Undangan Kegiatan: <?= htmlspecialchars($kegiatan['judul']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .invitation-container { max-width: 800px; margin: 2rem auto; background: white; border: 1px solid #dee2e6; border-radius: .5rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
        .invitation-official-header {
            text-align: center;
            padding: 2rem 2rem 0;
        }
        .invitation-header { text-align: center; padding: 2rem; border-bottom: 2px solid #000; }
        .invitation-header h1 { font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem; }
        .invitation-header p { font-size: 1.2rem; color: #6c757d; }
        .invitation-body { padding: 2rem; }
        .invitation-body .details { font-size: 1.1rem; }
        .invitation-footer { text-align: center; padding: 1.5rem; border-top: 1px solid #dee2e6; }
        .print-button-container { position: fixed; bottom: 20px; right: 20px; z-index: 100; }
        @media print {
            body { background-color: white; }
            .print-button-container { display: none; }
            .invitation-container { box-shadow: none; border: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="invitation-container">
    <?php if ($letterhead_image_url): ?>
    <div class="invitation-official-header">
        <img src="<?= $letterhead_image_url ?>" alt="Kop Surat" class="img-fluid">
    </div>
    <?php endif; ?>
    <div class="invitation-header" <?php if ($letterhead_image_url) echo 'style="border-bottom: none; padding-top: 1.5rem;"'; ?>>
        <h1>UNDANGAN</h1>
        <p>Pengurus RT <?= htmlspecialchars($housing_name) ?></p>
    </div>
    <div class="invitation-body">
        <p class="text-end mt-4">Kepada Yth.<br>Bapak/Ibu Warga<br>di Tempat</p>
        <br>
        <p>Dengan hormat,</p>
        <p>Sehubungan dengan akan diadakannya kegiatan <strong><?= htmlspecialchars($kegiatan['judul']) ?></strong>, kami mengundang seluruh warga untuk dapat berpartisipasi dalam kegiatan tersebut, yang akan diselenggarakan pada:</p>
        
        <table class="table table-borderless details mt-4 mb-4">
            <tbody>
                <tr>
                    <td style="width: 120px;"><strong>Hari, Tanggal</strong></td>
                    <td>: <?= htmlspecialchars($tanggal_formatted) ?></td>
                </tr>
                <tr>
                    <td><strong>Waktu</strong></td>
                    <td>: <?= htmlspecialchars($waktu_formatted) ?></td>
                </tr>
                <tr>
                    <td><strong>Tempat</strong></td>
                    <td>: <?= htmlspecialchars($kegiatan['lokasi'] ?: 'Sesuai informasi') ?></td>
                </tr>
                <tr>
                    <td class="align-top"><strong>Acara</strong></td>
                    <td class="align-top">: <?= htmlspecialchars($kegiatan['judul']) ?></td>
                </tr>
            </tbody>
        </table>

        <p><?= nl2br(htmlspecialchars($kegiatan['deskripsi'])) ?></p>
        <br>
        <p>Demikian undangan ini kami sampaikan. Atas perhatian dan partisipasi Bapak/Ibu, kami ucapkan terima kasih.</p>
        <br><br>
        <div class="row">
            <div class="col-6"></div>
            <div class="col-6 text-center">
                <p>Hormat kami,</p>
                <br><br><br>
                <p><strong>(Ketua RT)</strong></p>
            </div>
        </div>
    </div>
    <div class="invitation-footer">
        <small class="text-muted">Dicetak dari Aplikasi RT pada <?= date('d-m-Y H:i') ?></small>
    </div>
</div>

<div class="print-button-container">
    <button class="btn btn-primary btn-lg shadow" onclick="window.print()">
        <i class="bi bi-printer-fill"></i> Cetak Undangan
    </button>
</div>

</body>
</html>