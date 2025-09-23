<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-envelope-paper-fill"></i> Surat Pengantar</h1>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'warga'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#suratModal">
            <i class="bi bi-plus-circle"></i> Ajukan Permintaan
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Tampilan untuk Admin -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pemohon</th>
                <th>Jenis Surat</th>
                <th>Keperluan</th>
                <th>Status</th>
                <th>Diproses Oleh</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="surat-admin-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="7" class="text-center p-5"><div class="spinner-border"></div></td></tr>
        </tbody>
    </table>
</div>
<?php else: ?>
<!-- Tampilan untuk Warga -->
<p>Gunakan tombol "Ajukan Permintaan" untuk meminta surat pengantar dari pengurus RT.</p>
<h4 class="mt-4">Riwayat Permintaan Anda</h4>
<div id="surat-warga-list" class="row">
    <!-- Daftar permintaan milik pengguna akan dimuat di sini -->
    <div class="col-12 text-center p-5"><div class="spinner-border"></div></div>
</div>
<?php endif; ?>


<!-- Modal untuk Ajuan/Detail Surat -->
<div class="modal fade" id="suratModal" tabindex="-1" aria-labelledby="suratModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="suratModalLabel">Ajukan Permintaan Surat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="surat-form">
            <input type="hidden" name="action" id="surat-action">
            <input type="hidden" name="surat_id" id="surat-id">

            <!-- Info View for Admin -->
            <div id="surat-info-view" class="d-none mb-3">
                <p><strong>Pemohon:</strong> <span id="view-pemohon"></span></p>
                <p><strong>Jenis Surat:</strong> <span id="view-jenis-surat"></span></p>
                <p><strong>Keperluan:</strong> <span id="view-keperluan"></span></p>
            </div>

            <!-- Form Fields for Warga -->
            <div id="surat-form-fields">
                <div class="mb-3">
                    <label for="jenis_surat" class="form-label">Jenis Surat</label>
                    <select class="form-select" id="jenis_surat" name="jenis_surat" required>
                        <option value="Surat Keterangan Domisili">Surat Keterangan Domisili</option>
                        <option value="Pengantar SKCK">Pengantar SKCK</option>
                        <option value="Pengantar Nikah">Pengantar Nikah</option>
                        <option value="Surat Keterangan Usaha">Surat Keterangan Usaha</option>
                        <option value="Pengantar Izin Usaha">Pengantar Izin Usaha</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="keperluan" class="form-label">Keperluan</label>
                    <textarea class="form-control" id="keperluan" name="keperluan" rows="4" required placeholder="Contoh: Untuk melamar pekerjaan di PT. Sejahtera Abadi"></textarea>
                </div>
            </div>

            <!-- Admin Actions -->
            <div id="surat-admin-actions" class="d-none border-top pt-3">
                <div class="mb-3"><label for="nomor_surat" class="form-label">Nomor Surat (jika disetujui)</label><input type="text" class="form-control" id="nomor_surat" name="nomor_surat"></div>
                <div class="mb-3"><label for="keterangan_admin" class="form-label">Keterangan (jika ditolak)</label><textarea class="form-control" id="keterangan_admin" name="keterangan_admin" rows="2"></textarea></div>
            </div>
        </form>
      </div>
      <div class="modal-footer" id="surat-modal-footer">
        <!-- Buttons will be populated by JS -->
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>