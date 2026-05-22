<?php
session_start();

// Include koneksi database
require_once '../config/db.php';

// Pastikan koneksi tersedia
if (!isset($conn)) {
    die('Koneksi database tidak ditemukan. Cek config/db.php');
}

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE username = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_login'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nama'] = $admin['nama'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Username atau password salah. Coba lagi.';
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = 'Terjadi kesalahan pada sistem.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Sistem Lost & Found SMKN 1 Surabaya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            --kuning: #F5C518;
            --border: #E5E7EB;
            --abu-muda: #F3F4F6;
            --merah: #DC2626;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--biru);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Dekorasi background */
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(245, 197, 24, 0.06);
            top: -200px;
            right: -100px;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
            bottom: -100px;
            left: -80px;
            pointer-events: none;
        }

        /* Strip kuning atas */
        .top-strip {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--kuning);
        }

        /* Kartu login */
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header kartu */
        .card-top {
            background: var(--biru);
            padding: 32px 32px 28px;
            text-align: center;
            border-bottom: 4px solid var(--kuning);
            position: relative;
        }

        .card-top::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--kuning);
        }

        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 8px;
        }

        /* Kontainer putih untuk meletakkan logo asli SMKN 1 agar kontras dan terbaca jelas */
        .logo-box {
            background: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .logo-box img {
            height: 52px;
            object-fit: contain;
            /* Tanpa filter agar warna asli logo SMKN 1 Surabaya tetap terjaga */
        }

        .sistem-title {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            margin-top: 10px;
            line-height: 1.5;
        }

        .sistem-title strong {
            color: var(--kuning);
            font-weight: 600;
        }

        /* Badge ADMIN */
        .badge-admin {
            display: inline-block;
            background: var(--kuning);
            color: var(--biru);
            font-size: 11px;
            font-weight: 800;
            padding: 3px 12px;
            border-radius: 20px;
            letter-spacing: 1px;
            margin-top: 12px;
        }

        /* Form area */
        .card-body {
            padding: 28px 32px 32px;
        }

        .card-body h2 {
            font-size: 17px;
            font-weight: 700;
            color: var(--biru);
            margin-bottom: 6px;
        }

        .card-body p {
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #111827;
            transition: border-color 0.15s, box-shadow 0.15s;
            background: #FAFAFA;
        }

        .input-wrap input:focus {
            outline: none;
            border-color: var(--biru-mid);
            box-shadow: 0 0 0 3px rgba(36, 81, 163, 0.12);
            background: #fff;
        }

        .input-wrap .ico {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 16px;
        }

        .input-wrap .toggle-pass {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9CA3AF;
            font-size: 16px;
            padding: 0;
        }

        .input-wrap .toggle-pass:hover {
            color: var(--biru);
        }

        /* Error */
        .error-msg {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            color: #991B1B;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Tombol login */
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--biru);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            background: var(--biru-mid);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login::after {
            content: '→';
            font-size: 18px;
        }

        /* Footer kartu */
        .card-footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #9CA3AF;
        }

        /* Footer halaman */
        .page-footer {
            color: rgba(255, 255, 255, 0.4);
            font-size: 12px;
            margin-top: 24px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
    </style>
</head>

<body>

    <div class="top-strip"></div>

    <div class="login-card">
        <div class="card-top">
            <div class="logo-wrap">
                <!-- Box Putih agar Logo Asli SMKN 1 Surabaya tetap kontras, jelas, dan berwarna indah -->
                <div class="logo-box">
                    <img src="../assets/css/img/smeas.png" alt="SMKN 1 Surabaya"
                        onerror="this.parentElement.innerHTML='🏫'">
                </div>
            </div>
            <div class="sistem-title">
                <strong>Sistem Informasi Lost & Found</strong><br>
                SMK Negeri 1 Surabaya
            </div>
            <div class="badge-admin">⚙ ADMIN PETUGAS</div>
        </div>

        <div class="card-body">
            <h2>Masuk ke Dashboard</h2>
            <p>Gunakan akun petugas yang sudah terdaftar</p>

            <?php if ($error): ?>
                <div class="error-msg">
                    <span>⚠</span> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrap">
                        <span class="ico">👤</span>
                        <input type="text" name="username" placeholder="Masukkan username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <span class="ico">🔒</span>
                        <input type="password" name="password" id="inputPassword" placeholder="Masukkan password"
                            autocomplete="current-password" required>
                        <button type="button" class="toggle-pass" onclick="togglePassword()" id="toggleBtn">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-login">Masuk</button>
            </form>

            <div class="card-footer-note">
                Lupa password? Hubungi administrator sistem.
            </div>
        </div>
    </div>

    <div class="page-footer">
        &copy; <?= date('Y') ?> Sistem Lost & Found — SMKN 1 Surabaya
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('inputPassword');
            const btn = document.getElementById('toggleBtn');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁';
            }
        }
    </script>

</body>

</html>