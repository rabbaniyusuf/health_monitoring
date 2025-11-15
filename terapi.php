<?php
session_start();
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
    header("Location: index.php");
    exit;
}

// Get patient info
$sql = "SELECT * FROM patients WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

if (!$patient) {
    header("Location: index.php");
    exit;
}

// Get therapy videos
$videos_sql = "SELECT * FROM therapy_videos WHERE is_active = 1 ORDER BY difficulty, title";
$videos_result = $conn->query($videos_sql);

// Get therapy history
$history_sql = "SELECT * FROM therapy_sessions WHERE patient_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$history_result = $stmt->get_result();
$stmt->close();

// Count total completed sessions
$count_sql = "SELECT COUNT(*) as total FROM therapy_sessions WHERE patient_id = ? AND completion_status = 'completed'";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_completed = $count_result->fetch_assoc()['total'];
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terapi Rehabilitasi - <?= $patient['nama'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .patient-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .patient-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            color: #666;
            font-size: 14px;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin-right: 10px;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-info { background: #17a2b8; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .video-selector {
            margin-bottom: 20px;
        }
        
        .video-selector label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .video-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        
        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 */
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .instruction-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .instruction-box h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .instruction-box p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .instruction-box ul {
            margin-left: 20px;
            color: #666;
        }
        
        .instruction-box li {
            margin-bottom: 8px;
        }
        
        .therapy-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .status-panel {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            font-weight: 600;
            color: #333;
        }
        
        .status-value {
            color: #667eea;
            font-weight: 700;
            font-size: 18px;
        }
        
        .sensor-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .sensor-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .sensor-card h4 {
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .sensor-card .value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .sensor-card .unit {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .chart-mini {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        #therapyStatus {
            display: none;
        }
        
        #therapyStatus.active {
            display: block;
        }
        
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            .sensor-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sensor-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="patient-info">
                <div>
                    <h2>🏋️ Terapi Rehabilitasi Gerakan Tangan</h2>
                    <div class="patient-meta">
                        <strong><?= $patient['nama'] ?></strong>
                        <span>|</span>
                        <span><?= $patient['jenis_kelamin'] == 'Laki-laki' ? '♂' : '♀' ?> <?= $patient['jenis_kelamin'] ?></span>
                        <span>|</span>
                        <span><?= $patient['usia'] ?> tahun</span>
                        <span>|</span>
                        <span class="badge <?= $patient['status_kesehatan'] == 'Sehat' ? 'badge-success' : 'badge-danger' ?>">
                            <?= $patient['status_kesehatan'] ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if ($total_completed > 0): ?>
                        <a href="grafik_terapi.php?patient_id=<?= $patient_id ?>" class="btn btn-info">
                            📈 Lihat Grafik (<?= $total_completed ?>)
                        </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-primary">← Kembali</a>
                </div>
            </div>
        </div>

        <div class="main-grid">
            <!-- Left Section: Video & Instructions -->
            <div class="card">
                <h3 class="card-title">📹 Video Instruksi Terapi</h3>
                
                <div class="video-selector">
                    <label for="videoSelect">Pilih Jenis Terapi:</label>
                    <select id="videoSelect" onchange="changeVideo()">
                        <option value="">-- Pilih Video Terapi --</option>
                        <?php while($video = $videos_result->fetch_assoc()): ?>
                            <option value="<?= $video['id'] ?>" 
                                    data-url="<?= $video['video_url'] ?>"
                                    data-title="<?= $video['title'] ?>"
                                    data-desc="<?= $video['description'] ?>"
                                    data-duration="<?= $video['duration'] ?>"
                                    data-difficulty="<?= $video['difficulty'] ?>">
                                <?= $video['title'] ?> (<?= ucfirst($video['difficulty']) ?> - <?= gmdate("i:s", $video['duration']) ?> menit)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="video-container" id="videoContainer">
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white;">
                        <p>Pilih video terapi untuk memulai</p>
                    </div>
                </div>

                <div class="instruction-box" id="instructionBox">
                    <h3>📋 Instruksi Terapi</h3>
                    <p id="videoDescription">Silakan pilih video terapi terlebih dahulu untuk melihat instruksi detail.</p>
                    
                    <h3 style="margin-top: 20px;">⚠️ Peringatan Keselamatan:</h3>
                    <ul>
                        <li>Lakukan gerakan secara perlahan dan terkontrol</li>
                        <li>Jangan memaksakan gerakan yang menyebabkan nyeri</li>
                        <li>Pastikan alat sensor terpasang dengan benar di pergelangan tangan</li>
                        <li>Lakukan pemanasan ringan sebelum memulai terapi</li>
                        <li>Hentikan segera jika merasa pusing atau tidak nyaman</li>
                    </ul>
                </div>

                <div class="therapy-controls">
                    <button onclick="startTherapy()" class="btn btn-success" id="btnStart">
                        ▶️ Mulai Terapi
                    </button>
                    <button onclick="pauseTherapy()" class="btn btn-warning" id="btnPause" style="display:none;">
                        ⏸ Pause
                    </button>
                    <button onclick="stopTherapy()" class="btn btn-danger" id="btnStop" style="display:none;">
                        ⏹ Stop Terapi
                    </button>
                </div>

                <div class="alert alert-info" id="therapyAlert">
                    <strong>Tips:</strong> Pastikan sensor MPU6050 sudah terpasang di pergelangan tangan Anda sebelum memulai terapi.
                    <br><br>
                    <strong>📏 Deteksi Gerakan:</strong> Gerakan akan terhitung otomatis ketika Pitch sensor mencapai <strong>-90 derajat (±7°)</strong>. 
                    Ini menandakan lengan Anda telah diangkat ke posisi <strong>vertikal</strong>.
                    <br><br>
                    <strong>🎯 Range Deteksi:</strong> -83° sampai -97° (toleransi ±7° dari -90°)
                </div>
            </div>

            <!-- Right Section: Real-time Data -->
            <div class="card">
                <h3 class="card-title">📊 Data Gerakan Real-time</h3>
                
                <div class="status-panel" id="therapyStatus">
                    <div class="status-item">
                        <span class="status-label">Status:</span>
                        <span class="status-value" id="statusText">Standby</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Durasi:</span>
                        <span class="status-value" id="durationText">00:00</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Total Gerakan:</span>
                        <span class="status-value" id="movementCount" style="transition: all 0.3s;">0</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Pitch Saat Ini:</span>
                        <span class="status-value" id="currentPitchDisplay">--°</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Status Deteksi:</span>
                        <span class="status-value" id="detectionStatus" style="font-size: 14px; color: #999;">Menunggu...</span>
                    </div>
                </div>

                <div class="sensor-grid">
                    <div class="sensor-card">
                        <h4>🔄 Roll</h4>
                        <div class="value" id="rollValue">--</div>
                        <div class="unit">degrees</div>
                    </div>
                    <div class="sensor-card">
                        <h4>↕️ Pitch</h4>
                        <div class="value" id="pitchValue">--</div>
                        <div class="unit">degrees</div>
                    </div>
                    <div class="sensor-card">
                        <h4>📊 Accel X</h4>
                        <div class="value" id="axValue">--</div>
                        <div class="unit">G</div>
                    </div>
                    <div class="sensor-card">
                        <h4>📊 Accel Y</h4>
                        <div class="value" id="ayValue">--</div>
                        <div class="unit">G</div>
                    </div>
                    <div class="sensor-card">
                        <h4>📊 Accel Z</h4>
                        <div class="value" id="azValue">--</div>
                        <div class="unit">G</div>
                    </div>
                    <div class="sensor-card">
                        <h4>🔄 Gyro X</h4>
                        <div class="value" id="gxValue">--</div>
                        <div class="unit">°/s</div>
                    </div>
                    <div class="sensor-card">
                        <h4>🔄 Gyro Y</h4>
                        <div class="value" id="gyValue">--</div>
                        <div class="unit">°/s</div>
                    </div>
                    <div class="sensor-card">
                        <h4>🔄 Gyro Z</h4>
                        <div class="value" id="gzValue">--</div>
                        <div class="unit">°/s</div>
                    </div>
                </div>

                <div class="chart-mini">
                    <h4 style="margin-bottom: 15px; color: #333;">📈 Grafik Gerakan</h4>
                    <canvas id="movementChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 class="card-title" style="margin-bottom: 0;">📜 Riwayat Terapi</h3>
                <?php if ($total_completed > 0): ?>
                    <a href="grafik_terapi.php?patient_id=<?= $patient_id ?>" class="btn btn-info" style="padding: 8px 20px; font-size: 13px;">
                        📈 Lihat Grafik Lengkap
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($history_result->num_rows > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis Terapi</th>
                            <th>Durasi</th>
                            <th>Total Gerakan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($session = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($session['start_time'])) ?></td>
                                <td><?= $session['therapy_type'] ?></td>
                                <td><?= gmdate("i:s", $session['duration']) ?> menit</td>
                                <td><?= $session['total_movements'] ?> gerakan</td>
                                <td>
                                    <span class="badge <?= $session['completion_status'] == 'completed' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= ucfirst($session['completion_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detail_terapi.php?session_id=<?= $session['id'] ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">
                                        📋 Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    Belum ada riwayat terapi. Mulai sesi terapi pertama Anda!
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
   // ==========================================
// THERAPY PAGE - TRIPLE DETECTION SYSTEM
// 1. Vertical (Pitch -90°) - Mengangkat lengan
// 2. Horizontal (Gyro Z) - Kanan ke Kiri
// 3. Rotation (Roll ±90°) - Rotasi pergelangan tangan
// ==========================================

const patientId = <?= $patient_id ?>;
let therapyActive = false;
let sessionId = null;
let startTime = null;
let durationInterval = null;
let movementCount = 0;
let selectedVideoId = null;
let selectedVideoTitle = '';
let selectedTherapyType = ''; // 'vertical', 'horizontal', atau 'rotation'
let chart = null;

// ==========================================
// VERTICAL MOVEMENT DETECTION VARIABLES (Pitch -90°)
// ==========================================
let previousPitch = null;
let crossedVertical90 = false;
let pitchThreshold = 7;
let targetVertical90 = -90;

// ==========================================
// HORIZONTAL MOVEMENT DETECTION VARIABLES (Gyro Z + Accel Y)
// ==========================================
let previousGyroZ = null;
let previousAccelY = null;
let horizontalDirection = 'center';
let gyroZThreshold = 50;
let accelYThreshold = 0.3;
let horizontalPhase = 'waiting';

// ==========================================
// ROTATION MOVEMENT DETECTION VARIABLES (Roll ±90°)
// ==========================================
let previousRoll = null;
let lastRotationPosition = 'center';
let rollThreshold = 7;
let targetRollRight = -90;
let targetRollLeft = 90;
let rotationPhase = 'waiting';

// ==========================================
// GENERAL VARIABLES
// ==========================================
let lastMovementTime = 0;
let movementCooldown = 800;

console.log('Therapy page loaded for patient:', patientId);

// ==========================================
// INITIALIZE CHART
// ==========================================
const ctx = document.getElementById('movementChart').getContext('2d');
chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'Roll',
                data: [],
                borderColor: '#f39c12',
                backgroundColor: 'rgba(243, 156, 18, 0.1)',
                tension: 0.4,
                borderWidth: 2
            },
            {
                label: 'Pitch',
                data: [],
                borderColor: '#9b59b6',
                backgroundColor: 'rgba(155, 89, 182, 0.1)',
                tension: 0.4,
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: false,
                min: -100,
                max: 100
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});

// ==========================================
// VIDEO SELECTION & THERAPY TYPE DETECTION
// ==========================================
function changeVideo() {
    const select = document.getElementById('videoSelect');
    const option = select.options[select.selectedIndex];
    
    if (!option.value) {
        document.getElementById('videoContainer').innerHTML = 
            '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white;"><p>Pilih video terapi untuk memulai</p></div>';
        return;
    }

    selectedVideoId = option.value;
    selectedVideoTitle = option.dataset.title;
    const videoUrl = option.dataset.url;
    const description = option.dataset.desc;
    const difficulty = option.dataset.difficulty;

    // Deteksi jenis terapi berdasarkan judul video
    if (selectedVideoTitle.toLowerCase().includes('rotasi') || 
        selectedVideoTitle.toLowerCase().includes('putar') || 
        selectedVideoTitle.toLowerCase().includes('pergelangan')) {
        selectedTherapyType = 'rotation';
        console.log('🔄 Therapy type: ROTATION (Roll ±90°)');
    } else if (selectedVideoTitle.toLowerCase().includes('horizontal') || 
               selectedVideoTitle.toLowerCase().includes('kanan') && selectedVideoTitle.toLowerCase().includes('kiri')) {
        selectedTherapyType = 'horizontal';
        console.log('↔️ Therapy type: HORIZONTAL (Gyro Z + Accel Y)');
    } else if (selectedVideoTitle.toLowerCase().includes('vertikal') || 
               selectedVideoTitle.toLowerCase().includes('angkat') || 
               selectedVideoTitle.toLowerCase().includes('lengan')) {
        selectedTherapyType = 'vertical';
        console.log('⬆️ Therapy type: VERTICAL (Pitch -90°)');
    } else {
        selectedTherapyType = 'vertical';
        console.log('⬆️ Therapy type: VERTICAL (default)');
    }

    document.getElementById('videoContainer').innerHTML = 
        `<iframe src="${videoUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    
    let detectionInfo = '';
    if (selectedTherapyType === 'rotation') {
        detectionInfo = `<br><br><strong>🔄 Deteksi Gerakan Rotasi:</strong>
        <br>• Posisi Kanan: Roll = -90° (±7°)
        <br>• Posisi Kiri: Roll = +90° (±7°)
        <br>• 1 Gerakan = Putar dari Kanan → Kiri atau Kiri → Kanan`;
    } else if (selectedTherapyType === 'horizontal') {
        detectionInfo = `<br><br><strong>↔️ Deteksi Gerakan Horizontal:</strong>
        <br>• Menggunakan Gyroscope Z dan Accelerometer Y
        <br>• 1 Gerakan = Kanan ↔ Kiri`;
    } else {
        detectionInfo = `<br><br><strong>⬆️ Deteksi Gerakan Vertikal:</strong>
        <br>• Target: Pitch = -90° (±7°)
        <br>• 1 Gerakan = Angkat lengan vertikal`;
    }
    
    document.getElementById('videoDescription').innerHTML = 
        `<strong>${selectedVideoTitle}</strong><br>
        Tingkat Kesulitan: <span class="badge badge-${difficulty === 'mudah' ? 'success' : difficulty === 'sedang' ? 'warning' : 'danger'}">${difficulty.toUpperCase()}</span><br><br>
        ${description}${detectionInfo}`;
    
    updateTherapyAlert('select');
}

function updateTherapyAlert(state) {
    const alertElement = document.getElementById('therapyAlert');
    
    if (state === 'select') {
        if (selectedTherapyType === 'rotation') {
            alertElement.className = 'alert alert-info';
            alertElement.innerHTML = `<strong>Tips:</strong> Pastikan sensor MPU6050 terpasang di pergelangan tangan.
            <br><br><strong>🔄 Deteksi Gerakan Rotasi:</strong> Roll -90° (kanan) dan +90° (kiri).
            <br><strong>🎯 Range:</strong> Kanan -83° s/d -97° | Kiri 83° s/d 97°`;
        } else if (selectedTherapyType === 'horizontal') {
            alertElement.className = 'alert alert-info';
            alertElement.innerHTML = `<strong>Tips:</strong> Pastikan sensor MPU6050 terpasang di pergelangan tangan.
            <br><br><strong>↔️ Deteksi Horizontal:</strong> Gerakan kanan-kiri menggunakan Gyro Z dan Accel Y.`;
        } else {
            alertElement.className = 'alert alert-info';
            alertElement.innerHTML = `<strong>Tips:</strong> Pastikan sensor MPU6050 terpasang di pergelangan tangan.
            <br><br><strong>⬆️ Deteksi Vertikal:</strong> Pitch -90° (±7°) saat lengan vertikal.
            <br><strong>🎯 Range:</strong> -83° sampai -97°`;
        }
    } else if (state === 'active') {
        alertElement.className = 'alert alert-success';
        const msg = selectedTherapyType === 'rotation' ? 'Putar pergelangan tangan melingkar.' :
                    selectedTherapyType === 'horizontal' ? 'Gerakkan tangan kanan-kiri horizontal.' :
                    'Angkat lengan hingga vertikal.';
        alertElement.innerHTML = `✅ <strong>Terapi Dimulai!</strong> ${msg}`;
    } else if (state === 'pause') {
        alertElement.className = 'alert alert-warning';
        alertElement.innerHTML = '⏸ <strong>Terapi Di-pause</strong> - Klik Resume untuk melanjutkan.';
    } else if (state === 'complete') {
        alertElement.className = 'alert alert-info';
        alertElement.innerHTML = '✅ <strong>Terapi Selesai!</strong> Data tersimpan.';
    }
}

async function startTherapy() {
    if (!selectedVideoId) {
        alert('⚠️ Silakan pilih video terapi terlebih dahulu!');
        return;
    }
    if (therapyActive) {
        alert('⚠️ Terapi sudah berjalan!');
        return;
    }

    try {
        const response = await fetch('api/start_therapy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                patient_id: patientId,
                therapy_type: selectedVideoTitle
            })
        });

        const result = await response.json();
        if (result.success) {
            therapyActive = true;
            sessionId = result.session_id;
            startTime = Date.now();
            movementCount = 0;
            
            if (selectedTherapyType === 'rotation') {
                previousRoll = null;
                lastRotationPosition = 'center';
                rotationPhase = 'waiting';
            } else if (selectedTherapyType === 'horizontal') {
                previousGyroZ = null;
                previousAccelY = null;
                horizontalDirection = 'center';
                horizontalPhase = 'waiting';
            } else {
                previousPitch = null;
                crossedVertical90 = false;
            }
            
            lastMovementTime = 0;
            document.getElementById('therapyStatus').classList.add('active');
            document.getElementById('statusText').textContent = 'Aktif';
            document.getElementById('statusText').style.color = '#28a745';
            document.getElementById('btnStart').style.display = 'none';
            document.getElementById('btnPause').style.display = 'inline-block';
            document.getElementById('btnStop').style.display = 'inline-block';
            updateTherapyAlert('active');
            durationInterval = setInterval(updateDuration, 1000);
            fetchTherapyData();
            console.log('✅ Therapy started:', sessionId, 'Type:', selectedTherapyType);
        } else {
            alert('❌ Gagal memulai terapi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Error: ' + error.message);
    }
}

function pauseTherapy() {
    if (!therapyActive) return;
    therapyActive = false;
    clearInterval(durationInterval);
    document.getElementById('statusText').textContent = 'Pause';
    document.getElementById('statusText').style.color = '#ffc107';
    document.getElementById('btnPause').textContent = '▶️ Resume';
    updateTherapyAlert('pause');
}

async function stopTherapy() {
    if (!sessionId || !confirm('Yakin ingin menghentikan terapi?')) return;

    try {
        const duration = Math.floor((Date.now() - startTime) / 1000);
        const response = await fetch('api/stop_therapy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: sessionId,
                duration: duration,
                total_movements: movementCount
            })
        });

        const result = await response.json();
        if (result.success) {
            therapyActive = false;
            clearInterval(durationInterval);
            document.getElementById('statusText').textContent = 'Selesai';
            document.getElementById('statusText').style.color = '#dc3545';
            document.getElementById('btnStart').style.display = 'inline-block';
            document.getElementById('btnPause').style.display = 'none';
            document.getElementById('btnStop').style.display = 'none';
            updateTherapyAlert('complete');
            alert(`✅ Terapi selesai!\n\nDurasi: ${gmdate(duration)}\nTotal Gerakan: ${movementCount}`);
            setTimeout(() => location.reload(), 2000);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Error: ' + error.message);
    }
}

function updateDuration() {
    if (!startTime) return;
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    document.getElementById('durationText').textContent = gmdate(elapsed);
}

async function fetchTherapyData() {
    if (!therapyActive) return;

    try {
        const response = await fetch(`api/get_latest.php?patient_id=${patientId}&limit=1`);
        const data = await response.json();

        if (data.success && data.data.length > 0) {
            const latest = data.data[0];
            const currentPitch = parseFloat(latest.pitch);
            const currentRoll = parseFloat(latest.roll);
            const currentGyroZ = parseFloat(latest.gz);
            const currentAccelY = parseFloat(latest.ayG);

            document.getElementById('rollValue').textContent = currentRoll.toFixed(1);
            document.getElementById('pitchValue').textContent = currentPitch.toFixed(1);
            document.getElementById('axValue').textContent = parseFloat(latest.axG).toFixed(2);
            document.getElementById('ayValue').textContent = currentAccelY.toFixed(2);
            document.getElementById('azValue').textContent = parseFloat(latest.azG).toFixed(2);
            document.getElementById('gxValue').textContent = parseFloat(latest.gx).toFixed(2);
            document.getElementById('gyValue').textContent = parseFloat(latest.gy).toFixed(2);
            document.getElementById('gzValue').textContent = currentGyroZ.toFixed(2);
            
            if (selectedTherapyType === 'rotation') {
                document.getElementById('currentPitchDisplay').textContent = currentRoll.toFixed(1) + '° (Roll)';
                updateRotationDetectionStatus(currentRoll);
                detectRotationMovement(currentRoll);
            } else if (selectedTherapyType === 'horizontal') {
                document.getElementById('currentPitchDisplay').textContent = currentGyroZ.toFixed(1) + '°/s (Gyro Z)';
                updateHorizontalDetectionStatus(currentGyroZ, currentAccelY);
                detectHorizontalMovement(currentGyroZ, currentAccelY);
            } else {
                document.getElementById('currentPitchDisplay').textContent = currentPitch.toFixed(1) + '° (Pitch)';
                updateVerticalDetectionStatus(currentPitch);
                detectVerticalMovement(currentPitch);
            }

            const time = new Date().toLocaleTimeString('id-ID');
            chart.data.labels.push(time);
            chart.data.datasets[0].data.push(currentRoll);
            chart.data.datasets[1].data.push(currentPitch);

            if (chart.data.labels.length > 20) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
                chart.data.datasets[1].data.shift();
            }
            chart.update('none');

            await saveTherapyMovement(latest);
        }
    } catch (error) {
        console.error('Error:', error);
    }

    if (therapyActive) setTimeout(fetchTherapyData, 500);
}

function detectVerticalMovement(currentPitch) {
    const currentTime = Date.now();
    if (previousPitch !== null) {
        const deviationFrom90 = Math.abs(currentPitch - targetVertical90);
        const isAt90 = deviationFrom90 <= pitchThreshold;
        const wasNotAt90 = Math.abs(previousPitch - targetVertical90) > pitchThreshold;
        const cooldownPassed = (currentTime - lastMovementTime) > movementCooldown;
        
        if (isAt90 && wasNotAt90 && cooldownPassed && !crossedVertical90) {
            movementCount++;
            document.getElementById('movementCount').textContent = movementCount;
            visualFeedback();
            crossedVertical90 = true;
            lastMovementTime = currentTime;
            console.log(`✅ VERTICAL #${movementCount} | Pitch: ${currentPitch.toFixed(2)}°`);
            playBeep();
        }
        if (deviationFrom90 > pitchThreshold + 5) crossedVertical90 = false;
    }
    previousPitch = currentPitch;
}

