<?php
$page_active = $page_active ?? 'dashboard';
$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Admin') ?> — Lost & Found SMKN 1 Surabaya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
   GLOBAL STYLES — Lost & Found SMKN 1 Surabaya
   Font: Inter (Google Fonts)
   Warna utama: Biru tua #1B3A6B, Kuning #F5C518
   ============================================================ */

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --biru: #1B3A6B;
            --biru-mid: #2451A3;
            --biru-muda: #EBF0FB;
            --kuning: #F5C518;
            --kuning-gelap: #D4A800;
            --hijau: #16A34A;
            --hijau-muda: #DCFCE7;
            --merah: #DC2626;
            --merah-muda: #FEE2E2;
            --orange: #EA580C;
            --orange-muda: #FFEDD5;
            --abu: #6B7280;
            --abu-muda: #F3F4F6;
            --putih: #FFFFFF;
            --border: #E5E7EB;
            --teks: #111827;
            --teks-abu: #6B7280;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
            --radius: 8px;
            --radius-lg: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--teks);
            background: var(--abu-muda);
            line-height: 1.5;
        }

        /* ====================================================
   HEADER
   ==================================================== */
        .header-wrapper {
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); 
        }


        .site-header {
            box-shadow: none !important; 
        }
        .site-header {
            background: var(--putih);
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .site-header .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .site-header .logo-area img {
        height: 48px;
        object-fit: contain;
        }

        .site-header .header-right {
            text-align: right;
            color: var(--biru-mid);
            font-size: 12px;
            line-height: 1.6;
        }

        .site-header .header-right strong {
            display: block;
            font-size: 14px;
            color: var(--biru);
            font-weight: 600;
        }

        /* ====================================================
   NAVBAR
   ==================================================== */
        .main-nav {
            background: var(--biru-mid);
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .main-nav .brand-badge {
            background: var(--biru);
            color: var(--putih);
            font-weight: 700;
            font-size: 13px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-right: 8px;
        }

        .main-nav .brand-badge .badge-role {
            background: var(--kuning-gelap);
            color: var(--merah);
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
            letter-spacing: 0.5px;
        }

        .main-nav a {
            color: var(--putih);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            padding: 10px 16px;
            display: block;
            transition: background 0.15s;
            border-radius: 4px 4px 0 0;
        }

        .main-nav a:hover,
        .main-nav a.active {
            background: var(--biru);
            color: var(--putih);
        }

        /* ====================================================
   STAT BAR
   ==================================================== */
        .stat-bar {
            background: var(--putih);
            border-bottom: 1px solid var(--border);
            padding: 10px 24px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: var(--radius);
            background: var(--abu-muda);
            border: 1px solid var(--border);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s;
        }

        .stat-item .stat-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .stat-item .stat-num {
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
        }

        .stat-item .stat-label {
            font-size: 11px;
            color: var(--teks-abu);
            font-weight: 400;
        }

        .stat-item.belum {
            border-color: #FBBF24;
            background: #FFFBEB;
        }

        .stat-item.sudah {
            border-color: #86EFAC;
            background: #F0FDF4;
        }

        .stat-item.hari {
            border-color: #93C5FD;
            background: #EFF6FF;
        }

        .stat-item.lokasi {
            border-color: #C4B5FD;
            background: #F5F3FF;
        }

        .stat-item.belum .stat-icon {
            background: #FEF3C7;
        }

        .stat-item.sudah .stat-icon {
            background: #DCFCE7;
        }

        .stat-item.hari .stat-icon {
            background: #DBEAFE;
        }

        .stat-item.lokasi .stat-icon {
            background: #EDE9FE;
        }

        /* Stat aktif saat diklik */
        .stat-active {
            border-color: var(--biru-mid) !important;
            background: var(--biru-muda) !important;
            box-shadow: 0 0 0 2px rgba(36, 81, 163, 0.15);
        }

        .stat-active .stat-num {
            color: var(--biru);
        }

        /* ====================================================
   ALERT BANNER
   ==================================================== */
        .alert-banner {
            margin: 16px 24px;
            padding: 12px 16px;
            border-radius: var(--radius);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-banner.merah {
            background: var(--merah-muda);
            border: 1px solid #FCA5A5;
            color: #991B1B;
        }

        .alert-banner.biru {
            background: var(--biru-muda);
            border: 1px solid #BFDBFE;
            color: #1E40AF;
        }

        .alert-banner .alert-icon {
            font-size: 16px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* ====================================================
   KONTEN
   ==================================================== */
        .content-wrap {
            padding: 16px 24px;
        }

        /* ====================================================
   CARD
   ==================================================== */
        .card {
            background: var(--putih);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
            font-size: 14px;
            background: var(--abu-muda);
        }

        /* ====================================================
   TOMBOL
   ==================================================== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--biru);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--biru-mid);
        }

        .btn-kuning {
            background: var(--kuning);
            color: var(--biru);
            font-weight: 600;
        }

        .btn-kuning:hover {
            background: var(--kuning-gelap);
        }

        .btn-success {
            background: var(--hijau);
            color: #fff;
        }

        .btn-success:hover {
            background: #15803D;
        }

        .btn-danger {
            background: var(--merah);
            color: #fff;
        }

        .btn-danger:hover {
            background: #B91C1C;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--teks);
        }

        .btn-outline:hover {
            background: var(--abu-muda);
        }

        /* ====================================================
   BADGE STATUS
   ==================================================== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge-belum {
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .badge-sudah {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #6EE7B7;
        }

        /* ====================================================
   TABEL
   ==================================================== */
        .tabel-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: var(--biru);
            color: var(--putih);
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
            letter-spacing: 0.3px;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.1s;
        }

        tbody tr:hover {
            background: var(--biru-muda);
        }

        tbody td {
            padding: 10px 14px;
            font-size: 13px;
            vertical-align: middle;
        }

        /* ====================================================
   FORM
   ==================================================== */
        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--teks-abu);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--teks);
            background: var(--putih);
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--biru-mid);
            box-shadow: 0 0 0 3px rgba(36, 81, 163, 0.12);
        }

        select.form-control {
            cursor: pointer;
        }

        /* ====================================================
   MODAL
   ==================================================== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: var(--putih);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 700;
            font-size: 15px;
            background: var(--biru);
            color: var(--putih);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .modal-header .modal-close {
            background: none;
            border: none;
            color: var(--putih);
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
            padding: 0 4px;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            background: var(--abu-muda);
        }

        /* ====================================================
   SEARCH & FILTER BAR
   ==================================================== */
        .search-filter-bar {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .search-wrap input {
            width: 100%;
            padding: 8px 12px 8px 36px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--teks);
            background: var(--putih);
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .search-wrap input:focus {
            outline: none;
            border-color: var(--biru-mid);
            box-shadow: 0 0 0 3px rgba(36, 81, 163, 0.1);
        }

        .search-wrap .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--teks-abu);
            font-size: 14px;
            pointer-events: none;
        }

        /* ====================================================
   PAGINASI
   ==================================================== */
        .pagination {
            display: flex;
            gap: 4px;
            justify-content: center;
            padding: 14px 0 4px;
        }

        .pagination a,
        .pagination span {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--teks);
            transition: all 0.15s;
        }

        .pagination a:hover {
            background: var(--biru-muda);
            border-color: var(--biru-mid);
        }

        .pagination .active {
            background: var(--biru);
            color: #fff;
            border-color: var(--biru);
        }

        /* ====================================================
   ADMIN LAYOUT
   ==================================================== */
        .admin-layout {
            display: flex;
            gap: 16px;
            padding: 16px 24px;
            align-items: flex-start;
        }

        .admin-main {
            flex: 1;
            min-width: 0;
        }

        .admin-side {
            width: 280px;
            flex-shrink: 0;
        }

        /* ====================================================
   AKTIVITAS TERKINI
   ==================================================== */
        .aktivitas-item {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 12px;
            line-height: 1.5;
        }

        .aktivitas-item:last-child {
            border-bottom: none;
        }

        .aktivitas-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--hijau);
            margin-top: 4px;
            flex-shrink: 0;
        }

        .aktivitas-dot.orange {
            background: var(--orange);
        }

        /* ====================================================
   AKSI CEPAT
   ==================================================== */
        .aksi-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 14px;
        }

        .aksi-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--putih);
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: var(--teks);
            text-decoration: none;
            transition: all 0.15s;
        }

        .aksi-btn:hover {
            background: var(--biru-muda);
            border-color: var(--biru-mid);
            color: var(--biru);
        }

        .aksi-btn .aksi-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .aksi-icon.kuning {
            background: #FEF3C7;
        }

        .aksi-icon.biru {
            background: var(--biru-muda);
        }

        .aksi-icon.hijau {
            background: var(--hijau-muda);
        }

        /* ====================================================
   RESPONSIVE
   ==================================================== */
        @media (max-width: 1024px) {
            .admin-layout {
                flex-direction: column;
            }

            .admin-side {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .stat-bar {
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px 12px;
            }

            .content-wrap {
                padding: 12px;
            }

            .admin-layout {
                padding: 12px;
                gap: 12px;
            }

            .main-nav {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
    <div class="header-wrapper">
        <header class="site-header">
            <div class="logo-area">
                <img src="../assets/css/img/smeas.png" alt="SMKN 1 Surabaya" style="height:48px;object-fit:contain;"
                    onerror="this.style.display='none'">
            </div>
            <div class="header-right">
                <strong>Sistem Informasi Lost & Found</strong>
                Dikelola oleh Petugas Tata Usaha
            </div>
        </header>

        <nav class="main-nav">
            <div class="brand-badge">
                🗂 Lost & Found
                <span class="badge-role">ADMIN</span>
            </div>
            <a href="dashboard.php" class="<?= $page_active === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="daftar.php" class="<?= $page_active === 'daftar' ? 'active' : '' ?>">📋 Daftar Barang</a>
            <a href="scan.php" class="<?= $page_active === 'scan' ? 'active' : '' ?>">📷 Scan Barcode</a>
            <a href="riwayat.php" class="<?= $page_active === 'riwayat' ? 'active' : '' ?>">📜 Riwayat Pengambilan</a>
            <div style="flex:1;"></div>
            <span style="color:var(--putih);font-size:12px;font-weight:500;padding:0 8px;">🔥
                <?= e($admin_nama) ?></span>
            <a href="logout.php" style="color:#DC2626!important;font-size:12px;font-weight:600;"
                onclick="return confirm('Yakin mau logout?')">🚪 Logout</a>
        </nav>
    </div>
    
</body>