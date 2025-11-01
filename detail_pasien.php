<?php
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

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_readings,
    AVG(roll) as avg_roll,
    MIN(roll) as min_roll,
    MAX(roll) as max_roll,
    AVG(pitch) as avg_pitch,
    MIN(pitch) as min_pitch,
    MAX(pitch) as max_pitch,
    MIN(timestamp) as first_reading,
    MAX(timestamp) as last_reading
    FROM sensor_data WHERE patient_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();

// Get all videos for this patient
$videos_sql = "SELECT * FROM patient_videos WHERE patient_id = ? ORDER BY recorded_at DESC";
$stmt = $conn->prepare($videos_sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$videos_result = $stmt->get_result();
$stmt->close();

// Get all sensor data
$limit = $_GET['limit'] ?? 50;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

$data_sql = "SELECT * FROM sensor_data WHERE patient_id = ? ORDER BY timestamp DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($data_sql);
$stmt->bind_param("iii", $patient_id, $limit, $offset);
$stmt->execute();
$data_result = $stmt->get_result();
$stmt->close();

// Count total pages
$count_sql = "SELECT COUNT(*) as total FROM sensor_data WHERE patient_id = ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pasien - <?= $patient['nama'] ?></title>
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
            font-size: 24px;
        }
        .patient-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
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
            color: #333;
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
            font-size: 13px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 11px;
            text-transform: uppercase;
            position: sticky;
            top: 0;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .btn {
            padding: 10px 20px;
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
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            background: white;
            border: 1px solid #ddd;
        }
        .pagination .active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        .export-btn {
            background: #28a745;
            color: white;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            table {
                font-size: 11px;
            }
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="patient-info">
                <div>
                    <h2>📋 Riwayat Data Pasien</h2>
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
                            <span class="badge badge-warning"><?= $patient['kategori_sakit'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="actions">
                    <a href="monitoring.php?patient_id=<?= $patient_id ?>" class="btn btn-success">📊 Live Monitor</a>
                    <a href="index.php" class="btn btn-primary">← Kembali</a>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">📊 Total Pembacaan</div>
                <div class="stat-value"><?= number_format($stats['total_readings']) ?></div>
                <div class="stat-subtitle">data tersimpan</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">🔄 Roll (Rata-rata)</div>
                <div class="stat-value"><?= $stats['avg_roll'] ? round($stats['avg_roll'], 1) : '--' ?>°</div>
                <div class="stat-subtitle">
                    Min: <?= $stats['min_roll'] ? round($stats['min_roll'], 1) : '--' ?>° | Max: <?= $stats['max_roll'] ? round($stats['max_roll'], 1) : '--' ?>°
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">↕️ Pitch (Rata-rata)</div>
                <div class="stat-value"><?= $stats['avg_pitch'] ? round($stats['avg_pitch'], 1) : '--' ?>°</div>
                <div class="stat-subtitle">
                    Min: <?= $stats['min_pitch'] ? round($stats['min_pitch'], 1) : '--' ?>° | Max: <?= $stats['max_pitch'] ? round($stats['max_pitch'], 1) : '--' ?>°
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">📅 Periode Data</div>
                <div class="stat-value" style="font-size: 14px;">
                    <?= $stats['first_reading'] ? date('d/m/Y', strtotime($stats['first_reading'])) : '--' ?>
                </div>
                <div class="stat-subtitle">
                    s/d <?= $stats['last_reading'] ? date('d/m/Y', strtotime($stats['last_reading'])) : '--' ?>
                </div>
            </div>
        </div>

        <?php if ($videos_result->num_rows > 0): ?>
        <div class="video-section" style="background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 20px; color: #333;">📹 Video Rekaman (<?= $videos_result->num_rows ?> video)</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                <?php while($video = $videos_result->fetch_assoc()): ?>
                    <div style="border: 1px solid #ddd; border-radius: 10px; overflow: hidden; background: #f8f9fa;">
                        <video controls style="width: 100%; height: 200px; background: #000;">
                            <source src="<?= $video['video_path'] ?>" type="video/webm">
                            Browser tidak support video.
                        </video>
                        <div style="padding: 15px;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">
                                📅 <?= date('d/m/Y H:i:s', strtotime($video['recorded_at'])) ?>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">
                                ⏱️ Durasi: <?= gmdate("i:s", $video['duration']) ?> menit
                            </div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 12px;">
                                📦 Ukuran: <?= number_format($video['file_size'] / (1024*1024), 2) ?> MB
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <a href="<?= $video['video_path'] ?>" download class="btn btn-sm" style="background: #28a745; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 12px;">
                                    💾 Download
                                </a>
                                <a href="api/delete_video.php?id=<?= $video['id'] ?>&patient_id=<?= $patient_id ?>" 
                                   onclick="return confirm('Yakin ingin menghapus video ini?')" 
                                   class="btn btn-sm" 
                                   style="background: #dc3545; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 12px;">
                                    🗑️ Hapus
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="data-table">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>📊 Data Sensor Lengkap</h3>
                <a href="api/export_csv.php?patient_id=<?= $patient_id ?>" class="btn export-btn btn-sm">
                    📥 Export CSV
                </a>
            </div>
            
            <?php if ($data_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Timestamp</th>
                            <th>Roll</th>
                            <th>Pitch</th>
                            <th>AX (G)</th>
                            <th>AY (G)</th>
                            <th>AZ (G)</th>
                            <th>GX</th>
                            <th>GY</th>
                            <th>GZ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        while($row = $data_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($row['timestamp'])) ?></td>
                                <td><strong><?= number_format($row['roll'], 1) ?>°</strong></td>
                                <td><strong><?= number_format($row['pitch'], 1) ?>°</strong></td>
                                <td><?= number_format($row['axG'], 2) ?></td>
                                <td><?= number_format($row['ayG'], 2) ?></td>
                                <td><?= number_format($row['azG'], 2) ?></td>
                                <td><?= number_format($row['gx'], 2) ?></td>
                                <td><?= number_format($row['gy'], 2) ?></td>
                                <td><?= number_format($row['gz'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?patient_id=<?= $patient_id ?>&page=<?= $page - 1 ?>&limit=<?= $limit ?>">« Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= min($total_pages, 5); $i++): ?>
                            <a href="?patient_id=<?= $patient_id ?>&page=<?= $i ?>&limit=<?= $limit ?>" 
                               class="<?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?patient_id=<?= $patient_id ?>&page=<?= $page + 1 ?>&limit=<?= $limit ?>">Next »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #999;">
                    Belum ada data sensor untuk pasien ini.
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>