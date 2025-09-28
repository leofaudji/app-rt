<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div id="tabungan-saya-container">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-piggy-bank"></i> Tabungan Saya</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="#" id="cetak-tabungan-saya-btn" target="_blank" class="btn btn-sm btn-outline-success disabled">
                <i class="bi bi-printer-fill"></i> Cetak Buku Tabungan
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body text-center">
            <h5 class="card-title">Total Saldo Anda</h5>
            <p class="card-text fs-1 fw-bold text-success" id="saldo-saya-total">Memuat...</p>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Target Tabungan Saya</h4>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#goalModal" data-action="add">
            <i class="bi bi-plus-circle-fill"></i> Tambah Target
        </button>
    </div>

    <div class="row" id="savings-goals-container">
        <!-- Target tabungan akan dimuat di sini oleh JavaScript -->
        <div class="col-12 text-center p-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Riwayat Transaksi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody id="tabungan-saya-table-body">
                        <!-- Data diisi oleh JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Target Tabungan -->
<div class="modal fade" id="goalModal" tabindex="-1" aria-labelledby="goalModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="goalModalLabel">Tambah Target Tabungan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="goal-form">
            <input type="hidden" name="id" id="goal-id">
            <input type="hidden" name="action" id="goal-action" value="add_goal">
            <div class="mb-3">
                <label for="nama_goal" class="form-label">Nama Target</label>
                <input type="text" class="form-control" id="nama_goal" name="nama_goal" placeholder="cth: Dana Pendidikan Anak" required>
            </div>
            <div class="mb-3"><label for="target_jumlah" class="form-label">Jumlah Target (Rp)</label><input type="number" class="form-control" id="target_jumlah" name="target_jumlah" required></div>
            <div class="mb-3"><label for="tanggal_target" class="form-label">Tanggal Target (Opsional)</label><input type="date" class="form-control" id="tanggal_target" name="tanggal_target"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-goal-btn">Simpan Target</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>