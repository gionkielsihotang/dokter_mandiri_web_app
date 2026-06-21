USE praktik_mandiri;

-- 0. PERSIAPAN STRUKTUR

-- Menambahkan kolom nomor_rm jika belum ada
ALTER TABLE Pasien ADD COLUMN IF NOT EXISTS nomor_rm VARCHAR(30);

-- Membuat Tabel Audit Logging
CREATE TABLE IF NOT EXISTS Log_Audit_Sistem (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tabel VARCHAR(50),
    jenis_aksi VARCHAR(20),
    waktu_eksekusi DATETIME,
    keterangan TEXT,
    pengguna VARCHAR(50)
);

-- 1. TRIGGER: Auto-generate Nomor Rekam Medis Pasien Baru
DELIMITER $$
CREATE TRIGGER trg_generate_rm_pasien
BEFORE INSERT ON Pasien
FOR EACH ROW
BEGIN
    DECLARE v_next_id INT;
    
    -- Mencari ID selanjutnya untuk format RM
    SELECT IFNULL(MAX(patient_id), 0) + 1 INTO v_next_id FROM Pasien;
    
    -- Format: RM-TahunBulan-ID (Misal: RM-202606-0001)
    SET NEW.nomor_rm = CONCAT('RM-', DATE_FORMAT(CURDATE(), '%Y%m'), '-', LPAD(v_next_id, 4, '0'));
END $$
DELIMITER ;

-- 2. TRIGGER: Sinkronisasi Total Tagihan (INSERT, UPDATE, DELETE)
-- Kondisi Update Insert
DELIMITER $$
CREATE TRIGGER trg_update_tagihan_insert
AFTER INSERT ON Detail_Tagihan
FOR EACH ROW
BEGIN
    UPDATE Tagihan
    SET total_biaya = (
        SELECT IFNULL(SUM(harga_satuan), 0) 
        FROM Detail_Tagihan 
        WHERE tagihan_id = NEW.tagihan_id
    )
    WHERE tagihan_id = NEW.tagihan_id;
END $$
DELIMITER ;

-- Kondisi Update Update
DELIMITER $$
CREATE TRIGGER trg_update_tagihan_update
AFTER UPDATE ON Detail_Tagihan
FOR EACH ROW
BEGIN
    UPDATE Tagihan
    SET total_biaya = (
        SELECT IFNULL(SUM(harga_satuan), 0) 
        FROM Detail_Tagihan 
        WHERE tagihan_id = NEW.tagihan_id
    )
    WHERE tagihan_id = NEW.tagihan_id;
END $$
DELIMITER ;

-- Kondisi Update Deelete
DELIMITER $$
CREATE TRIGGER trg_update_tagihan_delete
AFTER DELETE ON Detail_Tagihan
FOR EACH ROW
BEGIN
    UPDATE Tagihan
    SET total_biaya = (
        SELECT IFNULL(SUM(harga_satuan), 0) 
        FROM Detail_Tagihan 
        WHERE tagihan_id = OLD.tagihan_id
    )
    WHERE tagihan_id = OLD.tagihan_id;
END $$
DELIMITER ;

-- 3. TRIGGER: Validasi Jadwal Dokter (Mencegah Overlap)
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

-- 4. TRIGGER: Audit Trail Logging Rekam Medis
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

-- 5. TRIGGER: Auto-Update Stok Setelah Resep Masuk (Metode FEFO)
DELIMITER $$
CREATE TRIGGER trg_auto_kurangi_stok_resep
AFTER INSERT ON Detail_Resep
FOR EACH ROW
BEGIN
    DECLARE v_batch_id INT;
    DECLARE v_lokasi_id INT DEFAULT 1; -- Default lokasi gudang farmasi
    
    -- Mencari batch dengan expiry date terdekat yang stoknya mencukupi
    SELECT b.batch_id INTO v_batch_id
    FROM Batch b
    JOIN Transaksi_Stok ts ON b.batch_id = ts.batch_id
    WHERE b.obat_id = NEW.obat_id
    GROUP BY b.batch_id, b.expiry_date
    HAVING SUM(ts.jumlah) >= NEW.jumlah
    ORDER BY b.expiry_date ASC
    LIMIT 1;

    -- Memotong stok jika batch ditemukan, jika tidak tolak transaksi
    IF v_batch_id IS NOT NULL THEN
        INSERT INTO Transaksi_Stok (
            tanggal, jenis_transaksi, jumlah, referensi, keterangan, batch_id, lokasi_id
        )
        VALUES (
            NOW(), 'Stok Keluar', -NEW.jumlah, CONCAT('RESEP-', NEW.resep_id), 'Pemotongan otomatis FEFO via Resep', v_batch_id, v_lokasi_id
        );
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'VALIDASI GAGAL: Stok obat tidak mencukupi di batch mana pun untuk melayani resep ini!';
    END IF;
