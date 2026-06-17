use praktik_mandiri;

-- transaksi kunjungan pasien baru
DELIMITER $$

CREATE PROCEDURE sp_tambah_kunjungan
(
    IN p_layanan VARCHAR(50),
    IN p_pasien INT,
    IN p_dokter INT
)
BEGIN
    -- 1. DEKLARASI VARIABEL (Wajib paling atas)
    DECLARE v_pesan VARCHAR(100);
    DECLARE v_antrian INT;
    DECLARE v_pasien_eksis INT;
    DECLARE v_dokter_eksis INT;

    -- 2. DEKLARASI HANDLER EROR
    -- Warning Handling
    DECLARE CONTINUE HANDLER FOR SQLWARNING
        SET v_pesan = 'WARNING : Terjadi warning saat proses tambah kunjungan';
	
    -- Error Handling General
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Terjadi kesalahan saat menambah kunjungan';
    END;
	
    -- 3. VALIDASI INPUT DATA
    IF p_pasien <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ID pasien tidak valid';
    END IF;
	
    IF p_dokter <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ID dokter tidak valid';
    END IF;
	
    -- 4. MEMASTIKAN PASIEN DAN DOKTER MEMANG ADA DI DATABASE (Pengganti NOT FOUND)
    SELECT COUNT(*) INTO v_pasien_eksis FROM Pasien WHERE patient_id = p_pasien;
    SELECT COUNT(*) INTO v_dokter_eksis FROM Dokter WHERE doctor_id = p_dokter;
    
    IF v_pasien_eksis = 0 OR v_dokter_eksis = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Pasien atau dokter tidak ditemukan di data master';
    END IF;

    -- 5. MEMULAI TRANSAKSI
    START TRANSACTION;
    
        -- Mengambil nomor antrean otomatis untuk hari ini
        SELECT IFNULL(MAX(antrian_no), 0) + 1 
        INTO v_antrian 
        FROM Kunjungan 
        WHERE tgl_kunjungan = CURDATE();

        -- Insert data ke tabel Kunjungan
        INSERT INTO Kunjungan
        (
            tgl_kunjungan,   
            waktu_datang,    
            jenis_layanan,
            patient_id,
            doctor_id,
            antrian_no,
            status
        )
        VALUES
        (
            CURDATE(),       
            NOW(),           
            p_layanan,
            p_pasien,
            p_dokter,
            v_antrian,       
            'Terdaftar'
        );
        
    COMMIT;
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_buat_rekam_medis
(
    IN p_visit_id INT,
    IN p_diagnosa TEXT
)
BEGIN
    -- 1. DEKLARASI VARIABEL (Paling Atas)
    DECLARE v_pesan VARCHAR(100);
    DECLARE v_visit_eksis INT;

    -- 2. DEKLARASI HANDLER
    -- Warning Handler
    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_pesan = 'WARNING : Data rekam medis menghasilkan warning';

    -- Exception Handler (Error)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal membuat rekam medis';
    END;

    -- 3. VALIDASI INPUT
    IF p_visit_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Visit ID tidak valid';
    END IF;

    IF p_diagnosa IS NULL OR LENGTH(TRIM(p_diagnosa)) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Diagnosa tidak boleh kosong';
    END IF;

    -- Validasi tambahan: Memastikan visit_id benar-benar ada di tabel Kunjungan
    SELECT COUNT(*) INTO v_visit_eksis FROM Kunjungan WHERE visit_id = p_visit_id;
    IF v_visit_eksis = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data kunjungan tidak ditemukan';
    END IF;

    -- 4. MULAI TRANSAKSI CRUD
    START TRANSACTION;

        INSERT INTO Rekam_Medis
        (
            tanggal_catatan,
            catatan_klinis,
            visit_id
        )
        VALUES
        (
            CURDATE(),
            p_diagnosa,
            p_visit_id
        );

    COMMIT; -- Wajib ada untuk menyimpan hasil Insert
END $$

DELIMITER ;

-- membuat resep baru oleh dokter
DELIMITER $$

