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
    <h1 class="h2"><i class="bi bi-person-x-fill"></i> Laporan Tunggakan Iuran</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
    </div>
</div>
<?php endif; ?>


<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search-tunggakan" class="form-control" placeholder="Cari nama kepala keluarga...">
                </div>
            </div>
            <div class="col-auto ms-md-auto">
                <label for="filter-tahun-tunggakan" class="col-form-label">Tahun:</label>
            </div>
            <div class="col-auto">
                <select id="filter-tahun-tunggakan" class="form-select">
                    <!-- Opsi tahun akan diisi oleh JavaScript -->
                </select>
            </div>
            <div class="col-auto">
                <label for="filter-min-tunggakan" class="col-form-label">Min. Tunggakan:</label>
            </div>
            <div class="col-auto">
                <select id="filter-min-tunggakan" class="form-select">
                    <option value="1">>= 1 Bulan</option>
                    <option value="2" selected>>= 2 Bulan</option>
                    <option value="3">>= 3 Bulan</option>
                    <option value="6">>= 6 Bulan</option>
                </select>
            </div>
            <div class="col-auto">
                <div class="btn-group">
                    <button type="button" id="cetak-tunggakan-btn" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer-fill"></i> Cetak</button>
                    <button type="button" id="export-tunggakan-btn" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel-fill"></i> Ekspor</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ringkasan -->
<div class="row mb-3">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Warga Menunggak</h5>
                <p class="card-text fs-2 fw-bold" id="total-warga-menunggak">-</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Potensi Pemasukan</h5>
                <p class="card-text fs-2 fw-bold text-danger" id="total-potensi-pemasukan">-</p>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Data Tunggakan -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr><th>No.</th><th>Nama Kepala Keluarga</th><th>Alamat</th><th>Jumlah Bulan Menunggak</th><th>Total Tunggakan</th><th class="text-end">Aksi</th></tr>
        </thead>
        <tbody id="tunggakan-table-body">
            <tr><td colspan="6" class="text-center p-5"><div class="spinner-border"></div></td></tr>
        </tbody>
    </table>
</div>

<?php
if (!$is_spa_request && !$is_included) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>