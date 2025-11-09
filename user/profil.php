<?php
session_start();
require_once '../app/koneksi.php';
check_user();

$nrp = $_SESSION['nrp'];
$success = '';
$error = '';

// Cek apakah ada pesan sukses dari redirect
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Hapus pesan agar tidak muncul lagi
}

// Cek apakah data personel sudah ada
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
          WHERE p.nrp = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $nrp);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$personel = mysqli_fetch_assoc($result);

$is_new = ($personel == null);
$edit_mode = $is_new; // Auto edit mode jika data kosong

// Ambil data untuk dropdown
$korp_list = mysqli_query($conn, "SELECT * FROM korp ORDER BY SEBUTAN");
$pangkat_list = mysqli_query($conn, "SELECT * FROM pangkat ORDER BY kd_pkt");
$matra_list = mysqli_query($conn, "SELECT * FROM matra ORDER BY MTR");
$satker_list = mysqli_query($conn, "SELECT * FROM satker ORDER BY nama_satker");
$gender_list = mysqli_query($conn, "SELECT * FROM gender");

// Handle upload foto profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_profil'])) {
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $file = $_FILES['foto_profil'];
    
    if ($file['error'] == 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_ext)) {
            if ($file['size'] <= 2000000) {
                $upload_dir = '../uploads/profile/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = $nrp . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    if (!$is_new && !empty($personel['foto_profil']) && file_exists($upload_dir . $personel['foto_profil'])) {
                        unlink($upload_dir . $personel['foto_profil']);
                    }
                    
                    if ($is_new) {
                        // Terapkan Pola PRG
                        $_SESSION['success_message'] = "Foto berhasil diupload! Lengkapi data diri Anda.";
                    } else {
                        $update_foto = "UPDATE personel SET foto_profil = ? WHERE nrp = ?";
                        $stmt2 = mysqli_prepare($conn, $update_foto);
                        mysqli_stmt_bind_param($stmt2, "ss", $new_filename, $nrp);
                        mysqli_stmt_execute($stmt2);
                        catat_log($_SESSION['user_id'], 'UPDATE FOTO', 'User mengupdate foto profil');
                        
                        // Terapkan Pola PRG
                        $_SESSION['success_message'] = "Foto profil berhasil diupdate!";
                    }
                    
                    // Redirect setelah upload foto sukses
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;

                } else {
                    $error = "Gagal mengupload foto!";
                }
            } else {
                $error = "Ukuran file maksimal 2MB!";
            }
        } else {
            $error = "Format file harus JPG, JPEG, PNG, atau GIF!";
        }
    }
}

