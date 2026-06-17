use praktik_mandiri;

-- menghitung usia pasien berdasarkan tanggal lahir
-- Function untuk menghitung usia pasien berdasarkan tanggal lahir
DELIMITER $$

CREATE FUNCTION fn_hitung_usia(p_patient_id INT)
RETURNS INT
DETERMINISTIC

BEGIN

    DECLARE v_usia INT DEFAULT 0;

    -- Jika data tidak ditemukan
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET v_usia = -1;

    -- Jika warning
    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_usia = -2;

    -- Jika error
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    SET v_usia = -3;

    SELECT TIMESTAMPDIFF(
        YEAR,
        tgl_lahir,
        CURDATE()
    )
    INTO v_usia
    FROM Pasien
    WHERE patient_id = p_patient_id;

    RETURN v_usia;

END $$

DELIMITER ;

-- menghitung total tagihan pasien berdasarkan detail tagihan
-- Function untuk menghitung total tagihan pasien
DELIMITER $$

CREATE FUNCTION fn_total_tagihan(p_tagihan_id INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC

BEGIN

    DECLARE v_total DECIMAL(15,2) DEFAULT 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET v_total = -1;

    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_total = -2;

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    SET v_total = -3;

    SELECT IFNULL(
        SUM(harga_satuan),
        0
    )
    INTO v_total
    FROM Detail_Tagihan
    WHERE tagihan_id = p_tagihan_id;

    RETURN v_total;

END $$

DELIMITER ;

-- menghitung stok obat berdasarkan seluruh transaksi stok
-- Function untuk menghitung stok obat dari seluruh transaksi
DELIMITER $$

CREATE FUNCTION fn_cek_stok_obat(p_obat_id INT)
RETURNS INT
DETERMINISTIC

BEGIN

    DECLARE v_stok INT DEFAULT 0;

    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET v_stok = -1;

    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_stok = -2;

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    SET v_stok = -3;

    SELECT IFNULL(
        SUM(ts.jumlah),
        0
    )
    INTO v_stok
    FROM Batch b
    JOIN Transaksi_Stok ts
        ON b.batch_id = ts.batch_id
    WHERE b.obat_id = p_obat_id;

    RETURN v_stok;

END $$

DELIMITER ;

-- memvalidasi apakah NIK pasien terdiri dari 16 digit
-- Function untuk validasi NIK pasien
DELIMITER $$

CREATE FUNCTION fn_validasi_nik(p_nik VARCHAR(16))
RETURNS VARCHAR(30)
DETERMINISTIC

BEGIN

    DECLARE v_hasil VARCHAR(30) DEFAULT 'TIDAK VALID';

    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_hasil = 'WARNING';

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    SET v_hasil = 'ERROR';

    IF LENGTH(TRIM(p_nik)) = 16 THEN
        SET v_hasil = 'VALID';
    ELSE
        SET v_hasil = 'TIDAK VALID';
    END IF;

    RETURN v_hasil;

END $$

DELIMITER ;

-- memvalidasi apakah pasien memiliki asuransi aktif
-- Function untuk memvalidasi status asuransi pasien
DELIMITER $$

CREATE FUNCTION fn_validasi_asuransi(p_patient_id INT)
RETURNS VARCHAR(30)
DETERMINISTIC

BEGIN

    DECLARE v_asuransi VARCHAR(50);
    DECLARE v_status VARCHAR(30);

    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET v_status = 'PASIEN TIDAK DITEMUKAN';

    DECLARE CONTINUE HANDLER FOR SQLWARNING
    SET v_status = 'WARNING';

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    SET v_status = 'ERROR';

    SELECT asuransi_id
    INTO v_asuransi
    FROM Pasien
    WHERE patient_id = p_patient_id;

    IF v_status IS NULL THEN

        IF v_asuransi IS NULL THEN
            SET v_status = 'PASIEN UMUM';
        ELSE
            SET v_status = 'PASIEN ASURANSI';
        END IF;

    END IF;

    RETURN v_status;

END $$

DELIMITER ;

-- Pemanggilan fn_hitung_usia(p_patient_id INT)
SELECT fn_hitung_usia(1);
SELECT fn_hitung_usia(999);

-- Pemanggilan fn_total_tagihan(p_tagihan_id INT)
SELECT fn_total_tagihan(1);
SELECT fn_total_tagihan(999);

-- Pemanggilan fn_cek_stok_obat(p_obat_id INT)
SELECT fn_cek_stok_obat(1);
SELECT fn_cek_stok_obat(999);

-- Pemanggilan fn_validasi_nik(p_nik VARCHAR(16))
SELECT fn_validasi_nik('3515011203950001');
SELECT fn_validasi_nik('1234567890');

-- Pemanggilan fn_validasi_asuransi(p_patient_id INT)
SELECT fn_validasi_asuransi(1);
SELECT fn_validasi_asuransi(999);

