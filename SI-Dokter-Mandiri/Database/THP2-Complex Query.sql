USE praktik_mandiri;
-- KATEGORI 1: JOIN & Fungsi Agregat (GROUP BY + HAVING)

-- 1.Analisis Pendapatan Kotor Klinik Berdasarkan Metode Pembayaran
-- Skenario: Kasir ingin mengetahui total pendapatan yang sudah dilunasi per metode pembayaran, dan hanya menampilkan metode yang menghasilkan pendapatan di atas Rp 50.000.
SELECT 
    t.metode_pembayaran,
    COUNT(t.tagihan_id) AS jumlah_transaksi,
    SUM(dt.harga_satuan - t.diskon) AS total_pendapatan_bersih
FROM Tagihan t
INNER JOIN Detail_Tagihan dt ON t.tagihan_id = dt.tagihan_id
WHERE t.status = 'Lunas'
GROUP BY t.metode_pembayaran
HAVING total_pendapatan_bersih > 50000
ORDER BY total_pendapatan_bersih DESC;

-- 2.Rekapitulasi Demografis Pasien dan Total Kunjungan
-- Skenario: Admin ingin melihat pasien mana saja (termasuk yang asuransinya kosong/NULL) beserta frekuensi kunjungan mereka.
SELECT 
    p.nama AS nama_pasien,
    p.jenis_kelamin,
    IFNULL(p.asuransi_id, 'PASIEN UMUM') AS status_asuransi,
    COUNT(k.visit_id) AS total_kunjungan
FROM Pasien p
LEFT JOIN Kunjungan k ON p.patient_id = k.patient_id
GROUP BY p.patient_id, p.nama, p.jenis_kelamin, p.asuransi_id
ORDER BY total_kunjungan DESC, nama_pasien ASC;

-- 3.Evaluasi Performa Stok Obat Harian (Masuk vs Keluar)
-- Skenario: Apoteker ingin melihat ringkasan berapa jumlah barang masuk dan keluar di gudang utama pada hari tertentu.
SELECT 
    o.nama_obat,
    SUM(CASE WHEN ts.jenis_transaksi LIKE '%Masuk%' THEN ts.jumlah ELSE 0 END) AS total_masuk,
    SUM(CASE WHEN ts.jenis_transaksi LIKE '%Keluar%' THEN ts.jumlah ELSE 0 END) AS total_keluar
FROM Obat o
INNER JOIN Batch b ON o.obat_id = b.obat_id
INNER JOIN Transaksi_Stok ts ON b.batch_id = ts.batch_id
GROUP BY o.obat_id, o.nama_obat
ORDER BY o.nama_obat;

-- KATEGORI 2: Subqueries (Bersarang)

-- 4.Pencarian Pasien dengan Tagihan Terbesar (Single-Row Subquery)
-- Skenario: Manajemen mencari data pasien (nama dan total tagihan) yang memiliki nominal detail tagihan paling mahal di klinik.
SELECT 
    p.nama AS nama_pasien,
    dt.jenis_item,
    dt.harga_satuan
FROM Pasien p
INNER JOIN Kunjungan k ON p.patient_id = k.patient_id
INNER JOIN Tagihan t ON k.visit_id = t.visit_id
INNER JOIN Detail_Tagihan dt ON t.tagihan_id = dt.tagihan_id
WHERE dt.harga_satuan = (
    SELECT MAX(harga_satuan) FROM Detail_Tagihan
);

-- 5.Identifikasi Obat Premium (Correlated Subquery)
-- Skenario: Menampilkan detail resep untuk obat-obatan yang harga belinya di atas rata-rata harga beli seluruh obat yang ada di batch.
SELECT 
    dr.resep_id,
    o.nama_obat,
    dr.dosis,
    dr.jumlah
FROM Detail_Resep dr
INNER JOIN Obat o ON dr.obat_id = o.obat_id
WHERE dr.obat_id IN (
    SELECT b.obat_id 
    FROM Batch b 
    WHERE b.harga_beli > (SELECT AVG(harga_beli) FROM Batch)
);

-- 6.Deteksi Diagnosa Pasien Berdasarkan Hasil Lab Spesifik (EXISTS Subquery)
-- Skenario: Dokter ingin melacak nama pasien yang memiliki hasil pemeriksaan penunjang lab berupa 'Hemoglobin'.
SELECT 
    rm.record_id,
    rm.catatan_klinis
FROM Rekam_Medis rm
WHERE EXISTS (
    SELECT 1 
    FROM Order_Penunjang op 
    INNER JOIN Hasil_Penunjang hp ON op.order_id = hp.order_id
    WHERE op.record_id = rm.record_id 
    AND hp.hasil LIKE '%Hemoglobin%'
);

