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

// Hapus patient aktif jika ada parameter
if (isset($_GET['stop'])) {
    unset($_SESSION['active_patient_id']);
    header('Location: index.php');
    exit;
}

$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM sensor_data WHERE patient_id = p.id) as total_readings,
        (SELECT timestamp FROM sensor_data WHERE patient_id = p.id ORDER BY timestamp DESC LIMIT 1) as last_reading
        FROM patients p ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Monitoring System</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
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
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        .btn-sm {
            padding: 8px 15px;
            font-size: 12px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .badge-primary { background: #d1ecf1; color: #0c5460; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .container { padding: 15px; }
            table { font-size: 12px; }
            th, td { padding: 10px; }
            .btn { padding: 10px 15px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Health Monitoring System</h1>
        <p class="subtitle">Sistem Monitoring Kesehatan Real-time dengan MPU6050 & MAX30102</p>
        
        <div class="header-actions">
            <a href="input_pasien.php" class="btn btn-primary">➕ Tambah Pasien Baru</a>
            <?php if (isset($_SESSION['active_patient_id'])): ?>
                <a href="?stop=1" class="btn btn-danger">⏹ Stop Monitoring</a>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['active_patient_id'])): 
            $active_id = $_SESSION['active_patient_id'];
            $active_sql = "SELECT * FROM patients WHERE id = $active_id";
            $active_result = $conn->query($active_sql);
            $active_patient = $active_result->fetch_assoc();
        ?>
            <div class="alert alert-info">
                🟢 <strong>Monitoring Aktif:</strong> <?= $active_patient['nama'] ?> 
                (<?= $active_patient['jenis_kelamin'] ?>, <?= $active_patient['usia'] ?> tahun)
            </div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>JK</th>
                        <th>Usia</th>
                        <th>Status</th>
                        <th>Kategori</th>
                        <th>Total Data</th>
                        <th>Terakhir Update</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><strong><?= $row['nama'] ?></strong></td>
                            <td><?= $row['jenis_kelamin'] == 'Laki-laki' ? '♂' : '♀' ?></td>
                            <td><?= $row['usia'] ?> th</td>
                            <td>
                                <span class="badge <?= $row['status_kesehatan'] == 'Sehat' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $row['status_kesehatan'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status_kesehatan'] == 'Sakit'): ?>
                                    <span class="badge <?= 
                                        $row['kategori_sakit'] == 'Ringan' ? 'badge-warning' : 
                                        ($row['kategori_sakit'] == 'Sedang' ? 'badge-secondary' : 'badge-danger') 
                                    ?>">
                                        <?= $row['kategori_sakit'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['total_readings'] ?> data</td>
                            <td>
                                <?= $row['last_reading'] ? date('d/m/Y H:i', strtotime($row['last_reading'])) : '-' ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="monitoring.php?patient_id=<?= $row['id'] ?>" class="btn btn-success btn-sm">
                                        📊 Monitor
                                    </a>
                                    <a href="detail_pasien.php?patient_id=<?= $row['id'] ?>" class="btn btn-info btn-sm">
                                        📋 Detail
                                    </a>
                                    <a href="terapi.php?patient_id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                                        🏋️ Terapi
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 64px; margin-bottom: 20px;">📋</div>
                <h3>Belum Ada Data Pasien</h3>
                <p>Klik tombol "Tambah Pasien Baru" untuk memulai monitoring</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>