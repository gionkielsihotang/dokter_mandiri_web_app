<?php
session_start();

// [FITUR WAJIB] Authentication & Authorization
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';

// Menghubungkan ke database
include '../config/koneksi.php';

// [FITUR WAJIB] Input Validation & Sanitization (Mencegah manipulasi input filter)
$jenis_laporan = isset($_GET['jenis_laporan']) ? trim(filter_var($_GET['jenis_laporan'], FILTER_SANITIZE_SPECIAL_CHARS)) : '';
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';

// Validasi format tanggal (YYYY-MM-DD) jika diisi user
if (!empty($dari_tanggal) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari_tanggal)) { $dari_tanggal = ''; }
if (!empty($sampai_tanggal) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai_tanggal)) { $sampai_tanggal = ''; }

$daftar_laporan = [];
$opsi_jenis_laporan = [];
$is_filtered = (!empty($jenis_laporan) || !empty($dari_tanggal) || !empty($sampai_tanggal));
$total_data_asli = 0;
$error_message = null;

try {
    if (isset($koneksi)) {
        // Ambil daftar jenis laporan secara dinamis
        $query_jenis = $koneksi->query("SELECT DISTINCT jenis_laporan FROM laporan_eksternal WHERE jenis_laporan IS NOT NULL AND jenis_laporan != ''");
        $opsi_jenis_laporan = $query_jenis->fetchAll(PDO::FETCH_COLUMN);

        // Cek total data asli di database
        $cek_total = $koneksi->query("SELECT COUNT(*) FROM laporan_eksternal");
        $total_data_asli = $cek_total->fetchColumn();

        // Kueri pencarian data laporan eksternal
        $sql = "SELECT * FROM laporan_eksternal WHERE 1=1";
        $params = [];

        if (!empty($jenis_laporan)) {
            $sql .= " AND jenis_laporan = :jenis";
            $params[':jenis'] = $jenis_laporan;
        }
        if (!empty($dari_tanggal)) {
            $sql .= " AND tanggal_kirim >= :dari";
            $params[':dari'] = $dari_tanggal;
        }
        if (!empty($sampai_tanggal)) {
            $sql .= " AND tanggal_kirim <= :sampai";
            $params[':sampai'] = $sampai_tanggal;
        }

        $sql .= " ORDER BY laporan_id DESC";
        
        $query = $koneksi->prepare($sql);
        $query->execute($params);
        $daftar_laporan = $query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // [FITUR WAJIB] User-friendly Error Handling
    error_log("Database Error pada laporan.php: " . $e->getMessage());
    $error_message = "Gagal memuat data laporan karena gangguan internal sistem.";
    $daftar_laporan = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - Dokter Mandiri</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">Dokter Mandiri</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="pasien.php">Manajemen Pasien</a></li>
                <li><a href="kunjungan.php">Jadwal / Kunjungan</a></li>
                
                <?php if (in_array($role_aktif, ['Admin', 'Doctor', 'Dokter'])): ?>
                    <li><a href="rekam_medis.php">Rekam Medis</a></li>
                <?php endif; ?>
                
                <?php if (in_array($role_aktif, ['Admin', 'Doctor', 'Dokter', 'Apoteker'])): ?>
                    <li><a href="resep.php">Resep & Dispensing</a></li>
                <?php endif; ?>
                
                <?php if (in_array($role_aktif, ['Admin', 'Apoteker'])): ?>
                    <li><a href="obat.php">Obat & Stok</a></li>
                <?php endif; ?>
                
                <?php if (in_array($role_aktif, ['Admin', 'Resepsionis'])): ?>
                    <li><a href="tagihan.php">Tagihan</a></li>
                <?php endif; ?>
                
                <li><a href="laporan.php" class="active">Laporan</a></li>
                
                <?php if ($role_aktif === 'Admin'): ?>
                    <li><a href="users.php">User Management</a></li>
                    <li><a href="utility.php">Backup & Restore</a></li>
                <?php endif; ?>
                
                <li><a href="#" onclick="logout(); return false;">Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <header class="topbar">
                <div>Halo, <strong id="userDisplay"><?= htmlspecialchars($_SESSION['username'] ?? ''); ?></strong> <span style="font-size: 0.85rem; color: #7f8c8d;">(<?= htmlspecialchars($role_aktif); ?>)</span></div>
            </header>

            <main class="content-area">
                <div class="table-container" style="margin-bottom: 2rem;">
                    <h2>Filter Laporan</h2><br>
                    <form method="GET" action="laporan.php" style="display: flex; gap: 1rem; align-items: flex-end;">
                        <div class="form-group" style="margin: 0;">
                            <label>Jenis Laporan</label>
                            <select name="jenis_laporan" class="form-control">
                                <option value="">-- Semua Laporan --</option>
                                <?php foreach ($opsi_jenis_laporan as $opsi): ?>
                                    <option value="<?= htmlspecialchars($opsi); ?>" <?= $jenis_laporan == $opsi ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($opsi); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Dari Tanggal</label>
                            <input type="date" name="dari_tanggal" class="form-control" value="<?= htmlspecialchars($dari_tanggal); ?>">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Sampai Tanggal</label>
                            <input type="date" name="sampai_tanggal" class="form-control" value="<?= htmlspecialchars($sampai_tanggal); ?>">
                        </div>
                        <button type="submit" class="btn btn-small" style="height: 38px;">Tampilkan Data</button>
                    </form>
                </div>

                <div class="table-container">
                    <h3>Daftar Dokumen Laporan</h3><br>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                            <?= htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Jenis Laporan</th>
                                <th>Penerima</th>
                                <th>Tanggal Kirim</th>
                                <th>Nama File</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_laporan)): ?>
                                <?php foreach ($daftar_laporan as $lap): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($lap['jenis_laporan'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($lap['tujuan'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($lap['tanggal_kirim'] ?? '-'); ?></td>
                                        <td>
                                            <a href="../Data/Laporan/<?= htmlspecialchars($lap['file_laporan'] ?? ''); ?>" target="_blank" style="color: #3498db; text-decoration: none; font-weight: 500;">
                                                <?= htmlspecialchars($lap['file_laporan'] ?? 'Buka File'); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (strtolower($lap['status_kirim'] ?? '') === 'selesai'): ?>
                                                <span style="color: #27ae60; font-weight: bold;">Selesai</span>
                                            <?php else: ?>
                                                <span style="color: #e67e22; font-weight: bold;"><?= htmlspecialchars($lap['status_kirim'] ?? 'Proses'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php elseif ($is_filtered || $total_data_asli > 0): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 20px; font-style: italic;">
                                        Tidak ada data laporan yang sesuai dengan filter pencarian Anda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td>Laporan Rekam Medis Bulanan</td>
                                    <td>Dinas Kesehatan Kota</td>
                                    <td>2026-06-01</td>
                                    <td><span style="color: #95a5a6; font-style: italic;">laporan_rekam_medis_juni.pdf (Dummy)</span></td>
                                    <td><span style="color: #27ae60; font-weight: bold;">Selesai</span></td>
                                </tr>
                                <tr>
                                    <td>Laporan Epidemiologi Penyakit Terbanyak</td>
                                    <td>Puskesmas Pembina</td>
                                    <td>2026-06-15</td>
                                    <td><span style="color: #95a5a6; font-style: italic;">laporan_surveilans_juni.pdf (Dummy)</span></td>
                                    <td><span style="color: #e67e22; font-weight: bold;">Proses</span></td>
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
            if(userDisplay && userDisplay.textContent.trim() === "") {
                const userLogin = localStorage.getItem("userLogin") || "Pengguna";
                userDisplay.textContent = userLogin;
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
            
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; padding: 12px 20px;
                background-color: ${bgColor}; color: white; border-radius: 4px; z-index: 10000;
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 2000);
        }
    </script>
    <script src="../assets/main.js"></script>
</body>
</html>