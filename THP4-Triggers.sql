USE praktik_mandiri;

-- 0. PERSIAPAN STRUKTUR (Jalankan Sekali Saja)

-- Menggunakan ALTER TABLE karena ini adalah best practice untuk relasi 1:1
-- (Abaikan jika akan muncul warning error kolom sudah ada saat di-run ulang)
ALTER TABLE Pasien ADD COLUMN IF NOT EXISTS nomor_rm VARCHAR(30);

-- Membuat Tabel Audit Logging (Sesuai rubrik Audit Trail 15%)
CREATE TABLE IF NOT EXISTS Log_Audit_Sistem (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tabel VARCHAR(50),
    jenis_aksi VARCHAR(20),
    waktu_eksekusi DATETIME,
    keterangan TEXT,
    pengguna VARCHAR(50)
);

-- TRIGGER 1: Auto-generate Nomor Rekam Medis (Auto-Update)
-- Skenario: Otomatis mengisi kolom nomor_rm saat pendaftaran pasien baru
DELIMITER $$

CREATE TRIGGER trg_generate_rm_pasien
BEFORE INSERT ON Pasien
FOR EACH ROW
BEGIN
    DECLARE v_next_id INT;
    
    -- Mencari ID selanjutnya untuk ditaruh di nomor RM
    SELECT IFNULL(MAX(patient_id), 0) + 1 INTO v_next_id FROM Pasien;
    
    -- Format: RM-TahunBulan-ID (Misal: RM-202606-0001)
    SET NEW.nomor_rm = CONCAT('RM-', DATE_FORMAT(CURDATE(), '%Y%m'), '-', LPAD(v_next_id, 4, '0'));
END $$
DELIMITER ;

-- TRIGGER 2: Update Total Tagihan (Maintenance Denormalized)
-- Skenario: Update kolom total_biaya di Tagihan setiap ada Detail baru
DELIMITER $$

CREATE TRIGGER trg_update_tagihan_insert
AFTER INSERT ON Detail_Tagihan
FOR EACH ROW
BEGIN
    -- Menghitung ulang total dari Detail_Tagihan ke tabel induk Tagihan
    UPDATE Tagihan
    SET total_biaya = (
        SELECT IFNULL(SUM(harga_satuan), 0) 
        FROM Detail_Tagihan 
        WHERE tagihan_id = NEW.tagihan_id
    )
    WHERE tagihan_id = NEW.tagihan_id;
END $$
DELIMITER ;

-- TRIGGER 3: Validasi Jadwal Dokter (Business Rules & Validasi Data)
-- Skenario: Mencegah dokter di-booking di tanggal dan jam yang sama persis
DELIMITER $$

CREATE TRIGGER trg_validasi_jadwal_dokter
BEFORE INSERT ON Kunjungan
FOR EACH ROW
BEGIN
    DECLARE v_overlap INT DEFAULT 0;
    
    -- Mengecek apakah dokter ada kunjungan lain di jam dan hari yang sama
    SELECT COUNT(*) INTO v_overlap
    FROM Kunjungan
    WHERE doctor_id = NEW.doctor_id
      AND DATE(waktu_datang) = DATE(NEW.waktu_datang)
      AND HOUR(waktu_datang) = HOUR(NEW.waktu_datang)
      AND status NOT IN ('Selesai', 'Batal');

    IF v_overlap > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'VALIDASI GAGAL: Jadwal dokter overlap. Dokter sudah memiliki pasien di jam tersebut.';
    END IF;
END $$
DELIMITER ;

-- TRIGGER 4: Log Perubahan Data Sensitif (Audit Trail Logging)
-- Skenario: Mencatat ke log jika dokter merevisi isi catatan rekam medis
DELIMITER $$

