<?php
// ============================================================
//  DASHBOARD ADMIN — FIXED ALL
//  File: admin/dashboard.php
// ============================================================
session_start();
if (!isset($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true) {
    header("Location: login.php"); exit();
}
require_once '../config/db.php';
// Pastikan koneksi tersedia
if (!isset($conn)) {
    die('Koneksi database tidak ditemukan. Cek config/db.php');
}

// ================================================================
// HAPUS — WAJIB DI ATAS SEBELUM ADA OUTPUT HTML APAPUN
// ================================================================
if (isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    $r   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_barang FROM barang WHERE id=$hid"));
    if ($r && $r['foto_barang'] && file_exists('../uploads/' . $r['foto_barang'])) {
        unlink('../uploads/' . $r['foto_barang']);
    }
    mysqli_query($conn, "DELETE FROM barang WHERE id=$hid");
    // Redirect balik dengan filter yang sama
    $qs = http_build_query(array_filter([
        'status' => $_GET['fs'] ?? '',
        'hari'   => $_GET['fh'] ?? '',
    ]));
    header("Location: dashboard.php" . ($qs ? "?$qs" : ''));
    exit();
}

// ---- FILTER dari klik stat bar ----
$filter_status = trim($_GET['status'] ?? '');
$filter_hari   = isset($_GET['hari']) && $_GET['hari'] === '1';

// ---- STATISTIK ----
$stat_belum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='belum_diambil'"))['n'];
$stat_sudah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='sudah_diambil'"))['n'];
$stat_hari  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE DATE(created_at)=CURDATE()"))['n'];

// ---- BUILD WHERE ----
$where = ["1=1"];
if ($filter_status === 'belum_diambil') $where[] = "b.status='belum_diambil'";
if ($filter_status === 'sudah_diambil') $where[] = "b.status='sudah_diambil'";
if ($filter_hari)                       $where[] = "DATE(b.created_at)=CURDATE()";
$sql_where = implode(' AND ', $where);

// ---- DATA TABEL ----
$q_barang = mysqli_query($conn,
    "SELECT b.*, p.nama_pengambil, p.kelas
     FROM barang b
     LEFT JOIN pengambilan p ON b.id = p.id_barang
     WHERE $sql_where
     ORDER BY b.created_at DESC LIMIT 10"
);
$total_semua = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as n FROM barang b WHERE $sql_where"))['n'];

// ---- AKTIVITAS TERKINI: ditemukan + diambil hari ini ----
$q_aktivitas = mysqli_query($conn,
    "SELECT 'ditemukan' as tipe, b.kode_barang, b.jenis_barang,
            NULL as nama_pengambil, NULL as kelas, b.created_at as waktu
     FROM barang b WHERE DATE(b.created_at)=CURDATE()
     UNION ALL
     SELECT 'diambil' as tipe, b.kode_barang, b.jenis_barang,
            p.nama_pengambil, p.kelas, p.tanggal_ambil as waktu
     FROM pengambilan p JOIN barang b ON p.id_barang=b.id
     WHERE DATE(p.tanggal_ambil)=CURDATE()
     ORDER BY waktu DESC LIMIT 10"
);

$page_active = 'dashboard';
$page_title  = 'Dashboard';
require_once 'partials/header.php';

$kategori_list = ['Pakaian','Elektronik','Kendaraan','Alat Tulis','Tas','Lainnya'];
?>

<!-- STAT BAR — klik untuk filter tabel -->
<div class="stat-bar">
    <a href="dashboard.php" class="stat-item <?= (!$filter_status&&!$filter_hari)?'stat-active':'' ?>" style="text-decoration:none;cursor:pointer;">
        <div class="stat-icon" style="background:#E0E7FF;">📊</div>
        <div><div class="stat-num"><?= $stat_belum+$stat_sudah ?></div><div class="stat-label">Semua Barang</div></div>
    </a>
    <a href="dashboard.php?status=belum_diambil" class="stat-item belum <?= $filter_status==='belum_diambil'?'stat-active':'' ?>" style="text-decoration:none;cursor:pointer;">
        <div class="stat-icon">📦</div>
        <div><div class="stat-num"><?= $stat_belum ?></div><div class="stat-label">Belum Diambil</div></div>
    </a>
    <a href="dashboard.php?status=sudah_diambil" class="stat-item sudah <?= $filter_status==='sudah_diambil'?'stat-active':'' ?>" style="text-decoration:none;cursor:pointer;">
        <div class="stat-icon">✅</div>
        <div><div class="stat-num"><?= $stat_sudah ?></div><div class="stat-label">Sudah Diambil</div></div>
    </a>
    <a href="dashboard.php?hari=1" class="stat-item hari <?= $filter_hari?'stat-active':'' ?>" style="text-decoration:none;cursor:pointer;">
        <div class="stat-icon">🕐</div>
        <div><div class="stat-num"><?= $stat_hari ?></div><div class="stat-label">Masuk Hari Ini</div></div>
    </a>
    <div class="stat-item lokasi">
        <div class="stat-icon">📍</div>
        <div><div class="stat-num" style="font-size:13px;line-height:1.2;">Ruang TU</div><div class="stat-label">Lokasi Pengumpulan</div></div>
    </div>
</div>

<div class="alert-banner merah" style="margin:12px 24px 0;">
    <span class="alert-icon">⚠️</span>
    <span>Pastikan verifikasi ciri-ciri barang dilakukan <strong>sebelum</strong> scan barcode. Minta pengambil menunjukkan kartu identitas / name tag.</span>
</div>

<div class="admin-layout">

    <!-- KONTEN UTAMA -->
    <div class="admin-main">
        <div class="card">
            <div class="card-header">
                <span>
                    <?php
                    if ($filter_status==='belum_diambil')      echo '📋 Barang Belum Diambil';
                    elseif ($filter_status==='sudah_diambil')  echo '📋 Barang Sudah Diambil';
                    elseif ($filter_hari)                      echo '📋 Barang Masuk Hari Ini';
                    else                                       echo '📋 Semua Barang (10 Terbaru)';
                    ?>
                </span>
                <a href="tambah.php" class="btn btn-kuning btn-sm">＋ Tambah Barang</a>
            </div>

            <div class="tabel-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:36px;">No</th>
                            <th>Kode</th>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($q_barang) === 0): ?>
                        <tr><td colspan="8" style="text-align:center;padding:32px;color:#9CA3AF;">
                            <div style="font-size:28px;margin-bottom:8px;">📭</div>
                            Tidak ada data
                            <?php if ($filter_status||$filter_hari): ?>
                            — <a href="dashboard.php" style="color:var(--biru-mid);">Reset filter</a>
                            <?php endif; ?>
                        </td></tr>
                    <?php
                    else:
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($q_barang)):
                        // Path foto relatif dari folder admin/
                        $foto_path = '../uploads/' . $row['foto_barang'];
                        $ada_foto  = $row['foto_barang'] && file_exists($foto_path);
                    ?>
                        <tr>
                            <td style="color:#9CA3AF;font-size:12px;"><?= $no++ ?></td>
                            <td><strong style="color:var(--biru);font-family:monospace;font-size:13px;"><?= e($row['kode_barang']) ?></strong></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <?php if ($ada_foto): ?>
                                        <img src="<?= $foto_path ?>"
                                            style="width:38px;height:38px;object-fit:cover;border-radius:6px;border:1px solid var(--border);flex-shrink:0;" alt="">
                                    <?php else: ?>
                                        <div style="width:38px;height:38px;background:var(--abu-muda);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">📦</div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:600;"><?= e($row['jenis_barang']) ?></div>
                                        <?php if ($row['warna']): ?>
                                        <div style="font-size:11px;color:#9CA3AF;"><?= e($row['warna']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($row['kategori']): ?>
                                    <span style="background:var(--biru-muda);color:var(--biru);padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;"><?= e($row['kategori']) ?></span>
                                <?php else: ?>
                                    <span style="color:#D1D5DB;font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px;max-width:110px;"><?= e($row['lokasi_ditemukan']) ?></td>
                            <td style="font-size:12px;white-space:nowrap;"><?= tglIndo($row['tanggal_ditemukan']) ?></td>
                            <td style="white-space:nowrap;">
                                <?php if ($row['status']==='belum_diambil'): ?>
                                    <span class="badge badge-belum">Belum Diambil</span>
                                <?php else: ?>
                                    <span class="badge badge-sudah">Sudah Diambil</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;justify-content:center;flex-wrap:nowrap;">
                                    <!-- BARCODE — link langsung -->
                                    <a href="cetak_barcode.php?id=<?= $row['id'] ?>"
                                        class="btn btn-outline btn-sm" title="Cetak Barcode">🖨</a>
                                    <!-- EDIT — buka modal -->
                                    <button class="btn btn-primary btn-sm" title="Edit"
                                        onclick='bukaEdit(<?= json_encode([
                                            'id'               => $row['id'],
                                            'jenis_barang'     => $row['jenis_barang'],
                                            'kategori'         => $row['kategori'],
                                            'warna'            => $row['warna'],
                                            'lokasi_ditemukan' => $row['lokasi_ditemukan'],
                                            'tanggal_ditemukan'=> $row['tanggal_ditemukan'],
                                            'foto_barang'      => $row['foto_barang'],
                                        ]) ?>)'>✏️</button>
                                    <!-- HAPUS — link dengan filter param -->
                                    <a href="dashboard.php?hapus=<?= $row['id'] ?><?= $filter_status?'&status='.$filter_status:'' ?><?= $filter_hari?'&hari=1':'' ?>"
                                        class="btn btn-danger btn-sm" title="Hapus"
                                        onclick="return confirm('Hapus barang <?= e($row['kode_barang']) ?>?\nTidak bisa dibatalkan.')">🗑</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="padding:10px 18px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#9CA3AF;">
                <span>Menampilkan <?= mysqli_num_rows($q_barang) ?> dari <?= $total_semua ?> data</span>
                <a href="daftar.php" style="color:var(--biru-mid);font-weight:500;">Lihat semua di Daftar Barang →</a>
            </div>
        </div>
    </div>

    <!-- SIDEBAR -->
    <div class="admin-side">

        <!-- Aksi Cepat -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">⚡ Aksi Cepat</div>
            <div class="aksi-list">
                <a href="tambah.php" class="aksi-btn">
                    <div class="aksi-icon kuning">📦</div>
                    <div><div style="font-weight:600;">Tambah Barang Baru</div><div style="font-size:11px;color:#9CA3AF;">Input barang yang ditemukan</div></div>
                </a>
                <a href="scan.php" class="aksi-btn">
                    <div class="aksi-icon biru">📷</div>
                    <div><div style="font-weight:600;">Scan Barcode</div><div style="font-size:11px;color:#9CA3AF;">Scan saat barang diambil</div></div>
                </a>
                <a href="riwayat.php" class="aksi-btn">
                    <div class="aksi-icon hijau">📜</div>
                    <div><div style="font-weight:600;">Riwayat Pengambilan</div><div style="font-size:11px;color:#9CA3AF;">Lihat semua bukti</div></div>
                </a>
            </div>
        </div>

        <!-- Aktivitas Terkini: ditemukan + diambil hari ini -->
        <div class="card">
            <div class="card-header">
                🕐 Aktivitas Hari Ini
                <span style="font-size:11px;color:#9CA3AF;font-weight:400;"><?= date('d M Y') ?></span>
            </div>
            <div style="padding:14px;">
            <?php if (mysqli_num_rows($q_aktivitas) === 0): ?>
                <p style="font-size:12px;color:#9CA3AF;text-align:center;padding:16px 0;">Belum ada aktivitas hari ini</p>
            <?php else:
            while ($akt = mysqli_fetch_assoc($q_aktivitas)): ?>
                <div class="aktivitas-item">
                    <div class="aktivitas-dot <?= $akt['tipe']==='diambil'?'':'orange' ?>"></div>
                    <div>
                        <div style="font-size:11px;font-weight:700;color:<?= $akt['tipe']==='diambil'?'var(--hijau)':'var(--orange)' ?>;">
                            <?= $akt['tipe']==='diambil' ? '✅ DIAMBIL' : '📦 DITEMUKAN' ?>
                        </div>
                        <div style="font-weight:500;font-size:12px;"><?= e($akt['kode_barang']) ?> — <?= e($akt['jenis_barang']) ?></div>
                        <?php if ($akt['tipe']==='diambil'): ?>
                        <div style="color:#6B7280;font-size:12px;">Oleh: <strong><?= e($akt['nama_pengambil']) ?></strong> <?= e($akt['kelas']) ?></div>
                        <?php endif; ?>
                        <div style="color:#9CA3AF;font-size:11px;"><?= date('H:i', strtotime($akt['waktu'])) ?> WIB</div>
                    </div>
                </div>
            <?php endwhile; endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header">
            <span>✏️ Edit Informasi Barang</span>
            <button class="modal-close" onclick="tutupModal('modalEdit')">✕</button>
        </div>
        <form method="POST" action="edit.php" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="redirect" value="dashboard.php<?= $filter_status?'?status='.$filter_status:($filter_hari?'?hari=1':'') ?>">
            <div class="modal-body">
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:200px;">
                        <div class="form-group">
                            <label>Jenis Barang</label>
                            <input type="text" name="jenis_barang" id="editJenis" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" id="editKategori" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategori_list as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Warna / Ciri Khas</label>
                            <input type="text" name="warna" id="editWarna" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Lokasi Ditemukan</label>
                            <input type="text" name="lokasi_ditemukan" id="editLokasi" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Ditemukan</label>
                            <input type="date" name="tanggal_ditemukan" id="editTanggal" class="form-control">
                        </div>
                    </div>
                    <div style="width:150px;flex-shrink:0;">
                        <div class="form-group">
                            <label>Foto Barang</label>
                            <img id="editFotoPreview" src="" alt=""
                                style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;border:1px solid var(--border);margin-bottom:8px;display:none;">
                            <div id="editFotoPlc"
                                style="width:100%;aspect-ratio:1;background:var(--abu-muda);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:36px;margin-bottom:8px;">📦</div>
                            <input type="file" name="foto_baru" id="editFotoFile" accept="image/*"
                                class="form-control" style="font-size:12px;padding:4px;"
                                onchange="previewEditFoto(this)">
                            <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">Kosongkan jika tidak ganti foto</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="tutupModal('modalEdit')">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-side {
    position: sticky;
    top: 120px;
    max-height: calc(100vh - 140px);
    overflow-y: auto;
}
.stat-active { border-color:var(--biru-mid)!important;background:var(--biru-muda)!important;box-shadow:0 0 0 2px rgba(36,81,163,0.15); }
.stat-active .stat-num { color:var(--biru); }
</style>

