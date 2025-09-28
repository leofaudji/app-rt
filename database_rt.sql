SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `iuran`, `laporan_warga`, `kegiatan`, `kas`, `warga`, `rumah`, `rumah_penghuni_history`, `users`, `notifications`, `pengumuman`, `activity_log`, `settings`, `dokumen`, `fasilitas`, `booking_fasilitas`, `polling`, `polling_votes`, `anggaran`, `surat_pengantar`, `surat_templates`, `aset_rt`, `panic_log`, `peminjaman_aset`, `surat_keluar`, `struktur_organisasi`, `tamu`, `usaha_warga`, `galeri_album`, `galeri_foto`, `galeri_komentar`, `kas_kategori`,`iuran_settings_history`;

DROP TABLE IF EXISTS `tabungan_warga`, `tabungan_kategori`, `tabungan_goals`;
SET FOREIGN_KEY_CHECKS = 1;

-- Tabel untuk pengguna aplikasi (Admin RT, Bendahara, dll)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `role` enum('admin','bendahara','warga') NOT NULL DEFAULT 'warga',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk data rumah
CREATE TABLE `rumah` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blok` varchar(10) NOT NULL,
  `nomor` varchar(10) NOT NULL,
  `pemilik` varchar(100) DEFAULT NULL,
  `no_kk_penghuni` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blok_nomor` (`blok`,`nomor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk histori penghuni rumah
CREATE TABLE `rumah_penghuni_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rumah_id` int(11) NOT NULL,
  `no_kk_penghuni` varchar(20) NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rumah_id` (`rumah_id`),
  FOREIGN KEY (`rumah_id`) REFERENCES `rumah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk data warga
CREATE TABLE `warga` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_kk` varchar(20) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `golongan_darah` varchar(2) DEFAULT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `status_tinggal` enum('tetap','kontrak') NOT NULL DEFAULT 'tetap',
  `pekerjaan` varchar(100) DEFAULT NULL,
  `nama_panggilan` varchar(50) DEFAULT NULL,
  `status_dalam_keluarga` enum('Kepala Keluarga','Istri','Anak','Lainnya') DEFAULT 'Lainnya',
  `tgl_lahir` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nik` (`nik`),
  UNIQUE KEY `nama_panggilan` (`nama_panggilan`),
  KEY `no_kk` (`no_kk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk kegiatan RT
CREATE TABLE `kegiatan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_kegiatan` datetime NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `dibuat_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk kas RT
CREATE TABLE `kas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `jenis` enum('masuk','keluar') NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `keterangan` varchar(255) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `dicatat_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dicatat_oleh` (`dicatat_oleh`),
  FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk laporan warga
CREATE TABLE `laporan_warga` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warga_pelapor_id` int(11) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`warga_pelapor_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk notifikasi
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `warga_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `warga_id` (`warga_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk pengumuman
CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `isi_pengumuman` text NOT NULL,
  `tanggal_terbit` datetime DEFAULT NULL,
  `dibuat_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dibuat_oleh` (`dibuat_oleh`),
  FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk repositori dokumen
CREATE TABLE `dokumen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_dokumen` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(100) DEFAULT 'Lain-lain',
  `nama_file` varchar(255) NOT NULL,
  `path_file` varchar(255) NOT NULL,
  `diunggah_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `diunggah_oleh` (`diunggah_oleh`),
  FOREIGN KEY (`diunggah_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk daftar fasilitas
CREATE TABLE `fasilitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_fasilitas` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `warna_event` varchar(7) DEFAULT '#3788d8',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk booking fasilitas
CREATE TABLE `booking_fasilitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fasilitas_id` int(11) NOT NULL,
  `warga_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fasilitas_id` (`fasilitas_id`),
  KEY `warga_id` (`warga_id`),
  FOREIGN KEY (`fasilitas_id`) REFERENCES `fasilitas` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk polling/jajak pendapat
CREATE TABLE `polling` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `options` json NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan suara polling
CREATE TABLE `polling_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polling_id` int(11) NOT NULL,
  `warga_id` int(11) NOT NULL,
  `selected_option` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `polling_warga_vote` (`polling_id`,`warga_id`),
  FOREIGN KEY (`polling_id`) REFERENCES `polling` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Anggaran
CREATE TABLE `anggaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tahun` smallint(4) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `jumlah_anggaran` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tahun_kategori` (`tahun`,`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Permintaan Surat Pengantar
CREATE TABLE `surat_pengantar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warga_id` int(11) NOT NULL,
  `jenis_surat` varchar(100) NOT NULL,
  `keperluan` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `nomor_surat` varchar(100) DEFAULT NULL,
  `keterangan_admin` text DEFAULT NULL,
  `processed_by_id` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `warga_id` (`warga_id`),
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`processed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Template Surat
CREATE TABLE `surat_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_template` varchar(100) NOT NULL,
  `judul_surat` varchar(255) NOT NULL,
  `konten` longtext NOT NULL,
  `requires_parent_data` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_template` (`nama_template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Inventaris Aset RT
CREATE TABLE `aset_rt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_aset` varchar(255) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik',
  `lokasi_simpan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk log aktivitas
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk iuran warga
CREATE TABLE `iuran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_kk` varchar(20) NOT NULL,
  `periode_tahun` smallint(4) NOT NULL,
  `periode_bulan` tinyint(2) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `dicatat_oleh` int(11) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kk_periode` (`no_kk`,`periode_tahun`,`periode_bulan`),
  FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk log tombol panik
CREATE TABLE `panic_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warga_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warga_id` (`warga_id`),
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Surat Keluar (Official Letters)
CREATE TABLE `surat_keluar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_surat` varchar(100) NOT NULL,
  `perihal` varchar(255) NOT NULL,
  `tujuan` varchar(255) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `isi_surat` text DEFAULT NULL,
  `dibuat_oleh_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_surat` (`nomor_surat`),
  FOREIGN KEY (`dibuat_oleh_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Struktur Organisasi RT
CREATE TABLE `struktur_organisasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jabatan` varchar(100) NOT NULL,
  `deskripsi_tugas` text DEFAULT NULL,
  `warga_id` int(11) DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jabatan` (`jabatan`),
  KEY `warga_id` (`warga_id`),
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Lapor Tamu
CREATE TABLE `tamu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warga_id` int(11) NOT NULL,
  `nama_tamu` varchar(100) NOT NULL,
  `nik_tamu` varchar(20) DEFAULT NULL,
  `tgl_datang` date NOT NULL,
  `tgl_pulang` date DEFAULT NULL,
  `keperluan` text DEFAULT NULL,
  `status` enum('menginap','selesai') NOT NULL DEFAULT 'menginap',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Peminjaman Aset
CREATE TABLE `peminjaman_aset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aset_id` int(11) NOT NULL,
  `warga_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `status` enum('pending','approved','rejected','returned','taken') NOT NULL DEFAULT 'pending',
  `catatan_peminjam` text DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`aset_id`) REFERENCES `aset_rt` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Direktori Usaha Warga
CREATE TABLE `usaha_warga` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warga_id` int(11) NOT NULL,
  `nama_usaha` varchar(100) NOT NULL,
  `kategori_usaha` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `nomor_whatsapp` varchar(20) DEFAULT NULL,
  `foto_usaha` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `warga_id` (`warga_id`),
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Album Galeri
CREATE TABLE `galeri_album` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kegiatan_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kegiatan_id` (`kegiatan_id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Foto dalam Galeri
CREATE TABLE `galeri_foto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `album_id` int(11) NOT NULL,
  `path_file` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`album_id`) REFERENCES `galeri_album` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Komentar pada Foto Galeri
CREATE TABLE `galeri_komentar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `foto_id` int(11) NOT NULL,
  `warga_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `foto_id` (`foto_id`),
  KEY `warga_id` (`warga_id`),
  FOREIGN KEY (`foto_id`) REFERENCES `galeri_foto` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk kategori kas
CREATE TABLE `kas_kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `jenis` enum('masuk','keluar') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_kategori_jenis` (`nama_kategori`,`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk target tabungan warga
CREATE TABLE `tabungan_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warga_id` int(11) NOT NULL,
  `nama_goal` varchar(255) NOT NULL,
  `target_jumlah` decimal(12,2) NOT NULL,
  `tanggal_target` date DEFAULT NULL,
  `status` enum('aktif','tercapai') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `warga_id` (`warga_id`),
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk kategori tabungan warga
CREATE TABLE `tabungan_kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `jenis` enum('setor','tarik') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_kategori_jenis` (`nama_kategori`,`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk transaksi tabungan warga
CREATE TABLE `tabungan_warga` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warga_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jenis` enum('setor','tarik') NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `dicatat_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `warga_id` (`warga_id`),
  KEY `kategori_id` (`kategori_id`),
  KEY `dicatat_oleh` (`dicatat_oleh`),
  FOREIGN KEY (`warga_id`) REFERENCES `warga` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kategori_id`) REFERENCES `tabungan_kategori` (`id`),
  FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data awal untuk kategori kas
INSERT INTO `kas_kategori` (`nama_kategori`, `jenis`) VALUES
('Iuran Warga', 'masuk'),
('Sumbangan', 'masuk'),
('Sewa Fasilitas', 'masuk'),
('Saldo Awal', 'masuk'),
('Lain-lain', 'masuk'),
('Kebersihan', 'keluar'),
('Keamanan', 'keluar'),
('Listrik & Air', 'keluar'),
('Perbaikan', 'keluar'),
('Acara RT', 'keluar'),
('Administrasi', 'keluar'),
('Lain-lain', 'keluar');

-- Data awal untuk kategori tabungan
INSERT INTO `tabungan_kategori` (`nama_kategori`, `jenis`) VALUES
('Setoran Tunai', 'setor'),
('Bagi Hasil / Bunga', 'setor'),
('Penarikan Tunai', 'tarik'),
('Biaya Administrasi', 'tarik');

-- Tabel untuk pengaturan umum
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk histori perubahan nominal iuran
CREATE TABLE `iuran_settings_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `monthly_fee` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal
-- Password default untuk 'admin' adalah 'password'
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'Aplikasi RT'),
('housing_name', 'Perumahan Sejahtera'),
('monthly_fee', '50000'),
('notification_interval', '15000'),
('rt_head_name', 'Nama Ketua RT Default'),
('log_cleanup_interval_days', '180'),
('whatsapp_notification_number', '');

-- Data Awal untuk histori iuran, berlaku mulai dari tanggal tertentu sampai ada perubahan baru
INSERT INTO `iuran_settings_history` (`monthly_fee`, `start_date`, `updated_by`) VALUES (50000.00, '2020-01-01', 1);
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES ('admin', '{$default_password_hash}', 'Administrator RT', 'admin');
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('A', '1', 'Budi Santoso', '3201010101010001');
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('A', '2', 'Siti Aminah', '3201010101010002');
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('A', '3', 'Eko Prasetyo', NULL);
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('B', '10', 'Faudji', '3573013005920004');
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('B', '11', 'Rina Wati', '3201010101010003');
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('C', '1', 'Hendra Wijaya', '3201010101010005');

-- Tambahan 15 Rumah Baru
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES
('C', '2', 'Joko Susilo', '3201010101010006'),
('C', '3', 'Slamet Riyadi', '3201010101010007'),
('C', '4', 'Endang Sutarmi', NULL),
('C', '5', 'Ahmad Dahlan', '3201010101010008'),
('C', '6', 'Putu Gede', '3201010101010009'),
('C', '7', 'Morgan Oey', '3201010101010010'),
('C', '8', 'Kevin Sanjaya', '3201010101010011'),
('C', '9', 'Taufik Hidayat', NULL),
('C', '10', 'Susi Susanti', '3201010101010012'),
('D', '1', 'Rudi Hartono', '3201010101010013'),
('D', '2', 'Christian Hadinata', '3201010101010014'),
('D', '3', 'Lius Pongoh', '3201010101010015'),
('D', '4', 'Icuk Sugiarto', '3201010101010016'),
('D', '5', 'Alan Budikusuma', NULL),
('D', '6', 'Ardy Wiranata', '3201010101010017');

-- Tambahan 10 Rumah Baru
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES
('D', '7', 'Herman Susanto', '3201010101010019'),
('D', '8', 'Dedi Cahyadi', '3201010101010020'),
('D', '9', 'Bambang Wijoyo', '3201010101010021'),
('D', '10', 'Andrianto', '3201010101010022'),
('E', '1', 'Sutrisno', '3201010101010023'),
('E', '2', 'Arif Rahman', '3201010101010024'),
('E', '3', 'Gandi Martin', NULL),
('E', '4', 'Vino Sanjaya', '3201010101010025'),
('E', '5', 'Dedi Mahendra', '3201010101010026'),
('E', '6', 'Radit Purnomo', '3201010101010027');


-- Histori untuk rumah C-1
INSERT INTO `rumah_penghuni_history` (`rumah_id`, `no_kk_penghuni`, `tanggal_masuk`, `tanggal_keluar`) VALUES (5, '3201010101010004', '2022-01-15', '2024-03-10');
INSERT INTO `rumah_penghuni_history` (`rumah_id`, `no_kk_penghuni`, `tanggal_masuk`) VALUES (5, '3201010101010005', '2024-03-15');

-- Data Warga (Beberapa Keluarga)
-- Keluarga Budi Santoso (Tetap)
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010001', '3201010101900001', 'Budi Santoso', 'Blok A No. 1', 'Laki-laki', 'tetap', 'Karyawan Swasta', 'budi', 'Kepala Keluarga', '1990-01-01');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010001', '3201010101920002', 'Wati Susanti', 'Blok A No. 1', 'Perempuan', 'tetap', 'Ibu Rumah Tangga', 'wati', 'Istri', '1992-03-15');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010001', '3201010101150001', 'Adi Santoso', 'Blok A No. 1', 'Laki-laki', 'tetap', 'Pelajar', 'adi', 'Anak', '2015-08-20');

