<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-person-lines-fill"></i> Keluarga Saya</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" id="add-anggota-btn" data-bs-toggle="modal" data-bs-target="#anggotaModal">
            <i class="bi bi-plus-circle"></i> Tambah Anggota Keluarga
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0" id="info-no-kk">Memuat data...</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Status dalam Keluarga</th>
                        <th>Tanggal Lahir</th>
                        <th>Pekerjaan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="keluarga-saya-table-body">
                    <!-- Data will be loaded here by JavaScript -->
                    <tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Anggota Keluarga -->
<div class="modal fade" id="anggotaModal" tabindex="-1" aria-labelledby="anggotaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="anggotaModalLabel">Tambah Anggota Keluarga</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="anggota-form">
            <input type="hidden" name="action" value="add_family_member">
            <div class="alert alert-info">
                No. Kartu Keluarga: <strong id="anggota-no-kk"></strong>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="anggota_nama_lengkap" class="form-label">Nama Lengkap</label><input type="text" class="form-control" id="anggota_nama_lengkap" name="nama_lengkap" required></div>
                <div class="col-md-6 mb-3"><label for="anggota_nama_panggilan" class="form-label">Nama Panggilan (untuk login)</label><input type="text" class="form-control" id="anggota_nama_panggilan" name="nama_panggilan" required><small class="form-text text-muted">Harus unik, tanpa spasi.</small></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="anggota_nik" class="form-label">NIK</label><input type="text" class="form-control" id="anggota_nik" name="nik" required></div>
                <div class="col-md-6 mb-3"><label for="anggota_tgl_lahir" class="form-label">Tanggal Lahir</label><input type="date" class="form-control" id="anggota_tgl_lahir" name="tgl_lahir" required><small class="form-text text-muted">Password default: tgl lahir (ddmmyyyy).</small></div>
            </div>
            <div class="row">
                 <div class="col-md-6 mb-3"><label for="anggota_status_dalam_keluarga" class="form-label">Status dalam Keluarga</label><select class="form-select" id="anggota_status_dalam_keluarga" name="status_dalam_keluarga"><option value="Istri">Istri</option><option value="Anak" selected>Anak</option><option value="Lainnya">Lainnya</option></select></div>
                 <div class="col-md-6 mb-3"><label for="anggota_jenis_kelamin" class="form-label">Jenis Kelamin</label><select class="form-select" id="anggota_jenis_kelamin" name="jenis_kelamin" required><option value="">-- Pilih --</option><option value="Laki-laki">Laki-laki</option><option value="Perempuan">Perempuan</option></select></div>
            </div>
             <div class="mb-3"><label for="anggota_pekerjaan" class="form-label">Pekerjaan</label><input type="text" class="form-control" id="anggota_pekerjaan" name="pekerjaan"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-anggota-btn">Simpan</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>