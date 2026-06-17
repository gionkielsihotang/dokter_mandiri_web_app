/* ==========================================================================
   BAGIAN 0: PERSIAPAN STRUKTUR & TABEL AUDIT
   ========================================================================== */
USE praktik_mandiri;

-- Tambahkan kolom nomor_rm jika belum ada
ALTER TABLE Pasien ADD COLUMN IF NOT EXISTS nomor_rm VARCHAR(30);

-- Tabel untuk mencatat histori perubahan data
CREATE TABLE IF NOT EXISTS Log_Audit_Sistem (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tabel VARCHAR(50),
    jenis_aksi VARCHAR(20),
    waktu_eksekusi DATETIME,
    keterangan TEXT,
    pengguna VARCHAR(50)
);


/* ==========================================================================
   BAGIAN 1: PEMBUATAN TRIGGER (AUTOMATION & VALIDATION)
   ========================================================================== */

-- 1. Auto-generate Nomor Rekam Medis
DELIMITER $$
CREATE TRIGGER trg_generate_rm_pasien BEFORE INSERT ON Pasien FOR EACH ROW
BEGIN
    DECLARE v_next_id INT;
    SELECT IFNULL(MAX(patient_id), 0) + 1 INTO v_next_id FROM Pasien;
    SET NEW.nomor_rm = CONCAT('RM-', DATE_FORMAT(CURDATE(), '%Y%m'), '-', LPAD(v_next_id, 4, '0'));
END $$
DELIMITER ;

-- 2. Sinkronisasi Status Tagihan
DELIMITER $$
CREATE TRIGGER trg_update_tagihan_otomatis AFTER INSERT ON detail_tagihan FOR EACH ROW
BEGIN
    UPDATE tagihan SET status = 'Belum Lunas' WHERE tagihan_id = NEW.tagihan_id;
END$$
DELIMITER ;

-- 3. Validasi Jadwal Dokter (Mencegah Overlap)
DROP TRIGGER IF EXISTS trg_validasi_jadwal_waktu;
DELIMITER $$
CREATE TRIGGER trg_validasi_jadwal_waktu BEFORE INSERT ON kunjungan FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM kunjungan WHERE waktu_datang = NEW.waktu_datang AND status NOT IN ('Selesai', 'Batal')) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'VALIDASI GAGAL: Slot waktu tersebut sudah diambil!';
    END IF;
END$$
DELIMITER ;

-- 4. Audit Trail Logging Rekam Medis
DELIMITER $$
CREATE TRIGGER trg_audit_rekam_medis AFTER UPDATE ON Rekam_Medis FOR EACH ROW
BEGIN
    IF OLD.catatan_klinis != NEW.catatan_klinis THEN
        INSERT INTO Log_Audit_Sistem (nama_tabel, jenis_aksi, waktu_eksekusi, keterangan, pengguna)
        VALUES ('Rekam_Medis', 'UPDATE', NOW(), CONCAT('Revisi Diagnosa ID ', NEW.record_id, '. LAMA: "', OLD.catatan_klinis, '" | BARU: "', NEW.catatan_klinis, '"'), CURRENT_USER());
    END IF;
END $$
DELIMITER ;

-- 5. Auto-Update Stok (Metode FEFO)
DELIMITER $$
CREATE TRIGGER trg_auto_kurangi_stok_resep AFTER INSERT ON Detail_Resep FOR EACH ROW
BEGIN
    DECLARE v_batch_id INT;
    SELECT b.batch_id INTO v_batch_id FROM Batch b
    JOIN Transaksi_Stok ts ON b.batch_id = ts.batch_id
    WHERE b.obat_id = NEW.obat_id GROUP BY b.batch_id, b.expiry_date HAVING SUM(ts.jumlah) >= NEW.jumlah ORDER BY b.expiry_date ASC LIMIT 1;

    IF v_batch_id IS NOT NULL THEN
        INSERT INTO Transaksi_Stok (tanggal, jenis_transaksi, jumlah, referensi, keterangan, batch_id, lokasi_id)
        VALUES (NOW(), 'Stok Keluar', -NEW.jumlah, CONCAT('RESEP-', NEW.resep_id), 'Pemotongan otomatis FEFO', v_batch_id, 1);
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'VALIDASI GAGAL: Stok tidak mencukupi!';
    END IF;
END $$
DELIMITER ;

-- 6. Validasi Stok Obat Tidak Boleh Negatif
DELIMITER $$
CREATE TRIGGER trg_validasi_stok_negatif BEFORE INSERT ON Transaksi_Stok FOR EACH ROW
BEGIN
    DECLARE v_stok_saat_ini INT DEFAULT 0;
    IF NEW.jumlah < 0 THEN
        SELECT IFNULL(SUM(jumlah), 0) INTO v_stok_saat_ini FROM Transaksi_Stok WHERE batch_id = NEW.batch_id AND lokasi_id = NEW.lokasi_id;
        IF (v_stok_saat_ini + NEW.jumlah) < 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'VALIDASI GAGAL: Stok menjadi negatif.';
        END IF;
    END IF;
