<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    echo '<div class="alert alert-danger m-3">Akses ditolak.</div>';
    if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; }
    return;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-piggy-bank-fill"></i> Tabungan Warga</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search-tabungan" class="form-control" placeholder="Cari nama atau No. KK...">
                </div>
            </div>
            <div class="col-md-8 d-flex align-items-center justify-content-end">
                <strong class="me-2">Total Semua Saldo:</strong>
                <span class="fs-5 fw-bold text-success" id="total-semua-saldo">Memuat...</span>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Nama Kepala Keluarga</th>
                <th>No. KK</th>
                <th>Alamat</th>
                <th class="text-end">Total Saldo</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="tabungan-summary-table-body">
            <!-- Data diisi oleh JavaScript -->
        </tbody>
    </table>
</div>

<?php
if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; }
?>