-- Keluarga Agus Setiawan (Kontrak)
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010002', '3201010101880003', 'Agus Setiawan', 'Blok A No. 2', 'Laki-laki', 'kontrak', 'Wiraswasta', 'agus', 'Kepala Keluarga', '1988-05-10');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010002', '3201010101900004', 'Dewi Lestari', 'Blok A No. 2', 'Perempuan', 'kontrak', 'Guru', 'dewi', 'Istri', '1990-11-25');

-- Keluarga Rahmat Hidayat (Kontrak)
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010003', '3201010101850005', 'Rahmat Hidayat', 'Blok B No. 11', 'Laki-laki', 'kontrak', 'PNS', 'rahmat', 'Kepala Keluarga', '1985-02-12');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010003', '3201010101870006', 'Fitriani', 'Blok B No. 11', 'Perempuan', 'kontrak', 'Ibu Rumah Tangga', 'fitri', 'Istri', '1987-07-07');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010003', '3201010101140002', 'Dina Hidayat', 'Blok B No. 11', 'Perempuan', 'kontrak', 'Pelajar', 'dina', 'Anak', '2014-09-01');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010003', '3201010101180003', 'Doni Hidayat', 'Blok B No. 11', 'Laki-laki', 'kontrak', 'Belum Sekolah', 'doni', 'Anak', '2018-12-30');

