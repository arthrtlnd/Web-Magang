<?php
session_start();
require_once '../app/koneksi.php';
check_admin();

// Query untuk mengambil semua data personel
$query = "SELECT p.*, g.gender, k.SEBUTAN as korp_sebutan, 
          pkt.sebutan as pangkat_sebutan, m.Nama as matra_nama, 
          s.nama_satker, s_lama.nama_satker as nama_satker_lama
          FROM personel p
          LEFT JOIN gender g ON p.kd_gender = g.kd_gender
          LEFT JOIN korp k ON p.korp = k.KORPSID
          LEFT JOIN pangkat pkt ON p.pangkat = pkt.kd_pkt
          LEFT JOIN matra m ON p.matra = m.MTR
          LEFT JOIN satker s ON p.kd_satker = s.kd_satker
          LEFT JOIN satker s_lama ON p.satker_lama = s_lama.kd_satker
          ORDER BY p.nama ASC"; // Urutkan berdasarkan nama sesuai permintaan tabel

$result = mysqli_query($conn, $query);

// Set header untuk download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Data_Personel_' . date('Y-m-d_His') . '.xls"');
header('Cache-Control: max-age=0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #7d1c1c;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>DATA PERSONEL TNI</h2>
    <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
    
    <table>
        <!-- REVISI 2: Ubah urutan kolom export -->
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NRP</th>
                <th>Pangkat</th>
                <th>Korp</th>
                <th>Matra</th>
                <th>Jenis Kelamin</th>
                <th>Tempat Lahir</th>
                <th>Tanggal Lahir</th>
                <th>Satuan Lama</th>
                <th>No KEP Lama</th>
                <th>No Sprint Lama</th>
                <th>Satuan Baru</th>
                <th>No KEP Baru</th>
                <th>No Sprint Baru</th>
                <th>NIK</th>
                <th>Alamat</th>
                <th>No HP</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)): 
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo $row['nama']; ?></td>
                <td>'<?php echo $row['nrp']; ?></td> <!-- Tambah petik agar NRP dibaca sebagai teks -->
                <td><?php echo $row['pangkat_sebutan'] ?? '-'; ?></td>
                <td><?php echo $row['korp_sebutan'] ?? '-'; ?></td>
                <td><?php echo $row['matra_nama'] ?? '-'; ?></td>
                <td><?php echo $row['gender'] ?? '-'; ?></td>
                <td><?php echo $row['tempat_lahir'] ?? '-'; ?></td>
                <td><?php echo $row['tanggal_lahir'] ? date('d/m/Y', strtotime($row['tanggal_lahir'])) : '-'; ?></td>
                <td><?php echo $row['nama_satker_lama'] ?? '-'; ?></td>
                <td>'<?php echo $row['no_kep_lama'] ?? '-'; ?></td>
                <td>'<?php echo $row['no_sprint_lama'] ?? '-'; ?></td>
                <td><?php echo $row['nama_satker'] ?? '-'; ?></td> <!-- Satuan Baru -->
                <td>'<?php echo $row['no_kep'] ?? '-'; ?></td> <!-- No KEP Baru -->
                <td>'<?php echo $row['no_sprint'] ?? '-'; ?></td> <!-- No Sprint Baru -->
                <td>'<?php echo $row['nik'] ?? '-'; ?></td>
                <td><?php echo $row['alamat'] ?? '-'; ?></td>
                <td>'<?php echo $row['no_hp'] ?? '-'; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
<?php
catat_log($_SESSION['user_id'], 'EXPORT DATA', 'Admin export data personel ke Excel');
exit();
?>
