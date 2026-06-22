<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staf';

if (!in_array($role_aktif, ['Admin', 'Doctor', 'Dokter', 'Apoteker'])) {
    header("Location: dashboard.php");
    exit();
}

include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

$daftar_resep = [];
$pilihan_obat = [];
$pilihan_rm = [];
$error_message = null;

try {
    if (isset($db)) {
        // Query tetap mengambil data resep secara aman dengan LEFT JOIN
        $query = $db->query("SELECT 
                                r.resep_id,
                                o.nama_obat AS nama_obat,
                                r.tanggal_resep AS tanggal_resep, 
                                r.catatan_dokter AS catatan_dokter,
                                r.status_resep AS status_resep 
                             FROM Resep r
                             LEFT JOIN Rekam_Medis rm ON r.record_id = rm.record_id
                             LEFT JOIN detail_resep dr ON r.resep_id = dr.resep_id
                             LEFT JOIN obat o ON dr.obat_id = o.obat_id
                             ORDER BY r.resep_id DESC");
        $daftar_resep = $query->fetchAll(PDO::FETCH_ASSOC);

        // Ambil master obat
        $query_obat = $db->query("SELECT obat_id, nama_obat, fn_cek_stok_obat(obat_id) AS stok_terkini 
                                  FROM obat 
                                  ORDER BY nama_obat ASC");
        $pilihan_obat = $query_obat->fetchAll(PDO::FETCH_ASSOC);

        // Ambil data Rekam Medis aktif untuk dipasangkan ke Resep di form modal
        $query_rm = $db->query("SELECT rm.record_id, p.nama AS nama_pasien, rm.tanggal_catatan 
                                FROM Rekam_Medis rm
                                INNER JOIN kunjungan k ON rm.visit_id = k.visit_id
                                INNER JOIN pasien p ON k.patient_id = p.patient_id
                                ORDER BY rm.record_id DESC");
        $pilihan_rm = $query_rm->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error pada resep.php: " . $e->getMessage());
    $error_message = "Error MySQL: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Resep & Dispensing - Dokter Mandiri</title>
    <link rel="stylesheet" href="../Assets/style.css">
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
                <li><a href="resep.php" class="active">Resep & Dispensing</a></li>
                
                <?php if (in_array($role_aktif, ['Admin', 'Doctor', 'Dokter', 'Apoteker'])): ?>
                    <li><a href="obat.php">Obat & Stok</a></li>
                <?php endif; ?>
                
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
                        <h2>Resep & Dispensing Obat</h2>
                        <button class="btn btn-small" id="btnAdd">+ Tambah Resep Baru</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Tanggal</th>
                                <th>Catatan Dokter</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_resep)): ?>
                                <?php foreach ($daftar_resep as $resep): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($resep['nama_obat'] ?? 'Belum ada item obat'); ?></strong></td>
                                        <td><?= !empty($resep['tanggal_resep']) ? date('Y-m-d', strtotime($resep['tanggal_resep'])) : 'Tanpa Tanggal'; ?></td>
                                        <td><?= htmlspecialchars($resep['catatan_dokter'] ?? '-'); ?></td>
                                        <td>
                                            <?php 
                                            $status = strtolower($resep['status_resep'] ?? 'proses');
                                            if ($status === 'selesai' || $status === 'complete'): 
                                            ?>
                                                <span style="color: #27ae60; font-weight: bold;">Selesai</span>
                                            <?php else: ?>
                                                <span style="color: #e67e22; font-weight: bold;">Proses</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #7f8c8d; font-style: italic; padding: 15px;">
                                        Tidak ada data resep yang tersedia.
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
            <h2 style="margin-bottom: 1rem;">Tambah Resep & Dispensing</h2>
            <form method="POST" action="../proses/proses_tambah_resep.php">
                
                <div class="form-group">
                    <label for="record_id">Pilih Rekam Medis Pasien</label>
                    <select id="record_id" name="record_id" class="form-control" required>
                        <option value="">-- Pilih Pasien (RM) --</option>
                        <?php foreach ($pilihan_rm as $rm): ?>
                            <option value="<?= $rm['record_id']; ?>">
                                RM#<?= $rm['record_id']; ?> - <?= htmlspecialchars($rm['nama_pasien']); ?> (<?= $rm['tanggal_catatan']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="obat_id">Nama Obat</label>
                    <select id="obat_id" name="obat_id" class="form-control" required>
                        <option value="">-- Pilih Obat --</option>
                        <?php foreach ($pilihan_obat as $o): ?>
                            <?php 
                                $raw_stok = (int)$o['stok_terkini']; 
                                if ($raw_stok <= 0) {
                                    $stok_label = "Habis";
                                    $disabled = 'disabled';
                                } else {
                                    $stok_label = $raw_stok;
                                    $disabled = '';
                                }
                            ?>
                            <option value="<?= htmlspecialchars($o['obat_id']); ?>" <?= $disabled; ?>>
                                <?= htmlspecialchars($o['nama_obat']); ?> (Stok: <?= $stok_label; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah">Jumlah / Kuantitas Obat</label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" value="1" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_resep">Tanggal</label>
                    <input type="date" id="tanggal_resep" name="tanggal_resep" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label for="catatan_dokter">Catatan Dokter / Signa</label>
                    <textarea id="catatan_dokter" name="catatan_dokter" class="form-control" rows="2" placeholder="Contoh: 3 x 1 tablet setelah makan" required></textarea>
                </div>

                <input type="hidden" name="status_resep" value="Diproses">

                <button type="submit" class="btn">Simpan Resep & Potong Stok</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setupUserSession();
            setupModalTriggers();
            checkUrlStatus();
            
            <?php if ($error_message): ?>
                showNotification(<?= json_encode($error_message); ?>, "error");
            <?php endif; ?>
        });

        function setupUserSession() {
            const userDisplay = document.getElementById("userDisplay");
            const userLogin = localStorage.getItem("userLogin");
            if (!userLogin) {
                const sessionUser = "<?= htmlspecialchars($_SESSION['username'] ?? ''); ?>";
                localStorage.setItem("userLogin", sessionUser || "Pengguna");
                if (userDisplay) userDisplay.textContent = sessionUser || "Pengguna";
            } else { if (userDisplay) userDisplay.textContent = userLogin; }
        }

        function setupModalTriggers() {
            const modal = document.getElementById("formModal");
            const btnAdd = document.getElementById("btnAdd");
            const closeModal = document.querySelector(".close-modal");
            if(btnAdd && modal) { btnAdd.onclick = function() { modal.style.display = "flex"; } }
            if(closeModal && modal) { closeModal.onclick = function() { modal.style.display = "none"; } }
            window.onclick = function(event) { if (event.target == modal) { modal.style.display = "none"; } }
        }

        function checkUrlStatus() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') { showNotification("Resep berhasil disimpan dan stok dikurangi!", "success"); }
            else if (urlParams.get('status') === 'error') { showNotification("Gagal memproses resep.", "error"); }
        }

        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                localStorage.removeItem("userLogin");
                showNotification("Logout berhasil", "success");
                setTimeout(() => { window.location.href = "login.php"; }, 1000);
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
                box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: sans-serif;
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), type === "error" ? 10000 : 2000);
        }
    </script>
</body>
</html>