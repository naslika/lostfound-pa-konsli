<?php
// ============================================================
//  TAMBAH BARANG BARU + WEBCAM
//  File: admin/tambah.php
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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_barang = trim($_POST['jenis_barang'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $warna = trim($_POST['warna'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $lokasi_ditemukan = trim($_POST['lokasi_ditemukan'] ?? '');
    $tanggal = trim($_POST['tanggal_ditemukan'] ?? date('Y-m-d'));
    $foto_data = $_POST['foto_base64'] ?? ''; // dari webcam (base64)

    if (empty($jenis_barang) || empty($lokasi_ditemukan)) {
        $error = 'Jenis barang dan lokasi wajib diisi.';
    } else {
        // Generate kode barang
        $kode = generateKodeBarang($conn);

        // Simpan foto dari webcam (base64 → file)
        $nama_foto = '';
        if (!empty($foto_data) && strpos($foto_data, 'data:image') === 0) {
            $foto_raw = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $foto_data));
            $nama_foto = $kode . '_' . time() . '.jpg';
            $path_foto = '../uploads/' . $nama_foto;
            file_put_contents($path_foto, $foto_raw);
        }
        // Atau upload file biasa
        elseif (isset($_FILES['foto_file']) && $_FILES['foto_file']['error'] === 0) {
            $ext = pathinfo($_FILES['foto_file']['name'], PATHINFO_EXTENSION);
            $nama_foto = $kode . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['foto_file']['tmp_name'], '../uploads/' . $nama_foto);
        }

        // Simpan ke database
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO barang (kode_barang, jenis_barang, kategori, warna, deskripsi, lokasi_ditemukan, foto_barang, tanggal_ditemukan)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssss',
            $kode,
            $jenis_barang,
            $kategori,
            $warna,
            $deskripsi,
            $lokasi_ditemukan,
            $nama_foto,
            $tanggal
        );

        if (mysqli_stmt_execute($stmt)) {
            $id_baru = mysqli_insert_id($conn);
            $success = $kode;
            // Redirect ke halaman cetak barcode
            header("Location: cetak_barcode.php?id=$id_baru&baru=1");
            exit();
        } else {
            $error = 'Gagal menyimpan data. Coba lagi.';
        }
    }
}

$page_active = 'daftar';
$page_title = 'Tambah Barang';
require_once 'partials/header.php';
?>

<!-- STAT BAR singkat -->
<div class="stat-bar">
    <a href="dashboard.php" style="font-size:13px;color:var(--biru);text-decoration:none;">← Kembali ke Dashboard</a>
    <span style="color:#9CA3AF;font-size:13px;">/ Tambah Barang Baru</span>
</div>

