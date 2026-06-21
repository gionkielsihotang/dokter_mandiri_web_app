use praktik_mandiri;


-- ==============================================================================
-- 1. INSERT DATA LEVEL 0 (TABEL MASTER / TANPA FOREIGN KEY)
-- ==============================================================================

-- Tabel Role 
INSERT INTO Role (id_role, nama_role, deskripsi) VALUES
(1, 'Admin', 'Mengelola manajemen user dan konfigurasi sistem'),
(2, 'Dokter Spesialis', 'Melakukan pemeriksaan spesifik sesuai keahlian'),
(3, 'Perawat', 'Melakukan pemeriksaan tanda-tanda vital awal pasien'),
(4, 'Apoteker', 'Mengelola inventori obat dan dispensing resep'),
(5, 'Kasir', 'Mengelola tagihan pasien dan asuransi');


INSERT INTO Pasien (patient_id, nik, nama, tgl_lahir, jenis_kelamin, alamat, no_telpon, email, kontak_darurat, asuransi_id) VALUES
(1, '3515011203950001', 'Budi Santoso', '1995-03-12', 'L', 'Jl. Merdeka No. 10, Malang', '081234567890', 'budi.santoso@email.com', '081299887766', 'BPJS-00123'),
(2, '3515012408920002', 'Dewi Lestari', '1992-08-24', 'P', 'Jl. Mawar No. 45, Batu', '082134567891', 'dewi.lestari@email.com', '082199887765', 'BPJS-00124'),
(3, '3515010505880003', 'Ahmad Hidayat', '1988-05-05', 'L', 'Jl. Soekarno Hatta No. 12, Malang', '083134567892', 'ahmad.h@email.com', '083199887764', 'IND-99211'),
(4, '3515011711990004', 'Siti Rahmawati', '1999-11-17', 'P', 'Jl. Ijen No. 8, Malang', '085234567893', 'siti.rahma@email.com', '085299887763', 'BPJS-00125'),
(5, '3515013001850005', 'Eko Prasetyo', '1985-01-30', 'L', 'Jl. Borobudur No. 22, Blitar', '087734567894', 'eko.p@email.com', '087799887762', NULL),
(6, '3515011402900006', 'Rina Wijaya', '1990-02-14', 'P', 'Jl. Gajahmada No. 100, Pasuruan', '089934567895', 'rina.w@email.com', '089999887761', 'PRU-88231'),
(7, '3515010909930007', 'Hendra Wijaya', '1993-09-09', 'L', 'Jl. Panjaitan No. 14, Malang', '081334567896', 'hendra.w@email.com', '081399887760', 'BPJS-00126'),
(8, '3515012112970008', 'Mega Utami', '1997-12-21', 'P', 'Jl. Danau Toba No. 3, Malang', '081434567897', 'mega.u@email.com', '081499887759', 'ALL-44122'),
(9, '3515010207800009', 'Rudi Hermawan', '1980-07-02', 'L', 'Jl. Kawi No. 56, Malang', '081534567898', 'rudi.h@email.com', '081599887758', NULL),
(10, '3515011806940010', 'Indah Permata', '1994-06-18', 'P', 'Jl. Sigura-gura No. 27, Malang', '081634567899', 'indah.p@email.com', '081699887757', 'BPJS-00127');

-- Tabel Dokter (Hanya 1 Record sesuai instruksi)
INSERT INTO Dokter (doctor_id, nama, sip_no, spesialisasi, jadwal_id) VALUES
(1, 'dr. Andi Wijaya, Sp.PD', 'SIP-2026/001/INTERNAL', 'Spesialis Penyakit Dalam', 'JADWAL-A');

-- Tabel Obat (10 Record)
INSERT INTO Obat (obat_id, nama_obat, bentuk_sediaan, satuan, kategori) VALUES
(1, 'Paracetamol 500mg', 'Tablet', 'Tablet', 'Analgesik'),
(2, 'Amoxicillin 500mg', 'Kapsul', 'Kapsul', 'Antibiotik'),
(3, 'Metformin 500mg', 'Tablet', 'Tablet', 'Antidiabetes'),
(4, 'Amlodipine 5mg', 'Tablet', 'Tablet', 'Antihipertensi'),
(5, 'Omeprazole 20mg', 'Kapsul', 'Kapsul', 'Antasida'),
(6, 'Cetirizine 10mg', 'Tablet', 'Tablet', 'Antihistamin'),
(7, 'Ibuprofen 400mg', 'Tablet', 'Tablet', 'Analgesik'),
(8, 'Antisep Sirup 60ml', 'Sirup', 'Botol', 'Obat Batuk'),
(9, 'Salbutamol Nebules', 'Cairan Inhalasi', 'Ampul', 'Bronkodilator'),
(10, 'Simvastatin 20mg', 'Tablet', 'Tablet', 'Antikolesterol');

