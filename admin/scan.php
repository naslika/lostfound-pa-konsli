<?php
// ============================================================
//  SCAN BARCODE — ADMIN
//  File: admin/scan.php
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

$barang = null;
$error = '';

// ---- CARI BARANG BY KODE ----
if (isset($_GET['kode']) && $_GET['kode'] !== '') {
    $kode = strtoupper(trim($_GET['kode']));
    $stmt = mysqli_prepare(
        $conn,
        "SELECT b.*, p.nama_pengambil, p.kelas, p.tanggal_ambil
         FROM barang b LEFT JOIN pengambilan p ON b.id = p.id_barang
         WHERE b.kode_barang = ?"
    );
    mysqli_stmt_bind_param($stmt, 's', $kode);
    mysqli_stmt_execute($stmt);
    $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$barang)
        $error = "Kode \"$kode\" tidak ditemukan di database.";
}

// ---- PROSES SERAHKAN BARANG ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_ambil'])) {
    $id_barang = (int) $_POST['id_barang'];
    $nama_pengambil = trim($_POST['nama_pengambil']);
    $kelas = trim($_POST['kelas']);
    $id_card = trim($_POST['id_card']);
    $kode_barang = trim($_POST['kode_barang']);

    if (empty($nama_pengambil)) {
        $error = 'Nama pengambil wajib diisi.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO pengambilan (id_barang, nama_pengambil, kelas, id_card) VALUES (?,?,?,?)"
        );
        mysqli_stmt_bind_param($stmt, 'isss', $id_barang, $nama_pengambil, $kelas, $id_card);
        mysqli_stmt_execute($stmt);
        mysqli_query($conn, "UPDATE barang SET status='sudah_diambil' WHERE id=$id_barang");
        // Ambil id pengambilan yang baru disimpan
        $id_pengambilan = mysqli_insert_id($conn);
        header("Location: riwayat.php?cetak=$id_pengambilan");
        exit();
    }
}

// ---- LOAD DATA SETELAH SUKSES ----
if (isset($_GET['sukses'])) {
    $sid = (int) $_GET['sukses'];
    $stmt = mysqli_prepare(
        $conn,
        "SELECT b.*, p.nama_pengambil, p.kelas, p.id_card, p.tanggal_ambil
         FROM barang b LEFT JOIN pengambilan p ON b.id = p.id_barang
         WHERE b.id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $sid);
    mysqli_stmt_execute($stmt);
    $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$stat_belum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='belum_diambil'"))['n'];
$stat_sudah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='sudah_diambil'"))['n'];
$stat_hari = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE DATE(tanggal_ditemukan)=CURDATE()"))['n'];
$q_aktivitas = mysqli_query(
    $conn,
    "SELECT * FROM (
        SELECT 'ditemukan' as tipe, b.kode_barang, b.jenis_barang,
               NULL as nama_pengambil, NULL as kelas, b.created_at as waktu
        FROM barang b WHERE DATE(b.created_at)=CURDATE()
        UNION ALL
        SELECT 'diambil' as tipe, b.kode_barang, b.jenis_barang,
               p.nama_pengambil, p.kelas, p.tanggal_ambil as waktu
        FROM pengambilan p JOIN barang b ON p.id_barang=b.id
        WHERE DATE(p.tanggal_ambil)=CURDATE()
    ) aktivitas ORDER BY waktu DESC LIMIT 10"
);

$page_active = 'scan';
$page_title = 'Scan Barcode';
require_once 'partials/header.php';
?>



<div class="alert-banner merah" style="margin:12px 24px 0;">
    <span class="alert-icon">⚠️</span>
    <span>Pastikan verifikasi ciri-ciri barang dilakukan <strong>sebelum</strong> scan barcode. Minta pengambil
        menunjukkan kartu identitas / name tag.</span>
</div>

