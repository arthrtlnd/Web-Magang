<?php
session_start();
require_once '../app/koneksi.php';
check_admin();

$success = '';
$error = '';

// Ambil ID dari URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = clean_input($_GET['id']);

// Ambil data personel
$query = "SELECT p.*, u.role FROM personel p 
          LEFT JOIN users u ON p.nrp = u.nrp 
          WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: dashboard.php");
    exit();
}

$personel = mysqli_fetch_assoc($result);

// Ambil data untuk dropdown
$korp_list = mysqli_query($conn, "SELECT * FROM korp ORDER BY SEBUTAN");
$pangkat_list = mysqli_query($conn, "SELECT * FROM pangkat ORDER BY kd_pkt");
$matra_list = mysqli_query($conn, "SELECT * FROM matra ORDER BY MTR");
$satker_list = mysqli_query($conn, "SELECT * FROM satker ORDER BY nama_satker");
$gender_list = mysqli_query($conn, "SELECT * FROM gender");

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean_input($_POST['nama']);
    $tempat_lahir = clean_input($_POST['tempat_lahir']);
    $tanggal_lahir = clean_input($_POST['tanggal_lahir']);
    $korp = !empty($_POST['korp']) ? clean_input($_POST['korp']) : null;
    $pangkat = !empty($_POST['pangkat']) ? clean_input($_POST['pangkat']) : null;
    $matra = !empty($_POST['matra']) ? clean_input($_POST['matra']) : null;
    $kd_satker = !empty($_POST['kd_satker']) ? clean_input($_POST['kd_satker']) : null; // "Baru"
    $alamat = !empty($_POST['alamat']) ? clean_input($_POST['alamat']) : null;
    $nik = !empty($_POST['nik']) ? clean_input($_POST['nik']) : null;
    $no_hp = !empty($_POST['no_hp']) ? clean_input($_POST['no_hp']) : null;
    $no_kep = !empty($_POST['no_kep']) ? clean_input($_POST['no_kep']) : null; // "Baru"
    $no_sprint = !empty($_POST['no_sprint']) ? clean_input($_POST['no_sprint']) : null; // "Baru"
    $kd_gender = !empty($_POST['kd_gender']) ? clean_input($_POST['kd_gender']) : null;

    // Field baru
    $satker_lama = !empty($_POST['satker_lama']) ? clean_input($_POST['satker_lama']) : null;
    $no_kep_lama = !empty($_POST['no_kep_lama']) ? clean_input($_POST['no_kep_lama']) : null;
    $no_sprint_lama = !empty($_POST['no_sprint_lama']) ? clean_input($_POST['no_sprint_lama']) : null;
    
    if (empty($nama) || empty($tempat_lahir) || empty($tanggal_lahir)) {
        $error = "Nama, Tempat Lahir, dan Tanggal Lahir harus diisi!";
    } else {
        $update_query = "UPDATE personel SET 
                        nama = ?, tempat_lahir = ?, tanggal_lahir = ?, 
                        korp = ?, pangkat = ?, matra = ?, kd_satker = ?,
                        alamat = ?, nik = ?, no_hp = ?, no_kep = ?, no_sprint = ?, kd_gender = ?,
                        satker_lama = ?, no_kep_lama = ?, no_sprint_lama = ?
                        WHERE id = ?";
        
        $stmt2 = mysqli_prepare($conn, $update_query);
        // 16 field (s) + 1 id (i) = ssssssssssssssssi
        mysqli_stmt_bind_param($stmt2, "ssssssssssssssssi", 
            $nama, $tempat_lahir, $tanggal_lahir, $korp, $pangkat, 
            $matra, $kd_satker, $alamat, $nik, $no_hp, $no_kep, $no_sprint, $kd_gender,
            $satker_lama, $no_kep_lama, $no_sprint_lama, $id);
        
        if (mysqli_stmt_execute($stmt2)) {
            catat_log($_SESSION['user_id'], 'UPDATE DATA', 'Admin mengupdate data personel NRP: ' . $personel['nrp']);
            $success = "Data berhasil diupdate!";
            
            // Refresh data
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $personel = mysqli_fetch_assoc($result);
        } else {
            $error = "Gagal mengupdate data: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt2);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Personel - KORPRAPORT</title>
    <!-- REVISI 4: Ganti CSS -->
    <link rel="stylesheet" href="../assets/css/masterpersonel.css"> <!-- Pakai CSS master -->
    <link rel="stylesheet" href="../assets/css/edituser.css"> <!-- CSS spesifik -->
    <style>
        /* Override beberapa style masterpersonel.css jika perlu */
        .main-content {
            padding-top: 30px;
        }
        .card {
            max-width: 800px; /* Sesuaikan lebar card form */
            margin: 0 auto; /* tengahkan card */
        }
        /* Styling tambahan untuk field baru */
        .data-group {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            background: #fcfcfc;
            width: 100%; /* Pastikan mengisi .form-row */
        }
        .data-group-title {
            font-weight: 600;
            color: #555;
            margin-bottom: 15px;
        }
        /* Pastikan form-row di .card menangani 2 .data-group */
        .card .form-row {
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    </style>
</head>
<body>
    <!-- REVISI 4: Ganti Navbar dengan Sidebar -->
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
    
    <!-- REVISI 4: Bungkus konten dengan main-content -->
    <div class="main-content">
        <div class="top-bar">
            <h1>Edit Data Personel</h1>
            <div class="user-info">
                 <a href="masterpersonel.php" class="btn btn-secondary">Kembali ke Master Data</a>
            </div>
        </div>

        <div class="card">
            
            <div class="info-box">
                <strong>NRP:</strong> <?php echo $personel['nrp']; ?> | 
                <strong>Role:</strong> <?php echo strtoupper($personel['role'] ?? 'USER'); ?>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Data Pribadi -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap *</label>
                        <input type="text" id="nama" name="nama" required 
                               value="<?php echo $personel['nama']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" id="nik" name="nik" maxlength="16"
                               value="<?php echo $personel['nik'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tempat_lahir">Tempat Lahir *</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" required
                               value="<?php echo $personel['tempat_lahir'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal_lahir">Tanggal Lahir *</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" required
                               value="<?php echo $personel['tanggal_lahir'] ?? ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="no_hp">No. HP</label>
                        <input type="text" id="no_hp" name="no_hp" maxlength="15"
                               value="<?php echo $personel['no_hp'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="kd_gender">Jenis Kelamin</label>
                        <select id="kd_gender" name="kd_gender">
                            <option value="">-- Pilih --</option>
                            <?php 
                            mysqli_data_seek($gender_list, 0);
                            while($row = mysqli_fetch_assoc($gender_list)): ?>
                                <option value="<?php echo $row['kd_gender']; ?>"
                                    <?php echo (isset($personel['kd_gender']) && $personel['kd_gender'] == $row['kd_gender']) ? 'selected' : ''; ?>>
                                    <?php echo $row['gender']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Data Kepangkatan -->
                <div class="form-row">                   
                    <div class="form-group">
                        <label for="pangkat">Pangkat</label>
                        <select id="pangkat" name="pangkat">
                            <option value="">-- Pilih Pangkat --</option>
                            <?php 
                            mysqli_data_seek($pangkat_list, 0);
                            while($row = mysqli_fetch_assoc($pangkat_list)): ?>
                                <option value="<?php echo $row['kd_pkt']; ?>"
                                    <?php echo ($personel['pangkat'] == $row['kd_pkt']) ? 'selected' : ''; ?>>
                                    <?php echo $row['sebutan']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="korp">Korp</label>
                        <select id="korp" name="korp">
                            <option value="">-- Pilih Korp --</option>
                            <?php 
                            mysqli_data_seek($korp_list, 0);
                            while($row = mysqli_fetch_assoc($korp_list)): ?>
                                <option value="<?php echo $row['KORPSID']; ?>"
                                    <?php echo ($personel['korp'] == $row['KORPSID']) ? 'selected' : ''; ?>>
                                    <?php echo $row['SEBUTAN']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="matra">Matra</label>
                    <select id="matra" name="matra">
                        <option value="">-- Pilih Matra --</option>
                        <?php 
                        mysqli_data_seek($matra_list, 0);
                        while($row = mysqli_fetch_assoc($matra_list)): ?>
                            <option value="<?php echo $row['MTR']; ?>"
                                <?php echo ($personel['matra'] == $row['MTR']) ? 'selected' : ''; ?>>
                                <?php echo $row['Nama']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- REVISI 1: Field Baru (Lama vs Baru) -->
                <div class="form-row">
                    <div class="data-group">
                        <div class="data-group-title">Data Lama</div>
                        <div class="form-group">
                            <!-- REVISI: Diubah ke dropdown -->
                            <label for="satker_lama">Satuan Kerja Lama</label>
                            <select id="satker_lama" name="satker_lama">
                                <option value="">-- Pilih Satker --</option>
                                <?php 
                                mysqli_data_seek($satker_list, 0); // Reset pointer
                                while($row = mysqli_fetch_assoc($satker_list)): ?>
                                    <option value="<?php echo $row['kd_satker']; ?>"
                                        <?php echo (isset($personel['satker_lama']) && $personel['satker_lama'] == $row['kd_satker']) ? 'selected' : ''; ?>>
                                        <?php echo $row['nama_satker']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="no_kep_lama">No. KEP Lama</label>
                            <input type="text" id="no_kep_lama" name="no_kep_lama" maxlength="20"
                                   value="<?php echo $personel['no_kep_lama'] ?? ''; ?>" placeholder="Opsional">
                        </div>
                        <div class="form-group">
                            <label for="no_sprint_lama">No. Sprint Lama</label>
                            <input type="text" id="no_sprint_lama" name="no_sprint_lama" maxlength="20"
                                   value="<?php echo $personel['no_sprint_lama'] ?? ''; ?>" placeholder="Opsional">
                        </div>
                    </div>
                    
                    <div class="data-group">
                        <div class="data-group-title">Data Baru</div>
                        <div class="form-group">
                            <label for="kd_satker">Satuan Kerja Baru</label>
                            <select id="kd_satker" name="kd_satker">
                                <option value="">-- Pilih Satker --</option>
                                <?php 
                                mysqli_data_seek($satker_list, 0);
                                while($row = mysqli_fetch_assoc($satker_list)): ?>
                                    <option value="<?php echo $row['kd_satker']; ?>"
                                        <?php echo ($personel['kd_satker'] == $row['kd_satker']) ? 'selected' : ''; ?>>
                                        <?php echo $row['nama_satker']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="no_kep">No. KEP Baru</label>
                            <input type="text" id="no_kep" name="no_kep" maxlength="20"
                                   value="<?php echo $personel['no_kep'] ?? ''; ?>" placeholder="Opsional">
                        </div>
                        <div class="form-group">
                            <label for="no_sprint">No. Sprint Baru</label>
                            <input type="text" id="no_sprint" name="no_sprint" maxlength="20"
                                   value="<?php echo $personel['no_sprint'] ?? ''; ?>" placeholder="Opsional">
                        </div>
                    </div>
                </div>

                <!-- Alamat -->
                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat"><?php echo $personel['alamat'] ?? ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <!-- Tombol kembali dipindah ke top-bar -->
            </form>
        </div>
    </div>
    
    <script src="../assets/js/edituser.js"></script>
    <script>
        // Tambahkan ID baru ke script formatting dari edituser.js (jika belum ada)
        // Format NIK (hanya angka)
        document.getElementById('nik').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Format No HP (hanya angka)
        document.getElementById('no_hp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Format No KEP (hanya angka)
        document.getElementById('no_kep').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Format No Sprint (hanya angka)
        document.getElementById('no_sprint').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Script untuk field baru
        document.getElementById('no_kep_lama').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        document.getElementById('no_sprint_lama').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Filter Korp berdasarkan Matra
        const korpByMatra = {
            '1': ['A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1', 'K1', 'M1', 'N1', 'P1', 'Q1', 'R1', 'X1', 'Y1', 'Z1', 'A3'], // TNI AD
            '2': ['12', '22', '32', '42', '52', '62', '72', '82'], // TNI AL
            '3': ['13', '23', '33', '43', '53', '63', '73', '83', '93', 'A3'], // TNI AU
            '0': [] // PNS - tidak punya korp
        };
        
        const matraSelect = document.getElementById('matra');
        const korpSelect = document.getElementById('korp');
        const allKorpOptions = Array.from(korpSelect.options);
        
        function filterKorp() {
            const selectedMatra = matraSelect.value;
            // Ambil nilai korp saat ini SEBELUM dropdown di-reset
            const currentKorp = korpSelect.value;
            
            // Hapus semua option kecuali yang pertama
            korpSelect.innerHTML = '<option value="">-- Pilih Korp --</option>';
            
            if (selectedMatra === '0') {
                // Jika PNS, disable dropdown korp
                korpSelect.disabled = true;
                korpSelect.value = '';
            } else if (selectedMatra && korpByMatra[selectedMatra]) {
                // Enable dropdown
                korpSelect.disabled = false;
                
                // Tambahkan option yang sesuai dengan matra
                allKorpOptions.forEach(option => {
                    if (option.value && korpByMatra[selectedMatra].includes(option.value)) {
                        const newOption = option.cloneNode(true);
                        // Set 'selected' jika nilainya sama dengan nilai sebelum di-reset
                        if (option.value === currentKorp) {
                            newOption.selected = true;
                        }
                        korpSelect.appendChild(newOption);
                    }
                });
            } else {
                // Jika matra tidak dipilih, enable dan tampilkan semua korp
                korpSelect.disabled = false;
                allKorpOptions.forEach(option => {
                    // Jangan tambahkan placeholder (value="") lagi
                    if (option.value) { 
                        const newOption = option.cloneNode(true);
                         // Set 'selected' jika nilainya sama dengan nilai sebelum di-reset
                        if (option.value === currentKorp) {
                            newOption.selected = true;
                        }
                        korpSelect.appendChild(newOption);
                    }
                });
            }
        }
        
        // Event listener untuk matra
        matraSelect.addEventListener('change', filterKorp);
        
        // Filter saat halaman pertama kali dimuat
        // Panggil filterKorp() setelah memastikan allKorpOptions sudah terisi penuh
        // (Biasanya sudah, tapi ini praktik aman)
        window.addEventListener('DOMContentLoaded', (event) => {
            filterKorp();
        });
    </script>
</body>
</html>

