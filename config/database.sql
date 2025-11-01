-- ========================================
-- HEALTH MONITORING SYSTEM DATABASE
-- ========================================

-- Buat database baru
CREATE DATABASE IF NOT EXISTS health_monitoring;
USE health_monitoring;

-- ========================================
-- Tabel untuk data pasien
-- ========================================
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    usia INT NOT NULL,
    status_kesehatan ENUM('Sehat', 'Sakit') NOT NULL,
    kategori_sakit ENUM('Ringan', 'Sedang', 'Parah', '-') DEFAULT '-',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nama (nama),
    INDEX idx_status (status_kesehatan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Tabel untuk data sensor
-- ========================================
CREATE TABLE IF NOT EXISTS sensor_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    axG FLOAT DEFAULT 0,
    ayG FLOAT DEFAULT 0,
    azG FLOAT DEFAULT 0,
    gx FLOAT DEFAULT 0,
    gy FLOAT DEFAULT 0,
    gz FLOAT DEFAULT 0,
    roll INT DEFAULT 0,
    pitch INT DEFAULT 0,
    hr INT DEFAULT 0,
    spo2 INT DEFAULT 0,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_patient (patient_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_patient_timestamp (patient_id, timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Data sample (opsional, untuk testing)
-- ========================================

-- Insert sample patient
INSERT INTO patients (nama, jenis_kelamin, usia, status_kesehatan, kategori_sakit) VALUES
('John Doe', 'Laki-laki', 35, 'Sehat', '-'),
('Jane Smith', 'Perempuan', 28, 'Sakit', 'Ringan'),
('Ahmad Wijaya', 'Laki-laki', 45, 'Sakit', 'Sedang');

-- Insert sample sensor data (opsional)
-- Uncomment jika ingin menambahkan data sample
/*
INSERT INTO sensor_data (patient_id, axG, ayG, azG, gx, gy, gz, roll, pitch, hr, spo2) VALUES
(1, 0.12, 0.45, 9.81, 0.02, 0.03, 0.01, 5, 10, 75, 98),
(1, 0.15, 0.42, 9.79, 0.03, 0.02, 0.02, 6, 11, 76, 97),
(2, 0.10, 0.48, 9.82, 0.01, 0.04, 0.01, 4, 9, 82, 95),
(3, 0.14, 0.43, 9.80, 0.02, 0.03, 0.02, 5, 10, 88, 94);
*/

-- ========================================
-- View untuk statistik pasien (opsional)
-- ========================================
CREATE OR REPLACE VIEW patient_statistics AS
SELECT 
    p.id,
    p.nama,
    p.jenis_kelamin,
    p.usia,
    p.status_kesehatan,
    p.kategori_sakit,
    COUNT(s.id) as total_readings,
    AVG(s.hr) as avg_heart_rate,
    AVG(s.spo2) as avg_spo2,
    MIN(s.timestamp) as first_reading,
    MAX(s.timestamp) as last_reading
FROM patients p
LEFT JOIN sensor_data s ON p.id = s.patient_id
GROUP BY p.id;

-- ========================================
-- Stored Procedure untuk cleanup data lama (opsional)
-- ========================================
DELIMITER //
CREATE PROCEDURE cleanup_old_data(IN days_old INT)
BEGIN
    DELETE FROM sensor_data 
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL days_old DAY);
    
    SELECT ROW_COUNT() as deleted_records;
END //
DELIMITER ;

-- Cara menggunakan stored procedure:
-- CALL cleanup_old_data(30); -- Hapus data lebih dari 30 hari

-- ========================================
-- Verificasi tabel yang sudah dibuat
-- ========================================
SHOW TABLES;

-- Lihat struktur tabel
DESCRIBE patients;
DESCRIBE sensor_data;

-- ========================================
-- SELESAI
-- ========================================
SELECT 'Database health_monitoring berhasil dibuat!' as status;