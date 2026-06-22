<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Hubungkan ke database
include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

// Keamanan: Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../views/login.php");
    exit();
}

// PROTEKSI KETAT: Pengisian rekam medis hanya boleh dilakukan oleh Dokter atau Admin
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';
if (!in_array($role_aktif, ['Admin', 'Dokter'])) {
    echo "<script>
            alert('Akses Ditolak! Anda tidak memiliki otoritas medically untuk mengisi rekam medis.');
            window.location.href = '../views/rekam_medis.php';
          </script>";
    exit();
}

// 2. Pastikan file ini diakses melalui submit form (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Tangkap semua input dari form modal rekam_medis.php
    $visit_id             = filter_var($_POST['visit_id'] ?? null, FILTER_VALIDATE_INT);
    $tekanan_darah        = trim($_POST['tekanan_darah'] ?? '-');
    $nadi                 = trim($_POST['nadi'] ?? '-');
    $suhu                 = trim($_POST['suhu'] ?? '-');
    $tinggi_badan         = trim($_POST['tinggi_badan'] ?? '0');
    $berat_badan          = trim($_POST['berat_badan'] ?? '0');
    $anamnesa             = trim($_POST['anamnesa'] ?? '-');
    $pemeriksaan_fisik    = trim($_POST['pemeriksaan_fisik'] ?? '-');
    $catatan_klinis       = trim($_POST['catatan_klinis'] ?? '-');
    $riwayat_penyakit     = trim($_POST['riwayat_penyakit'] ?? '-');
    $alergi_obat_makanan  = trim($_POST['alergi_obat_makanan'] ?? '-');

    // Validasi dasar field penting
    if (!$visit_id || empty($catatan_klinis) || empty($anamnesa)) {
        echo "<script>
                alert('Gagal: ID Kunjungan, Anamnesa, dan Catatan Klinis wajib diisi!');
                window.location.href = '../views/rekam_medis.php';
              </script>";
        exit();
    }

    try {
        // =========================================================================
        // SOLUSI BYPASS: INSERT LANGSUNG SECARA TERSTRUKTUR KE KOLOM MASING-MASING
        // =========================================================================
        // Langkah ini dilakukan untuk menghindari error 1644 dari Stored Procedure Anda
        $vital_summary = "TD: " . $tekanan_darah . ", N: " . $nadi . ", S: " . $suhu;
        
        $sqlInsertDirect = "INSERT INTO rekam_medis (
                                tanggal_catatan, 
                                visit_id, 
                                vital_summary, 
                                tinggi_badan, 
                                berat_badan, 
                                anamnesa, 
                                pemeriksaan_fisik, 
                                catatan_klinis, 
                                riwayat_penyakit, 
                                alergi_obat_makanan
                            ) VALUES (
                                CURDATE(), 
                                :visit_id, 
                                :vital, 
                                :tb, 
                                :bb, 
                                :anamnesa, 
                                :fisik, 
                                :catatan, 
                                :penyakit, 
                                :alergi
                            )";
                      
        $stmtDirect = $db->prepare($sqlInsertDirect);
        $stmtDirect->execute([
            ':visit_id'  => $visit_id,
            ':vital'     => $vital_summary,
            ':tb'        => $tinggi_badan,
            ':bb'        => $berat_badan,
            ':anamnesa'  => $anamnesa,
            ':fisik'     => $pemeriksaan_fisik,
            ':catatan'   => $catatan_klinis,
            ':penyakit'  => $riwayat_penyakit,
            ':alergi'    => $alergi_obat_makanan
        ]);

        echo "<script>
                alert('Data Rekam Medis berhasil disimpan langsung ke kolom masing-masing!');
                window.location.href = '../views/rekam_medis.php?status=success';
              </script>";
        exit();

    } catch (PDOException $e) {
        error_log("Gagal Simpan Direct RM: " . $e->getMessage());
        $pesan_error = $e->getMessage();
        
        // Membantu mendeteksi jika visit_id duplikat secara spesifik
        if (strpos($pesan_error, 'Duplicate entry') !== false) {
            $pesan_error = "Gagal: Visit ID (" . $visit_id . ") sudah memiliki catatan rekam medis sebelumnya!";
        }
        
        echo "<script>
                alert('Gagal menyimpan data langsung: " . addslashes($pesan_error) . "');
                window.location.href = '../views/rekam_medis.php';
              </script>";
        exit();
    }

} else {
    header("Location: ../views/rekam_medis.php");
    exit();
}