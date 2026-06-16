USE praktik_mandiri;

-- Views Laporan Pendapatan Real-Time
CREATE VIEW v_dashboard_keuangan_lunas AS
SELECT 
    t.tagihan_id AS 'ID Tagihan',
    k.visit_id AS 'ID Kunjungan',
    k.tgl_kunjungan AS 'Tanggal Transaksi',
    p.nama AS 'Nama Pasien',
    t.metode_pembayaran AS 'Metode Pembayaran',
    t.diskon AS 'Potongan Diskon',
    IFNULL(SUM(dt.harga_satuan), 0) AS 'Total Kotor',
    IFNULL(SUM(dt.harga_satuan) - t.diskon, 0) AS 'Total Bersih Diterima'
FROM Tagihan t
INNER JOIN Kunjungan k ON t.visit_id = k.visit_id
INNER JOIN Pasien p ON k.patient_id = p.patient_id
LEFT JOIN Detail_Tagihan dt ON t.tagihan_id = dt.tagihan_id
WHERE t.status = 'Lunas'
GROUP BY t.tagihan_id, k.visit_id, k.tgl_kunjungan, p.nama, t.metode_pembayaran, t.diskon;

SELECT * FROM v_dashboard_keuangan_lunas;

-- Views Laporan Rekam Medis Integratif
CREATE VIEW v_resume_medis_pasien AS
SELECT 
    k.visit_id AS 'ID Kunjungan',
    k.tgl_kunjungan AS 'Tanggal Kunjungan',
    p.nik AS 'NIK',
    p.nama AS 'Nama Pasien',
    d.nama AS 'Dokter Pemeriksa',
    tv.keluhan_utama AS 'Keluhan Utama',
    tv.tekanan_darah AS 'Tensi',
    tv.suhu AS 'Suhu Badan',
    rm.catatan_klinis AS 'Diagnosa Klinis'
FROM Kunjungan k
INNER JOIN Pasien p ON k.patient_id = p.patient_id
LEFT JOIN Dokter d ON k.doctor_id = d.doctor_id
INNER JOIN Rekam_Medis rm ON k.visit_id = rm.visit_id
LEFT JOIN Triage_Vital tv ON rm.record_id = tv.record_id;

SELECT * FROM v_resume_medis_pasien;

-- Views Laporan Inventori Aktif per Lokasi
CREATE OR REPLACE VIEW v_kartu_stok_apotek AS
SELECT 
    b.batch_id AS 'ID Batch',
    o.nama_obat AS 'Nama Obat',
    o.kategori AS 'Kategori',
    b.expiry_date AS 'Tanggal Kadaluarsa',
    l.nama_lokasi AS 'Lokasi Penyimpanan',
    IFNULL(SUM(ts.jumlah), 0) AS 'Sisa Stok Aktual'
FROM Obat o
INNER JOIN Batch b ON o.obat_id = b.obat_id
INNER JOIN Lokasi l ON b.lokasi_rak = l.nama_lokasi OR l.lokasi_id = 1 -- Penyelarasan relasi data logistik Anda
LEFT JOIN Transaksi_Stok ts ON b.batch_id = ts.batch_id AND ts.lokasi_id = l.lokasi_id
GROUP BY b.batch_id, o.nama_obat, o.kategori, b.expiry_date, l.nama_lokasi;

SELECT * FROM v_kartu_stok_apotek;

-- Views Monitoring Status Kunjungan
CREATE VIEW v_antrian_pelayanan_hari_ini AS
SELECT 
    k.antrian_no AS 'No Antrian',
    k.visit_id AS 'ID Kunjungan',
    p.nama AS 'Nama Pasien',
    k.jenis_layanan AS 'Layanan',
    DATE_FORMAT(k.waktu_datang, '%H:%i') AS 'Jam Datang',
    k.status AS 'Status Alur'
FROM Kunjungan k
INNER JOIN Pasien p ON k.patient_id = p.patient_id
ORDER BY k.antrian_no ASC;

SELECT * FROM v_antrian_pelayanan_hari_ini;

-- Views Laporan Penyerahan Resep
CREATE VIEW v_manifest_dispensing_obat AS
SELECT 
    dp.dispensing_id AS 'ID Dispensing',
    k.visit_id AS 'ID Kunjungan',
    p.nama AS 'Nama Pasien',
    o.nama_obat AS 'Obat Diberikan',
    dr.jumlah AS 'Jumlah Qty',
    dr.frekuensi AS 'Aturan Pakai',
    dp.edukasi_pasien AS 'Edukasi Farmasi',
    u.nama AS 'Petugas Apotek'
FROM Dispensing dp
INNER JOIN Detail_Resep dr ON dp.detail_id = dr.detail_id
INNER JOIN Obat o ON dr.obat_id = o.obat_id
INNER JOIN Resep r ON dr.resep_id = r.resep_id
INNER JOIN Rekam_Medis rm ON r.record_id = rm.record_id
INNER JOIN Kunjungan k ON rm.visit_id = k.visit_id
INNER JOIN Pasien p ON k.patient_id = p.patient_id
LEFT JOIN User u ON dp.petugas_id = u.user_id;

SELECT * FROM v_manifest_dispensing_obat;