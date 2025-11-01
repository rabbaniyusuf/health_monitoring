<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_monitoring";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$patient_id = $_GET['patient_id'] ?? 0;

if ($patient_id == 0) {
    die("Patient ID required");
}

// Get patient info
$patient_sql = "SELECT * FROM patients WHERE id = ?";
$stmt = $conn->prepare($patient_sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient = $patient_result->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("Patient not found");
}

// Get all sensor data
$sql = "SELECT * FROM sensor_data WHERE patient_id = ? ORDER BY timestamp ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

// Set headers untuk download CSV
$filename = "data_sensor_" . str_replace(' ', '_', $patient['nama']) . "_" . date('YmdHis') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buat output stream
$output = fopen('php://output', 'w');

// Tulis BOM untuk Excel UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Tulis informasi pasien
fputcsv($output, ['INFORMASI PASIEN']);
fputcsv($output, ['Nama', $patient['nama']]);
fputcsv($output, ['Jenis Kelamin', $patient['jenis_kelamin']]);
fputcsv($output, ['Usia', $patient['usia'] . ' tahun']);
fputcsv($output, ['Status Kesehatan', $patient['status_kesehatan']]);
if ($patient['status_kesehatan'] == 'Sakit') {
    fputcsv($output, ['Kategori Sakit', $patient['kategori_sakit']]);
}
fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')]);
fputcsv($output, []); // Baris kosong

// Header kolom data sensor
fputcsv($output, [
    'No',
    'Timestamp',
    'Heart Rate (BPM)',
    'SpO2 (%)',
    'Roll (deg)',
    'Pitch (deg)',
    'Accel X (G)',
    'Accel Y (G)',
    'Accel Z (G)',
    'Gyro X',
    'Gyro Y',
    'Gyro Z'
]);

// Tulis data sensor
$no = 1;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['timestamp'],
        $row['hr'],
        $row['spo2'],
        $row['roll'],
        $row['pitch'],
        $row['axG'],
        $row['ayG'],
        $row['azG'],
        $row['gx'],
        $row['gy'],
        $row['gz']
    ]);
}

fclose($output);
$stmt->close();
$conn->close();
exit;
?>