function updateVerticalDetectionStatus(currentPitch) {
    const detectionElement = document.getElementById('detectionStatus');
    const deviation = Math.abs(currentPitch - targetVertical90);
    if (deviation <= pitchThreshold) {
        detectionElement.textContent = '✅ Posisi 90°!';
        detectionElement.style.color = '#28a745';
    } else if (deviation < 15) {
        detectionElement.textContent = `⚠️ Mendekati (${currentPitch.toFixed(1)}°)`;
        detectionElement.style.color = '#ffc107';
    } else {
        detectionElement.textContent = '⏳ Menunggu...';
        detectionElement.style.color = '#999';
    }
}

function detectHorizontalMovement(currentGyroZ, currentAccelY) {
    const currentTime = Date.now();
    if (previousGyroZ !== null && previousAccelY !== null) {
        const gyroZAbs = Math.abs(currentGyroZ);
        const isMoving = gyroZAbs > gyroZThreshold;
        const cooldownPassed = (currentTime - lastMovementTime) > movementCooldown;
        
        let currentDirection = 'center';
        if (isMoving) {
            if (currentGyroZ > gyroZThreshold && currentAccelY > accelYThreshold) {
                currentDirection = 'right';
            } else if (currentGyroZ < -gyroZThreshold && currentAccelY < -accelYThreshold) {
                currentDirection = 'left';
            }
        }
        
        switch(horizontalPhase) {
            case 'waiting':
                if (currentDirection === 'right') {
                    horizontalPhase = 'moving_right';
                    horizontalDirection = 'right';
                } else if (currentDirection === 'left') {
                    horizontalPhase = 'moving_left';
                    horizontalDirection = 'left';
                }
                break;
            case 'moving_right':
                if (currentDirection === 'left' && cooldownPassed) {
                    movementCount++;
                    document.getElementById('movementCount').textContent = movementCount;
                    visualFeedback();
                    lastMovementTime = currentTime;
                    horizontalPhase = 'moving_left';
                    console.log(`✅ HORIZONTAL #${movementCount} | KANAN → KIRI`);
                    playBeep();
                } else if (currentDirection === 'center') horizontalPhase = 'waiting';
                break;
            case 'moving_left':
                if (currentDirection === 'right' && cooldownPassed) {
                    movementCount++;
                    document.getElementById('movementCount').textContent = movementCount;
                    visualFeedback();
                    lastMovementTime = currentTime;
                    horizontalPhase = 'moving_right';
                    console.log(`✅ HORIZONTAL #${movementCount} | KIRI → KANAN`);
                    playBeep();
                } else if (currentDirection === 'center') horizontalPhase = 'waiting';
                break;
        }
    }
    previousGyroZ = currentGyroZ;
    previousAccelY = currentAccelY;
}

