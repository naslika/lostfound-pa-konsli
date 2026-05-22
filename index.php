<?php
// ============================================================
//  HALAMAN PUBLIC — USER (PREMIUM REVISI)
//  File: index.php
// ============================================================
require_once 'config/db.php';

if (!function_exists('e')) {
    function e($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');
$hari = isset($_GET['hari']) && $_GET['hari'] === '1';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 18; // Ditingkatkan agar pas dengan grid 6 kolom
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
if ($search)
    $where[] = "(kode_barang LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR jenis_barang LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR warna LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR lokasi_ditemukan LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
if ($status)
    $where[] = "status='" . mysqli_real_escape_string($conn, $status) . "'";
if ($kategori)
    $where[] = "kategori='" . mysqli_real_escape_string($conn, $kategori) . "'";
if ($hari)
    $where[] = "DATE(created_at)=CURDATE()";

$order = $sort === 'terlama' ? 'created_at ASC' : 'created_at DESC';
$sql_where = implode(' AND ', $where);

$total_res = mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE $sql_where");
$total = $total_res ? mysqli_fetch_assoc($total_res)['n'] : 0;
$total_page = ceil($total / $per_page);
$q = mysqli_query($conn, "SELECT * FROM barang WHERE $sql_where ORDER BY $order LIMIT $per_page OFFSET $offset");
//----STATISTIK----
$stat_belum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='belum_diambil'"))['n'];
$stat_sudah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='sudah_diambil'"))['n'];
$stat_hari  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE DATE(created_at)=CURDATE()"))['n'];
$stat_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang"))['n'];


$kategori_list = ['Pakaian', 'Elektronik', 'Kendaraan', 'Alat Tulis', 'Tas', 'Lainnya'];

function buildUrl($params)
{
    $base = array_merge($_GET, $params);
    unset($base['page']);
    return '?' . http_build_query(array_filter($base, fn($v) => $v !== ''));
}

if (!function_exists('tglIndo')) {
    function tglIndo($tgl)
    {
        return date('d M Y', strtotime($tgl));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found SMKN 1 Surabaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1E3A8A;
            --dark-blue: #162B65;
            --gold: #D4AF37;
            --gold-light: #FACC15;
            --bg-body: #F8FAFC;
            --white: #FFFFFF;
            --border: #E2E8F0;
            --text-dark: #0F172A;
            --text-muted: #64748B;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-dark); padding-top: 130px; /* Jarak untuk sticky header */ }

        /* STICKY HEADER SYSTEM */
        .sticky-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .top-bar {
            background: var(--white);
            padding: 8px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }

        .logo-area { display: flex; align-items: center; gap: 20px; }
        .logo-area img { height: 55px; width: auto; }
        .school-name { border-left: 3px solid var(--gold); padding-left: 20px; }
        .school-name h1 { font-size: 20px; font-weight: 800; color: var(--primary-blue); letter-spacing: -0.5px; }
        .school-name h1 span { color: var(--gold); }
        .school-name p { font-size: 13px; color: var(--text-muted); font-weight: 500; }

        .system-info { text-align: right; }
        .system-info h2 { font-size: 16px; color: var(--dark-blue); font-weight: 700; }
        .system-info p { font-size: 12px; color: var(--gold); font-weight: 600; }

        /* NAVBAR BIRU */
        .navbar-main {
            background: var(--dark-blue);
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand { color: var(--white); font-weight: 700; display: flex; align-items: center; gap: 10px; font-size: 16px; }
        .badge-gold { background: var(--gold); color: var(--dark-blue); font-size: 10px; padding: 2px 8px; border-radius: 4px; font-weight: 800; }

        .stats-grid { display: flex; gap: 10px; }
        .stat-item {
            background: var(--white);
            padding: 6px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            min-width: 140px;
            border-bottom: 3px solid transparent;
            transition: 0.2s;
        }
        .stat-item:hover { transform: translateY(-2px); }
        .stat-item.active { border-bottom-color: var(--gold-light); background: #f0f4ff; }
        .stat-icon { font-size: 18px; }
        .stat-val { display: flex; flex-direction: column; }
        .stat-val .num { font-size: 13px; font-weight: 800; color: var(--primary-blue); }
        .stat-val .lbl { font-size: 9px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        /* MAIN CONTENT */
        .container { padding: 30px 40px; max-width: 1600px; margin: 0 auto; }

        .alert-premium {
            background: linear-gradient(to right, #FFFBEB, #FEF3C7);
            border-left: 5px solid var(--gold);
            border-radius: 8px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .alert-premium .icon { font-size: 24px; color: var(--gold); }
        .alert-premium p { font-size: 13px; color: #92400E; font-weight: 500; line-height: 1.6; }

        /* FILTER BAR */
        .filter-bar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 15px;
            margin-bottom: 25px;
        }
        .search-input-wrap { position: relative; }
        .search-input-wrap input {
            width: 100%; padding: 12px 15px 12px 45px;
            border: 1px solid var(--border); border-radius: 10px;
            font-size: 14px; outline: none; transition: 0.2s;
        }
        .search-input-wrap input:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        .search-input-wrap::before { content: '🔍'; position: absolute; left: 15px; top: 12px; opacity: 0.5; }

        .dropdowns { display: flex; gap: 10px; }
        .custom-select {
            padding: 10px 15px; border: 1px solid var(--border); border-radius: 10px;
            background: var(--white); font-size: 13px; font-weight: 600; cursor: pointer; min-width: 150px;
        }

        /* CARD GRID 6 KOLOM */
        .barang-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr); /* Tetap 6 kolom di desktop besar */
            gap: 15px;
        }

        .card-barang {
            background: var(--white);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            transition: 0.3s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        .card-barang:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); border-color: var(--gold); }

        .thumb {
            width: 100%;
            aspect-ratio: 1/1;
            background: #EDF2F7;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .thumb img { width: 100%; height: 100%; object-fit: cover; }
        .thumb .empty-icon { font-size: 40px; opacity: 0.2; }

        .card-info { padding: 12px; flex-grow: 1; display: flex; flex-direction: column; }
        .card-info .kode { font-size: 9px; font-weight: 800; color: var(--gold); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }
        .card-info .nama { font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; line-height: 1.3; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .card-info .meta { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; margin-bottom: 3px; }

        .status-tag {
            margin-top: auto;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 5px;
            display: inline-block;
            text-align: center;
        }
        .tag-pending { background: #FEF3C7; color: #92400E; }
        .tag-done { background: #D1FAE5; color: #065F46; }

        /* MODAL */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 20px; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: var(--white); width: 100%; max-width: 600px; border-radius: 20px; overflow: hidden; animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* PAGINATION */
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 40px; padding-bottom: 50px; }
        .pagination a, .pagination span { padding: 10px 18px; border-radius: 8px; background: var(--white); border: 1px solid var(--border); font-weight: 600; font-size: 14px; }
        .pagination .active { background: var(--primary-blue); color: var(--white); border-color: var(--primary-blue); }

        @media (max-width: 1200px) { .barang-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 900px) { .barang-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 600px) { 
            body { padding-top: 110px; }
            .barang-grid { grid-template-columns: repeat(2, 1fr); } 
            .top-bar { padding: 8px 20px; }
            .logo-area img { height: 40px; }
            .school-name h1 { font-size: 14px; }
            .stats-grid { display: none; }
            .filter-bar { grid-template-columns: 1fr; }
        }
       
    </style>
</head>
<body>

    <div class="sticky-wrapper">
        <!-- TOP HEADER (PUTIH) -->
        <header class="top-bar">
            <div class="logo-area">
                <img src="assets/css/img/smeas.png" alt="Logo SMKN 1 Surabaya" onerror="this.src='https://upload.wikimedia.org/wikipedia/id/3/30/Logo_SMK_Negeri_1_Surabaya.png'">
            </div>
            <div class="system-info">
                <h4>Sistem Informasi Lost & Found</h4>
                <h5>Dikelola Oleh Tata Usaha SMKN 1 Surabaya</h5>
            </div>
        </header>

        <!-- NAVBAR NAVY -->
        <nav class="navbar-main">
            <div class="nav-brand">
                📂 Lost & Found <span class="badge-gold">PUBLIC ACCESS</span>
            </div>
            <div class="stats-grid">
                <a href="index.php" class="stat-item <?= !$status && !$hari ? 'active' : '' ?>">
                    <span class="stat-icon">📂</span>
                    <div class="stat-val">
                        <span class="num"><?= $stat_belum + $stat_sudah ?> Item</span>
                        <span class="lbl">Semua Barang</span>
                    </div>
                </a>
                <a href="index.php?status=sudah_diambil" class="stat-item <?= $status == 'sudah_diambil' ? 'active' : '' ?>">
                    <span class="stat-icon">✅</span>
                    <div class="stat-val">
                        <span class="num"><?= $stat_sudah ?> Item</span>
                        <span class="lbl">Sudah Diambil</span>
                    </div>
                </a>
                <a href="index.php?status=belum_diambil" class="stat-item <?= $status == 'belum_diambil' ? 'active' : '' ?>">
                    <span class="stat-icon">📦</span>
                    <div class="stat-val">
                        <span class="num">
                            <?= $stat_belum ?> Item
                        </span>
                        <span class="lbl">Belum Diambil</span>
                    </div>
                </a>
                <a href="index.php?hari=1" class="stat-item <?= $hari ? 'active' : '' ?>">
                    <span class="stat-icon">📅</span>
                    <div class="stat-val">
                        <span class="num">
                            <?= $stat_hari ?> Item
                        </span>
                        <span class="lbl">Masuk Hari Ini</span>
                    </div>
                </a>
            </div>
        </nav>
    </div>

    <main class="container">
        
        <!-- ALERT -->
        <div class="alert-premium">
            <div class="icon">🔔</div>
            <p>
                <strong>Pemberitahuan Penting:</strong> Jika Anda mengenali salah satu barang di bawah ini sebagai milik Anda, harap segera melapor ke <strong>Ruang Tata Usaha (TU)</strong>. Pastikan membawa kartu identitas (Kartu Pelajar/KTP) dan bukti pendukung lainnya untuk proses verifikasi pengambilan.
            </p>
        </div>

        <!-- SEARCH & FILTER -->
        <form method="GET" action="index.php">
            <div class="filter-bar">
                <div class="search-input-wrap">
                    <input type="text" name="search" placeholder="Cari Jenis Barang, Warna, atau Lokasi..." value="<?= e($search) ?>">
                </div>
                <div class="dropdowns">
                    <select name="status" class="custom-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="belum_diambil" <?= $status == 'belum_diambil' ? 'selected' : '' ?>> Belum Diambil</option>
                        <option value="sudah_diambil" <?= $status == 'sudah_diambil' ? 'selected' : '' ?>> Sudah Diambil</option>
                    </select>
                    <select name="kategori" class="custom-select" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat ?>" <?= $kategori == $kat ? 'selected' : '' ?>><?= $kat ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="sort" class="custom-select" onchange="this.form.submit()">
                        <option value="terbaru" <?= $sort == 'terbaru' ? 'selected' : '' ?>>Urutan Terbaru</option>
                        <option value="terlama" <?= $sort == 'terlama' ? 'selected' : '' ?>>Urutan Terlama</option>
                    </select>
                </div>
            </div>
        </form>

        <div style="margin-bottom: 15px; font-size: 13px; color: var(--text-muted); font-weight: 500;">
            Menampilkan <strong><?= mysqli_num_rows($q) ?></strong> data barang dari total <?= $total ?>.
        </div>

        <!-- GRID BARANG -->
        <div class="barang-grid">
            <?php if (mysqli_num_rows($q) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($q)): ?>
                            <div class="card-barang" onclick='lihatDetail(<?= json_encode($row) ?>)'>
                                <div class="thumb">
                                    <?php if ($row['foto_barang'] && file_exists('uploads/' . $row['foto_barang'])): ?>
                                            <img src="uploads/<?= e($row['foto_barang']) ?>" alt="Barang">
                                    <?php else: ?>
                                            <span class="empty-icon">📦</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-info">
                                    <span class="kode"><?= e($row['kode_barang']) ?></span>
                                    <h3 class="nama"><?= e($row['jenis_barang']) ?></h3>
                                    <div class="meta">📍 <?= e($row['lokasi_ditemukan']) ?></div>
                                    <div class="meta">📅 <?= tglIndo($row['tanggal_ditemukan']) ?></div>
                                    <div class="status-tag <?= $row['status'] == 'belum_diambil' ? 'tag-pending' : 'tag-done' ?>">
                                        <?= $row['status'] == 'belum_diambil' ? '⏳ Belum Diambil' : '✅ Sudah Diambil' ?>
                                    </div>
                                </div>
                            </div>
                    <?php endwhile; ?>
            <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 100px 0;">
                        <span style="font-size: 60px;">🔍</span>
                        <p style="margin-top: 15px; color: var(--text-muted); font-weight: 500;">Maaf, barang yang Anda cari tidak ditemukan.</p>
                    </div>
            <?php endif; ?>
        </div>

        <!-- PAGINASI -->
        <?php if ($total_page > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                    <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                    <a href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                            <?php endif; ?>
                    <?php endfor; ?>
                </div>
        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer style="background: var(--dark-blue); padding: 40px; color: var(--white); text-align: center;">
        <p style="font-weight: 700; font-size: 14px; opacity: 0.9;">SMK NEGERI 1 SURABAYA</p>
        <p style="font-size: 12px; opacity: 0.6; margin-top: 5px;">Sistem Informasi Temuan Barang (Lost & Found) &copy; <?= date('Y') ?></p>
    </footer>

    <!-- MODAL DETAIL -->
    <div class="modal-overlay" id="modalDetail">
        <div class="modal-box">
            <div style="padding: 25px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-weight: 800; color: var(--primary-blue);">INFORMASI BARANG</h3>
                <button onclick="tutupModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">✕</button>
            </div>
            <div id="mBody" style="padding: 30px;">
                <!-- JS Inject -->
            </div>
            <div style="padding: 20px; background: #f8fafc; text-align: center;">
                <button onclick="tutupModal()" style="padding: 10px 30px; background: var(--primary-blue); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Tutup Detail</button>
            </div>
        </div>
    </div>

    <script>
        function lihatDetail(data) {
            const m = document.getElementById('mBody');
            m.innerHTML = `
                <div style="display: grid; grid-template-columns: 200px 1fr; gap: 25px;">
                    <div>
                        <img src="${data.foto_barang ? 'uploads/'+data.foto_barang : ''}" style="width: 100%; aspect-ratio: 1; border-radius: 12px; object-fit: cover; border: 1px solid #ddd; ${!data.foto_barang ? 'display:none' : ''}">
                        ${!data.foto_barang ? '<div style="background:#f1f5f9; aspect-ratio:1; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:50px;">📦</div>' : ''}
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: var(--gold); letter-spacing: 1px;">#${data.kode_barang}</span>
                        <h2 style="font-size: 22px; margin: 5px 0 15px 0; color: var(--text-dark);">${data.jenis_barang}</h2>
                        
                        <div style="display: grid; gap: 10px; font-size: 14px;">
                            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
                                <span style="color: var(--text-muted);">Warna Utama</span>
                                <span style="font-weight: 600;">${data.warna || '-'}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
                                <span style="color: var(--text-muted);">Titik Temuan</span>
                                <span style="font-weight: 600;">${data.lokasi_ditemukan}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
                                <span style="color: var(--text-muted);">Tanggal Lapor</span>
                                <span style="font-weight: 600;">${data.tanggal_ditemukan}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Status Barang</span>
                                <span style="font-weight: 800; color: ${data.status=='belum_diambil'?'#B45309':'#047857'};">${data.status=='belum_diambil'?'BELUM DIAMBIL':'SUDAH DIAMBIL'}</span>
                            </div>
                        </div>

                        <div style="margin-top: 25px; padding: 15px; background: #eff6ff; border-radius: 10px; color: #1e40af; font-size: 13px; line-height: 1.5; border-left: 4px solid #1e40af;">
                            📍 Segera hubungi <strong>Ruang Tata Usaha</strong> dengan menyebutkan kode barang di atas jika ini milik Anda.
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('modalDetail').classList.add('active');
        }

        function tutupModal() {
            document.getElementById('modalDetail').classList.remove('active');
        }

        window.onclick = function(e) {
            if (e.target == document.getElementById('modalDetail')) tutupModal();
        }
    </script>
</body>
</html>