-- Tabel Supplier (10 Record)
INSERT INTO Supplier (supplier_id, nama_supplier, alamat, kontak_supplier) VALUES
(1, 'PT. Kimia Farma Trading', 'Jl. Rungkut Industri No. 4, Surabaya', '031-8412345'),
(2, 'PT. Bina San Prima', 'Jl. Kalianak No. 12, Surabaya', '031-7489912'),
(3, 'PT. Enseval Putera Megatrading', 'Jl. Raden Intan No. 5, Malang', '0341-491122'),
(4, 'PT. Mensa Bina Sukses', 'Jl. Letjen Sutoyo No. 88, Malang', '0341-412233'),
(5, 'Anugrah Argon Medica', 'Kawasan Industri SIER, Surabaya', '031-8433445'),
(6, 'PT. Parit Padang Global', 'Jl. Tenaga No. 15, Malang', '0341-472288'),
(7, 'Distributor Alkes Utama', 'Jl. Ciliwung No. 9, Malang', '08111222333'),
(8, 'PT. Tempo Scan Pacific', 'Jl. Ahmad Yani No. 120, Surabaya', '031-8284500'),
(9, 'Kalbe Farma Distribusi', 'Jl. Sunter Elok, Jakarta Utara', '021-6530012'),
(10, 'Phapros Distributor', 'Jl. Simanjuntak No. 10, Semarang', '024-8415511');

-- Tabel Lokasi (10 Record)
INSERT INTO Lokasi (lokasi_id, nama_lokasi, tipe_lokasi, deskripsi) VALUES
(1, 'Gudang Farmasi Utama', 'Gudang', 'Penyimpanan utama semua stok obat masuk'),
(2, 'Apotek Depan', 'Retail', 'Lokasi dispensing obat langsung ke pasien'),
(3, 'Ruang Triage Utama', 'Klinik', 'Tempat pemeriksaan vital sign awal'),
(4, 'Poli Penyakit Dalam', 'Klinik', 'Ruang praktik dokter spesialis'),
(5, 'Ruang Tindakan', 'Klinik', 'Tempat pelaksanaan tindakan medis minor'),
(6, 'Rak Obat Generik A1', 'Rak', 'Penyimpanan obat tablet generik'),
(7, 'Rak Obat Paten B1', 'Rak', 'Penyimpanan khusus obat paten'),
(8, 'Chiller Vaksin', 'Kulkas', 'Penyimpanan suhu dingin untuk vaksin'),
(9, 'Gudang Logistik Alkes', 'Gudang', 'Penyimpanan kasa, jarum suntik, dll'),
(10, 'Poli Umum 1', 'Klinik', 'Ruang pemeriksaan dokter umum');

-- Tabel Report_Agregasi (10 Record)
INSERT INTO Report_Agregasi (report_id, periode_mulai, periode_akhir, jenis_laporan, keterangan) VALUES
(1, '2026-01-01', '2026-01-31', 'Bulanan', 'Laporan bulanan kunjungan pasien Januari 2026'),
(2, '2026-02-01', '2026-02-28', 'Bulanan', 'Laporan bulanan transaksi farmasi Februari 2026'),
(3, '2026-03-01', '2026-03-31', 'Bulanan', 'Laporan triwulan kompilasi penyakit menular'),
(4, '2026-04-01', '2026-04-30', 'Bulanan', 'Laporan penggunaan obat antibiotik April 2026'),
(5, '2026-05-01', '2026-05-31', 'Bulanan', 'Laporan keuangan klaim asuransi Mei 2026'),
(6, '2026-06-01', '2026-06-15', 'Mingguan', 'Laporan berkala tengah bulan kunjungan Poli'),
(7, '2025-01-01', '2025-12-31', 'Tahunan', 'Laporan tahunan operasional klinik 2025'),
(8, '2026-01-01', '2026-03-31', 'Triwulan', 'Laporan stok opname gudang farmasi Q1'),
(9, '2026-04-01', '2026-06-30', 'Triwulan', 'Proyeksi pengadaan obat dan alkes Q2'),
(10, '2026-06-01', '2026-06-07', 'Mingguan', 'Laporan mingguan kasus demam berdarah');