<div class="content-wrap" style="max-width:960px;">

    <?php if ($error): ?>
        <div class="alert-banner merah" style="margin-bottom:16px;">
            <span class="alert-icon">⚠️</span> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="formTambah">
        <input type="hidden" name="foto_base64" id="foto_base64">

        <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">

            <!-- KOLOM KIRI: Form data barang -->
            <div style="flex:1;min-width:300px;">
                <div class="card">
                    <div class="card-header">📦 Data Barang</div>
                    <div style="padding:20px;">

                        <div class="form-group">
                            <label>Jenis Barang <span style="color:red;">*</span></label>
                            <input type="text" name="jenis_barang" class="form-control"
                                placeholder="Contoh: Jaket, Botol minum, Helm, Charger"
                                value="<?= e($_POST['jenis_barang'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Pakaian" <?= ($_POST['kategori'] ?? '') === 'Pakaian' ? 'selected' : '' ?>>Pakaian
                                </option>
                                <option value="Elektronik" <?= ($_POST['kategori'] ?? '') === 'Elektronik' ? 'selected' : '' ?>>
                                    Elektronik</option>
                                <option value="Kendaraan" <?= ($_POST['kategori'] ?? '') === 'Kendaraan' ? 'selected' : '' ?>>
                                    Kendaraan</option>
                                <option value="Alat Tulis" <?= ($_POST['kategori'] ?? '') === 'Alat Tulis' ? 'selected' : '' ?>>
                                    Alat Tulis</option>
                                <option value="Tas" <?= ($_POST['kategori'] ?? '') === 'Tas' ? 'selected' : '' ?>>Tas</option>
                                <option value="Lainnya" <?= ($_POST['kategori'] ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Warna / Ciri Khas</label>
                            <input type="text" name="warna" class="form-control"
                                placeholder="Contoh: Biru dongker, ada nama di kerah"
                                value="<?= e($_POST['warna'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Deskripsi Tambahan</label>
                            <textarea name="deskripsi" class="form-control" rows="3"
                                placeholder="Ciri-ciri khusus yang membantu identifikasi..."
                                style="resize:vertical;"><?= e($_POST['deskripsi'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Lokasi Ditemukan <span style="color:red;">*</span></label>
                            <input type="text" name="lokasi_ditemukan" class="form-control"
                                placeholder="Contoh: Kantin lantai 1, Lapangan olahraga"
                                value="<?= e($_POST['lokasi_ditemukan'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Ditemukan</label>
                            <input type="date" name="tanggal_ditemukan" class="form-control"
                                value="<?= e($_POST['tanggal_ditemukan'] ?? date('Y-m-d')) ?>">
                        </div>

                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: Webcam + foto -->
            <div style="width:340px;flex-shrink:0;">
                <div class="card">
                    <div class="card-header">📷 Foto Barang</div>
                    <div style="padding:16px;">

                        <!-- Tab pilih metode foto -->
                        <div style="display:flex;gap:6px;margin-bottom:14px;">
                            <button type="button" class="btn btn-primary btn-sm" id="btnTabWebcam"
                                onclick="switchTab('webcam')">📷 Webcam</button>
                            <button type="button" class="btn btn-outline btn-sm" id="btnTabUpload"
                                onclick="switchTab('upload')">📁 Upload File</button>
                        </div>

                        <!-- TAB WEBCAM -->
                        <div id="tabWebcam">
                            <!-- Preview webcam / foto -->
                            <div
                                style="position:relative;background:#000;border-radius:8px;overflow:hidden;aspect-ratio:4/3;margin-bottom:10px;">
                                <video id="webcamVideo" autoplay playsinline
                                    style="width:100%;height:100%;object-fit:cover;display:block;"></video>
                                <canvas id="webcamCanvas"
                                    style="width:100%;height:100%;object-fit:cover;display:none;position:absolute;top:0;left:0;"></canvas>
                                <!-- Overlay saat belum aktif -->
                                <div id="webcamPlaceholder"
                                    style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#9CA3AF;gap:8px;">
                                    <div style="font-size:40px;">📷</div>
                                    <div style="font-size:13px;">Klik "Aktifkan Kamera"</div>
                                </div>
                            </div>

                            <!-- Tombol kontrol webcam -->
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button type="button" class="btn btn-primary btn-sm" id="btnAktifkan"
                                    onclick="aktifkanWebcam()">
                                    📷 Aktifkan Kamera
                                </button>
                                <button type="button" class="btn btn-kuning btn-sm" id="btnAmbilFoto"
                                    onclick="ambilFoto()" style="display:none;">
                                    📸 Ambil Foto
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" id="btnRetake"
                                    onclick="retakeWebcam()" style="display:none;">
                                    🔄 Ulangi
                                </button>
                            </div>

                            <div id="statusWebcam" style="margin-top:8px;font-size:12px;color:#9CA3AF;"></div>
                        </div>

                        <!-- TAB UPLOAD -->
                        <div id="tabUpload" style="display:none;">
                            <div id="dropZone"
                                style="border:2px dashed var(--border);border-radius:8px;padding:32px 16px;text-align:center;cursor:pointer;transition:border-color 0.15s;"
                                onclick="document.getElementById('fotoFile').click()"
                                ondragover="event.preventDefault();this.style.borderColor='var(--biru-mid)'"
                                ondragleave="this.style.borderColor='var(--border)'" ondrop="handleDrop(event)">
                                <div style="font-size:32px;margin-bottom:8px;">🖼️</div>
                                <div style="font-size:13px;color:#6B7280;">Klik atau drag foto ke sini</div>
                                <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">JPG, PNG, WEBP maks. 5MB</div>
                            </div>
                            <input type="file" name="foto_file" id="fotoFile" accept="image/*" style="display:none;"
                                onchange="previewUpload(this)">
                            <img id="previewUploadImg" src="" alt=""
                                style="display:none;width:100%;border-radius:8px;margin-top:10px;border:1px solid var(--border);">
                            <button type="button" class="btn btn-outline btn-sm" id="btnGantiUpload"
                                onclick="gantiUpload()" style="display:none;margin-top:8px;">🔄 Ganti Foto</button>
                        </div>

                        <!-- Preview foto final (setelah ambil/upload) -->
                        <div id="fotoStatus"
                            style="display:none;margin-top:10px;padding:8px 12px;background:#D1FAE5;border:1px solid #6EE7B7;border-radius:6px;font-size:12px;color:#065F46;">
                            ✅ Foto berhasil diambil
                        </div>

                    </div>
                </div>

                <!-- Tombol simpan -->
                <div style="margin-top:16px;display:flex;gap:8px;">
                    <button type="submit" class="btn btn-kuning" style="flex:1;justify-content:center;padding:12px;"
                        id="btnSimpan">
                        💾 Simpan & Cetak Barcode
                    </button>
                </div>
                <div style="margin-top:8px;">
                    <a href="daftar.php" class="btn btn-outline" style="width:100%;justify-content:center;">Batal</a>
                </div>

                <!-- Info -->
                <div
                    style="margin-top:14px;padding:12px;background:var(--biru-muda);border-radius:8px;font-size:12px;color:#1E40AF;line-height:1.6;">
                    ℹ️ Setelah disimpan, sistem akan otomatis membuat kode barang (LF-xxx) dan menampilkan barcode untuk
                    dicetak.
                </div>
            </div>

        </div>
    </form>
</div>

<script>
    let streamAktif = null;
    let fotoSudahDiambil = false;
    let tabAktif = 'webcam';

    // ---- TAB SWITCH ----
    function switchTab(tab) {
        tabAktif = tab;
        document.getElementById('tabWebcam').style.display = tab === 'webcam' ? 'block' : 'none';
        document.getElementById('tabUpload').style.display = tab === 'upload' ? 'block' : 'none';
        document.getElementById('btnTabWebcam').className = tab === 'webcam'
            ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm';
        document.getElementById('btnTabUpload').className = tab === 'upload'
            ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm';

        // Stop webcam kalau pindah ke upload
        if (tab === 'upload' && streamAktif) {
            streamAktif.getTracks().forEach(t => t.stop());
            streamAktif = null;
            document.getElementById('webcamVideo').style.display = 'none';
            document.getElementById('btnAmbilFoto').style.display = 'none';
            document.getElementById('btnAktifkan').style.display = 'block';
        }
    }

    // ---- WEBCAM ----
    async function aktifkanWebcam() {
        const status = document.getElementById('statusWebcam');
        try {
            status.textContent = 'Meminta izin kamera...';
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 640, height: 480, facingMode: 'environment' }
            });
            streamAktif = stream;
            const video = document.getElementById('webcamVideo');
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('webcamPlaceholder').style.display = 'none';
            document.getElementById('webcamCanvas').style.display = 'none';
            document.getElementById('btnAktifkan').style.display = 'none';
            document.getElementById('btnAmbilFoto').style.display = 'inline-flex';
            document.getElementById('btnRetake').style.display = 'none';
            status.textContent = '🟢 Kamera aktif — arahkan ke barang lalu ambil foto';
            fotoSudahDiambil = false;
            document.getElementById('foto_base64').value = '';
            document.getElementById('fotoStatus').style.display = 'none';
        } catch (err) {
            status.textContent = '❌ Kamera tidak bisa diakses: ' + err.message;
            status.style.color = '#DC2626';
        }
    }

    function ambilFoto() {
        const video = document.getElementById('webcamVideo');
        const canvas = document.getElementById('webcamCanvas');
        const ctx = canvas.getContext('2d');

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        document.getElementById('foto_base64').value = dataUrl;

        // Tampilkan canvas, sembunyikan video
        canvas.style.display = 'block';
        video.style.display = 'none';
        document.getElementById('btnAmbilFoto').style.display = 'none';
        document.getElementById('btnRetake').style.display = 'inline-flex';
        document.getElementById('fotoStatus').style.display = 'block';
        document.getElementById('statusWebcam').textContent = '✅ Foto berhasil diambil';

        // Hentikan stream
        if (streamAktif) {
            streamAktif.getTracks().forEach(t => t.stop());
            streamAktif = null;
        }
        fotoSudahDiambil = true;
    }

    function retakeWebcam() {
        const canvas = document.getElementById('webcamCanvas');
        const video = document.getElementById('webcamVideo');
        canvas.style.display = 'none';
        video.style.display = 'block';
        document.getElementById('btnRetake').style.display = 'none';
        document.getElementById('btnAktifkan').style.display = 'inline-flex';
        document.getElementById('fotoStatus').style.display = 'none';
        document.getElementById('statusWebcam').textContent = '';
        document.getElementById('foto_base64').value = '';
        fotoSudahDiambil = false;
    }

    // ---- UPLOAD FILE ----
    function previewUpload(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.getElementById('previewUploadImg');
                img.src = e.target.result;
                img.style.display = 'block';
                document.getElementById('dropZone').style.display = 'none';
                document.getElementById('btnGantiUpload').style.display = 'inline-flex';
                document.getElementById('fotoStatus').style.display = 'block';
                // Kosongkan base64 supaya PHP pakai file upload
                document.getElementById('foto_base64').value = '';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function handleDrop(e) {
        e.preventDefault();
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const input = document.getElementById('fotoFile');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            previewUpload(input);
        }
        document.getElementById('dropZone').style.borderColor = 'var(--border)';
    }

    function gantiUpload() {
        document.getElementById('previewUploadImg').style.display = 'none';
        document.getElementById('dropZone').style.display = 'block';
        document.getElementById('btnGantiUpload').style.display = 'none';
        document.getElementById('fotoStatus').style.display = 'none';
        document.getElementById('fotoFile').value = '';
    }

    // ---- SUBMIT ----
    document.getElementById('formTambah').addEventListener('submit', function (e) {
        const btn = document.getElementById('btnSimpan');
        btn.disabled = true;
        btn.textContent = '⏳ Menyimpan...';
    });
</script>

<?php require_once 'partials/footer.php'; ?>