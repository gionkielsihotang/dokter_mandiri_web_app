<?php
session_start();

// [OTORISASI & AUTENTIKASI] Proteksi halaman - Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Mengambil role pengguna (Default ke 'Staf' jika tidak diatur di session)
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staf';

// Modul Finansial/Kasir: Hanya Admin dan Resepsionis yang berhak mengelola tagihan & pembayaran
if (!in_array($role_aktif, ['Admin', 'Resepsionis'])) {
    header("Location: dashboard.php");
    exit();
}

// Koneksi ke database
include '../config/koneksi.php';
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

$daftar_tagihan = [];
$error_message = null;

try {
    if (isset($db)) {
        /**
         * =========================================================================
         * INTEGRASI OBJEK DATABASE: v_dashboard_keuangan_lunas (UNION Fallback Belum Lunas)
         * =========================================================================
                  */
        $query = $db->query("
            -- 1. Ambil data transaksi yang sudah Lunas melalui View v_dashboard_keuangan_lunas
            SELECT 
                `ID Tagihan` AS tagihan_id,
                `Tanggal Transaksi` AS tanggal_tagihan,
                `Nama Pasien` AS nama_pasien,
                `Total Kotor` AS subtotal_kalkulasi,
                `Potongan Diskon` AS diskon,
                `Total Bersih Diterima` AS total_akhir,
                'Lunas' AS status
            FROM v_dashboard_keuangan_lunas

            UNION ALL

            -- 2. Ambil data yang Belum Lunas secara real-time agar Kasir dapat mengeksekusi pembayaran
            SELECT 
                t.tagihan_id,
                k.tgl_kunjungan AS tanggal_tagihan,
                p.nama AS nama_pasien,
                IFNULL(SUM(dt.harga_satuan), 0) AS subtotal_kalkulasi,
                t.diskon,
                IFNULL(SUM(dt.harga_satuan) - t.diskon, 0) AS total_akhir,
                'Belum Lunas' AS status
            FROM `praktik_mandiri`.`tagihan` t
            JOIN `praktik_mandiri`.`kunjungan` k ON t.visit_id = k.visit_id
            JOIN `praktik_mandiri`.`pasien` p ON k.patient_id = p.patient_id
            LEFT JOIN `praktik_mandiri`.`detail_tagihan` dt ON t.tagihan_id = dt.tagihan_id
            WHERE t.status = 'Belum Lunas'
            GROUP BY t.tagihan_id, k.tgl_kunjungan, p.nama, t.diskon
            
            ORDER BY tanggal_tagihan DESC
        ");
        $daftar_tagihan = $query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error pada tagihan.php: " . $e->getMessage());
    $error_message = "Gagal memuat billing tagihan. Pastikan view 'v_dashboard_keuangan_lunas' telah dikonfigurasi di database.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tagihan - Dokter Mandiri</title>
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
                
                <?php if (in_array($role_aktif, ['Admin', 'Doctor', 'Dokter', 'Apoteker'])): ?>
                    <li><a href="resep.php">Resep & Dispensing</a></li>
                <?php endif; ?>
                
                <li><a href="obat.php">Obat & Stok</a></li>
                
                <?php if (in_array($role_aktif, ['Admin', 'Resepsionis'])): ?>
                    <li><a href="tagihan.php" class="active">Tagihan</a></li>
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
                <div>Halo, <strong id="userDisplay"><?= htmlspecialchars($_SESSION['username'] ?? ''); ?></strong> <span style="font-size: 0.8rem; color: #7f8c8d;">(<?= htmlspecialchars($role_aktif); ?>)</span></div>
            </header>

            <main class="content-area">
                <div class="table-container">
                    <div class="table-header">
                        <h2>Daftar Tagihan Pasien</h2>
                    </div>
                    <br>

                    <?php if ($error_message): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #f5c6cb; font-size: 0.9rem;">
                            <?= htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Pasien</th>
                                <th>Tagihan</th>
                                <th>Diskon</th>
                                <th>Total Tagihan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_tagihan)): ?>
                                <?php foreach ($daftar_tagihan as $tagihan): ?>
                                    <?php 
                                        $status_tagihan = strtolower(trim($tagihan['status'] ?? 'belum lunas')); 
                                        $subtotal = $tagihan['subtotal_kalkulasi'] ?? 0;
                                        $diskon = $tagihan['diskon'] ?? 0;
                                        $total_akhir = $tagihan['total_akhir'] ?? 0;

                                        // Breakdown proporsi biaya dokter & obat untuk kebutuhan tampilan modal ringkas
                                        $biaya_dokter = min($subtotal, 80000); 
                                        $biaya_obat = max(0, $subtotal - $biaya_dokter);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tagihan['tanggal_tagihan'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($tagihan['nama_pasien'] ?? 'Pasien'); ?></td>
                                        <td>Rp <?= number_format($subtotal, 0, ',', '.'); ?></td>
                                        <td>Rp <?= number_format($diskon, 0, ',', '.'); ?></td>
                                        <td>Rp <?= number_format($total_akhir, 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($status_tagihan === 'lunas'): ?>
                                                <span style="color: #27ae60; font-weight:bold;">Lunas</span>
                                            <?php else: ?>
                                                <span style="color: #e74c3c; font-weight:bold;">Belum Lunas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($status_tagihan === 'lunas'): ?>
                                                <a href="cetak_struk.php?id=<?= htmlspecialchars($tagihan['tagihan_id']); ?>" class="btn btn-small" target="_blank" style="text-decoration: none; display: inline-block;">Lihat Struk</a>
                                            <?php else: ?>
                                                <button class="btn btn-small btn-detail" 
                                                        id="btnAdd"
                                                        data-id="<?= htmlspecialchars($tagihan['tagihan_id']); ?>"
                                                        data-nama="<?= htmlspecialchars($tagihan['nama_pasien'] ?? 'Pasien'); ?>"
                                                        data-tanggal="<?= htmlspecialchars($tagihan['tanggal_tagihan'] ?? ''); ?>"
                                                        data-dokter="<?= $biaya_dokter; ?>"
                                                        data-obat="<?= $biaya_obat; ?>"
                                                        data-diskon="<?= $diskon; ?>"
                                                        data-total="<?= $total_akhir; ?>">
                                                    Detail Tagihan
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 15px;">
                                        Tidak ada data tagihan di database.
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
            <h2 style="margin-bottom: 1rem;">Detail Tagihan:</h2>
            <hr style="margin-bottom: 10px;">
            <div style="margin-bottom: 1rem;">
                <p><strong>Nama Pasien:</strong> <span id="modalNamaPasien">-</span></p>
                <p><strong>Tanggal:</strong> <span id="modalTanggal">-</span></p>
            </div>
            
            <form method="POST" action="../proses/proses_pembayaran.php">
                <input type="hidden" name="tagihan_id" id="modalTagihanId" value="">
                
                <table style="margin-bottom: 1rem; width: 100%;">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Jasa Dokter</td>
                            <td style="text-align: right;" id="modalJasaDokter">Rp 0</td>
                        </tr>
                        <tr>
                            <td>Biaya Obat</td>
                            <td style="text-align: right;" id="modalBiayaObat">Rp 0</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th style="text-align: left;">Diskon</th>
                            <th style="text-align: right;" id="modalDiskon">Rp 0</th>
                        </tr>
                        <tr>
                            <th style="text-align: left;">TOTAL</th>
                            <th style="text-align: right;" id="modalTotal">Rp 0</th>
                        </tr>
                    </tfoot>
                </table>

                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select class="form-control" name="metode_pembayaran" required>
                        <option value="Tunai">Tunai</option>
                        <option value="QRIS">QRIS</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                    </select>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="btn" style="background-color: #27ae60;">Proses & Cetak Struk</button>
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
                userDisplay.textContent = localStorage.getItem("userLogin") || "Kasir";
            }
        }

        function setupModalTriggers() {
            const modal = document.getElementById("formModal");
            const detailButtons = document.querySelectorAll(".btn-detail");
            const closeModal = document.querySelector(".close-modal");

            detailButtons.forEach(button => {
                button.onclick = function() {
                    document.getElementById("modalTagihanId").value = this.getAttribute("data-id");
                    document.getElementById("modalNamaPasien").textContent = this.getAttribute("data-nama");
                    document.getElementById("modalTanggal").textContent = this.getAttribute("data-tanggal");
                    
                    const dokter = parseInt(this.getAttribute("data-dokter")) || 0;
                    const obat = parseInt(this.getAttribute("data-obat")) || 0;
                    const diskon = parseInt(this.getAttribute("data-diskon")) || 0;
                    const total = parseInt(this.getAttribute("data-total")) || 0;

                    document.getElementById("modalJasaDokter").textContent = "Rp " + dokter.toLocaleString('id-ID');
                    document.getElementById("modalBiayaObat").textContent = "Rp " + obat.toLocaleString('id-ID');
                    document.getElementById("modalDiskon").textContent = "Rp " + diskon.toLocaleString('id-ID');
                    document.getElementById("modalTotal").textContent = "Rp " + total.toLocaleString('id-ID');
                    
                    modal.style.display = "block";
                }
            });

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
                showNotification("Pembayaran sukses divalidasi!", "success");
            }
        }

        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                localStorage.removeItem("userLogin");
                window.location.href = "../proses/proses_logout.php";
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement("div");
            notification.textContent = message;
            let bgColor = (type === "error") ? "#dc3545" : "#27ae60";
            
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; padding: 12px 20px;
                background-color: ${bgColor}; color: white; border-radius: 4px; z-index: 10000;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    </script>
    <script src="../Assets/main.js"></script>
</body>
</html>