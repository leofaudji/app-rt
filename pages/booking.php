<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-calendar-check-fill"></i> Booking Fasilitas</h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="booking-calendar-container">
            <div id="booking-calendar"></div>
        </div>
    </div>
</div>

<!-- Modal untuk Booking Fasilitas -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bookingModalLabel">Buat Booking Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="booking-form">
            <input type="hidden" name="action" id="booking-action">
            <input type="hidden" name="booking_id" id="booking-id">
            
            <div id="booking-info-view" class="d-none">
                <p><strong>Fasilitas:</strong> <span id="view-fasilitas"></span></p>
                <p><strong>Keperluan:</strong> <span id="view-judul"></span></p>
                <p><strong>Pemesan:</strong> <span id="view-pemesan"></span></p>
                <p><strong>Waktu:</strong> <span id="view-waktu"></span></p>
                <p><strong>Status:</strong> <span id="view-status"></span></p>
            </div>

            <div id="booking-form-fields">
                <div class="mb-3">
                    <label for="fasilitas_id" class="form-label">Fasilitas</label>
                    <select class="form-select" id="fasilitas_id" name="fasilitas_id" required>
                        <!-- Opsi akan diisi oleh JavaScript -->
                    </select>
                </div>
                <div class="mb-3">
                    <label for="judul_booking" class="form-label">Keperluan</label>
                    <input type="text" class="form-control" id="judul_booking" name="judul" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                        <input type="datetime-local" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                        <input type="datetime-local" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                    </div>
                </div>
            </div>
            
            <div id="admin-actions" class="mt-3 border-top pt-3 d-none">
                <h5>Aksi Admin</h5>
                <p>Setujui atau tolak permintaan booking ini.</p>
                <div class="btn-group">
                    <button type="button" class="btn btn-success" id="approve-booking-btn">Setujui</button>
                    <button type="button" class="btn btn-danger" id="reject-booking-btn">Tolak</button>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger me-auto d-none" id="delete-booking-btn">Hapus</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="save-booking-btn">Ajukan Booking</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>