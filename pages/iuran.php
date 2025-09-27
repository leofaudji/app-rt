<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-wallet2"></i> Iuran Warga</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
        <a href="#" id="cetak-iuran-btn" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer-fill"></i> Cetak Laporan
        </a>
    </div>
</div>

<!-- Ringkasan Iuran -->
<div class="row mb-3">
    <!-- Card Total Pemasukan -->
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Pemasukan Iuran</h5>
                        <h2 class="fw-bold" id="total-pemasukan-iuran"><div class="spinner-border spinner-border-sm" role="status"></div></h2>
                    </div>
                    <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Jumlah Belum Bayar -->
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Jumlah Belum Dibayar</h5>
                        <h2 class="fw-bold" id="jumlah-belum-bayar"><div class="spinner-border spinner-border-sm" role="status"></div></h2>
                    </div>
                    <i class="bi bi-exclamation-triangle-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Periode -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search-iuran" class="form-control" placeholder="Cari nama kepala keluarga...">
                </div>
            </div>
            <div class="col-auto ms-md-auto">
                <label for="filter-bulan" class="col-form-label">Periode:</label>
            </div>
            <div class="col-auto">
                <select id="filter-bulan" class="form-select">
                    <!-- Opsi bulan akan diisi oleh JavaScript -->
                </select>
            </div>
            <div class="col-auto">
                <select id="filter-tahun" class="form-select">
                    <!-- Opsi tahun akan diisi oleh JavaScript -->
                </select>
            </div>
            <div class="col-auto">
                <select id="filter-status-pembayaran" class="form-select">
                    <option value="semua" selected>Semua</option>
                    <option value="lunas">Lunas</option>
                    <option value="belum_lunas">Belum Lunas</option>
                </select>
            </div>
            <div class="col-auto ms-md-auto">
                <label for="iuran-limit" class="col-form-label">Tampilkan:</label>
            </div>
            <div class="col-auto">
                <select id="iuran-limit" class="form-select">
                    <option value="10" selected>10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">Semua</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Data Iuran -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Nama Warga</th>
                <th>Alamat</th>
                <th>Status Pembayaran</th>
                <th>Tanggal Bayar</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="iuran-table-body">
            <!-- Data akan dimuat di sini oleh JavaScript -->
            <tr><td colspan="5" class="text-center">Pilih periode untuk menampilkan data...</td></tr>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center" id="iuran-pagination">
        <!-- Pagination controls will be inserted here by JavaScript -->
    </ul>
</nav>

<!-- Modal untuk Konfirmasi Pembayaran -->
<div class="modal fade" id="bayarModal" tabindex="-1" aria-labelledby="bayarModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bayarModalLabel">Konfirmasi Pembayaran Iuran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Anda akan menandai iuran untuk <strong id="nama-warga-bayar"></strong> pada periode <strong id="periode-bayar"></strong> sebagai LUNAS.</p>
        <form id="bayar-form">
            <input type="hidden" name="warga_id" id="bayar-warga-id">
            <input type="hidden" name="periode_tahun" id="bayar-periode-tahun">
            <input type="hidden" name="periode_bulan" id="bayar-periode-bulan">
            <div class="mb-3"><label for="jumlah" class="form-label">Jumlah Iuran (Rp)</label><input type="number" class="form-control" id="bayar-jumlah" name="jumlah" value="50000" required></div>
            <div class="mb-3"><label for="tanggal_bayar" class="form-label">Tanggal Bayar</label><input type="date" class="form-control" id="bayar-tanggal" name="tanggal_bayar" required></div>
            <div class="mb-3"><label for="catatan_bayar" class="form-label">Catatan (Opsional)</label><textarea class="form-control" id="catatan_bayar" name="catatan" rows="2"></textarea></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-bayar-btn">Simpan Pembayaran</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>