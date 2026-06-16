use praktik_mandiri;

-- transaksi kunjungan pasien baru
DELIMITER $$

CREATE PROCEDURE sp_tambah_kunjungan
(
    IN p_tgl DATE,
    IN p_layanan VARCHAR(50),
    IN p_pasien INT,
    IN p_dokter INT
)

BEGIN

    INSERT INTO Kunjungan
    (
        tgl_kunjungan,
        jenis_layanan,
        patient_id,
        doctor_id,
        status
    )
    VALUES
    (
        p_tgl,
        p_layanan,
        p_pasien,
        p_dokter,
        'Terdaftar'
    );

END $$

DELIMITER ;

-- membuat rekam medis berdasarkan kunjungan pasien
DELIMITER $$

CREATE PROCEDURE sp_buat_rekam_medis
(
    IN p_visit_id INT,
    IN p_diagnosa TEXT
)

BEGIN

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

    SELECT IFNULL(SUM(harga_satuan),0)
    INTO p_total
    FROM Detail_Tagihan
    WHERE tagihan_id = p_tagihan_id;

END $$

DELIMITER ;

-- mencatat transaksi penambahan stok obat

DELIMITER $$

CREATE PROCEDURE sp_tambah_stok_obat
(
    IN p_batch_id INT,
    IN p_lokasi_id INT,
    IN p_jumlah INT
)

BEGIN

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

END $$

DELIMITER ;

-- mengurangi stok obat dengan validasi ketersediaan stok dan rollback jika stok tidak mencukupi
DELIMITER $$

CREATE PROCEDURE sp_kurangi_stok_obat
(
    IN p_obat_id INT,
    IN p_batch_id INT,
    IN p_lokasi_id INT,
    IN p_jumlah INT
)

BEGIN

    DECLARE v_stok INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

    SET v_stok = fn_cek_stok_obat(p_obat_id);

    IF v_stok < p_jumlah THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT='Stok tidak mencukupi';

    ELSE

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
            'Stok Keluar',
            -p_jumlah,
            'PROC-STOK-KELUAR',
            p_batch_id,
            p_lokasi_id
        );

        COMMIT;

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

    SELECT
        COUNT(*) AS total_kunjungan
    FROM Kunjungan
    WHERE tgl_kunjungan
    BETWEEN p_mulai
    AND p_selesai;

END $$

DELIMITER ;
