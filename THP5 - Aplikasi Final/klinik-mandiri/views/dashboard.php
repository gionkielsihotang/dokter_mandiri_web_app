<?php
session_start();

// [FITUR WAJIB] Authentication & Authorization
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';

// Menghubungkan ke database dengan aman
include '../config/koneksi.php';

// Memastikan koneksi aman dari scope global
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

// Inisialisasi data default (Fallback aman jika database kosong / error)
$total_pasien = 0;
$tagihan_belum_selesai = 0;
$pre_order_obat = 0;
$pendapatan_bulan_ini = 0;
$daftar_kunjungan = [];
$error_kunjungan = null; // Penampung debug jika kueri gagal

if (isset($db)) {
    // 1. Ambil Total Pasien Nyata (Sesuai nama tabel master: Pasien)
    try {
        $q_pasien = $db->query("SELECT COUNT(*) as total FROM Pasien");
        $res_pasien = $q_pasien->fetch();
        if ($res_pasien) {
            $total_pasien = $res_pasien['total'];
        }
    } catch (PDOException $e) {
        error_log("Dashboard Pasien Error: " . $e->getMessage());
    }

    // 2. Ambil Tagihan Belum Selesai (Sesuai nama tabel master: Tagihan)
    try {
        $q_tagihan = $db->query("SELECT COUNT(*) as total FROM Tagihan WHERE status != 'Lunas'");
        $res_tagihan = $q_tagihan->fetch();
        if ($res_tagihan) {
            $tagihan_belum_selesai = $res_tagihan['total'];
        }
    } catch (PDOException $e) {
        error_log("Dashboard Tagihan Error: " . $e->getMessage());
    }

    // 3. Ambil Pre Order Obat secara Dinamis (Sesuai nama tabel master: PO)
    try {
        $q_po = $db->query("SELECT COUNT(*) as total FROM PO");
        $res_po = $q_po->fetch();
        if ($res_po) {
            $pre_order_obat = $res_po['total'];
        }
    } catch (PDOException $e) {
        error_log("Dashboard PO Error: " . $e->getMessage());
    }

    // 4. Ambil Total Pendapatan Bulan Ini (Akurat: Menjumlahkan harga_satuan dari Detail_Tagihan untuk Tagihan yang Lunas)
    try {
        $q_pendapatan = $db->query("SELECT SUM(dt.harga_satuan) as total 
                                    FROM Tagihan t
                                    JOIN Detail_Tagihan dt ON t.tagihan_id = dt.tagihan_id
                                    WHERE t.status = 'Lunas' 
                                    AND MONTH(t.tanggal_tagihan) = MONTH(CURRENT_DATE()) 
                                    AND YEAR(t.tanggal_tagihan) = YEAR(CURRENT_DATE())");
        $res_pendapatan = $q_pendapatan->fetch();
        if ($res_pendapatan && $res_pendapatan['total'] != null) {
            $pendapatan_bulan_ini = $res_pendapatan['total'];
        }
    } catch (PDOException $e) {
        error_log("Dashboard Pendapatan Error: " . $e->getMessage());
        $pendapatan_bulan_ini = 0; 
    }

    // 5. Ambil Daftar Kunjungan Hari Ini (Menggunakan DATE(k.waktu_datang) untuk mendapatkan tanggal hari ini)
    try {
        $query_kunjungan = $db->prepare("SELECT 
                                            k.patient_id, 
                                            p.nama AS nama_lengkap, 
                                            DATE_FORMAT(k.waktu_datang, '%H:%i') AS jam_masuk, 
                                            k.status 
                                         FROM Kunjungan k 
                                         JOIN Pasien p ON k.patient_id = p.patient_id 
                                         WHERE DATE(k.waktu_datang) = CURDATE() 
                                         ORDER BY k.waktu_datang ASC 
                                         LIMIT 5");
        $query_kunjungan->execute();
        $daftar_kunjungan = $query_kunjungan->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Dashboard Kunjungan Error: " . $e->getMessage());
        $error_kunjungan = $e->getMessage();
        $daftar_kunjungan = [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Dokter Mandiri</title>
    <link rel="stylesheet" href="/klinik-app/assets/style.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">Dokter Mandiri</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                
                <li><a href="pasien.php">Manajemen Pasien</a></li>
                <li><a href="kunjungan.php">Jadwal / Kunjungan</a></li>
                
                <?php if($role_aktif == 'Admin' || $role_aktif == 'Dokder' || $role_aktif == 'Dokter'): ?>
                    <li><a href="rekam_medis.php">Rekam Medis</a></li>
                    <li><a href="resep.php">Resep & Dispensing</a></li>
                <?php endif; ?>
                
                <?php if($role_aktif == 'Admin' || $role_aktif == 'Apoteker'): ?>
                    <li><a href="obat.php">Obat & Stok</a></li>
                <?php endif; ?>
                
                <?php if($role_aktif == 'Admin' || $role_aktif == 'Resepsionis'): ?>
                    <li><a href="tagihan.php">Tagihan</a></li>
                <?php endif; ?>
                
                <li><a href="laporan.php">Laporan</a></li>
                
                <?php if($role_aktif == 'Admin'): ?>
                    <li><a href="users.php">User Management</a></li>
                    <li><a href="utility.php">Backup & Restore</a></li>
                <?php endif; ?>
                
                <li><a href="#" onclick="logout(); return false;">Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <header class="topbar">
                <div>Halo, <strong id="userDisplay"><?= htmlspecialchars($_SESSION['username']); ?></strong> 
                <span style="font-size: 0.85rem; color: #7f8c8d; margin-left: 5px;">(<?= htmlspecialchars($role_aktif); ?>)</span></div>
            </header>

            <main class="content-area">
                <h2>Summary</h2>
                <br>
                <div class="dashboard-cards">
                    <div class="card">
                        <h3>Total Pasien</h3>
                        <p><?= htmlspecialchars($total_pasien); ?></p>
                    </div>
                    <div class="card">
                        <h3>Tagihan Belum Selesai</h3>
                        <p><?= htmlspecialchars($tagihan_belum_selesai); ?></p>
                    </div>
                    <div class="card">
                        <h3>Pre Order Obat</h3>
                        <p><?= htmlspecialchars($pre_order_obat); ?></p>
                    </div>
                    <div class="card">
                        <h3>Pendapatan Bulan Ini</h3>
                        <p>Rp <?= number_format($pendapatan_bulan_ini, 0, ',', '.'); ?></p>
                    </div>
                </div>

                <div class="table-container" style="margin-top: 2rem;">
                    <h3>Daftar Kunjungan Hari Ini</h3>
                    <br>
                    <table>
                        <thead>
                            <tr>
                                <th>ID Pasien</th>
                                <th>Nama Pasien</th>
                                <th>Waktu</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_kunjungan)): ?>
                                <?php foreach ($daftar_kunjungan as $kunj): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($kunj['patient_id']); ?></td>
                                        <td><?= htmlspecialchars($kunj['nama_lengkap']); ?></td>
                                        <td>
                                            <?php 
                                                echo !empty($kunj['jam_masuk']) ? htmlspecialchars($kunj['jam_masuk']) : '--:--';
                                            ?> WIB
                                        </td>
                                        <td><?= htmlspecialchars($kunj['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #7f8c8d; padding: 15px;">
                                        Tidak ada data kunjungan pasien untuk hari ini.
                                        <?php if ($error_kunjungan): ?>
                                            <br><span style="color: #dc3545; font-size: 0.8rem;">[Malfungsi Sistem: <?= htmlspecialchars($error_kunjungan); ?>]</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setupUserSession();
        });

        function setupUserSession() {
            const userDisplay = document.getElementById("userDisplay");
            if(userDisplay.textContent.trim() === "") {
                const userLogin = localStorage.getItem("userLogin");
                if (!userLogin) {
                    localStorage.setItem("userLogin", "Pengguna");
                    if (userDisplay) userDisplay.textContent = "Pengguna";
                } else {
                    if (userDisplay) userDisplay.textContent = userLogin;
                }
            }
        }

        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                localStorage.removeItem("userLogin");
                showNotification("Logout berhasil", "success");
                setTimeout(() => {
                    window.location.href = "../proses/proses_logout.php";
                }, 1000);
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement("div");
            notification.textContent = message;
            let bgColor = "#27ae60";
            if (type === "error") bgColor = "#dc3545";
            if (type === "info") bgColor = "#3498db";
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background-color: ${bgColor};
                color: white;
                border-radius: 4px;
                z-index: 10000;
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 2000);
        }
    </script>
    <script src="/klinik-app/assets/main.js"></script>
</body>
</html>