function updateHorizontalDetectionStatus(currentGyroZ, currentAccelY) {
    const detectionElement = document.getElementById('detectionStatus');
    const gyroZAbs = Math.abs(currentGyroZ);
    if (gyroZAbs > gyroZThreshold) {
        if (currentGyroZ > 0 && currentAccelY > accelYThreshold) {
            detectionElement.textContent = '➡️ Ke KANAN';
            detectionElement.style.color = '#28a745';
        } else if (currentGyroZ < 0 && currentAccelY < -accelYThreshold) {
            detectionElement.textContent = '⬅️ Ke KIRI';
            detectionElement.style.color = '#28a745';
        } else {
            detectionElement.textContent = '🔄 Bergerak...';
            detectionElement.style.color = '#ffc107';
        }
    } else {
        detectionElement.textContent = '⏺️ CENTER';
        detectionElement.style.color = '#17a2b8';
    }
}

function detectRotationMovement(currentRoll) {
    const currentTime = Date.now();
    if (previousRoll !== null) {
        const deviationFromRight = Math.abs(currentRoll - targetRollRight);
        const deviationFromLeft = Math.abs(currentRoll - targetRollLeft);
        const isAtRight = deviationFromRight <= rollThreshold;
        const isAtLeft = deviationFromLeft <= rollThreshold;
        const isAtCenter = Math.abs(currentRoll) < 30;
        const cooldownPassed = (currentTime - lastMovementTime) > movementCooldown;
        
        switch(rotationPhase) {
            case 'waiting':
                if (isAtRight) {
                    rotationPhase = 'at_right';
                    lastRotationPosition = 'right';
                } else if (isAtLeft) {
                    rotationPhase = 'at_left';
                    lastRotationPosition = 'left';
                }
                break;
            case 'at_right':
                if (isAtLeft && cooldownPassed) {
                    movementCount++;
                    document.getElementById('movementCount').textContent = movementCount;
                    visualFeedback();
                    lastMovementTime = currentTime;
                    rotationPhase = 'at_left';
                    console.log(`✅ ROTATION #${movementCount} | KANAN → KIRI`);
                    playBeep();
                } else if (isAtCenter) rotationPhase = 'waiting';
                break;
            case 'at_left':
                if (isAtRight && cooldownPassed) {
                    movementCount++;
                    document.getElementById('movementCount').textContent = movementCount;
                    visualFeedback();
                    lastMovementTime = currentTime;
                    rotationPhase = 'at_right';
                    console.log(`✅ ROTATION #${movementCount} | KIRI → KANAN`);
                    playBeep();
                } else if (isAtCenter) rotationPhase = 'waiting';
                break;
        }
    }
    previousRoll = currentRoll;
}