// Handle save data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_data'])) {
    
    $nama = clean_input($_POST['nama']);
    $tempat_lahir = clean_input($_POST['tempat_lahir']);
    $tanggal_lahir = clean_input($_POST['tanggal_lahir']);
    $korp = !empty($_POST['korp']) ? clean_input($_POST['korp']) : null;
    $pangkat = !empty($_POST['pangkat']) ? clean_input($_POST['pangkat']) : null;
    $matra = !empty($_POST['matra']) ? clean_input($_POST['matra']) : null;
    $kd_satker = !empty($_POST['kd_satker']) ? clean_input($_POST['kd_satker']) : null; // Ini "Baru"
    $alamat = !empty($_POST['alamat']) ? clean_input($_POST['alamat']) : null;
    $nik = !empty($_POST['nik']) ? clean_input($_POST['nik']) : null;
    $no_hp = !empty($_POST['no_hp']) ? clean_input($_POST['no_hp']) : null;
    $no_kep = !empty($_POST['no_kep']) ? clean_input($_POST['no_kep']) : null; // Ini "Baru"
    $no_sprint = !empty($_POST['no_sprint']) ? clean_input($_POST['no_sprint']) : null; // Ini "Baru"
    $kd_gender = !empty($_POST['kd_gender']) ? clean_input($_POST['kd_gender']) : null;

    // Field baru
    $satker_lama = !empty($_POST['satker_lama']) ? clean_input($_POST['satker_lama']) : null;
    $no_kep_lama = !empty($_POST['no_kep_lama']) ? clean_input($_POST['no_kep_lama']) : null;
    $no_sprint_lama = !empty($_POST['no_sprint_lama']) ? clean_input($_POST['no_sprint_lama']) : null;

    
    // Validasi field wajib
    if (empty($nama) || empty($tempat_lahir) || empty($tanggal_lahir)) {
        $error = "Nama Lengkap, Tempat Lahir, dan Tanggal Lahir wajib diisi!";
        $edit_mode = true;
    } else {
        if ($is_new) {
            // Insert data baru
            $insert_query = "INSERT INTO personel (nrp, nama, tempat_lahir, tanggal_lahir, korp, pangkat, matra, kd_satker, alamat, nik, no_hp, no_kep, no_sprint, kd_gender, satker_lama, no_kep_lama, no_sprint_lama) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_save = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt_save, "sssssssssssssssss", 
                $nrp, $nama, $tempat_lahir, $tanggal_lahir, $korp, $pangkat, 
                $matra, $kd_satker, $alamat, $nik, $no_hp, $no_kep, $no_sprint, $kd_gender,
                $satker_lama, $no_kep_lama, $no_sprint_lama);
            
            if (mysqli_stmt_execute($stmt_save)) {
                catat_log($_SESSION['user_id'], 'TAMBAH DATA DIRI', 'User ' . $nrp . ' melengkapi data diri');
                
                // Terapkan Pola PRG
                $_SESSION['success_message'] = "Data berhasil disimpan!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;

            } else {
                $error = "Gagal menyimpan data! " . mysqli_error($conn);
                $edit_mode = true;
            }
        } else {
            // Update data
            $update_query = "UPDATE personel SET 
                             nama = ?, tempat_lahir = ?, tanggal_lahir = ?, 
                             korp = ?, pangkat = ?, matra = ?, kd_satker = ?,
                             alamat = ?, nik = ?, no_hp = ?, no_kep = ?, no_sprint = ?, kd_gender = ?,
                             satker_lama = ?, no_kep_lama = ?, no_sprint_lama = ?
                             WHERE nrp = ?";
            
            $stmt_save = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt_save, "sssssssssssssssss", 
                $nama, $tempat_lahir, $tanggal_lahir, $korp, $pangkat, 
                $matra, $kd_satker, $alamat, $nik, $no_hp, $no_kep, $no_sprint, $kd_gender,
                $satker_lama, $no_kep_lama, $no_sprint_lama, $nrp);
            
            if (mysqli_stmt_execute($stmt_save)) {
                catat_log($_SESSION['user_id'], 'UPDATE DATA DIRI', 'User ' . $nrp . ' mengupdate data diri');
                
                // Terapkan Pola PRG
                $_SESSION['success_message'] = "Data berhasil diupdate!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;

            } else {
                $error = "Gagal mengupdate data! " . mysqli_error($conn);
                $edit_mode = true;
            }
        }
    }
}

