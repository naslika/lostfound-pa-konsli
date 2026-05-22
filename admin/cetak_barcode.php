<?php

session_start();
if (!isset($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true) {
    header("Location: login.php"); exit();
}

// Include koneksi database
require_once '../config/db.php';

// Pastikan koneksi tersedia
if (!isset($conn)) {
    die('Koneksi database tidak ditemukan. Cek config/db.php');
}

$id   = (int)($_GET['id'] ?? 0);
$baru = isset($_GET['baru']);

if (!$id) { header("Location: daftar.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT * FROM barang WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$barang) { header("Location: daftar.php"); exit(); }

$page_active = 'daftar';
$page_title  = 'Cetak Barcode';
require_once 'partials/header.php';
?>

<div class="stat-bar">
    <a href="daftar.php" style="font-size:13px;color:var(--biru);text-decoration:none;">← Kembali ke Daftar</a>
    <span style="color:#9CA3AF;font-size:13px;">/ Cetak Barcode</span>
</div>

<div class="content-wrap" style="max-width:700px;">

<?php if ($baru): ?>
<div class="alert-banner" style="background:#D1FAE5;border:1px solid #6EE7B7;color:#065F46;margin-bottom:16px;">
    <span class="alert-icon">✅</span>
    <span>Barang <strong><?= e($barang['kode_barang']) ?></strong> berhasil disimpan! Cetak barcode lalu tempel ke barang.</span>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        🖨️ Barcode Barang — <?= e($barang['kode_barang']) ?>
        <div style="display:flex;gap:8px;">
            <button onclick="cetakBarcode()" class="btn btn-kuning btn-sm">🖨️ Cetak</button>
            <a href="daftar.php" class="btn btn-outline btn-sm">← Daftar</a>
        </div>
    </div>

    <div style="padding:24px;display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">

        <!-- Area cetak barcode -->
        <div id="areaCetak" style="border:2px solid var(--border);border-radius:12px;padding:20px;text-align:center;min-width:260px;background:#fff;">
            

            <!-- Header label -->
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0; margin-bottom:10px;">
                <div>
                    <img src="../assets/css/img/smeas.png" alt="SMKN 1 Surabaya" style="width:140px; height:auto; object-fit:contain;">
                </div>
            </div>

            <div style="border-top:1px solid #E5E7EB;margin-bottom:12px;"></div>

            <!-- Info barang singkat -->
            <div style="text-align:left;font-size:12px;line-height:1.8;color:#374151;">
                <div><strong>Barang:</strong> <?= e($barang['jenis_barang']) ?>
                </div>
                <?php if ($barang['warna']): ?>
                    <div><strong>Ciri:</strong>
                        <?= e($barang['warna']) ?>
                    </div>
                <?php endif; ?>
                <div><strong>Lokasi:</strong>
                    <?= e($barang['lokasi_ditemukan']) ?>
                </div>
                <div><strong>Tanggal:</strong>
                    <?= tglIndo($barang['tanggal_ditemukan']) ?>
                </div>
                <div><strong>Status:</strong>
                    <span
                        style="color:<?= $barang['status'] === 'belum_diambil' ? '#D97706' : '#16A34A' ?>;font-weight:600;">
                        <?= $barang['status'] === 'belum_diambil' ? 'BELUM DIAMBIL' : 'SUDAH DIAMBIL' ?>
                    </span>
                </div>
            </div>
    
            <!-- Barcode (dibuat pakai JsBarcode) -->
            <div style="margin:0 auto;display:center">
                <svg id="barcodeImg"></svg>
            </div>
    
            <div style="font-size:14px;font-weight:700;color:var(--biru);letter-spacing:2px;margin-top:6px;">
                <?= e($barang['kode_barang']) ?>
            </div>
    
            <div style="border-top:1px solid #E5E7EB;margin:12px 0 10px;"></div>
    
            <div style="margin-top:10px;font-size:10px;color:#9CA3AF;">
                Tempel label ini pada barang
            </div>
        </div>

        <!-- Info & instruksi -->
        <div style="flex:1;min-width:220px;">
            <h3 style="font-size:15px;font-weight:700;color:var(--biru);margin-bottom:16px;">📋 Langkah Selanjutnya</h3>

            <div style="display:flex;flex-direction:column;gap:10px;">
                <?php
                $steps = [
                    ['🖨️','Cetak label','Klik tombol "Cetak" dan pilih printer thermal atau printer biasa'],
                    ['✂️','Gunting label','Potong label sesuai ukuran kotak barcode'],
                    ['📎','Tempel ke barang','Tempel label barcode di bagian yang mudah terlihat'],
                    ['📦','Simpan barang','Simpan barang di tempat yang sudah ditentukan'],
                ];
                foreach ($steps as $i => [$ico, $judul, $desk]):
                ?>
                <div style="display:flex;gap:10px;padding:10px 12px;background:var(--abu-muda);border-radius:8px;font-size:13px;">
                    <div style="width:28px;height:28px;background:var(--biru);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;"><?= $i+1 ?></div>
                    <div>
                        <div style="font-weight:600;"><?= $ico ?> <?= $judul ?></div>
                        <div style="color:#6B7280;font-size:12px;margin-top:2px;"><?= $desk ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($barang['foto_barang']): ?>
            <div style="margin-top:16px;">
                <div style="font-size:12px;font-weight:600;color:#6B7280;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Foto Barang</div>
                <img src="../uploads/<?= e($barang['foto_barang']) ?>"
                    style="width:100%;max-width:200px;border-radius:8px;border:1px solid var(--border);"
                    alt="Foto barang">
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
</div>

<!-- JsBarcode dari CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
// Generate barcode
JsBarcode("#barcodeImg", "<?= e($barang['kode_barang']) ?>", {
    format:      "CODE128",
    width:       2,
    height:      60,
    displayValue: false,
    margin:      6,
    background:  "#ffffff",
    lineColor:   "#000000"
});

// Cetak hanya area barcode
function cetakBarcode() {
    const win = window.open('', '_blank', 'width=400,height=600');
    win.document.write(`
        <!DOCTYPE html><html><head>
        <title>Cetak Barcode — <?= e($barang['kode_barang']) ?></title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
                display: flex;
                justify-content: center;
            }
            .label-wrap {
                border: 2px solid #ccc;
                border-radius: 10px;
                padding: 16px;
                text-align: center;
                width: 260px;
                background: #fff;
            }
            .label-wrap img { width: 140px; height: auto; }
            .divider { border-top: 1px solid #E5E7EB; margin: 10px 0; }
            .info { text-align: left; font-size: 12px; line-height: 1.8; color: #374151; }
            .info strong { font-weight: 600; }
            .kode {
                font-size: 14px;
                font-weight: 700;
                color: #1D4ED8;
                letter-spacing: 2px;
                margin-top: 6px;
            }
            .footer { font-size: 10px; color: #9CA3AF; margin-top: 8px; }
            svg { display: block; margin: 10px auto 0; }
            @media print {
                body { padding: 0; }
                @page { margin: 5mm; }
            }
        </style>
        </head><body>
        <div class="label-wrap">
            <img src="../assets/css/img/smeas.png" alt="SMKN 1 Surabaya">
            <div class="divider"></div>
            <div class="info">
                <div><strong>Barang:</strong> <?= e($barang['jenis_barang']) ?></div>
                <?php if ($barang['warna']): ?>
                    <div><strong>Ciri:</strong> <?= e($barang['warna']) ?></div>
                <?php endif; ?>
                <div><strong>Lokasi:</strong> <?= e($barang['lokasi_ditemukan']) ?></div>
                <div><strong>Tanggal:</strong> <?= tglIndo($barang['tanggal_ditemukan']) ?></div>
                <div><strong>Status:</strong>
                    <span style="color:<?= $barang['status'] === 'belum_diambil' ? '#D97706' : '#16A34A' ?>;font-weight:600;">
                        <?= $barang['status'] === 'belum_diambil' ? 'BELUM DIAMBIL' : 'SUDAH DIAMBIL' ?>
                    </span>
                </div>
            </div>
            <svg id="barcodeImg"></svg>
            <div class="kode"><?= e($barang['kode_barang']) ?></div>
            <div class="divider"></div>
            <div class="footer">Tempel label ini pada barang</div>
        </div>

        <script>
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js';
            s.onload = function() {
                JsBarcode('#barcodeImg', '<?= e($barang['kode_barang']) ?>', {
                    format: 'CODE128',
                    width: 2,
                    height: 60,
                    displayValue: false,
                    margin: 6,
                    background: '#ffffff',
                    lineColor: '#000000'
                });
                setTimeout(() => { window.print(); window.close(); }, 800);
            };
            document.head.appendChild(s);
        <\/script>
        </body></html>
    `);
        win.document.close();
    }
</script>

<?php require_once 'partials/footer.php'; ?>
