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
    header("Location: index.php");
    exit;
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
    header("Location: index.php");
    exit;
}

// Get movement data
$movements_sql = "SELECT * FROM therapy_movements WHERE session_id = ? ORDER BY timestamp ASC";
$stmt = $conn->prepare($movements_sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$movements_result = $stmt->get_result();

// Store movements in array for chart
$movements_array = [];
while ($row = $movements_result->fetch_assoc()) {
    $movements_array[] = $row;
}
$stmt->close();

// Calculate statistics
$stats_sql = "SELECT 
    COUNT(*) as total_data,
    AVG(roll) as avg_roll,
    MIN(roll) as min_roll,
    MAX(roll) as max_roll,
    AVG(pitch) as avg_pitch,
    MIN(pitch) as min_pitch,
    MAX(pitch) as max_pitch,
    AVG(axG) as avg_ax,
    AVG(ayG) as avg_ay,
    AVG(azG) as avg_az
    FROM therapy_movements WHERE session_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Terapi - <?= $session['nama'] ?></title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-right: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: #28a745;
        }
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .stat-title {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-subtitle {
            font-size: 12px;
            color: #666;
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
            font-size: 13px;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            font-size: 11px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .chart-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2>📊 Detail Sesi Terapi</h2>
                    <p style="color: #666; margin-top: 10px;">
                        <strong><?= $session['nama'] ?></strong> | <?= $session['therapy_type'] ?>
                    </p>
                </div>
                <div>
                    <a href="grafik_terapi.php?patient_id=<?= $session['patient_id'] ?>" class="btn btn-success">
                        📈 Lihat Grafik Perbandingan
                    </a>
                    <a href="api/export_therapy_csv.php?session_id=<?= $session_id ?>" class="btn btn-warning">
                        📥 Export CSV
                    </a>
                    <a href="terapi.php?patient_id=<?= $session['patient_id'] ?>" class="btn">← Kembali</a>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">📅 Tanggal & Waktu</div>
                <div class="stat-value" style="font-size: 16px;">
                    <?= date('d/m/Y H:i', strtotime($session['start_time'])) ?>
                </div>
                <div class="stat-subtitle">Mulai terapi</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">⏱️ Durasi</div>
                <div class="stat-value"><?= gmdate("i:s", $session['duration']) ?></div>
                <div class="stat-subtitle">menit</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">🔄 Total Gerakan</div>
                <div class="stat-value"><?= $session['total_movements'] ?></div>
                <div class="stat-subtitle">gerakan terekam</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">📊 Total Data</div>
                <div class="stat-value"><?= $stats['total_data'] ?></div>
                <div class="stat-subtitle">data points</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">🔄 Roll (Avg)</div>
                <div class="stat-value"><?= number_format($stats['avg_roll'], 1) ?>°</div>
                <div class="stat-subtitle">
                    Min: <?= number_format($stats['min_roll'], 1) ?>° | 
                    Max: <?= number_format($stats['max_roll'], 1) ?>°
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">↕️ Pitch (Avg)</div>
                <div class="stat-value"><?= number_format($stats['avg_pitch'], 1) ?>°</div>
                <div class="stat-subtitle">
                    Min: <?= number_format($stats['min_pitch'], 1) ?>° | 
                    Max: <?= number_format($stats['max_pitch'], 1) ?>°
                </div>
            </div>
        </div>

        <!-- Grafik Gerakan Terdeteksi -->
        <div class="chart-section">
            <div class="chart-header">
                <h3 class="chart-title">📈 Grafik Gerakan yang Terdeteksi (Roll & Pitch)</h3>
                <button onclick="exportMovementChartPDF()" class="btn btn-info">
                    📄 Export PDF
                </button>
            </div>
            
            <div class="alert-info">
    ℹ️          <strong>Keterangan:</strong> Grafik ini menampilkan data Roll & Pitch hanya pada saat gerakan terdeteksi 
                (ketika Pitch mencapai <strong>-90° ± 7°</strong>, range: <strong>-83° sampai -97°</strong>). 
                <br><br>
                Setiap titik pada grafik merepresentasikan 1 gerakan yang berhasil tercatat ketika lengan diangkat ke posisi vertikal.
                <br><br>
                <strong>🎯 Target:</strong> Pitch = -90° (lengan vertikal)<br>
                <strong>📏 Range Valid:</strong> -83° sampai -97° (toleransi ±7°)<br>
                <strong>✅ Gerakan Terdeteksi:</strong> Saat pitch masuk ke range valid setelah berada di luar range
            </div>

            <canvas id="movementChart" width="400" height="200"></canvas>
            
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <strong>Total Gerakan Terdeteksi:</strong> <span id="totalDetectedMovements">0</span> gerakan
            </div>
        </div>

        <div class="data-table">
            <h3 style="margin-bottom: 20px;">📋 Data Gerakan Lengkap</h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Waktu</th>
                        <th>Roll</th>
                        <th>Pitch</th>
                        <th>Accel X</th>
                        <th>Accel Y</th>
                        <th>Accel Z</th>
                        <th>Gyro X</th>
                        <th>Gyro Y</th>
                        <th>Gyro Z</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($movements_array as $movement):
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('H:i:s', strtotime($movement['timestamp'])) ?></td>
                            <td><?= number_format($movement['roll'], 2) ?>°</td>
                            <td><?= number_format($movement['pitch'], 2) ?>°</td>
                            <td><?= number_format($movement['axG'], 3) ?></td>
                            <td><?= number_format($movement['ayG'], 3) ?></td>
                            <td><?= number_format($movement['azG'], 3) ?></td>
                            <td><?= number_format($movement['gx'], 2) ?></td>
                            <td><?= number_format($movement['gy'], 2) ?></td>
                            <td><?= number_format($movement['gz'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
    // ==========================================
// DETAIL TERAPI PAGE - MULTI DETECTION ANALYSIS
// Support: Vertical, Horizontal, Rotation
// ==========================================

const movementsData = <?= json_encode($movements_array) ?>;
const therapyType = "<?= $session['therapy_type'] ?>"; // Dari database

console.log('Total movements data:', movementsData.length);
console.log('Therapy type:', therapyType);

// Deteksi jenis terapi berdasarkan nama
let detectionType = 'vertical'; // default
if (therapyType.toLowerCase().includes('rotasi') || 
    therapyType.toLowerCase().includes('putar') || 
    therapyType.toLowerCase().includes('pergelangan')) {
    detectionType = 'rotation';
    console.log('🔄 Detection: ROTATION (Roll ±90°)');
} else if (therapyType.toLowerCase().includes('horizontal') || 
           therapyType.toLowerCase().includes('kanan') && therapyType.toLowerCase().includes('kiri')) {
    detectionType = 'horizontal';
    console.log('↔️ Detection: HORIZONTAL (Gyro Z + Accel Y)');
} else if (therapyType.toLowerCase().includes('vertikal') || 
           therapyType.toLowerCase().includes('angkat') || 
           therapyType.toLowerCase().includes('lengan')) {
    detectionType = 'vertical';
    console.log('⬆️ Detection: VERTICAL (Pitch -90°)');
}

// ==========================================
// MOVEMENT DETECTION LOGIC
// ==========================================

let detectedMovements = [];
let movementIndex = 1;

if (detectionType === 'vertical') {
    // ========== VERTICAL DETECTION (Pitch -90°) ==========
    let previousPitch = null;
    const pitchThreshold = 7;
    const targetPitch = -90;
    
    movementsData.forEach((movement, index) => {
        const currentPitch = parseFloat(movement.pitch);
        
        if (previousPitch !== null) {
            const isAt90 = Math.abs(currentPitch - targetPitch) <= pitchThreshold;
            const wasNotAt90 = Math.abs(previousPitch - targetPitch) > pitchThreshold;
            
            if (isAt90 && wasNotAt90) {
                detectedMovements.push({
                    index: movementIndex++,
                    timestamp: movement.timestamp,
                    roll: parseFloat(movement.roll),
                    pitch: currentPitch,
                    axG: parseFloat(movement.axG),
                    ayG: parseFloat(movement.ayG),
                    azG: parseFloat(movement.azG),
                    gx: parseFloat(movement.gx),
                    gy: parseFloat(movement.gy),
                    gz: parseFloat(movement.gz),
                    type: 'vertical'
                });
            }
        }
        previousPitch = currentPitch;
    });
    
} else if (detectionType === 'rotation') {
    // ========== ROTATION DETECTION (Roll ±90°) ==========
    let previousRoll = null;
    let rotationPhase = 'waiting'; // 'waiting', 'at_right', 'at_left'
    const rollThreshold = 7;
    const targetRollRight = -90;
    const targetRollLeft = 90;
    
    movementsData.forEach((movement, index) => {
        const currentRoll = parseFloat(movement.roll);
        
        if (previousRoll !== null) {
            const deviationFromRight = Math.abs(currentRoll - targetRollRight);
            const deviationFromLeft = Math.abs(currentRoll - targetRollLeft);
            const isAtRight = deviationFromRight <= rollThreshold;
            const isAtLeft = deviationFromLeft <= rollThreshold;
            const isAtCenter = Math.abs(currentRoll) < 30;
            
            switch(rotationPhase) {
                case 'waiting':
                    if (isAtRight) {
                        rotationPhase = 'at_right';
                    } else if (isAtLeft) {
                        rotationPhase = 'at_left';
                    }
                    break;
                    
                case 'at_right':
                    if (isAtLeft) {
                        // Gerakan KANAN → KIRI terdeteksi
                        detectedMovements.push({
                            index: movementIndex++,
                            timestamp: movement.timestamp,
                            roll: currentRoll,
                            pitch: parseFloat(movement.pitch),
                            axG: parseFloat(movement.axG),
                            ayG: parseFloat(movement.ayG),
                            azG: parseFloat(movement.azG),
                            gx: parseFloat(movement.gx),
                            gy: parseFloat(movement.gy),
                            gz: parseFloat(movement.gz),
                            type: 'rotation',
                            direction: 'right_to_left'
                        });
                        rotationPhase = 'at_left';
                    } else if (isAtCenter) {
                        rotationPhase = 'waiting';
                    }
                    break;
                    
                case 'at_left':
                    if (isAtRight) {
                        // Gerakan KIRI → KANAN terdeteksi
                        detectedMovements.push({
                            index: movementIndex++,
                            timestamp: movement.timestamp,
                            roll: currentRoll,
                            pitch: parseFloat(movement.pitch),
                            axG: parseFloat(movement.axG),
                            ayG: parseFloat(movement.ayG),
                            azG: parseFloat(movement.azG),
                            gx: parseFloat(movement.gx),
                            gy: parseFloat(movement.gy),
                            gz: parseFloat(movement.gz),
                            type: 'rotation',
                            direction: 'left_to_right'
                        });
                        rotationPhase = 'at_right';
                    } else if (isAtCenter) {
                        rotationPhase = 'waiting';
                    }
                    break;
            }
        }
        previousRoll = currentRoll;
    });
    
} else if (detectionType === 'horizontal') {
    // ========== HORIZONTAL DETECTION (Gyro Z + Accel Y) ==========
    let previousGyroZ = null;
    let previousAccelY = null;
    let horizontalPhase = 'waiting'; // 'waiting', 'moving_right', 'moving_left'
    const gyroZThreshold = 50;
    const accelYThreshold = 0.3;
    
    movementsData.forEach((movement, index) => {
        const currentGyroZ = parseFloat(movement.gz);
        const currentAccelY = parseFloat(movement.ayG);
        
        if (previousGyroZ !== null && previousAccelY !== null) {
            const gyroZAbs = Math.abs(currentGyroZ);
            const isMoving = gyroZAbs > gyroZThreshold;
            
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
                    } else if (currentDirection === 'left') {
                        horizontalPhase = 'moving_left';
                    }
                    break;
                    
                case 'moving_right':
                    if (currentDirection === 'left') {
                        // Gerakan KANAN → KIRI terdeteksi
                        detectedMovements.push({
                            index: movementIndex++,
                            timestamp: movement.timestamp,
                            roll: parseFloat(movement.roll),
                            pitch: parseFloat(movement.pitch),
                            axG: parseFloat(movement.axG),
                            ayG: currentAccelY,
                            azG: parseFloat(movement.azG),
                            gx: parseFloat(movement.gx),
                            gy: parseFloat(movement.gy),
                            gz: currentGyroZ,
                            type: 'horizontal',
                            direction: 'right_to_left'
                        });
                        horizontalPhase = 'moving_left';
                    } else if (currentDirection === 'center') {
                        horizontalPhase = 'waiting';
                    }
                    break;
                    
                case 'moving_left':
                    if (currentDirection === 'right') {
                        // Gerakan KIRI → KANAN terdeteksi
                        detectedMovements.push({
                            index: movementIndex++,
                            timestamp: movement.timestamp,
                            roll: parseFloat(movement.roll),
                            pitch: parseFloat(movement.pitch),
                            axG: parseFloat(movement.axG),
                            ayG: currentAccelY,
                            azG: parseFloat(movement.azG),
                            gx: parseFloat(movement.gx),
                            gy: parseFloat(movement.gy),
                            gz: currentGyroZ,
                            type: 'horizontal',
                            direction: 'left_to_right'
                        });
                        horizontalPhase = 'moving_right';
                    } else if (currentDirection === 'center') {
                        horizontalPhase = 'waiting';
                    }
                    break;
            }
        }
        previousGyroZ = currentGyroZ;
        previousAccelY = currentAccelY;
    });
}

console.log('Detected movements:', detectedMovements.length);
document.getElementById('totalDetectedMovements').textContent = detectedMovements.length;

// ==========================================
// PREPARE CHART DATA
// ==========================================

const labels = detectedMovements.map(m => {
    const time = new Date(m.timestamp);
    return `Gerakan ${m.index}\n${time.toLocaleTimeString('id-ID')}`;
});

let dataset1Data, dataset2Data, dataset1Label, dataset2Label;
let yAxisConfig = { beginAtZero: false };

if (detectionType === 'vertical') {
    // Chart untuk Vertical: Roll & Pitch
    dataset1Label = 'Roll (°)';
    dataset2Label = 'Pitch (°)';
    dataset1Data = detectedMovements.map(m => m.roll);
    dataset2Data = detectedMovements.map(m => m.pitch);
    yAxisConfig = { beginAtZero: false, min: -100, max: 20 };
    
} else if (detectionType === 'rotation') {
    // Chart untuk Rotation: Roll & Pitch
    dataset1Label = 'Roll (°)';
    dataset2Label = 'Pitch (°)';
    dataset1Data = detectedMovements.map(m => m.roll);
    dataset2Data = detectedMovements.map(m => m.pitch);
    yAxisConfig = { beginAtZero: false, min: -100, max: 100 };
    
} else if (detectionType === 'horizontal') {
    // Chart untuk Horizontal: Gyro Z & Accel Y
    dataset1Label = 'Gyro Z (°/s)';
    dataset2Label = 'Accel Y (G)';
    dataset1Data = detectedMovements.map(m => m.gz);
    dataset2Data = detectedMovements.map(m => m.ayG);
    yAxisConfig = { beginAtZero: true };
}

// ==========================================
// CREATE CHART
// ==========================================

const ctx = document.getElementById('movementChart').getContext('2d');
const movementChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: dataset1Label,
                data: dataset1Data,
                borderColor: '#f39c12',
                backgroundColor: 'rgba(243, 156, 18, 0.1)',
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#f39c12',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                fill: true
            },
            {
                label: dataset2Label,
                data: dataset2Data,
                borderColor: '#9b59b6',
                backgroundColor: 'rgba(155, 89, 182, 0.1)',
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#9b59b6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: { size: 14, weight: 'bold' }
                }
            },
            tooltip: {
                callbacks: {
                    title: function(context) {
                        return context[0].label.replace('\n', ' - ');
                    },
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toFixed(2);
                    },
                    afterLabel: function(context) {
                        const movement = detectedMovements[context.dataIndex];
                        let extraInfo = [
                            '─────────────────'
                        ];
                        
                        if (detectionType === 'vertical') {
                            extraInfo.push(
                                'Roll: ' + movement.roll.toFixed(2) + '°',
                                'Pitch: ' + movement.pitch.toFixed(2) + '°',
                                'Accel X: ' + movement.axG.toFixed(3) + ' G',
                                'Accel Y: ' + movement.ayG.toFixed(3) + ' G',
                                'Accel Z: ' + movement.azG.toFixed(3) + ' G',
                                '─────────────────',
                                'Target: -90° (±7°)',
                                'Range: -83° s/d -97°'
                            );
                        } else if (detectionType === 'rotation') {
                            extraInfo.push(
                                'Roll: ' + movement.roll.toFixed(2) + '°',
                                'Pitch: ' + movement.pitch.toFixed(2) + '°',
                                'Direction: ' + (movement.direction === 'right_to_left' ? 'Kanan → Kiri' : 'Kiri → Kanan'),
                                '─────────────────',
                                'Kanan: -90° (±7°)',
                                'Kiri: +90° (±7°)'
                            );
                        } else if (detectionType === 'horizontal') {
                            extraInfo.push(
                                'Gyro Z: ' + movement.gz.toFixed(2) + '°/s',
                                'Accel Y: ' + movement.ayG.toFixed(3) + ' G',
                                'Direction: ' + (movement.direction === 'right_to_left' ? 'Kanan → Kiri' : 'Kiri → Kanan'),
                                '─────────────────',
                                'Gyro Z Threshold: ±50°/s',
                                'Accel Y Threshold: ±0.3G'
                            );
                        }
                        
                        return extraInfo;
                    }
                },
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 12 },
                padding: 12
            }
        },
        scales: {
            y: yAxisConfig,
            x: {
                title: {
                    display: true,
                    text: 'Urutan Gerakan',
                    font: { size: 14, weight: 'bold' }
                },
                ticks: {
                    font: { size: 10 },
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        }
    }
});

