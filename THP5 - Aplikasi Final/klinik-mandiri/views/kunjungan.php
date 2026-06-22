<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// [FITUR WAJIB] Authentication & Authorization
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Pengguna';

// Menghubungkan ke database
include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

$daftar_kunjungan = [];
$error_message = null;

try {
    if (isset($db)) {
        // PERBAIKAN: Menggunakan CURDATE() MySQL langsung & mempertahankan fungsi fn_hitung_usia
        $query = $db->prepare("SELECT 
                                    k.visit_id,
                                    k.antrian_no AS nomor_antrean,
                                    p.nama AS nama_lengkap,
                                    DATE_FORMAT(k.waktu_datang, '%H:%i') AS waktu_datang,
                                    k.status,
                                    k.tgl_kunjungan,
                                    DATE_FORMAT(k.waktu_selesai, '%H:%i') AS waktu_selesai,
                                    fn_hitung_usia(k.patient_id) AS usia
                                FROM Kunjungan k
                                JOIN Pasien p ON k.patient_id = p.patient_id
                                WHERE k.tgl_kunjungan = CURDATE()
                                ORDER BY k.antrian_no ASC");
                                
        $query->execute();
        $daftar_kunjungan = $query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error pada kunjungan.php: " . $e->getMessage());
    $error_message = "Gagal memuat daftar kunjungan hari ini.";
    $daftar_kunjungan = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Kunjungan - Dokter Mandiri</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">Dokter Mandiri</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="pasien.php">Manajemen Pasien</a></li>
                <li><a href="kunjungan.php" class="active">Jadwal / Kunjungan</a></li>
                
                <?php if($role_aktif == 'Admin' || $role_aktif == 'Dokter'): ?>
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
                <div>Halo, <strong id="userDisplay"><?= htmlspecialchars($_SESSION['username']); ?></strong> <span style="font-size: 0.85rem; color: #7f8c8d;">(<?= htmlspecialchars($role_aktif); ?>)</span></div>
            </header>

            <main class="content-area">
                <div class="table-container patient-scroll-box">
                    <div class="table-header">
                        <h2>Daftar Kunjungan Hari Ini</h2>
                        <button class="btn btn-small" id="btnAdd">+ Pasien Datang</button>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                            <?= htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Antrean</th>
                                <th>Nama Pasien</th>
                                <th>Tanggal</th>
                                <th>Waktu Datang</th>
                                <th>Waktu Selesai</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_kunjungan)): ?>
                                <?php foreach ($daftar_kunjungan as $kunj): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($kunj['nomor_antrean'] ?? '-'); ?></strong></td>
                                        <td>
                                            <?= htmlspecialchars($kunj['nama_lengkap']); ?> 
                                            <span style="font-size: 0.85rem; color: #7f8c8d;">(<?= htmlspecialchars($kunj['usia'] ?? '0'); ?> Thn)</span>
                                        </td>
                                        <td><?= htmlspecialchars($kunj['tgl_kunjungan'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($kunj['waktu_datang'] ?? '--:--'); ?> WIB</td>
                                        <td><?= !empty($kunj['waktu_selesai']) ? htmlspecialchars($kunj['waktu_selesai']) . ' WIB' : '-'; ?></td>
                                        <td>
                                            <?php if (in_array($kunj['status'], ['Selesai', 'Selesai Diperiksa'])): ?>
                                                <span style="color: #27ae60; font-weight: bold;"><?= htmlspecialchars($kunj['status']); ?></span>
                                            <?php elseif ($kunj['status'] === 'Batal'): ?>
                                                <span style="color: #c0392b; font-weight: bold;"><?= htmlspecialchars($kunj['status']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #e67e22; font-weight: bold;"><?= htmlspecialchars($kunj['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!in_array($kunj['status'], ['Selesai', 'Selesai Diperiksa', 'Batal'])): ?>
                                                <a href="../proses/proses_tambah_kunjungan.php?aksi=selesai&id=<?= $kunj['visit_id']; ?>" class="btn btn-small" style="background-color: #27ae60; color: white; text-decoration: none;">Selesai</a>
                                                <a href="../proses/proses_tambah_kunjungan.php?aksi=batal&id=<?= $kunj['visit_id']; ?>" class="btn btn-small btn-danger" style="text-decoration: none;" onclick="return confirm('Batalkan antrean ini?')">Batal</a>
                                            <?php else: ?>
                                                <button class="btn btn-small" style="background-color: #bdc3c7; color: white;" disabled>Selesai</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #7f8c8d; font-style: italic; padding: 20px;">
                                        Belum ada kunjungan pasien terdaftar untuk hari ini.
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
            <h2 style="margin-bottom: 1rem;">Registrasi Kunjungan</h2>
            
            <form action="../proses/proses_tambah_kunjungan.php?aksi=tambah" method="POST" id="formKunjungan">
                <input type="hidden" name="jenis_layanan" value="Rawat Jalan">

                <div class="form-group">
                    <label for="patient_id">Cari Pasien (ID Pasien / Nama)</label>
                    <input type="number" id="patient_id" name="patient_id" class="form-control" placeholder="Masukkan ID Pasien..." min="1" required>
                </div>
                <div class="form-group">
                    <label for="keluhan">Keluhan Pasien</label>
                    <textarea id="keluhan" name="keluhan" class="form-control" rows="3" placeholder="Tuliskan keluhan utama..." maxlength="500" required></textarea>
                </div>
                <button type="submit" class="btn">Daftarkan Antrean</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setupUserSession();
            setupModalTriggers();
            
            // Validasi Sisi Klien
            const form = document.getElementById('formKunjungan');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const patientId = document.getElementById('patient_id').value;
                    const keluhan = document.getElementById('keluhan').value.trim();
                    
                    if (patientId <= 0 || isNaN(patientId)) {
                        e.preventDefault();
                        alert('ID Pasien harus berupa angka positif.');
                    } else if (keluhan.length < 4) {
                        e.preventDefault();
                        alert('Keluhan harus diisi minimal 4 karakter.');
                    }
                });
            }
        });

        function setupUserSession() {
            const userDisplay = document.getElementById("userDisplay");
            if(userDisplay && userDisplay.textContent.trim() === "") {
                userDisplay.textContent = localStorage.getItem("userLogin") || "Pengguna";
            }
        }

        function setupModalTriggers() {
            const modal = document.getElementById("formModal");
            const btnAdd = document.getElementById("btnAdd");
            const closeModal = document.querySelector(".close-modal");

            if(btnAdd && modal) { btnAdd.onclick = function() { modal.style.display = "block"; } }
            if(closeModal && modal) { closeModal.onclick = function() { modal.style.display = "none"; } }
            window.onclick = function(event) { if (event.target == modal) { modal.style.display = "none"; } }
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