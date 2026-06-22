<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Hapus semua data variabel session di server
$_SESSION = array();

// 2. Jika session menggunakan cookie (standar PHP), hancurkan cookie-nya
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session sepenuhnya di sisi server
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Logging Out...</title>
    <script>
        // 4. Bersihkan localStorage yang dipakai oleh main.js front-end Anda
        localStorage.removeItem("userLogin");
        
        // 5. Alihkan halaman langsung ke views/login.php
        window.location.href = "../views/login.php";
    </script>
    <?php
    // FALLBACK SAFETY: Jika JavaScript di browser mati, PHP yang akan memaksa pindah halaman dalam 0 detik
    header("Refresh: 0; URL=../views/login.php");
    exit();
    ?>
</head>
<body>
    <p style="text-align: center; margin-top: 50px; font-family: sans-serif; color: #7f8c8d;">
        Sedang keluar dari sistem, mohon tunggu...
    </p>
</body>
</html>