-- ==============================================================================
-- 2. INSERT DATA LEVEL 1 (1 TINGKAT KETERGANTUNGAN)
-- ==============================================================================

-- Tabel User (10 Record - Memiliki korelasi ke 5 Role di atas)
INSERT INTO User (user_id, nama, username, password, kontak, id_role) VALUES
(1, 'Andi Admin', 'admin1', 'pass_admin_123', '0811111111', 1),         -- Admin
(2, 'dr. Andi Wijaya', 'dr_andi', 'pass_dokter_456', '0811111122', 2),   -- Dokter Spesialis
(3, 'Siti Perawat', 'perawat_siti', 'pass_rawat_789', '0811111133', 3),  -- Perawat
(4, 'Randi Apoteker', 'apoteker_randi', 'pass_apotek_abc', '0811111144', 4), -- Apoteker
(5, 'Amel Kasir', 'kasir_amel', 'pass_kasir_def', '0811111155', 5),     -- Kasir
(6, 'Budi Gudang', 'gudang_budi', 'pass_gudang_ghi', '0811111166', 4),   -- Apoteker/Staf Farmasi
(7, 'Ria Medis', 'perekam_ria', 'pass_medis_jkl', '0811111177', 1),      -- Admin/Staf Registrasi
(8, 'Super User', 'superuser', 'pass_root_secure', '0811111188', 1),     -- Admin
(9, 'Hendra Manager', 'manager_hendra', 'pass_mngr_990', '0811111199', 1), -- Admin/Manajemen
(10, 'Maya Perawat', 'perawat_maya', 'pass_maya_xyz', '0811111100', 3);   -- Perawat

-- Tabel Kunjungan (10 Record - Berelasi ke Pasien & Dokter id=1)
INSERT INTO Kunjungan (visit_id, tgl_kunjungan, jenis_layanan, antrian_no, waktu_datang, waktu_selesai, status, patient_id, doctor_id) VALUES
(1, '2026-06-16', 'Rawat Jalan', 1, '2026-06-16 08:00:00', '2026-06-16 08:30:00', 'Selesai', 1, 1),
(2, '2026-06-16', 'Rawat Jalan', 2, '2026-06-16 08:20:00', '2026-06-16 08:50:00', 'Selesai', 2, 1),
(3, '2026-06-16', 'Rawat Jalan', 3, '2026-06-16 08:45:00', '2026-06-16 09:15:00', 'Selesai', 3, 1),
(4, '2026-06-16', 'Rawat Jalan', 4, '2026-06-16 09:10:00', '2026-06-16 09:40:00', 'Selesai', 4, 1),
(5, '2026-06-16', 'Rawat Jalan', 5, '2026-06-16 09:30:00', '2026-06-16 10:00:00', 'Selesai', 5, 1),
(6, '2026-06-16', 'Rawat Jalan', 6, '2026-06-16 10:00:00', '2026-06-16 10:30:00', 'Selesai', 6, 1),
(7, '2026-06-16', 'Rawat Jalan', 7, '2026-06-16 10:20:00', '2026-06-16 10:50:00', 'Selesai', 7, 1),
(8, '2026-06-16', 'Rawat Jalan', 8, '2026-06-16 10:45:00', '2026-06-16 11:15:00', 'Selesai', 8, 1),
(9, '2026-06-16', 'Rawat Jalan', 9, '2026-06-16 11:10:00', '2026-06-16 11:40:00', 'Selesai', 9, 1),
(10, '2026-06-16', 'Rawat Jalan', 10, '2026-06-16 11:30:00', '2026-06-16 12:00:00', 'Selesai', 10, 1);

