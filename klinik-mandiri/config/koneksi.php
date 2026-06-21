<?php
// Konfigurasi Database
$host     = "localhost";
$dbname   = "praktik_mandiri"; 
$username = "root";            
$password = "";                

try {
    // Membuka koneksi menggunakan PDO
    $koneksi = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Keamanan & Penanganan Error
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $koneksi->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Solusi jika database di-include dari file di dalam folder berbeda (Global Scope)
    $GLOBALS['koneksi'] = $koneksi;

} catch(PDOException $e) {
    // Catat error ke log server, jangan ekspos detail kredensial ke user umum jika di production
    error_log("Database Connection Error: " . $e->getMessage());
    
    die("Koneksi ke database gagal. Pastikan modul MySQL di XAMPP sudah menyala (Start).");
}
?>