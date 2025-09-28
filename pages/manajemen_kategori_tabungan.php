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
    <h1 class="h2"><i class="bi bi-bookmark-star-fill"></i> Manajemen Kategori Tabungan</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
    </div>
</div>

<div class="row" id="kategori-tabungan-tables-container">
    <!-- Kategori Setoran -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success-subtle">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-arrow-down-circle-fill"></i> Kategori Setoran</h5>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#kategoriTabunganModal" data-jenis="setor">
                        <i class="bi bi-plus-circle-fill"></i> Tambah
                    </button>
                </div>
            </div>
            <div class="table-responsive"><table class="table table-striped table-hover mb-0"><tbody id="kategori-setor-table-body"></tbody></table></div>
        </div>
    </div>

    <!-- Kategori Penarikan -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-danger-subtle">
                 <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-arrow-up-circle-fill"></i> Kategori Penarikan</h5>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#kategoriTabunganModal" data-jenis="tarik">
                        <i class="bi bi-plus-circle-fill"></i> Tambah
                    </button>
                </div>
            </div>
            <div class="table-responsive"><table class="table table-striped table-hover mb-0"><tbody id="kategori-tarik-table-body"></tbody></table></div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Kategori -->
<div class="modal fade" id="kategoriTabunganModal" tabindex="-1" aria-labelledby="kategoriTabunganModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="kategoriTabunganModalLabel">Tambah Kategori Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="kategori-tabungan-form">
            <input type="hidden" name="id" id="kategori-id">
            <input type="hidden" name="action" id="kategori-action" value="add">
            <input type="hidden" name="jenis" id="kategori-jenis">
            <div class="mb-3"><label for="nama_kategori" class="form-label">Nama Kategori</label><input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-kategori-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; }
?>