<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-bar-chart-steps"></i> Jajak Pendapat Warga</h1>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#pollingModal">
            <i class="bi bi-plus-circle"></i> Buat Jajak Pendapat
        </button>
    </div>
    <?php endif; ?>
</div>

<div id="polling-list" class="row">
    <!-- Daftar polling akan dimuat di sini oleh JavaScript -->
    <div class="text-center p-5"><div class="spinner-border"></div></div>
</div>

<!-- Modal untuk Buat Polling (hanya untuk admin) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="pollingModal" tabindex="-1" aria-labelledby="pollingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pollingModalLabel">Buat Jajak Pendapat Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="polling-form">
            <input type="hidden" name="action" value="create">
            <div class="mb-3">
                <label for="question" class="form-label">Pertanyaan</label>
                <textarea class="form-control" id="question" name="question" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Opsi Jawaban</label>
                <div id="polling-options-container">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="options[]" required>
                        <button class="btn btn-outline-danger remove-option-btn" type="button" disabled><i class="bi bi-trash"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="options[]" required>
                        <button class="btn btn-outline-danger remove-option-btn" type="button"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-option-btn"><i class="bi bi-plus"></i> Tambah Opsi</button>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-polling-btn">Simpan</button>
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