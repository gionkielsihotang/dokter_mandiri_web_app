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

// Otorisasi dasar role pengguna
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';
$aksi = isset($_GET['aksi']) ? trim($_GET['aksi']) : '';

// =========================================================================
// JALUR A: PROSES HAPUS PASIEN (Menggunakan Parameter GET)
// =========================================================================
if ($aksi === 'hapus') {
    // Proteksi: Hanya Admin dan Resepsionis yang boleh menghapus
    if (!in_array($role_aktif, ['Admin', 'Resepsionis'])) {
        echo "<script>
                alert('Akses Ditolak! Anda tidak memiliki otoritas untuk menghapus data pasien.');
                window.location.href = '../views/pasien.php';
              </script>";
        exit();
    }

    $patient_id = isset($_GET['id']) ? trim($_GET['id']) : '';

    if (empty($patient_id)) {
        echo "<script>
                alert('Gagal: ID Pasien tidak terbaca oleh sistem!');
                window.location.href = '../views/pasien.php';
              </script>";
        exit();
    }

    try {
        // Cek relasi data: Apakah pasien memiliki riwayat kunjungan?
        $stmtCek = $db->prepare("SELECT COUNT(*) FROM Kunjungan WHERE patient_id = :id");
        $stmtCek->execute([':id' => $patient_id]);
        
        if ($stmtCek->fetchColumn() > 0) {
            echo "<script>
                    alert('Gagal: Pasien tidak bisa dihapus karena memiliki riwayat kunjungan medis aktif!');
                    window.location.href = '../views/pasien.php?status=error&msg=Pasien+memiliki+riwayat+kunjungan';
                  </script>";
            exit();
        }

        // Jalankan query hapus jika aman
        $queryHapus = $db->prepare("DELETE FROM Pasien WHERE patient_id = :id");
        $queryHapus->execute([':id' => $patient_id]);

        echo "<script>
                alert('Sukses: Data pasien dengan ID [" . $patient_id . "] berhasil dihapus!');
                window.location.href = '../views/pasien.php?status=success';
              </script>";
        exit();

    } catch (PDOException $e) {
        error_log("Gagal Hapus Pasien: " . $e->getMessage());
        echo "<script>
                alert('Error Database saat hapus: " . addslashes($e->getMessage()) . "');
                window.location.href = '../views/pasien.php';
              </script>";
        exit();
    }
}

