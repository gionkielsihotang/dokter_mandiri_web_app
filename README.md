# Sistem Informasi Praktik Dokter Mandiri

Sistem informasi berbasis web yang dikembangkan untuk membantu pengelolaan layanan kesehatan pada praktik dokter mandiri. Fitur utama meliputi manajemen pasien, rekam medis, resep dan stok obat, penjadwalan kunjungan, transaksi pembayaran, serta penyusunan laporan. Proyek ini dibuat sebagai implementasi dan penerapan konsep basis data relasional serta pemrograman SQL dalam lingkungan yang menyerupai kebutuhan dunia nyata.

---

## 1. Anggota Kelompok 3

* **Gion Yehezkiel Sihotang** | 255150401111041 | *Project Manager*
* **Nareva Tri Djuwita Rahmawati** | 255150401111043 | *Database Architect*
* **Michael Yordan Sisokhi Zebua** | 255150401111047 | *Database Architect*
* **Rifqi Apriliano Putrawan** | 255150401111045 | *Front-end Developer*
* **I Made Nayaka Pradnyana** | 255150400111067 | *Backend Developer*

---

## 2. Teknologi yang Digunakan

* **Database:** MySQL (InnoDB Storage Engine)
* **Backend:** PHP Native
* **Front-end:** HTML & Vanilla CSS
* **Web Server:** XAMPP (Apache & MySQL)

---

## 3. Struktur File Database (.sql)

Struktur database dibagi menjadi beberapa tahap mengikuti ketentuan penugasan:

* **Tahap 1:** Perancangan dan Implementasi Database (Skema DDL & Constraints).
* **Tahap 2:** Manipulasi Data dan Query Kompleks (DML & Complex Queries).
* **Tahap 3:** Stored Programs (Procedures & Functions) serta Views.
* **Tahap 4:** Triggers dan Otomatisasi (Logika FEFO & Audit Logging).

---

## 4. Fitur Utama (Struktur Menu Sidebar)

1. **Dashboard:** Halaman utama yang menampilkan statistik ringkas operasional klinik.
2. **Manajemen Pasien:** Tempat mengatur data master pasien beserta validasi NIK 16 digit (CRUD).
3. **Jadwal/Kunjungan:** Pengelolaan jadwal praktik dokter dan antrean pasien secara *real-time*.
4. **Rekam Medis:** Pencatatan riwayat klinis, anamnesis, diagnosis, dan tindakan medis pasien.
5. **Resep & Dispensing:** Pembuatan resep obat, kustomisasi dosis, dan proses penyerahan obat.
6. **Obat & Stok:** Manajemen inventori logistik obat dengan otomatisasi pengurangan stok berbasis metode FEFO (*First Expired First Out*).
7. **Tagihan:** Pembuatan *invoice* pembayaran komponen periksa, tindakan, dan obat pasien secara transparan (Transaksi ACID).
8. **Laporan:** Fitur pelaporan keuangan (anggaran) dan sebaran rekam medis berbasis database *Views*.
9. **User Management:** Pengaturan akun pengguna dan pembatasan hak akses berbasis peran (*Role-Based Access Control*).

---

## 5. Panduan Instalasi dan Testing Program

### 5.1 Persiapan Database (XAMPP)
1. Buka **XAMPP Control Panel** dan aktifkan modul **Apache** serta **MySQL**.
2. Buka browser dan akses **phpMyAdmin** melalui alamat: `http://localhost/phpmyadmin/`.
3. Buat database baru bernama `db_dokter_mandiri`.
4. Pilih database tersebut, lalu masuk ke menu **Import** untuk memasukkan file-file `.sql` Anda secara berurutan sesuai tahap pengerjaan.

### 5.2 Konfigurasi File Aplikasi
1. Pindahkan folder proyek aplikasi Anda ke dalam direktori root XAMPP di `C:\xampp\htdocs\`. (Pastikan nama foldernya mudah diakses, misal: `htdocs/medcare`).
2. Buka file konfigurasi koneksi database PHP Anda (misal `config.php` atau `database.php`), kemudian sesuaikan kredensialnya:
   ```php
   $host = "localhost";
   $user = "root";
   $password = "";
   $database = "db_dokter_mandiri";


### 5.3 Mengakses Website
1. Buka browser dan ketik alamat URL lokal sesuai nama folder Anda, contoh: http://localhost/medcare/
2. Sistem akan otomatis mengarahkan Anda ke halaman Login.
3. Login dengan menggunakan salah satu dari daftar username dan password berikut:
| user_id | nama            | username      | password             |
|---------|-----------------|---------------|----------------------|
| 1       | Andi Admin      | admin1        | pass_admin_123       |
| 2       | dr. Andi Wijaya | dr_andi       | pass_dokter_456      |
| 3       | Siti Perawat    | perawat_siti  | pass_rawat_789       |
| 4       | Randi Apoteker  | apoteker_randi| pass_apotek_abc      |
| 5       | Amel Kasir      | kasir_amel    | pass_kasir_def       |
| 6       | Budi Gudang     | gudang_budi   | pass_gudang_ghi      |
| 7       | Ria Medis       | perekam_ria   | pass_medis_jkl       |
| 8       | Super User      | superuser     | pass_root_secure     |
| 9       | Hendra Manager  | manager_hendra| pass_mngr_990        |
| 10      | Maya Perawat    | perawat_maya  | pass_maya_xyz        |