-- Keluarga Faudji (Tetap)
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3573013005920004', '3573013005920004', 'Faudji', 'Blok B No. 10', 'Laki-laki', 'tetap', 'Presiden', 'faudji', 'Kepala Keluarga', '1992-05-30');

-- Keluarga David (Penghuni Lama, untuk histori)
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010004', '3201010101890007', 'David', 'Blok C No. 1 (Lama)', 'Laki-laki', 'kontrak', 'Programmer', 'david', 'Kepala Keluarga', '1989-04-04');

-- Keluarga Iwan (Penghuni Baru di C-1)
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010005', '3201010101910008', 'Iwan Setiawan', 'Blok C No. 1', 'Laki-laki', 'tetap', 'Musisi', 'iwan', 'Kepala Keluarga', '1991-06-06');

-- Tambahan 15 Keluarga Baru
-- KK 0006
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010006', '3201010101930009', 'Joko Susilo', 'Blok C No. 2', 'Laki-laki', 'tetap', 'Arsitek', 'joko', 'Kepala Keluarga', '1993-01-20');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010006', '3201010101950010', 'Jihan Audy', 'Blok C No. 2', 'Perempuan', 'tetap', 'Desainer', 'jihan', 'Istri', '1995-04-22');
-- KK 0007
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010007', '3201010101840011', 'Slamet Riyadi', 'Blok C No. 3', 'Laki-laki', 'kontrak', 'Dokter', 'slamet', 'Kepala Keluarga', '1984-08-17');
-- KK 0008
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010008', '3201010101800012', 'Ahmad Zaelani', 'Blok C No. 5', 'Laki-laki', 'tetap', 'Pengusaha', 'ahmad', 'Kepala Keluarga', '1980-11-11');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010008', '3201010101820013', 'Siti Zulaikha', 'Blok C No. 5', 'Perempuan', 'tetap', 'Ibu Rumah Tangga', 'sitiw', 'Istri', '1982-12-12');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010008', '3201010101100014', 'Fatimah Zaelani', 'Blok C No. 5', 'Perempuan', 'tetap', 'Pelajar', 'fatimah', 'Anak', '2010-10-10');
-- KK 0009
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010009', '3201010101960015', 'Putu Gede', 'Blok C No. 6', 'Laki-laki', 'kontrak', 'Atlet', 'putu', 'Kepala Keluarga', '1996-06-07');
-- KK 0010
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010010', '3201010101910016', 'Morgansyah', 'Blok C No. 7', 'Laki-laki', 'kontrak', 'Aktor', 'morgan', 'Kepala Keluarga', '1991-05-25');
-- KK 0011
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010011', '3201010101950017', 'Kevin Gunawan', 'Blok C No. 8', 'Laki-laki', 'tetap', 'Atlet', 'kevin', 'Kepala Keluarga', '1995-08-02');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010011', '3201010101970018', 'Valerie Tan', 'Blok C No. 8', 'Perempuan', 'tetap', 'Pengusaha', 'valencia', 'Istri', '1997-01-13');
-- KK 0012
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010012', '3201010101710019', 'Susi Lestari', 'Blok C No. 10', 'Perempuan', 'tetap', 'Pensiunan', 'susi', 'Kepala Keluarga', '1971-02-11');
-- KK 0013
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010013', '3201010101880020', 'Rudi Haryanto', 'Blok D No. 1', 'Laki-laki', 'kontrak', 'Freelancer', 'rudi', 'Kepala Keluarga', '1988-09-18');
-- KK 0014
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010014', '3201010101890021', 'Christian Adi', 'Blok D No. 2', 'Laki-laki', 'tetap', 'Pelatih', 'christian', 'Kepala Keluarga', '1989-12-11');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010014', '3201010101900022', 'Yuni Kartini', 'Blok D No. 2', 'Perempuan', 'tetap', 'Komentator', 'yuni', 'Istri', '1990-06-16');
-- KK 0015
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010015', '3201010101850023', 'Lius Pangaribuan', 'Blok D No. 3', 'Laki-laki', 'tetap', 'PNS', 'lius', 'Kepala Keluarga', '1985-12-03');
-- KK 0016
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010016', '3201010101820024', 'Ikhsan Sugiarto', 'Blok D No. 4', 'Laki-laki', 'kontrak', 'Seniman', 'icuk', 'Kepala Keluarga', '1982-10-04');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010016', '3201010101830025', 'Nina Yaroh', 'Blok D No. 4', 'Perempuan', 'kontrak', 'Ibu Rumah Tangga', 'nina', 'Istri', '1983-11-05');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010016', '3201010101120026', 'Tomi Sugiarto', 'Blok D No. 4', 'Laki-laki', 'kontrak', 'Pelajar', 'tommy', 'Anak', '2012-05-31');
-- KK 0017
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010017', '3201010101900027', 'Ardi Wirawan', 'Blok D No. 6', 'Laki-laki', 'tetap', 'Karyawan BUMN', 'ardy', 'Kepala Keluarga', '1990-02-10');
-- KK 0018 (Penghuni lama untuk histori)
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010018', '3201010101880028', 'Toni Ahmad', 'Blok C No. 9 (Lama)', 'Laki-laki', 'kontrak', 'Pegawai Bank', 'tontowi', 'Kepala Keluarga', '1988-07-18');

