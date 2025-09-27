<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    echo '<div class="alert alert-danger m-3">Akses ditolak. Anda harus menjadi Admin atau Bendahara untuk melihat halaman ini.</div>';
    if (!$is_spa_request) {
        require_once PROJECT_ROOT . '/views/footer.php';
    }
    return; // Stop rendering
}

$role = $_SESSION['role'];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-graph-up-arrow"></i> Laporan Terpadu</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
    </div>
</div>

<ul class="nav nav-tabs" id="laporanTerpaduTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="laporan-keuangan-tab" data-bs-toggle="tab" data-bs-target="#laporan-keuangan-pane" type="button" role="tab">Laporan Keuangan</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="laporan-tunggakan-tab" data-bs-toggle="tab" data-bs-target="#laporan-tunggakan-pane" type="button" role="tab">Tunggakan Iuran</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="laporan-statistik-tab" data-bs-toggle="tab" data-bs-target="#laporan-statistik-pane" type="button" role="tab">Statistik Iuran</button>
    </li>
    <?php if ($role === 'admin'): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="laporan-surat-tab" data-bs-toggle="tab" data-bs-target="#laporan-surat-pane" type="button" role="tab">Laporan Surat</button>
    </li>
    <?php endif; ?>
</ul>

<div class="tab-content" id="laporanTerpaduTabContent">
    <div class="tab-pane fade show active" id="laporan-keuangan-pane" role="tabpanel">
        <div class="card card-tab"><div class="card-body"><?php include 'laporan_keuangan.php'; ?></div></div>
    </div>
    <div class="tab-pane fade" id="laporan-tunggakan-pane" role="tabpanel">
        <div class="card card-tab"><div class="card-body"><?php include 'laporan_iuran.php'; ?></div></div>
    </div>
    <div class="tab-pane fade" id="laporan-statistik-pane" role="tabpanel">
        <div class="card card-tab"><div class="card-body"><?php include 'laporan_iuran_statistik.php'; ?></div></div>
    </div>
    <?php if ($role === 'admin'): ?>
    <div class="tab-pane fade" id="laporan-surat-pane" role="tabpanel">
        <div class="card card-tab"><div class="card-body"><?php include 'laporan_surat.php'; ?></div></div>
    </div>
    <?php endif; ?>
</div>


<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>