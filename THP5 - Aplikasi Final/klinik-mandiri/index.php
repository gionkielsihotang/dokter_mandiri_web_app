<?php
// Memulai session sistem
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['username']) && !empty(trim($_SESSION['username']))) {
    // Jika sesi terdeteksi valid, arahkan ke area internal (Dashboard)
    header("Location: views/dashboard.php");
    exit();
} else {
    // Jika tidak ada sesi aktif, paksa masuk ke halaman autentikasi (Login)
    header("Location: views/login.php");
    exit();
}