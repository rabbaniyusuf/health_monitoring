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
$duration = $data['duration'] ?? 0;
$total_movements = $data['total_movements'] ?? 0;

if ($session_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

// Update therapy session
$sql = "UPDATE therapy_sessions 
        SET end_time = NOW(), 
            duration = ?, 
            total_movements = ?,
            completion_status = 'completed' 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $duration, $total_movements, $session_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Therapy session completed',
        'session_id' => $session_id,
        'duration' => $duration,
        'total_movements' => $total_movements
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to stop therapy: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>