function updateRotationDetectionStatus(currentRoll) {
    const detectionElement = document.getElementById('detectionStatus');
    const deviationFromRight = Math.abs(currentRoll - targetRollRight);
    const deviationFromLeft = Math.abs(currentRoll - targetRollLeft);
    
    if (deviationFromRight <= rollThreshold) {
        detectionElement.textContent = '✅ KANAN (-90°)!';
        detectionElement.style.color = '#28a745';
    } else if (deviationFromLeft <= rollThreshold) {
        detectionElement.textContent = '✅ KIRI (+90°)!';
        detectionElement.style.color = '#28a745';
    } else if (Math.abs(currentRoll) < 30) {
        detectionElement.textContent = '⏺️ CENTER';
        detectionElement.style.color = '#17a2b8';
    } else if (currentRoll < 0 && deviationFromRight < 20) {
        detectionElement.textContent = `⚠️ → KANAN (${currentRoll.toFixed(1)}°)`;
        detectionElement.style.color = '#ffc107';
    } else if (currentRoll > 0 && deviationFromLeft < 20) {
        detectionElement.textContent = `⚠️ ← KIRI (${currentRoll.toFixed(1)}°)`;
        detectionElement.style.color = '#ffc107';
    } else {
        detectionElement.textContent = '⏳ Menunggu...';
        detectionElement.style.color = '#999';
    }
}

