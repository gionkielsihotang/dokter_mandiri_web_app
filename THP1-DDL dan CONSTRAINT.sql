-- ==============================================================================
-- 1. DROP TABLES (Eksekusi dari level terbawah ke atas untuk menghindari error FK)
-- ==============================================================================
DROP TABLE IF EXISTS Dispensing;
DROP TABLE IF EXISTS Transaksi_Stok;
DROP TABLE IF EXISTS Detail_Resep;
DROP TABLE IF EXISTS Hasil_Penunjang;
DROP TABLE IF EXISTS Batch;
DROP TABLE IF EXISTS Detail_Tagihan;
DROP TABLE IF EXISTS Resep;
DROP TABLE IF EXISTS Tindakan;
DROP TABLE IF EXISTS Order_Penunjang;
DROP TABLE IF EXISTS Triage_Vital;
DROP TABLE IF EXISTS Penerimaan_Barang;
DROP TABLE IF EXISTS Tagihan;
DROP TABLE IF EXISTS Rekam_Medis;
DROP TABLE IF EXISTS Laporan_Eksternal;
DROP TABLE IF EXISTS PO;
DROP TABLE IF EXISTS Kunjungan;
DROP TABLE IF EXISTS User;
DROP TABLE IF EXISTS Report_Agregasi;
DROP TABLE IF EXISTS Lokasi;
DROP TABLE IF EXISTS Supplier;
DROP TABLE IF EXISTS Obat;
DROP TABLE IF EXISTS Dokter;
DROP TABLE IF EXISTS Pasien;
DROP TABLE IF EXISTS Role;


create database praktik_mandiri;
use praktik_mandiri;
-- ==============================================================================
-- 2. LEVEL 0: TABEL MASTER (Tanpa Foreign Key)
-- ==============================================================================

CREATE TABLE Role (
    id_role INT PRIMARY KEY AUTO_INCREMENT,
    nama_role VARCHAR(50) NOT NULL,
    deskripsi TEXT
);

CREATE TABLE Pasien (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    nik VARCHAR(16) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    tgl_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    alamat TEXT,
    no_telpon VARCHAR(20),
    email VARCHAR(100),
    kontak_darurat VARCHAR(50),
    asuransi_id VARCHAR(50),

    CONSTRAINT chk_nik
    CHECK (CHAR_LENGTH(nik)=16)
);

CREATE TABLE Dokter (
    doctor_id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    sip_no VARCHAR(50) UNIQUE NOT NULL,
    spesialisasi VARCHAR(100),
    jadwal_id VARCHAR(50)
);

CREATE TABLE Obat (
    obat_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_obat VARCHAR(150) NOT NULL,
    bentuk_sediaan VARCHAR(50),
    satuan VARCHAR(20),
    kategori VARCHAR(50)
);

CREATE TABLE Supplier (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_supplier VARCHAR(100) NOT NULL,
    alamat TEXT,
    kontak_supplier VARCHAR(50)
);

CREATE TABLE Lokasi (
    lokasi_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_lokasi VARCHAR(100) NOT NULL,
    tipe_lokasi VARCHAR(50),
    deskripsi TEXT
);

CREATE TABLE Report_Agregasi (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    periode_mulai DATE NOT NULL,
    periode_akhir DATE NOT NULL,
    jenis_laporan VARCHAR(50),
    tanggal_generate DATETIME DEFAULT CURRENT_TIMESTAMP,
    keterangan TEXT
);

-- ==============================================================================
-- 3. LEVEL 1: TABEL DENGAN 1 TINGKAT KETERGANTUNGAN
-- ==============================================================================

CREATE TABLE User (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    kontak VARCHAR(50),
    id_role INT,
    FOREIGN KEY (id_role) REFERENCES Role(id_role) ON DELETE SET NULL
);