-- Tabel PO (10 Record)
INSERT INTO PO (po_id, tanggal_po, status_po, total_po, supplier_id) VALUES
(1, '2026-06-01', 'Selesai', 5000000.00, 1),
(2, '2026-06-02', 'Selesai', 3500000.00, 2),
(3, '2026-06-03', 'Selesai', 7200000.00, 3),
(4, '2026-06-04', 'Selesai', 1200000.00, 4),
(5, '2026-06-05', 'Selesai', 4300000.00, 5),
(6, '2026-06-06', 'Selesai', 2900000.00, 6),
(7, '2026-06-07', 'Selesai', 1500000.00, 7),
(8, '2026-06-08', 'Selesai', 8800000.00, 8),
(9, '2026-06-09', 'Selesai', 6100000.00, 9),
(10, '2026-06-10', 'Selesai', 3400000.00, 10);

-- Tabel Laporan_Eksternal (10 Record)
INSERT INTO Laporan_Eksternal (laporan_id, jenis_laporan, tujuan, tanggal_kirim, file_laporan, status_kirim, report_id) VALUES
(1, 'Kunjungan', 'Dinas Kesehatan Kota', '2026-02-02', 'pdf_jan_2026.pdf', 'Terkirim', 1),
(2, 'Farmasi', 'Dinas Kesehatan Kota', '2026-03-02', 'pdf_feb_2026.pdf', 'Terkirim', 2),
(3, 'Penyakit Menular', 'Kemenkes RI', '2026-04-02', 'triwulan_1.pdf', 'Terkirim', 3),
(4, 'Narkotika', 'BPOM', '2026-05-02', 'antibiotik_apr.pdf', 'Terkirim', 4),
(5, 'Klaim BPJS', 'BPJS Kesehatan', '2026-06-02', 'klaim_mei_2026.pdf', 'Terkirim', 5),
(6, 'Internal', 'Direksi Klinik', '2026-06-16', 'internal_juni.pdf', 'Proses', 6),
(7, 'Tahunan', 'Yayasan Utama', '2026-01-15', 'tahunan_2025.pdf', 'Terkirim', 7),
(8, 'Logistik', 'Internal Audit', '2026-04-05', 'stok_q1.pdf', 'Terkirim', 8),
(9, 'Anggaran', 'Manajemen Inti', '2026-04-10', 'proyeksi_q2.pdf', 'Terkirim', 9),
(10, 'Epidemiologi', 'Puskesmas Lokal', '2026-06-08', 'dbd_minggu1.pdf', 'Terkirim', 10);


-- ==============================================================================
-- 3. INSERT DATA LEVEL 2 (KETERGANTUNGAN LANJUTAN)
-- ==============================================================================

-- Tabel Rekam_Medis (10 Record)
INSERT INTO Rekam_Medis (record_id, tanggal_catatan, vital_summary, tinggi_badan, berat_badan, anamnesa, pemeriksaan_fisik, catatan_klinis, riwayat_penyakit, alergi_obat_makanan, visit_id) VALUES
(1, '2026-06-16', 'TD: 120/80, N: 80, S: 36.5', 165.00, 60.00, 'Pusing dan lemas sejak 2 hari', 'Anemis (+)', 'Suspek Anemia', 'Tidak ada', 'Tidak ada', 1),
(2, '2026-06-16', 'TD: 140/90, N: 84, S: 36.2', 170.00, 75.00, 'Kontrol rutin hipertensi', 'Jantung normal', 'Hipertensi Stage 1', 'Hipertensi', 'Seafood', 2),
(3, '2026-06-16', 'TD: 110/70, N: 90, S: 38.5', 155.00, 48.00, 'Demam menggigil', 'Suhu tinggi', 'Suspek Febris', 'Tidak ada', 'Amoxicillin', 3),
(4, '2026-06-16', 'TD: 120/70, N: 78, S: 36.6', 160.00, 55.00, 'Nyeri ulu hati', 'Nyeri tekan epigastrium', 'Dyspepsia Syndrome', 'Gastritis', 'Tidak ada', 4),
(5, '2026-06-16', 'TD: 130/80, N: 88, S: 36.4', 175.00, 80.00, 'Cek gula darah rutin', 'Kondisi umum baik', 'Diabetes Melitus Tipe 2', 'Diabetes', 'Tidak ada', 5),
(6, '2026-06-16', 'TD: 115/75, N: 76, S: 36.0', 150.00, 52.00, 'Batuk berdahak 5 hari', 'Faring hiperemis', 'Acute Pharyngitis', 'Tidak ada', 'Debu', 6),
(7, '2026-06-16', 'TD: 125/85, N: 82, S: 36.7', 168.00, 68.00, 'Nyeri sendi lutut kiri', 'Crepitus knee (+)', 'Osteoarthritis', 'Tidak ada', 'Tidak ada', 7),
(8, '2026-06-16', 'TD: 110/80, N: 80, S: 36.3', 158.00, 50.00, 'Bersin setiap pagi hari', 'Mukosa pucat', 'Rhinitis Alergi', 'Asma', 'Dingin', 8),
(9, '2026-06-16', 'TD: 135/85, N: 85, S: 36.8', 163.00, 70.00, 'Leher bagian kaku', 'Kaku kuduk (-)', 'Hiperkolesterolemia', 'Tidak ada', 'Tidak ada', 9),
(10, '2026-06-16', 'TD: 120/80, N: 81, S: 37.2', 172.00, 63.00, 'Luka robek kecil di tangan', 'Vulnus laceratum 2cm', 'Rawat Luka', 'Tidak ada', 'Tidak ada', 10);

