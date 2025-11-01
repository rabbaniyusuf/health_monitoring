<?php
/**
 * HEALTH MONITORING SYSTEM
 * Configuration File
 * 
 * File ini berisi semua konfigurasi sistem
 * Edit sesuai dengan environment Anda
 */

// ========================================
// DATABASE CONFIGURATION
// ========================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'health_monitoring');

// ========================================
// TIMEZONE SETTINGS
// ========================================
date_default_timezone_set('Asia/Jakarta');

// ========================================
// SYSTEM SETTINGS
// ========================================
define('SITE_NAME', 'Health Monitoring System');
define('SITE_URL', 'http://localhost/health_monitoring');
define('AUTO_REFRESH_INTERVAL', 2000); // milliseconds

// ========================================
// DATA LIMITS
// ========================================
define('DEFAULT_PAGE_LIMIT', 50);
define('MAX_EXPORT_RECORDS', 10000);

// ========================================
// ALERT THRESHOLDS (untuk future use)
// ========================================
define('HR_MIN_NORMAL', 60);
define('HR_MAX_NORMAL', 100);
define('SPO2_MIN_NORMAL', 95);
define('SPO2_MAX_NORMAL', 100);

// ========================================
// DATABASE CONNECTION FUNCTION
// ========================================
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Sanitize input data
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Format timestamp untuk display Indonesia
 */
function format_timestamp($timestamp) {
    return date('d/m/Y H:i:s', strtotime($timestamp));
}

/**
 * Check if value is in normal range
 */
function is_hr_normal($hr) {
    return ($hr >= HR_MIN_NORMAL && $hr <= HR_MAX_NORMAL);
}

function is_spo2_normal($spo2) {
    return ($spo2 >= SPO2_MIN_NORMAL && $spo2 <= SPO2_MAX_NORMAL);
}

/**
 * Get status badge class
 */
function get_status_badge($status) {
    return $status == 'Sehat' ? 'badge-success' : 'badge-danger';
}

function get_kategori_badge($kategori) {
    $badges = [
        'Ringan' => 'badge-warning',
        'Sedang' => 'badge-secondary',
        'Parah' => 'badge-danger'
    ];
    return $badges[$kategori] ?? 'badge-secondary';
}

/**
 * Log activity (untuk future use)
 */
function log_activity($activity, $patient_id = null) {
    // Implement logging system jika diperlukan
    error_log("[" . date('Y-m-d H:i:s') . "] $activity - Patient ID: $patient_id");
}

/**
 * Send JSON response
 */
function send_json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

/**
 * Validate patient data
 */
function validate_patient_data($nama, $jenis_kelamin, $usia, $status_kesehatan, $kategori_sakit) {
    $errors = [];
    
    if (empty($nama) || strlen($nama) < 3) {
        $errors[] = "Nama harus minimal 3 karakter";
    }
    
    if (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
        $errors[] = "Jenis kelamin tidak valid";
    }
    
    if ($usia < 1 || $usia > 120) {
        $errors[] = "Usia tidak valid (1-120 tahun)";
    }
    
    if (!in_array($status_kesehatan, ['Sehat', 'Sakit'])) {
        $errors[] = "Status kesehatan tidak valid";
    }
    
    if ($status_kesehatan == 'Sakit' && !in_array($kategori_sakit, ['Ringan', 'Sedang', 'Parah'])) {
        $errors[] = "Kategori sakit tidak valid";
    }
    
    return $errors;
}

/**
 * Get alert message based on sensor values
 */
function get_health_alert($hr, $spo2) {
    $alerts = [];
    
    if ($hr < HR_MIN_NORMAL) {
        $alerts[] = "⚠️ Heart Rate rendah (Bradycardia)";
    } elseif ($hr > HR_MAX_NORMAL) {
        $alerts[] = "⚠️ Heart Rate tinggi (Tachycardia)";
    }
    
    if ($spo2 < SPO2_MIN_NORMAL) {
        $alerts[] = "⚠️ SpO2 rendah (Hypoxemia)";
    }
    
    return $alerts;
}

// ========================================
// ERROR REPORTING (Development Mode)
// ========================================
// Ubah ke 0 untuk production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ========================================
// SESSION CONFIGURATION
// ========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// AUTO-LOAD DATABASE CONNECTION (Optional)
// ========================================
// Uncomment jika ingin auto-connect di setiap halaman
// $conn = getDBConnection();

?>