-- Tambahan 10 Keluarga Baru
-- KK 0019
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010019', '3201010101860029', 'Herman Susanto', 'Blok D No. 7', 'Laki-laki', 'tetap', 'Artis', 'raffi', 'Kepala Keluarga', '1986-02-17');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010019', '3201010101870030', 'Lina Marlina', 'Blok D No. 7', 'Perempuan', 'tetap', 'Artis', 'gigi', 'Istri', '1987-02-17');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010019', '3201010101150031', 'Rian Susanto', 'Blok D No. 7', 'Laki-laki', 'tetap', 'Pelajar', 'rafathar', 'Anak', '2015-08-15');
-- KK 0020
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010020', '3201010101760032', 'Dedi Cahyadi', 'Blok D No. 8', 'Laki-laki', 'tetap', 'Youtuber', 'deddy', 'Kepala Keluarga', '1976-12-28');
-- KK 0021
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010021', '3201010101810033', 'Bambang Wijoyo', 'Blok D No. 9', 'Laki-laki', 'kontrak', 'Aktor', 'baim', 'Kepala Keluarga', '1981-04-27');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010021', '3201010101900034', 'Poppy Wulandari', 'Blok D No. 9', 'Perempuan', 'kontrak', 'Model', 'paula', 'Istri', '1990-09-18');
-- KK 0022
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010022', '3201010101740035', 'Andrianto', 'Blok D No. 10', 'Laki-laki', 'tetap', 'Komedian', 'andre', 'Kepala Keluarga', '1974-09-17');
-- KK 0023
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010023', '3201010101760036', 'Sutrisno', 'Blok E No. 1', 'Laki-laki', 'tetap', 'Komedian', 'sule', 'Kepala Keluarga', '1976-11-15');
-- KK 0024
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010024', '3201010101810037', 'Arif Rahman', 'Blok E No. 2', 'Laki-laki', 'kontrak', 'Musisi', 'ariel', 'Kepala Keluarga', '1981-09-16');
-- KK 0025
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010025', '3201010101780038', 'Vino Sanjaya', 'Blok E No. 4', 'Laki-laki', 'tetap', 'Presenter', 'vincent', 'Kepala Keluarga', '1978-03-29');
-- KK 0026
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010026', '3201010101770039', 'Dedi Mahendra', 'Blok E No. 5', 'Laki-laki', 'tetap', 'Presenter', 'desta', 'Kepala Keluarga', '1977-03-15');
-- KK 0027
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010027', '3201010101840040', 'Radit Purnomo', 'Blok E No. 6', 'Laki-laki', 'kontrak', 'Penulis', 'radit', 'Kepala Keluarga', '1984-12-28');