-- Tabel Tagihan (10 Record)
INSERT INTO Tagihan (tagihan_id, tanggal_tagihan, diskon, metode_pembayaran, asuransi_id, status, visit_id) VALUES
(1, '2026-06-16', 0.00, 'Tunai', NULL, 'Lunas', 1),
(2, '2026-06-16', 10000.00, 'Debit', 'BPJS-00124', 'Lunas', 2),
(3, '2026-06-16', 0.00, 'Asuransi', 'IND-99211', 'Pending', 3),
(4, '2026-06-16', 0.00, 'Tunai', NULL, 'Lunas', 4),
(5, '2026-06-16', 0.00, 'QRIS', 'BPJS-00125', 'Lunas', 5),
(6, '2026-06-16', 5000.00, 'Tunai', NULL, 'Lunas', 6),
(7, '2026-06-16', 0.00, 'Debit', 'PRU-88231', 'Lunas', 7),
(8, '2026-06-16', 0.00, 'Asuransi', 'BPJS-00126', 'Pending', 8),
(9, '2026-06-16', 0.00, 'Tunai', NULL, 'Lunas', 9),
(10, '2026-06-16', 20000.00, 'QRIS', 'BPJS-00127', 'Lunas', 10);

-- Tabel Penerimaan_Barang (10 Record)
INSERT INTO Penerimaan_Barang (gr_id, faktur_no, po_id) VALUES
(1, 'FAK-001', 1),
(2, 'FAK-002', 2),
(3, 'FAK-003', 3),
(4, 'FAK-004', 4),
(5, 'FAK-005', 5),
(6, 'FAK-006', 6),
(7, 'FAK-007', 7),
(8, 'FAK-008', 8),
(9, 'FAK-009', 9),
(10, 'FAK-010', 10);


-- ==============================================================================
-- 4. INSERT DATA LEVEL 3 (TRANSAKSI DETAIL REKAM MEDIS & INVENTORY BATCH)
-- ==============================================================================

-- Tabel Triage_Vital (10 Record)
INSERT INTO Triage_Vital (triage_id, tekanan_darah, nadi, suhu, spO2, keluhan_utama, riwayat_alergi, riwayat_obat, record_id) VALUES
(1, '120/80', 80, 36.5, 99, 'Pusing kepala', 'Tidak ada', 'Paracetamol', 1),
(2, '140/90', 84, 36.2, 98, 'Kontrol rutin obat darah tinggi', 'Seafood', 'Amlodipine', 2),
(3, '110/70', 90, 38.5, 96, 'Badan panas tinggi', 'Amoxicillin', 'Sanmol', 3),
(4, '120/70', 78, 36.6, 99, 'Nyeri ulu hati perih', 'Tidak ada', 'Antasida', 4),
(5, '130/80', 88, 36.4, 97, 'Kaki sering kesemutan', 'Tidak ada', 'Metformin', 5),
(6, '115/75', 76, 36.0, 99, 'Batuk gatal berdahak', 'Debu', 'OBH', 6),
(7, '125/85', 82, 36.7, 98, 'Lutut nyeri saat digerakkan', 'Tidak ada', 'Voltaren', 7),
(8, '110/80', 80, 36.3, 100, 'Bersin terus menerus', 'Dingin', 'CTM', 8),
(9, '135/85', 85, 36.8, 97, 'Tengkuk leher kaku berat', 'Tidak ada', 'Simvastatin', 9),
(10, '120/80', 81, 37.2, 99, 'Tangan tersayat pisau', 'Tidak ada', 'Tidak ada', 10);

