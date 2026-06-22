<?php
// Pastikan tidak ada spasi atau karakter sebelum tag <?php ini

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Hubungkan ke database
include '../config/koneksi.php';

// Memastikan koneksi aman dari scope global
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    echo "<script>
            alert('Gagal: Anda belum login atau session habis.');
            window.location.href = '../views/login.php';
          </script>";
    exit();
}

// --- BAGIAN DIAGNOSIS ROLE SESSION ---
// Kita longgarkan sementara pengecekan role agar semua user yang login bisa mencoba input,
// untuk memastikan apakah masalahnya ada di filter Role atau bukan.
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Admin'; 

// 2. Pastikan file ini diakses melalui submit form (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 3. Tangkap dan bersihkan data dari form modal di obat.php
    $nama_obat      = isset($_POST['nama_obat']) ? trim($_POST['nama_obat']) : '';
    $bentuk_sediaan = isset($_POST['bentuk_sediaan']) ? trim($_POST['bentuk_sediaan']) : '';
    $satuan         = isset($_POST['satuan']) ? trim($_POST['satuan']) : '';
    $kategori       = isset($_POST['kategori']) ? trim($_POST['kategori']) : '';
    
    // Validasi input wajib tidak kosong
    if (empty($nama_obat) || empty($satuan)) {
        echo "<script>
                alert('Gagal: Nama Obat dan Satuan wajib diisi!');
                window.location.href = '../views/obat.php';
              </script>";
        exit();
    }

    // Cek apakah koneksi database ($db) benar-benar ada
    if (!$db) {
        die("Error: Variabel koneksi database (\$koneksi atau \$db) tidak ditemukan atau bernilai null. Periksa file config/koneksi.php Anda.");
    }

    try {
        // Aktifkan error mode PDO agar jika query gagal, ia langsung melempar Exception yang terbaca
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // A. Cegah duplikasi nama obat yang sama di tabel master (Case-Insensitive)
        $checkObat = $db->prepare("SELECT obat_id FROM Obat WHERE LOWER(nama_obat) = LOWER(:nama)");
        $checkObat->execute([':nama' => $nama_obat]);
        if ($checkObat->fetch()) {
            echo "<script>
                    alert('Gagal: Obat dengan nama tersebut sudah terdaftar di sistem!');
                    window.location.href = '../views/obat.php';
                  </script>";
            exit();
        }

        // B. INSERT DATA KE TABEL MASTER OBAT (Murni 4 Kolom sesuai skema DDL Anda)
        $stmtInsert = $db->prepare("
            INSERT INTO Obat (nama_obat, bentuk_sediaan, satuan, kategori) 
            VALUES (:nama, :bentuk, :satuan, :kategori)
        ");
        
        $success = $stmtInsert->execute([
            ':nama'     => $nama_obat,
            ':bentuk'   => $bentuk_sediaan,
            ':satuan'   => $satuan,
            ':kategori' => $kategori
        ]);

        if ($success) {
            echo "<script>
                    alert('Data Master Obat berhasil ditambahkan ke database!');
                    window.location.href = '../views/obat.php?status=success';
                  </script>";
            exit();
        } else {
            echo "Gagal mengeksekusi query insert tanpa memicu PDOException.";
        }

    } catch (PDOException $e) {
        // JIKA EROR, TAMPILKAN DETAILNYA LANGSUNG DI LAYAR UNTUK TRACELOG
        echo "<h3>Terjadi Kesalahan Database SQL:</h3>";
        echo "Pesan Error: " . $e->getMessage() . "<br>";
        echo "Kode Error: " . $e->getCode() . "<br>";
        echo "<br><a href='../views/obat.php'>Kembali ke Halaman Obat</a>";
        exit();
    }

} else {
    header("Location: ../views/obat.php");
    exit();
}