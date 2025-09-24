<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-images"></i> Galeri Kegiatan Warga</h1>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#albumModal" data-action="add">
            <i class="bi bi-plus-circle"></i> Buat Album Baru
        </button>
    </div>
    <?php endif; ?>
</div>

<div id="album-list" class="row">
    <!-- Daftar album akan dimuat di sini oleh JavaScript -->
    <div class="text-center p-5"><div class="spinner-border"></div></div>
</div>

<!-- Modal untuk Tambah/Edit Album (hanya untuk admin) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="albumModal" tabindex="-1" aria-labelledby="albumModalLabel" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="albumModalLabel">Buat Album Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="album-form"><input type="hidden" name="action" id="album-action"><input type="hidden" name="id" id="album-id"><div class="mb-3"><label for="judul_album" class="form-label">Judul Album</label><input type="text" class="form-control" id="judul_album" name="judul" required></div><div class="mb-3"><label for="deskripsi_album" class="form-label">Deskripsi (Opsional)</label><textarea class="form-control" id="deskripsi_album" name="deskripsi" rows="3"></textarea></div><div class="mb-3"><label for="kegiatan_id_album" class="form-label">Tautkan ke Kegiatan (Opsional)</label><select class="form-select" id="kegiatan_id_album" name="kegiatan_id"><option value="">-- Tidak ditautkan --</option></select></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="save-album-btn">Simpan</button></div></div></div>
</div>
<?php endif; ?>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>