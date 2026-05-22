<?php
// ============================================================
//  API: Ambil data pengambil barang
//  File: api/pengambil.php
//  Dipakai oleh halaman public (index.php) via fetch JS
// ============================================================
header('Content-Type: application/json');
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(null); exit(); }

$stmt = mysqli_prepare($conn,
    "SELECT p.nama_pengambil, p.kelas, p.id_card, p.tanggal_ambil
     FROM pengambilan p
     WHERE p.id_barang = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

echo json_encode($result ?: null);
?>