<div class="admin-layout">
    <div class="admin-main">

        <!-- NOTIF SUKSES + BUKTI -->
        <?php if (isset($_GET['sukses']) && $barang): ?>
            <div class="card" style="margin-bottom:16px;border-color:#6EE7B7;">
                <div class="modal-header"
                    style="background:var(--hijau);border-radius:var(--radius-lg) var(--radius-lg) 0 0;">
                    <span>✅ Barang Berhasil Diserahkan!</span>
                    <a href="scan.php" style="color:#fff;font-size:13px;text-decoration:none;font-weight:500;">Scan Lagi
                        →</a>
                </div>
                <div style="padding:20px;">

                    <!-- PREVIEW BUKTI -->
                    <div id="areaBukti" style="
                        background:#fff;
                        border:1px solid #D1D5DB;
                        border-radius:10px;
                        max-width:340px;
                        margin:0 auto;
                        font-family:'Courier New',Courier,monospace;
                        font-size:12px;
                        color:#111;
                        overflow:hidden;
                    ">
                        <!-- KOP -->
                        <div style="background:#000;padding:20px 20px 16px;text-align:center;">
                            <img src="../assets/css/img/smeas.png" alt="SMKN 1 Surabaya"
                                style="width:130px;height:auto;filter:brightness(0) invert(1);display:block;margin:0 auto 12px;">
                            <div
                                style="color:#fff;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">
                                BUKTI PENGAMBILAN BARANG
                            </div>
                            <div style="color:#ccc;font-size:10px;margin-top:2px;">Lost & Found — SMKN 1 Surabaya</div>
                        </div>

                        <!-- GARIS PUTUS -->
                        <div style="border-top:2px dashed #D1D5DB;margin:0;"></div>

                        <!-- ISI -->
                        <div style="padding:14px 16px;">

                            <!-- Kode besar di tengah -->
                            <div style="text-align:center;margin-bottom:12px;">
                                <div
                                    style="font-size:10px;color:#6B7280;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                                    ID Barang</div>
                                <div class="kode-val-data"
                                    style="font-size:22px;font-weight:700;letter-spacing:4px;color:#000;">
                                    <?= e($barang['kode_barang']) ?>
                                </div>
                            </div>

                            <div style="border-top:1px dashed #D1D5DB;margin-bottom:10px;"></div>

                            <!-- Detail barang -->
                            <table style="width:100%;border-collapse:collapse;font-size:11.5px;line-height:1.9;">
                                <tr>
                                    <td style="color:#6B7280;width:100px;vertical-align:top;">Jenis Barang</td>
                                    <td style="font-weight:700;"><?= e($barang['jenis_barang']) ?></td>
                                </tr>
                                <tr>
                                    <td style="color:#6B7280;vertical-align:top;">Warna / Ciri</td>
                                    <td><?= e($barang['warna'] ?: '—') ?></td>
                                </tr>
                                <tr>
                                    <td style="color:#6B7280;vertical-align:top;">Status</td>
                                    <td style="font-weight:700;">SUDAH DIAMBIL</td>
                                </tr>
                            </table>

                            <div style="border-top:1px dashed #D1D5DB;margin:10px 0;"></div>

                            <!-- Detail pengambil -->
                            <table style="width:100%;border-collapse:collapse;font-size:11.5px;line-height:1.9;">
                                <tr>
                                    <td style="color:#6B7280;width:100px;">Diambil Oleh</td>
                                    <td style="font-weight:700;"><?= e($barang['nama_pengambil']) ?></td>
                                </tr>
                                <tr>
                                    <td style="color:#6B7280;">Kelas</td>
                                    <td><?= e($barang['kelas'] ?: '—') ?></td>
                                </tr>
                                <tr>
                                    <td style="color:#6B7280;">ID Card</td>
                                    <td><?= e($barang['id_card'] ?: '—') ?></td>
                                </tr>
                                <tr>
                                    <td style="color:#6B7280;">Tanggal</td>
                                    <td><?= date('d-m-Y', strtotime($barang['tanggal_ambil'])) ?></td>
                                </tr>
                                <tr>
                                    <td style="color:#6B7280;">Jam</td>
                                    <td><?= date('H:i', strtotime($barang['tanggal_ambil'])) ?> WIB</td>
                                </tr>
                            </table>

                            <div style="border-top:1px dashed #D1D5DB;margin:12px 0 10px;"></div>

                            <!-- Tanda tangan -->
                            <div
                                style="display:flex;justify-content:space-between;font-size:10.5px;color:#6B7280;margin-bottom:4px;">
                                <div style="text-align:center;width:45%;">
                                    <div>Petugas</div>
                                    <div style="height:36px;border-bottom:1px solid #111;margin:6px 0;"></div>
                                    <div style="font-size:10px;">( ________________ )</div>
                                </div>
                                <div style="text-align:center;width:45%;">
                                    <div>Pengambil</div>
                                    <div style="height:36px;border-bottom:1px solid #111;margin:6px 0;"></div>
                                    <div style="font-size:10px;">( ________________ )</div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div style="border-top:2px dashed #D1D5DB;background:#F9FAFB;padding:8px 16px;text-align:center;">
                            <div style="font-size:9.5px;color:#9CA3AF;">
                                Simpan bukti ini sebagai tanda terima resmi.<br>
                                Data tercatat di sistem Lost & Found SMKN 1 Surabaya.
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL AKSI -->
                    <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap;">
                        <button onclick="cetakBukti()" class="btn btn-kuning">🖨️ Cetak Bukti</button>
                        <a href="scan.php" class="btn btn-primary">📷 Scan Berikutnya</a>
                        <a href="riwayat.php" class="btn btn-outline">📜 Lihat Riwayat</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- AREA SCAN -->
        <?php if (!isset($_GET['sukses'])): ?>
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header">📷 Scan Barcode Pengambilan</div>
                <div style="padding:20px;">
                    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start;">

                        <!-- Input manual / scanner fisik -->
                        <div style="flex:1;min-width:200px;">
                            <div
                                style="padding:16px;background:var(--abu-muda);border-radius:10px;border:1px solid var(--border);">
                                <div style="font-size:13px;font-weight:600;margin-bottom:4px;">⌨️ Input Kode Manual</div>
                                <div style="font-size:12px;color:#9CA3AF;margin-bottom:12px;">
                                    Arahkan scanner ke barcode pada barang, atau ketik kode secara manual
                                </div>
                                <form method="GET" id="formCari" action="scan.php#hasilBarang">
                                    <div class="form-group" style="margin-bottom:8px;">
                                        <label>ID Barang</label>
                                        <input type="text" name="kode" id="inputKode" class="form-control"
                                            placeholder="LF-001" value="<?= e(strtoupper($_GET['kode'] ?? '')) ?>"
                                            style="font-family:monospace;font-size:16px;font-weight:700;letter-spacing:3px;text-transform:uppercase;"
                                            autocomplete="off">
                                    </div>
                                    <button type="submit" class="btn btn-primary"
                                        style="width:100%;justify-content:center;">🔍 Cari Barang</button>
                                </form>
                            </div>
                            <?php if ($error): ?>
                                <div class="alert-banner merah" style="margin-top:10px;"><span>⚠️</span> <?= e($error) ?></div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <!-- HASIL: INFO BARANG + FORM IDENTITAS -->
            <?php if ($barang): ?>
                <div class="card" id="hasilBarang">
                    <div class="card-header">📦 Barang Ditemukan — <?= e($barang['kode_barang']) ?></div>
                    <div style="padding:20px;">

                        <?php if ($barang['status'] === 'sudah_diambil'): ?>
                            <div class="alert-banner"
                                style="background:#FEF3C7;border:1px solid #FDE68A;color:#92400E;margin-bottom:16px;">
                                <span>⚠️</span>
                                <span>Barang ini <strong>sudah diambil</strong> oleh
                                    <strong><?= e($barang['nama_pengambil']) ?></strong>
                                    <?= e($barang['kelas']) ?> pada <?= date('d M Y H:i', strtotime($barang['tanggal_ambil'])) ?>
                                    WIB.</span>
                            </div>
                        <?php endif; ?>

                        <div style="display:flex;gap:16px;flex-wrap:wrap;">
                            <!-- Info barang -->
                            <div style="display:flex;gap:12px;flex:1;min-width:220px;">
                                <?php if ($barang['foto_barang']): ?>
                                    <img src="../uploads/<?= e($barang['foto_barang']) ?>"
                                        style="width:90px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border);flex-shrink:0;"
                                        alt="">
                                <?php else: ?>
                                    <div
                                        style="width:90px;height:90px;background:var(--abu-muda);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:36px;flex-shrink:0;">
                                        📦</div>
                                <?php endif; ?>
                                <table style="font-size:13px;border-collapse:collapse;">
                                    <tr>
                                        <td style="color:#6B7280;padding:4px 0;width:110px;">Jenis Barang</td>
                                        <td style="font-weight:600;"><?= e($barang['jenis_barang']) ?></td>
                                    </tr>
                                    <tr>
                                        <td style="color:#6B7280;padding:4px 0;">Warna / Ciri</td>
                                        <td><?= e($barang['warna'] ?: '—') ?></td>
                                    </tr>
                                    <tr>
                                        <td style="color:#6B7280;padding:4px 0;">Lokasi</td>
                                        <td><?= e($barang['lokasi_ditemukan']) ?></td>
                                    </tr>
                                    <tr>
                                        <td style="color:#6B7280;padding:4px 0;">Tgl Ditemukan</td>
                                        <td><?= tglIndo($barang['tanggal_ditemukan']) ?></td>
                                    </tr>
                                    <tr>
                                        <td style="color:#6B7280;padding:4px 0;">Status</td>
                                        <td>
                                            <?php if ($barang['status'] === 'belum_diambil'): ?>
                                                <span class="badge badge-belum">⏳ Belum Diambil</span>
                                            <?php else: ?>
                                                <span class="badge badge-sudah">✅ Sudah Diambil</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Tombol buka modal — hanya kalau belum diambil -->
                            <?php if ($barang['status'] === 'belum_diambil'): ?>
                                <div style="margin-top:16px;">
                                    <button onclick="document.getElementById('modalSerahkan').classList.add('active')"
                                        class="btn btn-success" style="padding:12px 24px;font-size:14px;">
                                        ✅ Serahkan Barang
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- MODAL FORM IDENTITAS PENGAMBIL -->
                <?php if ($barang['status'] === 'belum_diambil'): ?>
                    <div class="modal-overlay" id="modalSerahkan">
                        <div class="modal-box" style="max-width:420px;">
                            <div class="modal-header">
                                <span>👤 Identitas Pengambil</span>
                                <button class="modal-close"
                                    onclick="document.getElementById('modalSerahkan').classList.remove('active')">✕</button>
                            </div>
                            <!-- Info barang singkat di modal -->
                            <div
                                style="padding:12px 20px;background:var(--biru-muda);border-bottom:1px solid var(--border);font-size:13px;">
                                <strong><?= e($barang['kode_barang']) ?></strong> — <?= e($barang['jenis_barang']) ?>
                                <?php if ($barang['warna']): ?>
                                    <span style="color:#6B7280;"> | <?= e($barang['warna']) ?></span>
                                <?php endif; ?>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="proses_ambil" value="1">
                                <input type="hidden" name="id_barang" value="<?= $barang['id'] ?>">
                                <input type="hidden" name="kode_barang" value="<?= e($barang['kode_barang']) ?>">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Nama Pengambil <span style="color:red;">*</span></label>
                                        <input type="text" name="nama_pengambil" class="form-control"
                                            placeholder="Sesuai name tag / ID card" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Kelas</label>
                                        <input type="text" name="kelas" class="form-control" placeholder="Contoh: 11 DKV 1">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label>Nomor ID Card</label>
                                        <input type="text" name="id_card" class="form-control" placeholder="Nomor ID card siswa">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline"
                                        onclick="document.getElementById('modalSerahkan').classList.remove('active')">Batal</button>
                                    <button type="submit" class="btn btn-success"
                                        onclick="return confirm('Konfirmasi penyerahan barang kepada pengambil ini?')">
                                        ✅ Serahkan & Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- SIDEBAR DIBUAT STICKY -->
    <div class="admin-side" style="position: sticky; top: 80px; align-self: flex-start; z-index: 10;">

        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">⚡ Aksi Cepat</div>
            <div class="aksi-list">
                <a href="tambah.php" class="aksi-btn">
                    <div class="aksi-icon kuning">📦</div>
                    <div>
                        <div style="font-weight:600;">Tambah Barang Baru</div>
                        <div style="font-size:11px;color:#9CA3AF;">Input barang yang ditemukan</div>
                    </div>
                </a>
                <a href="riwayat.php" class="aksi-btn">
                    <div class="aksi-icon hijau">📜</div>
                    <div>
                        <div style="font-weight:600;">Riwayat Pengambilan</div>
                        <div style="font-size:11px;color:#9CA3AF;">Lihat semua bukti</div>
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

