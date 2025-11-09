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

// Get all completed therapy sessions
$sessions_sql = "SELECT * FROM therapy_sessions 
                 WHERE patient_id = ? AND completion_status = 'completed' 
                 ORDER BY start_time DESC";
$stmt = $conn->prepare($sessions_sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$sessions_result = $stmt->get_result();
$stmt->close();

// Get statistics for each session
$sessions_data = [];
while ($session = $sessions_result->fetch_assoc()) {
    $stats_sql = "SELECT 
        AVG(roll) as avg_roll,
        MAX(roll) as max_roll,
        MIN(roll) as min_roll,
        AVG(pitch) as avg_pitch,
        MAX(pitch) as max_pitch,
        MIN(pitch) as min_pitch,
        AVG(axG) as avg_ax,
        AVG(ayG) as avg_ay,
        AVG(azG) as avg_az,
        AVG(gx) as avg_gx,
        AVG(gy) as avg_gy,
        AVG(gz) as avg_gz,
        COUNT(*) as data_count
        FROM therapy_movements WHERE session_id = ?";
    
    $stmt = $conn->prepare($stats_sql);
    $stmt->bind_param("i", $session['id']);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stmt->close();
    
    $sessions_data[] = array_merge($session, $stats);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Perbandingan Terapi - <?= $patient['nama'] ?></title>
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
        
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .stats-overview {
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
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-subtitle {
            font-size: 12px;
            color: #666;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-export-chart {
            padding: 6px 12px;
            font-size: 12px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-export-chart:hover {
            background: #138496;
            transform: translateY(-1px);
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .session-selector {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .session-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .session-chip {
            padding: 10px 20px;
            border: 2px solid #667eea;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
            font-weight: 600;
            color: #667eea;
            background: white;
        }
        
        .session-chip:hover {
            background: #667eea;
            color: white;
        }
        
        .session-chip.active {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 1200px) {
            .charts-grid {
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
                    <h2>📈 Grafik Perbandingan Terapi</h2>
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
                <div class="export-buttons">
                    <button onclick="exportAllChartsAsPDF()" class="btn btn-success">
                        📊 Export Semua Grafik (PDF)
                    </button>
                    <button onclick="exportAllChartsAsImages()" class="btn btn-warning">
                        🖼️ Export Semua Grafik (PNG)
                    </button>
                    <a href="terapi.php?patient_id=<?= $patient_id ?>" class="btn">← Kembali ke Terapi</a>
                </div>
            </div>
        </div>

        <?php if (count($sessions_data) == 0): ?>
            <div class="alert">
                ℹ️ <strong>Belum ada data terapi yang selesai.</strong> Lakukan sesi terapi terlebih dahulu untuk melihat grafik perbandingan.
            </div>
        <?php else: ?>
            
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-title">📊 Total Sesi</div>
                    <div class="stat-value"><?= count($sessions_data) ?></div>
                    <div class="stat-subtitle">Terapi selesai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">⏱️ Total Durasi</div>
                    <div class="stat-value">
                        <?php
                        $total_duration = array_sum(array_column($sessions_data, 'duration'));
                        echo gmdate("H:i", $total_duration);
                        ?>
                    </div>
                    <div class="stat-subtitle">jam:menit</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">🔄 Total Gerakan</div>
                    <div class="stat-value">
                        <?= number_format(array_sum(array_column($sessions_data, 'total_movements'))) ?>
                    </div>
                    <div class="stat-subtitle">gerakan terekam</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">📅 Periode</div>
                    <div class="stat-value" style="font-size: 16px;">
                        <?= date('d/m/Y', strtotime(end($sessions_data)['start_time'])) ?>
                    </div>
                    <div class="stat-subtitle">
                        s/d <?= date('d/m/Y', strtotime($sessions_data[0]['start_time'])) ?>
                    </div>
                </div>
            </div>

            <div class="session-selector">
                <h3 style="margin-bottom: 10px; color: #333;">🔍 Filter Sesi Terapi</h3>
                <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Klik untuk memilih/batalkan sesi yang ingin ditampilkan di grafik</p>
                <div class="session-list">
                    <button class="session-chip active" onclick="toggleAllSessions(this)">
                        ✓ Semua Sesi
                    </button>
                    <?php foreach ($sessions_data as $index => $session): ?>
                        <button class="session-chip active" data-index="<?= $index ?>" onclick="toggleSession(this, <?= $index ?>)">
                            Sesi <?= count($sessions_data) - $index ?> - <?= date('d/m H:i', strtotime($session['start_time'])) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <div class="chart-title">📊 Perbandingan Roll & Pitch</div>
                    <canvas id="rollPitchChart" width="400" height="300"></canvas>
                </div>

                <div class="chart-container">
                    <div class="chart-title">⏱️ Durasi & Total Gerakan</div>
                    <canvas id="durationChart" width="400" height="300"></canvas>
                </div>

                <div class="chart-container">
                    <div class="chart-title">📈 Perbandingan Accelerometer (Rata-rata)</div>
                    <canvas id="accelChart" width="400" height="300"></canvas>
                </div>

                <div class="chart-container">
                    <div class="chart-title">🔄 Perbandingan Gyroscope (Rata-rata)</div>
                    <canvas id="gyroChart" width="400" height="300"></canvas>
                </div>

                <div class="chart-container full-width">
                    <div class="chart-title">📊 Tren Perkembangan Terapi</div>
                    <canvas id="trendChart" width="400" height="200"></canvas>
                </div>

                <div class="chart-container full-width">
                    <div class="chart-title">📉 Range Roll & Pitch per Sesi</div>
                    <canvas id="rangeChart" width="400" height="200"></canvas>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        const sessionsData = <?= json_encode(array_reverse($sessions_data)) ?>;
        let activeSessionIndices = sessionsData.map((_, i) => i);
        let charts = {};

        console.log('Sessions data:', sessionsData);

        function getFilteredData() {
            return sessionsData.filter((_, index) => activeSessionIndices.includes(index));
        }

        function getLabels(data) {
            return data.map((session, index) => {
                const date = new Date(session.start_time);
                return `Sesi ${index + 1}\n${date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })}`;
            });
        }

        function initCharts() {
            const filteredData = getFilteredData();
            const labels = getLabels(filteredData);

            // Roll & Pitch Chart
            const ctx1 = document.getElementById('rollPitchChart').getContext('2d');
            charts.rollPitch = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Roll (Avg)',
                            data: filteredData.map(s => parseFloat(s.avg_roll).toFixed(2)),
                            backgroundColor: 'rgba(243, 156, 18, 0.7)',
                            borderColor: '#f39c12',
                            borderWidth: 2
                        },
                        {
                            label: 'Pitch (Avg)',
                            data: filteredData.map(s => parseFloat(s.avg_pitch).toFixed(2)),
                            backgroundColor: 'rgba(155, 89, 182, 0.7)',
                            borderColor: '#9b59b6',
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
                    }
                }
            });

            // Duration & Movements Chart
            const ctx2 = document.getElementById('durationChart').getContext('2d');
            charts.duration = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Durasi (menit)',
                            data: filteredData.map(s => (s.duration / 60).toFixed(1)),
                            backgroundColor: 'rgba(52, 152, 219, 0.7)',
                            borderColor: '#3498db',
                            borderWidth: 2,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Total Gerakan',
                            data: filteredData.map(s => s.total_movements),
                            backgroundColor: 'rgba(46, 204, 113, 0.7)',
                            borderColor: '#2ecc71',
                            borderWidth: 2,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Durasi (menit)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Total Gerakan'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });

            // Accelerometer Chart
            const ctx3 = document.getElementById('accelChart').getContext('2d');
            charts.accel = new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Accel X (G)',
                            data: filteredData.map(s => parseFloat(s.avg_ax).toFixed(3)),
                            borderColor: '#e67e22',
                            backgroundColor: 'rgba(230, 126, 34, 0.1)',
                            tension: 0.4,
                            borderWidth: 2
                        },
                        {
                            label: 'Accel Y (G)',
                            data: filteredData.map(s => parseFloat(s.avg_ay).toFixed(3)),
                            borderColor: '#1abc9c',
                            backgroundColor: 'rgba(26, 188, 156, 0.1)',
                            tension: 0.4,
                            borderWidth: 2
                        },
                        {
                            label: 'Accel Z (G)',
                            data: filteredData.map(s => parseFloat(s.avg_az).toFixed(3)),
                            borderColor: '#34495e',
                            backgroundColor: 'rgba(52, 73, 94, 0.1)',
                            tension: 0.4,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true
                }
            });

            // Gyroscope Chart
            const ctx4 = document.getElementById('gyroChart').getContext('2d');
            charts.gyro = new Chart(ctx4, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Gyro X (°/s)',
                            data: filteredData.map(s => parseFloat(s.avg_gx).toFixed(2)),
                            borderColor: '#c0392b',
                            backgroundColor: 'rgba(192, 57, 43, 0.1)',
                            tension: 0.4,
                            borderWidth: 2
                        },
                        {
                            label: 'Gyro Y (°/s)',
                            data: filteredData.map(s => parseFloat(s.avg_gy).toFixed(2)),
                            borderColor: '#16a085',
                            backgroundColor: 'rgba(22, 160, 133, 0.1)',
                            tension: 0.4,
                            borderWidth: 2
                        },
                        {
                            label: 'Gyro Z (°/s)',
                            data: filteredData.map(s => parseFloat(s.avg_gz).toFixed(2)),
                            borderColor: '#2c3e50',
                            backgroundColor: 'rgba(44, 62, 80, 0.1)',
                            tension: 0.4,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true
                }
            });

            // Trend Chart
            const ctx5 = document.getElementById('trendChart').getContext('2d');
            charts.trend = new Chart(ctx5, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Roll (Avg)',
                            data: filteredData.map(s => parseFloat(s.avg_roll).toFixed(2)),
                            borderColor: '#f39c12',
                            backgroundColor: 'rgba(243, 156, 18, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            fill: true
                        },
                        {
                            label: 'Pitch (Avg)',
                            data: filteredData.map(s => parseFloat(s.avg_pitch).toFixed(2)),
                            borderColor: '#9b59b6',
                            backgroundColor: 'rgba(155, 89, 182, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Tren pergerakan Roll & Pitch dari waktu ke waktu'
                        }
                    }
                }
            });

            // Range Chart
            const ctx6 = document.getElementById('rangeChart').getContext('2d');
            charts.range = new Chart(ctx6, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Roll Range (Max - Min)',
                            data: filteredData.map(s => (parseFloat(s.max_roll) - parseFloat(s.min_roll)).toFixed(2)),
                            backgroundColor: 'rgba(243, 156, 18, 0.7)',
                            borderColor: '#f39c12',
                            borderWidth: 2
                        },
                        {
                            label: 'Pitch Range (Max - Min)',
                            data: filteredData.map(s => (parseFloat(s.max_pitch) - parseFloat(s.min_pitch)).toFixed(2)),
                            backgroundColor: 'rgba(155, 89, 182, 0.7)',
                            borderColor: '#9b59b6',
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Rentang gerakan menunjukkan variasi/fleksibilitas gerakan'
                        }
                    }
                }
            });
        }

        function updateCharts() {
            const filteredData = getFilteredData();
            const labels = getLabels(filteredData);

            Object.keys(charts).forEach(key => {
                charts[key].data.labels = labels;
                
                if (key === 'rollPitch') {
                    charts[key].data.datasets[0].data = filteredData.map(s => parseFloat(s.avg_roll).toFixed(2));
                    charts[key].data.datasets[1].data = filteredData.map(s => parseFloat(s.avg_pitch).toFixed(2));
                } else if (key === 'duration') {
                    charts[key].data.datasets[0].data = filteredData.map(s => (s.duration / 60).toFixed(1));
                    charts[key].data.datasets[1].data = filteredData.map(s => s.total_movements);
                } else if (key === 'accel') {
                    charts[key].data.datasets[0].data = filteredData.map(s => parseFloat(s.avg_ax).toFixed(3));
                    charts[key].data.datasets[1].data = filteredData.map(s => parseFloat(s.avg_ay).toFixed(3));
                    charts[key].data.datasets[2].data = filteredData.map(s => parseFloat(s.avg_az).toFixed(3));
                } else if (key === 'gyro') {
                    charts[key].data.datasets[0].data = filteredData.map(s => parseFloat(s.avg_gx).toFixed(2));
                    charts[key].data.datasets[1].data = filteredData.map(s => parseFloat(s.avg_gy).toFixed(2));
                    charts[key].data.datasets[2].data = filteredData.map(s => parseFloat(s.avg_gz).toFixed(2));
                } else if (key === 'trend') {
                    charts[key].data.datasets[0].data = filteredData.map(s => parseFloat(s.avg_roll).toFixed(2));
                    charts[key].data.datasets[1].data = filteredData.map(s => parseFloat(s.avg_pitch).toFixed(2));
                } else if (key === 'range') {
                    charts[key].data.datasets[0].data = filteredData.map(s => (parseFloat(s.max_roll) - parseFloat(s.min_roll)).toFixed(2));
                    charts[key].data.datasets[1].data = filteredData.map(s => (parseFloat(s.max_pitch) - parseFloat(s.min_pitch)).toFixed(2));
                }
                
                charts[key].update();
            });
        }

        function toggleSession(btn, index) {
            const allBtn = document.querySelector('.session-chip[onclick*="toggleAllSessions"]');
            
            if (activeSessionIndices.includes(index)) {
                activeSessionIndices = activeSessionIndices.filter(i => i !== index);
                btn.classList.remove('active');
                allBtn.classList.remove('active');
            } else {
                activeSessionIndices.push(index);
                activeSessionIndices.sort((a, b) => a - b);
                btn.classList.add('active');
                
                if (activeSessionIndices.length === sessionsData.length) {
                    allBtn.classList.add('active');
                }
            }
            
            if (activeSessionIndices.length > 0) {
                updateCharts();
            }
        }

        function toggleAllSessions(btn) {
            const sessionBtns = document.querySelectorAll('.session-chip[data-index]');
            
            if (btn.classList.contains('active')) {
                // Deselect all
                activeSessionIndices = [];
                btn.classList.remove('active');
                sessionBtns.forEach(b => b.classList.remove('active'));
            } else {
                // Select all
                activeSessionIndices = sessionsData.map((_, i) => i);
                btn.classList.add('active');
                sessionBtns.forEach(b => b.classList.add('active'));
            }
            
            if (activeSessionIndices.length > 0) {
                updateCharts();
            }
        }

        // Initialize charts on page load
        window.addEventListener('load', () => {
            if (sessionsData.length > 0) {
                initCharts();
            }
        });

        // ==========================================
        // EXPORT FUNCTIONS
        // ==========================================

        async function exportAllChartsAsPDF() {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            
            // Title page
            pdf.setFontSize(20);
            pdf.text('Laporan Grafik Terapi', pageWidth / 2, 20, { align: 'center' });
            pdf.setFontSize(12);
            pdf.text('<?= $patient['nama'] ?>', pageWidth / 2, 30, { align: 'center' });
            pdf.text('Tanggal Export: ' + new Date().toLocaleDateString('id-ID'), pageWidth / 2, 37, { align: 'center' });
            
            const chartElements = [
                { id: 'rollPitchChart', title: 'Perbandingan Roll & Pitch' },
                { id: 'durationChart', title: 'Durasi & Total Gerakan' },
                { id: 'accelChart', title: 'Perbandingan Accelerometer' },
                { id: 'gyroChart', title: 'Perbandingan Gyroscope' },
                { id: 'trendChart', title: 'Tren Perkembangan Terapi' },
                { id: 'rangeChart', title: 'Range Roll & Pitch per Sesi' }
            ];
            
            let isFirstChart = true;
            
            for (const chart of chartElements) {
                if (!isFirstChart) {
                    pdf.addPage();
                }
                
                const canvas = document.getElementById(chart.id);
                const imgData = canvas.toDataURL('image/png');
                
                pdf.setFontSize(14);
                pdf.text(chart.title, pageWidth / 2, 15, { align: 'center' });
                
                const imgWidth = pageWidth - 20;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                pdf.addImage(imgData, 'PNG', 10, 25, imgWidth, imgHeight);
                
                isFirstChart = false;
            }
            
            pdf.save('grafik_terapi_<?= str_replace(' ', '_', $patient['nama']) ?>_' + new Date().getTime() + '.pdf');
            
            alert('✅ PDF berhasil di-download!');
        }

        async function exportAllChartsAsImages() {
            const chartElements = [
                { id: 'rollPitchChart', title: 'roll_pitch' },
                { id: 'durationChart', title: 'durasi_gerakan' },
                { id: 'accelChart', title: 'accelerometer' },
                { id: 'gyroChart', title: 'gyroscope' },
                { id: 'trendChart', title: 'tren_perkembangan' },
                { id: 'rangeChart', title: 'range_gerakan' }
            ];
            
            let downloadedCount = 0;
            
            for (const chart of chartElements) {
                const canvas = document.getElementById(chart.id);
                
                // Convert canvas to blob
                canvas.toBlob(function(blob) {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.download = `grafik_${chart.title}_<?= str_replace(' ', '_', $patient['nama']) ?>_${new Date().getTime()}.png`;
                    link.href = url;
                    link.click();
                    URL.revokeObjectURL(url);
                    
                    downloadedCount++;
                    if (downloadedCount === chartElements.length) {
                        setTimeout(() => {
                            alert('✅ Semua grafik berhasil di-download sebagai PNG!');
                        }, 500);
                    }
                }, 'image/png');
                
                // Delay between downloads
                await new Promise(resolve => setTimeout(resolve, 300));
            }
        }

        // Export individual chart
        function exportChart(chartId, filename) {
            const canvas = document.getElementById(chartId);
            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.download = filename + '_' + new Date().getTime() + '.png';
                link.href = url;
                link.click();
                URL.revokeObjectURL(url);
            }, 'image/png');
        }
    </script>
</body>
</html>