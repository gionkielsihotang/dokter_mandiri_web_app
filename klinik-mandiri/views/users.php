<?php
session_start();

// [AUTENTIKASI] Proteksi halaman 1 - Cek session login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Ambil data Role dari Session (Default ke 'Pengguna' jika kosong)
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';

// [OTORISASI RBAC] Proteksi halaman 2 - HANYA ADMIN YANG BOLEH MASUK HALAMAN INI
if ($role_aktif !== 'Admin') {
    echo "<script>
            alert('Akses Ditolak! Halaman Manajemen User hanya dapat diakses oleh Administrator.'); 
            window.location.href='dashboard.php';
          </script>";
    exit();
}

// Koneksi ke database
include '../config/koneksi.php';
$koneksi = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

$daftar_user = [];
$error_message = null;

try {
    if (isset($koneksi)) {
        $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $koneksi->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Mengambil data user riil terbaru menggunakan LEFT JOIN sesuai DDL Anda
        $query = $koneksi->query("SELECT u.*, COALESCE(r.nama_role, 'Belum Diatur') as nama_role 
                                  FROM User u 
                                  LEFT JOIN Role r ON u.id_role = r.id_role 
                                  ORDER BY u.user_id DESC");
        $daftar_user = $query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error pada users.php: " . $e->getMessage());
    $error_message = "Gagal memuat daftar pengguna: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen User - Dokter Mandiri</title>
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
                
                <?php if(in_array($role_aktif, ['Admin', 'Dokter', 'Doctor'])): ?>
                    <li><a href="rekam_medis.php">Rekam Medis</a></li>
                    <li><a href="resep.php">Resep & Dispensing</a></li>
                <?php endif; ?>
                
                <?php if(in_array($role_aktif, ['Admin', 'Apoteker'])): ?>
                    <li><a href="obat.php">Obat & Stok</a></li>
                <?php endif; ?>
                
                <?php if(in_array($role_aktif, ['Admin', 'Resepsionis'])): ?>
                    <li><a href="tagihan.php">Tagihan</a></li>
                <?php endif; ?>
                
                <li><a href="laporan.php">Laporan</a></li>
                
                <?php if($role_aktif === 'Admin'): ?>
                    <li><a href="users.php" class="active">User Management</a></li>
                    <li><a href="utility.php">Backup & Restore</a></li>
                <?php endif; ?>
                
                <li><a href="#" onclick="logout(); return false;">Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <header class="topbar">
                <div>Halo, <strong id="userDisplay"><?= htmlspecialchars($_SESSION['username']); ?></strong> <span style="font-size: 0.85rem; color: #7f8c8d;">(<?= htmlspecialchars($role_aktif); ?>)</span></div>
            </header>

            <main class="content-area">
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar User</h2>
                        <button class="btn btn-small" id="btnAdd">+ Tambah User</button>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="mb-2" style="color: #dc3545; font-size: 0.95rem; background: #fdf2f2; padding: 10px; border-radius: 4px;">
                            <?= htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Kontak</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_user)): ?>
                                <?php foreach ($daftar_user as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['nama']); ?></td>
                                        <td><?= htmlspecialchars($user['username']); ?></td>
                                        <td><?= htmlspecialchars($user['nama_role']); ?></td>
                                        <td><?= htmlspecialchars($user['kontak'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-small" onclick="window.location.href='../proses/proses_tambah_user.php?aksi=reset&id=<?= urlencode($user['user_id']); ?>'; return confirm('Apakah Anda yakin ingin mereset password user ini?')">Reset Password</button>
                                            <button class="btn btn-small btn-danger" onclick="window.location.href='../proses/proses_tambah_user.php?aksi=hapus&id=<?= urlencode($user['user_id']); ?>'; return confirm('Apakah Anda yakin ingin menghapus akun user ini secara permanen?')">Hapus</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>dr. Budi Santoso</td>
                                    <td>drbudi</td>
                                    <td>Dokter Spesialis</td>
                                    <td>081234567890</td>
                                    <td>
                                        <button class="btn btn-small" onclick="alert('Fitur reset dinonaktifkan pada data simulasi.')">Reset Password</button>
                                        <button class="btn btn-small btn-danger" onclick="alert('Fitur hapus dinonaktifkan pada data simulasi.')">Hapus</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Siti Aminah</td>
                                    <td>kasir_siti</td>
                                    <td>Kasir / Pendaftaran</td>
                                    <td>081234567891</td>
                                    <td>
                                        <button class="btn btn-small" onclick="alert('Fitur reset dinonaktifkan pada data simulasi.')">Reset Password</button>
                                        <button class="btn btn-small btn-danger" onclick="alert('Fitur hapus dinonaktifkan pada data simulasi.')">Hapus</button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div id="formModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 style="margin-bottom: 1rem;">Tambah User Baru</h2>
            <form method="POST" action="../proses/proses_tambah_user.php?aksi=tambah">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kontak</label>
                    <input type="text" name="kontak" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="id_role" class="form-control" required>
                        <option value="1">Admin</option>
                        <option value="2">Dokter Spesialis</option>
                        <option value="3">Perawat</option>
                        <option value="4">Apoteker</option>
                        <option value="5">Kasir</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Simpan User</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setupUserSession();
            setupModalTriggers();
        });

        function setupUserSession() {
            const userDisplay = document.getElementById("userDisplay");
            const userLogin = "<?= htmlspecialchars($_SESSION['username']); ?>";
            if (userDisplay) {
                userDisplay.textContent = userLogin || localStorage.getItem("userLogin") || "Admin";
            }
        }

        function setupModalTriggers() {
            const modal = document.getElementById("formModal");
            const btnAdd = document.getElementById("btnAdd");
            const closeModal = document.querySelector(".close-modal");

            if(btnAdd && modal) {
                btnAdd.onclick = function() { modal.style.display = "flex"; }
            }
            if(closeModal && modal) {
                closeModal.onclick = function() { modal.style.display = "none"; }
            }
            window.onclick = function(event) {
                if (event.target == modal) { modal.style.display = "none"; }
            }
        }

        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                localStorage.removeItem("userLogin");
                window.location.href = "../proses/proses_logout.php";
            }
        }
    </script>
</body>
</html>