// Update alert info berdasarkan deteksi
const alertDiv = document.querySelector('.alert-info');
if (alertDiv) {
    let detectionInfo = '';
    
    if (detectionType === 'vertical') {
        detectionInfo = `
            ℹ️ <strong>Keterangan:</strong> Grafik ini menampilkan data Roll & Pitch hanya pada saat gerakan terdeteksi 
            (ketika Pitch mencapai <strong>-90° ± 7°</strong>, range: <strong>-83° sampai -97°</strong>). 
            <br><br>
            Setiap titik pada grafik merepresentasikan 1 gerakan yang berhasil tercatat ketika lengan diangkat ke posisi vertikal.
            <br><br>
            <strong>🎯 Target:</strong> Pitch = -90° (lengan vertikal)<br>
            <strong>📏 Range Valid:</strong> -83° sampai -97° (toleransi ±7°)<br>
            <strong>✅ Gerakan Terdeteksi:</strong> Saat pitch masuk ke range valid setelah berada di luar range
        `;
    } else if (detectionType === 'rotation') {
        detectionInfo = `
            ℹ️ <strong>Keterangan:</strong> Grafik ini menampilkan data Roll & Pitch hanya pada saat gerakan rotasi terdeteksi 
            (ketika Roll mencapai <strong>-90° (kanan)</strong> atau <strong>+90° (kiri)</strong> dengan toleransi ±7°). 
            <br><br>
            Setiap titik pada grafik merepresentasikan 1 gerakan rotasi penuh (dari kanan ke kiri atau kiri ke kanan).
            <br><br>
            <strong>🎯 Target Kanan:</strong> Roll = -90° (range: -83° s/d -97°)<br>
            <strong>🎯 Target Kiri:</strong> Roll = +90° (range: 83° s/d 97°)<br>
            <strong>✅ Gerakan Terdeteksi:</strong> Saat roll berpindah dari satu posisi ekstrem ke posisi ekstrem lainnya
        `;
    } else if (detectionType === 'horizontal') {
        detectionInfo = `
            ℹ️ <strong>Keterangan:</strong> Grafik ini menampilkan data Gyroscope Z dan Accelerometer Y pada saat gerakan horizontal terdeteksi 
            (menggunakan threshold <strong>Gyro Z > 50°/s</strong> dan <strong>Accel Y > 0.3G</strong>). 
            <br><br>
            Setiap titik pada grafik merepresentasikan 1 gerakan horizontal penuh (dari kanan ke kiri atau kiri ke kanan).
            <br><br>
            <strong>🎯 Gyro Z Threshold:</strong> ±50°/s (kecepatan rotasi)<br>
            <strong>🎯 Accel Y Threshold:</strong> ±0.3G (arah gerakan)<br>
            <strong>✅ Gerakan Terdeteksi:</strong> Saat tangan bergerak horizontal dengan kecepatan dan arah yang signifikan
        `;
    }
    
    alertDiv.innerHTML = detectionInfo;
}

