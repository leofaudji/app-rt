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
    <h1 class="h2"><i class="bi bi-balance-scale"></i> Neraca Keuangan RT</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                Posisi Keuangan Saat Ini
            </div>
            <div class="card-body p-4">
                <div class="row mb-3">
                    <div class="col">Aset Lancar (Kas di Tangan)</div>
                    <div class="col text-end fs-5 fw-bold" id="neraca-kas"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
                <div class="row mb-3">
                    <div class="col">Liabilitas (Total Dana Titipan Warga)</div>
                    <div class="col text-end fs-5 fw-bold text-danger" id="neraca-tabungan"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col"><strong>Posisi Dana Bersih (Ekuitas)</strong></div>
                    <div class="col text-end fs-4 fw-bolder text-success" id="neraca-bersih"><div class="spinner-border spinner-border-sm"></div></div>
                </div>
            </div>
            <div class="card-footer text-muted small">
                <p class="mb-1"><strong>Aset Lancar:</strong> Total uang tunai yang saat ini tercatat di dalam Kas RT.</p>
                <p class="mb-1"><strong>Liabilitas:</strong> Total dana milik warga yang dititipkan melalui program Tabungan Warga. Dana ini bukan milik RT dan harus dapat dikembalikan.</p>
                <p class="mb-0"><strong>Posisi Dana Bersih:</strong> Selisih antara Aset dan Liabilitas. Ini adalah jumlah dana yang sesungguhnya dimiliki oleh kas RT.</p>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>