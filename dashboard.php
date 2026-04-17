<?php
require 'config.php';

if(!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

// Filter Kelas
$kelas_id = isset($_GET['kelas']) ? $_GET['kelas'] : 1;
$kelas_info = mysqli_query($conn, "SELECT * FROM kelas WHERE id = $kelas_id")->fetch_assoc();

// Statistik
$total_siswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa WHERE kelas_id = $kelas_id")->fetch_assoc()['total'];
$rata_rata_nilai = mysqli_query($conn, "SELECT AVG(nilai_angka) as avg FROM nilai WHERE kelas_id = $kelas_id")->fetch_assoc()['avg'];
$total_hadir = mysqli_query($conn, "SELECT SUM(hadir) as total FROM kehadiran WHERE kelas_id = $kelas_id")->fetch_assoc()['total'];
$total_alpa = mysqli_query($conn, "SELECT SUM(alpa) as total FROM kehadiran WHERE kelas_id = $kelas_id")->fetch_assoc()['total'];

// Top Siswa
$top_siswa = mysqli_query($conn, "
    SELECT s.nama_siswa, AVG(n.nilai_angka) as rata_rata
    FROM siswa s
    JOIN nilai n ON s.id = n.siswa_id
    WHERE s.kelas_id = $kelas_id
    GROUP BY s.id
    ORDER BY rata_rata DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Monitoring Nilai SDN Curug 01</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Sidebar - Dark & Authoritative */
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

        .sidebar-brand i {
            font-size: 45px;
            color: var(--gold);
            margin-bottom: 12px;
            text-shadow: 0 0 10px rgba(217, 119, 6, 0.5);
        }

        .sidebar-brand h5 {
            font-weight: 700;
            font-size: 17px;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .sidebar-brand small {
            font-size: 12px;
            color: var(--slate);
            font-weight: 500;
        }

        .sidebar-menu {
            padding: 25px 15px;
        }

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
            border-left-color: var(--gold);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
        }

        .sidebar-menu a i {
            margin-right: 12px;
            width: 22px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            background-color: var(--light-bg);
            padding: 30px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--royal-blue) 100%);
            color: var(--white);
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.2);
        }

        .page-header h2 {
            font-weight: 700;
            font-size: 26px;
            margin: 0;
        }

        .page-header h2 i {
            margin-right: 12px;
            color: var(--gold);
        }

        .page-header span {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Statistics Cards - More Elegant */
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-top: 4px solid var(--royal-blue);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .stat-card.blue { border-top-color: var(--royal-blue); }
        .stat-card.gold { border-top-color: var(--gold); }
        .stat-card.green { border-top-color: #10b981; }
        .stat-card.red { border-top-color: #ef4444; }

        .stat-card h6 {
            color: var(--slate);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .stat-card h3 {
            color: var(--dark-navy);
            font-weight: 700;
            font-size: 36px;
            margin: 0;
        }

        .stat-card i {
            font-size: 40px;
            opacity: 0.2;
            color: var(--dark-navy);
        }

        /* Cards */
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

        .card-header i {
            margin-right: 10px;
            color: var(--gold);
        }

        .card-body {
            padding: 25px;
        }

        /* Table */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--dark-navy);
            color: var(--white);
            border: none;
            padding: 15px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: var(--light-slate);
            font-size: 14px;
        }

        .table tbody tr:hover {
            background: var(--light-bg);
        }

        /* Badges */
        .badge {
            padding: 8px 14px;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
        }

        .badge-success { background: #10b981; color: white; }
        .badge-warning { background: var(--gold); color: white; }
        .badge-danger { background: #ef4444; color: white; }
        .badge-primary { background: var(--royal-blue); color: white; }

        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--royal-blue) 0%, var(--bright-blue) 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--bright-blue) 0%, var(--royal-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .btn-warning {
            background: var(--gold);
            border: none;
            color: var(--white);
        }

        .btn-warning:hover {
            background: #b45309;
            color: var(--white);
        }

        .btn-secondary {
            background: var(--slate);
            border: none;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--royal-blue);
            box-shadow: 0 0 0 0.25rem rgba(30, 64, 175, 0.15);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -280px;
                z-index: 1000;
                transition: all 0.3s;
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                padding: 20px;
            }
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
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="import_excel.php"><i class="fas fa-file-excel"></i> Import Excel</a>
                <a href="leger.php?kelas=<?= $kelas_id ?>"><i class="fas fa-file-alt"></i> Leger Nilai</a>
                <a href="grafik_nilai.php"><i class="fas fa-chart-bar"></i> Grafik Nilai</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-tachometer-alt"></i> DASHBOARD MONITORING</h2>
                    <div class="d-flex gap-3 align-items-center">
                        <span><i class="far fa-calendar-alt"></i> <?= date('d F Y') ?></span>
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6><i class="fas fa-users"></i> Total Siswa</h6>
                                <h3><?= $total_siswa ?></h3>
                            </div>
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card gold">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6><i class="fas fa-chart-line"></i> Rata-rata Nilai</h6>
                                <h3><?= number_format($rata_rata_nilai ?? 0, 1) ?></h3>
                            </div>
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6><i class="fas fa-check-circle"></i> Kehadiran</h6>
                                <h3><?= $total_hadir ?? 0 ?></h3>
                            </div>
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card red">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6><i class="fas fa-times-circle"></i> Alpha</h6>
                                <h3><?= $total_alpa ?? 0 ?></h3>
                            </div>
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Siswa -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-trophy"></i> TOP 5 SISWA BERPRESTASI
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Peringkat</th>
                                    <th>Nama Siswa</th>
                                    <th>Rata-rata Nilai</th>
                                    <th>Predikat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($top_siswa)): 
                                    $predikat = $row['rata_rata'] >= 90 ? 'A' : ($row['rata_rata'] >= 80 ? 'B' : ($row['rata_rata'] >= 70 ? 'C' : 'D'));
                                    $badgeClass = $predikat == 'A' ? 'badge-success' : ($predikat == 'B' ? 'badge-primary' : ($predikat == 'C' ? 'badge-warning' : 'badge-danger'));
                                ?>
                                <tr>
                                    <td><span class="badge <?= $no <= 3 ? 'badge-warning' : 'badge-secondary' ?>">#<?= $no++ ?></span></td>
                                    <td><strong><?= $row['nama_siswa'] ?></strong></td>
                                    <td><strong><?= number_format($row['rata_rata'], 2) ?></strong></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= $predikat ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>