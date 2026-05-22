<?php
// ============================================================
//  PROSES EDIT BARANG
//  File: admin/edit.php
//  Dipanggil dari form modal edit di daftar.php
// ============================================================
session_start();
if (!isset($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php';

// Pastikan koneksi tersedia
if (!isset($conn)) {
    die('Koneksi database tidak ditemukan. Cek config/db.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: daftar.php");
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$jenis_barang = trim($_POST['jenis_barang'] ?? '');
$kategori = trim($_POST['kategori'] ?? '');
$warna = trim($_POST['warna'] ?? '');
$lokasi_ditemukan = trim($_POST['lokasi_ditemukan'] ?? '');
$tanggal = trim($_POST['tanggal_ditemukan'] ?? '');

if (!$id || empty($jenis_barang) || empty($lokasi_ditemukan)) {
    header("Location: daftar.php?msg=error");
    exit();
}

// Ambil data lama
$old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_barang FROM barang WHERE id=$id"));
$nama_foto = $old['foto_barang'] ?? '';

// Kalau ada foto baru di-upload
if (isset($_FILES['foto_baru']) && $_FILES['foto_baru']['error'] === 0) {
    // Hapus foto lama
    if ($nama_foto && file_exists('../uploads/' . $nama_foto)) {
        unlink('../uploads/' . $nama_foto);
    }
    $ext = pathinfo($_FILES['foto_baru']['name'], PATHINFO_EXTENSION);
    $nama_foto = 'LF-edit_' . $id . '_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['foto_baru']['tmp_name'], '../uploads/' . $nama_foto);
}

$stmt = mysqli_prepare(
    $conn,
    "UPDATE barang SET jenis_barang=?, warna=?, lokasi_ditemukan=?, tanggal_ditemukan=?, foto_barang=?
     WHERE id=?"
);
mysqli_stmt_bind_param(
    $stmt,
    'sssssi',
    $jenis_barang,
    $warna,
    $lokasi_ditemukan,
    $tanggal,
    $nama_foto,
    $id
);
mysqli_stmt_execute($stmt);

header("Location: daftar.php?msg=edit");
exit();
?>