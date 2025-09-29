<?php
// Cek apakah ini permintaan dari SPA via AJAX
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';

// Hanya muat header jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="d-flex align-items-center">
            <div class="me-2">
                <label for="dashboard-bulan-filter" class="form-label visually-hidden">Bulan</label>
                <select id="dashboard-bulan-filter" class="form-select form-select-sm">
                    <!-- Options will be populated by JS -->
                </select>
            </div>
            <div class="me-2">
                <label for="dashboard-tahun-filter" class="form-label visually-hidden">Tahun</label>
                <select id="dashboard-tahun-filter" class="form-select form-select-sm">
                    <!-- Options will be populated by JS -->
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Baris Khusus Admin & Bendahara -->
<?php if (in_array($_SESSION['role'], ['admin', 'bendahara'])): ?>
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card text-white bg-success h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Kas Bersih RT</h5>
                <h2 class="fw-bold" id="posisi-kas-bersih-widget"><div class="spinner-border spinner-border-sm"></div></h2>
                <a href="<?= base_url('/neraca') ?>" class="text-white stretched-link small">Lihat Neraca <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card text-white bg-info h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Iuran Bulan Ini</h5>
                <div id="iuran-summary-widget"><h2 class="fw-bold"><div class="spinner-border spinner-border-sm"></div></h2></div>
                <div class="progress mt-2" role="progressbar" style="height: 5px;">
                  <div id="iuran-progress-bar" class="progress-bar bg-white" style="width: 0%"></div>
                </div>
                <a href="<?= base_url('/iuran') ?>" class="text-white stretched-link small mt-2 d-block">Lihat Detail <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-clipboard-check-fill"></i> Tugas Administratif</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="admin-tasks-widget">
                    <div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0"><i class="bi bi-person-x-fill"></i> Warga Menunggak Iuran (>2 Bulan)</h5>
            </div>
            <div class="card-body p-0" style="overflow-y: auto; max-height: 250px;">
                <ul class="list-group list-group-flush" id="iuran-menunggak-widget">
                    <li class="list-group-item text-center"><div class="spinner-border spinner-border-sm"></div></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Baris Informasi Umum -->
<div class="row">
    <!-- Widget: Pengumuman Terbaru -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-megaphone-fill"></i> <b>Pengumuman & Kegiatan</b></h5>
                <a href="<?= base_url('/pengumuman') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <h6>Pengumuman Terbaru</h6>
                <div class="list-group list-group-flush mb-3" id="latest-announcements-widget">
                    <div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
                <h5 class="mt-3"><i class="bi bi-calendar-event-fill me-2"></i><b>Kegiatan Akan Datang</b></h5>
                <div class="list-group list-group-flush" id="upcoming-activities-widget">
                    <div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-person-plus-fill"></i> Selamat Datang Warga Baru!</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush" id="new-residents-widget">
                    <div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Baris Khusus Warga -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'warga'): ?>
<h4 class="mb-3 mt-4">Ringkasan Saya</h4>
<div class="row">
    <!-- Card: Saldo Tabungan -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-success text-white h-100 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle">Saldo Tabungan Saya</h6>
                <h2 class="fw-bold" id="saldo-tabungan-widget"><div class="spinner-border spinner-border-sm"></div></h2>
                <div style="position: relative; height:60px; width:100%"><canvas id="tabungan-trend-mini-chart"></canvas></div>
                <a href="<?= base_url('/tabungan-saya') ?>" class="small stretched-link text-white">Lihat Detail Tabungan Saya</a>
            </div>
        </div>
    </div>
    <!-- Widget: Target Tabungan -->
    <div class="col-xl-9 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-bullseye"></i> Target Tabungan Saya</h5>
                <a href="<?= base_url('/tabungan-saya') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-circle"></i> Kelola Target</a>
            </div>
            <div class="card-body">
                <div id="savings-goals-widget" class="row"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<h4 class="mb-3 mt-4">Data & Analisis RT</h4>
<div class="row">
    <!-- Card: Jumlah Warga -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card text-white bg-primary h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Jumlah Warga</h5>
                <h2 class="fw-bold" id="total-warga-widget"><div class="spinner-border spinner-border-sm"></div></h2>
                <a href="<?= base_url('/warga') ?>" class="text-white stretched-link small">Lihat Detail <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
    </div>
    <!-- Card: Saldo Kas -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Saldo & Tren Kas RT</h6>
                <h4 class="fw-bold" id="saldo-kas-widget"><div class="spinner-border spinner-border-sm"></div></h4>
                <div style="position: relative; height:60px; width:100%"><canvas id="saldo-trend-mini-chart"></canvas></div>
                <a href="<?= base_url('/keuangan') ?>" class="small stretched-link">Lihat Detail Kas</a>
            </div>
        </div>
    </div>
    <!-- Chart: Ringkasan Status Rumah -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0"><i class="bi bi-pie-chart-fill"></i> Status Rumah</h5></div>
            <div class="card-body d-flex justify-content-center align-items-center"><div style="position: relative; height:150px; width:100%"><canvas id="rumah-status-chart"></canvas></div></div>
        </div>
    </div>
    <!-- Chart: Demografi Warga -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0"><i class="bi bi-bar-chart-line-fill"></i> Demografi Warga</h5></div>
            <div class="card-body d-flex justify-content-center align-items-center"><div style="position: relative; height:150px; width:100%"><canvas id="demographics-chart"></canvas></div></div>
        </div>
    </div>
</div>

<?php if (in_array($_SESSION['role'], ['admin', 'bendahara'])): ?>
<div class="row">
    <!-- Chart: Pemasukan vs Pengeluaran Bulan Ini -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-wallet2"></i> Kas Bulan Ini</h5>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <div style="position: relative; height:280px; width:100%">
                    <canvas id="kas-monthly-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <!-- Widget: Ulang Tahun Bulan Ini -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-gift-fill"></i> Ulang Tahun Bulan Ini</h5>
            </div>
            <div class="card-body p-0" style="overflow-y: auto; max-height: 250px;">
                <ul class="list-group list-group-flush" id="birthday-widget-list">
                    <li class="list-group-item text-center"><div class="spinner-border spinner-border-sm"></div></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Hanya muat footer jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>