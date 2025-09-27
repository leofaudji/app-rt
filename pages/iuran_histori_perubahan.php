<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-cash-stack"></i> Histori Perubahan Nominal Iuran</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/settings') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Pengaturan
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nominal Iuran</th>
                        <th>Mulai Berlaku</th>
                        <th>Berakhir Pada</th>
                        <th>Diperbarui Oleh</th>
                        <th>Tanggal Diperbarui</th>
                    </tr>
                </thead>
                <tbody id="fee-history-table-body">
                    <tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>