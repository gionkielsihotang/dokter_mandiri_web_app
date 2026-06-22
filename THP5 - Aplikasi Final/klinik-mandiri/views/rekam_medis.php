<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman - Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Mengambil role pengguna (Default ke 'Staf' jika tidak diatur di session)
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staf';

// Batasi akses modul: Admin, Dokter, dan Perawat yang dapat melihat rekam medis
if (!in_array($role_aktif, ['Admin', 'Dokter', 'Perawat'])) {
    header("Location: dashboard.php");
    exit();
}

// Koneksi ke database
include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

$daftar_rekam_medis = [];
$error_message = null;

try {
    if (isset($db)) {
        // =========================================================================
        // PERBAIKAN FINAL QUERY: Kembali menggunakan record_id dengan LEFT JOIN yang benar
        // =========================================================================
        $query = $db->query("SELECT 
                                p.patient_id,
                                p.nama AS nama_pasien,
                                k.tgl_kunjungan AS tanggal_catatan,
                                IFNULL(rm.vital_summary, '-') AS vital_summary,
                                IFNULL(rm.tinggi_badan, 0) AS tinggi_badan,
                                IFNULL(rm.berat_badan, 0) AS berat_badan,
                                IFNULL(rm.anamnesa, '-') AS anamnesa,
                                IFNULL(rm.pemeriksaan_fisik, '-') AS pemeriksaan_fisik,
                                rm.catatan_klinis,
                                IFNULL(tv.riwayat_obat, 'Tidak ada') AS riwayat_obat,
                                IFNULL(rm.riwayat_penyakit, 'Tidak ada') AS riwayat_penyakit,
                                IFNULL(rm.alergi_obat_makanan, 'Tidak ada') AS alergi_obat_makanan
                             FROM rekam_medis rm
                             INNER JOIN kunjungan k ON rm.visit_id = k.visit_id
                             INNER JOIN pasien p ON k.patient_id = p.patient_id
                             LEFT JOIN triage_vital tv ON rm.record_id = tv.record_id
                             ORDER BY k.tgl_kunjungan DESC, p.patient_id ASC");
        
        $daftar_rekam_medis = $query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error pada rekam_medis.php: " . $e->getMessage());
    $error_message = "Gagal memuat data. Error DB: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekam Medis - Dokter Mandiri</title>
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
                <li><a href="rekam_medis.php" class="active">Rekam Medis</a></li>
                
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
                        <h2>Pemeriksaan & Rekam Medis</h2>
                        <button class="btn btn-small" id="btnAdd">+ Rekam Medis Baru</button>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 12px 15px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #f5c6cb;">
                            <?= htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="patient-scroll-box">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Pasien</th>
                                    <th>Nama Pasien</th>
                                    <th>Tanggal</th>
                                    <th>Vital Summary</th>
                                    <th>Tinggi Badan</th>
                                    <th>Berat Badan</th>
                                    <th>Anamnesa</th>
                                    <th>Pemeriksaan</th>
                                    <th>Catatan</th>
                                    <th>Riwayat Obat</th>
                                    <th>Riwayat Penyakit</th>
                                    <th>Alergi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($daftar_rekam_medis)): ?>
                                    <?php foreach ($daftar_rekam_medis as $rm): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($rm['patient_id'] ?? '-'); ?></td>
                                            <td><strong><?= htmlspecialchars($rm['nama_pasien'] ?? '-'); ?></strong></td>
                                            <td><?= !empty($rm['tanggal_catatan']) ? date('Y-m-d', strtotime($rm['tanggal_catatan'])) : '-'; ?></td>
                                            <td><?= htmlspecialchars($rm['vital_summary'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($rm['tinggi_badan'] ?? '0'); ?> cm</td>
                                            <td><?= htmlspecialchars($rm['berat_badan'] ?? '0'); ?> kg</td>
                                            <td><?= htmlspecialchars($rm['anamnesa'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($rm['pemeriksaan_fisik'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($rm['catatan_klinis'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($rm['riwayat_obat'] ?? 'Tidak ada'); ?></td> 
                                            <td><?= htmlspecialchars($rm['riwayat_penyakit'] ?? 'Tidak ada'); ?></td>
                                            <td><?= htmlspecialchars($rm['alergi_obat_makanan'] ?? 'Tidak ada'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" style="text-align: center; color: #7f8c8d; padding: 20px; font-style: italic;">
                                            Belum ada data rekam medis yang tercatat di database.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="formModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 class="mb-2">Isi Rekam Medis</h2>
            <form method="POST" action="../proses/proses_tambah_rekam_medis.php">
                <div class="form-group">
                    <label for="visit_id">ID Kunjungan (Visit ID)</label>
                    <input type="number" id="visit_id" name="visit_id" class="form-control" placeholder="Masukkan ID Kunjungan Aktif" required>
                </div>
                
                <h3 class="mt-2 mb-1" style="color: #2c3e50; font-size: 1.1rem; font-weight: 600;">Vital Summary</h3>
                <div class="form-group">
                    <label for="tekanan_darah">Tekanan Darah</label>
                    <input type="text" id="tekanan_darah" name="tekanan_darah" class="form-control" placeholder="Contoh: 110/70" required>
                </div>
                <div class="form-group">
                    <label for="nadi">Nadi (BPM)</label>
                    <input type="text" id="nadi" name="nadi" class="form-control" placeholder="Contoh: 80" required>
                </div>
                <div class="form-group">
                    <label for="suhu">Suhu (°C)</label>
                    <input type="text" id="suhu" name="suhu" class="form-control" placeholder="Contoh: 37" required>
                </div>
                <div class="form-group">
                    <label for="tinggi_badan">Tinggi Badan (cm)</label>
                    <input type="number" id="tinggi_badan" name="tinggi_badan" class="form-control" placeholder="Contoh: 170" required>
                </div>
                <div class="form-group">
                    <label for="berat_badan">Berat Badan (kg)</label>
                    <input type="number" id="berat_badan" name="berat_badan" class="form-control" placeholder="Contoh: 70" required>
                </div>

                <h3 class="mt-2 mb-1" style="color: #2c3e50; font-size: 1.1rem; font-weight: 600;">Keterangan Klinis</h3>
                <div class="form-group">
                    <label for="anamnesa">Anamnesa (Keluhan Pasien)</label>
                    <textarea id="anamnesa" name="anamnesa" class="form-control" rows="2" required placeholder="pusing..."></textarea>
                </div>
                <div class="form-group">
                    <label for="pemeriksaan_fisik">Pemeriksaan Fisik</label>
                    <textarea id="pemeriksaan_fisik" name="pemeriksaan_fisik" class="form-control" rows="2" required placeholder="normal..."></textarea>
                </div>
                <div class="form-group">
                    <label for="catatan_klinis">Catatan Klinis / Diagnosa</label>
                    <textarea id="catatan_klinis" name="catatan_klinis" class="form-control" rows="2" required placeholder="rawat pulang..."></textarea>
                </div>
                <div class="form-group">
                    <label for="riwayat_obat">Riwayat Obat</label>
                    <input type="text" id="riwayat_obat" name="riwayat_obat" class="form-control" placeholder="metil..." required>
                </div>
                <div class="form-group">
                    <label for="riwayat_penyakit">Riwayat Penyakit Dahulu</label>
                    <input type="text" id="riwayat_penyakit" name="riwayat_penyakit" class="form-control" placeholder="Isi 'Tidak ada' jika absen" required>
                </div>
                <div class="form-group">
                    <label for="alergi_obat_makanan">Alergi Obat / Makanan</label>
                    <input type="text" id="alergi_obat_makanan" name="alergi_obat_makanan" class="form-control" placeholder="Isi 'Tidak ada' jika absen" required>
                </div>

                <div class="text-right mt-2">
                    <button type="submit" class="btn btn-small">Simpan Rekam Medis</button>
                </div>
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
            if(userDisplay && userDisplay.textContent.trim() === "") {
                userDisplay.textContent = localStorage.getItem("userLogin") || "Pengguna";
            }
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
            if (urlParams.get('status') === 'success') {
                showNotification("Data rekam medis berhasil disimpan!", "success");
            } else if (urlParams.get('status') === 'error') {
                const msg = urlParams.get('msg') || "Gagal menyimpan rekam medis.";
                showNotification(msg, "error");
            }
        }

        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                localStorage.removeItem("userLogin");
                showNotification("Logout berhasil", "success");
                setTimeout(() => { window.location.href = "../proses/proses_logout.php"; }, 1000);
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement("div");
            notification.textContent = message;
            let bgColor = "#27ae60";
            if (type === "error") bgColor = "#dc3545";
            notification.style.cssText = `position: fixed; top: 20px; right: 20px; padding: 12px 20px; background-color: ${bgColor}; color: white; border-radius: 4px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: sans-serif;`;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    </script>
</body>
</html>