<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-people-fill"></i> Data Warga</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="input-group me-2">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="search-warga" placeholder="Cari warga...">
        </div>
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importWargaModal">
                <i class="bi bi-upload"></i> Impor CSV
            </button>
            <a href="#" id="export-warga-btn" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel-fill"></i> Ekspor CSV</a>
            <a href="#" id="print-warga-btn" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-printer-fill"></i> Cetak</a>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#wargaModal" data-action="add">
            <i class="bi bi-plus-circle"></i> Tambah Warga
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>#</th>
                <th>Foto</th>
                <th class="sortable" data-sort="nama_lengkap">Nama Lengkap</th>
                <th class="sortable" data-sort="nik">NIK</th>
                <th class="sortable" data-sort="alamat">Alamat</th>
                <th>No. Telepon</th>
                <th>Jenis Kelamin</th>
                <th class="sortable" data-sort="status_tinggal">Status Tinggal</th>
                <th class="sortable" data-sort="pekerjaan">Pekerjaan</th>
                <th class="sortable" data-sort="tgl_lahir">Tanggal Lahir</th>
                <th>Umur</th>
                <th>Keluarga</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="warga-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="13" class="text-center">Memuat data...</td></tr>
        </tbody>
    </table>
</div>

<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center" id="warga-pagination">
        <!-- Pagination controls will be inserted here by JavaScript -->
    </ul>
</nav>

<!-- Modal Tambah/Edit Warga -->
<div class="modal fade" id="wargaModal" tabindex="-1" aria-labelledby="wargaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="wargaModalLabel">Tambah Warga Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="warga-form">
            <input type="hidden" name="id" id="warga-id">
            <input type="hidden" name="action" id="warga-action">
            <div class="row">
                <div class="col-md-6 mb-3"><label for="nama_lengkap" class="form-label">Nama Lengkap</label><input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required></div>
                <div class="col-md-6 mb-3"><label for="nama_panggilan" class="form-label">Nama Panggilan (untuk login)</label><input type="text" class="form-control" id="nama_panggilan" name="nama_panggilan" required><small class="form-text text-muted">Harus unik, tanpa spasi.</small></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="nik" class="form-label">NIK</label><input type="text" class="form-control" id="nik" name="nik" required></div>
                <div class="col-md-6 mb-3">
                    <label for="no_kk_select" class="form-label">No. Kartu Keluarga</label>
                    <select class="form-select" id="no_kk_select" required>
                        <!-- Opsi akan diisi oleh JavaScript -->
                    </select>
                    <input type="text" class="form-control mt-2 d-none" id="no_kk_new" placeholder="Masukkan No. KK baru...">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="jenis_kelamin" class="form-label">Jenis Kelamin</label><select class="form-select" id="jenis_kelamin" name="jenis_kelamin"><option value="">-- Pilih --</option><option value="Laki-laki">Laki-laki</option><option value="Perempuan">Perempuan</option></select></div>
                <div class="col-md-6 mb-3"><label for="no_telepon" class="form-label">No. Telepon</label><input type="text" class="form-control" id="no_telepon" name="no_telepon"></div>
            </div>
            <div class="mb-3"><label for="pekerjaan" class="form-label">Pekerjaan</label><input type="text" class="form-control" id="pekerjaan" name="pekerjaan"></div>
            <div class="mb-3"><label for="alamat" class="form-label">Alamat</label><input type="text" class="form-control" id="alamat" name="alamat" required></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="status_tinggal" class="form-label">Status Tinggal</label><select class="form-select" id="status_tinggal" name="status_tinggal"><option value="tetap">Tetap</option><option value="kontrak">Kontrak</option></select></div>
                <div class="col-md-6 mb-3"><label for="tgl_lahir" class="form-label">Tanggal Lahir</label><input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir"><small class="form-text text-muted">Password default: tgl lahir (ddmmyyyy).</small></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="status_dalam_keluarga" class="form-label">Status dalam Keluarga</label><select class="form-select" id="status_dalam_keluarga" name="status_dalam_keluarga"><option value="Kepala Keluarga">Kepala Keluarga</option><option value="Istri">Istri</option><option value="Anak">Anak</option><option value="Lainnya">Lainnya</option></select></div>
            </div>
            <div class="mb-3">
                <label for="foto_profil" class="form-label">Foto Profil</label>
                <input class="form-control" type="file" id="foto_profil" name="foto_profil" accept="image/png, image/jpeg">
                <div id="foto-profil-preview" class="mt-2"></div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-warga-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Lihat Keluarga -->
<div class="modal fade" id="keluargaModal" tabindex="-1" aria-labelledby="keluargaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="keluargaModalLabel">Anggota Keluarga</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="keluarga-content"></div>
    </div>
  </div>
</div>

<!-- Modal Impor Warga -->
<div class="modal fade" id="importWargaModal" tabindex="-1" aria-labelledby="importWargaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importWargaModalLabel">Impor Data Warga dari CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Unggah file CSV dengan format kolom berikut (header wajib ada dan sesuai):</p>
        <code>no_kk,nik,nama_lengkap,nama_panggilan,alamat,no_telepon,status_tinggal,pekerjaan,tgl_lahir</code>
        <p class="mt-2"><a href="#" id="download-template-csv">Unduh Template CSV</a></p>
        <hr>
        <form id="import-warga-form" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="warga_csv_file" class="form-label">Pilih File CSV</label>
                <input class="form-control" type="file" id="warga_csv_file" name="warga_csv_file" accept=".csv" required>
            </div>
        </form>
        <div id="import-result" class="d-none mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="submit-import-warga-btn">Mulai Impor</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>