-- Tambahan Histori Penghuni
INSERT INTO `rumah_penghuni_history` (`rumah_id`, `no_kk_penghuni`, `tanggal_masuk`, `tanggal_keluar`) VALUES (15, '3201010101010018', '2021-05-20', '2024-01-15');
INSERT INTO `rumah_penghuni_history` (`rumah_id`, `no_kk_penghuni`, `tanggal_masuk`) VALUES (15, '32010101010012', '2024-02-01');

-- Data Kegiatan
INSERT INTO `kegiatan` (`judul`, `deskripsi`, `tanggal_kegiatan`, `lokasi`, `dibuat_oleh`) VALUES 
('Kerja Bakti Bulanan', 'Membersihkan area taman dan selokan bersama.', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Taman Utama RT', 1),
('Rapat Pengurus RT', 'Pembahasan rencana kegiatan 17 Agustus.', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'Balai Warga', 1),
('Penyuluhan Kesehatan DBD', 'Penyuluhan dari Puskesmas setempat mengenai pencegahan Demam Berdarah Dengue.', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'Balai Warga', 1),
('Bazar UMKM Warga', 'Acara bazar untuk mempromosikan usaha milik warga perumahan.', DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'Area Lapangan', 1);

-- Data Kas (Pemasukan dan Pengeluaran beberapa bulan)
-- Bulan ini
INSERT INTO `kas` (`tanggal`, `jenis`, `kategori`, `keterangan`, `jumlah`, `dicatat_oleh`) VALUES 
(CURDATE(), 'masuk', 'Saldo Awal', 'Saldo awal kas', 5500000.00, 1),
(DATE_FORMAT(NOW() ,'%Y-%m-02'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Budi Santoso', 50000.00, 1),
(DATE_FORMAT(NOW() ,'%Y-%m-03'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Agus Setiawan', 50000.00, 1),
(DATE_FORMAT(NOW() ,'%Y-%m-05'), 'keluar', 'Kebersihan', 'Pembayaran gaji petugas kebersihan', 350000.00, 1),
(DATE_FORMAT(NOW() ,'%Y-%m-05'), 'keluar', 'Keamanan', 'Pembayaran gaji satpam', 750000.00, 1),
(DATE_FORMAT(NOW() ,'%Y-%m-10'), 'masuk', 'Sewa Fasilitas', 'Sewa Balai Warga oleh Bpk. Joko', 200000.00, 1),
(DATE_FORMAT(NOW() ,'%Y-%m-11'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Herman Susanto', 50000.00, 1),
(DATE_FORMAT(NOW() ,'%Y-%m-12'), 'keluar', 'Administrasi', 'Pembelian ATK dan fotokopi', 75000.00, 1);

-- Bulan lalu
INSERT INTO `kas` (`tanggal`, `jenis`, `kategori`, `keterangan`, `jumlah`, `dicatat_oleh`) VALUES 
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-02'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Budi Santoso', 50000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-03'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Faudji', 50000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-04'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Rahmat Hidayat', 50000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-05'), 'keluar', 'Kebersihan', 'Pembayaran gaji petugas kebersihan', 350000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-05'), 'keluar', 'Keamanan', 'Pembayaran gaji satpam', 750000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-15'), 'keluar', 'Listrik & Air', 'Pembayaran Listrik Fasum (Lampu Jalan)', 250000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-16'), 'keluar', 'Listrik & Air', 'Pembayaran Air PDAM (Masjid & Fasum)', 150000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-25'), 'keluar', 'Acara RT', 'Konsumsi acara syukuran warga baru', 450000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-06'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Herman Susanto', 50000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-07'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Bambang Wijoyo', 50000.00, 1);

-- 2 Bulan lalu
INSERT INTO `kas` (`tanggal`, `jenis`, `kategori`, `keterangan`, `jumlah`, `dicatat_oleh`) VALUES 
(DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-02'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Budi Santoso', 50000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-05'), 'keluar', 'Kebersihan', 'Pembayaran gaji petugas kebersihan', 350000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-05'), 'keluar', 'Keamanan', 'Pembayaran gaji satpam', 750000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-20'), 'keluar', 'Perbaikan', 'Perbaikan lampu jalan Blok B', 125000.00, 1);
INSERT INTO `kas` (`tanggal`, `jenis`, `kategori`, `keterangan`, `jumlah`, `dicatat_oleh`) VALUES (DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-06'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Joko Susilo', 50000.00, 1);
INSERT INTO `kas` (`tanggal`, `jenis`, `kategori`, `keterangan`, `jumlah`, `dicatat_oleh`) VALUES (DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-07'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Ahmad Dahlan', 50000.00, 1);
-- 3 Bulan lalu
INSERT INTO `kas` (`tanggal`, `jenis`, `kategori`, `keterangan`, `jumlah`, `dicatat_oleh`) VALUES 
(DATE_FORMAT(NOW() - INTERVAL 3 MONTH ,'%Y-%m-02'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Budi Santoso', 50000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 3 MONTH ,'%Y-%m-03'), 'masuk', 'Iuran Warga', 'Pembayaran iuran Agus Setiawan', 50000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 3 MONTH ,'%Y-%m-05'), 'keluar', 'Kebersihan', 'Pembayaran gaji petugas kebersihan', 350000.00, 1),
(DATE_FORMAT(NOW() - INTERVAL 3 MONTH ,'%Y-%m-05'), 'keluar', 'Keamanan', 'Pembayaran gaji satpam', 750000.00, 1);

-- Data Iuran (sesuai dengan data kas)
-- Bulan ini
INSERT INTO `iuran` (`no_kk`, `periode_tahun`, `periode_bulan`, `jumlah`, `tanggal_bayar`, `dicatat_oleh`) VALUES 
('3201010101010001', YEAR(CURDATE()), MONTH(CURDATE()), 50000.00, DATE_FORMAT(NOW() ,'%Y-%m-02'), 1),
('3201010101010002', YEAR(CURDATE()), MONTH(CURDATE()), 50000.00, DATE_FORMAT(NOW() ,'%Y-%m-03'), 1),
('3201010101010019', YEAR(CURDATE()), MONTH(CURDATE()), 50000.00, DATE_FORMAT(NOW() ,'%Y-%m-11'), 1);

-- Bulan lalu
INSERT INTO `iuran` (`no_kk`, `periode_tahun`, `periode_bulan`, `jumlah`, `tanggal_bayar`, `dicatat_oleh`) VALUES 
('3201010101010001', YEAR(CURDATE() - INTERVAL 1 MONTH), MONTH(CURDATE() - INTERVAL 1 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-02'), 1),
('3573013005920004', YEAR(CURDATE() - INTERVAL 1 MONTH), MONTH(CURDATE() - INTERVAL 1 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-03'), 1),
('3201010101010003', YEAR(CURDATE() - INTERVAL 1 MONTH), MONTH(CURDATE() - INTERVAL 1 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-04'), 1),
('3201010101010019', YEAR(CURDATE() - INTERVAL 1 MONTH), MONTH(CURDATE() - INTERVAL 1 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-06'), 1),
('3201010101010021', YEAR(CURDATE() - INTERVAL 1 MONTH), MONTH(CURDATE() - INTERVAL 1 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-07'), 1);

-- 2 Bulan lalu
INSERT INTO `iuran` (`no_kk`, `periode_tahun`, `periode_bulan`, `jumlah`, `tanggal_bayar`, `dicatat_oleh`) VALUES 
('3201010101010001', YEAR(CURDATE() - INTERVAL 2 MONTH), MONTH(CURDATE() - INTERVAL 2 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-02'), 1);
INSERT INTO `iuran` (`no_kk`, `periode_tahun`, `periode_bulan`, `jumlah`, `tanggal_bayar`, `dicatat_oleh`) VALUES ('3201010101010006', YEAR(CURDATE() - INTERVAL 2 MONTH), MONTH(CURDATE() - INTERVAL 2 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-06'), 1);
INSERT INTO `iuran` (`no_kk`, `periode_tahun`, `periode_bulan`, `jumlah`, `tanggal_bayar`, `dicatat_oleh`) VALUES ('3201010101010008', YEAR(CURDATE() - INTERVAL 2 MONTH), MONTH(CURDATE() - INTERVAL 2 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 2 MONTH ,'%Y-%m-07'), 1);

-- 3 Bulan lalu
INSERT INTO `iuran` (`no_kk`, `periode_tahun`, `periode_bulan`, `jumlah`, `tanggal_bayar`, `dicatat_oleh`) VALUES 
('3201010101010001', YEAR(CURDATE() - INTERVAL 3 MONTH), MONTH(CURDATE() - INTERVAL 3 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 3 MONTH ,'%Y-%m-02'), 1),
('3201010101010002', YEAR(CURDATE() - INTERVAL 3 MONTH), MONTH(CURDATE() - INTERVAL 3 MONTH), 50000.00, DATE_FORMAT(NOW() - INTERVAL 3 MONTH ,'%Y-%m-03'), 1);

INSERT INTO `fasilitas` (`id`, `nama_fasilitas`, `deskripsi`, `warna_event`) VALUES
(1, 'Balai Warga', 'Ruang serbaguna untuk rapat dan acara warga.', '#0d6efd'),
(2, 'Lapangan Badminton', 'Lapangan outdoor untuk bermain badminton.', '#198754');
INSERT INTO `fasilitas` (`id`, `nama_fasilitas`, `deskripsi`, `warna_event`) VALUES (3, 'Taman Bermain Anak', 'Area bermain untuk anak-anak.', '#fd7e14');

-- Data Demo: Pengumuman
INSERT INTO `pengumuman` (`judul`, `isi_pengumuman`, `tanggal_terbit`, `dibuat_oleh`) VALUES
('Pemberitahuan Pemadaman Listrik Bergilir', 'Diberitahukan kepada seluruh warga, akan ada pemadaman listrik dari PLN pada hari Sabtu, 25 Juli 2024, dari pukul 10:00 hingga 14:00. Mohon persiapannya.', '2024-07-20 10:00:00', 1),
('Hasil Rapat Warga Bulan Juni', 'Berikut adalah notulensi dan hasil rapat warga yang telah dilaksanakan pada tanggal 15 Juni 2024. Dokumen lengkap dapat diunduh di menu Repositori Dokumen.', '2024-06-18 15:30:00', 1),
('Lomba Kebersihan Antar Blok', 'Dalam rangka menyambut HUT RI, akan diadakan lomba kebersihan antar blok. Penilaian akan dilakukan pada tanggal 10 Agustus 2024. Mari kita jaga kebersihan lingkungan bersama!', NULL, 1),
('Penggunaan Fasilitas Selama Libur Sekolah', 'Selama periode libur sekolah, penggunaan fasilitas umum seperti taman bermain dan lapangan agar tetap menjaga ketertiban dan kebersihan. Batas waktu bermain di area umum adalah pukul 21:00.', NULL, 1),
('Informasi Iuran Keamanan Tambahan', 'Disampaikan bahwa akan ada iuran tambahan sebesar Rp 25.000 untuk peningkatan sistem keamanan (pemasangan CCTV baru) yang akan ditagihkan bersamaan dengan iuran bulan Agustus.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 1),
('Jadwal Fogging Nyamuk', 'Akan dilaksanakan fogging untuk pencegahan DBD pada hari Minggu pagi. Mohon untuk menyimpan makanan dan minuman di tempat tertutup.', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 1),
('Perubahan Jadwal Pengambilan Sampah', 'Diberitahukan bahwa jadwal pengambilan sampah diubah dari hari Selasa & Jumat menjadi hari Senin & Kamis, efektif mulai minggu depan.', NULL, 1);

-- Data Demo: Laporan Warga
INSERT INTO `laporan_warga` (`warga_pelapor_id`, `kategori`, `deskripsi`, `status`) VALUES
(1, 'Fasilitas Umum', 'Lampu jalan di depan rumah Blok A No. 1 mati sudah 3 hari.', 'baru'),
(4, 'Kebersihan', 'Tumpukan sampah di dekat taman bermain belum diangkut oleh petugas.', 'diproses'),
(6, 'Keamanan', 'Ada orang tidak dikenal sering mondar-mandir di Blok B pada malam hari.', 'selesai'),
(10, 'Lainnya', 'Mohon diadakan kembali kegiatan senam pagi bersama setiap hari Minggu.', 'baru'),
(16, 'Fasilitas Umum', 'Jaring net di lapangan badminton sobek, perlu perbaikan.', 'baru'),
(29, 'Fasilitas Umum', 'Ayunan di taman bermain ada yang rusak, berbahaya untuk anak-anak.', 'baru'),
(33, 'Kebersihan', 'Selokan di depan Blok D tersumbat dan menyebabkan genangan air.', 'diproses');

-- Data Demo: Surat Pengantar
INSERT INTO `surat_pengantar` (`warga_id`, `jenis_surat`, `keperluan`, `status`, `nomor_surat`, `keterangan_admin`, `processed_by_id`) VALUES
(1, 'Surat Keterangan Domisili', 'Untuk pendaftaran sekolah anak.', 'approved', '001/SKD/RT01/VII/2024', NULL, 1),
(4, 'Pengantar SKCK', 'Untuk melamar pekerjaan.', 'pending', NULL, NULL, NULL),
(6, 'Surat Keterangan Domisili', 'Untuk pengurusan administrasi di kelurahan.', 'rejected', NULL, 'Data NIK pemohon tidak sesuai dengan KTP.', 1),
(10, 'Pengantar Nikah', 'Untuk pendaftaran pernikahan di KUA.', 'pending', NULL, NULL, NULL),
(16, 'Pengantar SKCK', 'Syarat perpanjangan kontrak kerja.', 'approved', '002/SKCK/RT01/VII/2024', NULL, 1),
(32, 'Surat Keterangan Domisili', 'Untuk membuka rekening bank.', 'pending', NULL, NULL, NULL),
(36, 'Surat Keterangan Usaha', 'Untuk pengajuan pinjaman KUR.', 'pending', NULL, NULL, NULL);

-- Data Demo: Repositori Dokumen
INSERT INTO `dokumen` (`nama_dokumen`, `deskripsi`, `kategori`, `nama_file`, `path_file`, `diunggah_oleh`) VALUES
('Notulensi Rapat Juni 2024', 'Hasil lengkap rapat warga bulanan Juni 2024.', 'Notulensi Rapat', 'notulen_juni_2024.pdf', 'uploads/dokumen/dummy.pdf', 1),
('Peraturan Keamanan Lingkungan', 'Update peraturan keamanan terbaru per Juli 2024.', 'Peraturan Lingkungan', 'peraturan_keamanan_2024.pdf', 'uploads/dokumen/dummy.pdf', 1),
('Laporan Keuangan Q2 2024', 'Rincian laporan keuangan untuk kuartal kedua tahun 2024.', 'Laporan Keuangan', 'lapkeu_q2_2024.xlsx', 'uploads/dokumen/dummy.xlsx', 1),
('Surat Edaran Kerja Bakti', 'Surat edaran resmi untuk kegiatan kerja bakti menyambut HUT RI.', 'Surat Edaran', 'edaran_kerjabakti.pdf', 'uploads/dokumen/dummy.pdf', 1),
('Denah Perumahan', 'Peta denah perumahan beserta nomor rumah.', 'Lain-lain', 'denah_perumahan.jpg', 'uploads/dokumen/dummy.jpg', 1);

-- Data Demo: Jajak Pendapat (Polling) & Suara (Votes)
INSERT INTO `polling` (`id`, `question`, `options`, `status`, `created_by`) VALUES
(1, 'Apakah perlu diadakan program fogging nyamuk rutin setiap bulan?', '["Setuju", "Tidak Setuju", "Cukup 3 bulan sekali"]', 'closed', 1),
(2, 'Pilihan warna baru untuk cat gerbang utama perumahan?', '["Abu-abu Modern", "Hitam Elegan", "Putih Klasik"]', 'open', 1),
(3, 'Kegiatan apa yang paling diminati untuk acara 17 Agustus?', '["Lomba Anak-anak", "Panggung Hiburan Malam", "Jalan Sehat & Bazar"]', 'open', 1);

-- Votes untuk Polling 1
INSERT INTO `polling_votes` (`polling_id`, `warga_id`, `selected_option`) VALUES
(1, 1, 0), (1, 4, 0), (1, 6, 2), (1, 8, 0), (1, 11, 1), (1, 14, 2), (1, 16, 0);
-- Votes untuk Polling 2
INSERT INTO `polling_votes` (`polling_id`, `warga_id`, `selected_option`) VALUES
(2, 1, 1), (2, 6, 0), (2, 10, 1), (2, 15, 2);

-- Data Demo: Booking Fasilitas
INSERT INTO `booking_fasilitas` (`fasilitas_id`, `warga_id`, `judul`, `tanggal_mulai`, `tanggal_selesai`, `status`) VALUES
(1, 1, 'Acara Syukuran Keluarga', DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'approved'),
(2, 4, 'Latihan Badminton Rutin', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'pending'),
(2, 6, 'Main Bareng Blok B', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'approved'),
(1, 10, 'Rapat Karang Taruna', DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'pending'),
(1, 16, 'Acara Ulang Tahun Anak', DATE_ADD(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'rejected'),
(3, 29, 'Acara Bermain Anak Blok D', DATE_ADD(CURDATE(), INTERVAL 8 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'approved'),
(1, 33, 'Rapat Persiapan 17an', DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'pending');

-- Data Demo: Aset RT
INSERT INTO `aset_rt` (`nama_aset`, `jumlah`, `kondisi`, `lokasi_simpan`) VALUES
('Kursi Lipat', 50, 'Baik', 'Gudang Balai Warga'),
('Meja Panjang', 10, 'Baik', 'Gudang Balai Warga'),
('Sound System Portable', 1, 'Rusak Ringan', 'Rumah Ketua RT'),
('Tenda Pleton', 2, 'Baik', 'Gudang Balai Warga'),
('Mesin Potong Rumput', 1, 'Baik', 'Pos Satpam');

INSERT INTO `anggaran` (`tahun`, `kategori`, `jumlah_anggaran`) VALUES
(YEAR(CURDATE()), 'Kebersihan', 1200000.00),
(YEAR(CURDATE()), 'Keamanan', 2400000.00);
INSERT INTO `surat_templates` (`id`, `nama_template`, `judul_surat`, `konten`, `requires_parent_data`) VALUES
(1, 'Surat Keterangan Domisili', 'SURAT KETERANGAN DOMISILI', 'Yang bertanda tangan di bawah ini, Ketua RT {{app.housing_name}}, dengan ini menerangkan bahwa:\n\nNama Lengkap: {{surat.nama_lengkap}}\nNIK: {{surat.nik}}\nTanggal Lahir: {{surat.tgl_lahir_formatted}}\nPekerjaan: {{surat.pekerjaan}}\nAlamat: {{surat.alamat}}\n\nAdalah benar warga kami yang berdomisili di alamat tersebut di atas. Surat keterangan ini dibuat sebagai pengantar untuk keperluan:\n\n{{surat.keperluan}}\n\nDemikian surat keterangan domisili ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 0),
(2, 'Pengantar SKCK', 'SURAT PENGANTAR SKCK', 'Yang bertanda tangan di bawah ini, Ketua RT {{app.housing_name}}, dengan ini menerangkan bahwa:\n\nNama Lengkap: {{surat.nama_lengkap}}\nNIK: {{surat.nik}}\nAlamat: {{surat.alamat}}\n\nAdalah benar warga kami yang berdomisili di alamat tersebut di atas dan berkelakuan baik di lingkungan kami. Surat keterangan ini dibuat sebagai pengantar untuk mengurus SKCK di Kepolisian.\n\nKeperluan: {{surat.keperluan}}\n\nDemikian surat pengantar ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 0),
(3, 'Pengantar Nikah', 'SURAT PENGANTAR NIKAH', 'Yang bertanda tangan di bawah ini, Ketua RT {{app.housing_name}}, dengan ini menerangkan bahwa:\n\nI. DATA CALON\nNama Lengkap: {{surat.nama_lengkap}}\nNIK: {{surat.nik}}\nTanggal Lahir: {{surat.tgl_lahir_formatted}}\nAlamat: {{surat.alamat}}\n\nII. DATA ORANG TUA / WALI\nAyah:\nNama Lengkap: {{data_ayah.nama_lengkap}}\nNIK: {{data_ayah.nik}}\n\nIbu:\nNama Lengkap: {{data_ibu.nama_lengkap}}\nNIK: {{data_ibu.nik}}\n\nAdalah benar warga kami yang berdomisili di alamat tersebut di atas. Surat pengantar ini dibuat sebagai salah satu syarat administrasi untuk mengurus pernikahan.\n\nDemikian surat pengantar ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 1);