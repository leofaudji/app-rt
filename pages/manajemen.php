<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'bendahara'])) {
    echo '<div class="alert alert-danger m-3">Akses ditolak. Anda harus menjadi Admin atau Bendahara untuk melihat halaman ini.</div>';
    if (!$is_spa_request) {
        require_once PROJECT_ROOT . '/views/footer.php';
    }
    return; // Stop rendering
}

$role = $_SESSION['role'];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-kanban-fill"></i> Pusat Manajemen RT</h1>
</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="manajemenTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="keuangan-tab" data-bs-toggle="tab" data-bs-target="#keuangan-tab-pane" type="button" role="tab">
                    <i class="bi bi-wallet-fill me-1"></i> Keuangan
                </button>
            </li>
            <?php if ($role === 'admin'): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="administrasi-tab" data-bs-toggle="tab" data-bs-target="#administrasi-tab-pane" type="button" role="tab">
                    <i class="bi bi-shield-lock-fill me-1"></i> Administrasi
                </button>
            </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="laporan-tab" data-bs-toggle="tab" data-bs-target="#laporan-tab-pane" type="button" role="tab">
                    <i class="bi bi-graph-up-arrow me-1"></i> Laporan
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="manajemenTabContent">
            <!-- Tab Keuangan -->
            <div class="tab-pane fade show active" id="keuangan-tab-pane" role="tabpanel">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 py-3">
                    <div class="col"><a href="<?= base_url('/keuangan') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></div><h5 class="card-title mt-3">Kas RT</h5><p class="card-text small text-muted">Catat dan kelola semua transaksi pemasukan dan pengeluaran kas.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/iuran') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-primary-subtle text-primary"><i class="bi bi-wallet2"></i></div><h5 class="card-title mt-3">Iuran Warga</h5><p class="card-text small text-muted">Pantau dan catat status pembayaran iuran bulanan dari setiap warga.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/anggaran') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-info-subtle text-info"><i class="bi bi-clipboard-data-fill"></i></div><h5 class="card-title mt-3">Anggaran</h5><p class="card-text small text-muted">Tetapkan dan pantau realisasi anggaran belanja untuk setiap kategori.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/manajemen/kategori-kas') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-secondary-subtle text-secondary"><i class="bi bi-tags-fill"></i></div><h5 class="card-title mt-3">Kategori Kas</h5><p class="card-text small text-muted">Kelola daftar kategori untuk transaksi pemasukan dan pengeluaran.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/tabungan') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-warning-subtle text-warning"><i class="bi bi-piggy-bank-fill"></i></div><h5 class="card-title mt-3">Tabungan Warga</h5><p class="card-text small text-muted">Kelola tabungan, setoran, dan penarikan dana dari warga.</p></div></div></a></div>
                </div>
            </div>

            <!-- Tab Administrasi (Hanya Admin) -->
            <?php if ($role === 'admin'): ?>
            <div class="tab-pane fade" id="administrasi-tab-pane" role="tabpanel">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 py-3">
                    <div class="col"><a href="<?= base_url('/warga') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></div><h5 class="card-title mt-3">Data Warga</h5><p class="card-text small text-muted">Kelola semua data kependudukan warga di lingkungan RT.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/rumah') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-secondary-subtle text-secondary"><i class="bi bi-house-fill"></i></div><h5 class="card-title mt-3">Data Rumah</h5><p class="card-text small text-muted">Kelola data properti dan histori penghuni setiap rumah.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/users') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-warning-subtle text-warning"><i class="bi bi-person-badge-fill"></i></div><h5 class="card-title mt-3">Manajemen User</h5><p class="card-text small text-muted">Atur akun dan hak akses pengguna aplikasi.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/aset') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-info-subtle text-info"><i class="bi bi-box-seam-fill"></i></div><h5 class="card-title mt-3">Inventaris Aset</h5><p class="card-text small text-muted">Catat dan kelola semua aset yang dimiliki oleh RT.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/settings') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-dark-subtle text-dark"><i class="bi bi-gear-fill"></i></div><h5 class="card-title mt-3">Pengaturan Sistem</h5><p class="card-text small text-muted">Konfigurasi umum aplikasi, kop surat, dan template.</p></div></div></a></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tab Laporan -->
            <div class="tab-pane fade" id="laporan-tab-pane" role="tabpanel">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 py-3">
                    <div class="col"><a href="<?= base_url('/laporan-terpadu') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-primary-subtle text-primary"><i class="bi bi-collection-fill"></i></div><h5 class="card-title mt-3">Laporan Terpadu</h5><p class="card-text small text-muted">Akses semua laporan keuangan, iuran, dan surat dalam satu halaman.</p></div></div></a></div>
                    <?php if ($role === 'admin'): ?>
                    <div class="col"><a href="<?= base_url('/log-aktivitas') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-secondary-subtle text-secondary"><i class="bi bi-person-vcard"></i></div><h5 class="card-title mt-3">Log Aktivitas</h5><p class="card-text small text-muted">Pantau semua aktivitas penting yang terjadi di dalam sistem.</p></div></div></a></div>
                    <div class="col"><a href="<?= base_url('/log-panik') ?>" class="text-decoration-none"><div class="card h-100 text-center management-card"><div class="card-body"><div class="icon-wrapper bg-danger-subtle text-danger"><i class="bi bi-exclamation-octagon-fill"></i></div><h5 class="card-title mt-3">Log Tombol Panik</h5><p class="card-text small text-muted">Lihat riwayat penggunaan tombol panik oleh warga.</p></div></div></a></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>