-- Tabel Order_Penunjang (10 Record)
INSERT INTO Order_Penunjang (order_id, jenis_pemeriksaan, tanggal_order, status_order, record_id) VALUES
(1, 'Darah Lengkap (Hb)', '2026-06-16 08:10:00', 'Selesai', 1),
(2, 'Profil Lipid', '2026-06-16 08:30:00', 'Selesai', 2),
(3, 'Widal Test', '2026-06-16 08:55:00', 'Selesai', 3),
(4, 'Tidak Ada', '2026-06-16 09:15:00', 'Batal', 4),
(5, 'Gula Darah Puasa & HbA1c', '2026-06-16 09:35:00', 'Selesai', 5),
(6, 'Tidak Ada', '2026-06-16 10:05:00', 'Batal', 6),
(7, 'Rontgen Genu', '2026-06-16 10:25:00', 'Selesai', 7),
(8, 'Tidak Ada', '2026-06-16 10:50:00', 'Batal', 8),
(9, 'Kolesterol Total', '2026-06-16 11:15:00', 'Selesai', 9),
(10, 'Tidak Ada', '2026-06-16 11:35:00', 'Batal', 10);

-- Tabel Tindakan (10 Record)
INSERT INTO Tindakan (tindakan_id, jenis_tindakan, tanggal_tindakan, keterangan, record_id) VALUES
(1, 'Konsultasi Medis', '2026-06-16 08:15:00', 'Edukasi diet zat besi', 1),
(2, 'Konsultasi Medis', '2026-06-16 08:40:00', 'Edukasi pembatasan garam', 2),
(3, 'Injeksi Antipiretik', '2026-06-16 09:00:00', 'Injeksi Metamizole via IV', 3),
(4, 'Konsultasi Medis', '2026-06-16 09:20:00', 'Edukasi hindari makanan pedas', 4),
(5, 'Edukasi Diabetes', '2026-06-16 09:45:00', 'Edukasi pola hidup sehat', 5),
(6, 'Inhalasi / Nebulizer', '2026-06-16 10:10:00', 'Nebulizer dengan Ventolin', 6),
(7, 'Fisioterapi Ringan', '2026-06-16 10:35:00', 'Kompres hangat sendi', 7),
(8, 'Konsultasi Medis', '2026-06-16 10:55:00', 'Edukasi hindari alergen dingin', 8),
(9, 'Konsultasi Medis', '2026-06-16 11:20:00', 'Diet rendah lemak', 9),
(10, 'Hecting & Rawat Luka', '2026-06-16 11:45:00', 'Penjahitan luka 2 simpul', 10);

-- Tabel Resep (10 Record - Berelasi ke Dokter Tunggal id=1)
INSERT INTO Resep (resep_id, tanggal_resep, catatan_dokter, status_resep, record_id, doctor_id) VALUES
(1, '2026-06-16 08:20:00', 'Diminum rutin setelah makan', 'Selesai', 1, 1),
(2, '2026-06-16 08:45:00', 'Obat darah tinggi jangan distop', 'Selesai', 2, 1),
(3, '2026-06-16 09:05:00', 'Habiskan antibiotik', 'Selesai', 3, 1),
(4, '2026-06-16 09:25:00', 'Makan dulu baru minum obat', 'Selesai', 4, 1),
(5, '2026-06-16 09:50:00', 'Kontrol 2 minggu lagi', 'Selesai', 5, 1),
(6, '2026-06-16 10:15:00', 'Kocok dahulu sebelum diminum', 'Selesai', 6, 1),
(7, '2026-06-16 10:40:00', 'Minum bila nyeri terasa hebat', 'Selesai', 7, 1),
(8, '2026-06-16 11:00:00', 'Menyebabkan kantuk', 'Selesai', 8, 1),
(9, '2026-06-16 11:25:00', 'Minum malam hari sebelum tidur', 'Selesai', 9, 1),
(10, '2026-06-16 11:50:00', 'Jaga perban luka tetap kering', 'Selesai', 10, 1);

