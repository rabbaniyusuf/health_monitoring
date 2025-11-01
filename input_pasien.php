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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $usia = $_POST['usia'];
    $status_kesehatan = $_POST['status_kesehatan'];
    $kategori_sakit = $_POST['kategori_sakit'] ?? '-';
    
    if ($status_kesehatan == 'Sehat') {
        $kategori_sakit = '-';
    }
    
    $sql = "INSERT INTO patients (nama, jenis_kelamin, usia, status_kesehatan, kategori_sakit) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiss", $nama, $jenis_kelamin, $usia, $status_kesehatan, $kategori_sakit);
    
    if ($stmt->execute()) {
        $patient_id = $conn->insert_id;
        $_SESSION['active_patient_id'] = $patient_id;
        $message = "Data pasien berhasil ditambahkan! ID Pasien: " . $patient_id;
        
        // Redirect ke halaman monitoring
        header("Location: monitoring.php?patient_id=" . $patient_id);
        exit;
    } else {
        $error = "Gagal menyimpan data: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Pasien</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            text-align: center;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .required {
            color: #dc3545;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .kategori-sakit {
            display: none;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-link:hover {
            color: #764ba2;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Kembali ke Daftar Pasien</a>
        
        <h1>📝 Input Data Pasien</h1>
        <p class="subtitle">Masukkan data pasien sebelum memulai monitoring</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama" required placeholder="Masukkan nama lengkap">
            </div>
            
            <div class="form-group">
                <label>Jenis Kelamin <span class="required">*</span></label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="jenis_kelamin" value="Laki-laki" required>
                        <span>♂ Laki-laki</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="jenis_kelamin" value="Perempuan" required>
                        <span>♀ Perempuan</span>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Usia (tahun) <span class="required">*</span></label>
                <input type="number" name="usia" min="1" max="120" required placeholder="Masukkan usia">
            </div>
            
            <div class="form-group">
                <label>Status Kesehatan <span class="required">*</span></label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="status_kesehatan" value="Sehat" required onclick="hideKategori()">
                        <span>✅ Sehat</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="status_kesehatan" value="Sakit" required onclick="showKategori()">
                        <span>🏥 Sakit</span>
                    </label>
                </div>
            </div>
            
            <div class="form-group kategori-sakit" id="kategoriSakit">
                <label>Kategori Sakit <span class="required">*</span></label>
                <select name="kategori_sakit" id="kategoriSelect">
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Ringan">🟡 Ringan</option>
                    <option value="Sedang">🟠 Sedang</option>
                    <option value="Parah">🔴 Parah</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Simpan & Mulai Monitoring</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
    
    <script>
        function showKategori() {
            const kategoriDiv = document.getElementById('kategoriSakit');
            const kategoriSelect = document.getElementById('kategoriSelect');
            kategoriDiv.style.display = 'block';
            kategoriSelect.required = true;
        }
        
        function hideKategori() {
            const kategoriDiv = document.getElementById('kategoriSakit');
            const kategoriSelect = document.getElementById('kategoriSelect');
            kategoriDiv.style.display = 'none';
            kategoriSelect.required = false;
            kategoriSelect.value = '';
        }
    </script>
</body>
</html>