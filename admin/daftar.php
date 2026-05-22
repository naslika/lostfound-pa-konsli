<?php
// ============================================================
//  DAFTAR BARANG — FIXED
//  File: admin/daftar.php
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

// ================================================================
// HAPUS — DI ATAS SEBELUM OUTPUT
// ================================================================
if (isset($_GET['hapus'])) {
    $hid = (int) $_GET['hapus'];
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto_barang FROM barang WHERE id=$hid"));
    if ($r && $r['foto_barang'] && file_exists('../uploads/' . $r['foto_barang'])) {
        unlink('../uploads/' . $r['foto_barang']);
    }
    mysqli_query($conn, "DELETE FROM barang WHERE id=$hid");
    header("Location: daftar.php?msg=hapus");
    exit();
}

// ---- FILTER ----
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');
$hari = isset($_GET['hari']) && $_GET['hari'] === '1';
$page = 1;
$offset = 0;

$where = ["1=1"];
if ($search)
    $where[] = "(b.kode_barang LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
    OR b.jenis_barang LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
    OR b.warna LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
    OR b.lokasi_ditemukan LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
if ($status)
    $where[] = "b.status='" . mysqli_real_escape_string($conn, $status) . "'";
if ($kategori)
    $where[] = "b.kategori='" . mysqli_real_escape_string($conn, $kategori) . "'";
if ($hari)
    $where[] = "DATE(b.created_at)=CURDATE()";
$order = $sort === 'terlama' ? 'b.created_at ASC' : 'b.created_at DESC';
$sql_where = implode(' AND ', $where);

// Statistik
$stat_belum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='belum_diambil'"))['n'];
$stat_sudah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE status='sudah_diambil'"))['n'];
$stat_hari = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang WHERE DATE(created_at)=CURDATE()"))['n'];

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM barang b WHERE $sql_where"))['n'];

$q = mysqli_query(
    $conn,
    "SELECT b.*, p.nama_pengambil, p.kelas
     FROM barang b LEFT JOIN pengambilan p ON b.id=p.id_barang
     WHERE $sql_where ORDER BY $order"
);

// Aktivitas terkini hari ini
$q_aktivitas = mysqli_query(
    $conn,
    "SELECT 'ditemukan' as tipe, b.kode_barang, b.jenis_barang,
            NULL as nama_pengambil, NULL as kelas, b.created_at as waktu
     FROM barang b WHERE DATE(b.created_at)=CURDATE()
     UNION ALL
     SELECT 'diambil' as tipe, b.kode_barang, b.jenis_barang,
            p.nama_pengambil, p.kelas, p.tanggal_ambil as waktu
     FROM pengambilan p JOIN barang b ON p.id_barang=b.id
     WHERE DATE(p.tanggal_ambil)=CURDATE()
     ORDER BY waktu DESC LIMIT 8"
);

$kategori_list = ['Pakaian', 'Elektronik', 'Kendaraan', 'Alat Tulis', 'Tas', 'Lainnya'];

$page_active = 'daftar';
$page_title = 'Daftar Barang';
require_once 'partials/header.php';
?>

<!-- STAT BAR -->
<div class="stat-bar">
    <a href="daftar.php" class="stat-item <?= (!$status && !$hari) ? 'stat-active' : '' ?>"
        style="text-decoration:none;cursor:pointer;">
        <div class="stat-icon" style="background:#E0E7FF;">📊</div>
        <div>
            <div class="stat-num"><?= $stat_belum + $stat_sudah ?></div>
            <div class="stat-label">Semua Barang</div>
        </div>
    </a>
    <a href="daftar.php?status=belum_diambil" class="stat-item belum <?= $status === 'belum_diambil' ? 'stat-active' : '' ?>"
        style="text-decoration:none;cursor:pointer;">
        <div class="stat-icon">📦</div>
        <div>
            <div class="stat-num"><?= $stat_belum ?></div>
            <div class="stat-label">Belum Diambil</div>
        </div>
    </a>
    <a href="daftar.php?status=sudah_diambil" class="stat-item sudah <?= $status === 'sudah_diambil' ? 'stat-active' : '' ?>"
        style="text-decoration:none;cursor:pointer;">
        <div class="stat-icon">✅</div>
        <div>
            <div class="stat-num"><?= $stat_sudah ?></div>
            <div class="stat-label">Sudah Diambil</div>
        </div>
    </a>
    <a href="daftar.php?hari=1" class="stat-item hari <?= $hari ? 'stat-active' : '' ?>"
        style="text-decoration:none;cursor:pointer;">
        <div class="stat-icon">🕐</div>
        <div>
            <div class="stat-num"><?= $stat_hari ?></div>
            <div class="stat-label">Masuk Hari Ini</div>
        </div>
    </a>
    <div class="stat-item lokasi">
        <div class="stat-icon">📍</div>
        <div>
            <div class="stat-num" style="font-size:13px;line-height:1.2;">Ruang TU</div>
            <div class="stat-label">Lokasi Pengumpulan</div>
        </div>
    </div>
