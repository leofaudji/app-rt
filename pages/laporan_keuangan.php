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
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-graph-up"></i> Laporan Keuangan</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="me-2">
            <label for="laporan-bulan-filter" class="form-label visually-hidden">Bulan</label>
            <select id="laporan-bulan-filter" class="form-select form-select-sm">
                <!-- Options will be populated by JS -->
            </select>
        </div>
        <div class="me-2">
            <label for="laporan-tahun-filter" class="form-label visually-hidden">Tahun</label>
            <select id="laporan-tahun-filter" class="form-select form-select-sm">
                <!-- Options will be populated by JS -->
            </select>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="cetak-laporan-keuangan-btn">
            <i class="bi bi-printer-fill"></i> Cetak Laporan Bulanan
        </button>
    </div>
</div>

<div class="row mb-4" id="monthly-summary-details-container">
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Saldo Awal Bulan</h6>
                <p class="card-text fs-4 fw-bold" id="summary-saldo-awal">-</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Total Pemasukan</h6>
                <p class="card-text fs-4 fw-bold text-success" id="summary-pemasukan">-</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Total Pengeluaran</h6>
                <p class="card-text fs-4 fw-bold text-danger" id="summary-pengeluaran">-</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card bg-body-tertiary">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Saldo Akhir Bulan</h6>
                <p class="card-text fs-4 fw-bold" id="summary-saldo-akhir">-</p>
            </div>
        </div>
    </div>
</div>

<div id="laporan-keuangan-container">
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">Ringkasan Bulanan</div>
                <div class="card-body">
                    <canvas id="monthly-summary-chart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">Kategori Pengeluaran</div>
                <div class="card-body">
                    <canvas id="expense-category-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center p-5" id="laporan-loading-spinner">
        <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Memuat data laporan...</span>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>