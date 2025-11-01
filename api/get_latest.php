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

$patient_id = $_GET['patient_id'] ?? 0;
$limit = $_GET['limit'] ?? 10;

if ($patient_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Patient ID required']);
    exit;
}

$sql = "SELECT * FROM sensor_data WHERE patient_id = ? ORDER BY timestamp DESC LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $patient_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $data,
    'count' => count($data)
]);

$stmt->close();
$conn->close();
?>