CREATE TABLE Kunjungan (
    visit_id INT PRIMARY KEY AUTO_INCREMENT,
    tgl_kunjungan DATE NOT NULL,
    jenis_layanan VARCHAR(50),
    antrian_no INT,
    waktu_datang DATETIME,
    waktu_selesai DATETIME,
    status VARCHAR(50),
    patient_id INT NOT NULL,
    doctor_id INT,
    FOREIGN KEY (patient_id) REFERENCES Pasien(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES Dokter(doctor_id) ON DELETE SET NULL
);

CREATE TABLE PO (
    po_id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal_po DATE NOT NULL,
    status_po VARCHAR(50),
    total_po DECIMAL(15,2),
    supplier_id INT NOT NULL,

    CONSTRAINT chk_total_po
    CHECK (total_po >= 0),

    FOREIGN KEY (supplier_id)
    REFERENCES Supplier(supplier_id)
    ON DELETE CASCADE
);

CREATE TABLE Laporan_Eksternal (
    laporan_id INT PRIMARY KEY AUTO_INCREMENT,
    jenis_laporan VARCHAR(50),
    tujuan VARCHAR(100),
    tanggal_kirim DATE,
    file_laporan VARCHAR(255),
    status_kirim VARCHAR(50),
    report_id INT NOT NULL,
    FOREIGN KEY (report_id) REFERENCES Report_Agregasi(report_id) ON DELETE CASCADE
);

-- ==============================================================================
-- 4. LEVEL 2: TABEL KETERGANTUNGAN LANJUTAN
-- ==============================================================================


CREATE TABLE Rekam_Medis (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal_catatan DATE,
    vital_summary TEXT,
    tinggi_badan DECIMAL(5,2),
    berat_badan DECIMAL(5,2),
    anamnesa TEXT,
    pemeriksaan_fisik TEXT,
    catatan_klinis TEXT,
    riwayat_penyakit TEXT,
    alergi_obat_makanan TEXT,
    visit_id INT UNIQUE NOT NULL,

    CONSTRAINT chk_tinggi_badan
    CHECK (tinggi_badan > 0),

    CONSTRAINT chk_berat_badan
    CHECK (berat_badan > 0),

    FOREIGN KEY (visit_id)
    REFERENCES Kunjungan(visit_id)
    ON DELETE CASCADE
);

CREATE TABLE Tagihan (
    tagihan_id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal_tagihan DATE NOT NULL,
    diskon DECIMAL(15,2) DEFAULT 0,
    metode_pembayaran VARCHAR(50),
    asuransi_id VARCHAR(50),
    status VARCHAR(50),
    visit_id INT NOT NULL,

    CONSTRAINT chk_diskon
    CHECK (diskon >= 0),

    FOREIGN KEY (visit_id)
    REFERENCES Kunjungan(visit_id)
    ON DELETE CASCADE
);

CREATE TABLE Penerimaan_Barang (
    gr_id INT PRIMARY KEY AUTO_INCREMENT,
    faktur_no VARCHAR(50),
    po_id INT NOT NULL,
    FOREIGN KEY (po_id) REFERENCES PO(po_id) ON DELETE CASCADE
);

-- ==============================================================================
-- 5. LEVEL 3: TABEL TRANSAKSI/DETAIL DARI REKAM MEDIS & INVENTORY
-- ==============================================================================

CREATE TABLE Triage_Vital (
    triage_id INT PRIMARY KEY AUTO_INCREMENT,
    tekanan_darah VARCHAR(20),
    nadi INT,
    suhu DECIMAL(4,2),
    spO2 INT,
    keluhan_utama TEXT,
    riwayat_alergi TEXT,
    riwayat_obat TEXT,
    record_id INT NOT NULL,

    CONSTRAINT chk_nadi
    CHECK (nadi > 0),

    CONSTRAINT chk_suhu
    CHECK (suhu BETWEEN 30 AND 45),

    CONSTRAINT chk_spo2
    CHECK (spO2 BETWEEN 0 AND 100),

    FOREIGN KEY (record_id)
    REFERENCES Rekam_Medis(record_id)
    ON DELETE CASCADE
);

CREATE TABLE Order_Penunjang (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    jenis_pemeriksaan VARCHAR(100),
    tanggal_order DATETIME,
    status_order VARCHAR(50),
    record_id INT NOT NULL,
    FOREIGN KEY (record_id) REFERENCES Rekam_Medis(record_id) ON DELETE CASCADE
);

CREATE TABLE Tindakan (
    tindakan_id INT PRIMARY KEY AUTO_INCREMENT,
    jenis_tindakan VARCHAR(100),
    tanggal_tindakan DATETIME,
    keterangan TEXT,
    record_id INT NOT NULL,
    FOREIGN KEY (record_id) REFERENCES Rekam_Medis(record_id) ON DELETE CASCADE
);

CREATE TABLE Resep (
    resep_id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal_resep DATETIME,
    catatan_dokter TEXT,
    status_resep VARCHAR(50),
    record_id INT NOT NULL,
    doctor_id INT,
    FOREIGN KEY (record_id) REFERENCES Rekam_Medis(record_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES Dokter(doctor_id) ON DELETE SET NULL
);

CREATE TABLE Detail_Tagihan (
    detail_tagihan_id INT PRIMARY KEY AUTO_INCREMENT,
    jenis_item VARCHAR(50),
    harga_satuan DECIMAL(15,2),
    deskripsi TEXT,
    tagihan_id INT NOT NULL,

    CONSTRAINT chk_harga_satuan
    CHECK (harga_satuan >= 0),

    FOREIGN KEY (tagihan_id)
    REFERENCES Tagihan(tagihan_id)
    ON DELETE CASCADE
);

CREATE TABLE Batch (
    batch_id INT PRIMARY KEY AUTO_INCREMENT,
    expiry_date DATE,
    harga_beli DECIMAL(15,2),
    lokasi_rak VARCHAR(50),
    gr_id INT NOT NULL,
    obat_id INT NOT NULL,

    CONSTRAINT chk_harga_beli
    CHECK (harga_beli >= 0),

    FOREIGN KEY (gr_id)
    REFERENCES Penerimaan_Barang(gr_id)
    ON DELETE CASCADE,

    FOREIGN KEY (obat_id)
    REFERENCES Obat(obat_id)
    ON DELETE CASCADE
);

-- ==============================================================================
-- 6. LEVEL 4 & 5: UJUNG CABANG RELASI (Hasil, Stok, Dispensing)
-- ==============================================================================

CREATE TABLE Hasil_Penunjang (
    hasil_id INT PRIMARY KEY AUTO_INCREMENT,
    hasil TEXT,
    satuan VARCHAR(20),
    tanggal_hasil DATETIME,
    order_id INT NOT NULL,

    CONSTRAINT chk_tanggal_hasil
    CHECK (tanggal_hasil IS NOT NULL),

    FOREIGN KEY (order_id)
    REFERENCES Order_Penunjang(order_id)
    ON DELETE CASCADE
);

CREATE TABLE Detail_Resep (
    detail_id INT PRIMARY KEY AUTO_INCREMENT,
    dosis VARCHAR(50),
    rute VARCHAR(50),
    frekuensi VARCHAR(50),
    durasi VARCHAR(50),
    jumlah INT,
    instruksi_khusus TEXT,
    resep_id INT NOT NULL,
    obat_id INT NOT NULL,

    CONSTRAINT chk_jumlah_obat
    CHECK (jumlah > 0),

    FOREIGN KEY (resep_id)
    REFERENCES Resep(resep_id)
    ON DELETE CASCADE,

    FOREIGN KEY (obat_id)
    REFERENCES Obat(obat_id)
    ON DELETE CASCADE
);

CREATE TABLE Transaksi_Stok (
    transaksi_id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATETIME,
    jenis_transaksi VARCHAR(50),
    jumlah INT,
    referensi VARCHAR(100),
    keterangan TEXT,
    batch_id INT NOT NULL,
    lokasi_id INT NOT NULL,

    CONSTRAINT chk_jumlah_stok
    CHECK (jumlah <> 0),

    FOREIGN KEY (batch_id)
    REFERENCES Batch(batch_id)
    ON DELETE CASCADE,

    FOREIGN KEY (lokasi_id)
    REFERENCES Lokasi(lokasi_id)
    ON DELETE CASCADE
);

CREATE TABLE Dispensing (
    dispensing_id INT PRIMARY KEY AUTO_INCREMENT,
    edukasi_pasien TEXT,
    serah_terima VARCHAR(100),
    petugas_id INT, -- Berelasi dengan tabel User
    detail_id INT NOT NULL,
    FOREIGN KEY (detail_id) REFERENCES Detail_Resep(detail_id) ON DELETE CASCADE,
    FOREIGN KEY (petugas_id) REFERENCES User(user_id) ON DELETE SET NULL
);