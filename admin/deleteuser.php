<?php
session_start();
require_once '../app/koneksi.php';
check_admin();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = clean_input($_GET['id']);

// Ambil data personel untuk log
$query = "SELECT p.nrp, p.nama FROM personel p WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$personel = mysqli_fetch_assoc($result);

if ($personel) {
    $nrp = $personel['nrp'];
    
    // Hapus foto profil jika ada
    $foto_query = "SELECT foto_profil FROM personel WHERE id = ?";
    $stmt_foto = mysqli_prepare($conn, $foto_query);
    mysqli_stmt_bind_param($stmt_foto, "i", $id);
    mysqli_stmt_execute($stmt_foto);
    $result_foto = mysqli_stmt_get_result($stmt_foto);
    $foto_data = mysqli_fetch_assoc($result_foto);
    
    if ($foto_data && !empty($foto_data['foto_profil'])) {
        $foto_path = '../uploads/profile/' . $foto_data['foto_profil'];
        if (file_exists($foto_path)) {
            unlink($foto_path);
        }
    }
    
    // Hapus data personel
    $delete_personel = "DELETE FROM personel WHERE id = ?";
    $stmt_del_personel = mysqli_prepare($conn, $delete_personel);
    mysqli_stmt_bind_param($stmt_del_personel, "i", $id);
    mysqli_stmt_execute($stmt_del_personel);
    
    // Hapus user
    $delete_user = "DELETE FROM users WHERE nrp = ?";
    $stmt_del_user = mysqli_prepare($conn, $delete_user);
    mysqli_stmt_bind_param($stmt_del_user, "s", $nrp);
    mysqli_stmt_execute($stmt_del_user);
    
    // Catat log
    catat_log($_SESSION['user_id'], 'HAPUS DATA', 'Admin menghapus data personel NRP: ' . $nrp);
}

header("Location: dashboard.php");
exit();
?>