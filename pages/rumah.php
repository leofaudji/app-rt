<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-house-fill"></i> Data Rumah</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="input-group me-2">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control form-control-sm" id="search-rumah" placeholder="Cari pemilik atau penghuni...">
        </div>
        <div class="input-group me-2">
            <label class="input-group-text" for="filter-kepemilikan-rumah">Status Kepemilikan</label>
            <select class="form-select form-select-sm" id="filter-kepemilikan-rumah">
                <option value="semua" selected>Semua Rumah</option>
                <option value="tetap">Milik Sendiri (Dihuni)</option>
                <option value="kontrak">Sewa / Kontrak (Dihuni)</option>
                <option value="kosong">Tidak Berpenghuni</option>
            </select>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#rumahModal" data-action="add">
                <i class="bi bi-plus-circle"></i> Tambah Rumah
            </button>
            <a href="#" id="export-rumah-btn" class="btn btn-sm btn-success" target="_blank"><i class="bi bi-file-earmark-excel-fill"></i> Ekspor Excel</a>
        </div>
    </div>
</div>

<!-- Tabel Data Rumah -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Blok/Nomor</th>
                <th>Pemilik</th>
                <th>Kepala Keluarga Penghuni</th>
                <th>Tgl Masuk Penghuni</th>
                <th>Status Penghuni</th>
                <th>Jumlah Anggota Keluarga</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="rumah-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="7" class="text-center">Memuat data...</td></tr>
        </tbody>
    </table>
</div>

<!-- Modal untuk Tambah/Edit Rumah -->
<div class="modal fade" id="rumahModal" tabindex="-1" aria-labelledby="rumahModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rumahModalLabel">Tambah Rumah Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="rumah-form">
            <input type="hidden" name="id" id="rumah-id">
            <input type="hidden" name="action" id="rumah-action">
            <div class="row">
                <div class="col-md-6 mb-3"><label for="blok" class="form-label">Blok</label><input type="text" class="form-control" id="blok" name="blok" required></div>
                <div class="col-md-6 mb-3"><label for="nomor" class="form-label">Nomor</label><input type="text" class="form-control" id="nomor" name="nomor" required></div>
            </div>
            <div class="mb-3"><label for="pemilik" class="form-label">Nama Pemilik</label><input type="text" class="form-control" id="pemilik" name="pemilik"></div>
            <div class="mb-3">
                <label for="no_kk_penghuni" class="form-label">Penghuni (No. KK)</label>
                <select class="form-select" id="no_kk_penghuni" name="no_kk_penghuni">
                    <!-- Opsi akan diisi oleh JavaScript -->
                </select>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-rumah-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk Detail Anggota Keluarga -->
<div class="modal fade" id="anggotaKeluargaModal" tabindex="-1" aria-labelledby="anggotaKeluargaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="anggotaKeluargaModalLabel">Anggota Keluarga</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="anggota-keluarga-content">
        <!-- Detail anggota keluarga akan dimuat di sini -->
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk Histori Penghuni -->
<div class="modal fade" id="occupantHistoryModal" tabindex="-1" aria-labelledby="occupantHistoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="occupantHistoryModalLabel">Histori Penghuni Rumah</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Menampilkan histori untuk rumah: <strong id="history-rumah-info"></strong></p>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr><th>Kepala Keluarga</th><th>Tanggal Masuk</th><th>Tanggal Keluar</th><th>Catatan</th></tr>
                </thead>
                <tbody id="occupant-history-content">
                    <!-- Detail histori akan dimuat di sini -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="print-history-btn"><i class="bi bi-printer-fill"></i> Cetak Histori</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>