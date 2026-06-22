<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman - Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staf';

// Hanya Admin yang boleh mengakses halaman utility ini
if ($role_aktif !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Utility Database - Dokter Mandiri</title>
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
                
                <?php if (in_array($role_aktif, ['Admin', 'Dokter', 'Perawat'])): ?>
                    <li><a href="rekam_medis.php">Rekam Medis</a></li>
                <?php endif; ?>
                
                <?php if (in_array($role_aktif, ['Admin', 'Dokter', 'Apoteker'])): ?>
                    <li><a href="resep.php">Resep & Dispensing</a></li>
                    <li><a href="obat.php">Obat & Stok</a></li>
                <?php endif; ?>
                
                <?php if (in_array($role_aktif, ['Admin', 'Resepsionis'])): ?>
                    <li><a href="tagihan.php">Tagihan</a></li>
                <?php endif; ?>
                
                <li><a href="laporan.php">Laporan</a></li>
                
                <?php if ($role_aktif === 'Admin'): ?>
                    <li><a href="users.php">User Management</a></li>
                    <li><a href="utility.php" class="active">Backup & Restore</a></li>
                <?php endif; ?>
                
                <li><a href="#" onclick="logout(); return false;">Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <header class="topbar">
                <div>Halo, <strong><?= htmlspecialchars($_SESSION['username']); ?></strong> <span style="font-size: 0.8rem; color: #7f8c8d;">(<?= htmlspecialchars($role_aktif); ?>)</span></div>
            </header>

            <main class="content-area">
                <h2>Utility System (Maintenance Database)</h2>
                <p style="color: #7f8c8d; margin-bottom: 20px;">Fasilitas wajib untuk mengamankan data rekam medis dan memulihkannya saat terjadi keadaan darurat.</p>

                <?php if (isset($_GET['status'])): ?>
                    <?php if ($_GET['status'] === 'backup_success'): ?>
                        <div style="padding: 12px; margin-bottom: 20px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">
                            Database berhasil dicadangkan! File .sql otomatis terunduh.
                        </div>
                    <?php elseif ($_GET['status'] === 'restore_success'): ?>
                        <div style="padding: 12px; margin-bottom: 20px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">
                            Database berhasil dipulihkan (Restore) ke kondisi file cadangan!
                        </div>
                    <?php elseif ($_GET['status'] === 'error'): ?>
                        <div style="padding: 12px; margin-bottom: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;">
                            Gagal memproses tindakan: <?= htmlspecialchars($_GET['msg'] ?? 'Terjadi kesalahan internal.'); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    
                    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center;">
                        <h3 style="margin-bottom: 10px; color: #2c3e50;">Backup Database</h3>
                        <p style="font-size: 0.9rem; color: #7f8c8d; margin-bottom: 20px;">Unduh seluruh struktur tabel dan data aplikasi Dokter Mandiri ke dalam file berekstensi .sql.</p>
                        <form method="POST" action="../proses/proses_backup.php">
                            <button type="submit" class="btn" style="background-color: #27ae60; width: auto; padding: 10px 30px; border: none; color: white; border-radius: 4px; cursor: pointer;">
                                📥 Mulai Ekspor (.SQL)
                            </button>
                        </form>
                    </div>

                    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        <h3 style="margin-bottom: 10px; color: #2c3e50; text-align: center;">Restore Database</h3>
                        <p style="font-size: 0.9rem; color: #7f8c8d; margin-bottom: 20px; text-align: center;">Pilih file backup .sql dari komputer Anda untuk menimpa/mengembalikan data sistem saat ini.</p>
                        <form method="POST" action="../proses/proses_restore.php" enctype="multipart/form-data">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="file_sql" style="display: block; margin-bottom: 5px;">Pilih File Backup (.sql)</label>
                                <input type="file" id="file_sql" name="file_sql" accept=".sql" class="form-control" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <button type="submit" class="btn" style="background-color: #e74c3c; width: 100%; padding: 10px; border: none; color: white; border-radius: 4px; cursor: pointer;" onclick="return confirm('Peringatan! Proses restore akan menimpa data yang ada saat ini. Apakah Anda yakin?')">
                                📤 Mulai Impor (.SQL)
                            </button>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                window.location.href = "../proses/proses_logout.php";
            }
        }
    </script>
</body>
</html>