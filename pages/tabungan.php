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
    <h1 class="h2"><i class="bi bi-piggy-bank-fill"></i> Tabungan Warga</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
        <button class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#tabunganTxGlobalModal">
            <i class="bi bi-plus-circle-fill"></i> Tambah Transaksi
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search-tabungan" class="form-control" placeholder="Cari nama atau No. KK...">
                </div>
            </div>
            <div class="col-md-8 d-flex align-items-center justify-content-end">
                <strong class="me-2">Total Semua Saldo:</strong>
                <span class="fs-5 fw-bold text-success" id="total-semua-saldo">Memuat...</span>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Nama Kepala Keluarga</th>
                <th>No. KK</th>
                <th>Alamat</th>
                <th class="text-end">Total Saldo</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="tabungan-summary-table-body">
            <!-- Data diisi oleh JavaScript -->
        </tbody>
    </table>
</div>

<!-- Modal Tambah Transaksi Global -->
<div class="modal fade" id="tabunganTxGlobalModal" tabindex="-1" aria-labelledby="tabunganTxGlobalModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="tabunganTxGlobalModalLabel">Tambah Transaksi Tabungan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="tabungan-tx-global-form">
            <input type="hidden" name="action" value="add_transaction">
            <div class="mb-3">
                <label for="tx-global-warga" class="form-label">Pilih Warga</label>
                <select class="form-select" id="tx-global-warga" name="warga_id" required></select>
            </div>
            <div class="mb-3"><label for="tx-global-tanggal" class="form-label">Tanggal</label><input type="date" class="form-control" id="tx-global-tanggal" name="tanggal" required></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="tx-global-jenis" class="form-label">Jenis</label><select class="form-select" id="tx-global-jenis" name="jenis"><option value="setor">Setor</option><option value="tarik">Tarik</option></select></div>
                <div class="col-md-6 mb-3"><label for="tx-global-kategori" class="form-label">Kategori</label><select class="form-select" id="tx-global-kategori" name="kategori_id" required></select></div>
            </div>
            <div class="mb-3"><label for="tx-global-goal" class="form-label">Alokasikan ke Target (Opsional)</label><select class="form-select" id="tx-global-goal" name="goal_id" disabled><option value="">-- Pilih Warga Terlebih Dahulu --</option></select></div>
            <div class="mb-3"><label for="tx-global-jumlah" class="form-label">Jumlah (Rp)</label><input type="number" class="form-control" id="tx-global-jumlah" name="jumlah" required></div>
            <div class="mb-3"><label for="tx-global-keterangan" class="form-label">Keterangan</label><input type="text" class="form-control" id="tx-global-keterangan" name="keterangan"></div>
        </form>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="save-tabungan-tx-global-btn">Simpan Transaksi</button></div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; }
?>