<script>
function tutupModal(id) { document.getElementById(id).classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target===m) tutupModal(m.id); });
});

function bukaEdit(data) {
    document.getElementById('editId').value      = data.id;
    document.getElementById('editJenis').value   = data.jenis_barang;
    document.getElementById('editWarna').value   = data.warna     || '';
    document.getElementById('editLokasi').value  = data.lokasi_ditemukan;
    document.getElementById('editTanggal').value = data.tanggal_ditemukan;

    // Set kategori
    const sel = document.getElementById('editKategori');
    for (let o of sel.options) o.selected = o.value === (data.kategori || '');

    // Foto preview
    const prev = document.getElementById('editFotoPreview');
    const plc  = document.getElementById('editFotoPlc');
    if (data.foto_barang) {
        prev.src = '../uploads/' + data.foto_barang;
        prev.style.display = 'block';
        plc.style.display  = 'none';
    } else {
        prev.style.display = 'none';
        plc.style.display  = 'flex';
    }
    document.getElementById('editFotoFile').value = '';
    document.getElementById('modalEdit').classList.add('active');
}

function previewEditFoto(input) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            const p = document.getElementById('editFotoPreview');
            p.src = e.target.result;
            p.style.display = 'block';
            document.getElementById('editFotoPlc').style.display = 'none';
        };
        r.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>