<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-megaphone-fill"></i> Papan Pengumuman</h1>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#pengumumanModal" data-action="add">
            <i class="bi bi-plus-circle"></i> Buat Pengumuman
        </button>
    </div>
    <?php endif; ?>
</div>

<div id="pengumuman-list" class="row">
    <!-- Daftar pengumuman akan dimuat di sini oleh JavaScript -->
    <div class="text-center p-5"><div class="spinner-border"></div></div>
</div>

<!-- Modal untuk Tambah/Edit Pengumuman (hanya untuk admin) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="pengumumanModal" tabindex="-1" aria-labelledby="pengumumanModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pengumumanModalLabel">Buat Pengumuman Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="pengumuman-form">
            <input type="hidden" name="id" id="pengumuman-id">
            <input type="hidden" name="action" id="pengumuman-action">
            <div class="mb-3"><label for="judul-pengumuman" class="form-label">Judul</label><input type="text" class="form-control" id="judul-pengumuman" name="judul" required></div>
            <div class="mb-3"><label for="isi-pengumuman" class="form-label">Isi Pengumuman</label><textarea class="form-control" id="isi-pengumuman" name="isi_pengumuman" rows="5" required></textarea></div>
            <div class="mb-3">
                <label for="tanggal_terbit" class="form-label">Jadwalkan Terbit (Opsional)</label>
                <input type="datetime-local" class="form-control" id="tanggal_terbit" name="tanggal_terbit">
                <small class="form-text text-muted">Kosongkan untuk menerbitkan sekarang juga.</small>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-pengumuman-btn">Simpan</button>
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