<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../views/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_sql'])) {
    
    $file_tmp = $_FILES['file_sql']['tmp_name'];
    $file_name = $_FILES['file_sql']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validasi ekstensi berkas wajib berupa file .sql
    if ($file_ext !== 'sql') {
        header("Location: ../views/utility.php?status=error&msg=" . urlencode("Format file tidak valid. Harus berekstensi .sql"));
        exit();
    }

    try {
        // Membaca konten file .sql ke dalam string teks
        $sql_content = file_get_contents($file_tmp);
        
        // Mematikan foreign key checks sementara agar tidak terjadi error relasi antar tabel saat proses drop/insert
        $db->exec("SET FOREIGN_KEY_CHECKS=0;");

        // Mengeksekusi seluruh isi script SQL ke database
        $db->exec($sql_content);

        // Menghidupkan kembali foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS=1;");

        header("Location: ../views/utility.php?status=restore_success");
        exit();

    } catch (PDOException $e) {
        error_log("Error Restore Database: " . $e->getMessage());
        header("Location: ../views/utility.php?status=error&msg=" . urlencode("Gagal memproses isi SQL. Periksa kompatibilitas file cadangan Anda."));
        exit();
    }
} else {
    header("Location: ../views/utility.php");
    exit();
}