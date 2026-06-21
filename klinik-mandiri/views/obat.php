<?php
session_start();

// [OTORISASI & AUTENTIKASI] Proteksi halaman - Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Mengambil role pengguna (Default ke 'Staf' jika tidak diatur di session)
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staf';

// Batasi akses modul: Hanya Admin, Apoteker, Dokter, dan Perawat yang boleh melihat master obat
if (!in_array($role_aktif, ['Admin', 'Apoteker', 'Dokter', 'Perawat'])) {
    header("Location: dashboard.php");
    exit();
}

// Koneksi ke database
include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

$daftar_obat = [];
$error_message = null;

try {
    if (isset($db)) {
        // Mengambil Master Obat (Sesuai nama tabel fisik 'Obat' di database Anda)
        $query = $db->query("SELECT obat_id, nama_obat, bentuk_sediaan, satuan, kategori 
                             FROM Obat 
                             ORDER BY nama_obat ASC");
        $daftar_obat = $query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error pada obat.php: " . $e->getMessage());
    $error_message = "Gagal memuat data obat. Pesan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Obat - Dokter Mandiri</title>
    <link rel="stylesheet" href="../assets/style.css">
    
    <style>
        .modal {
            position: fixed !important;
            z-index: 9999 !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            overflow: auto !important;
            background-color: rgba(0,0,0,0.5) !important; /* Efek gelap di belakang */
            display: none; /* Dikontrol oleh JS */
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Mengatur isi kotak modal agar otomatis presisi di tengah */
        .modal-content {
            background-color: #fff !important;
            margin: 10% auto !important; /* Pengaman jika browser jadul */
            padding: 24px !important;
            border-radius: 8px !important;
            width: 90% !important;
            max-width: 500px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2) !important;
            position: relative !important;
        }

        /* Memaksa flexbox jika browser mendukung display flex */
        @supports (display: flex) {
            .modal[style*="display: block"] {
                display: flex !important;
            }
            .modal-content {
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">Dokter Mandiri</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="pasien.php">Manajemen Pasien</a></li>
                <li><a href="kunjungan.php">Jadwal / Kunjungan</a></li>
                <li><a href="rekam_medis.php">Rekam Medis</a></li>
                
                <?php if (in_array($role_aktif, ['Admin', 'Doctor', 'Dokter', 'Apoteker'])): ?>
                    <li><a href="resep.php">Resep & Dispensing</a></li>
                <?php endif; ?>
                
                <li><a href="obat.php" class="active">Obat & Stok</a></li>
                
                <?php if (in_array($role_aktif, ['Admin', 'Resepsionis'])): ?>
                    <li><a href="tagihan.php">Tagihan</a></li>
                <?php endif; ?>
                
                <li><a href="laporan.php">Laporan</a></li>
                
                <?php if ($role_aktif === 'Admin'): ?>
                    <li><a href="users.php">User Management</a></li>
                    <li><a href="utility.php">Backup & Restore</a></li>
                <?php endif; ?>
                
                <li><a href="#" onclick="logout(); return false;">Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <header class="topbar">
                <div>Halo, <strong id="userDisplay"><?= htmlspecialchars($_SESSION['username']); ?></strong> <span style="font-size: 0.8rem; color: #7f8c8d;">(<?= htmlspecialchars($role_aktif); ?>)</span></div>
            </header>

            <main class="content-area">
                <div class="table-container">
                    <div class="table-header">
                        <h2>Data Obat</h2>
                        <button class="btn btn-small" id="btnAdd">+ Tambah Obat Baru</button>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div style="padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9rem; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;">
                            <?= htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Bentuk</th>
                                <th>Satuan</th>
                                <th>Kategori</th>
                                <th>Detail Obat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_obat)): ?>
                                <?php foreach ($daftar_obat as $obat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($obat['nama_obat']); ?></td>
                                        <td><?= htmlspecialchars($obat['bentuk_sediaan'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($obat['satuan'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($obat['kategori'] ?? '-'); ?></td>
                                        <td>
                                            <a href="detail_obat.php?id=<?= urlencode($obat['obat_id']); ?>" class="btn btn-small" onclick="window.location.href=this.href; return false;">Lihat Detail</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 20px; font-style: italic;">
                                        Belum ada data master obat yang tersedia.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div id="formModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 style="margin-bottom: 1rem;">Data Obat Baru</h2>
            <form method="POST" action="../proses/proses_tambah_obat.php">
                <div class="form-group">
                    <label>Nama Obat</label>
                    <input type="text" name="nama_obat" class="form-control" placeholder="Masukkan nama obat..." required>
                </div>
                <div class="form-group">
                    <label>Bentuk Sediaan</label>
                    <input type="text" name="bentuk_sediaan" class="form-control" placeholder="Misal: Tablet, Kapsul, Sirup...">
                </div>
                <div class="form-group">
                    <label>Satuan</label>
                    <input type="text" name="satuan" class="form-control" placeholder="Misal: Strip, Botol, Pcs..." required>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="kategori" class="form-control" placeholder="Misal: Analgesik, Antibiotik...">
                </div>

                <button type="submit" class="btn" style="margin-top: 15px;">Simpan Data Obat Baru</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setupUserSession();
            setupModalTriggers();
            checkUrlStatus();
        });

        function setupUserSession() {
            const userDisplay = document.getElementById("userDisplay");
            const userLogin = localStorage.getItem("userLogin");
            
            if (!userLogin) {
                const sessionUser = "<?= htmlspecialchars($_SESSION['username'] ?? 'Pengguna'); ?>";
                localStorage.setItem("userLogin", sessionUser);
                if (userDisplay) userDisplay.textContent = sessionUser;
            } else {
                if (userDisplay) userDisplay.textContent = userLogin;
            }
        }

        function setupModalTriggers() {
            const modal = document.getElementById("formModal");
            const btnAdd = document.getElementById("btnAdd");
            const closeModal = document.querySelector(".close-modal");

            if(btnAdd && modal) {
                btnAdd.onclick = function() { modal.style.display = "block"; }
            }
            if(closeModal && modal) {
                closeModal.onclick = function() { modal.style.display = "none"; }
            }
            window.onclick = function(event) {
                if (event.target == modal) { modal.style.display = "none"; }
            }
        }

        function checkUrlStatus() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') {
                showNotification("Data obat baru berhasil disimpan!", "success");
            } else if (urlParams.get('status') === 'error') {
                const msg = urlParams.get('msg') || "Terjadi kesalahan sistem.";
                showNotification(msg, "error");
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
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background-color: ${bgColor};
                color: white;
                border-radius: 4px;
                z-index: 10000;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 2000);
        }
    </script>
</body>
</html>