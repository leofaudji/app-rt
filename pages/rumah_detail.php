<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// The router captures the ID, but we need to get it from the URL path for SPA.
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$rumah_id = end($path_parts);

if (!is_numeric($rumah_id)) {
    echo '<div class="alert alert-danger m-3">ID Rumah tidak valid.</div>';
    if (!$is_spa_request) {
        require_once PROJECT_ROOT . '/views/footer.php';
    }
    return;
}
?>

<div id="rumah-detail-container" data-rumah-id="<?= htmlspecialchars($rumah_id) ?>">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2"><i class="bi bi-house-heart-fill"></i> Detail Rumah</h1>
            <h2 class="h4 text-muted" id="rumah-detail-alamat"><div class="spinner-border spinner-border-sm"></div></h2>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= base_url('/rumah') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Data Rumah
            </a>
        </div>
    </div>

    <!-- Konten Detail -->
    <div class="row">
        <!-- Informasi Rumah & Penghuni Saat Ini -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Properti & Penghuni Saat Ini</h5>
                </div>
                <div class="card-body" id="rumah-info-content">
                    <div class="text-center p-5"><div class="spinner-border"></div></div>
                </div>
            </div>
        </div>

        <!-- Histori Penghuni -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Histori Penghuni</h5>
                    <button id="print-history-btn" class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="bi bi-printer-fill"></i> Cetak Histori
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Kepala Keluarga</th><th>Tanggal Masuk</th><th>Tanggal Keluar</th></tr></thead>
                            <tbody id="rumah-history-content">
                                <tr><td colspan="3" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>