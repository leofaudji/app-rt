<?php
$is_included = count(get_included_files()) > 1;
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

<?php if (!$is_included): // Hanya tampilkan header jika file ini tidak di-include ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-bar-chart-line-fill"></i> Statistik Iuran Tahunan</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
<?php endif; ?>
        <div class="me-2">
            <label for="statistik-tahun-filter" class="form-label visually-hidden">Tahun</label>
            <select id="statistik-tahun-filter" class="form-select form-select-sm">
                <!-- Options will be populated by JS -->
            </select>
        </div>
<?php if (!$is_included): ?>
    </div>
</div>
<?php endif; ?>

<div id="statistik-iuran-container">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">Grafik Pemasukan Iuran per Bulan</div>
                <div class="card-body">
                    <canvas id="pemasukan-chart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">Grafik Tingkat Kepatuhan Pembayaran per Bulan (%)</div>
                <div class="card-body">
                    <canvas id="kepatuhan-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center p-5" id="statistik-loading-spinner">
        <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Memuat data statistik...</span>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request && !$is_included) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>