<style>
    @keyframes scanMove {
        0% {
            top: 10%;
        }

        50% {
            top: 85%;
        }

        100% {
            top: 10%;
        }
    }

    /* Indikator fokus aktif di input scanner */
    #inputKode:focus {
        outline: 2px solid #2563EB;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* Badge ready scanner */
    #scannerStatus {
        display: inline-block;
        font-size: 11px;
        padding: 3px 10px;
        border-radius: 20px;
        margin-top: 6px;
        font-weight: 600;
    }

    #scannerStatus.ready {
        background: #D1FAE5;
        color: #065F46;
    }

    #scannerStatus.blur {
        background: #FEF3C7;
        color: #92400E;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
    // ============================================================
    //  KAMERA SCANNER
    // ============================================================
    let streamScan = null;
    let scanInterval = null;

    async function aktifkanScanner() {
        try {
            document.getElementById('statusScan').textContent = 'Memulai kamera...';
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            streamScan = stream;
            const video = document.getElementById('scanVideo');
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('scanPlaceholder').style.display = 'none';
            document.getElementById('scanOverlay').style.display = 'block';
            document.getElementById('btnAktifkan').style.display = 'none';
            document.getElementById('btnStop').style.display = 'inline-flex';
            document.getElementById('statusScan').textContent = '🟢 Kamera aktif — arahkan barcode ke tengah frame';
        } catch (e) {
            document.getElementById('statusScan').textContent = '❌ ' + e.message;
            document.getElementById('statusScan').style.color = '#DC2626';
        }
    }

    function stopScanner() {
        if (streamScan) { streamScan.getTracks().forEach(t => t.stop()); streamScan = null; }
        if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
        document.getElementById('scanVideo').style.display = 'none';
        document.getElementById('scanPlaceholder').style.display = 'flex';
        document.getElementById('scanOverlay').style.display = 'none';
        document.getElementById('btnAktifkan').style.display = 'inline-flex';
        document.getElementById('btnStop').style.display = 'none';
        document.getElementById('statusScan').textContent = '';
    }

    // ============================================================
    //  SCANNER FISIK — FIX FOKUS (iWare / USB / Bluetooth)
    // ============================================================
    const inputKode = document.getElementById('inputKode');
    const formCari = document.getElementById('formCari');
    const formPost = document.querySelector('form[method="POST"]');

    // Badge status fokus
    let badgeEl = null;
    if (inputKode) {
        badgeEl = document.createElement('div');
        badgeEl.id = 'scannerStatus';
        inputKode.parentElement.parentElement.appendChild(badgeEl);
    }

    function setFokusBadge(aktif) {
        if (!badgeEl) return;
        if (aktif) {
            badgeEl.textContent = '🟢 Scanner siap — arahkan & scan';
            badgeEl.className = 'ready';
        } else {
            badgeEl.textContent = '🟡 Klik halaman ini agar scanner aktif';
            badgeEl.className = 'blur';
        }
    }

    function fokusInput() {
        if (!inputKode) return;
        // Jangan rebut fokus kalau user lagi isi form identitas pengambil
        if (formPost && formPost.contains(document.activeElement)) return;
        inputKode.focus();
    }

    if (inputKode) {
        // 1. Uppercase realtime
        inputKode.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });

        // 2. Enter = submit (scanner fisik selalu kirim Enter di akhir scan)
        inputKode.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    formCari.submit();
                }
            }
        });

        // 3. Update badge saat fokus berubah
        inputKode.addEventListener('focus', () => setFokusBadge(true));
        inputKode.addEventListener('blur', () => setFokusBadge(false));

        // 4. Fokus awal — pakai PHP variable langsung, tidak ada ambiguitas timing
        const adaHasilBarang = <?= ($barang && !isset($_GET['sukses'])) ? 'true' : 'false' ?>;

        setTimeout(() => {
            if (!adaHasilBarang) {
                fokusInput();
                setFokusBadge(document.activeElement === inputKode);
            } else {
                // Ada hasil → scroll ke hasilBarang
                const el = document.getElementById('hasilBarang');
                if (el) {
                    const top = el.getBoundingClientRect().top + window.pageYOffset - 130;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
            }
        }, 300);

        // 5. Klik di area halaman → kembalikan fokus ke input
        document.addEventListener('click', function (e) {
            if (adaHasilBarang) return; // ada hasil, jangan rebut fokus
            if (formPost && formPost.contains(e.target)) return;
            if (e.target === inputKode) return;
            setTimeout(fokusInput, 50);
        });

        // 6. Saat tab/window kembali aktif
        window.addEventListener('focus', () => {
            if (adaHasilBarang) return;
            setTimeout(fokusInput, 200);
        });
    }

    // Tutup modal saat klik overlay
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) m.classList.remove('active');
        });
    });

    // Auto-scroll sudah ditangani oleh anchor #hasilBarang di form action

    // ============================================================
    //  CETAK BUKTI PENGAMBILAN — Thermal optimized
    // ============================================================
    function cetakBukti() {
        const area = document.getElementById('areaBukti');
        if (!area) return;

        // Ambil data langsung dari elemen PHP yang sudah dirender
        const rows = area.querySelectorAll('table tr');
        const getData = (label) => {
            for (const row of rows) {
                const tds = row.querySelectorAll('td');
                if (tds.length >= 2 && tds[0].textContent.trim() === label) {
                    return tds[1].textContent.trim();
                }
            }
            return '—';
        };

        const kode = area.querySelector('.kode-val-data')?.textContent.trim() || '<?= e($barang["kode_barang"]) ?>';
        const jenis = getData('Jenis Barang');
        const warna = getData('Warna / Ciri');
        const diambilOleh = getData('Diambil Oleh');
        const kelas = getData('Kelas');
        const idCard = getData('ID Card');
        const tanggal = getData('Tanggal');
        const jam = getData('Jam');

        const imgBase = window.location.origin + window.location.pathname.replace(/\/[^/]+$/, '');

        const win = window.open('', '_blank', 'width=380,height=650');
        win.document.write(`<!DOCTYPE html><html><head>
    <title>Bukti Pengambilan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
            width: 72mm;
            margin: 0 auto;
        }
        .kop { background:#000; color:#fff; text-align:center; padding:20px 16px 14px; }
        .kop img { width:120px; height:auto; display:block; margin:0 auto 10px; filter:brightness(0) invert(1); }
        .kop-title { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; }
        .kop-sub   { font-size:9px; color:#ccc; margin-top:2px; }
        .dash      { border-top:1.5px dashed #999; }
        .body      { padding:12px 10px; }
        .kode-wrap { text-align:center; margin-bottom:10px; }
        .kode-label { font-size:9px; color:#555; text-transform:uppercase; letter-spacing:1px; }
        .kode-val   { font-size:20px; font-weight:700; letter-spacing:4px; margin-top:2px; }
        table  { width:100%; border-collapse:collapse; font-size:11px; line-height:1.85; }
        td.lbl { color:#555; width:90px; vertical-align:top; }
        td.val { font-weight:600; }
        .ttd-wrap { display:flex; justify-content:space-between; margin-top:12px; font-size:10px; color:#555; }
        .ttd-box  { text-align:center; width:46%; }
        .ttd-line { height:32px; border-bottom:1px solid #000; margin:6px 0 4px; }
        .footer {
            background:#F3F4F6; border-top:1.5px dashed #999;
            padding:7px 10px; text-align:center;
            font-size:9.5px; color:#666; line-height:1.6;
        }
        @media print {
            body { width:72mm; }
            @page { margin:3mm; size:80mm auto; }
        }
    </style>
    </head><body>

    <div class="kop">
        <img src="${imgBase}/../assets/css/img/smeas.png" alt="SMKN 1 Surabaya">
        <div class="kop-title">Bukti Pengambilan Barang</div>
        <div class="kop-sub">Lost &amp; Found &mdash; SMKN 1 Surabaya</div>
    </div>
    <div class="dash"></div>

    <div class="body">
        <div class="kode-wrap">
            <div class="kode-label">ID Barang</div>
            <div class="kode-val">${kode}</div>
        </div>
        <div class="dash" style="margin-bottom:10px;"></div>

        <table>
            <tr><td class="lbl">Jenis Barang</td><td class="val">${jenis}</td></tr>
            <tr><td class="lbl">Warna / Ciri</td><td class="val">${warna}</td></tr>
            <tr><td class="lbl">Status</td><td class="val">SUDAH DIAMBIL</td></tr>
        </table>
        <div class="dash" style="margin:10px 0;"></div>

        <table>
            <tr><td class="lbl">Diambil Oleh</td><td class="val">${diambilOleh}</td></tr>
            <tr><td class="lbl">Kelas</td><td class="val">${kelas}</td></tr>
            <tr><td class="lbl">ID Card</td><td class="val">${idCard}</td></tr>
            <tr><td class="lbl">Tanggal</td><td class="val">${tanggal}</td></tr>
            <tr><td class="lbl">Jam</td><td class="val">${jam}</td></tr>
        </table>
        <div class="dash" style="margin:10px 0;"></div>

        <div class="ttd-wrap">
            <div class="ttd-box">
                <div>Petugas</div>
                <div class="ttd-line"></div>
                <div>( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
            </div>
            <div class="ttd-box">
                <div>Pengambil</div>
                <div class="ttd-line"></div>
                <div>( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Simpan bukti ini sebagai tanda terima resmi.<br>
        Data tercatat di sistem Lost &amp; Found SMKN 1 Surabaya.
    </div>

    <script>
        window.onload = function() { setTimeout(function(){ window.print(); window.close(); }, 500); };
    <\/script>
    </body></html>`);
        win.document.close();
    }
</script>

<?php require_once 'partials/footer.php'; ?>