$foto_path = '../uploads/profile/' . ($personel['foto_profil'] ?? '');
if (empty($personel['foto_profil'] ?? true) || !file_exists($foto_path)) {
    $foto_path = 'https://via.placeholder.com/200?text=' . urlencode($is_new ? 'User' : substr($personel['nama'], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - KORPRAPORT</title>
    <style>
        /* CSS Anda yang benar ada di sini */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
        }
        
        .header {
            background: linear-gradient(to right, #E33333, #7D1C1C);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header img {
            width: 70px;
            height: 70px;
        }
        
        .header-title {
            font-size: 1rem;
            font-weight: bold;
            background: linear-gradient(to right, #FFB700, #F87400);
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .navbar-menu {
            display: flex;
            gap: 20px;
            align-items: center;
            background: linear-gradient(to right, #FFB700, #F87400);
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
            padding-left: 15rem;
        }
        
        .navbar-menu a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            color: #7d1c1c;
        }
        
        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .profile-photo {
            position: relative;
        }
        
        .profile-photo img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #7d1c1c;
        }
        
        .upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #7d1c1c;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
        }
        
        .upload-btn input {
            display: none;
        }
        
        .profile-info h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .profile-info .subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-editable input,
        .form-editable select,
        .form-editable textarea {
            background: white;
            border: 2px solid #ddd;
        }
        
        .form-readonly input,
        .form-readonly select,
        .form-readonly textarea {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            pointer-events: none;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-title {
            color: #7d1c1c;
            font-size: 18px;
            font-weight: 600;
        }
        
        .btn-edit {
            background: #ffa502;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-edit:hover {
            background: #ff8c00;
            transform: translateY(-2px);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-save {
            background: #2ed573;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            background: #26bf63;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .required-field::after {
            content: " *";
            color: #ff4757;
        }
        
        .first-login-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .first-login-banner h3 {
            margin-bottom: 10px;
        }

        /* Styling untuk field baru */
        .data-group {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        .data-group-title {
            font-weight: 600;
            color: #555;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header">
            <img src="../assets/img/logo-2.png" alt="logo">
            <div class="header-title">
                <h2>SISTEM INFORMASI PERSONEL</h2>
                <h2>MARKAS BESAR TENTARA NASIONAL INDONESIA</h2>
            </div>
            <div class="navbar-menu">
                <span>Selamat datang, <?php echo $is_new ? 'User' : explode(' ', $personel['nama'])[0]; ?></span>
                <a href="../auth/logout.php">Keluar</a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php if ($is_new): ?>
        <div class="first-login-banner">
            <h3>🎉 Selamat Datang!</h3>
            <p>Silakan lengkapi data diri Anda untuk melanjutkan.</p>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-photo">
                <img src="<?php echo $foto_path; ?>" alt="Foto Profil" id="previewFoto">
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <label class="upload-btn" for="foto_profil" title="Upload Foto">
                        📷
                        <input type="file" name="foto_profil" id="foto_profil" accept="image/*" onchange="previewImage(this); document.getElementById('uploadForm').submit();">
                    </label>
                </form>
            </div>
            
            <div class="profile-info">
                <h2><?php echo $is_new ? 'User Baru' : $personel['nama']; ?></h2>
                <div class="subtitle">
                    <?php if (!$is_new): ?>
                    <?php echo $personel['pangkat_sebutan'] ?? 'Pangkat'; ?> 
                    <?php echo $personel['korp_sebutan'] ? '(' . $personel['korp_sebutan'] . ')' : ''; ?> - 
                    <?php endif; ?>
                    NRP: <?php echo $nrp; ?>
                </div>
            </div>
        </div>
        
        <form method="POST" action="" id="profileForm" class="<?php echo $edit_mode ? 'form-editable' : 'form-readonly'; ?>" onsubmit="return confirm('Apakah Anda yakin data yang dimasukkan sudah benar?');">
            
            <!-- REVISI 1: Data Kepangkatan Pindah ke Atas -->
            <div class="card">
                <div class="section-header">
                    <h3 class="section-title">Data Kepangkatan</h3>
                    <?php if (!$edit_mode && !$is_new): ?>
                    <button type="button" class="btn-edit" onclick="enableEdit()">Edit</button>
                    <?php endif; ?>
                </div>
                
                <div class="info-grid">
                    <div class="form-group">
                        <label>Pangkat</label>
                        <select name="pangkat" <?php echo !$edit_mode ? 'disabled' : ''; ?>>
                            <option value="">-- Pilih Pangkat --</option>
                            <?php 
                            mysqli_data_seek($pangkat_list, 0); // Reset pointer
                            while($row = mysqli_fetch_assoc($pangkat_list)): ?>
                                <option value="<?php echo $row['kd_pkt']; ?>"
                                    <?php echo (isset($personel['pangkat']) && $personel['pangkat'] == $row['kd_pkt']) ? 'selected' : ''; ?>>
                                    <?php echo $row['sebutan']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Korp</label>
                        <select name="korp" id="korp" <?php echo !$edit_mode ? 'disabled' : ''; ?>>
                            <option value="">-- Pilih Korp --</option>
                            <?php 
                            mysqli_data_seek($korp_list, 0); // Reset pointer
                            while($row = mysqli_fetch_assoc($korp_list)): ?>
                                <option value="<?php echo $row['KORPSID']; ?>"
                                    <?php echo (isset($personel['korp']) && $personel['korp'] == $row['KORPSID']) ? 'selected' : ''; ?>>
                                    <?php echo $row['SEBUTAN']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Matra</label>
                    <select name="matra" id="matra" <?php echo !$edit_mode ? 'disabled' : ''; ?>>
                        <option value="">-- Pilih Matra --</option>
                        <?php 
                        mysqli_data_seek($matra_list, 0); // Reset pointer
                        while($row = mysqli_fetch_assoc($matra_list)): ?>
                            <option value="<?php echo $row['MTR']; ?>"
                                <?php echo (isset($personel['matra']) && $personel['matra'] == $row['MTR']) ? 'selected' : ''; ?>>
                                <?php echo $row['Nama']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- REVISI 1: Field Baru (Lama vs Baru) -->
                <div class="info-grid">
                    <div class="data-group">
                        <div class="data-group-title">Data Lama</div>
                        <div class="form-group">
                            <!-- REVISI: Diubah ke dropdown -->
                            <label>Satuan Kerja Lama</label>
                            <select name="satker_lama" <?php echo !$edit_mode ? 'disabled' : ''; ?>>
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
                            <label>No. KEP Lama</label>
                            <input type="text" name="no_kep_lama" maxlength="20" placeholder="Opsional" value="<?php echo $personel['no_kep_lama'] ?? ''; ?>" <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>No. Sprint Lama</label>
                            <input type="text" name="no_sprint_lama" maxlength="20" placeholder="Opsional" value="<?php echo $personel['no_sprint_lama'] ?? ''; ?>" <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="data-group">
                        <div class="data-group-title">Data Baru</div>
                        <div class="form-group">
                            <label>Satuan Kerja Baru</label>
                            <select name="kd_satker" <?php echo !$edit_mode ? 'disabled' : ''; ?>>
                                <option value="">-- Pilih Satker --</option>
                                <?php 
                                mysqli_data_seek($satker_list, 0); // Reset pointer
                                while($row = mysqli_fetch_assoc($satker_list)): ?>
                                    <option value="<?php echo $row['kd_satker']; ?>"
                                        <?php echo (isset($personel['kd_satker']) && $personel['kd_satker'] == $row['kd_satker']) ? 'selected' : ''; ?>>
                                        <?php echo $row['nama_satker']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No. KEP Baru</label>
                            <input type="text" name="no_kep" maxlength="20" placeholder="Opsional" value="<?php echo $personel['no_kep'] ?? ''; ?>" <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>No. Sprint Baru</label>
                            <input type="text" name="no_sprint" maxlength="20" placeholder="Opsional" value="<?php echo $personel['no_sprint'] ?? ''; ?>" <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REVISI 1: Data Pribadi Pindah ke Bawah -->
            <div class="card">
                <div class="section-header">
                    <h3 class="section-title">Data Pribadi</h3>
                </div>
                
                <div class="form-group">
                    <label <?php echo $edit_mode ? 'class="required-field"' : ''; ?>>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?php echo $personel['nama'] ?? ''; ?>" <?php echo $edit_mode ? 'required' : 'readonly'; ?>>
                </div>
                
                <div class="info-grid">
                    <div class="form-group">
                        <label>NIK</label>
                        <input type="text" name="nik" maxlength="16" value="<?php echo $personel['nik'] ?? ''; ?>" <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="kd_gender" <?php echo !$edit_mode ? 'disabled' : ''; ?>>
                            <option value="">-- Pilih --</option>
                            <?php 
                            mysqli_data_seek($gender_list, 0); // Reset pointer
                            while($row = mysqli_fetch_assoc($gender_list)): ?>
                                <option value="<?php echo $row['kd_gender']; ?>"
                                    <?php echo (isset($personel['kd_gender']) && $personel['kd_gender'] == $row['kd_gender']) ? 'selected' : ''; ?>>
                                    <?php echo $row['gender']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="info-grid">
                    <div class="form-group">
                        <label <?php echo $edit_mode ? 'class="required-field"' : ''; ?>>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="<?php echo $personel['tanggal_lahir'] ?? ''; ?>" <?php echo $edit_mode ? 'required' : 'readonly'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label <?php echo $edit_mode ? 'class="required-field"' : ''; ?>>Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="<?php echo $personel['tempat_lahir'] ?? ''; ?>" <?php echo $edit_mode ? 'required' : 'readonly'; ?>>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="no_hp" maxlength="15" value="<?php echo $personel['no_hp'] ?? ''; ?>" <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                </div>
            </div>
            
            <div class="card">
                <h3 class="section-title">Alamat</h3>
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" rows="3" <?php echo !$edit_mode ? 'readonly' : ''; ?>><?php echo $personel['alamat'] ?? ''; ?></textarea>
                </div>
            </div>
            
            <?php if ($edit_mode): ?>
            <div class="action-buttons">
                <button type="submit" name="save_data" class="btn-save">Simpan</button>
                <?php if (!$is_new): ?>
                <button type="button" class="btn-cancel" onclick="cancelEdit()">Batal</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewFoto').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function enableEdit() {
            document.getElementById('profileForm').classList.remove('form-readonly');
            document.getElementById('profileForm').classList.add('form-editable');
            
            // Enable all inputs
            document.querySelectorAll('#profileForm input, #profileForm select, #profileForm textarea').forEach(el => {
                el.removeAttribute('readonly');
                el.removeAttribute('disabled');
            });
            
            // Show action buttons
            var actionButtons = document.querySelector('.action-buttons');
            if (!actionButtons) {
                var buttons = document.createElement('div');
                buttons.className = 'action-buttons';
                buttons.innerHTML = '<button type="submit" name="save_data" class="btn-save">Simpan</button><button type="button" class="btn-cancel" onclick="cancelEdit()">Batal</button>';
                document.getElementById('profileForm').appendChild(buttons);
            }
            
            // Hide edit button
            document.querySelectorAll('.btn-edit').forEach(btn => btn.style.display = 'none');
            
            // Add required labels
            document.querySelectorAll('label').forEach(label => {
                if (label.textContent.includes('Nama Lengkap') || label.textContent.includes('Tempat Lahir') || label.textContent.includes('Tanggal Lahir')) {
                    label.classList.add('required-field');
                }
            });
        }
        
        function cancelEdit() {
            location.reload();
        }
        
        // Filter Korp by Matra
        const korpByMatra = {
            '1': ['A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1', 'K1', 'M1', 'N1', 'P1', 'Q1', 'R1', 'X1', 'Y1', 'Z1', 'A3'],
            '2': ['12', '22', '32', '42', '52', '62', '72', '82'],
            '3': ['13', '23', '33', '43', '53', '63', '73', '83', '93', 'A3'],
            '0': []
        };
        
        const matraSelect = document.getElementById('matra');
        const korpSelect = document.getElementById('korp');
        const allKorpOptions = Array.from(korpSelect.options);
        
        function filterKorp() {
            const selectedMatra = matraSelect.value;
            const currentKorp = korpSelect.value;
            
            korpSelect.innerHTML = '<option value="">-- Pilih Korp --</option>';
            
            if (selectedMatra === '0') {
                korpSelect.disabled = true;
                korpSelect.value = '';
            } else if (selectedMatra && korpByMatra[selectedMatra]) {
                korpSelect.disabled = false;
                allKorpOptions.forEach(option => {
                    if (option.value && korpByMatra[selectedMatra].includes(option.value)) {
                        const newOption = option.cloneNode(true);
                        if (option.value === currentKorp) {
                            newOption.selected = true;
                        }
                        korpSelect.appendChild(newOption);
                    }
                });
            } else {
                korpSelect.disabled = false;
                allKorpOptions.forEach(option => {
                    if (option.value) {
                        const newOption = option.cloneNode(true);
                        if (option.value === currentKorp) {
                            newOption.selected = true;
                        }
                        korpSelect.appendChild(newOption);
                    }
                });
            }
        }
        
        if (matraSelect) {
            matraSelect.addEventListener('change', filterKorp);
            filterKorp();
        }
        
        // Format number inputs
        document.querySelectorAll('input[name="nik"], input[name="no_hp"], input[name="no_kep"], input[name="no_sprint"], input[name="no_kep_lama"], input[name="no_sprint_lama"]').forEach(input => {
            input.addEventListener('input', function(e) {
                // Hapus semua kecuali angka
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    </script>
</body>
</html>

