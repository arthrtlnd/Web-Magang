<?php
session_start();
require_once '../app/koneksi.php';
check_admin();

$success = '';
$error = '';

// Handle Import Excel
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    require_once '../vendor/autoload.php'; // PhpSpreadsheet
    
    $file = $_FILES['excel_file'];
    $allowed_ext = ['xlsx', 'xls'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed_ext)) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $imported = 0;
            $failed = 0;
            $users_created = 0;
            
            // Skip header row
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Validasi data
                if (!empty($row[0]) && !empty($row[1])) { // NRP dan Nama wajib
                    $nrp = clean_input($row[0]);
                    $nama = clean_input($row[1]);
                    $nik = clean_input($row[2] ?? null);
                    $tempat_lahir = clean_input($row[3] ?? '');
                    
                    // Handle Excel date
                    $tanggal_lahir = '';
                    if (!empty($row[4])) {
                        if (is_numeric($row[4])) {
                            // Convert Excel timestamp to date
                            $tanggal_lahir = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[4])->format('Y-m-d');
                        } else {
                            // Try to parse as string
                            $tanggal_lahir = date('Y-m-d', strtotime($row[4]));
                        }
                    } else {
                        $tanggal_lahir = null; // Default
                    }
                    
                    // Escape for SQL
                    $nrp_sql = escape_string($nrp);
                    $nama_sql = escape_string($nama);
                    $nik_sql = escape_string($nik);
                    $tempat_lahir_sql = escape_string($tempat_lahir);
                    $tanggal_lahir_sql = $tanggal_lahir ? "'$tanggal_lahir'" : "NULL";
                    
                    // Insert atau update
                    $check = mysqli_query($conn, "SELECT * FROM personel WHERE nrp = '$nrp_sql'");
                    if (mysqli_num_rows($check) > 0) {
                        // Update
                        $query = "UPDATE personel SET nama='$nama_sql', nik='$nik_sql', tempat_lahir='$tempat_lahir_sql', tanggal_lahir=$tanggal_lahir_sql WHERE nrp='$nrp_sql'";
                    } else {
                        // Insert
                        $query = "INSERT INTO personel (nrp, nama, nik, tempat_lahir, tanggal_lahir) VALUES ('$nrp_sql', '$nama_sql', '$nik_sql', '$tempat_lahir_sql', $tanggal_lahir_sql)";
                    }
                    
                    if (mysqli_query($conn, $query)) {
                        $imported++;
                        
                        // REVISI 5: Auto-create user
                        $check_user = mysqli_query($conn, "SELECT * FROM users WHERE nrp = '$nrp_sql'");
                        if (mysqli_num_rows($check_user) == 0) {
                            $default_password = password_hash('password', PASSWORD_DEFAULT);
                            $default_role = 'user';
                            
                            $insert_user_query = "INSERT INTO users (nrp, password, role) VALUES ('$nrp_sql', '$default_password', '$default_role')";
                            if (mysqli_query($conn, $insert_user_query)) {
                                $users_created++;
                            }
                        }
                    } else {
                        $failed++;
                    }
                }
            }
            
            $success = "Berhasil import $imported data personel, gagal $failed data. Berhasil membuat $users_created akun user baru.";
            catat_log($_SESSION['user_id'], 'IMPORT DATA', "Admin mengimport $imported data personel dan membuat $users_created user baru.");
        } catch (Exception $e) {
            $error = "Gagal import: " . $e->getMessage();
        }
    } else {
        $error = "Format file harus .xlsx atau .xls";
    }
}

// Query untuk mengambil semua data personel
$where_clause = "WHERE 1=1";
$search = '';

// REVISI 6: Hapus pencarian tanggal
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = clean_input($_GET['search']);
    $where_clause .= " AND (p.nrp LIKE '%$search%' OR p.nama LIKE '%$search%' OR pkt.sebutan LIKE '%$search%')";
}

$query = "SELECT p.*, u.role, g.gender, k.SEBUTAN as korp_sebutan, 
          pkt.sebutan as pangkat_sebutan, m.Nama as matra_nama, 
          s.nama_satker, s_lama.nama_satker as nama_satker_lama
          FROM personel p
          LEFT JOIN users u ON p.nrp = u.nrp
          LEFT JOIN gender g ON p.kd_gender = g.kd_gender
          LEFT JOIN korp k ON p.korp = k.KORPSID
          LEFT JOIN pangkat pkt ON p.pangkat = pkt.kd_pkt
          LEFT JOIN matra m ON p.matra = m.MTR
          LEFT JOIN satker s ON p.kd_satker = s.kd_satker
          LEFT JOIN satker s_lama ON p.satker_lama = s_lama.kd_satker
          $where_clause
          ORDER BY p.id DESC";

