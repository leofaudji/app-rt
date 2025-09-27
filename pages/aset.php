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
    <h1 class="h2"><i class="bi bi-box-seam-fill"></i> Inventaris Aset RT</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#asetModal" data-action="add">
            <i class="bi bi-plus-circle"></i> Tambah Aset
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Nama Aset</th>
                <th>Jumlah</th>
                <th>Kondisi</th>
                <th>Lokasi Simpan</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="aset-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal untuk Tambah/Edit Aset -->
<div class="modal fade" id="asetModal" tabindex="-1" aria-labelledby="asetModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="asetModalLabel">Tambah Aset Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="aset-form">
            <input type="hidden" name="id" id="aset-id">
            <input type="hidden" name="action" id="aset-action">
            <div class="mb-3"><label for="nama_aset" class="form-label">Nama Aset</label><input type="text" class="form-control" id="nama_aset" name="nama_aset" required></div>
            <div class="mb-3"><label for="jumlah_aset" class="form-label">Jumlah</label><input type="number" class="form-control" id="jumlah_aset" name="jumlah" value="1" required></div>
            <div class="mb-3"><label for="kondisi_aset" class="form-label">Kondisi</label><select class="form-select" id="kondisi_aset" name="kondisi"><option value="Baik">Baik</option><option value="Rusak Ringan">Rusak Ringan</option><option value="Rusak Berat">Rusak Berat</option></select></div>
            <div class="mb-3"><label for="lokasi_simpan" class="form-label">Lokasi Penyimpanan</label><input type="text" class="form-control" id="lokasi_simpan" name="lokasi_simpan"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-aset-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>