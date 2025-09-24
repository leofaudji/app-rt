<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$album_id = end($path_parts);

if (!is_numeric($album_id)) {
    echo '<div class="alert alert-danger m-3">ID Album tidak valid.</div>';
    if (!$is_spa_request) { require_once PROJECT_ROOT . '/views/footer.php'; }
    return;
}
?>

<div id="album-detail-container" data-album-id="<?= htmlspecialchars($album_id) ?>">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2" id="album-title"><div class="spinner-border spinner-border-sm"></div></h1>
            <p class="text-muted" id="album-description"></p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= base_url('/galeri') ?>" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Galeri
            </a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFotoModal"><i class="bi bi-upload"></i> Unggah Foto</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Photo Grid -->
    <div id="photo-grid" class="row g-3">
        <div class="col-12 text-center p-5"><div class="spinner-border" style="width: 3rem; height: 3rem;"></div></div>
    </div>
</div>

<!-- Modal Upload Foto (Admin) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="uploadFotoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Unggah Foto ke Album</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="upload-foto-form" enctype="multipart/form-data"><input type="hidden" name="action" value="upload_photos"><input type="hidden" name="album_id" value="<?= htmlspecialchars($album_id) ?>"><div class="mb-3"><label for="photos" class="form-label">Pilih Foto (bisa lebih dari satu)</label><input type="file" class="form-control" id="photos" name="photos[]" multiple required accept="image/jpeg, image/png, image/gif"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="save-upload-btn">Unggah</button></div></div></div>
</div>
<?php endif; ?>

<!-- Modal View Foto -->
<div class="modal fade" id="viewPhotoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="view-photo-caption"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="row g-0">
          <div class="col-lg-8 bg-dark text-center d-flex align-items-center justify-content-center">
            <img id="view-photo-img" src="" class="img-fluid" style="max-height: 80vh; object-fit: contain;">
          </div>
          <div class="col-lg-4 d-flex flex-column bg-body-tertiary">
            <div id="comment-list" class="flex-grow-1 p-3" style="overflow-y: auto;">
              <!-- Comments will be loaded here -->
            </div>
            <div class="p-3 border-top"><form id="comment-form"><input type="hidden" name="action" value="add_comment"><input type="hidden" name="foto_id" id="comment-foto-id"><div class="input-group"><input type="text" class="form-control" name="komentar" placeholder="Tulis komentar..." required autocomplete="off"><button class="btn btn-primary" type="submit" id="submit-comment-btn"><i class="bi bi-send-fill"></i></button></div></form></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>