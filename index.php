<?php  
// Aplikasi RT - Front Controller

// Mulai sesi di setiap permintaan. Ini harus dilakukan sebelum output apa pun.
session_start();  

// Muat komponen inti
require_once 'includes/bootstrap.php';
require_once 'includes/Router.php';

// Router membutuhkan base path yang sudah didefinisikan di bootstrap.php
$router = new Router(BASE_PATH);

// --- Definisikan Rute (Routes) ---

// Rute untuk tamu (hanya bisa diakses jika belum login)
$router->get('/login', 'login.php', ['guest']);
$router->post('/login', 'actions/auth.php'); // Handler untuk proses login

// Rute yang memerlukan otentikasi
$router->get('/', 'logout.php'); // Selalu paksa logout/login saat membuka root
$router->get('/dashboard', 'pages/dashboard_rt.php', ['auth', 'log_access']);
$router->get('/logout', 'logout.php');

// --- Rute Manajemen Warga (Memerlukan login sebagai admin) ---
$router->get('/warga', 'pages/warga.php', ['auth', 'admin']);
$router->get('/warga/profil/(\d+)', 'pages/warga_profil.php', ['auth']);
$router->get('/users', 'pages/users.php', ['auth', 'admin']);
$router->get('/rumah', 'pages/rumah.php', ['auth', 'admin']);
$router->get('/rumah/detail/(\d+)', 'pages/rumah_detail.php', ['auth', 'admin']);
$router->get('/rumah/histori/cetak', 'pages/rumah_histori_cetak.php', ['auth', 'admin']);
$router->get('/kegiatan', 'pages/kegiatan.php', ['auth']);
$router->get('/settings', 'pages/settings.php', ['auth', 'admin']);
$router->get('/galeri', 'pages/galeri.php', ['auth']);
$router->get('/galeri/album/(\d+)', 'pages/galeri_album.php', ['auth']);
$router->get('/pengumuman', 'pages/pengumuman.php', ['auth']);
$router->get('/laporan-keuangan', 'pages/laporan_keuangan.php', ['auth']);
$router->get('/laporan-keuangan/cetak', 'pages/laporan_keuangan_cetak.php', ['auth', 'bendahara']);
$router->get('/aset', 'pages/aset.php', ['auth', 'admin']);
$router->get('/laporan/iuran/cetak', 'pages/laporan_iuran_cetak.php', ['auth', 'admin', 'bendahara']);
$router->get('/laporan/iuran', 'pages/laporan_iuran.php', ['auth', 'admin', 'bendahara']);
$router->get('/laporan/surat', 'pages/laporan_surat.php', ['auth', 'admin']);
$router->get('/laporan/surat/cetak', 'pages/laporan_surat_cetak.php', ['auth', 'admin']);
$router->get('/dokumen', 'pages/dokumen.php', ['auth']);
$router->get('/log-panik', 'pages/panic_log.php', ['auth', 'admin']);
$router->get('/log-aktivitas', 'pages/log_aktivitas.php', ['auth', 'admin']);
$router->get('/anggaran', 'pages/anggaran.php', ['auth', 'bendahara']);
$router->get('/booking', 'pages/booking.php', ['auth']);
$router->get('/surat-pengantar/cetak', 'pages/surat_cetak.php', ['auth']);
$router->get('/surat-pengantar', 'pages/surat_pengantar.php', ['auth']);
$router->get('/polling', 'pages/polling.php', ['auth']);
$router->get('/laporan', 'pages/laporan.php', ['auth']);
$router->get('/my-profile/edit', 'pages/my_profile_edit.php', ['auth']);
$router->get('/iuran-saya', 'pages/iuran_saya.php', ['auth']);
$router->get('/keluarga-saya', 'pages/keluarga_saya.php', ['auth']);
$router->get('/my-profile/change-password', 'pages/my_profile.php', ['auth']);

// --- Rute API (Untuk proses data via AJAX) ---
// Rute ini akan dipanggil oleh JavaScript untuk mendapatkan, menambah, mengubah, dan menghapus data tanpa reload halaman.
$router->get('/api/dashboard', 'api/dashboard_handler.php', ['auth']); // Mengambil data untuk dashboard
$router->get('/api/warga', 'api/warga_handler.php', ['auth']); // Mengambil daftar warga
$router->post('/api/warga', 'api/warga_handler.php', ['auth']); // Handler generik untuk aksi POST (new, update, delete, get_single, get_keluarga)
$router->get('/api/warga/export', 'api/warga_export.php', ['auth', 'admin']); // Ekspor data warga
$router->get('/warga/cetak', 'pages/warga_cetak.php', ['auth', 'admin']); // Cetak data warga
$router->post('/api/warga/import', 'api/warga_import.php', ['auth', 'admin']); // Impor data warga
$router->get('/api/warga/template', 'api/warga_template.php', ['auth', 'admin']); // Download template