END $$
DELIMITER ;

-- 6. TRIGGER: Validasi Stok Obat Tidak Boleh Negatif
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

        -- Jika saldo sekarang ditambah jumlah potong (minus) hasilnya di bawah 0, tolak
        IF (v_stok_saat_ini + NEW.jumlah) < 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'VALIDASI GAGAL: Transaksi ditolak karena akan menyebabkan stok obat menjadi negatif (minus).';
        END IF;
    END IF;
END $$
DELIMITER ;

-- BAGIAN TESTING
-- SKENARIO PENGUJIAN DUA ARAH (POSITIVE & NEGATIVE TESTING)

-- trg_generate_rm_pasien 1 Positif
-- Memasukkan data pasien baru dengan semua kolom wajib terpenuhi
INSERT INTO Pasien (nama, tgl_lahir, nik, jenis_kelamin) 
VALUES ('Dedi Setiawan', '1990-05-17', '3515011705900003', 'L');

-- Cek Hasil
SELECT patient_id, nama, nomor_rm FROM Pasien ORDER BY patient_id DESC LIMIT 1;

-- trg_generate_rm_pasien Negatif
-- Memasukkan data pasien yang sama lagi (Bentrokan Unique Key pada NIK)
INSERT INTO Pasien (nama, tgl_lahir, nik, jenis_kelamin) 
VALUES ('Dedi Duplikat', '1990-05-17', '3515011705900003', 'L');

-- trg_validasi_jadwal_dokter Positif
-- Mendaftarkan pasien ke Dokter A di jam 09:00 pada hari baru yang masih kosong
INSERT INTO Kunjungan (doctor_id, patient_id, waktu_datang, status) 
VALUES (1, 1, '2026-06-20 09:00:00', 'Menunggu');

-- trg_validasi_jadwal_dokter Negatif
-- Mendaftarkan pasien LAIN ke Dokter yang SAMA, di HARI dan JAM yang SAMA persis
INSERT INTO Kunjungan (doctor_id, patient_id, waktu_datang, status) 
VALUES (1, 2, '2026-06-20 09:00:00', 'Menunggu');

-- trg_audit_rekam_medis Positif
-- Mengubah catatan klinis yang memicu trigger audit
UPDATE Rekam_Medis SET catatan_klinis = 'Hipertensi Grade II' WHERE record_id = 1;

-- Cek tabel log
SELECT * FROM Log_Audit_Sistem ORDER BY log_id DESC LIMIT 1;

-- trg_audit_rekam_medis Negatif
-- Mengupdate rekam medis tapi kolom 'catatan_klinis' TIDAK diubah (mengubah kolom lain seperti alergi)
UPDATE Rekam_Medis SET alergi_obat = 'Amoxicillin' WHERE record_id = 1;

-- Cek tabel log apakah ada log baru masuk
SELECT * FROM Log_Audit_Sistem ORDER BY log_id DESC LIMIT 1;

-- trg_auto_kurangi_stok_resep & trg_validasi_stok_negatif Positif
-- Menginput resep dengan jumlah wajar yang tersedia di gudang (Misal minta 2 butir)
CALL sp_tambah_detail_resep(1, 1, 2);

-- Cek mutasi stok fisik
SELECT * FROM Transaksi_Stok ORDER BY transaksi_id DESC LIMIT 1;

-- trg_auto_kurangi_stok_resep & trg_validasi_stok_negatif Negatif
-- Menginput resep dengan jumlah ekstrem yang melebihi kapasitas stok fisik (Misal minta 9999 butir)
CALL sp_tambah_detail_resep(1, 1, 9999);