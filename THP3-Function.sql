use praktik_mandiri;

-- menghitung usia pasien berdasarkan tanggal lahir
DELIMITER $$

CREATE FUNCTION fn_hitung_usia(p_patient_id INT)
RETURNS INT
DETERMINISTIC

BEGIN
    DECLARE v_usia INT;

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
DELIMITER $$

CREATE FUNCTION fn_total_tagihan(p_tagihan_id INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC

BEGIN
    DECLARE v_total DECIMAL(15,2);

    SELECT IFNULL(SUM(harga_satuan),0)
    INTO v_total
    FROM Detail_Tagihan
    WHERE tagihan_id = p_tagihan_id;

    RETURN v_total;
END $$

DELIMITER ;

-- menghitung stok obat berdasarkan seluruh transaksi stok
DELIMITER $$

CREATE FUNCTION fn_cek_stok_obat(p_obat_id INT)
RETURNS INT
DETERMINISTIC

BEGIN
    DECLARE v_stok INT;

    SELECT IFNULL(SUM(ts.jumlah),0)
    INTO v_stok
    FROM Batch b
    JOIN Transaksi_Stok ts
        ON b.batch_id = ts.batch_id
    WHERE b.obat_id = p_obat_id;

    RETURN v_stok;
END $$

DELIMITER ;

-- memvalidasi apakah NIK pasien terdiri dari 16 digit
DELIMITER $$

CREATE FUNCTION fn_validasi_nik(p_nik VARCHAR(16))
RETURNS VARCHAR(30)
DETERMINISTIC

BEGIN

    IF LENGTH(p_nik)=16 THEN
        RETURN 'VALID';
    ELSE
        RETURN 'TIDAK VALID';
    END IF;

END $$

DELIMITER ;

-- memvalidasi apakah pasien memiliki asuransi aktif
DELIMITER $$

CREATE FUNCTION fn_validasi_asuransi(p_patient_id INT)
RETURNS VARCHAR(30)
DETERMINISTIC

BEGIN

    DECLARE v_asuransi VARCHAR(50);

    SELECT asuransi_id
    INTO v_asuransi
    FROM Pasien
    WHERE patient_id = p_patient_id;

    IF v_asuransi IS NULL THEN
        RETURN 'PASIEN UMUM';
    ELSE
        RETURN 'PASIEN ASURANSI';
    END IF;

END $$

DELIMITER ;