CREATE PROCEDURE sp_buat_resep
(
    IN p_record_id INT,
    IN p_doctor_id INT
)
BEGIN
    -- 1. DEKLARASI VARIABEL
    DECLARE v_pesan VARCHAR(100);
    DECLARE v_record_eksis INT;
    DECLARE v_doctor_eksis INT;

    -- 2. DEKLARASI HANDLER
    -- Warning Handler
    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_pesan = 'WARNING : Terjadi warning saat membuat resep';

    -- Exception Handler (Error)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal membuat resep';
    END;

    -- 3. VALIDASI INPUT PARAMETER
    IF p_record_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Record ID tidak valid';
    END IF;

    IF p_doctor_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Doctor ID tidak valid';
    END IF;

    -- Validasi tambahan: Memastikan data rekam medis dan dokter benar-benar ada
    SELECT COUNT(*) INTO v_record_eksis FROM Rekam_Medis WHERE record_id = p_record_id;
    SELECT COUNT(*) INTO v_doctor_eksis FROM Dokter WHERE doctor_id = p_doctor_id;

    IF v_record_eksis = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data rekam medis tidak ditemukan';
    END IF;

    IF v_doctor_eksis = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data dokter tidak ditemukan';
    END IF;

    -- 4. MULAI TRANSAKSI CRUD
    START TRANSACTION;

        INSERT INTO Resep
        (
            tanggal_resep,
            status_resep,
            record_id,
            doctor_id
        )
        VALUES
        (
            NOW(),
            'Diproses',
            p_record_id,
            p_doctor_id
        );

    COMMIT; -- Menutup transaksi agar data tersimpan
END $$

DELIMITER ;


-- menambahkan detail obat ke dalam resep
DELIMITER $$

CREATE PROCEDURE sp_tambah_detail_resep
(
    IN p_resep_id INT,
    IN p_obat_id INT,
    IN p_jumlah INT
)
BEGIN
    DECLARE v_pesan VARCHAR(100);
    DECLARE v_resep_eksis INT;
    DECLARE v_obat_eksis INT;

    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_pesan = 'WARNING : Terjadi warning saat menambah detail resep';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal menambah detail resep';
    END;

    IF p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah obat harus lebih dari 0';
    END IF;

    IF p_resep_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Resep ID tidak valid';
    END IF;

    IF p_obat_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Obat ID tidak valid';
    END IF;

    SELECT COUNT(*) INTO v_resep_eksis FROM Resep WHERE resep_id = p_resep_id;
    SELECT COUNT(*) INTO v_obat_eksis FROM Obat WHERE obat_id = p_obat_id;

    IF v_resep_eksis = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data resep tidak ditemukan di sistem';
    END IF;

    IF v_obat_eksis = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data master obat tidak ditemukan';
    END IF;

    START TRANSACTION;

        INSERT INTO Detail_Resep
        (
            dosis,
            rute,
            frekuensi,
            durasi,
            jumlah,
            resep_id,
            obat_id
        )
        VALUES
        (
            '500mg',    
            'Oral',     
            '3x1',      
            '5 Hari',   
            p_jumlah,
            p_resep_id,
            p_obat_id
        );

    COMMIT; 
END $$

DELIMITER ;

-- menghitung total tagihan dan mengembalikan hasil melalui parameter OUT
DELIMITER $$

CREATE PROCEDURE sp_hitung_tagihan
(
    IN p_tagihan_id INT,
    OUT p_total DECIMAL(15,2)
)
BEGIN
    -- 1. DEKLARASI VARIABEL
    DECLARE v_warning VARCHAR(100);
    DECLARE v_tagihan_eksis INT;

    -- 2. DEKLARASI HANDLER
    -- Warning Handler
    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_warning = 'WARNING : Terjadi warning saat menghitung tagihan';

    -- Exception Handler (Error)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_total = -1; -- Mengembalikan nilai -1 sebagai penanda error
    END;

    -- 3. VALIDASI INPUT
    IF p_tagihan_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tagihan ID tidak valid';
    END IF;

    -- Validasi eksistensi tagihan (opsional tapi disarankan)
    SELECT COUNT(*) INTO v_tagihan_eksis FROM Tagihan WHERE tagihan_id = p_tagihan_id;
    IF v_tagihan_eksis = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data tagihan tidak ditemukan';
    END IF;

    -- 4. PROSES UTAMA
    SELECT IFNULL(SUM(harga_satuan), 0)
    INTO p_total
    FROM Detail_Tagihan
    WHERE tagihan_id = p_tagihan_id;

