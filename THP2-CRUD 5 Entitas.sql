use praktik_mandiri;


-- CRUD TABEL PASIEN
-- Menambahkan data pasien baru ke dalam sistem
INSERT INTO Pasien(nik, nama, tgl_lahir, jenis_kelamin, alamat, no_telpon, email)
VALUES
(
'3515010101010011',
'Mikah',
'2005-01-01',
'L',
'Malang',
'081234567800',
'mikah@email.com'
);

-- Menampilkan seluruh data pasien
SELECT * FROM Pasien;

-- Memperbarui nomor telepon pasien
UPDATE Pasien
SET no_telpon = '081299999999'
WHERE patient_id = 11;

-- Menghapus data pasien berdasarkan ID
DELETE FROM Pasien
WHERE patient_id = 11;

-- CRUD TABEL KUNJUNGAN
-- Menambahkan data kunjungan pasien baru
INSERT INTO Kunjungan
(tgl_kunjungan, jenis_layanan, antrian_no, waktu_datang, status, patient_id, doctor_id)
VALUES
(CURDATE(), 'Rawat Jalan', 11, NOW(), 'Menunggu', 1, 1);

-- Menampilkan seluruh data kunjungan pasien
SELECT * FROM Kunjungan;

-- Mengubah status kunjungan menjadi selesai
UPDATE Kunjungan
SET status = 'Selesai'
WHERE visit_id = 11;

-- Menghapus data kunjungan tertentu
DELETE FROM Kunjungan
WHERE visit_id = 11;


-- CRUD TABEL REKAM MEDIS
-- Menambahkan catatan rekam medis untuk kunjungan pasien
INSERT INTO Rekam_Medis
(tanggal_catatan, vital_summary, anamnesa, visit_id)
VALUES
(CURDATE(), 'TD:120/80, N:80', 'Pasien mengeluh pusing dan lemas', 2);

-- Menampilkan seluruh data rekam medis pasien
SELECT * FROM Rekam_Medis;

-- Memperbarui hasil anamnesa pasien
UPDATE Rekam_Medis
SET anamnesa = 'Pasien mengeluh pusing, lemas, dan mual'
WHERE record_id = 2;

-- Menghapus rekam medis tertentu
DELETE FROM Rekam_Medis
WHERE record_id = 2;


-- CRUD TABEL TAGIHAN
-- Menambahkan tagihan baru untuk kunjungan pasien
INSERT INTO Tagihan
(tanggal_tagihan, diskon, metode_pembayaran, status, visit_id)
VALUES
(CURDATE(), 10000, 'QRIS', 'Belum Lunas', 1);

-- Menampilkan seluruh data tagihan pasien
SELECT * FROM Tagihan;

-- Mengubah status pembayaran tagihan menjadi lunas
UPDATE Tagihan
SET status = 'Lunas'
WHERE tagihan_id = 11;

-- Menghapus data tagihan tertentu
DELETE FROM Tagihan
WHERE tagihan_id = 11;

-- CRUD TABEL OBAT
-- Menambahkan data obat baru ke inventori klinik
INSERT INTO Obat
(nama_obat, bentuk_sediaan, satuan, kategori)
VALUES
('Vitamin C 500mg', 'Tablet', 'Tablet', 'Suplemen');

-- Menampilkan seluruh data obat yang tersedia
SELECT * FROM Obat;

-- Memperbarui kategori obat
UPDATE Obat
SET kategori = 'Vitamin'
WHERE obat_id = 11;

-- Menghapus data obat tertentu dari inventori
DELETE FROM Obat
WHERE obat_id = 11;





