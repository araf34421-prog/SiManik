<?php
require 'config.php';

if(!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

// Filter Kelas
$kelas_id = isset($_GET['kelas']) ? $_GET['kelas'] : 1;
$kelas_info = mysqli_query($conn, "SELECT * FROM kelas WHERE id = $kelas_id")->fetch_assoc();

// Rata-rata nilai per mata pelajaran - QUERY YANG BENAR
$mapel_stats = mysqli_query($conn, "
    SELECT m.nama_mapel, m.kode_mapel, AVG(n.nilai_angka) as rata_rata
    FROM nilai n
    JOIN mata_pelajaran m ON n.mapel_id = m.id
    WHERE n.kelas_id = $kelas_id
    GROUP BY m.id
    ORDER BY m.kode_mapel ASC
");

// Top 10 Siswa
$top_siswa = mysqli_query($conn, "
    SELECT s.nama_siswa, AVG(n.nilai_angka) as rata_rata
    FROM siswa s
    JOIN nilai n ON s.id = n.siswa_id
    WHERE s.kelas_id = $kelas_id
    GROUP BY s.id
    ORDER BY rata_rata DESC
    LIMIT 10
");

// Kehadiran
$kehadiran_stats = mysqli_query($conn, "
    SELECT SUM(sakit) as total_sakit, 
           SUM(izin) as total_izin, 
           SUM(alpa) as total_alpa,
           SUM(hadir) as total_hadir
    FROM kehadiran
    WHERE kelas_id = $kelas_id
")->fetch_assoc();

// Distribusi nilai
$distribusi_nilai = mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN nilai_angka >= 90 THEN 1 ELSE 0 END) as a_count,
        SUM(CASE WHEN nilai_angka >= 80 AND nilai_angka < 90 THEN 1 ELSE 0 END) as b_count,
        SUM(CASE WHEN nilai_angka >= 70 AND nilai_angka < 80 THEN 1 ELSE 0 END) as c_count,
        SUM(CASE WHEN nilai_angka >= 60 AND nilai_angka < 70 THEN 1 ELSE 0 END) as d_count,
        SUM(CASE WHEN nilai_angka < 60 THEN 1 ELSE 0 END) as e_count
    FROM nilai
    WHERE kelas_id = $kelas_id
")->fetch_assoc();

$total_siswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa WHERE kelas_id = $kelas_id")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Nilai - Kelas <?= $kelas_info['nama_kelas'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --dark-navy: #0f172a;
            --royal-blue: #1e40af;
            --bright-blue: #3b82f6;
            --gold: #d97706;
            --amber: #f59e0b;
            --slate: #64748b;
            --light-slate: #e2e8f0;
            --white: #ffffff;
            --light-bg: #f1f5f9;
        }
        body { 
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, var(--dark-navy) 0%, #1e293b 100%);
            min-height: 100vh;
            color: var(--white);
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-brand {
            padding: 35px 25px;
            text-align: center;
            border-bottom: 2px solid var(--gold);
            background: rgba(217, 119, 6, 0.1);
        }
        .sidebar-brand i { font-size: 45px; color: var(--gold); margin-bottom: 12px; }
        .sidebar-brand h5 { font-weight: 700; font-size: 17px; margin-bottom: 5px; }
        .sidebar-brand small { font-size: 12px; color: var(--slate); }
        .sidebar-menu { padding: 25px 15px; }
        .sidebar-menu a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: var(--white);
            border-left-color: var(--gold);
            padding-left: 25px;
        }
        .sidebar-menu a.active {
            background: linear-gradient(90deg, var(--gold) 0%, var(--amber) 100%);
            color: var(--dark-navy);
            font-weight: 700;
        }
        .sidebar-menu a i { margin-right: 12px; width: 22px; text-align: center; }
        .main-content { background-color: var(--light-bg); padding: 30px; }
        .page-header {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--royal-blue) 100%);
            color: var(--white);
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.2);
        }
        .page-header h2 { font-weight: 700; font-size: 26px; margin: 0; }
        .page-header h2 i { margin-right: 12px; color: var(--gold); }
        .card {
            background: var(--white);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .card-header {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--royal-blue) 100%);
            color: var(--white);
            padding: 18px 25px;
            font-weight: 700;
            font-size: 16px;
            border-radius: 12px 12px 0 0 !important;
        }
        .card-header i { margin-right: 10px; color: var(--gold); }
        .card-body { padding: 25px; }
        .chart-container { position: relative; height: 320px; padding: 15px; }
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-top: 4px solid var(--royal-blue);
            transition: all 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
        .stat-card.blue { border-top-color: var(--royal-blue); }
        .stat-card.gold { border-top-color: var(--gold); }
        .stat-card.green { border-top-color: #10b981; }
        .stat-card.red { border-top-color: #ef4444; }
        .stat-card h6 { color: var(--slate); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; font-weight: 600; }
        .stat-card h3 { color: var(--dark-navy); font-weight: 700; font-size: 36px; margin: 0; }
        .badge { padding: 8px 14px; font-weight: 600; font-size: 12px; border-radius: 6px; }
        .badge-success { background: #10b981; color: white; }
        .badge-warning { background: var(--gold); color: white; }
        .badge-danger { background: #ef4444; color: white; }
        .badge-primary { background: var(--royal-blue); color: white; }
        .btn { border-radius: 8px; padding: 10px 20px; font-weight: 600; font-size: 14px; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, var(--royal-blue) 0%, var(--bright-blue) 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, var(--bright-blue) 0%, var(--royal-blue) 100%); transform: translateY(-2px); }
        .btn-secondary { background: var(--slate); border: none; color: white; }
        .btn-success { background: #10b981; border: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -280px; z-index: 1000; transition: all 0.3s; }
            .sidebar.show { left: 0; }
            .main-content { padding: 20px; }
            .chart-container { height: 250px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-graduation-cap"></i>
                <h5>MONITORING NILAI</h5>
                <small>SDN CURUG 01 BOJONGSARI</small>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="import_excel.php"><i class="fas fa-file-excel"></i> Import Excel</a>
                <a href="leger.php?kelas=<?= $kelas_id ?>"><i class="fas fa-file-alt"></i> Leger Nilai</a>
                <a href="grafik_nilai.php" class="active"><i class="fas fa-chart-bar"></i> Grafik Nilai</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-chart-bar"></i> GRAFIK NILAI - KELAS <?= $kelas_info['nama_kelas'] ?></h2>
                    <div class="d-flex gap-2">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                        <select class="form-select form-select-sm" style="width: 150px; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);" onchange="window.location='?kelas='+this.value">
                            <option value="1" <?= $kelas_id==1?'selected':'' ?>>Kelas 4A</option>
                            <option value="2" <?= $kelas_id==2?'selected':'' ?>>Kelas 4B</option>
                            <option value="3" <?= $kelas_id==3?'selected':'' ?>>Kelas 4C</option>
                            <option value="4" <?= $kelas_id==4?'selected':'' ?>>Kelas 4D</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <h6><i class="fas fa-users"></i> Total Siswa</h6>
                        <h3><?= $total_siswa ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card gold">
                        <h6><i class="fas fa-chart-line"></i> Rata-rata Kelas</h6>
                        <h3><?= number_format(($distribusi_nilai['a_count']*95 + $distribusi_nilai['b_count']*85 + $distribusi_nilai['c_count']*75) / max(1, $distribusi_nilai['a_count']+$distribusi_nilai['b_count']+$distribusi_nilai['c_count']+$distribusi_nilai['d_count']+$distribusi_nilai['e_count']), 1) ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <h6><i class="fas fa-award"></i> Nilai A</h6>
                        <h3><?= $distribusi_nilai['a_count'] ?? 0 ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card red">
                        <h6><i class="fas fa-exclamation-triangle"></i> Perlu Bimbingan</h6>
                        <h3><?= ($distribusi_nilai['d_count'] ?? 0) + ($distribusi_nilai['e_count'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="row mb-4">
                <!-- Rata-rata per Mapel -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i> RATA-RATA NILAI PER MATA PELAJARAN
                        </div>
                        <div class="chart-container">
                            <canvas id="mapelChart"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Distribusi Nilai -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i> DISTRIBUSI NILAI (A-E)
                        </div>
                        <div class="chart-container">
                            <canvas id="distribusiChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="row mb-4">
                <!-- Top 10 Siswa -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-trophy"></i> TOP 10 SISWA BERPRESTASI
                        </div>
                        <div class="chart-container">
                            <canvas id="topSiswaChart"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Kehadiran -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar-check"></i> STATISTIK KEHADIRAN
                        </div>
                        <div class="chart-container">
                            <canvas id="kehadiranChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart 1: Rata-rata Nilai per Mapel
const ctxMapel = document.getElementById('mapelChart').getContext('2d');
new Chart(ctxMapel, {
    type: 'bar',
    data: {
        labels: [<?php 
            mysqli_data_seek($mapel_stats, 0);
            $labels = [];
            while($row = mysqli_fetch_assoc($mapel_stats)) {
                $labels[] = "'".$row['kode_mapel']."'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Rata-rata Nilai',
            data: [<?php 
                mysqli_data_seek($mapel_stats, 0);
                $data = [];
                while($row = mysqli_fetch_assoc($mapel_stats)) {
                    $data[] = number_format($row['rata_rata'], 2);
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: 'rgba(30, 64, 175, 0.8)',
            borderColor: 'rgba(30, 64, 175, 1)',
            borderWidth: 2,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { stepSize: 10 } }
        },
        plugins: { legend: { display: false } }
    }
});

// Chart 2: Distribusi Nilai
const ctxDistribusi = document.getElementById('distribusiChart').getContext('2d');
new Chart(ctxDistribusi, {
    type: 'doughnut',
    data: {
        labels: ['A (90-100)', 'B (80-89)', 'C (70-79)', 'D (60-69)', 'E (<60)'],
        datasets: [{
            data: [
                <?= $distribusi_nilai['a_count'] ?? 0 ?>,
                <?= $distribusi_nilai['b_count'] ?? 0 ?>,
                <?= $distribusi_nilai['c_count'] ?? 0 ?>,
                <?= $distribusi_nilai['d_count'] ?? 0 ?>,
                <?= $distribusi_nilai['e_count'] ?? 0 ?>
            ],
            backgroundColor: ['#10b981', '#1e40af', '#d97706', '#f97316', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 15 } } }
    }
});

// Chart 3: Top 10 Siswa
const ctxTopSiswa = document.getElementById('topSiswaChart').getContext('2d');
new Chart(ctxTopSiswa, {
    type: 'bar',
    data: {
        labels: [<?php 
            mysqli_data_seek($top_siswa, 0);
            $labels = [];
            while($row = mysqli_fetch_assoc($top_siswa)) {
                $nama = explode(' ', $row['nama_siswa']);
                $labels[] = "'".($nama[0] ?? 'Siswa')."'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Rata-rata Nilai',
            data: [<?php 
                mysqli_data_seek($top_siswa, 0);
                $data = [];
                while($row = mysqli_fetch_assoc($top_siswa)) {
                    $data[] = number_format($row['rata_rata'], 2);
                }
                echo implode(',', $data);
            ?>],
            backgroundColor: 'rgba(217, 119, 6, 0.8)',
            borderColor: 'rgba(217, 119, 6, 1)',
            borderWidth: 2,
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: { x: { beginAtZero: true, max: 100 } },
        plugins: { legend: { display: false } }
    }
});

// Chart 4: Kehadiran
const ctxKehadiran = document.getElementById('kehadiranChart').getContext('2d');
new Chart(ctxKehadiran, {
    type: 'pie',
    data: {
        labels: ['Hadir', 'Sakit', 'Izin', 'Alpha'],
        datasets: [{
            data: [
                <?= $kehadiran_stats['total_hadir'] ?? 0 ?>,
                <?= $kehadiran_stats['total_sakit'] ?? 0 ?>,
                <?= $kehadiran_stats['total_izin'] ?? 0 ?>,
                <?= $kehadiran_stats['total_alpa'] ?? 0 ?>
            ],
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 15 } } }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>