-- Tabel Detail_Tagihan (10 Record)
INSERT INTO Detail_Tagihan (detail_tagihan_id, jenis_item, harga_satuan, deskripsi, tagihan_id) VALUES
(1, 'Jasa Dokter', 100000.00, 'Jasa pemeriksaan dokter spesialis', 1),
(2, 'Jasa Dokter', 100000.00, 'Jasa pemeriksaan dokter spesialis', 2),
(3, 'Laboratorium', 150000.00, 'Paket cek demam', 3),
(4, 'Obat-obatan', 45000.00, 'Total resep obat maag', 4),
(5, 'Laboratorium', 85000.00, 'Cek gula darah', 5),
(6, 'Tindakan Medis', 60000.00, 'Biaya sewa alat nebulizer', 6),
(7, 'Penunjang', 200000.00, 'Rontgen genu', 7),
(8, 'Obat-obatan', 30000.00, 'Obat anti alergi', 8),
(9, 'Jasa Dokter', 100000.00, 'Jasa pemeriksaan dokter spesialis', 9),
(10, 'Tindakan Medis', 120000.00, 'Tindakan penjahitan luka', 10);

-- Tabel Batch (10 Record)
INSERT INTO Batch (batch_id, expiry_date, harga_beli, lokasi_rak, gr_id, obat_id) VALUES
(1, '2028-12-01', 5000.00, 'Rak Obat Generik A1', 1, 1),
(2, '2027-06-15', 25000.00, 'Rak Obat Paten B1', 2, 2),
(3, '2028-03-20', 3000.00, 'Rak Obat Generik A1', 3, 3),
(4, '2029-01-10', 2000.00, 'Rak Obat Generik A1', 4, 4),
(5, '2028-10-05', 8000.00, 'Rak Obat Paten B1', 5, 5),
(6, '2027-11-22', 4000.00, 'Rak Obat Generik A1', 6, 6),
(7, '2028-08-14', 6000.00, 'Rak Obat Generik A1', 7, 7),
(8, '2027-02-28', 18000.00, 'Chiller Vaksin', 8, 8),
(9, '2026-12-31', 45000.00, 'Chiller Vaksin', 9, 9),
(10, '2028-05-18', 7500.00, 'Rak Obat Paten B1', 10, 10);


-- ==============================================================================
-- 5. INSERT DATA LEVEL 4 & 5 (UJUNG CABANG RELASI - Hasil, Stok, Dispensing)
-- ==============================================================================

-- Tabel Hasil_Penunjang (10 Record)
INSERT INTO Hasil_Penunjang (hasil_id, hasil, satuan, tanggal_hasil, order_id) VALUES
(1, 'Hemoglobin: 10.2', 'g/dL', '2026-06-16 08:40:00', 1),
(2, 'Kolesterol Total: 240', 'mg/dL', '2026-06-16 09:10:00', 2),
(3, 'Widal Typhi O: 1/320', 'Titer', '2026-06-16 09:30:00', 3),
(4, 'Tidak ada pemeriksaan', 'N/A', '2026-06-16 09:15:00', 4),
(5, 'Gula Darah Puasa: 160', 'mg/dL', '2026-06-16 10:15:00', 5),
(6, 'Tidak ada pemeriksaan', 'N/A', '2026-06-16 10:05:00', 6),
(7, 'Penyempitan celah sendi grade 2', 'Kualitatif', '2026-06-16 11:00:00', 7),
(8, 'Tidak ada pemeriksaan', 'N/A', '2026-06-16 10:50:00', 8),
(9, 'Kolesterol LDL: 155', 'mg/dL', '2026-06-16 11:55:00', 9),
(10, 'Tidak ada pemeriksaan', 'N/A', '2026-06-16 11:35:00', 10);

