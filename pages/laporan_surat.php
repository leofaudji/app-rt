<?php
$is_included = count(get_included_files()) > 1;
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-3">Akses ditolak. Anda harus menjadi Admin untuk melihat halaman ini.</div>';
    if (!$is_spa_request) {
        require_once PROJECT_ROOT . '/views/footer.php';
    }
    return; // Stop rendering
}
?>

<?php if (!$is_included): // Hanya tampilkan header jika file ini tidak di-include ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan Surat Pengantar</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
<?php endif; ?>
        <div class="d-flex align-items-center">
            <div class="me-3">
                <label for="laporan-tipe-filter" class="form-label visually-hidden">Tipe Laporan</label>
                <select id="laporan-tipe-filter" class="form-select form-select-sm">
                    <option value="bulanan">Laporan Bulanan</option>
                    <option value="tahunan">Laporan Tahunan</option>
                </select>
            </div>
            <div class="me-3" id="laporan-bulan-filter-container">
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
            <div class="me-2">
                <label for="laporan-status-filter" class="form-label visually-hidden">Status</label>
                <select id="laporan-status-filter" class="form-select form-select-sm">
                    <option value="semua">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Ekspor
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" id="export-surat-pdf-btn"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Ekspor ke PDF</a></li>
                    <li><a class="dropdown-item" href="#" id="export-surat-excel-btn"><i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Ekspor ke Excel</a></li>
                </ul>
            </div>
        </div>
<?php if (!$is_included): ?>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Total Surat Diajukan</h5>
                <p class="card-text fs-1 fw-bold" id="total-surat-summary">-</p>
            </div>
        </div>
    </div>
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">Grafik Jenis Surat</div>
            <div class="card-body" style="min-height: 250px; position: relative;">
                <canvas id="surat-report-chart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">Rincian Berdasarkan Jenis Surat</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Jenis Surat</th><th class="text-end">Jumlah</th></tr></thead>
                        <tbody id="laporan-surat-table-body">
                            <!-- Data will be populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
if (!$is_spa_request && !$is_included) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>