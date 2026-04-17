<?php
require 'config.php';

if(!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$success = false;
$error = '';

if(isset($_POST['import'])) {
    $kelas_id = $_POST['kelas_id'];
    
    if(!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] == 4) {
        $error = 'Silakan pilih file Excel!';
    } else {
        require_once 'vendor/autoload.php';
        
        $file = $_FILES['file_excel']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        $data_count = 0;
        
        // Data mulai dari baris 7 (index 6)
        foreach($rows as $key => $row) {
            // Skip header (baris 1-6)
            if($key < 6) continue;
            
            // Skip baris kosong
            if(empty($row[1])) continue;
            
            // SKIP jika nama berisi ":" (artinya keterangan mapel, bukan siswa)
            if(strpos($row[1], ':') !== false) continue;
            
            // SKIP jika kolom NO bukan angka (artinya bukan data siswa)
            if(!is_numeric(trim($row[0]))) continue;
            
            // Ambil dan validasi NISN (harus 10 digit)
            $nisn_raw = trim($row[2]);
            $nisn_digits = preg_replace('/[^0-9]/', '', $nisn_raw);
            if(empty($nisn_digits) || strlen($nisn_digits) < 10) continue;
            
            // SANITASI DATA (PENTING: escape special characters untuk hindari SQL injection)
            $nama_siswa = mysqli_real_escape_string($conn, trim($row[1]));
            $nisn = mysqli_real_escape_string($conn, $nisn_raw);
            $nis = mysqli_real_escape_string($conn, trim($row[3]));
            
            // Cari atau buat siswa
            $siswa = mysqli_query($conn, "SELECT id FROM siswa WHERE nisn = '$nisn'");
            if(mysqli_num_rows($siswa) > 0) {
                $siswa_id = mysqli_fetch_assoc($siswa)['id'];
            } else {
                mysqli_query($conn, "INSERT INTO siswa SET 
                    nisn='$nisn', 
                    nis='$nis', 
                    nama_siswa='$nama_siswa', 
                    kelas_id=$kelas_id, 
                    jenis_kelamin='L'
                ");
                $siswa_id = mysqli_insert_id($conn);
            }
            
            // Mapping kolom Excel ke mapel_id
            // Index: 0=NO, 1=NAMA, 2=NISN, 3=NIS, 4=PAIDBP, 5=PAKDBP, 6=PPDK, 7=BI, 8=MU, 9=IPADSI, 10=PJODK, 11=ING, 12=MLBD, 13=SR, 14=GKS, 15=SB
            $mapel_map = [
                4  => ['id' => 1, 'kode' => 'PAIDBP'],   // Kolom E
                5  => ['id' => 2, 'kode' => 'PAKDBP'],   // Kolom F (KOSONG untuk siswa Muslim)
                6  => ['id' => 3, 'kode' => 'PPDK'],     // Kolom G
                7  => ['id' => 4, 'kode' => 'BI'],       // Kolom H - Bahasa Indonesia
                8  => ['id' => 5, 'kode' => 'MU'],       // Kolom I - Matematika
                9  => ['id' => 6, 'kode' => 'IPADSI'],   // Kolom J
                10 => ['id' => 7, 'kode' => 'PJODK'],    // Kolom K
                11 => ['id' => 8, 'kode' => 'ING'],      // Kolom L - Bahasa Inggris (bukan BI!)
                12 => ['id' => 9, 'kode' => 'MLBD'],     // Kolom M
                13 => ['id' => 10, 'kode' => 'SR'],      // Kolom N
                14 => ['id' => 11, 'kode' => 'GKS'],     // Kolom O
                15 => ['id' => 12, 'kode' => 'SB']       // Kolom P
            ];
            
            foreach($mapel_map as $index => $info) {
                $nilai = isset($row[$index]) ? trim($row[$index]) : '';
                
                // PENTING: Hanya insert jika nilai TIDAK KOSONG dan numeric
                if(!empty($nilai) && is_numeric($nilai)) {
                    $predikat = $nilai >= 90 ? 'A' : ($nilai >= 80 ? 'B' : ($nilai >= 70 ? 'C' : 'D'));
                    
                    $check = mysqli_query($conn, "SELECT id FROM nilai WHERE siswa_id=$siswa_id AND mapel_id={$info['id']}");
                    if(mysqli_num_rows($check) > 0) {
                        mysqli_query($conn, "UPDATE nilai SET nilai_angka=$nilai, predikat='$predikat' WHERE siswa_id=$siswa_id AND mapel_id={$info['id']}");
                    } else {
                        mysqli_query($conn, "INSERT INTO nilai SET 
                            siswa_id=$siswa_id, 
                            mapel_id={$info['id']}, 
                            kelas_id=$kelas_id, 
                            nilai_angka=$nilai, 
                            predikat='$predikat', 
                            semester='Ganjil', 
                            tahun_ajaran='2025/2026'
                        ");
                    }
                }
                // Jika kosong, TIDAK insert/update (kolom tetap kosong di database)
            }
            
            // Kehadiran (Kolom Q, R, S = index 16, 17, 18)
            $sakit = isset($row[16]) ? (int)$row[16] : 0;
            $izin = isset($row[17]) ? (int)$row[17] : 0;
            $alpa = isset($row[18]) ? (int)$row[18] : 0;
            $hadir = 100 - ($sakit + $izin + $alpa);
            
            $check = mysqli_query($conn, "SELECT id FROM kehadiran WHERE siswa_id=$siswa_id");
            if(mysqli_num_rows($check) > 0) {
                mysqli_query($conn, "UPDATE kehadiran SET 
                    sakit=$sakit, 
                    izin=$izin, 
                    alpa=$alpa, 
                    hadir=$hadir 
                    WHERE siswa_id=$siswa_id"
                );
            } else {
                mysqli_query($conn, "INSERT INTO kehadiran SET 
                    siswa_id=$siswa_id, 
                    kelas_id=$kelas_id, 
                    sakit=$sakit, 
                    izin=$izin, 
                    alpa=$alpa, 
                    hadir=$hadir, 
                    semester='Ganjil', 
                    tahun_ajaran='2025/2026'
                ");
            }
            
            // Ekstrakurikuler (Kolom T = index 19)
            $ekstra_predikat = isset($row[19]) ? trim($row[19]) : 'B';
            $check = mysqli_query($conn, "SELECT id FROM ekstrakurikuler WHERE siswa_id=$siswa_id");
            if(mysqli_num_rows($check) > 0) {
                mysqli_query($conn, "UPDATE ekstrakurikuler SET 
                    predikat='$ekstra_predikat', 
                    nama_ekstra='PRAMUKA SIAGA' 
                    WHERE siswa_id=$siswa_id"
                );
            } else {
                mysqli_query($conn, "INSERT INTO ekstrakurikuler SET 
                    siswa_id=$siswa_id, 
                    nama_ekstra='PRAMUKA SIAGA', 
                    predikat='$ekstra_predikat', 
                    semester='Ganjil', 
                    tahun_ajaran='2025/2026'
                ");
            }
            
            $data_count++;
        }
        
        if($data_count > 0) {
            $success = true;
            $success_msg = "✅ Import berhasil! $data_count data siswa masuk ke database.";
        } else {
            $error = "❌ Tidak ada data yang diimport. Pastikan format Excel sesuai!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Excel - Monitoring Nilai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --dark-navy: #0f172a;
            --royal-blue: #1e40af;
            --gold: #d97706;
            --slate: #64748b;
            --light-bg: #f1f5f9;
        }
        body { background-color: var(--light-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar {
            background: linear-gradient(180deg, var(--dark-navy) 0%, #1e293b 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar-brand {
            padding: 35px 25px;
            text-align: center;
            border-bottom: 2px solid var(--gold);
        }
        .sidebar-brand i { font-size: 45px; color: var(--gold); margin-bottom: 12px; }
        .sidebar-brand h5 { font-weight: 700; font-size: 17px; }
        .sidebar-brand small { font-size: 12px; color: var(--slate); }
        .sidebar-menu { padding: 25px 15px; }
        .sidebar-menu a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            margin-bottom: 8px;
            border-radius: 8px;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: var(--gold);
            padding-left: 25px;
        }
        .sidebar-menu a.active {
            background: linear-gradient(90deg, var(--gold) 0%, #f59e0b 100%);
            color: var(--dark-navy);
            font-weight: 700;
        }
        .sidebar-menu a i { margin-right: 12px; width: 22px; text-align: center; }
        .import-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            max-width: 800px;
            margin: 50px auto;
        }
        .page-header {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--royal-blue) 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .page-header h2 { font-weight: 700; font-size: 26px; margin: 0; }
        .page-header h2 i { margin-right: 12px; color: var(--gold); }
        .btn-primary {
            background: linear-gradient(135deg, var(--royal-blue) 0%, #3b82f6 100%);
            border: none;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-graduation-cap"></i>
                <h5>MONITORING NILAI</h5>
                <small>SDN CURUG 01</small>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="import_excel.php" class="active"><i class="fas fa-file-excel"></i> Import Excel</a>
                <a href="leger.php?kelas=1"><i class="fas fa-file-alt"></i> Leger Nilai</a>
                <a href="grafik_nilai.php"><i class="fas fa-chart-bar"></i> Grafik Nilai</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="col-md-10 main-content p-4">
            <div class="page-header">
                <h2><i class="fas fa-file-excel"></i> IMPORT DATA DARI EXCEL</h2>
            </div>
            
            <div class="import-box">
                <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success_msg ?>
                </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Panduan Import:</strong>
                    <ol class="mb-0">
                        <li>Pastikan file Excel sesuai format (seperti f_leger_Kelas 3 B.xlsx)</li>
                        <li>Data siswa dimulai dari baris 7</li>
                        <li>Kolom PAKDBP boleh kosong (untuk siswa Muslim)</li>
                        <li>Pilih kelas tujuan import</li>
                    </ol>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Pilih Kelas</label>
                        <select name="kelas_id" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <option value="1">Kelas 4A</option>
                            <option value="2">Kelas 4B</option>
                            <option value="3">Kelas 4C</option>
                            <option value="4">Kelas 4D</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">File Excel</label>
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls" required>
                        <small class="text-muted">Format: .xlsx atau .xls</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="import" class="btn btn-primary btn-lg">
                            <i class="fas fa-upload"></i> Upload & Import
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>