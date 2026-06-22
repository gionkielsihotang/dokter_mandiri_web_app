<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

if (!isset($_SESSION['username'])) {
    header("Location: ../views/login.php");
    exit();
}

$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';
if (!in_array($role_aktif, ['Admin', 'Dokter', 'Apoteker'])) {
    echo "<script>
            alert('Akses Ditolak! Anda tidak memiliki otoritas untuk mengelola resep obat.');
            window.location.href = '../views/resep.php';
          </script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $record_id      = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT); 
    $obat_id        = filter_var($_POST['obat_id'] ?? null, FILTER_VALIDATE_INT);
    $status_resep   = isset($_POST['status_resep']) ? trim($_POST['status_resep']) : 'Diproses'; 
    $doctor_id      = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 
    $jumlah_obat    = isset($_POST['jumlah']) ? filter_var($_POST['jumlah'], FILTER_VALIDATE_INT) : 1;
    $catatan_dokter = trim($_POST['catatan_dokter'] ?? '-');

    if (!$record_id || !$obat_id || !$jumlah_obat) {
        echo "<script>
                alert('Gagal: Rekam Medis, Obat, dan Jumlah wajib valid!');
                window.location.href = '../views/resep.php';
              </script>";
        exit();
    }

    try {
        // [MEMPERTAHANKAN FUNCTION] 1. Cek stok obat fisik via fn_cek_stok_obat
        $stmtCekStok = $db->prepare("SELECT fn_cek_stok_obat(:obat_id) AS sisa_stok");
        $stmtCekStok->execute([':obat_id' => $obat_id]);
        $resStok = $stmtCekStok->fetch(PDO::FETCH_ASSOC);

        if ($resStok && $resStok['sisa_stok'] < $jumlah_obat) {
            echo "<script>
                    alert('Gagal: Stok obat tidak mencukupi untuk memenuhi jumlah resep ini!');
                    window.location.href = '../views/resep.php';
                  </script>";
            exit();
        }

        // [MEMPERTAHANKAN STORED PROCEDURE 1] 2. CALL sp_buat_resep
        $query_resep = $db->prepare("CALL sp_buat_resep(:record_id, :doctor_id)");
        $query_resep->execute([
            ':record_id' => $record_id,
            ':doctor_id' => $doctor_id
        ]);
        $query_resep->closeCursor(); 

        // 3. Ambil ID resep terakhir hasil generate SP untuk dipasangkan catatannya
        $stmtId = $db->prepare("SELECT resep_id FROM Resep WHERE record_id = :record_id ORDER BY resep_id DESC LIMIT 1");
        $stmtId->execute([':record_id' => $record_id]);
        $new_resep_id = $stmtId->fetchColumn();

        if (!$new_resep_id) {
            throw new PDOException("Gagal mendeteksi Resep ID yang baru dibuat.");
        }

        // 4. Update data Catatan Dokter dari form ke Resep induk yang dibuat SP
        $stmtUpdateCatatan = $db->prepare("UPDATE Resep SET catatan_dokter = :catatan, status_resep = :status_r WHERE resep_id = :resep_id");
        $stmtUpdateCatatan->execute([
            ':catatan'  => $catatan_dokter,
            ':status_r' => $status_resep,
            ':resep_id' => $new_resep_id
        ]);

        // [MEMPERTAHANKAN STORED PROCEDURE 2] 5. CALL sp_tambah_detail_resep
        $query_detail = $db->prepare("CALL sp_tambah_detail_resep(:resep_id, :obat_id, :jumlah)");
        $query_detail->execute([
            ':resep_id' => $new_resep_id,
            ':obat_id'  => $obat_id,
            ':jumlah'   => $jumlah_obat
        ]);
        $query_detail->closeCursor(); 

        // [MEMPERTAHANKAN STORED PROCEDURE 3] 6. CALL sp_kurangi_stok_obat
        if (in_array(strtolower($status_resep), ['selesai', 'diproses'])) {
            $v_batch_id  = 1; 
            $v_lokasi_id = 1; 

            $query_stok = $db->prepare("CALL sp_kurangi_stok_obat(:batch_id, :lokasi_id, :jumlah)");
            $query_stok->execute([
                ':batch_id'  => $v_batch_id,
                ':lokasi_id' => $v_lokasi_id,
                ':jumlah'    => $jumlah_obat
            ]);
            $query_stok->closeCursor(); 
        }

        echo "<script>
                alert('Resep dan detail obat berhasil diproses berantai serta stok gudang telah dipotong!');
                window.location.href = '../views/resep.php?status=success';
              </script>";
        exit();

    } catch (PDOException $e) {
        error_log("Gagal Tambah Resep (SP): " . $e->getMessage());
        $pesan_error = $e->getMessage();
        
        if (strpos($pesan_error, 'tidak mencukupi') !== false) {
            $pesan_error = "Gagal: Alokasi stok fisik obat pada nomor batch/lokasi tersebut tidak mencukupi.";
        } elseif (strpos($pesan_error, 'tidak ditemukan') !== false) {
            $pesan_error = "Gagal: Referensi data resep atau master obat tidak ditemukan.";
        } else {
            $pesan_error = "Terjadi kesalahan database saat memproses resep: " . $e->getMessage();
        }

        echo "<script>
                alert('" . addslashes($pesan_error) . "');
                window.location.href = '../views/resep.php';
              </script>";
        exit();
    }
} else {
    header("Location: ../views/resep.php");
    exit();
}