<?php
// This is a standalone page for printing, so we don't use the SPA header/footer.
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// Check for ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("ID Surat tidak valid.");
}

$surat_id = (int)$_GET['id'];
$conn = Database::getInstance()->getConnection();

// Fetch letter details along with citizen data
$query = "
    SELECT 
        s.*, 
        w.nama_lengkap, w.nik, w.pekerjaan, w.alamat, w.tgl_lahir, w.no_kk
    FROM surat_pengantar s
    JOIN warga w ON s.warga_id = w.id
    WHERE s.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $surat_id);
$stmt->execute();
$surat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$surat) {
    http_response_code(404);
    die("Data surat tidak ditemukan.");
}

// Authorization check: user must be admin or the owner of the letter
$is_owner = false;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'warga' && isset($_SESSION['username'])) {
    $stmt_warga = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
    $stmt_warga->bind_param("s", $_SESSION['username']);
    $stmt_warga->execute();
    $warga_session = $stmt_warga->get_result()->fetch_assoc();
    if ($warga_session && $warga_session['id'] == $surat['warga_id']) {
        $is_owner = true;
    }
    $stmt_warga->close();
}

if ((!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') && !$is_owner) {
    http_response_code(403);
    die("Anda tidak memiliki izin untuk mengakses surat ini.");
}

// Check if the letter is approved
if ($surat['status'] !== 'approved') {
    http_response_code(403);
    die("Surat ini belum disetujui dan tidak dapat dicetak.");
}

// Fetch RT settings
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
$app_settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $app_settings[$row['setting_key']] = $row['setting_value'];
}
$housing_name = $app_settings['housing_name'] ?? 'Perumahan Sejahtera';
$letterhead_image_path = $app_settings['letterhead_image'] ?? null;
$signature_image_path = $app_settings['signature_image'] ?? null;
$stamp_image_path = $app_settings['stamp_image'] ?? null;
$rt_head_name = $app_settings['rt_head_name'] ?? 'Nama Ketua RT';

// --- Fetch Template ---
$stmt_template = $conn->prepare("SELECT * FROM surat_templates WHERE nama_template = ?");
$stmt_template->bind_param("s", $surat['jenis_surat']);
$stmt_template->execute();
$template = $stmt_template->get_result()->fetch_assoc();
$stmt_template->close();

if (!$template) {
    // Fallback to default if a specific template is deleted but still referenced.
    $fallback_name = 'Surat Keterangan Domisili';
    $stmt_fallback = $conn->prepare("SELECT * FROM surat_templates WHERE nama_template = ? LIMIT 1");
    $stmt_fallback->bind_param("s", $fallback_name);
    $stmt_fallback->execute();
    $template = $stmt_fallback->get_result()->fetch_assoc();
    $stmt_fallback->close();
    if (!$template) {
        http_response_code(500);
        die("Template surat default ('Surat Keterangan Domisili') tidak ditemukan.");
    }
}

$surat['tgl_lahir_formatted'] = $surat['tgl_lahir'] ? date('d F Y', strtotime($surat['tgl_lahir'])) : '-';

// --- Data Fetching for Specific Templates ---
$data_ayah = null;
$data_ibu = null;

if ($template && $template['requires_parent_data'] == 1 && !empty($surat['no_kk'])) {
    // Fetch Ayah
    $stmt_ayah = $conn->prepare("SELECT * FROM warga WHERE no_kk = ? AND status_dalam_keluarga = 'Kepala Keluarga' LIMIT 1");
    $stmt_ayah->bind_param("s", $surat['no_kk']);
    $stmt_ayah->execute();
    $data_ayah = $stmt_ayah->get_result()->fetch_assoc();
    $stmt_ayah->close();

    // Fetch Ibu
    $stmt_ibu = $conn->prepare("SELECT * FROM warga WHERE no_kk = ? AND status_dalam_keluarga = 'Istri' LIMIT 1");
    $stmt_ibu->bind_param("s", $surat['no_kk']);
    $stmt_ibu->execute();
    $data_ibu = $stmt_ibu->get_result()->fetch_assoc();
    $stmt_ibu->close();
}

// --- Template Rendering ---
$app_context = [
    'housing_name' => $housing_name,
    'rt_head_name' => $rt_head_name,
    'current_date' => date('d F Y')
];

