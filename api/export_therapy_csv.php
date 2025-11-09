<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_monitoring";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$session_id = $_GET['session_id'] ?? 0;

if ($session_id == 0) {
    die("Session ID required");
}

// Get session info
$session_sql = "SELECT ts.*, p.nama, p.jenis_kelamin, p.usia 
                FROM therapy_sessions ts
                JOIN patients p ON ts.patient_id = p.id
                WHERE ts.id = ?";
$stmt = $conn->prepare($session_sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session_result = $stmt->get_result();
$session = $session_result->fetch_assoc();
$stmt->close();

if (!$session) {
    die("Session not found");
}

// Get movement data
$movements_sql = "SELECT * FROM therapy_movements WHERE session_id = ? ORDER BY timestamp ASC";
$stmt = $conn->prepare($movements_sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$movements_result = $stmt->get_result();
$stmt->close();

// Set headers untuk download CSV
$filename = "terapi_" . str_replace(' ', '_', $session['nama']) . "_" . date('YmdHis', strtotime($session['start_time'])) . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buat output stream
$output = fopen('php://output', 'w');

// Tulis BOM untuk Excel UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Tulis informasi sesi terapi
fputcsv($output, ['INFORMASI SESI TERAPI']);
fputcsv($output, ['Nama Pasien', $session['nama']]);
fputcsv($output, ['Jenis Kelamin', $session['jenis_kelamin']]);
fputcsv($output, ['Usia', $session['usia'] . ' tahun']);
fputcsv($output, ['Jenis Terapi', $session['therapy_type']]);
fputcsv($output, ['Tanggal Mulai', date('d/m/Y H:i:s', strtotime($session['start_time']))]);
fputcsv($output, ['Tanggal Selesai', date('d/m/Y H:i:s', strtotime($session['end_time']))]);
fputcsv($output, ['Durasi', gmdate("i:s", $session['duration']) . ' menit']);
fputcsv($output, ['Total Gerakan', $session['total_movements']]);
fputcsv($output, ['Status', ucfirst($session['completion_status'])]);
fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')]);
fputcsv($output, []); // Baris kosong

// Header kolom data gerakan
fputcsv($output, [
    'No',
    'Timestamp',
    'Roll (deg)',
    'Pitch (deg)',
    'Accel X (G)',
    'Accel Y (G)',
    'Accel Z (G)',
    'Gyro X (°/s)',
    'Gyro Y (°/s)',
    'Gyro Z (°/s)'
]);

// Tulis data gerakan
$no = 1;
while ($row = $movements_result->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['timestamp'],
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
$conn->close();
exit;
?>