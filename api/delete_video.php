<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "health_monitoring";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$video_id = $_GET['id'] ?? 0;
$patient_id = $_GET['patient_id'] ?? 0;

if ($video_id == 0 || $patient_id == 0) {
    die("Invalid parameters");
}

// Get video info
$sql = "SELECT * FROM patient_videos WHERE id = ? AND patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $video_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$video = $result->fetch_assoc();
$stmt->close();

if ($video) {
    // Hapus file fisik
    $filepath = '../' . $video['video_path'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    // Hapus dari database
    $sql_delete = "DELETE FROM patient_videos WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $video_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: ../detail_pasien.php?patient_id=" . $patient_id . "&msg=Video berhasil dihapus");
        exit;
    } else {
        $stmt->close();
        $conn->close();
        header("Location: ../detail_pasien.php?patient_id=" . $patient_id . "&error=Gagal menghapus video");
        exit;
    }
} else {
    $conn->close();
    header("Location: ../detail_pasien.php?patient_id=" . $patient_id . "&error=Video tidak ditemukan");
    exit;
}
?>