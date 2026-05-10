<?php
session_start();
include '../koneksi.php'; 

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama     = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $kelas    = trim($_POST['kelas']);
    $keyword  = trim($_POST['keyword']);
    
    $keyword_bener = "PMRMILLENIUM";

    if ($keyword !== $keyword_bener) {
        $error = "Keyword salah! Hanya petugas resmi yang bisa mendaftar.";
    } else {
        // Cek username
        $stmt = mysqli_prepare($conn, "SELECT id_admin FROM admin WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // LOGIKA UPLOAD FOTO
            $nama_foto = "default.png"; 
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                $ekstensi_diperbolehkan = ['jpg', 'jpeg', 'png'];
                $x = explode('.', $_FILES['foto']['name']);
                $ekstensi = strtolower(end($x));
                $file_tmp = $_FILES['foto']['tmp_name'];

                if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
                    $nama_foto = time() . '_' . $username . '.' . $ekstensi;
                    move_uploaded_file($file_tmp, '../assets/img/' . $nama_foto);
                }
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // ✅ KODE BARU (BENAR)
            $insert = mysqli_prepare(
            $conn,"INSERT INTO admin (nama_lengkap, kelas, username, password, foto) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert, "sssss", $nama, $kelas, $username, $password_hash, $nama_foto);
            
            if (mysqli_stmt_execute($insert)) {
                header("Location: loginadmin.php");
                exit;
            } else {
                $error = "Gagal simpan data: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar Petugas | UKS SmartCare</title>

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

    /* REGISTER WRAPPER */
    .register-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 480px;
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

    /* REGISTER CARD */
    .register-card {
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

    .register-card:hover .logo-icon {
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

    /* REGISTER FORM */
    .register-form {
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

    .btn-register {
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

    .btn-register::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 50%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .btn-register:hover {
      transform: translateY(-3px);
      box-shadow: 0 14px 28px rgba(220, 38, 38, 0.35);
    }

    .btn-register:hover::before {
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
    .register-links {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 20px;
    }

    .register-link {
      font-size: 12px;
      color: var(--primary);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
    }

    .register-link:hover {
      color: var(--primary-dark);
      transform: translateX(3px);
    }

    .register-link i {
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

      .btn-register {
        padding: 12px 16px;
        font-size: 13px;
      }

      .btn-icon {
        font-size: 14px;
      }

      .register-link {
        font-size: 11px;
      }
    }

    /* Larger screens */
    @media (min-width: 768px) {
      body {
        padding: 40px;
      }

      .register-wrapper {
        max-width: 480px;
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
    .photo-upload-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.photo-preview {
    width: 100%;
    height: 120px;
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--light);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.photo-preview:hover {
    border-color: var(--primary);
    background: var(--primary-light);
}

.preview-placeholder {
    text-align: center;
    color: var(--muted);
}

.preview-placeholder i {
    font-size: 36px;
    margin-bottom: 8px;
}

.preview-text {
    font-size: 12px;
    font-weight: 600;
}

.btn-upload {
    padding: 10px 16px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: white;
    color: var(--dark);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-upload:hover {
    border-color: var(--primary);
    color: var(--primary);
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

#photoInput {
    display: none;
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: none;
}

.photo-preview.has-photo img {
    display: block;
}

.photo-preview.has-photo .preview-placeholder {
    display: none;
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

  <!-- REGISTER WRAPPER -->
  <main class="register-wrapper">
    <div class="register-card">
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
          <h1 class="welcome-title">Daftar Petugas</h1>
          <p class="welcome-desc">Lengkapi data di bawah ini untuk mendaftar sebagai Administrator UKS.</p>
        </div>

        <?php if($error): ?>
        <div class="error-message">
          <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="register-form" enctype="multipart/form-data">
    <div class="form-group">
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" class="form-input" required>
    </div>

    <div class="form-group">
        <label class="form-label">Kelas / Jabatan</label>
        <input type="text" name="kelas" class="form-input" required>
    </div>

    <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-input" required>
    </div>

    <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" required>
    </div>

    <div class="form-group">
    <label class="form-label">Foto Profil</label>
    <div class="photo-upload-container">
        <div class="photo-preview" id="photoPreview">
            <div class="preview-placeholder">
                <i class="fas fa-user-circle fa-3x"></i>
                <span class="preview-text">Tidak ada foto</span>
            </div>
        </div>
        <input type="file" name="foto" class="form-input" accept="image/*" id="photoInput" style="display: none;">
        <button type="button" class="btn-upload" onclick="document.getElementById('photoInput').click()">
            <i class="fas fa-camera"></i>
            <span>Pilih Foto</span>
        </button>
    </div>
</div>

    <div class="form-group">
        <label class="form-label">Keyword Petugas</label>
        <input type="password" name="keyword" class="form-input" required>
    </div>

    <button type="submit" class="btn-register">
        <i class="fas fa-user-plus btn-icon"></i>
        <span class="btn-text">DAFTAR SEKARANG</span>
    </button>
</form>
        <div class="register-links">
          <a href="loginadmin.php" class="register-link">
            <i class="fas fa-arrow-left"></i>
            <span>Sudah punya akun?</span>
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
    document.getElementById('photoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('photoPreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = preview.querySelector('img');
            if (!img) {
                const newImg = document.createElement('img');
                preview.appendChild(newImg);
            }
            preview.querySelector('img').src = e.target.result;
            preview.classList.add('has-photo');
        }
        reader.readAsDataURL(file);
    }
});
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