// =========================================================================
// JALUR B: PROSES TAMBAH PASIEN BARU (Menggunakan Form POST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Otorisasi Tambah Data Pasien
    if (!in_array($role_aktif, ['Admin', 'Resepsionis', 'Dokter', 'Perawat'])) {
        echo "<script>
                alert('Akses Ditolak! Anda tidak memiliki otoritas untuk menambah data pasien.');
                window.location.href = '../views/pasien.php';
              </script>";
        exit();
    }

    // Tangkap data dari form modal
    $patient_id     = trim($_POST['patient_id'] ?? '');
    $nik            = trim($_POST['nik'] ?? '');
    $nama           = trim($_POST['nama'] ?? ''); 
    $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? ''); 
    $tgl_lahir      = trim($_POST['tgl_lahir'] ?? ''); 
    $no_telpon      = trim($_POST['no_telpon'] ?? ''); 
    $email          = trim($_POST['email'] ?? '');
    $asuransi_id    = trim($_POST['asuransi_id'] ?? ''); 
    $alamat         = trim($_POST['alamat'] ?? '');
    $kontak_darurat = trim($_POST['kontak_darurat'] ?? '');

    // Validasi Kolom Wajib Dasar
    if (empty($nik) || empty($nama) || empty($tgl_lahir) || empty($jenis_kelamin) || empty($no_telpon)) {
        echo "<script>
                alert('Gagal: Kolom NIK, Nama, Tanggal Lahir, Jenis Kelamin, dan No. Telepon wajib diisi!');
                window.location.href = '../views/pasien.php';
              </script>";
        exit();
    }

    try {
        // PERBAIKAN: Validasi NIK menggunakan Stored Function database fn_validasi_nik
        $stmtValidasiNik = $db->prepare("SELECT fn_validasi_nik(:nik) AS status_nik");
        $stmtValidasiNik->execute([':nik' => $nik]);
        $hasilValidasi = $stmtValidasiNik->fetch(PDO::FETCH_ASSOC);
        $status_nik = $hasilValidasi['status_nik'] ?? 'TIDAK VALID';

        if ($status_nik !== 'VALID') {
            $pesan_error = "Gagal: Nomor NIK tidak valid (Harus tepat 16 digit)!";
            if ($status_nik === 'ERROR' || $status_nik === 'WARNING') {
                $pesan_error = "Gagal: Terjadi kesalahan internal database saat memvalidasi NIK.";
            }
            echo "<script>
                    alert('" . $pesan_error . "');
                    window.location.href = '../views/pasien.php';
                  </script>";
            exit();
        }

        // Validasi Format No Telepon (Hanya Angka)
        $no_telp_bersih = preg_replace('/[^0-9]/', '', $no_telpon);
        if (strlen($no_telp_bersih) < 9 || strlen($no_telp_bersih) > 14) {
            echo "<script>
                    alert('Gagal: Format nomor telepon harus berupa 9-14 digit angka.');
                    window.location.href = '../views/pasien.php';
                  </script>";
            exit();
        }

        // Cek Duplikasi NIK Pasien
        $checkPasien = $db->prepare("SELECT patient_id FROM Pasien WHERE nik = :nik");
        $checkPasien->execute([':nik' => $nik]);
        if ($checkPasien->fetch()) {
            echo "<script>
                    alert('Peringatan: Pasien dengan NIK tersebut sudah terdaftar di sistem!');
                    window.location.href = '../views/pasien.php';
                  </script>";
            exit();
        }

        // Proses Penyusunan Query Tambah Pasien
        if (empty($patient_id)) {
            $query = $db->prepare("
                INSERT INTO Pasien (nik, nama, tgl_lahir, jenis_kelamin, alamat, no_telpon, email, kontak_darurat, asuransi_id) 
                VALUES (:nik, :nama, :tgl_lahir, :jk, :alamat, :telp, :email, :kontak, :asuransi)
            ");
            $params = [
                ':nik'         => $nik,
                ':nama'        => $nama,
                ':tgl_lahir'   => $tgl_lahir,
                ':jk'          => $jenis_kelamin,
                ':alamat'      => !empty($alamat) ? $alamat : null,
                ':telp'        => $no_telp_bersih,
                ':email'       => !empty($email) ? $email : null,
                ':kontak'      => !empty($kontak_darurat) ? $kontak_darurat : null,
                ':asuransi'    => !empty($asuransi_id) ? $asuransi_id : null
            ];
        } else {
            $query = $db->prepare("
                INSERT INTO Pasien (patient_id, nik, nama, tgl_lahir, jenis_kelamin, alamat, no_telpon, email, kontak_darurat, asuransi_id) 
                VALUES (:patient_id, :nik, :nama, :tgl_lahir, :jk, :alamat, :telp, :email, :kontak, :asuransi)
            ");
            $params = [
                ':patient_id' => $patient_id,
                ':nik'         => $nik,
                ':nama'        => $nama,
                ':tgl_lahir'   => $tgl_lahir,
                ':jk'          => $jenis_kelamin,
                ':alamat'      => !empty($alamat) ? $alamat : null,
                ':telp'        => $no_telp_bersih,
                ':email'       => !empty($email) ? $email : null,
                ':kontak'      => !empty($kontak_darurat) ? $kontak_darurat : null,
                ':asuransi'    => !empty($asuransi_id) ? $asuransi_id : null
            ];
        }
        
        $query->execute($params);

        echo "<script>
                alert('Data Pasien Baru berhasil didaftarkan ke sistem!');
                window.location.href = '../views/pasien.php?status=success';
              </script>";
        exit();

    } catch (PDOException $e) {
        error_log("Gagal Tambah Pasien: " . $e->getMessage());
        echo "<script>
                alert('Terjadi kesalahan database: " . addslashes($e->getMessage()) . "');
                window.location.href = '../views/pasien.php';
              </script>";
        exit();
    }
}

header("Location: ../views/pasien.php");
exit();