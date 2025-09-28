<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    die("Akses ditolak.");
}

$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$warga_id = end($path_parts);

if (empty($warga_id) || !is_numeric($warga_id)) {
    http_response_code(400);
    die("ID Warga tidak valid.");
}

$conn = Database::getInstance()->getConnection();

// Warga can only print their own passbook, admin/bendahara can print anyone's
if ($_SESSION['role'] === 'warga') {
    $stmt_check = $conn->prepare("SELECT id FROM warga WHERE nama_panggilan = ?");
    $stmt_check->bind_param("s", $_SESSION['username']);
    $stmt_check->execute();
    $user_warga_id = $stmt_check->get_result()->fetch_assoc()['id'] ?? 0;
    $stmt_check->close();
    if ($warga_id != $user_warga_id) {
        http_response_code(403);
        die("Akses ditolak. Anda hanya dapat mencetak buku tabungan Anda sendiri.");
    }
}

// Fetch Warga Info
$stmt_warga = $conn->prepare("SELECT w.nama_lengkap, w.no_kk, CONCAT(r.blok, ' / ', r.nomor) as alamat FROM warga w LEFT JOIN rumah r ON w.no_kk = r.no_kk_penghuni WHERE w.id = ?");
$stmt_warga->bind_param("i", $warga_id);
$stmt_warga->execute();
$warga = $stmt_warga->get_result()->fetch_assoc();
$stmt_warga->close();

if (!$warga) {
    http_response_code(404);
    die("Data warga tidak ditemukan.");
}

// Fetch Transactions
$stmt_transaksi = $conn->prepare("
    SELECT t.tanggal, t.jenis, t.jumlah, k.nama_kategori, t.keterangan
    FROM tabungan_warga t
    JOIN tabungan_kategori k ON t.kategori_id = k.id
    WHERE t.warga_id = ?
    ORDER BY t.tanggal ASC, t.created_at ASC
");
$stmt_transaksi->bind_param("i", $warga_id);
$stmt_transaksi->execute();
$transactions = $stmt_transaksi->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_transaksi->close();
$conn->close();

// Helper to format currency
function format_rupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Tabungan - <?= htmlspecialchars($warga['nama_lengkap']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Times New Roman', Times, serif; }
        .passbook-container { max-width: 800px; margin: 2rem auto; background: white; border: 1px solid #dee2e6; border-radius: .5rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
        .passbook-header { text-align: center; padding: 1.5rem; border-bottom: 2px solid #000; }
        .passbook-header h1 { font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem; }
        .passbook-body { padding: 2rem; }
        .passbook-footer { text-align: center; padding: 1.5rem; border-top: 1px solid #dee2e6; font-size: 0.9rem; }
        .print-button-container { position: fixed; bottom: 20px; right: 20px; z-index: 100; }
        .transaction-table th, .transaction-table td { font-size: 0.9rem; vertical-align: middle; }
        @media print {
            body { background-color: white; }
            .print-button-container { display: none; }
            .passbook-container { box-shadow: none; border: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="passbook-container">
    <div class="passbook-header">
        <h1>BUKU TABUNGAN WARGA</h1>
        <p>Nama: <strong><?= htmlspecialchars($warga['nama_lengkap']) ?></strong></p>
        <p>No. KK: <?= htmlspecialchars($warga['no_kk']) ?> | Alamat: <?= htmlspecialchars($warga['alamat']) ?></p>
    </div>
    <div class="passbook-body">
        <table class="table table-striped table-bordered transaction-table">
            <thead class="table-dark">
                <tr>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th class="text-end">Setoran</th>
                    <th class="text-end">Penarikan</th>
                    <th class="text-end">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" class="text-end fw-bold">Saldo Awal</td>
                    <td class="text-end fw-bold"><?= format_rupiah(0) ?></td>
                </tr>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" class="text-center">Belum ada transaksi.</td></tr>
                <?php else:
                    $saldo = 0;
                    foreach ($transactions as $tx):
                        $setoran = $tx['jenis'] == 'setor' ? $tx['jumlah'] : 0;
                        $penarikan = $tx['jenis'] == 'tarik' ? $tx['jumlah'] : 0;
                        $saldo += ($setoran - $penarikan);
                ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($tx['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($tx['keterangan'] ?: $tx['nama_kategori']) ?></td>
                        <td class="text-end"><?= $setoran > 0 ? format_rupiah($setoran) : '-' ?></td>
                        <td class="text-end"><?= $penarikan > 0 ? format_rupiah($penarikan) : '-' ?></td>
                        <td class="text-end fw-bold"><?= format_rupiah($saldo) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="passbook-footer"><small class="text-muted">Dicetak dari Aplikasi RT pada <?= date('d F Y, H:i') ?></small></div>
</div>

<div class="print-button-container"><button class="btn btn-primary btn-lg shadow" onclick="window.print()"><i class="bi bi-printer-fill"></i> Cetak</button></div>

</body>
</html>