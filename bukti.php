<?php
// ============================================================
//  BUKTI PENGAMBILAN — PUBLIC (tanpa login)
//  Akses: http://172.16.49.31/lostfound/bukti.php?kode=LF-011
// ============================================================
require_once 'config/db.php';
if (!isset($conn)) die('Koneksi database tidak ditemukan.');

$kode = strtoupper(trim($_GET['kode'] ?? ''));
if (!$kode) { http_response_code(404); die('Kode tidak ditemukan.'); }

$stmt = mysqli_prepare($conn,
    "SELECT b.*, p.nama_pengambil, p.kelas, p.id_card, p.tanggal_ambil,
            a.nama AS nama_petugas
     FROM barang b
     LEFT JOIN pengambilan p ON b.id = p.id_barang
     LEFT JOIN admin a ON a.id = p.id_admin
     WHERE b.kode_barang = ? AND b.status = 'sudah_diambil'
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $kode);
mysqli_stmt_execute($stmt);
$d = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$d) { http_response_code(404); die('Data tidak ditemukan atau barang belum diambil.'); }

$tgl = date('d F Y', strtotime($d['tanggal_ambil']));
$jam = date('H.i', strtotime($d['tanggal_ambil'])) . ' WIB';

$logoPath = __DIR__ . '/assets/css/img/smeas.png';
$logoB64  = file_exists($logoPath)
    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
    : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Bukti Pengambilan — <?= htmlspecialchars($kode) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F3F4F6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 16px 48px;
        }
        .bukti {
            background: #fff;
            width: 100%;
            max-width: 360px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
        }
        .kop {
            padding: 24px 24px 18px;
            text-align: center;
            border-bottom: 2px solid #E5E7EB;
        }
        .kop img { width: 150px; height: auto; display: block; margin: 0 auto 12px; }
        .kop-title { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #1B3A6B; }
        .divider { border-top: 1px dashed #E5E7EB; margin: 0 24px; }
        .body { padding: 20px 24px; }
        .kode-wrap { text-align: center; margin-bottom: 18px; }
        .kode-label { font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px; }
        .kode-val { font-size: 28px; font-weight: 700; letter-spacing: 5px; color: #111; font-family: 'Courier New', monospace; }
        .section { margin-bottom: 18px; }
        .row { display: flex; justify-content: space-between; align-items: baseline; padding: 7px 0; border-bottom: 1px solid #F3F4F6; font-size: 13px; }
        .row:last-child { border-bottom: none; }
        .row-lbl { color: #9CA3AF; font-weight: 500; min-width: 100px; }
        .row-val { font-weight: 600; color: #111; text-align: right; word-break: break-word; max-width: 180px; }
        .qr-wrap { text-align: center; margin-bottom: 18px; }
        .qr-label { font-size: 10px; color: #9CA3AF; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        #qrCode { display: inline-block; padding: 10px; border: 1px solid #E5E7EB; border-radius: 8px; }
        .btn-download {
            display: block; width: 100%; padding: 13px;
            background: #1B3A6B; color: #fff; border: none; border-radius: 8px;
            font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600;
            cursor: pointer; text-align: center; letter-spacing: 0.3px;
        }
        .btn-download:active { opacity: 0.85; }
        .footer {
            border-top: 1px solid #F3F4F6;
            padding: 12px 24px;
            text-align: center;
            font-size: 10px;
            color: #9CA3AF;
            line-height: 1.7;
        }
        #loadingOverlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 999;
            align-items: center; justify-content: center;
            flex-direction: column; color: #fff; font-size: 14px; gap: 12px;
        }
        #loadingOverlay.show { display: flex; }
        .spinner {
            width: 32px; height: 32px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media print {
            body { background: #fff; padding: 0; }
            .btn-download { display: none; }
            .bukti { box-shadow: none; border-radius: 0; max-width: 100%; }
            @page { margin: 5mm; }
        }
    </style>
</head>
<body>

<div id="loadingOverlay">
    <div class="spinner"></div>
    <div>Menyiapkan file...</div>
</div>

<div class="bukti" id="areaBukti">

    <div class="kop">
        <?php if ($logoB64): ?>
        <img src="<?= $logoB64 ?>" alt="SMKN 1 Surabaya">
        <?php endif; ?>
        <div class="kop-title">Bukti Pengambilan Barang</div>
    </div>

    <div class="body">

        <div class="kode-wrap">
            <div class="kode-label">ID Barang</div>
            <div class="kode-val"><?= htmlspecialchars($d['kode_barang']) ?></div>
        </div>

        <div class="divider" style="margin:0 0 18px;"></div>

        <div class="section">
            <div class="row">
                <span class="row-lbl">Jenis Barang</span>
                <span class="row-val"><?= htmlspecialchars($d['jenis_barang']) ?></span>
            </div>
            <div class="row">
                <span class="row-lbl">Warna / Ciri</span>
                <span class="row-val"><?= htmlspecialchars($d['warna'] ?: '—') ?></span>
            </div>
        </div>

        <div class="divider" style="margin:0 0 18px;"></div>

        <div class="section">
            <div class="row">
                <span class="row-lbl">Diambil Oleh</span>
                <span class="row-val"><?= htmlspecialchars($d['nama_pengambil']) ?></span>
            </div>
            <div class="row">
                <span class="row-lbl">Kelas</span>
                <span class="row-val"><?= htmlspecialchars($d['kelas'] ?: '—') ?></span>
            </div>
            <div class="row">
                <span class="row-lbl">Tanggal</span>
                <span class="row-val"><?= $tgl ?></span>
            </div>
            <div class="row">
                <span class="row-lbl">Jam</span>
                <span class="row-val"><?= $jam ?></span>
            </div>
            <div class="row">
                <span class="row-lbl">Petugas</span>
                <span class="row-val"><?= htmlspecialchars($d['nama_petugas'] ?? 'Petugas Lost Found') ?></span>
            </div>
        </div>

        <div class="divider" style="margin:0 0 18px;"></div>

        <button class="btn-download" onclick="downloadBukti()">⬇ Download Bukti</button>

    </div>

    <div class="footer">
        Simpan bukti ini sebagai tanda terima resmi.<br>
        Data tercatat di sistem Lost &amp; Found SMKN 1 Surabaya.
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function downloadBukti() {
    const overlay = document.getElementById('loadingOverlay');
    const btn     = document.querySelector('.btn-download');
    overlay.classList.add('show');
    btn.style.display = 'none';
    html2canvas(document.getElementById('areaBukti'), {
        scale: 3, useCORS: true, backgroundColor: '#ffffff', logging: false
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'bukti-<?= htmlspecialchars($kode) ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        overlay.classList.remove('show');
        btn.style.display = 'block';
    }).catch(() => {
        overlay.classList.remove('show');
        btn.style.display = 'block';
        alert('Gagal membuat file. Coba screenshot manual.');
    });
}
</script>
</body>
</html>