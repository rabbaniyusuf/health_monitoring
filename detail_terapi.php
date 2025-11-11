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
                (ketika Pitch mencapai <strong>90° ± 7°</strong>, range: <strong>83° - 97°</strong>). 
                <br><br>
                Setiap titik pada grafik merepresentasikan 1 gerakan yang berhasil tercatat ketika lengan diangkat ke posisi vertikal.
                <br><br>
                <strong>🎯 Target:</strong> Pitch = 90° (lengan vertikal)<br>
                <strong>📏 Range Valid:</strong> 83° - 97° (toleransi ±7°)<br>
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
        // Script untuk detail_terapi.php dengan logika 90 derajat

// Data gerakan dari PHP
const movementsData = <?= json_encode($movements_array) ?>;

console.log('Total movements data:', movementsData.length);

// Filter gerakan yang terdeteksi (Pitch mendekati 90 derajat)
const pitchThreshold = 7; // Toleransi ±7 derajat
const target90Degree = 90;
let detectedMovements = [];
let previousPitch = null;
let movementIndex = 1;

movementsData.forEach((movement, index) => {
    const currentPitch = parseFloat(movement.pitch);
    
    // Deteksi gerakan: pitch saat ini di 90° dan sebelumnya tidak di 90°
    if (previousPitch !== null) {
        const isAt90 = Math.abs(currentPitch - target90Degree) <= pitchThreshold;
        const wasNotAt90 = Math.abs(previousPitch - target90Degree) > pitchThreshold;
        
        if (isAt90 && wasNotAt90) {
            detectedMovements.push({
                index: movementIndex++,
                timestamp: movement.timestamp,
                roll: parseFloat(movement.roll),
                pitch: currentPitch,
                axG: parseFloat(movement.axG),
                ayG: parseFloat(movement.ayG),
                azG: parseFloat(movement.azG)
            });
        }
    }
    
    previousPitch = currentPitch;
});

console.log('Detected movements at 90°:', detectedMovements.length);
document.getElementById('totalDetectedMovements').textContent = detectedMovements.length;

// Prepare chart data
const labels = detectedMovements.map(m => {
    const time = new Date(m.timestamp);
    return `Gerakan ${m.index}\n${time.toLocaleTimeString('id-ID')}`;
});

const rollData = detectedMovements.map(m => m.roll);
const pitchData = detectedMovements.map(m => m.pitch);

// Create chart
const ctx = document.getElementById('movementChart').getContext('2d');
const movementChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Roll (°)',
                data: rollData,
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
                label: 'Pitch (°)',
                data: pitchData,
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
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    title: function(context) {
                        return context[0].label.replace('\n', ' - ');
                    },
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '°';
                    },
                    afterLabel: function(context) {
                        const movement = detectedMovements[context.dataIndex];
                        return [
                            'Accel X: ' + movement.axG.toFixed(3) + ' G',
                            'Accel Y: ' + movement.ayG.toFixed(3) + ' G',
                            'Accel Z: ' + movement.azG.toFixed(3) + ' G',
                            '─────────────────',
                            'Target: 90° (±7°)',
                            'Range: 83° - 97°'
                        ];
                    }
                },
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 12 },
                padding: 12
            },
            // Add horizontal line at 90°
            annotation: {
                annotations: {
                    line90: {
                        type: 'line',
                        yMin: 90,
                        yMax: 90,
                        borderColor: 'rgb(255, 99, 132)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        label: {
                            content: 'Target: 90°',
                            enabled: true,
                            position: 'end'
                        }
                    },
                    line83: {
                        type: 'line',
                        yMin: 83,
                        yMax: 83,
                        borderColor: 'rgba(255, 193, 7, 0.5)',
                        borderWidth: 1,
                        borderDash: [3, 3],
                        label: {
                            content: 'Min: 83°',
                            enabled: true,
                            position: 'start'
                        }
                    },
                    line97: {
                        type: 'line',
                        yMin: 97,
                        yMax: 97,
                        borderColor: 'rgba(255, 193, 7, 0.5)',
                        borderWidth: 1,
                        borderDash: [3, 3],
                        label: {
                            content: 'Max: 97°',
                            enabled: true,
                            position: 'start'
                        }
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                min: 70,
                max: 110,
                title: {
                    display: true,
                    text: 'Derajat (°)',
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                },
                ticks: {
                    font: {
                        size: 12
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Urutan Gerakan',
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                },
                ticks: {
                    font: {
                        size: 10
                    },
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        }
    }
});
        
        // Export to PDF function
        async function exportMovementChartPDF() {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('l', 'mm', 'a4'); // Landscape
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
            
            // Chart
            const canvas = document.getElementById('movementChart');
            const imgData = canvas.toDataURL('image/png', 1.0);
            
            const imgWidth = pageWidth - 30;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            pdf.addImage(imgData, 'PNG', 15, 45, imgWidth, imgHeight);
            
            // Summary at bottom
            const yPosition = 45 + imgHeight + 10;
            pdf.setFontSize(11);
            pdf.text('Total Gerakan Terdeteksi: ' + detectedMovements.length + ' gerakan', 15, yPosition);
            pdf.text('Durasi Terapi: <?= gmdate("i:s", $session['duration']) ?> menit', 15, yPosition + 7);
            pdf.text('Total Data Points: <?= $stats['total_data'] ?>', 15, yPosition + 14);
            
            // Add data table on new page
            if (detectedMovements.length > 0) {
                pdf.addPage();
                pdf.setFontSize(14);
                pdf.text('Data Detail Gerakan Terdeteksi', pageWidth / 2, 15, { align: 'center' });
                
                // Table headers
                pdf.setFontSize(10);
                let yPos = 25;
                const colWidths = [15, 50, 30, 30, 30, 30, 30];
                const headers = ['No', 'Waktu', 'Roll (°)', 'Pitch (°)', 'Accel X', 'Accel Y', 'Accel Z'];
                
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
                    const row = [
                        movement.index,
                        time.toLocaleTimeString('id-ID'),
                        movement.roll.toFixed(2),
                        movement.pitch.toFixed(2),
                        movement.axG.toFixed(3),
                        movement.ayG.toFixed(3),
                        movement.azG.toFixed(3)
                    ];
                    
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
    </script>
</body>
</html>