// ==========================================
// EXPORT PDF FUNCTION
// ==========================================

async function exportMovementChartPDF() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('l', 'mm', 'a4');
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    
    // Title
    pdf.setFontSize(18);
    pdf.text('Laporan Grafik Gerakan Terdeteksi', pageWidth / 2, 15, { align: 'center' });
    
    // Session Info
    pdf.setFontSize(12);
    pdf.text('<?= $session['nama'] ?>', pageWidth / 2, 25, { align: 'center' });
    pdf.text('<?= $session['therapy_type'] ?>', pageWidth / 2, 32, { align: 'center' });
    pdf.text('Tanggal: <?= date('d/m/Y H:i', strtotime($session['start_time'])) ?>', pageWidth / 2, 39, { align: 'center' });
    
    // Detection Type Info
    let detectionTypeText = detectionType === 'vertical' ? 'VERTICAL (Pitch -90°)' :
                           detectionType === 'rotation' ? 'ROTATION (Roll ±90°)' :
                           'HORIZONTAL (Gyro Z + Accel Y)';
    pdf.text('Jenis Deteksi: ' + detectionTypeText, pageWidth / 2, 46, { align: 'center' });
    
    // Chart
    const canvas = document.getElementById('movementChart');
    const imgData = canvas.toDataURL('image/png', 1.0);
    
    const imgWidth = pageWidth - 30;
    const imgHeight = (canvas.height * imgWidth) / canvas.width;
    
    pdf.addImage(imgData, 'PNG', 15, 52, imgWidth, imgHeight);
    
    // Summary at bottom
    const yPosition = 52 + imgHeight + 10;
    pdf.setFontSize(11);
    pdf.text('Total Gerakan Terdeteksi: ' + detectedMovements.length + ' gerakan', 15, yPosition);
    pdf.text('Durasi Terapi: <?= gmdate("i:s", $session['duration']) ?> menit', 15, yPosition + 7);
    pdf.text('Total Data Points: <?= $stats['total_data'] ?>', 15, yPosition + 14);
    
    // Add data table on new page if there are movements
    if (detectedMovements.length > 0) {
        pdf.addPage();
        pdf.setFontSize(14);
        pdf.text('Data Detail Gerakan Terdeteksi', pageWidth / 2, 15, { align: 'center' });
        
        // Table headers
        pdf.setFontSize(10);
        let yPos = 25;
        const colWidths = [15, 50, 30, 30, 30, 30, 30];
        let headers = [];
        
        if (detectionType === 'vertical' || detectionType === 'rotation') {
            headers = ['No', 'Waktu', 'Roll (°)', 'Pitch (°)', 'Accel X', 'Accel Y', 'Accel Z'];
        } else if (detectionType === 'horizontal') {
            headers = ['No', 'Waktu', 'Gyro Z (°/s)', 'Accel Y (G)', 'Roll (°)', 'Pitch (°)', 'Direction'];
        }
        
        let xPos = 15;
        headers.forEach((header, i) => {
            pdf.text(header, xPos, yPos);
            xPos += colWidths[i];
        });
        
        // Table data
        yPos += 7;
        detectedMovements.forEach((movement, index) => {
            if (yPos > pageHeight - 20) {
                pdf.addPage();
                yPos = 20;
            }
            
            xPos = 15;
            const time = new Date(movement.timestamp);
            let row = [];
            
            if (detectionType === 'vertical' || detectionType === 'rotation') {
                row = [
                    movement.index,
                    time.toLocaleTimeString('id-ID'),
                    movement.roll.toFixed(2),
                    movement.pitch.toFixed(2),
                    movement.axG.toFixed(3),
                    movement.ayG.toFixed(3),
                    movement.azG.toFixed(3)
                ];
            } else if (detectionType === 'horizontal') {
                row = [
                    movement.index,
                    time.toLocaleTimeString('id-ID'),
                    movement.gz.toFixed(2),
                    movement.ayG.toFixed(3),
                    movement.roll.toFixed(2),
                    movement.pitch.toFixed(2),
                    movement.direction === 'right_to_left' ? 'R→L' : 'L→R'
                ];
            }
            
            row.forEach((cell, i) => {
                pdf.text(String(cell), xPos, yPos);
                xPos += colWidths[i];
            });
            
            yPos += 7;
        });
    }
    
    // Footer
    const pageCount = pdf.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        pdf.setPage(i);
        pdf.setFontSize(9);
        pdf.text(
            'Export Date: ' + new Date().toLocaleString('id-ID'),
            pageWidth - 15,
            pageHeight - 10,
            { align: 'right' }
        );
        pdf.text(
            'Page ' + i + ' of ' + pageCount,
            15,
            pageHeight - 10
        );
    }
    
    // Save PDF
    const filename = 'gerakan_terdeteksi_<?= str_replace(' ', '_', $session['nama']) ?>_<?= date('Ymd', strtotime($session['start_time'])) ?>.pdf';
    pdf.save(filename);
    
    alert('✅ PDF berhasil di-download!');
}

console.log('=== DETAIL TERAPI SCRIPT LOADED ===');
console.log('Detection Type:', detectionType);
console.log('Detected Movements:', detectedMovements.length);
    </script>
</body>
</html>