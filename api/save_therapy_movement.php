<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_monitoring";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$session_id = $data['session_id'] ?? 0;
$patient_id = $data['patient_id'] ?? 0;
$axG = $data['axG'] ?? 0;
$ayG = $data['ayG'] ?? 0;
$azG = $data['azG'] ?? 0;
$gx = $data['gx'] ?? 0;
$gy = $data['gy'] ?? 0;
$gz = $data['gz'] ?? 0;
$roll = $data['roll'] ?? 0;
$pitch = $data['pitch'] ?? 0;

if ($session_id == 0 || $patient_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$sql = "INSERT INTO therapy_movements (session_id, patient_id, axG, ayG, azG, gx, gy, gz, roll, pitch) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iidddddddd", $session_id, $patient_id, $axG, $ayG, $azG, $gx, $gy, $gz, $roll, $pitch);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Movement data saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save movement: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>