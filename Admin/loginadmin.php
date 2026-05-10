<?php
session_start();
include '../koneksi.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Ambil data admin dari tabel admin (bukan user)
    $stmt = mysqli_prepare($conn, "SELECT id_admin, nama_lengkap, password, foto, kelas FROM admin WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);

    if ($admin && password_verify($password, $admin['password'])) {
        // Simpan data ke Session
        $_SESSION['login']        = true;
        $_SESSION['id_admin']     = $admin['id_admin'];
        $_SESSION['username']     = $username;
        $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
        $_SESSION['kelas']        = $admin['kelas'];
        $_SESSION['foto']         = $admin['foto'];
        
        // Redirect ke halaman admin
        header("Location: halamanadmin.php");
        exit;
    } else {
        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | UKS SmartCare</title>

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

  <style>
    :root {
      --primary: #dc2626;
      --primary-dark: #991b1b;
      --primary-light: #fef2f2;
      --accent: #0ea5e9;
      --accent-light: #e0f2fe;
      --dark: #1e293b;
      --muted: #64748b;
      --light: #f8fafc;
      --border: #e2e8f0;
      --success: #10b981;
      --radius: 14px;
      --radius-lg: 20px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--dark);
      background: linear-gradient(135deg, #fef2f2 0%, #e0f2fe 50%, #f0fdf4 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow-x: hidden;
      position: relative;
      padding: 24px;
    }

    /* BACKGROUND ELEMENTS */
    .bg-container {
      position: fixed;
      inset: 0;
      z-index: 0;
      overflow: hidden;
      pointer-events: none;
    }

    .floating-shape {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.5;
      animation: floatAround 30s ease-in-out infinite;
    }

    .shape-1 {
      width: 350px;
      height: 350px;
      background: radial-gradient(circle, rgba(220, 38, 38, 0.35), transparent 70%);
      top: -100px;
      right: -80px;
    }

    .shape-2 {
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(14, 165, 233, 0.3), transparent 70%);
      bottom: -80px;
      left: -60px;
      animation-delay: -10s;
    }

    .shape-3 {
      width: 250px;
      height: 250px;
      background: radial-gradient(circle, rgba(16, 185, 129, 0.25), transparent 70%);
      top: 40%;
      left: 30%;
      animation-delay: -20s;
    }

    @keyframes floatAround {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(30px, -30px) scale(1.05); }
    }

    .particles-container {
      position: absolute;
      inset: 0;
      overflow: hidden;
    }

    .particle {
      position: absolute;
      border-radius: 50%;
      pointer-events: none;
    }

    .particle-small {
      width: 3px;
      height: 3px;
      background: var(--primary);
      opacity: 0.3;
      animation: particleFloat 22s linear infinite;
    }

    .particle-medium {
      width: 6px;
      height: 6px;
      background: var(--accent);
      opacity: 0.2;
      animation: particleFloat 28s linear infinite;
    }

    @keyframes particleFloat {
      0% { transform: translateY(100vh) scale(0); opacity: 0; }
      10% { opacity: 0.4; }
      90% { opacity: 0.2; }
      100% { transform: translateY(-10vh) scale(0.5); opacity: 0; }
    }

    /* LOGIN WRAPPER */
    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 380px;
    }

    /* LOGIN CARD */
    .login-card {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border-radius: var(--radius-lg);
      border: 1px solid rgba(255, 255, 255, 0.6);
      box-shadow: 
        0 24px 48px rgba(0, 0, 0, 0.08),
        0 0 0 1px rgba(255, 255, 255, 0.4) inset;
      overflow: hidden;
      position: relative;
      opacity: 0;
      transform: translateY(30px) scale(0.97);
      animation: cardReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s forwards;
    }

    @keyframes cardReveal {
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    /* CARD HEADER */
    .card-header {
      padding: 28px 24px 20px;
      text-align: center;
      position: relative;
    }

    .logo-container {
      position: relative;
      display: inline-block;
      margin-bottom: 16px;
    }

    .logo-glow {
      position: absolute;
      inset: -8px;
      background: radial-gradient(circle, rgba(220, 38, 38, 0.15), transparent 70%);
      border-radius: 50%;
      animation: logoPulse 3s ease-in-out infinite;
    }

    @keyframes logoPulse {
      0%, 100% { transform: scale(1); opacity: 0.6; }
      50% { transform: scale(1.15); opacity: 0.9; }
    }

    .logo-icon {
      position: relative;
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, var(--primary) 0%, #ef4444 100%);
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: white;
      box-shadow: 
        0 12px 24px rgba(220, 38, 38, 0.25),
        0 0 0 3px rgba(255, 255, 255, 0.9);
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .login-card:hover .logo-icon {
      transform: rotate(-5deg) scale(1.05);
    }

    .brand-name {
      font-size: 22px;
      font-weight: 800;
      background: linear-gradient(135deg, var(--primary), var(--dark));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: -0.3px;
    }

    .brand-tagline {
      font-size: 12px;
      color: var(--muted);
      font-weight: 600;
      margin-top: 4px;
      letter-spacing: 0.3px;
    }

    /* CARD BODY */
    .card-body {
      padding: 0 24px 24px;
      position: relative;
    }

    .welcome-section {
      text-align: center;
      margin-bottom: 20px;
    }

    .welcome-title {
      font-size: 17px;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 6px;
      opacity: 0;
      transform: translateY(15px);
      animation: fadeSlideUp 0.5s ease 0.4s forwards;
    }

    .welcome-desc {
      font-size: 13px;
      color: var(--muted);
      line-height: 1.5;
      opacity: 0;
      transform: translateY(15px);
      animation: fadeSlideUp 0.5s ease 0.5s forwards;
    }

    @keyframes fadeSlideUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* LOGIN FORM */
    .login-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-top: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-label {
      font-size: 13px;
      font-weight: 600;
      color: var(--dark);
    }

    .form-input {
      padding: 12px 16px;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 14px;
      transition: all 0.3s ease;
      background: white;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }

    .btn-login {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 14px 18px;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
      overflow: hidden;
      background: linear-gradient(135deg, var(--primary) 0%, #b91c1c 100%);
      color: white;
      border: none;
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(220, 38, 38, 0.28);
    }

    .btn-login::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 50%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .btn-login:hover {
      transform: translateY(-3px);
      box-shadow: 0 14px 28px rgba(220, 38, 38, 0.35);
    }

    .btn-login:hover::before {
      opacity: 1;
    }

    .btn-icon {
      font-size: 16px;
    }

    .btn-text {
      font-weight: 700;
    }

    /* ERROR MESSAGE */
    .error-message {
      background: rgba(220, 38, 38, 0.08);
      color: var(--primary);
      padding: 10px 14px;
      border-radius: var(--radius);
      font-size: 12px;
      font-weight: 600;
      text-align: center;
      opacity: 0;
      transform: translateY(-10px);
      animation: fadeSlideDown 0.3s ease forwards;
    }

    @keyframes fadeSlideDown {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* LINKS */
    .login-links {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 20px;
    }

    .login-link {
      font-size: 12px;
      color: var(--primary);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
    }

    .login-link:hover {
      color: var(--primary-dark);
      transform: translateX(3px);
    }

    .login-link i {
      font-size: 10px;
    }

    /* CARD FOOTER */
    .card-footer {
      padding: 16px 24px 20px;
      border-top: 1px solid rgba(226, 232, 240, 0.6);
      text-align: center;
      background: rgba(248, 250, 252, 0.5);
    }

    .security-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      background: rgba(16, 185, 129, 0.08);
      border-radius: 100px;
      font-size: 11px;
      font-weight: 600;
      color: var(--success);
      opacity: 0;
      animation: fadeSlideUp 0.5s ease 0.75s forwards;
    }

    .security-badge i {
      font-size: 12px;
    }

    /* RESPONSIVE - MOBILE FIRST */
    @media (max-width: 400px) {
      body {
        padding: 20px 16px;
      }

      .card-header {
        padding: 24px 20px 18px;
      }

      .card-body {
        padding: 0 20px 20px;
      }

      .card-footer {
        padding: 14px 20px 18px;
      }

      .logo-icon {
        width: 56px;
        height: 56px;
        font-size: 24px;
        border-radius: 14px;
      }

      .brand-name {
        font-size: 20px;
      }

      .brand-tagline {
        font-size: 11px;
      }

      .welcome-title {
        font-size: 16px;
      }

      .welcome-desc {
        font-size: 12px;
      }

      .form-input {
        padding: 10px 14px;
        font-size: 13px;
      }

      .btn-login {
        padding: 12px 16px;
        font-size: 13px;
      }

      .btn-icon {
        font-size: 14px;
      }

      .login-link {
        font-size: 11px;
      }
    }

    /* Larger screens */
    @media (min-width: 768px) {
      body {
        padding: 40px;
      }

      .login-wrapper {
        max-width: 400px;
      }

      .card-header {
        padding: 32px 28px 24px;
      }

      .card-body {
        padding: 0 28px 28px;
      }

      .card-footer {
        padding: 18px 28px 24px;
      }

      .logo-icon {
        width: 68px;
        height: 68px;
        font-size: 30px;
      }

      .brand-name {
        font-size: 24px;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>
<body>
  <!-- BACKGROUND -->
  <div class="bg-container">
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    <div class="particles-container" id="particles"></div>
  </div>

  <!-- LOGIN WRAPPER -->
  <main class="login-wrapper">
    <div class="login-card">
      <!-- Card Header -->
      <div class="card-header">
        <div class="logo-container">
          <div class="logo-glow"></div>
          <div class="logo-icon">
            <i class="fa-solid fa-heart-pulse"></i>
          </div>
        </div>
        
        <div class="brand-name">UKS SmartCare</div>
        <div class="brand-tagline">Sistem Kesehatan Sekolah Digital</div>
      </div>
      
      <!-- Card Body -->
      <div class="card-body">
        <div class="welcome-section">
          <h1 class="welcome-title">Login Admin</h1>
          <p class="welcome-desc">Masukkan kredensial Anda untuk mengakses dashboard admin.</p>
        </div>

        <?php if($error): ?>
        <div class="error-message">
          <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
          <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-input" required>
          </div>

          <button type="submit" class="btn-login">
            <i class="fas fa-right-to-bracket btn-icon"></i>
            <span class="btn-text">MASUK</span>
          </button>
        </form>

        <div class="login-links">
          <a href="pendaftaran.php" class="login-link">
            <i class="fas fa-user-plus"></i>
            <span>Daftar</span>
          </a>
          <a href="../index.php" class="login-link">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali</span>
          </a>
        </div>
      </div>

      <!-- Card Footer -->
      <div class="card-footer">
        <div class="security-badge">
          <i class="fa-solid fa-shield-halved"></i>
          <span>Koneksi Aman & Terenkripsi</span>
        </div>
      </div>
    </div>
  </main>

  <script>
    function createParticles() {
      const container = document.getElementById('particles');
      if (!container) return;
      
      const particleCount = 20;
      const types = ['particle-small', 'particle-medium'];
      
      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        const type = types[Math.floor(Math.random() * types.length)];
        particle.className = 'particle ' + type;
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 25 + 's';
        container.appendChild(particle);
      }
    }

    document.addEventListener('DOMContentLoaded', createParticles);
  </script>
</body>
</html>