$full_context = [
    'surat' => $surat,
    'app' => $app_context,
    'data_ayah' => $data_ayah,
    'data_ibu' => $data_ibu
];

$rendered_title = render_template($template['judul_surat'], $full_context);
$rendered_content = render_template($template['konten'], $full_context);

$conn->close();

// Check if signature and stamp images exist and create public URLs
$signature_image_url = null;
if ($signature_image_path && file_exists(PROJECT_ROOT . '/' . $signature_image_path)) {
    $signature_image_url = base_url($signature_image_path);
}
$stamp_image_url = null;
if ($stamp_image_path && file_exists(PROJECT_ROOT . '/' . $stamp_image_path)) {
    $stamp_image_url = base_url($stamp_image_path);
}

$letterhead_image_url = null;
if ($letterhead_image_path && file_exists(PROJECT_ROOT . '/' . $letterhead_image_path)) {
    $letterhead_image_url = base_url($letterhead_image_path);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak: <?= htmlspecialchars($rendered_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Times New Roman', Times, serif; }
        .letter-container { 
            max-width: 800px; 
            margin: 2rem auto; 
            background: white; 
            border: 1px solid #dee2e6; 
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
            padding: 3rem;
        }
        .letter-official-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .letter-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 1rem; margin-bottom: 2rem; }
        .letter-header h1 { font-size: 1.5rem; font-weight: bold; text-transform: uppercase; text-decoration: underline; margin-bottom: 0.25rem; }
        .letter-header p { font-size: 1.1rem; margin-bottom: 0; }
        .letter-body { font-size: 1.1rem; line-height: 1.6; text-align: justify; }
        .letter-body .content-block { white-space: pre-wrap; } /* To respect newlines from DB */
        .signature-block {
            width: 40%;
            margin-left: 60%;
            margin-top: 2rem;
        }
        .signature-block .signature-image-container {
            position: relative;
            height: 100px; /* Adjust as needed */
        }
        .signature-block .signature-image {
            position: absolute;
            bottom: 15px;
            left: 20px;
            max-height: 80px;
            z-index: 2;
        }
        .signature-block .stamp-image {
            position: absolute;
            bottom: 0;
            left: 0;
            max-height: 100px;
            opacity: 0.8;
            z-index: 1;
        }
        .print-button-container { position: fixed; bottom: 20px; right: 20px; z-index: 100; }
        @media print {
            body { background-color: white; }
            .print-button-container { display: none; }
            .letter-container { box-shadow: none; border: none; margin: 0; max-width: 100%; padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="letter-container">
    <?php if ($letterhead_image_url): ?>
    <div class="letter-official-header">
        <img src="<?= $letterhead_image_url ?>" alt="Kop Surat" class="img-fluid">
    </div>
    <?php endif; ?>

    <div class="letter-header" <?php if ($letterhead_image_url) echo 'style="border-bottom: none;"'; ?>>
        <h1><?= htmlspecialchars($rendered_title) ?></h1>
        <p>Nomor: <?= htmlspecialchars($surat['nomor_surat'] ?: '.../.../...') ?></p>
    </div>
    
    <div class="letter-body">
        <div class="content-block">
            <?= nl2br(htmlspecialchars($rendered_content)) ?>
        </div>

        <div class="signature-block">
            <p class="mb-2">................, <?= date('d F Y') ?></p>
            <p class="mb-5">Ketua RT <?= htmlspecialchars($housing_name) ?></p>
            
            <div class="signature-image-container">
                <?php if ($stamp_image_url): ?>
                    <img src="<?= $stamp_image_url ?>" alt="Stempel" class="stamp-image">
                <?php endif; ?>
                <?php if ($signature_image_url): ?>
                    <img src="<?= $signature_image_url ?>" alt="Tanda Tangan" class="signature-image">
                <?php endif; ?>
            </div>

            <p class="fw-bold text-decoration-underline mt-2 mb-0">( <?= htmlspecialchars($rt_head_name) ?> )</p>
        </div>
    </div>
</div>

<div class="print-button-container">
    <button class="btn btn-primary btn-lg shadow" onclick="window.print()">
        <i class="bi bi-printer-fill"></i> Cetak Surat
    </button>
</div>

</body>
</html>
?>