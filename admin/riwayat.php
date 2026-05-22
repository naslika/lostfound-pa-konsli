<?php

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

// ---- FILTER ----
$search = trim($_GET['search'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
if ($search)
    $where[] = "(b.kode_barang LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
    OR b.jenis_barang LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
    OR p.nama_pengambil LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
    OR p.kelas LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";

$order = $sort === 'terlama' ? 'p.tanggal_ambil ASC' : 'p.tanggal_ambil DESC';
$sql_where = implode(' AND ', $where);

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM pengambilan p JOIN barang b ON p.id_barang=b.id WHERE $sql_where"))['n'];
$total_page = ceil($total / $per_page);

$q = mysqli_query(
    $conn,
    "SELECT p.*, b.kode_barang, b.jenis_barang, b.warna, b.foto_barang, b.lokasi_ditemukan
     FROM pengambilan p
     JOIN barang b ON p.id_barang = b.id
     WHERE $sql_where
     ORDER BY $order
     LIMIT $per_page OFFSET $offset"
);

$stat_belum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='belum_diambil'"))['n'];
$stat_sudah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='sudah_diambil'"))['n'];
$stat_hari = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE DATE(tanggal_ditemukan)=CURDATE()"))['n'];

// Aktivitas terkini (5 teratas)
$q_aktivitas = mysqli_query(
    $conn,
    "SELECT p.*, b.kode_barang, b.jenis_barang FROM pengambilan p
     JOIN barang b ON p.id_barang = b.id
     ORDER BY p.tanggal_ambil DESC LIMIT 5"
);

$page_active = 'riwayat';
$page_title = 'Riwayat Pengambilan';
require_once 'partials/header.php';

function buildUrl($params)
{
    $base = array_merge($_GET, $params);
    unset($base['page']);
    return '?' . http_build_query(array_filter($base, fn($v) => $v !== ''));
}
?>

<div class="alert-banner merah" style="margin:12px 24px 0;">
    <span class="alert-icon">⚠️</span>
    <span>Pastikan verifikasi ciri-ciri barang dilakukan <strong>sebelum</strong> scan barcode. Minta pengambil
        menunjukkan kartu identitas / name tag.</span>
</div>

<div class="admin-layout">
    <div class="admin-main">
        <div class="card">
            <div class="card-header">
                <span>📜 Riwayat Pengambilan Barang</span>
                <span style="font-size:12px;color:#9CA3AF;font-weight:400;">Total: <?= $total ?> pengambilan</span>
            </div>

            <!-- Search & filter -->
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                <form method="GET">
                    <div class="search-filter-bar">
                        <div class="search-wrap" style="flex:2;">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search" placeholder="Cari kode, barang, nama pengambil, kelas..."
                                value="<?= e($search) ?>">
                        </div>
                        <select name="sort" class="form-control" style="width:auto;min-width:130px;">
                            <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>⬇ Terbaru</option>
                            <option value="terlama" <?= $sort === 'terlama' ? 'selected' : '' ?>>⬆ Terlama</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        <?php if ($search || $sort !== 'terbaru'): ?>
                            <a href="riwayat.php" class="btn btn-outline btn-sm">✕ Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabel riwayat -->
            <div class="tabel-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:36px;">No</th>
                            <th>Barang</th>
                            <th>Diambil Oleh</th>
                            <th>Kelas</th>
                            <th>ID Card</th>
                            <th>Tgl Ambil</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($q) === 0): ?>
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:#9CA3AF;">
                                    <div style="font-size:32px;margin-bottom:8px;">📭</div>
                                    <div>Belum ada riwayat pengambilan</div>
                                    <?php if ($search): ?>
                                        <div style="margin-top:6px;"><a href="riwayat.php" style="color:var(--biru-mid);">Reset
                                                pencarian</a></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        else:
                            $no = $offset + 1;
                            while ($row = mysqli_fetch_assoc($q)):
                                ?>
                                <tr>
                                    <td style="color:#9CA3AF;font-size:12px;"><?= $no++ ?></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <?php if ($row['foto_barang']): ?>
                                                <img src="../uploads/<?= e($row['foto_barang']) ?>"
                                                    style="width:34px;height:34px;object-fit:cover;border-radius:6px;border:1px solid var(--border);flex-shrink:0;"
                                                    alt="">
                                            <?php else: ?>
                                                <div
                                                    style="width:34px;height:34px;background:var(--abu-muda);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
                                                    📦</div>
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight:600;"><?= e($row['jenis_barang']) ?></div>
                                                <div style="font-size:11px;font-family:monospace;color:var(--biru);">
                                                    <?= e($row['kode_barang']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-weight:600;"><?= e($row['nama_pengambil']) ?></td>
                                    <td style="font-size:12px;"><?= e($row['kelas'] ?: '—') ?></td>
                                    <td style="font-size:12px;color:#6B7280;font-family:monospace;">
                                        <?= e($row['id_card'] ?: '—') ?>
                                    </td>
                                    <td style="font-size:12px;white-space:nowrap;">
                                        <div><?= date('d M Y', strtotime($row['tanggal_ambil'])) ?></div>
                                        <div style="color:#9CA3AF;"><?= date('H:i', strtotime($row['tanggal_ambil'])) ?> WIB
                                        </div>
                                    </td>
                                    <td style="text-align:center;">
                                        <button class="btn btn-outline btn-sm" title="Lihat bukti"
                                            onclick='lihatBuktiWrapper(<?= json_encode($row) ?>)'>👁 Bukti</button>
                                    </td>
                                </tr>
                            <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer + paginasi -->
            <div
                style="padding:12px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <div style="font-size:12px;color:#9CA3AF;">
                    Menampilkan <?= $total > 0 ? min($offset + 1, $total) : 0 ?>–<?= min($offset + $per_page, $total) ?>
                    dari
                    <?= $total ?> data
                </div>
                <?php if ($total_page > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?><a href="<?= buildUrl(['page' => $page - 1]) ?>">‹</a><?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_page, $page + 2); $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $total_page): ?><a
                                href="<?= buildUrl(['page' => $page + 1]) ?>">›</a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SIDEBAR DIBUAT STICKY -->
    <div class="admin-side" style="position: sticky; top: 80px; align-self: flex-start; z-index: 10;">

        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">⚡ Aksi Cepat</div>
            <div class="aksi-list">
                <a href="scan.php" class="aksi-btn">
                    <div class="aksi-icon biru">📷</div>
                    <div>
                        <div style="font-weight:600;">Scan Barcode</div>
                        <div style="font-size:11px;color:#9CA3AF;">Proses pengambilan baru</div>
                    </div>
                </a>
                <a href="tambah.php" class="aksi-btn">
                    <div class="aksi-icon kuning">📦</div>
                    <div>
                        <div style="font-weight:600;">Tambah Barang</div>
                        <div style="font-size:11px;color:#9CA3AF;">Input barang baru</div>
                    </div>
                </a>
            </div>
        </div>
        <div class="card">
            <div class="card-header">🕐 Aktivitas Terkini</div>
            <div style="padding:14px;">
                <?php if (mysqli_num_rows($q_aktivitas) === 0): ?>
                    <p style="font-size:12px;color:#9CA3AF;text-align:center;padding:16px 0;">Belum ada aktivitas</p>
                <?php else:
                    while ($akt = mysqli_fetch_assoc($q_aktivitas)): ?>
                        <div class="aktivitas-item">
                            <div class="aktivitas-dot"></div>
                            <div>
                                <div style="font-weight:500;"><?= e($akt['kode_barang']) ?> — <?= e($akt['jenis_barang']) ?>
                                </div>
                                <div style="color:#6B7280;">Diambil: <strong><?= e($akt['nama_pengambil']) ?></strong>
                                    <?= e($akt['kelas']) ?></div>
                                <div style="color:#9CA3AF;font-size:11px;">
                                    <?= date('d M Y, H:i', strtotime($akt['tanggal_ambil'])) ?> WIB
                                </div>
                            </div>
                        </div>
                    <?php endwhile; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL BUKTI PENGAMBILAN -->

<?php
// Embed logo sebagai base64 agar tampil di modal & print window tanpa masalah path
$logoPath = __DIR__ . '/../assets/css/img/smeas.png';
$logoB64 = file_exists($logoPath)
    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
    : '';
?>

<!-- MODAL BUKTI PENGAMBILAN -->
<div class="modal-overlay" id="modalBukti">
    <div class="modal-box" style="max-width:400px;width:100%;">
        <div class="modal-header">
            <span>📋 Bukti Pengambilan</span>
            <button class="modal-close" onclick="tutupModal('modalBukti')">✕</button>
        </div>

        <div class="modal-body" style="padding:0;max-height:80vh;overflow-y:auto;">
            <div id="areaBuktiModal"
                style="background:#fff;font-family:'Courier New',Courier,monospace;font-size:12px;color:#111;">

                <!-- KOP -->
                <div style="background:#fff;padding:20px 20px 16px;text-align:center;border-bottom:3px solid #1B3A6B;">
                    <?php if ($logoB64): ?>
                        <img src="<?= $logoB64 ?>" alt="SMKN 1 Surabaya"
                            style="width:150px;height:auto;display:block;margin:0 auto 10px;">
                    <?php endif; ?>
                    <div
                        style="color:#1B3A6B;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">
                        BUKTI PENGAMBILAN BARANG
                    </div>
                    <div style="color:rgba(255,255,255,0.6);font-size:10px;margin-top:3px;">
                    </div>
                </div>

                <div style="border-top:2px dashed #9CA3AF;"></div>

                <div style="padding:16px 20px;">

                    <!-- ID Barang -->
                    <div style="text-align:center;margin-bottom:14px;">
                        <div
                            style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:6px;">
                            ID Barang</div>
                        <div id="bKode" style="font-size:26px;font-weight:700;letter-spacing:5px;color:#000;"></div>
                    </div>

                    <div style="border-top:1px dashed #D1D5DB;margin-bottom:12px;"></div>

                    <!-- Info barang -->
                    <div
                        style="display:grid;grid-template-columns:100px 1fr;gap:6px 8px;font-size:12px;margin-bottom:12px;">
                        <div style="color:#9CA3AF;">Jenis Barang</div>
                        <div style="font-weight:700;" id="bJenis"></div>
                        <div style="color:#9CA3AF;">Warna / Ciri</div>
                        <div id="bWarna"></div>
                    </div>

                    <div style="border-top:1px dashed #D1D5DB;margin-bottom:12px;"></div>

                    <!-- Info pengambil -->
                    <div
                        style="display:grid;grid-template-columns:100px 1fr;gap:6px 8px;font-size:12px;margin-bottom:12px;">
                        <div style="color:#9CA3AF;">Diambil Oleh</div>
                        <div style="font-weight:700;" id="bNama"></div>
                        <div style="color:#9CA3AF;">Kelas</div>
                        <div id="bKelas"></div>
                        <div style="color:#9CA3AF;">Tanggal</div>
                        <div id="bTanggal"></div>
                        <div style="color:#9CA3AF;">Jam</div>
                        <div id="bJam"></div>
                        <div style="color:#9CA3AF;">Petugas</div>
                        <div style="font-weight:700;" id="bPetugas"></div>
                    </div>

                    <div style="border-top:1px dashed #D1D5DB;margin-bottom:14px;"></div>

                    <!-- QR Code -->
                    <div style="text-align:center;">
                        <div
                            style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">
                            Scan QR — download bukti di HP</div>
                        <div id="qrPreview"
                            style="display:inline-block;padding:8px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;">
                        </div>
                        <div style="font-size:9px;color:#9CA3AF;margin-top:6px;">Scan dengan kamera HP</div>
                    </div>

                </div>

                <!-- FOOTER -->
                <div style="border-top:2px dashed #9CA3AF;background:#F9FAFB;padding:10px 20px;text-align:center;">
                    <div style="font-size:9.5px;color:#9CA3AF;line-height:1.6;">
                        Simpan bukti ini sebagai tanda terima resmi.<br>
                        Data tercatat di sistem Lost &amp; Found SMKN 1 Surabaya.
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer" style="gap:8px;">
            <button onclick="tutupModal('modalBukti')" class="btn btn-outline">Tutup</button>
            <button onclick="downloadBukti()" class="btn btn-primary">⬇️ Download</button>
            <button onclick="cetakModal()" class="btn btn-kuning">🖨️ Cetak</button>
        </div>
    </div>
</div>

<!-- QRCode.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- html2canvas untuk download PNG -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    const namaPetugas = "<?php echo addslashes($_SESSION['admin_nama'] ?? 'Admin'); ?>";
    const logoB64 = "<?php echo $logoB64; ?>";

    function tutupModal(id) { document.getElementById(id).classList.remove('active'); }
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) tutupModal(m.id); });
    });

    let qrInstance = null;

    function lihatBukti(data) {
        document.getElementById('bKode').textContent = data.kode_barang;
        document.getElementById('bJenis').textContent = data.jenis_barang;
        document.getElementById('bWarna').textContent = data.warna || '—';
        document.getElementById('bNama').textContent = data.nama_pengambil;
        document.getElementById('bKelas').textContent = data.kelas || '—';
        document.getElementById('bPetugas').textContent = namaPetugas;

        const tgl = new Date(data.tanggal_ambil);
        document.getElementById('bTanggal').textContent = tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        document.getElementById('bJam').textContent = tgl.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

        // Generate QR Code
        const qrEl = document.getElementById('qrPreview');
        qrEl.innerHTML = '';
        qrInstance = new QRCode(qrEl, {
            text: 'http://172.16.49.31/lostfound/bukti.php?kode=' + data.kode_barang,
            width: 110,
            height: 110,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });

        document.getElementById('modalBukti').classList.add('active');
    }

    function buildPrintHTML(data) {
        const tgl = new Date(data.tanggal_ambil);
        const tanggal = tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        const jam = tgl.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

        // Ambil QR sebagai data URL dari canvas yang sudah dirender
        const qrCanvas = document.querySelector('#qrPreview canvas');
        const qrSrc = qrCanvas ? qrCanvas.toDataURL('image/png') : '';

        const logoTag = logoB64
            ? `<img src="${logoB64}" style="width:140px;height:auto;display:block;margin:0 auto 10px;">`
            : '';

        return `<!DOCTYPE html><html><head>
    <title>Bukti — ${data.kode_barang}</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Courier New',Courier,monospace;font-size:12px;color:#000;background:#fff;width:72mm;margin:0 auto;}
        .kop{background:#fff;color:#1B3A6B;text-align:center;padding:20px 16px 14px;border-bottom:3px solid #1B3A6B;}
        .kop-title{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#1B3A6B;}
        .dash{border-top:2px dashed #9CA3AF;}
        .body{padding:12px 14px;}
        .kode-wrap{text-align:center;margin-bottom:12px;}
        .kode-label{font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:1.5px;}
        .kode-val{font-size:20px;font-weight:700;letter-spacing:4px;color:#000;margin-top:4px;}
        .grid{display:grid;grid-template-columns:90px 1fr;gap:5px 8px;font-size:11px;margin-bottom:12px;}
        .lbl{color:#9CA3AF;}
        .val{font-weight:600;}
        .qr-wrap{text-align:center;margin:4px 0 8px;}
        .qr-label{font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;}
        .footer{background:#F3F4F6;border-top:2px dashed #9CA3AF;padding:8px 10px;text-align:center;font-size:9.5px;color:#9CA3AF;line-height:1.6;}
        @media print{body{width:72mm;}@page{margin:3mm;size:80mm auto;}}
    </style>
    </head><body>
    <div class="kop">
        ${logoTag}
        <div class="kop-title">Bukti Pengambilan Barang</div>
    </div>
    <div class="dash"></div>
    <div class="body">
        <div class="kode-wrap">
            <div class="kode-label">ID Barang</div>
            <div class="kode-val">${data.kode_barang}</div>
        </div>
        <div class="dash" style="margin-bottom:12px;"></div>
        <div class="grid">
            <div class="lbl">Jenis Barang</div><div class="val">${data.jenis_barang}</div>
            <div class="lbl">Warna / Ciri</div><div class="val">${data.warna || '—'}</div>
        </div>
        <div class="dash" style="margin-bottom:12px;"></div>
        <div class="grid">
            <div class="lbl">Diambil Oleh</div><div class="val">${data.nama_pengambil}</div>
            <div class="lbl">Kelas</div><div class="val">${data.kelas || '—'}</div>
            <div class="lbl">Tanggal</div><div class="val">${tanggal}</div>
            <div class="lbl">Jam</div><div class="val">${jam}</div>
            <div class="lbl">Petugas</div><div class="val">${namaPetugas}</div>
        </div>
        <div class="dash" style="margin-bottom:12px;"></div>
        <div class="qr-wrap">
            <div class="qr-label">Scan QR — download bukti di HP</div>
            ${qrSrc ? `<img src="${qrSrc}" style="width:100px;height:100px;">` : ''}
        </div>
    </div>
    <div class="footer">
        Simpan bukti ini sebagai tanda terima resmi.<br>
        Data tercatat di sistem Lost &amp; Found SMKN 1 Surabaya.
    </div>`;
    }

    let _lastData = null;

    function lihatBuktiWrapper(data) {
        _lastData = data;
        lihatBukti(data);
    }

    function cetakModal() {
        if (!_lastData) return;
        const win = window.open('', '_blank', 'width=380,height=650');
        win.document.write(buildPrintHTML(_lastData));
        win.document.write(`<script>window.onload=function(){setTimeout(function(){window.print();window.close();},600);};<\/script></body></html>`);
        win.document.close();
    }

    function downloadBukti() {
        if (!_lastData) return;
        html2canvas(document.getElementById('areaBuktiModal'), {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'bukti-' + (_lastData.kode_barang || 'pengambilan') + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }
</script>

<?php require_once 'partials/footer.php'; ?>