-- Tabel Detail_Resep (10 Record)
INSERT INTO Detail_Resep (detail_id, dosis, rute, frekuensi, durasi, jumlah, instruksi_khusus, resep_id, obat_id) VALUES
(1, '500mg', 'Oral', '3x1 Sehari', '5 Hari', 15, 'Bila demam saja', 1, 1),
(2, '5mg', 'Oral', '1x1 Sehari', '30 Hari', 30, 'Pagi hari konstan', 2, 4),
(3, '500mg', 'Oral', '3x1 Sehari', '5 Hari', 15, 'Harus dihabiskan', 3, 2),
(4, '20mg', 'Oral', '2x1 Sehari', '7 Hari', 14, 'Sebelum makan', 4, 5),
(5, '500mg', 'Oral', '2x1 Sehari', '30 Hari', 60, 'Bersama makan', 5, 3),
(6, '60ml', 'Oral', '3x1 Cth', '5 Hari', 1, 'Kocok dahulu', 6, 8),
(7, '400mg', 'Oral', '2x1 Sehari', '7 Hari', 14, 'Sesudah makan', 7, 7),
(8, '10mg', 'Oral', '1x1 Sehari', '10 Hari', 10, 'Malam hari sebelum tidur', 8, 6),
(9, '20mg', 'Oral', '1x1 Sehari', '30 Hari', 30, 'Malam hari sebelum tidur', 9, 10),
(10, '500mg', 'Oral', '3x1 Sehari', '3 Hari', 9, 'Anti nyeri luka robek', 10, 1);

-- Tabel Transaksi_Stok (10 Record)
INSERT INTO Transaksi_Stok (transaksi_id, tanggal, jenis_transaksi, jumlah, referensi, keterangan, batch_id, lokasi_id) VALUES
(1, '2026-06-16 08:00:00', 'Stok Masuk', 1000, 'PO-001', 'Penerimaan logistik masuk', 1, 1),
(2, '2026-06-16 08:05:00', 'Stok Masuk', 500, 'PO-002', 'Penerimaan logistik masuk', 2, 1),
(3, '2026-06-16 08:10:00', 'Stok Masuk', 2000, 'PO-003', 'Penerimaan logistik masuk', 3, 1),
(4, '2026-06-16 08:30:00', 'Stok Keluar', -15, 'RESEP-001', 'Pengeluaran obat jalan', 1, 2),
(5, '2026-06-16 08:50:00', 'Stok Keluar', -30, 'RESEP-002', 'Pengeluaran obat jalan', 4, 2),
(6, '2026-06-16 09:10:00', 'Stok Keluar', -15, 'RESEP-003', 'Pengeluaran obat jalan', 2, 2),
(7, '2026-06-16 09:30:00', 'Penyesuaian', -2, 'KOREKSI-01', 'Botol sirup pecah', 8, 2),
(8, '2026-06-16 10:00:00', 'Mutasi Keluar', -100, 'MUT-01', 'Mutasi keluar ke retail', 1, 1),
(9, '2026-06-16 10:00:00', 'Mutasi Masuk', 100, 'MUT-01', 'Penerimaan barang dari gudang', 1, 2),
(10, '2026-06-16 12:00:00', 'Stok Keluar', -9, 'RESEP-010', 'Pengeluaran obat jalan', 1, 2);

-- Tabel Dispensing (10 Record - Berelasi ke Petugas/User id=4)
INSERT INTO Dispensing (dispensing_id, edukasi_pasien, serah_terima, petugas_id, detail_id) VALUES
(1, 'Aturan pakai Paracetamol 3x1 hari jika pusing saja', 'Diserahkan ke Pasien langsung', 4, 1),
(2, 'Pentingnya minum obat Amlodipine teratur tiap pagi', 'Diserahkan ke Istri Pasien', 4, 2),
(3, 'Antibiotik Amoxicillin wajib habis dalam 5 hari', 'Diserahkan ke Pasien langsung', 4, 3),
(4, 'Omeprazole diminum saat perut kosong / sebelum makan', 'Diserahkan ke Suami Pasien', 4, 4),
(5, 'Metformin diminum bersamaan dengan suapan makanan', 'Diserahkan ke Anak Pasien', 4, 5),
(6, 'Sirup dikocok kuat dulu sebelum dituang ke sendok', 'Diserahkan ke Pasien langsung', 4, 6),
(7, 'Ibuprofen diminum hanya jika lutut terasa nyeri', 'Diserahkan ke Pasien langsung', 4, 7),
(8, 'Cetirizine diminum malam hari saja karena bikin kantuk', 'Diserahkan ke Pasien langsung', 4, 8),
(9, 'Simvastatin diminum malam hari untuk efek terbaik', 'Diserahkan ke Pasien langsung', 4, 9),
(10, 'Paracetamol diminum teratur per 8 jam pasca jahit', 'Diserahkan ke Pasien langsung', 4, 10);