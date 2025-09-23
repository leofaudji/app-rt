<?php
// Ambil pengaturan aplikasi dari database untuk digunakan di seluruh UI
$app_settings = [];
$settings_conn = Database::getInstance()->getConnection();
$settings_result = $settings_conn->query("SELECT setting_key, setting_value FROM settings");
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $app_settings[$row['setting_key']] = $row['setting_value'];
    }
}
$app_name = htmlspecialchars($app_settings['app_name'] ?? 'Aplikasi RT');
$notification_interval = (int)($app_settings['notification_interval'] ?? 15000);
$log_cleanup_days = (int)($app_settings['log_cleanup_interval_days'] ?? 180);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $app_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <script>
        const userRole = '<?= $_SESSION['role'] ?? 'warga' ?>';
        const username = '<?= $_SESSION['username'] ?? '' ?>';
        const basePath = '<?= BASE_PATH ?>';
        const notificationInterval = <?= $notification_interval ?>;
        const logCleanupDays = <?= $log_cleanup_days ?>;
    </script>
</head>
<body class="">
<div id="spa-loading-bar"></div>
<script>
    // Apply theme and sidebar state from localStorage
    (function() {
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        }

        const isSmallScreen = window.innerWidth <= 992;
        const storedState = localStorage.getItem('sidebar-collapsed');
        if (storedState === 'true' || (storedState === null && isSmallScreen)) {
            document.body.classList.add('sidebar-collapsed');
        }
    })();
</script>
<div class="sidebar">
    <a class="navbar-brand" href="<?= base_url('/dashboard') ?>"><i class="bi bi-house-door-fill"></i> <?= $app_name ?></a>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>

        <!-- Menu Utama -->
        <li class="sidebar-header">Menu Utama</li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/pengumuman') ?>"><i class="bi bi-megaphone-fill"></i> Papan Pengumuman</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/kegiatan') ?>"><i class="bi bi-calendar-event-fill"></i> Kegiatan</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/booking') ?>"><i class="bi bi-calendar-check-fill"></i> Booking Fasilitas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/polling') ?>"><i class="bi bi-bar-chart-steps"></i> Jajak Pendapat</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/dokumen') ?>"><i class="bi bi-folder-fill"></i> Repositori Dokumen</a>
        </li>

        <!-- Layanan Mandiri -->
        <li class="sidebar-header">Layanan Mandiri</li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/keluarga-saya') ?>"><i class="bi bi-person-lines-fill"></i> Keluarga Saya</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/surat-pengantar') ?>"><i class="bi bi-envelope-paper-fill"></i> Surat Pengantar</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/iuran-saya') ?>"><i class="bi bi-receipt"></i> Riwayat Iuran</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('/laporan') ?>"><i class="bi bi-flag-fill"></i> Laporan Warga</a>
        </li>

        <!-- Manajemen Keuangan (Bendahara & Admin) -->
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'bendahara'])): ?>
            <li class="sidebar-header">Manajemen Keuangan</li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/iuran') ?>"><i class="bi bi-wallet2"></i> Iuran Warga</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/keuangan') ?>"><i class="bi bi-cash-coin"></i> Kas RT</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/anggaran') ?>"><i class="bi bi-clipboard-data-fill"></i> Anggaran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/laporan-keuangan') ?>"><i class="bi bi-graph-up"></i> Laporan Keuangan</a>
            </li>
        <?php endif; ?>

        <!-- Administrasi Sistem (Admin) -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="sidebar-header">Administrasi Sistem</li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/warga') ?>"><i class="bi bi-people-fill"></i> Data Warga</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/rumah') ?>"><i class="bi bi-house-fill"></i> Data Rumah</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/users') ?>"><i class="bi bi-person-badge-fill"></i> Manajemen User</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/aset') ?>"><i class="bi bi-box-seam-fill"></i> Inventaris Aset</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/laporan/surat') ?>"><i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan Surat</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/log-aktivitas') ?>"><i class="bi bi-person-vcard"></i> Log Aktivitas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/log-panik') ?>"><i class="bi bi-exclamation-octagon-fill"></i> Log Tombol Panik</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('/settings') ?>"><i class="bi bi-gear-fill"></i> Pengaturan Sistem</a>
            </li>
        <?php endif; ?>
    </ul>
</div>
<div class="sidebar-overlay"></div>

<div class="content-wrapper">
    <nav class="top-navbar d-flex justify-content-between align-items-center">
        <button class="btn" id="sidebar-toggle-btn" title="Toggle sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="d-flex align-items-center">
            <div id="live-clock" class="text-muted small me-3 ms-auto fw-bold">
                <!-- Clock will be inserted here by JavaScript -->
            </div>
            <!-- Panic Button -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'warga'): ?>
            <button class="btn btn-danger btn-lg me-3" id="panic-button" title="Tekan dan tahan selama 3 detik untuk mengirim sinyal darurat">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span class="panic-button-text">PANIK</span>
            </button>
            <?php endif; ?>
            <!-- Notification Dropdown -->
            <div class="nav-item dropdown me-3">
                <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span id="notification-count-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="display: none;">
                        0
                    </span>
                </a>
                <ul id="notification-dropdown-list" class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="min-width: 320px; max-height: 400px; overflow-y: auto;">
                    <!-- Notifications will be populated here by JS -->
                    <li><p class="dropdown-item text-muted text-center small mb-0">Memuat notifikasi...</p></li>
                </ul>
            </div>

            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item d-flex align-items-center" href="#" id="theme-switcher"><i class="bi bi-moon-stars-fill me-2"></i><span id="theme-switcher-text">Mode Gelap</span></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= base_url('/my-profile/edit') ?>"><i class="bi bi-person-fill-gear me-2"></i>Edit Profil</a></li>
                    <li><a class="dropdown-item" href="<?= base_url('/my-profile/change-password') ?>"><i class="bi bi-key-fill me-2"></i>Ganti Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= base_url('/logout') ?>" data-spa-ignore><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content">