$result = mysqli_query($conn, $query);
$total_personel = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Personel - KORPRAPORT</title>
    <link rel="stylesheet" href="../assets/css/masterpersonel.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>KORPRAPORT</h2>
            <p>Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span>📊</span> Dashboard</a></li>
            <li><a href="masterpersonel.php" class="active"><span>👥</span> Master Data Personel</a></li>
            <li><a href="adduser.php"><span>➕</span> Tambah User</a></li>
            <li><a href="historylog.php"><span>📋</span> History Log</a></li>
            <li><a href="../auth/logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>Master Data Personel</h1>
            <div class="user-info">
                <span>Total Data: <strong><?php echo $total_personel; ?></strong></span>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <div class="header-actions">
                    <button class="btn btn-success" onclick="document.getElementById('importModal').style.display='block'">
                        📥 Import Excel
                    </button>
                    <a href="export_excel.php" class="btn btn-primary">📤 Export Excel</a>
                    <a href="adduser.php" class="btn btn-secondary">➕ Tambah User</a>
                </div>
            </div>
            
            <!-- REVISI 6: Hapus filter tanggal -->
            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Cari NRP, Nama, Pangkat..." value="<?php echo $search; ?>" style="min-width: 300px;">
                    </div>
                    <button type="submit" class="btn btn-search">🔍 Filter</button>
                    <a href="masterpersonel.php" class="btn btn-reset">🔄 Reset</a>
                </form>
            </div>
            
            <div class="table-container">
                <table id="personelTable">
                    <!-- REVISI 2: Ubah urutan kolom -->
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
                            <th>Role</th>
                            <th>Aksi</th>
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
                            <td><?php echo $row['nrp']; ?></td>
                            <td><?php echo $row['pangkat_sebutan'] ?? '-'; ?></td>
                            <td><?php echo $row['korp_sebutan'] ?? '-'; ?></td>
                            <td><?php echo $row['matra_nama'] ?? '-'; ?></td>
                            <td><?php echo $row['gender'] ?? '-'; ?></td>
                            <td><?php echo $row['tempat_lahir'] ?? '-'; ?></td>
                            <td><?php echo $row['tanggal_lahir'] ? date('d/m/Y', strtotime($row['tanggal_lahir'])) : '-'; ?></td>
                            <td><?php echo $row['nama_satker_lama'] ?? '-'; ?></td>
                            <td><?php echo $row['no_kep_lama'] ?? '-'; ?></td>
                            <td><?php echo $row['no_sprint_lama'] ?? '-'; ?></td>
                            <td><?php echo $row['nama_satker'] ?? '-'; ?></td> <!-- Satuan Baru -->
                            <td><?php echo $row['no_kep'] ?? '-'; ?></td> <!-- No KEP Baru -->
                            <td><?php echo $row['no_sprint'] ?? '-'; ?></td> <!-- No Sprint Baru -->
                            <td><?php echo $row['nik'] ?? '-'; ?></td>
                            <td><?php echo $row['alamat'] ?? '-'; ?></td>
                            <td><?php echo $row['no_hp'] ?? '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($row['role'] ?? 'user'); ?>">
                                    <?php echo strtoupper($row['role'] ?? 'USER'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edituser.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                    <a href="deleteuser.php?id=<?php echo $row['id']; ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($total_personel == 0): ?>
                        <tr>
                            <td colspan="20" style="text-align: center; padding: 30px; color: #999;">
                                Tidak ada data yang ditemukan
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Import Modal -->
    <div id="importModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('importModal').style.display='none'">&times;</span>
            <h2>Import Data dari Excel</h2>
            <p>Format Excel: NRP | Nama | NIK | Tempat Lahir | Tanggal Lahir (YYYY-MM-DD)</p>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Pilih File Excel (.xlsx atau .xls)</label>
                    <input type="file" name="excel_file" accept=".xlsx,.xls" required>
                </div>
                <button type="submit" name="import_excel" class="btn btn-primary">Upload & Import</button>
            </form>
            <div style="margin-top: 15px;">
                <a href="../templates/template_import.xlsx" class="btn btn-secondary">📥 Download Template Excel</a>
            </div>
        </div>
    </div>
    
    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('importModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

