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
    <h1 class="h2"><i class="bi bi-person-vcard"></i> Log Aktivitas Pengguna</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
        <button type="button" class="btn btn-sm btn-outline-danger" id="clear-old-logs-btn">
            <i class="bi bi-trash3-fill"></i> Bersihkan Log > 6 Bulan
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search-log" class="form-control" placeholder="Cari username atau aksi...">
                </div>
            </div>
            <div class="col-auto ms-md-auto">
                <label for="log-limit" class="col-form-label">Tampilkan:</label>
            </div>
            <div class="col-auto">
                <select id="log-limit" class="form-select">
                    <option value="15" selected>15</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">Semua</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive mt-3">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Username</th>
                <th>Aksi</th>
                <th>Detail</th>
                <th>Alamat IP</th>
            </tr>
        </thead>
        <tbody id="log-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
        </tbody>
    </table>
</div>

<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center" id="log-pagination">
        <!-- Pagination controls will be inserted here by JavaScript -->
    </ul>
</nav>

<!-- Modal for Log Details -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logDetailModalLabel">Detail Log Aktivitas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Waktu:</strong> <span id="modal-log-waktu"></span></p>
        <p><strong>Username:</strong> <span id="modal-log-username"></span></p>
        <p><strong>Aksi:</strong> <span id="modal-log-aksi"></span></p>
        <p><strong>Alamat IP:</strong> <span id="modal-log-ip"></span></p>
        <p><strong>Detail Lengkap:</strong></p>
        <pre><code id="modal-log-detail" style="white-space: pre-wrap; word-break: break-all;"></code></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>