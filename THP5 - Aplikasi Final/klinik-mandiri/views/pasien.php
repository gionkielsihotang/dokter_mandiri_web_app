<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// [OTORISASI & AUTENTIKASI] Proteksi halaman - Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Mengambil role pengguna (Default ke 'Staf' jika tidak diatur di session)
$role_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staf';

// Batasi akses modul: Admin, Dokter, Perawat, dan Resepsionis umumnya dapat mengelola data pasien
if (!in_array($role_aktif, ['Admin', 'Dokter', 'Perawat', 'Resepsionis'])) {
    header("Location: dashboard.php");
    exit();
}

// Koneksi ke database
include '../config/koneksi.php';

// Memastikan koneksi aman dari scope global
$db = isset($koneksi) ? $koneksi : $GLOBALS['koneksi'];

// =========================================================================
// LOGIKA PEMROSESAN DATA (TAMBAH, EDIT, HAPUS) DI JALANKAN DI SINI
// =========================================================================
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($db)) {
    // [PROSES EDIT]
    if ($aksi === 'edit') {
        try {
            $patient_id     = $_POST['patient_id'];
            $nik            = $_POST['nik'];
            $nama           = $_POST['nama'];
            $jenis_kelamin  = $_POST['jenis_kelamin'];
            $tgl_lahir      = $_POST['tgl_lahir'];
            $no_telpon      = $_POST['no_telpon'];
            $email          = !empty($_POST['email']) ? $_POST['email'] : null;
            $asuransi_id    = !empty($_POST['asuransi_id']) ? $_POST['asuransi_id'] : null;
            $kontak_darurat = $_POST['kontak_darurat'];
            $alamat         = $_POST['alamat'];

            $sql = "UPDATE Pasien SET 
                        nik = :nik, nama = :nama, jenis_kelamin = :jenis_kelamin, 
                        tgl_lahir = :tgl_lahir, no_telpon = :no_telpon, email = :email, 
                        asuransi_id = :asuransi_id, kontak_darurat = :kontak_darurat, alamat = :alamat 
                    WHERE patient_id = :patient_id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nik' => $nik, ':nama' => $nama, ':jenis_kelamin' => $jenis_kelamin,
                ':tgl_lahir' => $tgl_lahir, ':no_telpon' => $no_telpon, ':email' => $email,
                ':asuransi_id' => $asuransi_id, ':kontak_darurat' => $kontak_darurat, ':alamat' => $alamat,
                ':patient_id' => $patient_id
            ]);

            header("Location: pasien.php?status=success");
            exit();
        } catch (PDOException $e) {
            header("Location: pasien.php?status=error&msg=" . urlencode($e->getMessage()));
            exit();
        }
    }
    
    // [PROSES TAMBAH]
    if ($aksi === 'tambah') {
        try {
            $patient_id     = $_POST['patient_id'];
            $nik            = $_POST['nik'];
            $nama           = $_POST['nama'];
            $jenis_kelamin  = $_POST['jenis_kelamin'];
            $tgl_lahir      = $_POST['tgl_lahir'];
            $no_telpon      = $_POST['no_telpon'];
            $email          = !empty($_POST['email']) ? $_POST['email'] : null;
            $asuransi_id    = !empty($_POST['asuransi_id']) ? $_POST['asuransi_id'] : null;
            $kontak_darurat = $_POST['kontak_darurat'];
            $alamat         = $_POST['alamat'];

            $sql = "INSERT INTO Pasien (patient_id, nik, nama, jenis_kelamin, tgl_lahir, no_telpon, email, asuransi_id, kontak_darurat, alamat) 
                    VALUES (:patient_id, :nik, :nama, :jenis_kelamin, :tgl_lahir, :no_telpon, :email, :asuransi_id, :kontak_darurat, :alamat)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':patient_id' => $patient_id, ':nik' => $nik, ':nama' => $nama, ':jenis_kelamin' => $jenis_kelamin,
                ':tgl_lahir' => $tgl_lahir, ':no_telpon' => $no_telpon, ':email' => $email,
                ':asuransi_id' => $asuransi_id, ':kontak_darurat' => $kontak_darurat, ':alamat' => $alamat
            ]);

            header("Location: pasien.php?status=success");
            exit();
        } catch (PDOException $e) {
            header("Location: pasien.php?status=error&msg=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

// [PROSES HAPUS]
if ($aksi === 'hapus' && isset($_GET['id']) && isset($db)) {
    try {
        $id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM Pasien WHERE patient_id = :id");
        $stmt->execute([':id' => $id]);
        
        header("Location: pasien.php?status=success");
        exit();
    } catch (PDOException $e) {
        header("Location: pasien.php?status=error&msg=" . urlencode("Data terikat rekam medis/kunjungan!"));
        exit();
    }
}

// READ: Ambil daftar pasien untuk ditampilkan ke tabel
$daftar_pasien = [];
$error_message = null;

try {
    if (isset($db)) {
        $query = $db->query("SELECT * FROM Pasien ORDER BY patient_id ASC");
        $daftar_pasien = $query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error pada pasien.php: " . $e->getMessage());
    $error_message = "Gagal memuat data pasien.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pasien - Dokter Mandiri</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">Dokter Mandiri</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="pasien.php" class="active">Manajemen Pasien</a></li>
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
                <div class="table-container patient-scroll-box">
                    <div class="table-header">
                        <h2>Data Pasien</h2>
                        <button class="btn btn-small" id="btnAdd">+ Tambah Pasien</button>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div style="padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9rem; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;">
                            <?= htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>ID Pasien</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Tanggal Lahir</th>
                                <th>Jenis Kelamin</th>
                                <th>Alamat</th>
                                <th>No. Telepon</th>
                                <th>Email</th>
                                <th>Kontak Darurat</th>
                                <th>ID Asuransi</th>
                                <th>Riwayat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftar_pasien)): ?>
                                <?php foreach ($daftar_pasien as $pasien): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pasien['patient_id']); ?></td>
                                        <td><?= htmlspecialchars($pasien['nik'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($pasien['nama'] ?? '-'); ?></td>
                                        <td><?= !empty($pasien['tgl_lahir']) ? date('Y-m-d', strtotime($pasien['tgl_lahir'])) : '-'; ?></td>
                                        <td><?= htmlspecialchars($pasien['jenis_kelamin'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($pasien['alamat'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($pasien['no_telpon'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($pasien['email'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($pasien['kontak_darurat'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($pasien['asuransi_id'] ?? '-'); ?></td>
                                        <td>
                                            <a href="riwayat.php?id=<?= urlencode($pasien['patient_id']); ?>" class="btn btn-small">Lihat Riwayat</a>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-small btn-edit" 
                                                    data-id="<?= htmlspecialchars($pasien['patient_id']); ?>"
                                                    data-nik="<?= htmlspecialchars($pasien['nik'] ?? ''); ?>"
                                                    data-nama="<?= htmlspecialchars($pasien['nama'] ?? ''); ?>"
                                                    data-jk="<?= htmlspecialchars($pasien['jenis_kelamin'] ?? ''); ?>"
                                                    data-tgllahir="<?= !empty($pasien['tgl_lahir']) ? date('Y-m-d', strtotime($pasien['tgl_lahir'])) : ''; ?>"
                                                    data-telp="<?= htmlspecialchars($pasien['no_telpon'] ?? ''); ?>"
                                                    data-email="<?= htmlspecialchars($pasien['email'] ?? ''); ?>"
                                                    data-asuransi="<?= htmlspecialchars($pasien['asuransi_id'] ?? ''); ?>"
                                                    data-darurat="<?= htmlspecialchars($pasien['kontak_darurat'] ?? ''); ?>"
                                                    data-alamat="<?= htmlspecialchars($pasien['alamat'] ?? ''); ?>">
                                                Edit
                                            </button>
                                            
                                            <?php if (in_array($role_aktif, ['Admin', 'Resepsionis'])): ?>
                                                <a href="pasien.php?aksi=hapus&id=<?= urlencode($pasien['patient_id']); ?>" class="btn btn-small btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data pasien ini?')">Hapus</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" style="text-align: center; color: #7f8c8d; padding: 20px; font-style: italic;">
                                        Belum ada data pasien terdaftar. Silakan tambahkan data melalui tombol di atas.
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
        <div class="modal-content" style="max-width: 700px;">
            <span class="close-modal">&times;</span>
            <h2 id="modalTitle" style="margin-bottom: 1rem;">Form Data Pasien</h2>
            
            <form id="patientForm" method="POST" action="pasien.php?aksi=tambah"> 
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    
                    <div class="form-group">
                        <label for="patient_id">ID Pasien</label>
                        <input type="text" id="patient_id" name="patient_id" class="form-control" placeholder="Otomatis / Manual ID" required>
                    </div>
                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" id="nik" name="nik" class="form-control" maxlength="16" placeholder="16 Digit NIK" required autocomplete="off">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="form-control" required>
                            <option value="">Pilih...</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tgl_lahir">Tanggal Lahir</label>
                        <input type="date" id="tgl_lahir" name="tgl_lahir" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="no_telpon">No. Telepon</label>
                        <input type="text" id="no_telpon" name="no_telpon" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="email@contoh.com">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="asuransi_id">ID Asuransi</label>
                        <input type="text" id="asuransi_id" name="asuransi_id" class="form-control" placeholder="Kosongkan jika tidak ada">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="kontak_darurat">Kontak Darurat</label>
                        <input type="text" id="kontak_darurat" name="kontak_darurat" class="form-control" required>
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="alamat">Alamat Lengkap</label>
                        <textarea id="alamat" name="alamat" class="form-control" rows="2" required></textarea>
                    </div>

                </div>
                
                <div style="margin-top: 1rem; text-align: right;">
                    <button type="submit" id="btnSubmitForm" class="btn" style="width: auto; padding: 0.7rem 2rem;">Simpan Data</button>
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
            const form = document.getElementById("patientForm");
            const modalTitle = document.getElementById("modalTitle");
            const btnSubmitForm = document.getElementById("btnSubmitForm");
            const patientIdInput = document.getElementById("patient_id");

            if(btnAdd && modal) { 
                btnAdd.onclick = function() { 
                    form.reset();
                    modalTitle.textContent = "Form Tambah Data Pasien";
                    btnSubmitForm.textContent = "Simpan Data Baru";
                    form.action = "pasien.php?aksi=tambah";
                    patientIdInput.readOnly = false;
                    modal.style.display = "flex"; 
                } 
            }

            document.querySelectorAll(".btn-edit").forEach(button => {
                button.onclick = function() {
                    modalTitle.textContent = "Form Ubah/Edit Data Pasien";
                    btnSubmitForm.textContent = "Simpan Perubahan Data";
                    form.action = "pasien.php?aksi=edit";
                    
                    patientIdInput.value = this.getAttribute("data-id");
                    patientIdInput.readOnly = true; 
                    
                    document.getElementById("nik").value = this.getAttribute("data-nik");
                    document.getElementById("nama").value = this.getAttribute("data-nama");
                    document.getElementById("jenis_kelamin").value = this.getAttribute("data-jk");
                    document.getElementById("tgl_lahir").value = this.getAttribute("data-tgllahir");
                    document.getElementById("no_telpon").value = this.getAttribute("data-telp");
                    document.getElementById("email").value = this.getAttribute("data-email");
                    document.getElementById("asuransi_id").value = this.getAttribute("data-asuransi");
                    document.getElementById("kontak_darurat").value = this.getAttribute("data-darurat");
                    document.getElementById("alamat").value = this.getAttribute("data-alamat");
                    
                    modal.style.display = "flex";
                }
            });

            if(closeModal && modal) { closeModal.onclick = function() { modal.style.display = "none"; } }
            window.onclick = function(event) { if (event.target == modal) { modal.style.display = "none"; } }
        }

        function checkUrlStatus() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') {
                showNotification("Aksi berhasil diproses dengan sukses!", "success");
            } else if (urlParams.get('status') === 'error') {
                const msg = urlParams.get('msg') || "Terjadi kegagalan memproses data.";
                showNotification(decodeURIComponent(msg.replace(/\+/g, ' ')), "error");
            }
        }

        function logout() {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                localStorage.removeItem("userLogin");
                setTimeout(() => { window.location.href = "proses_logout.php"; }, 500);
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
            setTimeout(() => notification.remove(), 4000);
        }
    </script>
</body>
</html>