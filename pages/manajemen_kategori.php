<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    echo '<div class="alert alert-danger m-3">Akses ditolak. Anda harus menjadi Admin atau Bendahara untuk melihat halaman ini.</div>';
    if (!$is_spa_request) {
        require_once PROJECT_ROOT . '/views/footer.php';
    }
    return;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-tags-fill"></i> Manajemen Kategori Kas</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
    </div>
</div>

<div class="row">
    <!-- Kategori Pemasukan -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success-subtle">
                <h5 class="mb-0"><i class="bi bi-arrow-down-circle-fill"></i> Kategori Pemasukan</h5>
            </div>
            <div class="card-body">
                <ul class="list-group mb-3" id="kategori-masuk-list">
                    <!-- List akan diisi oleh JS -->
                </ul>
                <form id="add-kategori-masuk-form" class="input-group">
                    <input type="text" class="form-control" placeholder="Tambah kategori pemasukan baru..." required>
                    <button class="btn btn-success" type="submit">Tambah</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Kategori Pengeluaran -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-danger-subtle">
                <h5 class="mb-0"><i class="bi bi-arrow-up-circle-fill"></i> Kategori Pengeluaran</h5>
            </div>
            <div class="card-body">
                <ul class="list-group mb-3" id="kategori-keluar-list">
                    <!-- List akan diisi oleh JS -->
                </ul>
                <form id="add-kategori-keluar-form" class="input-group">
                    <input type="text" class="form-control" placeholder="Tambah kategori pengeluaran baru..." required>
                    <button class="btn btn-danger" type="submit">Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editKategoriModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="edit-kategori-form">
            <input type="hidden" name="id" id="edit-kategori-id">
            <div class="mb-3">
                <label for="edit-kategori-nama" class="form-label">Nama Kategori</label>
                <input type="text" class="form-control" id="edit-kategori-nama" name="nama_kategori" required>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-edit-kategori-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>