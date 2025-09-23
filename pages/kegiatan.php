<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-calendar-event-fill"></i> Kegiatan RT</h1>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#kegiatanModal" data-action="add">
            <i class="bi bi-plus-circle"></i> Tambah Kegiatan
        </button>
    </div>
    <?php endif; ?>
</div>

<div id="kegiatan-list" class="row">
    <!-- Daftar kegiatan akan dimuat di sini oleh JavaScript -->
    <div class="text-center p-5"><div class="spinner-border"></div></div>
</div>

<!-- Modal untuk Tambah/Edit Kegiatan (hanya untuk admin) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="kegiatanModal" tabindex="-1" aria-labelledby="kegiatanModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="kegiatanModalLabel">Tambah Kegiatan Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="kegiatan-form">
            <input type="hidden" name="id" id="kegiatan-id">
            <input type="hidden" name="action" id="kegiatan-action">
            <div class="mb-3"><label for="judul" class="form-label">Judul Kegiatan</label><input type="text" class="form-control" id="judul" name="judul" required></div>
            <div class="mb-3"><label for="deskripsi" class="form-label">Deskripsi</label><textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea></div>
            <div class="mb-3"><label for="tanggal_kegiatan" class="form-label">Tanggal & Waktu</label><input type="datetime-local" class="form-control" id="tanggal_kegiatan" name="tanggal_kegiatan" required></div>
            <div class="mb-3"><label for="lokasi" class="form-label">Lokasi</label><input type="text" class="form-control" id="lokasi" name="lokasi"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-kegiatan-btn">Simpan</button>
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