END $$

DELIMITER ;

-- menambahkan stok obat
DELIMITER $$

CREATE PROCEDURE sp_tambah_stok_obat
(
    IN p_batch_id INT,
    IN p_lokasi_id INT,
    IN p_jumlah INT
)
BEGIN

    DECLARE v_pesan VARCHAR(100);

    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_pesan = 'WARNING : Terjadi warning saat menambah stok';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal menambah stok obat';
    END;

    IF p_batch_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Batch ID tidak valid';
    END IF;

    IF p_lokasi_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Lokasi ID tidak valid';
    END IF;

    IF p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah stok harus lebih dari 0';
    END IF;

    START TRANSACTION;

        INSERT INTO Transaksi_Stok
        (
            tanggal,
            jenis_transaksi,
            jumlah,
            referensi,
            batch_id,
            lokasi_id
        )
        VALUES
        (
            NOW(),
            'Stok Masuk',
            p_jumlah,
            'PROC-STOK-MASUK',
            p_batch_id,
            p_lokasi_id
        );

    COMMIT;

END $$

DELIMITER ;

-- Procedure untuk mengurangi stok obat berdasarkan batch dan lokasi tertentu
DELIMITER $$

CREATE PROCEDURE sp_kurangi_stok_obat
(
    IN p_batch_id INT,
    IN p_lokasi_id INT,
    IN p_jumlah INT
)
BEGIN
    -- 1. DEKLARASI VARIABEL (Wajib paling atas)
    DECLARE v_stok_batch INT DEFAULT 0;
    DECLARE v_pesan VARCHAR(100);

    -- 2. DEKLARASI HANDLER
    -- Warning Handler
    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_pesan = 'WARNING : Terjadi warning saat mengurangi stok';

    -- Exception Handler (Error)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal mengurangi stok obat';
    END;

    -- 3. VALIDASI INPUT AWAL
    IF p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah stok yang dikurangi harus lebih dari 0';
    END IF;

    IF p_batch_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Batch ID tidak valid';
    END IF;

    IF p_lokasi_id <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Lokasi ID tidak valid';
    END IF;

    -- 4. MULAI TRANSAKSI
    START TRANSACTION;

        -- Mengambil stok aktual pada batch dan lokasi yang dipilih
        SELECT IFNULL(SUM(jumlah), 0)
        INTO v_stok_batch
        FROM Transaksi_Stok
        WHERE batch_id = p_batch_id
          AND lokasi_id = p_lokasi_id;

        -- Validasi ketersediaan stok (Tidak boleh minus)
        IF v_stok_batch < p_jumlah THEN
            -- Jika stok kurang, batalkan transaksi dan lemparkan error
            ROLLBACK; 
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stok pada batch dan lokasi tersebut tidak mencukupi';
            
        ELSE
            -- Jika stok cukup, catat transaksi stok keluar (menggunakan minus)
            INSERT INTO Transaksi_Stok
            (
                tanggal,
                jenis_transaksi,
                jumlah,
                referensi,
                keterangan,
                batch_id,
                lokasi_id
            )
            VALUES
            (
                NOW(),
                'Stok Keluar',
                -p_jumlah, -- p_jumlah diubah menjadi negatif untuk mengurangi total stok
                'PROC-STOK-KELUAR',
                'Pengurangan stok melalui stored procedure',
                p_batch_id,
                p_lokasi_id
            );

            COMMIT; -- Simpan perubahan permanen ke database

        END IF;