$router->get('/api/users', 'api/users_handler.php', ['auth', 'admin']);
$router->post('/api/users/new', 'api/users_handler.php', ['auth', 'admin']);
$router->post('/api/users/update', 'api/users_handler.php', ['auth', 'admin']);
$router->post('/api/users/delete', 'api/users_handler.php', ['auth', 'admin']);

$router->get('/api/rumah', 'api/rumah_handler.php', ['auth']);
$router->post('/api/rumah', 'api/rumah_handler.php', ['auth', 'admin']);
$router->get('/api/rumah/export', 'api/rumah_export.php', ['auth', 'admin']);

// Rute lainnya bisa ditambahkan di sini
$router->get('/keuangan', 'pages/keuangan.php', ['auth', 'bendahara']);
$router->get('/api/kas', 'api/kas_handler.php', ['auth', 'bendahara']);
$router->post('/api/kas/new', 'api/kas_handler.php', ['auth', 'bendahara']);
$router->post('/api/kas/update', 'api/kas_handler.php', ['auth', 'bendahara']);
$router->post('/api/kas/delete', 'api/kas_handler.php', ['auth', 'bendahara']);
$router->get('/iuran', 'pages/iuran.php', ['auth', 'bendahara']);
$router->get('/iuran/cetak', 'pages/iuran_cetak.php', ['auth', 'bendahara']);
$router->get('/iuran/histori/([a-zA-Z0-9_-]+)/kk', 'pages/iuran_histori.php', ['auth', 'admin', 'bendahara']);
$router->get('/api/iuran', 'api/iuran_handler.php', ['auth', 'bendahara']);
$router->post('/api/iuran', 'api/iuran_handler.php', ['auth', 'bendahara']);

$router->get('/api/kegiatan', 'api/kegiatan_handler.php', ['auth']);
$router->post('/api/kegiatan', 'api/kegiatan_handler.php', ['auth', 'admin']);
$router->get('/kegiatan/undangan', 'pages/kegiatan_undangan.php', ['auth', 'admin']);
$router->get('/api/laporan', 'api/laporan_handler.php', ['auth']);
$router->post('/api/laporan', 'api/laporan_handler.php', ['auth']);
$router->get('/api/notifications', 'api/notifications_handler.php', ['auth']);
$router->post('/api/notifications', 'api/notifications_handler.php', ['auth']);
$router->get('/api/settings', 'api/settings_handler.php', ['auth', 'admin']);
$router->post('/api/settings', 'api/settings_handler.php', ['auth', 'admin']);
$router->get('/api/pengumuman', 'api/pengumuman_handler.php', ['auth']);
$router->post('/api/pengumuman', 'api/pengumuman_handler.php', ['auth', 'admin']);
$router->get('/api/dokumen', 'api/dokumen_handler.php', ['auth']);
$router->post('/api/dokumen', 'api/dokumen_handler.php', ['auth', 'admin']);
$router->get('/api/galeri', 'api/galeri_handler.php', ['auth']);
$router->get('/api/laporan/iuran', 'api/laporan_iuran_handler.php', ['auth', 'admin', 'bendahara']);
$router->get('/api/laporan/iuran/export', 'api/laporan_iuran_export.php', ['auth', 'admin', 'bendahara']);
$router->get('/api/booking', 'api/booking_handler.php', ['auth']);
$router->post('/api/booking', 'api/booking_handler.php', ['auth']);
$router->get('/api/surat-pengantar', 'api/surat_pengantar_handler.php', ['auth']);
$router->post('/api/surat-pengantar', 'api/surat_pengantar_handler.php', ['auth']);
$router->get('/api/laporan/surat/export/excel', 'api/laporan_surat_export_excel.php', ['auth', 'admin']);
$router->get('/api/surat-templates', 'api/surat_template_handler.php', ['auth']); // Warga needs to list them
$router->post('/api/surat-templates', 'api/surat_template_handler.php', ['auth', 'admin']);
$router->get('/api/panic-log', 'api/panic_log_handler.php', ['auth', 'admin']);
$router->get('/api/anggaran', 'api/anggaran_handler.php', ['auth', 'bendahara']);
$router->get('/api/log', 'api/log_handler.php', ['auth', 'admin']);
$router->post('/api/anggaran', 'api/anggaran_handler.php', ['auth', 'bendahara']);
$router->get('/api/polling', 'api/polling_handler.php', ['auth']);
$router->post('/api/galeri', 'api/galeri_handler.php', ['auth', 'admin']);
$router->post('/api/polling', 'api/polling_handler.php', ['auth']);
$router->post('/api/panic', 'api/panic_handler.php', ['auth']);
$router->get('/api/laporan-keuangan', 'api/laporan_keuangan_handler.php', ['auth']);
$router->get('/api/my-profile', 'api/my_profile_edit_handler.php', ['auth']);
$router->get('/api/iuran-saya', 'api/iuran_saya_handler.php', ['auth']);
$router->post('/api/my-profile', 'api/my_profile_edit_handler.php', ['auth']);
$router->post('/api/my-profile/change-password', 'api/my_profile_handler.php', ['auth']);

// Jalankan router
$router->dispatch();