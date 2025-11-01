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

$patient_id = $data['patient_id'] ?? 0;
$therapy_type = $data['therapy_type'] ?? '';

if ($patient_id == 0 || empty($therapy_type)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Create new therapy session
$sql = "INSERT INTO therapy_sessions (patient_id, therapy_type, start_time, completion_status) 
        VALUES (?, ?, NOW(), 'in_progress')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $patient_id, $therapy_type);

if ($stmt->execute()) {
    $session_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Therapy session started',
        'session_id' => $session_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to start therapy: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>