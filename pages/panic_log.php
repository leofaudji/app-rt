<?php
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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-exclamation-octagon-fill"></i> Log Tombol Panik</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Nama Warga</th>
                <th>Alamat</th>
                <th>No. Telepon</th>
                <th>Alamat IP</th>
            </tr>
        </thead>
        <tbody id="panic-log-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>
        </tbody>
    </table>
</div>

<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center" id="panic-log-pagination">
        <!-- Pagination controls will be inserted here by JavaScript -->
    </ul>
</nav>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>