<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_monitoring";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed']));
}

$data = json_decode(file_get_contents('php://input'), true);

// Ambil patient_id dari session atau parameter
session_start();
$patient_id = isset($_SESSION['active_patient_id']) ? $_SESSION['active_patient_id'] : 1;

$axG = $data['axG'] ?? 0;
$ayG = $data['ayG'] ?? 0;
$azG = $data['azG'] ?? 0;
$gx = $data['gx'] ?? 0;
$gy = $data['gy'] ?? 0;
$gz = $data['gz'] ?? 0;
$roll = $data['roll'] ?? 0;
$pitch = $data['pitch'] ?? 0;
$hr = $data['hr'] ?? 0;
$spo2 = $data['spo2'] ?? 0;

$sql = "INSERT INTO sensor_data (patient_id, axG, ayG, azG, gx, gy, gz, roll, pitch, hr, spo2) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iddddddddii", $patient_id, $axG, $ayG, $azG, $gx, $gy, $gz, $roll, $pitch, $hr, $spo2);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Data saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save data']);
}

$stmt->close();
$conn->close();
?>