CREATE TRIGGER trg_audit_rekam_medis
AFTER UPDATE ON Rekam_Medis
FOR EACH ROW
BEGIN
    -- Hanya catat jika field catatan_klinis benar-benar diubah/diedit
    IF OLD.catatan_klinis != NEW.catatan_klinis THEN
        INSERT INTO Log_Audit_Sistem (nama_tabel, jenis_aksi, waktu_eksekusi, keterangan, pengguna)
        VALUES (
            'Rekam_Medis', 
            'UPDATE', 
            NOW(), 
            CONCAT('Revisi Diagnosa ID ', NEW.record_id, '. LAMA: "', OLD.catatan_klinis, '" | BARU: "', NEW.catatan_klinis, '"'), 
            CURRENT_USER()
        );
    END IF;
END $$
DELIMITER ;

-- TRIGGER 5: Auto-Update Stok Setelah Dispensing Resep (Auto-Update)
-- Skenario: Saat detail resep masuk, otomatis mencatat stok keluar
DELIMITER $$

CREATE TRIGGER trg_auto_kurangi_stok_resep
AFTER INSERT ON Detail_Resep
FOR EACH ROW
BEGIN
    -- Diasumsikan mengambil dari batch_id 1 dan lokasi_id 1 sebagai gudang default farmasi
    INSERT INTO Transaksi_Stok (
        tanggal, 
        jenis_transaksi, 
        jumlah, 
        referensi, 
        keterangan,
        batch_id, 
        lokasi_id
    )
    VALUES (
        NOW(), 
        'Stok Keluar', 
        -NEW.jumlah, -- Dibikin minus agar memotong stok
        CONCAT('RESEP-', NEW.resep_id), 
        'Pemotongan otomatis via Resep Pasien',
        1, 
        1
    );
END $$
DELIMITER ;

-- TRIGGER 6: Validasi Stok Obat Tidak Boleh Negatif (Validasi Data)
-- Skenario: Menahan Transaksi_Stok jika pemotongan membuat saldo stok minus
DELIMITER $$

CREATE TRIGGER trg_validasi_stok_negatif
BEFORE INSERT ON Transaksi_Stok
FOR EACH ROW
BEGIN
    DECLARE v_stok_saat_ini INT DEFAULT 0;
    
    -- Kita hanya cek jika transaksi bersifat memotong stok (minus)
    IF NEW.jumlah < 0 THEN
        -- Ambil total saldo stok fisik yang ada sekarang
        SELECT IFNULL(SUM(jumlah), 0) INTO v_stok_saat_ini
        FROM Transaksi_Stok
        WHERE batch_id = NEW.batch_id AND lokasi_id = NEW.lokasi_id;

        -- Jika saldo sekarang ditambah jumlah potong (minus) hasilnya di bawah 0, tolak!
        IF (v_stok_saat_ini + NEW.jumlah) < 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'VALIDASI GAGAL: Transaksi ditolak karena akan menyebabkan stok obat menjadi negatif (minus).';
        END IF;
    END IF;
END $$
DELIMITER ;

-- BAGIAN TESTING (Untuk Pembuktian di Depan Dosen)

-- 1. Test Trigger 1 (Auto RM)
INSERT INTO Pasien (nama, tgl_lahir) VALUES ('Budi Trigger', '1995-10-10');
SELECT patient_id, nama, nomor_rm FROM Pasien ORDER BY patient_id DESC LIMIT 1;

-- 2. Test Trigger 4 (Audit Trail Logging)
-- (Catatan: Pastikan record_id = 1 ada datanya)
UPDATE Rekam_Medis SET catatan_klinis = 'Tipes (Revisi Diagnosa oleh Dokter Budi)' WHERE record_id = 1;
SELECT * FROM Log_Audit_Sistem;

-- 3. Test Trigger 5 & 6 (Dispensing & Stok Negatif)
-- Trigger 5 akan otomatis jalan karena Stored Procedure sp_tambah_detail_resep melakukan INSERT
CALL sp_tambah_detail_resep(1, 1, 5);
SELECT * FROM Transaksi_Stok ORDER BY tanggal DESC LIMIT 3;