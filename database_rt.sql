SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `iuran`, `laporan_warga`, `kegiatan`, `kas`, `warga`, `rumah`, `rumah_penghuni_history`, `users`, `notifications`, `pengumuman`, `activity_log`, `settings`, `dokumen`, `fasilitas`, `booking_fasilitas`, `polling`, `polling_votes`, `anggaran`, `surat_pengantar`, `surat_templates`, `aset_rt`, `panic_log`, `peminjaman_aset`, `surat_keluar`, `struktur_organisasi`, `tamu`, `usaha_warga`;

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
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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

-- Tabel untuk pengaturan umum
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
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
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES ('admin', '{$default_password_hash}', 'Administrator RT', 'admin');
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('A', '1', 'Budi Santoso', '3201010101010001');
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('A', '2', 'Siti Aminah', NULL);
INSERT INTO `rumah` (`blok`, `nomor`, `pemilik`, `no_kk_penghuni`) VALUES ('B', '10', 'Faudji', '3573013005920004');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `agama`, `golongan_darah`, `foto_profil`, `no_telepon`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3201010101010001', '3201010101900001', 'Budi Santoso', 'Blok A No. 1', 'Laki-laki', 'Islam', 'O', NULL, '081234567890', 'tetap', 'Karyawan Swasta', 'budi', 'Kepala Keluarga', '1990-01-01');
INSERT INTO `warga` (`no_kk`, `nik`, `nama_lengkap`, `alamat`, `jenis_kelamin`, `agama`, `golongan_darah`, `foto_profil`, `no_telepon`, `status_tinggal`, `pekerjaan`, `nama_panggilan`, `status_dalam_keluarga`, `tgl_lahir`) VALUES ('3573013005920004', '3573013005920004', 'Faudji', 'Blok B No. 10', 'Laki-laki', 'Islam', 'A', NULL, '087759651803', 'tetap', 'Presiden', 'faudji', 'Kepala Keluarga', '1992-05-30');
INSERT INTO `kegiatan` (`judul`, `deskripsi`, `tanggal_kegiatan`, `lokasi`, `dibuat_oleh`) VALUES ('Kerja Bakti Bulanan', 'Membersihkan area taman dan selokan bersama.', '2024-06-30 08:00:00', 'Taman Utama RT', 1);
INSERT INTO `kas` (`tanggal`, `jenis`, `kategori`, `keterangan`, `jumlah`, `dicatat_oleh`) VALUES (CURDATE(), 'masuk', 'Saldo Awal', 'Saldo awal kas', 500000.00, 1);
INSERT INTO `fasilitas` (`id`, `nama_fasilitas`, `deskripsi`, `warna_event`) VALUES
(1, 'Balai Warga', 'Ruang serbaguna untuk rapat dan acara warga.', '#0d6efd'),
(2, 'Lapangan Badminton', 'Lapangan outdoor untuk bermain badminton.', '#198754');
INSERT INTO `anggaran` (`tahun`, `kategori`, `jumlah_anggaran`) VALUES
(YEAR(CURDATE()), 'Kebersihan', 1200000.00),
(YEAR(CURDATE()), 'Keamanan', 2400000.00);
INSERT INTO `surat_templates` (`id`, `nama_template`, `judul_surat`, `konten`, `requires_parent_data`) VALUES
(1, 'Surat Keterangan Domisili', 'SURAT KETERANGAN DOMISILI', 'Yang bertanda tangan di bawah ini, Ketua RT {{app.housing_name}}, dengan ini menerangkan bahwa:\n\nNama Lengkap: {{surat.nama_lengkap}}\nNIK: {{surat.nik}}\nTanggal Lahir: {{surat.tgl_lahir_formatted}}\nPekerjaan: {{surat.pekerjaan}}\nAlamat: {{surat.alamat}}\n\nAdalah benar warga kami yang berdomisili di alamat tersebut di atas. Surat keterangan ini dibuat sebagai pengantar untuk keperluan:\n\n{{surat.keperluan}}\n\nDemikian surat keterangan domisili ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 0),
(2, 'Pengantar SKCK', 'SURAT PENGANTAR SKCK', 'Yang bertanda tangan di bawah ini, Ketua RT {{app.housing_name}}, dengan ini menerangkan bahwa:\n\nNama Lengkap: {{surat.nama_lengkap}}\nNIK: {{surat.nik}}\nAlamat: {{surat.alamat}}\n\nAdalah benar warga kami yang berdomisili di alamat tersebut di atas dan berkelakuan baik di lingkungan kami. Surat keterangan ini dibuat sebagai pengantar untuk mengurus SKCK di Kepolisian.\n\nKeperluan: {{surat.keperluan}}\n\nDemikian surat pengantar ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 0),
(3, 'Pengantar Nikah', 'SURAT PENGANTAR NIKAH', 'Yang bertanda tangan di bawah ini, Ketua RT {{app.housing_name}}, dengan ini menerangkan bahwa:\n\nI. DATA CALON\nNama Lengkap: {{surat.nama_lengkap}}\nNIK: {{surat.nik}}\nTanggal Lahir: {{surat.tgl_lahir_formatted}}\nAlamat: {{surat.alamat}}\n\nII. DATA ORANG TUA / WALI\nAyah:\nNama Lengkap: {{data_ayah.nama_lengkap}}\nNIK: {{data_ayah.nik}}\n\nIbu:\nNama Lengkap: {{data_ibu.nama_lengkap}}\nNIK: {{data_ibu.nik}}\n\nAdalah benar warga kami yang berdomisili di alamat tersebut di atas. Surat pengantar ini dibuat sebagai salah satu syarat administrasi untuk mengurus pernikahan.\n\nDemikian surat pengantar ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 1);