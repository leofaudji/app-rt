<?php
// Cek apakah ini permintaan dari SPA via AJAX
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';

// Hanya muat header jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-cash-coin"></i> Manajemen Kas RT</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#kasModal" data-action="add">
            <i class="bi bi-plus-circle"></i> Tambah Transaksi
        </button>
    </div>
</div>

<!-- Filter dan Pencarian -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search-kas" class="form-control" placeholder="Cari keterangan...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filter-jenis-kas" class="form-select">
                    <option value="">Semua Jenis</option>
                    <option value="masuk">Pemasukan</option>
                    <option value="keluar">Pengeluaran</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Data Kas -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Dicatat Oleh</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="kas-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="7" class="text-center">Memuat data...</td></tr>
        </tbody>
    </table>
</div>

<!-- Modal untuk Tambah/Edit Kas -->
<div class="modal fade" id="kasModal" tabindex="-1" aria-labelledby="kasModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="kasModalLabel">Tambah Transaksi Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="kas-form">
            <input type="hidden" name="id" id="kas-id">
            <input type="hidden" name="action" id="kas-action">
            <div class="mb-3"><label for="tanggal" class="form-label">Tanggal</label><input type="date" class="form-control" id="tanggal" name="tanggal" required></div>
            <div class="mb-3"><label for="jenis" class="form-label">Jenis Transaksi</label><select class="form-select" id="jenis" name="jenis"><option value="masuk">Pemasukan</option><option value="keluar">Pengeluaran</option></select></div>
            <div class="mb-3">
                <label for="kategori" class="form-label">Kategori</label>
                <select class="form-select" id="kategori" name="kategori" required>
                    <!-- Opsi akan diisi oleh JavaScript berdasarkan jenis transaksi -->
                </select>
            </div>
            <div class="mb-3"><label for="keterangan" class="form-label">Keterangan</label><input type="text" class="form-control" id="keterangan" name="keterangan" required></div>
            <div class="mb-3"><label for="jumlah" class="form-label">Jumlah (Rp)</label><input type="number" step="0.01" class="form-control" id="jumlah" name="jumlah" required></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-kas-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<?php
// Hanya muat footer jika ini bukan permintaan SPA
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>