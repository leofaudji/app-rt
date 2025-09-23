<?php
// bootstrap.php sudah di-require oleh index.php, jadi kita tidak perlu me-require-nya lagi.
// session_start() juga sudah dipanggil di index.php.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('/login'));
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Username dan password tidak boleh kosong.';
    header('Location: ' . base_url('/login'));
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("SELECT id, username, password, role, nama_lengkap FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        // Login berhasil
        session_regenerate_id(true); // Mencegah session fixation
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];

        log_activity($user['username'], 'Login', 'Login berhasil.');

        // Redirect ke halaman dashboard yang sebenarnya
        header('Location: ' . base_url('/dashboard'));
        exit;
    } else {
        // Jika login via tabel 'users' gagal, coba otentikasi via tabel 'warga'
        // untuk login pertama kali.
        $stmt_warga = $conn->prepare("SELECT id, nama_lengkap, nama_panggilan, tgl_lahir FROM warga WHERE nama_panggilan = ?");
        $stmt_warga->bind_param("s", $username);
        $stmt_warga->execute();
        $warga = $stmt_warga->get_result()->fetch_assoc();
        $stmt_warga->close();

        if ($warga && !empty($warga['tgl_lahir'])) {
            // Cek apakah password cocok dengan format tanggal lahir (ddmmyyyy)
            $password_from_db = date('dmY', strtotime($warga['tgl_lahir']));
            if ($password === $password_from_db) {
                // Password cocok, ini adalah login pertama kali yang valid.
                // Buat akun di tabel 'users' (Just-In-Time Provisioning).
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'warga';
                
                $stmt_create_user = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
                $stmt_create_user->bind_param("ssss", $warga['nama_panggilan'], $password_hash, $warga['nama_lengkap'], $role);
                $stmt_create_user->execute();
                $new_user_id = $stmt_create_user->insert_id;
                $stmt_create_user->close();

                // Login berhasil
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['username'] = $warga['nama_panggilan'];
                $_SESSION['nama_lengkap'] = $warga['nama_lengkap'];
                $_SESSION['role'] = $role;

                log_activity($warga['nama_panggilan'], 'Login', 'Login pertama kali berhasil, akun dibuat.');
                header('Location: ' . base_url('/dashboard'));
                exit;
            }
        }

        // Jika semua upaya login gagal
        $_SESSION['login_error'] = 'Username atau password salah.';
        log_activity($username, 'Login Gagal', 'Percobaan login gagal.');
        header('Location: ' . base_url('/login'));
        exit;
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['login_error'] = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
    header('Location: ' . base_url('/login'));
    exit;
}