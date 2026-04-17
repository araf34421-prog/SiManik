<?php
require 'config.php';

if(!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$kelas_id = isset($_GET['kelas']) ? $_GET['kelas'] : 1;
$kelas_info = mysqli_query($conn, "SELECT * FROM kelas WHERE id = $kelas_id")->fetch_assoc();

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Leger_Kelas_{$kelas_info['nama_kelas']}_{$kelas_info['tahun_ajaran']}.xls");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-size: 11px; }
        th, td { border: 1px solid black; padding: 5px; text-align: center; }
        th { background-color: #667eea; color: white; }
        .left { text-align: left; }
    </style>
</head>
<body>
    <h3 style="text-align: center;">LEGER NILAI RAPOR SISWA TAHUN PELAJARAN <?= $kelas_info['tahun_ajaran'] ?> <?= strtoupper($kelas_info['semester']) ?></h3>
    <p>SEKOLAH: SDN Curug 01 Bojongsari | Kelas: <?= $kelas_info['nama_kelas'] ?></p>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2">NO</th>
                <th rowspan="2">NAMA SISWA</th>
                <th rowspan="2">NISN</th>
                <th rowspan="2">NIS</th>
                <th>PAIDBP</th><th>PPDK</th><th>BI</th><th>MU</th><th>IPADSI</th><th>PJODK</th><th>ING</th><th>MLBD</th><th>SR</th><th>SB</th>
                <th colspan="3">Ketidakhadiran</th>
                <th rowspan="2">Ekstra</th>
            </tr>
            <tr>
                <th>Sakit</th><th>Izin</th><th>Alpa</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $siswa_list = mysqli_query($conn, "SELECT * FROM siswa WHERE kelas_id = $kelas_id ORDER BY nama_siswa ASC");
            while($siswa = mysqli_fetch_assoc($siswa_list)): 
                $nilai_query = mysqli_query($conn, "SELECT n.*, m.kode_mapel FROM nilai n JOIN mata_pelajaran m ON n.mapel_id = m.id WHERE n.siswa_id = {$siswa['id']}");
                $nilai_data = [];
                while($n = mysqli_fetch_assoc($nilai_query)) {
                    $nilai_data[$n['kode_mapel']] = $n['nilai_angka'];
                }
                
                $kehadiran = mysqli_query($conn, "SELECT * FROM kehadiran WHERE siswa_id = {$siswa['id']}")->fetch_assoc();
                $ekstra = mysqli_query($conn, "SELECT * FROM ekstrakurikuler WHERE siswa_id = {$siswa['id']}")->fetch_assoc();
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="left"><?= $siswa['nama_siswa'] ?></td>
                <td><?= $siswa['nisn'] ?></td>
                <td><?= $siswa['nis'] ?></td>
                <td><?= $nilai_data['PAIDBP'] ?? '-' ?></td>
                <td><?= $nilai_data['PPDK'] ?? '-' ?></td>
                <td><?= $nilai_data['BI'] ?? '-' ?></td>
                <td><?= $nilai_data['MU'] ?? '-' ?></td>
                <td><?= $nilai_data['IPADSI'] ?? '-' ?></td>
                <td><?= $nilai_data['PJODK'] ?? '-' ?></td>
                <td><?= $nilai_data['ING'] ?? '-' ?></td>
                <td><?= $nilai_data['MLBD'] ?? '-' ?></td>
                <td><?= $nilai_data['SR'] ?? '-' ?></td>
                <td><?= $nilai_data['SB'] ?? '-' ?></td>
                <td><?= $kehadiran['sakit'] ?? 0 ?></td>
                <td><?= $kehadiran['izin'] ?? 0 ?></td>
                <td><?= $kehadiran['alpa'] ?? 0 ?></td>
                <td><?= $ekstra['predikat'] ?? '-' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>