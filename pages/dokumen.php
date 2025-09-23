<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-folder-fill"></i> Repositori Dokumen</h1>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#dokumenModal">
            <i class="bi bi-plus-circle"></i> Unggah Dokumen
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Nama Dokumen</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Tanggal Unggah</th>
                <th>Diunggah Oleh</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="dokumen-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="6" class="text-center p-5"><div class="spinner-border"></div></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal untuk Unggah Dokumen (hanya untuk admin) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="dokumenModal" tabindex="-1" aria-labelledby="dokumenModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dokumenModalLabel">Unggah Dokumen Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="dokumen-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="mb-3">
                <label for="nama_dokumen" class="form-label">Nama Dokumen</label>
                <input type="text" class="form-control" id="nama_dokumen" name="nama_dokumen" required>
            </div>
            <div class="mb-3">
                <label for="kategori_dokumen" class="form-label">Kategori</label>
                <select class="form-select" id="kategori_dokumen" name="kategori">
                    <option value="Notulensi Rapat">Notulensi Rapat</option>
                    <option value="Surat Edaran">Surat Edaran</option>
                    <option value="Peraturan Lingkungan">Peraturan Lingkungan</option>
                    <option value="Laporan Keuangan">Laporan Keuangan</option>
                    <option value="Lain-lain">Lain-lain</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="deskripsi_dokumen" class="form-label">Deskripsi (Opsional)</label>
                <textarea class="form-control" id="deskripsi_dokumen" name="deskripsi" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="file_dokumen" class="form-label">Pilih File</label>
                <input type="file" class="form-control" id="file_dokumen" name="file_dokumen" required>
                <small class="form-text text-muted">Tipe file yang diizinkan: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Maks 5MB.</small>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-dokumen-btn">Unggah</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>