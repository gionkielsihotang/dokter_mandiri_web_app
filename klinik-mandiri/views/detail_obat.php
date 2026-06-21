<?php
session_start();

// [FITUR WAJIB] Authentication & Authorization
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Hubungkan database
include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

// [FITUR WAJIB] Input Validation (Server-side validation: pastikan ID murni integer positif)
$obat_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if ($obat_id === false || $obat_id <= 0) {
    $obat_id = 0; 
}

$data_obat = null;
$error_message = null;

if ($obat_id > 0 && isset($db)) {
    try {
        // Mengambil data spesifikasi master obat dan gabungan aturan pakai resep terakhir (jika ada)
        $query = $db->prepare("
            SELECT 
                ob.nama_obat,
                ob.bentuk_sediaan, 
                ob.satuan,
                ob.kategori,
                d.dosis, 
                d.rute, 
                d.frekuensi, 
                d.durasi, 
                d.jumlah, 
                d.instruksi_khusus
            FROM obat ob
            LEFT JOIN Detail_Resep d ON ob.obat_id = d.obat_id
            WHERE ob.obat_id = :id
            LIMIT 1
        ");
        $query->execute([':id' => $obat_id]);
        $data_obat = $query->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Error pada detail_obat.php: " . $e->getMessage());
        $error_message = "Terjadi gangguan sistem saat mengambil data detail obat.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Obat - Dokter Mandiri</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="main-content">
        <header class="topbar">
            <div>Halo, <strong id="userDisplay"><?= htmlspecialchars($_SESSION['username'] ?? 'dr. Budi'); ?></strong></div>
            <a href="obat.php" class="btn btn-small btn-danger">Back</a>
        </header>

        <main class="content-area">
            <div class="dashboard-cards">
                <div class="table-container">
                    <h3>Detail Obat</h3><br>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
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
                                <th>Dosis</th>
                                <th>Rute</th>
                                <th>Frekuensi</th>
                                <th>Durasi</th>
                                <th>Jumlah</th>
                                <th>Instruksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($data_obat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($data_obat['nama_obat'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($data_obat['bentuk_sediaan'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($data_obat['satuan'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($data_obat['kategori'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($data_obat['dosis'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($data_obat['rute'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($data_obat['frekuensi'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($data_obat['durasi'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($data_obat['jumlah'] ?? '-'); ?></td> 
                                    <td><?= htmlspecialchars($data_obat['instruksi_khusus'] ?? '-'); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; color: #7f8c8d; font-style: italic; padding: 20px;">
                                        Data spesifikasi obat belum tersedia.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setupUserSession();
        });

        function setupUserSession() {
            const userDisplay = document.getElementById("userDisplay");
            const userLogin = localStorage.getItem("userLogin");
            
            if (!userLogin) {
                localStorage.setItem("userLogin", "<?= htmlspecialchars($_SESSION['username'] ?? 'Pengguna'); ?>");
                if (userDisplay) userDisplay.textContent = "<?= htmlspecialchars($_SESSION['username'] ?? 'Pengguna'); ?>";
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