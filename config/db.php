<?php
// ============================================================
//  KONFIGURASI DATABASE
//  File: config/db.php
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lostfound_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("
    <div style='font-family:Inter,sans-serif;padding:40px;text-align:center;'>
        <h2 style='color:#dc2626;'>❌ Koneksi Database Gagal</h2>
        <p>Pastikan XAMPP sudah berjalan dan database <strong>lostfound_db</strong> sudah diimport.</p>
        <code>" . mysqli_connect_error() . "</code>
    </div>
    ");
}

mysqli_set_charset($conn, 'utf8mb4');

// Helper: generate kode barang otomatis (LF-001, LF-002, dst)
function generateKodeBarang($conn) {
    $result = mysqli_query($conn, "SELECT MAX(id) as max_id FROM barang");
    $row = mysqli_fetch_assoc($result);
    $next = ($row['max_id'] ?? 0) + 1;
    return 'LF-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// Helper: escape output untuk hindari XSS
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: format tanggal Indonesia
function tglIndo($date) {
    if (!$date) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
              'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $d = date('j', strtotime($date));
    $m = (int)date('n', strtotime($date));
    $y = date('Y', strtotime($date));
    return "$d {$bulan[$m]} $y";
}
?>
