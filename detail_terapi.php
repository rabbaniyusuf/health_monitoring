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
                    $movements_result->data_seek(0);
                    while($movement = $movements_result->fetch_assoc()): 
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
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>