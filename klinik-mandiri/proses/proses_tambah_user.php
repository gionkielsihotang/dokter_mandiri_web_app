<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

// 1. Proteksi Login
if (!isset($_SESSION['username'])) {
    header("Location: ../views/login.php");
    exit();
}

// 2. Proteksi Hak Akses Admin
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';
if ($role_aktif !== 'Admin') {
    die("Akses ditolak! Anda tidak memiliki hak akses Administrator.");
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // ==========================================
    // AKSI 1: TAMBAH USER (STRUKTUR PAS SESUAI DDL)
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'tambah') {
        $nama_lengkap = trim($_POST['nama']); // Menangkap input name="nama"
        $username     = trim($_POST['username']);
        $password     = $_POST['password'];
        $kontak       = trim($_POST['kontak']);
        $id_role      = filter_var($_POST['id_role'], FILTER_VALIDATE_INT);

        if (empty($nama_lengkap) || empty($username) || empty($password) || !$id_role) {
            die("Gagal: Semua kolom formulir wajib diisi!");
        }

        // PENGAMAN INTEGRITAS: Jika tabel Role kosong, isi dulu otomatis demi lancarnya FK constraint
        $checkRole = $db->query("SELECT COUNT(*) FROM Role")->fetchColumn();
        if ($checkRole == 0) {
            $db->query("INSERT INTO Role (id_role, nama_role) VALUES 
                (1, 'Admin'), (2, 'Dokter Spesialis'), (3, 'Perawat'), (4, 'Apoteker'), (5, 'Kasir')");
        }

        // Cek apakah username ganda
        $checkUsername = $db->prepare("SELECT user_id FROM User WHERE LOWER(username) = LOWER(:username)");
        $checkUsername->execute([':username' => $username]);
        if ($checkUsername->fetch()) {
            echo "<script>alert('Gagal: Username \'$username\' sudah terdaftar!'); window.location.href = '../views/users.php';</script>";
            exit();
        }

        $password_secure = password_hash($password, PASSWORD_BCRYPT);

        // Query insert disesuaikan dengan struktur kolom tabel User Anda (nama, username, password, kontak, id_role)
        $query = $db->prepare("INSERT INTO User (nama, username, password, kontak, id_role) VALUES (:nama, :username, :password, :kontak, :id_role)");
        $query->execute([
            ':nama'     => $nama_lengkap,
            ':username' => $username,
            ':password' => $password_secure,
            ':kontak'   => $kontak,
            ':id_role'  => $id_role
        ]);

        echo "<script>alert('User baru berhasil disimpan PERMANEN ke database!'); window.location.href = '../views/users.php';</script>";
        exit();

    // ==========================================
    // AKSI 2: RESET PASSWORD (Default: 123456)
    // ==========================================
    } elseif ($aksi === 'reset' && isset($_GET['id'])) {
        $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        $password_default = password_hash("123456", PASSWORD_BCRYPT);
        
        $query = $db->prepare("UPDATE User SET password = :password WHERE user_id = :id");
        $query->execute([':password' => $password_default, ':id' => $user_id]);

        echo "<script>alert('Password berhasil di-reset menjadi default: 123456'); window.location.href = '../views/users.php';</script>";
        exit();

    // ==========================================
    // AKSI 3: HAPUS USER
    // ==========================================
    } elseif ($aksi === 'hapus' && isset($_GET['id'])) {
        $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

        $query = $db->prepare("DELETE FROM User WHERE user_id = :id");
        $query->execute([':id' => $user_id]);

        echo "<script>alert('User berhasil dihapus secara permanen dari database!'); window.location.href = '../views/users.php';</script>";
        exit();
    }

} catch (PDOException $e) {
    die("Database menolak menyimpan data. Alasan Error: " . $e->getMessage());
}