</div>

<div class="alert-banner merah" style="margin:12px 24px 0;">
    <span class="alert-icon">⚠️</span>
    <span>Pastikan verifikasi ciri-ciri barang sebelum scan barcode. Minta pengambil tunjukkan kartu identitas / name
        tag.</span>
</div>

<div class="admin-layout">
    <div class="admin-main">
        <div class="card">
            <div class="card-header">
                <span>📋 Daftar Barang Temuan</span>
                <a href="tambah.php" class="btn btn-kuning btn-sm">＋ Tambah Barang</a>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div style="padding:10px 18px;">
                    <div class="alert-banner" style="background:#D1FAE5;border:1px solid #6EE7B7;color:#065F46;margin:0;">
                        <span>✅</span> <?= $_GET['msg'] === 'hapus' ? 'Barang berhasil dihapus.' : 'Data berhasil diperbarui.' ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                <form method="GET">
                    <?php if ($hari): ?><input type="hidden" name="hari" value="1"><?php endif; ?>
                    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
                    <div class="search-filter-bar">
                        <div class="search-wrap" style="flex:2;">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search" placeholder="Cari kode, jenis, warna, lokasi..."
                                value="<?= e($search) ?>">
                        </div>
                        <select name="kategori" class="form-control" style="width:auto;min-width:140px;"
                            onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategori_list as $k): ?>
                                <option value="<?= $k ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="sort" class="form-control" style="width:auto;min-width:130px;"
                            onchange="this.form.submit()">
                            <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>⬇ Terbaru</option>
                            <option value="terlama" <?= $sort === 'terlama' ? 'selected' : '' ?>>⬆ Terlama</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        <?php if ($search || $kategori || $sort !== 'terbaru'): ?>
                            <a href="daftar.php<?= $status ? '?status=' . $status : ($hari ? '?hari=1' : '') ?>"
                                class="btn btn-outline btn-sm">✕ Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabel: No | Kode | Barang | Kategori | Lokasi | Tanggal | Status | Aksi -->
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
                        <?php if (mysqli_num_rows($q) === 0): ?>
                            <tr>
                                <td colspan="8" style="text-align:center;padding:40px;color:#9CA3AF;">
                                    <div style="font-size:28px;margin-bottom:8px;">📭</div>
                                    Tidak ada data
                                    <?php if ($search || $status || $kategori): ?>
                                        — <a href="daftar.php" style="color:var(--biru-mid);">Reset filter</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        else:
                            $no = $offset + 1;
                            while ($row = mysqli_fetch_assoc($q)):
                                $foto_path = '../uploads/' . $row['foto_barang'];
                                $ada_foto = $row['foto_barang'] && file_exists($foto_path);
                                ?>
                                <tr>
                                    <td style="color:#9CA3AF;font-size:12px;"><?= $no++ ?></td>
                                    <td><strong
                                            style="color:var(--biru);font-family:monospace;"><?= e($row['kode_barang']) ?></strong>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <?php if ($ada_foto): ?>
                                                <img src="<?= $foto_path ?>"
                                                    style="width:38px;height:38px;object-fit:cover;border-radius:6px;border:1px solid var(--border);flex-shrink:0;"
                                                    alt="">
                                            <?php else: ?>
                                                <div
                                                    style="width:38px;height:38px;background:var(--abu-muda);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                                                    📦</div>
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
                                            <span
                                                style="background:var(--biru-muda);color:var(--biru);padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;"><?= e($row['kategori']) ?></span>
                                        <?php else: ?>
                                            <span style="color:#D1D5DB;font-size:12px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:12px;max-width:120px;"><?= e($row['lokasi_ditemukan']) ?></td>
                                    <td style="font-size:12px;white-space:nowrap;"><?= tglIndo($row['tanggal_ditemukan']) ?>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <?php if ($row['status'] === 'belum_diambil'): ?>
                                            <span class="badge badge-belum">Belum Diambil</span>
                                        <?php else: ?>
                                            <span class="badge badge-sudah">Sudah Diambil</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:4px;justify-content:center;">
                                            <button class="btn btn-outline btn-sm" title="Detail" onclick='lihatDetail(<?= json_encode([
                                                'id' => $row['id'],
                                                'kode_barang' => $row['kode_barang'],
                                                'jenis_barang' => $row['jenis_barang'],
                                                'kategori' => $row['kategori'],
                                                'warna' => $row['warna'],
                                                'lokasi_ditemukan' => $row['lokasi_ditemukan'],
                                                'tanggal_ditemukan' => $row['tanggal_ditemukan'],
                                                'status' => $row['status'],
                                                'foto_barang' => $row['foto_barang'],
                                                'nama_pengambil' => $row['nama_pengambil'],
                                                'kelas' => $row['kelas'],
                                            ]) ?>)'>👁</button>
                                            <a href="cetak_barcode.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm"
                                                title="Cetak">🖨</a>
                                            <button class="btn btn-primary btn-sm" title="Edit" onclick='bukaEdit(<?= json_encode([
                                                'id' => $row['id'],
                                                'jenis_barang' => $row['jenis_barang'],
                                                'kategori' => $row['kategori'],
                                                'warna' => $row['warna'],
                                                'lokasi_ditemukan' => $row['lokasi_ditemukan'],
                                                'tanggal_ditemukan' => $row['tanggal_ditemukan'],
                                                'foto_barang' => $row['foto_barang'],
                                            ]) ?>)'>✏️</button>
                                            <a href="daftar.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                                title="Hapus"
                                                onclick="return confirm('Hapus barang <?= e($row['kode_barang']) ?>?')">🗑</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="padding:12px 18px;border-top:1px solid var(--border);">
                <div style="font-size:12px;color:#9CA3AF;">Menampilkan <strong><?= $total ?></strong> data</div>
            </div>
        </div>
    </div>

    <!-- SIDEBAR: Aksi Cepat + Aktivitas Terkini -->
    <div class="admin-side">
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
                <a href="scan.php" class="aksi-btn">
                    <div class="aksi-icon biru">📷</div>
                    <div>
                        <div style="font-weight:600;">Scan Barcode</div>
                        <div style="font-size:11px;color:#9CA3AF;">Scan saat barang diambil</div>
                    </div>
                </a>
                <a href="riwayat.php" class="aksi-btn">
                    <div class="aksi-icon hijau">📜</div>
                    <div>
                        <div style="font-weight:600;">Riwayat Pengambilan</div>
                        <div style="font-size:11px;color:#9CA3AF;">Lihat semua bukti</div></div>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                🕐 Aktivitas Hari Ini
                <span style="font-size:11px;color:#9CA3AF;font-weight:400;"><?= date('d M Y') ?></span>
            </div>
            <div style="padding:14px;">
                <?php if (mysqli_num_rows($q_aktivitas) === 0): ?>
                    <p style="font-size:12px;color:#9CA3AF;text-align:center;padding:16px 0;">Belum ada aktivitas hari ini
                    </p>
                <?php else:
                    while ($akt = mysqli_fetch_assoc($q_aktivitas)): ?>
                        <div class="aktivitas-item">
                            <div class="aktivitas-dot <?= $akt['tipe'] === 'diambil' ? '' : 'orange' ?>"></div>
                            <div>
                                <div
                                    style="font-size:11px;font-weight:700;color:<?= $akt['tipe'] === 'diambil' ? 'var(--hijau)' : 'var(--orange)' ?>;">
                                    <?= $akt['tipe'] === 'diambil' ? '✅ DIAMBIL' : '📦 DITEMUKAN' ?>
                                </div>
                                <div style="font-weight:500;font-size:12px;"><?= e($akt['kode_barang']) ?> —
                                    <?= e($akt['jenis_barang']) ?></div>
                                <?php if ($akt['tipe'] === 'diambil'): ?>
                                    <div style="color:#6B7280;font-size:12px;">Oleh:
                                        <strong><?= e($akt['nama_pengambil']) ?></strong> <?= e($akt['kelas']) ?></div>
                                <?php endif; ?>
                                <div style="color:#9CA3AF;font-size:11px;"><?= date('H:i', strtotime($akt['waktu'])) ?> WIB</div>
                            </div>
                        </div>
                    <?php endwhile; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal-overlay" id="modalDetail">
    <div class="modal-box">
        <div class="modal-header"><span>📦 Informasi Barang</span><button class="modal-close"
                onclick="tutupModal('modalDetail')">✕</button></div>
        <div class="modal-body">
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                <img id="dFoto" src=""
                    style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:1px solid var(--border);display:none;"
                    alt="">
                <div id="dFotoPlc"
                    style="width:100px;height:100px;background:var(--abu-muda);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:36px;">
                    📦</div>
                <div style="flex:1;min-width:180px;">
                    <table style="width:100%;font-size:13px;border-collapse:collapse;">
                        <tr>
                            <td style="color:#6B7280;padding:5px 0;width:130px;">Kode</td>
                            <td style="font-weight:700;color:var(--biru);font-family:monospace;" id="dKode"></td>
                        </tr>
                        <tr>
                            <td style="color:#6B7280;padding:5px 0;">Jenis Barang</td>
                            <td style="font-weight:600;" id="dJenis"></td>
                        </tr>
                        <tr>
                            <td style="color:#6B7280;padding:5px 0;">Kategori</td>
                            <td id="dKategori"></td>
                        </tr>
                        <tr>
                            <td style="color:#6B7280;padding:5px 0;">Warna / Ciri</td>
                            <td id="dWarna"></td>
                        </tr>
                        <tr>
                            <td style="color:#6B7280;padding:5px 0;">Lokasi</td>
                            <td id="dLokasi"></td>
                        </tr>
                        <tr>
                            <td style="color:#6B7280;padding:5px 0;">Tanggal Ditemukan</td>
                            <td id="dTanggal"></td>
                        </tr>
                        <tr>
                            <td style="color:#6B7280;padding:5px 0;">Status</td>
                            <td id="dStatus"></td>
                        </tr>
                        <tr id="rowPengambil" style="display:none;">
                            <td style="color:#6B7280;padding:5px 0;">Diambil Oleh</td>
                            <td id="dPengambil"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a id="dLinkCetak" href="#" class="btn btn-outline btn-sm">🖨 Cetak Barcode</a>
            <button class="btn btn-outline" onclick="tutupModal('modalDetail')">Tutup</button>
        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header"><span>✏️ Edit Informasi Barang</span><button class="modal-close"
                onclick="tutupModal('modalEdit')">✕</button></div>
        <form method="POST" action="edit.php" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="redirect" value="daftar.php">
            <div class="modal-body">
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:200px;">
                        <div class="form-group"><label>Jenis Barang</label><input type="text" name="jenis_barang"
                                id="editJenis" class="form-control" required></div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" id="editKategori" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategori_list as $k): ?>
                                    <option value="<?= $k ?>"><?= $k ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Warna / Ciri Khas</label><input type="text" name="warna"
                                id="editWarna" class="form-control"></div>
                        <div class="form-group"><label>Lokasi Ditemukan</label><input type="text"
                                name="lokasi_ditemukan" id="editLokasi" class="form-control" required></div>
                        <div class="form-group"><label>Tanggal Ditemukan</label><input type="date"
                                name="tanggal_ditemukan" id="editTanggal" class="form-control"></div>
                    </div>
                    <div style="width:150px;flex-shrink:0;">
                        <div class="form-group">
                            <label>Foto Barang</label>
                            <img id="editFotoPreview" src=""
                                style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;border:1px solid var(--border);margin-bottom:8px;display:none;"
                                alt="">
                            <div id="editFotoPlc"
                                style="width:100%;aspect-ratio:1;background:var(--abu-muda);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:36px;margin-bottom:8px;">
                                📦</div>
                            <input type="file" name="foto_baru" id="editFotoFile" accept="image/*" class="form-control"
                                style="font-size:12px;padding:4px;" onchange="previewEditFoto(this)">
                            <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">Kosongkan jika tidak ganti foto
                            </div>
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
    /* Sidebar fixed saat scroll */
    .admin-side {
        position: sticky;
        top: 120px;
        /* sesuaikan dengan tinggi header+navbar */
        max-height: calc(100vh - 140px);
        overflow-y: auto;
    }

    .stat-active {
        border-color: var(--biru-mid) !important;
        background: var(--biru-muda) !important;
        box-shadow: 0 0 0 2px rgba(36, 81, 163, 0.15);
    }

    .stat-active .stat-num {
        color: var(--biru);
    }