async function saveTherapyMovement(sensorData) {
    if (!sessionId) return;
    try {
        await fetch('api/save_therapy_movement.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: sessionId,
                patient_id: patientId,
                axG: sensorData.axG,
                ayG: sensorData.ayG,
                azG: sensorData.azG,
                gx: sensorData.gx,
                gy: sensorData.gy,
                gz: sensorData.gz,
                roll: sensorData.roll,
                pitch: sensorData.pitch
            })
        });
    } catch (error) {
        console.error('Error saving:', error);
    }
}

function visualFeedback() {
    const countElement = document.getElementById('movementCount');
    countElement.style.color = '#28a745';
    countElement.style.transform = 'scale(1.3)';
    setTimeout(() => {
        countElement.style.color = '#667eea';
        countElement.style.transform = 'scale(1)';
    }, 300);
}

function playBeep() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch (error) {
        console.log('Audio not supported');
    }
}

function gmdate(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

document.getElementById('btnPause').addEventListener('click', function() {
    if (this.textContent.includes('Resume')) {
        therapyActive = true;
        durationInterval = setInterval(updateDuration, 1000);
        document.getElementById('statusText').textContent = 'Aktif';
        document.getElementById('statusText').style.color = '#28a745';
        this.textContent = '⏸ Pause';
        updateTherapyAlert('active');
        fetchTherapyData();
        console.log('▶️ Resumed');
    } else {
        pauseTherapy();
    }
});

console.log('=== TRIPLE DETECTION SYSTEM READY ===');
    </script>