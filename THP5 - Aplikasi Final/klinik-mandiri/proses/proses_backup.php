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

try {
    // Ambil daftar semua tabel di database saat ini
    $tables = array();
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sqlScript = "-- Backup Database Dokter Mandiri\n";
    $sqlScript .= "-- Waktu Kejadian: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Looping setiap tabel untuk mengambil struktur dan datanya
    foreach ($tables as $table) {
        // 1. Ambil query pembuatan tabel (CREATE TABLE)
        $query = $db->query("SHOW CREATE TABLE `$table`");
        $row = $query->fetch(PDO::FETCH_NUM);
        
        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlScript .= $row[1] . ";\n\n";
        
        // 2. Ambil data di dalam tabel (INSERT INTO)
        $queryData = $db->query("SELECT * FROM `$table`");
        $columnCount = $queryData->columnCount();
        
        while ($rowRecord = $queryData->fetch(PDO::FETCH_NUM)) {
            $sqlScript .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $columnCount; $j++) {
                if (isset($rowRecord[$j])) {
                    // Escape karakter string agar aman dibaca kembali oleh SQL
                    $value = str_replace("\n", "\\n", addslashes($rowRecord[$j]));
                    $sqlScript .= '"' . $value . '"';
                } else {
                    $sqlScript .= 'NULL';
                }
                if ($j < ($columnCount - 1)) {
                    $sqlScript .= ',';
                }
            }
            $sqlScript .= ");\n";
        }
        $sqlScript .= "\n";
    }
    
    $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Mengubah script teks menjadi unduhan otomatis di browser
    if (!empty($sqlScript)) {
        $backup_file_name = "backup_dokter_mandiri_" . date('Ymd_His') . ".sql";
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . $backup_file_name . "\"");
        echo $sqlScript;
        exit;
    }

} catch (Exception $e) {
    header("Location: ../views/utility.php?status=error&msg=" . urlencode($e->getMessage()));
    exit();
}