END $$

DELIMITER ;

-- menghasilkan laporan jumlah kunjungan pasien dalam rentang tanggal tertentu
DELIMITER $$

CREATE PROCEDURE sp_generate_laporan_kunjungan
(
    IN p_mulai DATE,
    IN p_selesai DATE
)
BEGIN
    -- 1. DEKLARASI VARIABEL (Wajib Paling Atas)
    DECLARE v_warning VARCHAR(100);

    -- 2. DEKLARASI HANDLER
    -- Warning Handler
    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_warning = 'WARNING : Terjadi warning saat generate laporan';

    -- Exception Handler (Error)
    -- Catatan: Tidak perlu ROLLBACK karena ini hanya proses SELECT (baca data)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal membuat laporan kunjungan';
    END;

    -- 3. VALIDASI INPUT PARAMETER
    -- Memastikan tanggal mulai tidak melampaui tanggal selesai
    IF p_mulai > p_selesai THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai';
    END IF;

    -- 4. PROSES UTAMA (Menampilkan Laporan)
    SELECT
        COUNT(*) AS total_kunjungan
    FROM Kunjungan
    WHERE tgl_kunjungan BETWEEN p_mulai AND p_selesai;

END $$

DELIMITER ;

-- Pemanggillan sp_tambah_kunjungan
CALL sp_tambah_kunjungan('Pemeriksaan Umum', 1, 1);
CALL sp_tambah_kunjungan('Pemeriksaan Umum', 0, 1);
CALL sp_tambah_kunjungan('Pemeriksaan Umum', 1, 9999);

SELECT * FROM Kunjungan ORDER BY visit_id DESC LIMIT 5;

-- Pemanggillan sp_buat_rekam_medis
CALL sp_buat_rekam_medis(11, 'Pasien mengalami demam dan batuk');
CALL sp_buat_rekam_medis(0, 'Demam');
CALL sp_buat_rekam_medis(1, '');
CALL sp_buat_rekam_medis(9999, 'Demam');

SELECT * FROM Rekam_Medis ORDER BY record_id DESC LIMIT 5;


-- Pemanggillan sp_buat_resep
CALL sp_buat_resep(1, 1);
CALL sp_buat_resep(0, 1);
CALL sp_buat_resep(9999, 1);
CALL sp_buat_resep(1, 9999);

SELECT * FROM Resep ORDER BY resep_id DESC LIMIT 5;

-- Pemanggillan sp_tambah_detail_resep
CALL sp_tambah_detail_resep(1, 1, 10); 
CALL sp_tambah_detail_resep(1, 1, 0); 
CALL sp_tambah_detail_resep(9999, 1, 10); 
CALL sp_tambah_detail_resep(1, 9999, 10);

SELECT * FROM Detail_Resep ORDER BY resep_id DESC LIMIT 5;



-- Pemanggillan sp_hitung_tagihan
CALL sp_hitung_tagihan(1, @total ); 
CALL sp_hitung_tagihan(0, @total ); 
CALL sp_hitung_tagihan(999, @total ); 
SELECT @total;

-- Pemanggillan p_tambah_stok_obat
CALL sp_tambah_stok_obat(1,1,50);
CALL sp_tambah_stok_obat(1,1,0);
SELECT * FROM Transaksi_Stok ORDER BY tanggal DESC LIMIT 5;

-- Pemanggillan sp_kurangi_stok_obat
CALL sp_kurangi_stok_obat(1, 1, 5); 
CALL sp_kurangi_stok_obat(1, 1, 0); 
CALL sp_kurangi_stok_obat(0, 1, 5);
CALL sp_kurangi_stok_obat(1, 1, 1000); 
SELECT * FROM Transaksi_Stok ORDER BY tanggal DESC LIMIT 5;

-- Pemanggillan sp_generate_laporan_kunjungan
CALL sp_generate_laporan_kunjungan('2025-01-01','2025-12-31');
CALL sp_generate_laporan_kunjungan('2025-12-31','2025-01-01');


