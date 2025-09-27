<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$term = $_GET['term'] ?? '';

if (strlen($term) < 3) {
    echo json_encode(['status' => 'success', 'data' => []]);
    exit;
}

$like_term = "%{$term}%";
$results = [];

try {
    // 1. Search Warga
    $stmt_warga = $conn->prepare("
        SELECT id, nama_lengkap, nik, alamat, 'warga' as type,
        (
            (CASE WHEN nama_lengkap LIKE ? THEN 10 ELSE 0 END) +
            (CASE WHEN nik LIKE ? THEN 5 ELSE 0 END)
        ) as score
        FROM warga 
        WHERE nama_lengkap LIKE ? OR nik LIKE ? OR alamat LIKE ?
    ");
    $stmt_warga->bind_param("sssss", $like_term, $like_term, $like_term, $like_term, $like_term);
    $stmt_warga->execute();
    $res_warga = $stmt_warga->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($res_warga as $item) {
        $results[] = ['type' => 'Warga', 'title' => $item['nama_lengkap'], 'subtitle' => "NIK: {$item['nik']}", 'link' => '/warga/profil/' . $item['id'], 'icon' => 'bi-person-fill', 'score' => $item['score']];
    }
    $stmt_warga->close();

    // 2. Search Kegiatan
    $stmt_kegiatan = $conn->prepare("
        SELECT id, judul, deskripsi, tanggal_kegiatan, 'kegiatan' as type,
        (CASE WHEN judul LIKE ? THEN 10 ELSE 2 END) as score
        FROM kegiatan 
        WHERE judul LIKE ? OR deskripsi LIKE ?
    ");
    $stmt_kegiatan->bind_param("sss", $like_term, $like_term, $like_term);
    $stmt_kegiatan->execute();
    $res_kegiatan = $stmt_kegiatan->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($res_kegiatan as $item) {
        $results[] = ['type' => 'Kegiatan', 'title' => $item['judul'], 'subtitle' => date('d M Y', strtotime($item['tanggal_kegiatan'])), 'link' => '/kegiatan#kegiatan-' . $item['id'], 'icon' => 'bi-calendar-event-fill', 'score' => $item['score']];
    }
    $stmt_kegiatan->close();

    // 3. Search Pengumuman
    $stmt_pengumuman = $conn->prepare("
        SELECT id, judul, isi_pengumuman, 'pengumuman' as type,
        (CASE WHEN judul LIKE ? THEN 10 ELSE 2 END) as score
        FROM pengumuman 
        WHERE (judul LIKE ? OR isi_pengumuman LIKE ?) AND (tanggal_terbit IS NULL OR tanggal_terbit <= NOW())
    ");
    $stmt_pengumuman->bind_param("sss", $like_term, $like_term, $like_term);
    $stmt_pengumuman->execute();
    $res_pengumuman = $stmt_pengumuman->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($res_pengumuman as $item) {
        $results[] = ['type' => 'Pengumuman', 'title' => $item['judul'], 'subtitle' => substr($item['isi_pengumuman'], 0, 50) . '...', 'link' => '/pengumuman#pengumuman-' . $item['id'], 'icon' => 'bi-megaphone-fill', 'score' => $item['score']];
    }
    $stmt_pengumuman->close();

    // 4. Search Dokumen
    $stmt_dokumen = $conn->prepare("SELECT id, nama_dokumen, kategori, 'dokumen' as type, (CASE WHEN nama_dokumen LIKE ? THEN 10 ELSE 5 END) as score FROM dokumen WHERE nama_dokumen LIKE ? OR kategori LIKE ?");
    $stmt_dokumen->bind_param("sss", $like_term, $like_term, $like_term);
    $stmt_dokumen->execute();
    $res_dokumen = $stmt_dokumen->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($res_dokumen as $item) {
        $results[] = ['type' => 'Dokumen', 'title' => $item['nama_dokumen'], 'subtitle' => "Kategori: {$item['kategori']}", 'link' => '/dokumen#dokumen-' . $item['id'], 'icon' => 'bi-folder-fill', 'score' => $item['score']];
    }
    $stmt_dokumen->close();

    // 5. Search Aset
    $stmt_aset = $conn->prepare("
        SELECT id, nama_aset, kondisi, lokasi_simpan, 'aset' as type,
        (CASE WHEN nama_aset LIKE ? THEN 10 ELSE 5 END) as score
        FROM aset_rt 
        WHERE nama_aset LIKE ? OR lokasi_simpan LIKE ?
    ");
    $stmt_aset->bind_param("sss", $like_term, $like_term, $like_term);
    $stmt_aset->execute();
    $res_aset = $stmt_aset->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($res_aset as $item) {
        $results[] = ['type' => 'Aset', 'title' => $item['nama_aset'], 'subtitle' => "Kondisi: {$item['kondisi']}, Lokasi: {$item['lokasi_simpan']}", 'link' => '/aset#aset-' . $item['id'], 'icon' => 'bi-box-seam-fill', 'score' => $item['score']];
    }
    $stmt_aset->close();

    // 6. Search Laporan Warga
    $stmt_laporan = $conn->prepare("
        SELECT l.id, l.kategori, l.deskripsi, l.status, w.nama_lengkap as pelapor, 'laporan' as type,
        (
            (CASE WHEN w.nama_lengkap LIKE ? THEN 5 ELSE 0 END) +
            (CASE WHEN l.kategori LIKE ? THEN 5 ELSE 0 END) +
            (CASE WHEN l.deskripsi LIKE ? THEN 2 ELSE 0 END)
        ) as score
        FROM laporan_warga l JOIN warga w ON l.warga_pelapor_id = w.id
        WHERE l.deskripsi LIKE ? OR l.kategori LIKE ? OR w.nama_lengkap LIKE ?
    ");
    $stmt_laporan->bind_param("ssssss", $like_term, $like_term, $like_term, $like_term, $like_term, $like_term);
    $stmt_laporan->execute();
    $res_laporan = $stmt_laporan->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($res_laporan as $item) {
        $results[] = ['type' => 'Laporan Warga', 'title' => "Laporan: {$item['kategori']}", 'subtitle' => "Oleh: {$item['pelapor']} - Status: {$item['status']}", 'link' => '/laporan#laporan-' . $item['id'], 'icon' => 'bi-flag-fill', 'score' => $item['score']];
    }
    $stmt_laporan->close();

    // Sort results by score descending
    usort($results, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    // Limit the final results
    $final_results = array_slice($results, 0, 10);

    echo json_encode(['status' => 'success', 'data' => $final_results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error: ' . $e->getMessage()]);
}

$conn->close();