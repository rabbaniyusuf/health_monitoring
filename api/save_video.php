<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_monitoring";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$patient_id = $_POST['patient_id'] ?? 0;
$duration = $_POST['duration'] ?? 0;

if ($patient_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Patient ID required']);
    exit;
}

// Cek apakah ada file video
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No video uploaded or upload error']);
    exit;
}

// Validasi file
$allowed_types = ['video/webm', 'video/mp4'];
$file_type = $_FILES['video']['type'];

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only WebM and MP4 allowed']);
    exit;
}

// Buat folder videos jika belum ada
$upload_dir = '../videos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate nama file unik
$file_extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
$filename = 'patient_' . $patient_id . '_' . time() . '.' . $file_extension;
$filepath = $upload_dir . $filename;

// Pindahkan file
if (move_uploaded_file($_FILES['video']['tmp_name'], $filepath)) {
    $file_size = filesize($filepath);
    $video_path = 'videos/' . $filename;
    
    // Update database - simpan di tabel patients
    $sql_update = "UPDATE patients SET 
                   video_path = ?,
                   video_duration = ?,
                   video_size = ?,
                   recording_end = NOW()
                   WHERE id = ?";
    
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("siii", $video_path, $duration, $file_size, $patient_id);
    
    if ($stmt->execute()) {
        // Juga simpan di tabel patient_videos untuk riwayat
        $sql_insert = "INSERT INTO patient_videos 
                       (patient_id, video_filename, video_path, video_duration, video_size) 
                       VALUES (?, ?, ?, ?, ?)";
        
        $stmt2 = $conn->prepare($sql_insert);
        $stmt2->bind_param("issii", $patient_id, $filename, $video_path, $duration, $file_size);
        $stmt2->execute();
        $stmt2->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Video saved successfully',
            'data' => [
                'filename' => $filename,
                'path' => $video_path,
                'size' => $file_size,
                'duration' => $duration
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}

$conn->close();
?>