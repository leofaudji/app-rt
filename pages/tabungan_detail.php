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

$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$warga_id = end($path_parts);

if (!is_numeric($warga_id)) {
    echo '<div class="alert alert-danger m-3">ID Warga tidak valid.</div>';
    if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; }
    return;
}
?>

<div id="tabungan-detail-container" data-warga-id="<?= htmlspecialchars($warga_id) ?>">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2"><i class="bi bi-person-check-fill"></i> Detail Tabungan Warga</h1>
            <h2 class="h4 text-muted" id="detail-warga-nama">Memuat...</h2>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= base_url('/tabungan') ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <a href="<?= base_url('/tabungan/cetak/' . $warga_id) ?>" target="_blank" class="btn btn-sm btn-outline-success me-2">
                <i class="bi bi-printer-fill"></i> Cetak Buku Tabungan
            </a>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tabunganTxModal">
                <i class="bi bi-plus-circle"></i> Tambah Transaksi
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body text-center">
            <h5 class="card-title">Total Saldo Saat Ini</h5>
            <p class="card-text fs-1 fw-bold text-success" id="detail-saldo-total">Memuat...</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th class="text-end">Jumlah</th>
                    <th>Dicatat Oleh</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabungan-detail-table-body">
                <!-- Data diisi oleh JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Transaksi -->
<div class="modal fade" id="tabunganTxModal" tabindex="-1" aria-labelledby="tabunganTxModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tabunganTxModalLabel">Tambah Transaksi Tabungan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="tabungan-tx-form">
            <input type="hidden" name="warga_id" id="tx-warga-id" value="<?= htmlspecialchars($warga_id) ?>">
            <input type="hidden" name="action" value="add_transaction">
            <div class="mb-3"><label for="tx-tanggal" class="form-label">Tanggal</label><input type="date" class="form-control" id="tx-tanggal" name="tanggal" required></div>
            <div class="mb-3"><label for="tx-jenis" class="form-label">Jenis Transaksi</label><select class="form-select" id="tx-jenis" name="jenis"><option value="setor">Setoran</option><option value="tarik">Penarikan</option></select></div>
            <div class="mb-3">
                <label for="tx-kategori" class="form-label">Kategori</label>
                <select class="form-select" id="tx-kategori" name="kategori_id" required></select>
            </div>
            <div class="mb-3"><label for="tx-jumlah" class="form-label">Jumlah (Rp)</label><input type="number" class="form-control" id="tx-jumlah" name="jumlah" required></div>
            <div class="mb-3"><label for="tx-keterangan" class="form-label">Keterangan (Opsional)</label><input type="text" class="form-control" id="tx-keterangan" name="keterangan"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-tabungan-tx-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; }
?>