END $$
DELIMITER ;


/* ==========================================================================
   BAGIAN 2: SKENARIO PENGUJIAN DUA ARAH (TESTING)
   ========================================================================== */

-- A. UJI TRIGGER GENERATE RM
INSERT INTO Pasien (nama, tgl_lahir, nik, jenis_kelamin) VALUES ('Dedi Setiawan', '1990-05-17', '3515011705900003', 'L');
SELECT patient_id, nama, nomor_rm FROM Pasien ORDER BY patient_id DESC LIMIT 1; -- Cek Hasil Positif
INSERT INTO Pasien (nama, tgl_lahir, nik, jenis_kelamin) VALUES ('Dedi Duplikat', '1990-05-17', '3515011705900003', 'L'); -- Negatif (Unique Key)

-- B. UJI TRIGGER AUDIT REKAM MEDIS
UPDATE Rekam_Medis SET catatan_klinis = 'Hipertensi Grade II' WHERE record_id = 1; -- Positif
SELECT * FROM Log_Audit_Sistem ORDER BY log_id DESC LIMIT 1; -- Cek Log Positif
UPDATE Rekam_Medis SET alergi_obat = 'Amoxicillin' WHERE record_id = 1; -- Negatif (Tidak ada update catatan_klinis)

-- C. UJI TRIGGER STOK OBAT (FEFO RESEP)
CALL sp_tambah_detail_resep(1, 1, 2); -- Positif
SELECT * FROM Transaksi_Stok ORDER BY transaksi_id DESC LIMIT 1; -- Cek Mutasi
CALL sp_tambah_detail_resep(1, 1, 9999); -- Negatif (Stok tidak cukup di batch manapun)

-- D. UJI TRIGGER VALIDASI STOK NEGATIF (MANUAL BYPASS)
-- Memaksa pengurangan stok secara manual melebihi saldo yang ada pada batch_id = 1
INSERT INTO Transaksi_Stok (tanggal, jenis_transaksi, jumlah, referensi, keterangan, batch_id, lokasi_id)
VALUES (NOW(), 'Stok Keluar', -9999, 'TEST-MANUAL', 'Uji Paksa Minus', 1, 1); -- Negatif (Dicegat trg_validasi_stok_negatif)

-- E. UJI TRIGGER TAGIHAN
-- Set status awal 'Lunas' agar efek perubahan trigger 'Belum Lunas' terlihat jelas
INSERT INTO tagihan (tagihan_id, tanggal_tagihan, status, visit_id) VALUES (1, '2026-06-17', 'Lunas', 12) ON DUPLICATE KEY UPDATE status = 'Lunas';
ALTER TABLE tagihan ADD COLUMN IF NOT EXISTS total_biaya DECIMAL(10,2) DEFAULT 0.00;

INSERT INTO detail_tagihan (tagihan_id, jenis_item, harga_satuan) VALUES (1, 'Konsultasi Mandiri', 150000.00); -- Positif (Mengubah status tagihan & total)
SELECT tagihan_id, tanggal_tagihan, status, total_biaya FROM tagihan WHERE tagihan_id = 1; -- Pembuktian Perubahan Status & Total

INSERT INTO detail_tagihan (tagihan_id, jenis_item, harga_satuan) VALUES (9999, 'Tindakan Tanpa Kuitansi Induk', 75000.00); -- Negatif (Fk Error)

-- F. UJI TRIGGER JADWAL KUNJUNGAN
INSERT INTO pasien (patient_id, nik, nama, tgl_lahir, jenis_kelamin) VALUES (1, '3515011705900088', 'Pasien Uji A', '1990-05-17', 'L'), (2, '3515011705900089', 'Pasien Uji B', '1995-08-20', 'P') ON DUPLICATE KEY UPDATE nama=nama;
INSERT INTO kunjungan (patient_id, tgl_kunjungan, waktu_datang, status, jenis_layanan) VALUES (1, '2026-06-17', '2026-06-25 13:00:00', 'Menunggu', 'Umum'); -- Positif
SELECT k.visit_id, p.nama, k.waktu_datang FROM kunjungan k JOIN pasien p ON k.patient_id = p.patient_id WHERE k.waktu_datang = '2026-06-25 13:00:00'; -- Bukti Positif
INSERT INTO kunjungan (patient_id, tgl_kunjungan, waktu_datang, status, jenis_layanan) VALUES (2, '2026-06-17', '2026-06-25 13:00:00', 'Menunggu', 'Umum'); -- Negatif