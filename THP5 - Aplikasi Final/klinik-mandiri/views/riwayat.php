<?php
session_start();

// Proteksi halaman - Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Sesuaikan path koneksi dengan struktur folder Anda
include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

$riwayat_pasien = [];
$pasien_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : null;
$error_message = null;

if ($pasien_id) {
    try {
        if (isset($db)) {
            // Mengambil riwayat medis pasien berdasarkan patient_id
            $stmt = $db->prepare("SELECT 
                                    rm.tanggal_catatan,
                                    rm.vital_summary,
                                    rm.tinggi_badan,
                                    rm.berat_badan,
                                    rm.anamnesa,
                                    rm.pemeriksaan_fisik,
                                    rm.catatan_klinis,
                                    tv.riwayat_obat,
                                    rm.riwayat_penyakit,
                                    rm.alergi_obat_makanan
                                  FROM rekam_medis rm 
                                  JOIN kunjungan k ON rm.visit_id = k.visit_id 
                                  LEFT JOIN triage_vital tv ON rm.record_id = tv.record_id
                                  WHERE k.patient_id = :pasien_id 
                                  ORDER BY rm.tanggal_catatan DESC");
            
            $stmt->execute(['pasien_id' => $pasien_id]);
            $riwayat_pasien = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Database Error pada riwayat.php: " . $e->getMessage());
        $error_message = "Gagal memuat data. Pesan sistem: " . $e->getMessage();
    }
} else {
    // Jika tidak ada ID di URL, kembalikan ke halaman pasien
    header("Location: pasien.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat - Dokter Mandiri</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
        <div class="main-content">
            <header class="topbar">
                <div>Halo, <strong id="userDisplay">dr. Budi</strong></div>
                <a href="pasien.php" class="btn btn-small btn-danger">Back</a>
            </header>

            <main class="content-area">
                <?php if ($error_message): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-family: sans-serif;">
                        <?= htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="dashboard-cards">
                    <div class="table-container" style="overflow-x: auto; width: 100%;">
                        <h3>Riwayat Pasien</h3><br>
                        <table>
                            <thead>
                                <tr>
                                    <th style="white-space: nowrap;">Tanggal</th>
                                    <th style="white-space: nowrap;">Vital Summary</th>
                                    <th style="white-space: nowrap;">Tinggi Badan</th>
                                    <th style="white-space: nowrap;">Berat Badan</th>
                                    <th>Anamnesa</th>
                                    <th>Pemeriksaan</th>
                                    <th>Catatan</th>
                                    <th>Riwayat Obat</th>
                                    <th>Riwayat Penyakit</th>
                                    <th>Alergi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($riwayat_pasien)): ?>
                                    <?php foreach ($riwayat_pasien as $riwayat): ?>
                                        <tr>
                                            <td style="white-space: nowrap;"><?= htmlspecialchars($riwayat['tanggal_catatan'] ?? '-'); ?></td>
                                            <td style="white-space: nowrap;"><?= htmlspecialchars($riwayat['vital_summary'] ?? '-'); ?></td>
                                            <td style="white-space: nowrap;"><?= htmlspecialchars($riwayat['tinggi_badan'] ?? '-'); ?> cm</td>
                                            <td style="white-space: nowrap;"><?= htmlspecialchars($riwayat['berat_badan'] ?? '-'); ?> kg</td>
                                            <td><?= htmlspecialchars($riwayat['anamnesa'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($riwayat['pemeriksaan_fisik'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($riwayat['catatan_klinis'] ?? '-'); ?></td>
                                            <td><?= htmlspecialchars($riwayat['riwayat_obat'] ?? 'Tidak ada'); ?></td>
                                            <td><?= htmlspecialchars($riwayat['riwayat_penyakit'] ?? 'Tidak ada'); ?></td>
                                            <td><?= htmlspecialchars($riwayat['alergi_obat_makanan'] ?? 'Tidak ada'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" style="text-align: center; padding: 30px; color: #7f8c8d; font-style: italic;">
                                            Belum ada data rekam medis untuk pasien ini.
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setupUserSession();
        });

        function setupUserSession() {
            const userDisplay = document.getElementById("userDisplay");
            const userLogin = localStorage.getItem("userLogin");
            
            if (!userLogin) {
                localStorage.setItem("userLogin", "Pengguna");
                if (userDisplay) userDisplay.textContent = "Pengguna";
            } else {
                if (userDisplay) userDisplay.textContent = userLogin;
            }
        }

        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                localStorage.removeItem("userLogin");
                showNotification("Logout berhasil", "success");
                setTimeout(() => {
                    window.location.href = "login.php";
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