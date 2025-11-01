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

$patient_id = $_GET['patient_id'] ?? $_SESSION['active_patient_id'] ?? 0;

if ($patient_id == 0) {
    header("Location: index.php");
    exit;
}

$_SESSION['active_patient_id'] = $patient_id;

$sql = "SELECT * FROM patients WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

if (!$patient) {
    header("Location: index.php");
    exit;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring - <?= $patient['nama'] ?></title>
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
            max-width: 1600px;
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
        .patient-details h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .patient-meta {
            display: flex;
            gap: 20px;
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
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .actions {
            display: flex;
            gap: 10px;
        }
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
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-success { background: #28a745; color: white; }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .left-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        /* Dashboard Cards - 2 baris */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-title {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .card-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .card-unit {
            font-size: 12px;
            color: #666;
        }
        
        /* Colors untuk cards */
        .card-heart .card-value { color: #e74c3c; }
        .card-spo2 .card-value { color: #3498db; }
        .card-roll .card-value { color: #f39c12; }
        .card-pitch .card-value { color: #9b59b6; }
        .card-accel-x .card-value { color: #e67e22; }
        .card-accel-y .card-value { color: #1abc9c; }
        .card-accel-z .card-value { color: #34495e; }
        .card-gyro-x .card-value { color: #c0392b; }
        .card-gyro-y .card-value { color: #16a085; }
        .card-gyro-z .card-value { color: #2c3e50; }
        
        .video-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .video-container {
            position: relative;
            width: 100%;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        #webcam {
            width: 100%;
            display: block;
            border-radius: 10px;
        }
        .recording-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 8px;
        }
        .recording-indicator.active {
            display: flex;
        }
        .rec-dot {
            width: 10px;
            height: 10px;
            background: white;
            border-radius: 50%;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        .video-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .video-info {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 13px;
        }
        .video-info div {
            margin-bottom: 8px;
        }
        .video-info strong {
            color: #333;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        .status-online { background: #28a745; }
        .status-offline { background: #dc3545; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Chart Containers - 2 grafik */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .data-table {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #999;
        }
        @media (max-width: 1400px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            .dashboard {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
            .patient-info {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="patient-info">
                <div class="patient-details">
                    <h2>🏥 Monitoring Real-time</h2>
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
                        <?php if ($patient['status_kesehatan'] == 'Sakit'): ?>
                            <span class="badge <?= 
                                $patient['kategori_sakit'] == 'Ringan' ? 'badge-warning' : 
                                ($patient['kategori_sakit'] == 'Sedang' ? 'badge-secondary' : 'badge-danger') 
                            ?>">
                                <?= $patient['kategori_sakit'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="actions">
                    <a href="detail_pasien.php?patient_id=<?= $patient_id ?>" class="btn btn-info">📋 Riwayat Data</a>
                    <button onclick="stopMonitoring()" class="btn btn-danger">⏹ Stop & Simpan</button>
                </div>
            </div>
        </div>

        <div class="main-grid">
            <div class="left-section">
                <!-- Dashboard Cards - Roll & Pitch -->
                <div class="dashboard" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="card card-roll">
                        <div class="card-title">🔄 Roll</div>
                        <div class="card-value" id="rollValue">--</div>
                        <div class="card-unit">° (degrees)</div>
                    </div>
                    <div class="card card-pitch">
                        <div class="card-title">↕️ Pitch</div>
                        <div class="card-value" id="pitchValue">--</div>
                        <div class="card-unit">° (degrees)</div>
                    </div>
                </div>

                <!-- Dashboard Cards Baris 2 - Acceleration & Gyro -->
                <div class="dashboard" style="grid-template-columns: repeat(6, 1fr);">
                    <!-- Acceleration -->
                    <div class="card card-accel-x">
                        <div class="card-title">📊 Accel X</div>
                        <div class="card-value" id="axValue">--</div>
                        <div class="card-unit">G</div>
                    </div>
                    <div class="card card-accel-y">
                        <div class="card-title">📊 Accel Y</div>
                        <div class="card-value" id="ayValue">--</div>
                        <div class="card-unit">G</div>
                    </div>
                    <div class="card card-accel-z">
                        <div class="card-title">📊 Accel Z</div>
                        <div class="card-value" id="azValue">--</div>
                        <div class="card-unit">G</div>
                    </div>
                    
                    <!-- Gyro -->
                    <div class="card card-gyro-x">
                        <div class="card-title">🔄 Gyro X</div>
                        <div class="card-value" id="gxValue">--</div>
                        <div class="card-unit">°/s</div>
                    </div>
                    <div class="card card-gyro-y">
                        <div class="card-title">🔄 Gyro Y</div>
                        <div class="card-value" id="gyValue">--</div>
                        <div class="card-unit">°/s</div>
                    </div>
                    <div class="card card-gyro-z">
                        <div class="card-title">🔄 Gyro Z</div>
                        <div class="card-value" id="gzValue">--</div>
                        <div class="card-unit">°/s</div>
                    </div>
                </div>

                <!-- Charts Grid - 2 Grafik Bersebelahan -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <div class="chart-title">
                            <span class="status-indicator status-online" id="statusIndicator1"></span>
                            📈 Acceleration (X, Y, Z)
                        </div>
                        <canvas id="accelChart" width="400" height="200"></canvas>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">
                            <span class="status-indicator status-online" id="statusIndicator2"></span>
                            🔄 Gyroscope (X, Y, Z)
                        </div>
                        <canvas id="gyroChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <div class="data-table">
                    <h3 class="chart-title">
                        <span class="status-indicator status-online" id="statusIndicator"></span>
                        Status: <span id="statusText">Waiting for data...</span>
                    </h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>HR</th>
                                <th>SpO2</th>
                                <th>Roll</th>
                                <th>Pitch</th>
                                <th>AX</th>
                                <th>AY</th>
                                <th>AZ</th>
                                <th>GX</th>
                                <th>GY</th>
                                <th>GZ</th>
                            </tr>
                        </thead>
                        <tbody id="dataTable">
                            <tr>
                                <td colspan="11" class="loading">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="video-section">
                <h3 class="chart-title">📹 Video Recording</h3>
                <div class="video-container">
                    <video id="webcam" autoplay muted></video>
                    <div class="recording-indicator" id="recordingIndicator">
                        <div class="rec-dot"></div>
                        <span>REC <span id="recordingTime">00:00</span></span>
                    </div>
                </div>
                <div class="video-controls">
                    <button onclick="startRecording()" class="btn btn-success" id="btnStart">
                        ▶️ Mulai Rekam
                    </button>
                    <button onclick="pauseRecording()" class="btn btn-warning" id="btnPause" style="display:none;">
                        ⏸ Pause
                    </button>
                    <button onclick="stopRecording()" class="btn btn-danger" id="btnStop" style="display:none;">
                        ⏹ Stop Rekam
                    </button>
                </div>
                <div class="video-info">
                    <div><strong>Status:</strong> <span id="videoStatus">Siap untuk merekam</span></div>
                    <div><strong>Durasi:</strong> <span id="videoDuration">00:00</span></div>
                    <div><strong>Ukuran:</strong> <span id="videoSize">0 MB</span></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
const patientId = <?= $patient_id ?>;
let accelChart, gyroChart;
let mediaRecorder;
let recordedChunks = [];
let stream;
let recordingStartTime;
let recordingInterval;

console.log('=== MONITORING PAGE LOADED ===');
console.log('Patient ID:', patientId);

// ========================================
// WEBCAM FUNCTIONS
// ========================================

async function initWebcam() {
    console.log('🎥 initWebcam() called');
    
    const videoElement = document.getElementById('webcam');
    const statusElement = document.getElementById('videoStatus');
    
    if (!videoElement) {
        console.error('❌ Video element not found!');
        return;
    }
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.error('❌ getUserMedia not available');
        statusElement.textContent = 'Error: Browser tidak support';
        alert('Browser Anda tidak support webcam!\nGunakan Chrome, Edge, atau Firefox versi terbaru.');
        return;
    }
    
    statusElement.textContent = 'Meminta akses webcam...';
    
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 },
                height: { ideal: 480 }
            }, 
            audio: true 
        });
        
        console.log('✅ Camera access granted!');
        videoElement.srcObject = stream;
        statusElement.textContent = 'Webcam aktif ✓';
        
    } catch (err) {
        console.error('❌ WEBCAM ERROR:', err);
        
        let errorMsg = 'Error: ';
        switch(err.name) {
            case 'NotAllowedError':
            case 'PermissionDeniedError':
                errorMsg = 'Izin ditolak! Klik ikon kamera di address bar → Allow';
                break;
            case 'NotFoundError':
                errorMsg = 'Webcam tidak ditemukan!';
                break;
            case 'NotReadableError':
                errorMsg = 'Webcam sedang digunakan aplikasi lain!';
                break;
            default:
                errorMsg = err.message;
        }
        
        statusElement.textContent = errorMsg;
        alert('Tidak dapat mengakses webcam:\n' + errorMsg);
    }
}

function startRecording() {
    console.log('▶️ startRecording() called');
    
    if (!stream) {
        alert('Webcam belum siap! Refresh halaman dan coba lagi.');
        return;
    }

    recordedChunks = [];
    
    try {
        mediaRecorder = new MediaRecorder(stream, {
            mimeType: 'video/webm;codecs=vp9'
        });

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                recordedChunks.push(event.data);
                updateVideoSize();
            }
        };

        mediaRecorder.onstop = () => {
            saveVideo();
        };

        mediaRecorder.start(1000);
        recordingStartTime = Date.now();
        
        document.getElementById('recordingIndicator').classList.add('active');
        document.getElementById('btnStart').style.display = 'none';
        document.getElementById('btnPause').style.display = 'inline-block';
        document.getElementById('btnStop').style.display = 'inline-block';
        document.getElementById('videoStatus').textContent = 'Sedang merekam...';

        recordingInterval = setInterval(updateRecordingTime, 1000);
        
    } catch (error) {
        console.error('❌ Error starting recording:', error);
        alert('Gagal memulai recording: ' + error.message);
    }
}

function pauseRecording() {
    if (!mediaRecorder) return;
    
    if (mediaRecorder.state === 'recording') {
        mediaRecorder.pause();
        clearInterval(recordingInterval);
        document.getElementById('videoStatus').textContent = 'Rekaman di-pause';
        document.getElementById('btnPause').textContent = '▶️ Resume';
    } else if (mediaRecorder.state === 'paused') {
        mediaRecorder.resume();
        recordingInterval = setInterval(updateRecordingTime, 1000);
        document.getElementById('videoStatus').textContent = 'Sedang merekam...';
        document.getElementById('btnPause').textContent = '⏸ Pause';
    }
}

function stopRecording() {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') return;
    
    mediaRecorder.stop();
    clearInterval(recordingInterval);
    document.getElementById('recordingIndicator').classList.remove('active');
    document.getElementById('btnStart').style.display = 'inline-block';
    document.getElementById('btnPause').style.display = 'none';
    document.getElementById('btnStop').style.display = 'none';
    document.getElementById('videoStatus').textContent = 'Menyimpan video...';
}

function updateRecordingTime() {
    const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;
    const timeString = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    document.getElementById('recordingTime').textContent = timeString;
    document.getElementById('videoDuration').textContent = timeString;
}

function updateVideoSize() {
    const totalSize = recordedChunks.reduce((acc, chunk) => acc + chunk.size, 0);
    const sizeMB = (totalSize / (1024 * 1024)).toFixed(2);
    document.getElementById('videoSize').textContent = sizeMB + ' MB';
}

async function saveVideo() {
    const blob = new Blob(recordedChunks, { type: 'video/webm' });
    const duration = Math.floor((Date.now() - recordingStartTime) / 1000);

    const formData = new FormData();
    formData.append('video', blob, `patient_${patientId}_${Date.now()}.webm`);
    formData.append('patient_id', patientId);
    formData.append('duration', duration);

    try {
        const response = await fetch('api/save_video.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('videoStatus').textContent = 'Video berhasil disimpan! ✓';
            alert('✅ Video berhasil disimpan!');
        } else {
            document.getElementById('videoStatus').textContent = 'Error: ' + result.message;
            alert('❌ Gagal menyimpan video:\n' + result.message);
        }
    } catch (error) {
        console.error('❌ Error saving video:', error);
        document.getElementById('videoStatus').textContent = 'Error saat menyimpan';
        alert('❌ Error:\n' + error.message);
    }
}

function stopMonitoring() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        if (confirm('Rekaman sedang berjalan. Stop dan simpan video?')) {
            stopRecording();
            setTimeout(() => {
                window.location.href = 'index.php?stop=1';
            }, 2000);
        }
    } else {
        window.location.href = 'index.php?stop=1';
    }
}

// ========================================
// CHART INITIALIZATION
// ========================================

console.log('📊 Initializing charts...');

// Acceleration Chart
const ctxAccel = document.getElementById('accelChart').getContext('2d');
accelChart = new Chart(ctxAccel, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'Accel X (G)',
                data: [],
                borderColor: '#e67e22',
                backgroundColor: 'rgba(230, 126, 34, 0.1)',
                tension: 0.4,
                borderWidth: 2
            },
            {
                label: 'Accel Y (G)',
                data: [],
                borderColor: '#1abc9c',
                backgroundColor: 'rgba(26, 188, 156, 0.1)',
                tension: 0.4,
                borderWidth: 2
            },
            {
                label: 'Accel Z (G)',
                data: [],
                borderColor: '#34495e',
                backgroundColor: 'rgba(52, 73, 94, 0.1)',
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
                min: -2,
                max: 2
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

// Gyroscope Chart
const ctxGyro = document.getElementById('gyroChart').getContext('2d');
gyroChart = new Chart(ctxGyro, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'Gyro X (°/s)',
                data: [],
                borderColor: '#c0392b',
                backgroundColor: 'rgba(192, 57, 43, 0.1)',
                tension: 0.4,
                borderWidth: 2
            },
            {
                label: 'Gyro Y (°/s)',
                data: [],
                borderColor: '#16a085',
                backgroundColor: 'rgba(22, 160, 133, 0.1)',
                tension: 0.4,
                borderWidth: 2
            },
            {
                label: 'Gyro Z (°/s)',
                data: [],
                borderColor: '#2c3e50',
                backgroundColor: 'rgba(44, 62, 80, 0.1)',
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
                beginAtZero: false
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

console.log('✅ Charts initialized');

// ========================================
// SENSOR DATA FUNCTIONS
// ========================================

async function fetchLatestData() {
    try {
        const response = await fetch(`api/get_latest.php?patient_id=${patientId}&limit=20`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            updateDashboard(data.data[0]);
            updateCharts(data.data);
            updateTable(data.data);
            updateStatus(true);
        } else {
            updateStatus(false);
        }
    } catch (error) {
        console.error('Error fetching data:', error);
        updateStatus(false);
    }
}

function updateDashboard(latest) {
    // Update nilai cards
    document.getElementById('hrValue').textContent = latest.hr || '--';
    document.getElementById('spo2Value').textContent = latest.spo2 || '--';
    document.getElementById('rollValue').textContent = latest.roll ? parseFloat(latest.roll).toFixed(1) : '--';
    document.getElementById('pitchValue').textContent = latest.pitch ? parseFloat(latest.pitch).toFixed(1) : '--';
    
    // Update Acceleration values
    document.getElementById('axValue').textContent = latest.axG ? parseFloat(latest.axG).toFixed(2) : '--';
    document.getElementById('ayValue').textContent = latest.ayG ? parseFloat(latest.ayG).toFixed(2) : '--';
    document.getElementById('azValue').textContent = latest.azG ? parseFloat(latest.azG).toFixed(2) : '--';
    
    // Update Gyro values
    document.getElementById('gxValue').textContent = latest.gx ? parseFloat(latest.gx).toFixed(2) : '--';
    document.getElementById('gyValue').textContent = latest.gy ? parseFloat(latest.gy).toFixed(2) : '--';
    document.getElementById('gzValue').textContent = latest.gz ? parseFloat(latest.gz).toFixed(2) : '--';
}

function updateCharts(dataArray) {
    // Reverse untuk urutan waktu yang benar
    const reversedData = dataArray.reverse();
    
    const labels = reversedData.map(d => {
        const time = new Date(d.timestamp);
        return time.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    });
    
    // Data untuk Acceleration Chart
    const axData = reversedData.map(d => parseFloat(d.axG));
    const ayData = reversedData.map(d => parseFloat(d.ayG));
    const azData = reversedData.map(d => parseFloat(d.azG));
    
    // Data untuk Gyro Chart
    const gxData = reversedData.map(d => parseFloat(d.gx));
    const gyData = reversedData.map(d => parseFloat(d.gy));
    const gzData = reversedData.map(d => parseFloat(d.gz));

    // Update Acceleration Chart
    accelChart.data.labels = labels.slice(-20);
    accelChart.data.datasets[0].data = axData.slice(-20);
    accelChart.data.datasets[1].data = ayData.slice(-20);
    accelChart.data.datasets[2].data = azData.slice(-20);
    accelChart.update('none'); // 'none' untuk performa lebih baik
    
    // Update Gyro Chart
    gyroChart.data.labels = labels.slice(-20);
    gyroChart.data.datasets[0].data = gxData.slice(-20);
    gyroChart.data.datasets[1].data = gyData.slice(-20);
    gyroChart.data.datasets[2].data = gzData.slice(-20);
    gyroChart.update('none');
}

function updateTable(dataArray) {
    const tbody = document.getElementById('dataTable');
    tbody.innerHTML = '';
    
    // Tampilkan 10 data terbaru
    dataArray.slice(0, 10).forEach(row => {
        const time = new Date(row.timestamp);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${time.toLocaleString('id-ID', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            })}</td>
            <td><strong>${row.hr}</strong></td>
            <td><strong>${row.spo2}%</strong></td>
            <td>${parseFloat(row.roll).toFixed(1)}°</td>
            <td>${parseFloat(row.pitch).toFixed(1)}°</td>
            <td>${parseFloat(row.axG).toFixed(2)}</td>
            <td>${parseFloat(row.ayG).toFixed(2)}</td>
            <td>${parseFloat(row.azG).toFixed(2)}</td>
            <td>${parseFloat(row.gx).toFixed(2)}</td>
            <td>${parseFloat(row.gy).toFixed(2)}</td>
            <td>${parseFloat(row.gz).toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });
}

function updateStatus(online) {
    const indicators = [
        document.getElementById('statusIndicator'),
        document.getElementById('statusIndicator1'),
        document.getElementById('statusIndicator2')
    ];
    const statusText = document.getElementById('statusText');
    
    if (online) {
        indicators.forEach(indicator => {
            if (indicator) {
                indicator.className = 'status-indicator status-online';
            }
        });
        if (statusText) statusText.textContent = 'Monitoring Active';
    } else {
        indicators.forEach(indicator => {
            if (indicator) {
                indicator.className = 'status-indicator status-offline';
            }
        });
        if (statusText) statusText.textContent = 'Waiting for data...';
    }
}

// ========================================
// PAGE INITIALIZATION
// ========================================

window.addEventListener('load', () => {
    console.log('=== WINDOW LOADED ===');
    console.log('Initializing webcam...');
    initWebcam();
    
    console.log('Starting data fetch...');
    fetchLatestData();
    setInterval(fetchLatestData, 2000); // Update setiap 2 detik
});

// Cleanup saat leave page
window.addEventListener('beforeunload', (e) => {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        e.preventDefault();
        e.returnValue = 'Rekaman sedang berjalan. Yakin ingin keluar?';
    }
    if (stream) {
        stream.getTracks().forEach(track => {
            console.log('Stopping track:', track.label);
            track.stop();
        });
    }
});

console.log('=== SCRIPT LOADED SUCCESSFULLY ===');
    </script>
</body>
</html>