</style>

<script>
    function tutupModal(id) { document.getElementById(id).classList.remove('active'); }
    document.querySelectorAll('.modal-overlay').forEach(m => { m.addEventListener('click', e => { if (e.target === m) tutupModal(m.id); }); });

    function lihatDetail(d) {
        document.getElementById('dKode').textContent = d.kode_barang;
        document.getElementById('dJenis').textContent = d.jenis_barang;
        document.getElementById('dWarna').textContent = d.warna || '—';
        document.getElementById('dLokasi').textContent = d.lokasi_ditemukan;
        document.getElementById('dTanggal').textContent = d.tanggal_ditemukan;
        document.getElementById('dLinkCetak').href = 'cetak_barcode.php?id=' + d.id;

        // Kategori badge
        const katEl = document.getElementById('dKategori');
        katEl.innerHTML = d.kategori
            ? `<span style="background:var(--biru-muda);color:var(--biru);padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;">${d.kategori}</span>`
            : '<span style="color:#D1D5DB;">—</span>';

        // Status
        document.getElementById('dStatus').innerHTML = d.status === 'belum_diambil'
            ? '<span class="badge badge-belum">⏳ Belum Diambil</span>'
            : '<span class="badge badge-sudah">✅ Sudah Diambil</span>';

        const rp = document.getElementById('rowPengambil');
        if (d.status === 'sudah_diambil' && d.nama_pengambil) {
            rp.style.display = '';
            document.getElementById('dPengambil').textContent = (d.nama_pengambil || '') + (d.kelas ? ' ' + d.kelas : '');
        } else { rp.style.display = 'none'; }

        // Foto
        const foto = document.getElementById('dFoto'), plc = document.getElementById('dFotoPlc');
        if (d.foto_barang) { foto.src = '../uploads/' + d.foto_barang; foto.style.display = 'block'; plc.style.display = 'none'; }
        else { foto.style.display = 'none'; plc.style.display = 'flex'; }
        document.getElementById('modalDetail').classList.add('active');
    }

    function bukaEdit(d) {
        document.getElementById('editId').value = d.id;
        document.getElementById('editJenis').value = d.jenis_barang;
        document.getElementById('editWarna').value = d.warna || '';
        document.getElementById('editLokasi').value = d.lokasi_ditemukan;
        document.getElementById('editTanggal').value = d.tanggal_ditemukan;
        const sel = document.getElementById('editKategori');
        for (let o of sel.options) o.selected = o.value === (d.kategori || '');
        const prev = document.getElementById('editFotoPreview'), plc = document.getElementById('editFotoPlc');
        if (d.foto_barang) { prev.src = '../uploads/' + d.foto_barang; prev.style.display = 'block'; plc.style.display = 'none'; }
        else { prev.style.display = 'none'; plc.style.display = 'flex'; }
        document.getElementById('editFotoFile').value = '';
        document.getElementById('modalEdit').classList.add('active');
    }
    function previewEditFoto(input) {
        if (input.files && input.files[0]) {
            const r = new FileReader();
            r.onload = e => { const p = document.getElementById('editFotoPreview'); p.src = e.target.result; p.style.display = 'block'; document.getElementById('editFotoPlc').style.display = 'none'; };
            r.readAsDataURL(input.files[0]);
        }
    }
</script>
<?php require_once 'partials/footer.php'; ?>