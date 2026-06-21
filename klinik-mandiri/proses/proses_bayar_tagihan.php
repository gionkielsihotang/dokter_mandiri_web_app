<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Memanggil file konfigurasi database
include '../config/koneksi.php';

// Memastikan koneksi aman dari scope global
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

// 2. PROTEKSI KEAMANAN 1: Cek Autentikasi Login
if (!isset($_SESSION['username'])) {
    header("Location: ../views/login.php");
    exit();
}

// 3. PROTEKSI KEAMANAN 2: Otorisasi Berbasis Role (RBAC)
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';
if (!in_array($role_aktif, ['Admin', 'Resepsionis'])) {
    echo "<script>
            alert('Akses Ditolak! Anda tidak memiliki otoritas untuk memproses transaksi pembayaran.');
            window.location.href = '../views/tagihan.php';
          </script>";
    exit();
}

// Inisialisasi variabel penampung ID dan Metode
$tagihan_id = 0;
$metode = 'Tunai';

// Cek Skenario Akses (POST atau GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tagihan_id'])) {
    $tagihan_id = filter_var($_POST['tagihan_id'], FILTER_VALIDATE_INT);
    $metode     = isset($_POST['metode_pembayaran']) ? trim($_POST['metode_pembayaran']) : 'Tunai';
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $tagihan_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $metode     = 'Tunai';
}

// Jika ID tidak valid, tendang kembali
if (!$tagihan_id) {
    echo "<script>alert('ID Tagihan tidak valid!'); window.location.href = '../views/tagihan.php';</script>";
    exit();
}

try {
    // A. JALANKAN STORED PROCEDURE (Mendapatkan Total Tagihan)
    // Karena p_total sifatnya parameter penampung di SP, kita gunakan variabel MySQL @total
    $stmtSP = $db->prepare("CALL sp_hitung_tagihan(:id, @total)");
    $stmtSP->execute([':id' => $tagihan_id]);
    $stmtSP->closeCursor();

    // Mengambil nilai @total hasil kalkulasi Stored Procedure tadi
    $total_biaya = $db->query("SELECT @total AS total")->fetchColumn();

    // Jika SP mengembalikan -1 (efek Exception Handler di database Anda)
    if ($total_biaya == -1) {
        throw new PDOException("Stored Procedure mendeteksi kesalahan perhitungan internal.");
    }

    // B. UPDATE STATUS DI PHP (Mengubah status menjadi Lunas & menyimpan total biaya)
    $stmtUpdate = $db->prepare("
        UPDATE Tagihan 
        SET total_biaya = :total, 
            status_pembayaran = 'Lunas', 
            metode_pembayaran = :metode,
            tanggal_pembayaran = NOW()
        WHERE tagihan_id = :id
    ");
    
    $stmtUpdate->execute([
        ':total'  => $total_biaya,
        ':metode' => $metode,
        ':id'     => $tagihan_id
    ]);

    echo "<script>
            alert('Pembayaran sebesar Rp " . number_format($total_biaya, 0, ',', '.') . " berhasil diproses via Stored Procedure & Status diupdate ke LUNAS!');
            window.location.href = '../views/tagihan.php?status=success';
          </script>";
    exit();

} catch (PDOException $e) {
    error_log("Gagal Proses Pembayaran: " . $e->getMessage());
    echo "<script>
            alert('Gagal memproses pembayaran: " . addslashes($e->getMessage()) . "');
            window.location.href = '../views/tagihan.php';
          </script>";
    exit();
}