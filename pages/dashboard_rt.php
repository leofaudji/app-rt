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
</div>

<div class="row">
    <!-- Card: Jumlah Warga -->
    <div class="col-sm-6 col-lg-4 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Jumlah Warga</h5>
                        <h2 class="fw-bold" id="total-warga-widget"><div class="spinner-border spinner-border-sm" role="status"></div></h2>
                    </div>
                    <i class="bi bi-people-fill fs-1 opacity-50"></i>
                </div>
                <div class="card-footer bg-transparent border-0 p-0 text-end">
                    <a href="<?= base_url('/warga') ?>" class="text-white stretched-link small">Lihat Detail <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Saldo Kas -->
    <div class="col-sm-6 col-lg-4 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Saldo Kas RT</h5>
                        <h2 class="fw-bold" id="saldo-kas-widget"><div class="spinner-border spinner-border-sm" role="status"></div></h2>
                    </div>
                    <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                </div>
                <div class="card-footer bg-transparent border-0 p-0 text-end">
                    <a href="<?= base_url('/keuangan') ?>" class="text-white stretched-link small">Lihat Detail <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Ringkasan Iuran -->
    <?php if (in_array($_SESSION['role'], ['admin', 'bendahara'])): ?>
    <div class="col-sm-12 col-lg-4 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Iuran Bulan Ini</h5>
                        <div id="iuran-summary-widget"><div class="spinner-border spinner-border-sm" role="status"></div></div>
                    </div>
                    <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                </div>
                <div class="progress mt-2" role="progressbar" style="height: 5px;">
                  <div id="iuran-progress-bar" class="progress-bar bg-white" style="width: 0%"></div>
                </div>
                 <div class="card-footer bg-transparent border-0 p-0 text-end mt-2">
                    <a href="<?= base_url('/iuran') ?>" class="text-white stretched-link small">Lihat Detail <i class="bi bi-arrow-right-circle"></i></a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cards for Jajak Pendapat and Total Pengumuman are removed as they are not supported by the API handler -->
</div>

<div class="row">
    <!-- Chart: Ringkasan Status Rumah -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-pie-chart-fill"></i> Ringkasan Status Rumah</h5>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <div style="position: relative; height:280px; width:100%">
                    <canvas id="rumah-status-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <!-- Chart: Demografi Warga -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-bar-chart-line-fill"></i> Demografi Warga</h5>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <div style="position: relative; height:280px; width:100%">
                    <canvas id="demographics-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
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
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-graph-up-arrow"></i> Tren Saldo Kas (6 Bulan Terakhir)</h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height:300px; width:100%">
                    <canvas id="saldo-trend-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Widget: Tugas Administratif -->
    <?php if (in_array($_SESSION['role'], ['admin', 'bendahara'])): ?>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-clipboard-check-fill"></i> Tugas Administratif</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush" id="admin-tasks-widget">
                    <div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- Widget: Pengumuman Terbaru -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-megaphone-fill"></i> Pengumuman Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush" id="latest-announcements-widget">
                    <div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="<?= base_url('/pengumuman') ?>" class="btn btn-outline-primary btn-sm w-100">Lihat Semua Pengumuman</a>
            </div>
        </div>
    </div>
    <!-- Widget: Kegiatan Akan Datang -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-calendar-event-fill"></i> Kegiatan Akan Datang</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush" id="upcoming-activities-widget">
                    <div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="<?= base_url('/kegiatan') ?>" class="btn btn-outline-primary btn-sm w-100">Lihat Semua Kegiatan</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Widget: Warga Baru -->
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
    <!-- Widget: Ulang Tahun Bulan Ini -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-gift-fill"></i> Ulang Tahun Bulan Ini</h5>
            </div>
            <div class="card-body" style="overflow-y: auto; max-height: 250px;">
                <ul class="list-group list-group-flush" id="birthday-widget-list">
                    <li class="list-group-item text-center"><div class="spinner-border spinner-border-sm"></div></li>
                </ul>
            </div>
        </div>
    </div>
    <!-- Widget: Log Keuangan Terbaru -->
    <?php if (in_array($_SESSION['role'], ['admin', 'bendahara'])): ?>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-receipt-cutoff"></i> Log Keuangan Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush" id="recent-transactions-widget">
                    <div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
             <div class="card-footer text-center">
                <a href="<?= base_url('/keuangan') ?>" class="btn btn-outline-primary btn-sm w-100">Lihat Semua Transaksi</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Hanya muat footer jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>