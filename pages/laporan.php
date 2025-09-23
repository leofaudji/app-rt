<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-flag-fill"></i> Laporan Warga</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#laporanModal" data-action="add">
            <i class="bi bi-plus-circle"></i> Buat Laporan Baru
        </button>
    </div>
</div>

<!-- Bagian ini hanya untuk Admin dan Bendahara -->
<?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'bendahara'])): ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="filter-status-laporan" class="col-form-label">Filter Status:</label>
            </div>
            <div class="col-md-3">
                <select id="filter-status-laporan" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="baru">Baru</option>
                    <option value="diproses">Diproses</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pelapor</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Foto</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="laporan-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="7" class="text-center">Memuat data...</td></tr>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p>Gunakan tombol "Buat Laporan Baru" di atas untuk mengirimkan laporan terkait kebersihan, keamanan, atau fasilitas umum di lingkungan kita.</p>
    <h4 class="mt-4">Riwayat Laporan Anda</h4>
    <div id="laporan-list-warga" class="row">
        <!-- Daftar laporan milik pengguna akan dimuat di sini -->
        <div class="col-12 text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Memuat...</span></div></div>
    </div>
<?php endif; ?>


<!-- Modal untuk Tambah Laporan -->
<div class="modal fade" id="laporanModal" tabindex="-1" aria-labelledby="laporanModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="laporanModalLabel">Buat Laporan Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="laporan-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label for="kategori" class="form-label">Kategori Laporan</label>
                <select class="form-select" id="kategori" name="kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Kebersihan">Kebersihan</option>
                    <option value="Keamanan">Keamanan</option>
                    <option value="Fasilitas Umum">Fasilitas Umum</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div class="mb-3"><label for="deskripsi" class="form-label">Deskripsi</label><textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required></textarea></div>
            <div class="mb-3"><label for="foto" class="form-label">Unggah Foto (Opsional)</label><input type="file" class="form-control" id="foto" name="foto" accept="image/png, image/jpeg"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-laporan-btn">Kirim Laporan</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>