-- KATEGORI 3: Operasi Himpunan (Set Operations)

-- 7.Direktori Master Kontak Klinik (UNION)
-- Skenario: Kebutuhan sistem SMS Gateway untuk menggabungkan seluruh nomor telepon Pasien, Dokter, dan Staf (User) dalam satu tampilan tabel utuh.
SELECT nama AS nama_lengkap, no_telpon AS kontak, 'Pasien' AS role_entitas FROM Pasien
UNION ALL
SELECT nama AS nama_lengkap, '0812XXXXXX' AS kontak, 'Dokter' AS role_entitas FROM Dokter
UNION ALL
SELECT nama AS nama_lengkap, kontak AS kontak, 'Staf/User' AS role_entitas FROM User
ORDER BY role_entitas, nama_lengkap;

-- 8.Audit Tagihan Menggantung/Belum Dibayar (EXCEPT Logic)
-- Skenario: Mencari ID Kunjungan, Nama Pasien, dan Status Tagihan yang ada di sistem, tetapi belum memiliki status 'Lunas' di tabel tagihannya. 
SELECT 
    k.visit_id AS 'ID Kunjungan',
    p.nama AS 'Nama Pasien',
    t.status AS 'Status Tagihan'
FROM Kunjungan k
INNER JOIN Pasien p ON k.patient_id = p.patient_id
INNER JOIN Tagihan t ON k.visit_id = t.visit_id
WHERE k.visit_id IN (
    SELECT visit_id FROM Kunjungan
    EXCEPT
    SELECT visit_id FROM Tagihan WHERE status = 'Lunas'
);

-- 9.Audit Pasien Konsultasi dengan Pembayaran Tunai (INTERSECT)
-- Skenario: Manajemen klinik ingin melakukan audit terhadap alur layanan mandiri. Mereka ingin menemukan ID Kunjungan, Nama Pasien, Jenis Tindakan, dan Metpdde Pembayaran dari pasien yang hanya menerima tindakan berupa 'Konsultasi Medis' (ranah klinis), yang sekaligus menyelesaikan pembayarannya menggunakan metode 'Tunai' (ranah finansial).
SELECT 
    k.visit_id AS 'ID Kunjungan',
    p.nama AS 'Nama Pasien',
    t.jenis_tindakan AS 'Jenis Tindakan',
    tg.metode_pembayaran AS 'Metode Pembayaran'
FROM Kunjungan k
INNER JOIN Pasien p ON k.patient_id = p.patient_id
INNER JOIN Rekam_Medis rm ON k.visit_id = rm.visit_id
INNER JOIN Tindakan t ON rm.record_id = t.record_id
INNER JOIN Tagihan tg ON k.visit_id = tg.visit_id
WHERE k.visit_id IN (
    SELECT rm_sub.visit_id 
    FROM Rekam_Medis rm_sub
    INNER JOIN Tindakan t_sub ON rm_sub.record_id = t_sub.record_id
    WHERE t_sub.jenis_tindakan = 'Konsultasi Medis'
    INTERSECT
    SELECT tg_sub.visit_id 
    FROM Tagihan tg_sub
    WHERE tg_sub.metode_pembayaran = 'Tunai'
);

-- KATEGORI 4: Eksekutif/Laporan Komprehensif (All-in-One)

-- 10.Dashboard Laporan Medis & Finansial Pasien
-- Skenario: Query tingkat lanjut untuk digabungkan menjadi View Laporan. Menggabungkan informasi demografi, rekam medis, jumlah obat, dan total tagihan pasien dalam satu baris bacaan.
SELECT 
    k.tgl_kunjungan,
    p.nama AS nama_pasien,
    rm.catatan_klinis AS diagnosa_utama,
    (SELECT COUNT(*) FROM Order_Penunjang op WHERE op.record_id = rm.record_id) AS total_tes_lab,
    IFNULL(SUM(dt.harga_satuan) - t.diskon, 0) AS total_tagihan_pasien
FROM Kunjungan k
INNER JOIN Pasien p ON k.patient_id = p.patient_id
LEFT JOIN Rekam_Medis rm ON k.visit_id = rm.visit_id
LEFT JOIN Tagihan t ON k.visit_id = t.visit_id
LEFT JOIN Detail_Tagihan dt ON t.tagihan_id = dt.tagihan_id
GROUP BY k.tgl_kunjungan, p.nama, rm.catatan_klinis, rm.record_id, t.diskon
ORDER BY total_tagihan_pasien DESC;