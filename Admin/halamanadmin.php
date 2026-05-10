<?php
session_start();
include '../koneksi.php';

// Cek apakah sudah login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: loginadmin.php");
    exit;
}

// AMBIL ID DARI SESSION (gunakan id_admin, bukan id_user)
$id_admin_sekarang = $_SESSION['id_admin'];

// AMBIL DATA TERBARU DARI DATABASE (Tabel admin)
$query = mysqli_query($conn, "SELECT * FROM admin WHERE id_admin = '$id_admin_sekarang'");
$admin = mysqli_fetch_assoc($query);

// Cek apakah data admin ditemukan
if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit;
}

// PROSES UPDATE PROFIL
if (isset($_POST['update'])) {
    $user_baru = mysqli_real_escape_string($conn, $_POST['username']);
    $kelas_baru = mysqli_real_escape_string($conn, $_POST['kelas']);
    $nama_lengkap_baru = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    
    $target_dir = "../assets/img/"; 
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $foto_nama = $admin['foto']; // Default pakai foto lama
    
    if ($_FILES['foto']['name'] != "") {
        $ekstensi = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        
        if(!in_array($ekstensi, $allowed)){
            echo "<script>alert('Format file salah! Gunakan JPG/PNG.');</script>";
        } else {
            // Hapus foto lama jika bukan default.png
            if($admin['foto'] != 'default.png' && file_exists($target_dir . $admin['foto'])) {
                unlink($target_dir . $admin['foto']);
            }

            $foto_nama = "admin_" . $id_admin_sekarang . "_" . time() . "." . $ekstensi;
            $target_file = $target_dir . $foto_nama;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                $_SESSION['foto'] = $foto_nama;
            }
        }
    }
    
    // Query update ke tabel 'admin'
    $sql = "UPDATE admin SET 
            username='$user_baru', 
            kelas='$kelas_baru',
            nama_lengkap='$nama_lengkap_baru',
            foto='$foto_nama' 
            WHERE id_admin='$id_admin_sekarang'";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['username'] = $user_baru;
        $_SESSION['nama_lengkap'] = $nama_lengkap_baru;
        
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='halamanadmin.php';</script>";
    } else {
        echo "<script>alert('Gagal update: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | UKS SmartCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

<style>
:root {
    --primary: #dc2626;
    --primary-dark: #b91c1c;
    --primary-light: #fef2f2;
    --accent: #0ea5e9;
    --accent-light: #e0f2fe;
    --dark: #1e293b;
    --muted: #64748b;
    --light: #f8fafc;
    --card: #ffffff;
    --border: #e2e8f0;
    --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    --radius: 16px;
    --radius-lg: 24px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: var(--dark);
    background: var(--light);
    overflow-x: hidden;
    min-height: 100vh;
}

/* ANIMATED BACKGROUND */
.bg-container {
    position: fixed;
    inset: 0;
    z-index: -1;
    overflow: hidden;
}

.gradient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.6;
    animation: orbFloat 20s ease-in-out infinite;
}

.orb-1 {
    width: 600px;
    height: 600px;
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.3), rgba(251, 113, 133, 0.2));
    top: -200px;
    right: -100px;
}

.orb-2 {
    width: 500px;
    height: 500px;
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(56, 189, 248, 0.15));
    bottom: -150px;
    left: -100px;
    animation-delay: -7s;
}

.orb-3 {
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(52, 211, 153, 0.1));
    top: 40%;
    left: 30%;
    animation-delay: -14s;
}

@keyframes orbFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(20px, 30px) scale(1.02); }
}

/* PARTICLES */
.particles {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: var(--primary);
    border-radius: 50%;
    opacity: 0.3;
    animation: particleRise 15s linear infinite;
}

@keyframes particleRise {
    0% { transform: translateY(100vh); opacity: 0; }
    10% { opacity: 0.3; }
    90% { opacity: 0.3; }
    100% { transform: translateY(-100vh); opacity: 0; }
}

/* HEADER */
header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 100;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(226, 232, 240, 0.5);
    transition: all 0.3s ease;
}

header.scrolled {
    background: rgba(255, 255, 255, 0.98);
    box-shadow: var(--shadow-lg);
}

.navbar {
    max-width: 1200px;
    margin: auto;
    padding: 16px 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 22px;
    font-weight: 800;
    color: var(--primary);
    text-decoration: none;
}

.logo:hover { transform: scale(1.02); }

.logo-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--primary), #f87171);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

/* PROFILE NAV */
.profile-container {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.welcome-text {
    font-size: 14px;
    line-height: 1.2;
    text-align: right;
}

.welcome-text strong {
    display: block;
    font-weight: 600;
    color: var(--dark);
}

.profile-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--card);
    border: 2px solid var(--border);
    transition: all 0.3s ease;
}

.profile-icon:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

.profile-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-dropdown {
    display: none;
    position: absolute;
    top: 55px;
    right: 0;
    background: var(--card);
    box-shadow: var(--shadow-xl);
    border-radius: var(--radius-lg);
    width: 220px;
    overflow: hidden;
    z-index: 101;
    border: 1px solid var(--border);
}

.profile-dropdown ul {
    list-style: none;
}

.profile-dropdown ul li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    text-decoration: none;
    color: var(--dark);
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 14px;
}

.profile-dropdown ul li:last-child a {
    color: var(--primary);
    border-top: 1px solid var(--border);
    font-weight: 600;
}

.profile-dropdown ul li a:hover {
    background: var(--primary-light);
    color: var(--primary);
}

.profile-dropdown.active { display: block; }

/* MAIN CONTENT */
.main-section {
    padding: 140px 6% 80px;
    max-width: 1200px;
    margin: auto;
    opacity: 0;
    transform: translateY(30px);
    animation: slideUp 0.8s ease forwards;
}

.section-header {
    text-align: center;
    margin-bottom: 48px;
}

.section-title {
    font-size: clamp(28px, 5vw, 42px);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 16px;
    background: linear-gradient(135deg, var(--dark), var(--muted));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.section-sub {
    font-size: 16px;
    color: var(--muted);
    line-height: 1.7;
    max-width: 500px;
    margin: 0 auto;
}

/* FEATURE TEXT SECTION */
.feature-text-section {
    background: var(--card);
    border-radius: var(--radius-lg);
    padding: 32px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border);
    margin-bottom: 40px;
    opacity: 0;
    transform: translateY(30px);
    animation: slideUp 0.8s ease 0.5s forwards;
}

.feature-text-section h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.feature-text-section h3 i {
    color: var(--primary);
}

.feature-text-section ul {
    list-style: none;
    padding: 0;
}

.feature-text-section li {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    font-size: 15px;
    color: var(--dark);
}

.feature-text-section li i {
    color: var(--primary);
    font-size: 14px;
}

/* FEATURE CARDS */
.feature-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.feature-card {
    background: var(--card);
    border-radius: var(--radius-lg);
    padding: 32px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border);
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(30px);
    animation: slideUp 0.8s ease forwards;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.feature-card:nth-child(1) { animation-delay: 0.6s; }
.feature-card:nth-child(2) { animation-delay: 0.7s; }
.feature-card:nth-child(3) { animation-delay: 0.8s; }
.feature-card:nth-child(4) { animation-delay: 0.9s; }
.feature-card:nth-child(5) { animation-delay: 1.0s; }

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary);
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}

.feature-icon i {
    font-size: 32px;
    color: var(--primary);
}

.feature-card h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--dark);
}

.feature-card p {
    font-size: 14px;
    color: var(--muted);
    line-height: 1.6;
}

/* MODAL */
.modal-bg {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 200;
    padding: 20px;
    backdrop-filter: blur(4px);
}

.modal-bg.active {
    display: flex;
}

.modal {
    background: var(--card);
    padding: 32px;
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 500px;
    position: relative;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-xl);
}

.modal h3 {
    font-size: 24px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 16px;
}

.modal p {
    font-size: 13px;
    color: var(--muted);
    margin-bottom: 24px;
}

.modal label {
    font-size: 14px;
    font-weight: 600;
    color: var(--muted);
    display: block;
    margin-bottom: 8px;
}

.modal input {
    width: 100%;
    padding: 14px;
    margin-bottom: 20px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: inherit;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #f9f9f9;
}

.modal input[readonly] {
    background: #f1f1f1;
    cursor: not-allowed;
}

.modal input:focus {
    outline: none;
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.save-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 14px;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-weight: 600;
    margin-top: 20px;
    transition: all 0.3s ease;
}

.save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
}

.close {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: var(--muted);
    transition: all 0.3s ease;
}

.close:hover {
    color: var(--primary);
    transform: scale(1.1);
}

/* FOOTER */
footer {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    margin-top: 60px;
    position: relative;
    overflow: hidden;
}

.footer-pattern {
    position: absolute;
    inset: 0;
    background-image: 
        radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
}

.footer-wrap {
    max-width: 1200px;
    margin: auto;
    padding: 64px 6% 32px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 48px;
    position: relative;
    z-index: 1;
}

.footer-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 16px;
}

.footer-brand-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

footer h4 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 20px;
}

footer p {
    font-size: 14px;
    line-height: 1.8;
    color: rgba(255, 255, 255, 0.85);
}

.footer-contact p {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.footer-contact i {
    color: rgba(255, 255, 255, 0.9);
    margin-top: 4px;
    font-size: 14px;
}

.social-links {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.social-links a {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.social-links a:hover {
    background: white;
    color: var(--primary);
    transform: translateY(-4px);
}

.footer-bottom {
    text-align: center;
    padding: 24px 6%;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
    position: relative;
    z-index: 1;
}

@keyframes slideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .navbar {
        padding: 16px 5%;
    }

    .welcome-text {
        display: none;
    }

    .main-section {
        padding: 120px 5% 60px;
    }

    .feature-cards {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .feature-card {
        padding: 24px;
    }

    .modal {
        width: 90%;
        margin: 0 5%;
        padding: 24px;
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
    <div class="bg-container">
        <div class="gradient-orb orb-1"></div>
        <div class="gradient-orb orb-2"></div>
        <div class="gradient-orb orb-3"></div>
        <div class="particles" id="particles"></div>
    </div>

    <header id="header">
        <div class="navbar">
            <a href="#" class="logo">
                <div class="logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                UKS SmartCare
            </a>
            <div class="profile-container" id="profileContainer">
                <div class="welcome-text">Halo, Admin!<br><strong><?= htmlspecialchars($admin['nama_lengkap']) ?></strong></div>
                <div class="profile-icon">
                    <img src="../assets/img/<?= $admin['foto'] ?: 'default.png' ?>" alt="Profile">
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <ul>
                        <li><a href="halamanadmin.php"><i class="fa-solid fa-home"></i> Beranda</a></li>
                        <li><a href="#" onclick="openProfile(); return false;"><i class="fas fa-cog"></i> Pengaturan</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="main-section">
        <div class="section-header">
            <h1 class="section-title">Dashboard Admin</h1>
            <p class="section-sub">Kelola semua data UKS SmartCare dengan cepat dan efisien</p>
        </div>

        <div class="feature-text-section">
            <h3><i class="fa-solid fa-star"></i> Fitur Unggulan</h3>
            <ul>
                <li><i class="fa-solid fa-check"></i> Sistem pelayanan obat yang cepat dan efisien</li>
                <li><i class="fa-solid fa-check"></i> Manajemen stok otomatis dengan notifikasi</li>
                <li><i class="fa-solid fa-check"></i> Jadwal piket petugas yang fleksibel</li>
                <li><i class="fa-solid fa-check"></i> Sistem feedback untuk peningkatan layanan</li>
                <li><i class="fa-solid fa-check"></i> Laporan komprehensif untuk analisis data</li>
            </ul>
        </div>

        <div class="feature-cards">
            <a href="terimaobat.php" class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-pills"></i>
                </div>
                <h3>Terima Obat</h3>
                <p>Kelola permintaan obat dari siswa dan proses pelayanan.</p>
            </a>
            <a href="feedback.php" class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h3>Feedback</h3>
                <p>Baca saran dan masukan siswa untuk peningkatan layanan.</p>
            </a>
            <a href="jadwal.php" class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-calendar-alt"></i>
                </div>
                <h3>Jadwal Jaga</h3>
                <p>Atur jadwal piket dan shift kerja petugas UKS.</p>
            </a>
            <a href="stok.php" class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <h3>Stok & Transaksi</h3>
                <p>Catat obat masuk, keluar, dan laporan stok.</p>
            </a>
            <a href="saran.php" class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
                <h3>Saran Obat</h3>
                <p>Edit data saran obat dan informasi kesehatan.</p>
            </a>
        </div>
    </main>

    <!-- MODAL EDIT PROFIL -->
    <div class="modal-bg" id="profileModal">
        <div class="modal">
            <span class="close" onclick="closeProfile()">&times;</span>
            <h3>Edit Profil</h3>
            <p style="font-size: 13px; color: var(--muted);">Perbarui informasi profil Anda</p>
            <form action="" method="POST" enctype="multipart/form-data">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 24px;">
                    <div style="position: relative;">
                        <img id="previewImg" src="../assets/img/<?= $admin['foto'] ?: 'default.png' ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border);">
                        <label for="fileInput" style="position: absolute; bottom: 0; right: 0; background: var(--primary); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: white;">
                            <i class="fas fa-camera" style="font-size: 12px;"></i>
                        </label>
                        <input type="file" name="foto" id="fileInput" hidden accept="image/*">
                    </div>
                    <div style="flex: 1;">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" value="<?= htmlspecialchars($admin['nama_lengkap']) ?>" required readonly>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" id="username" value="<?= htmlspecialchars($admin['username']) ?>" required readonly>
                        </div>
                        <div class="form-group">
                            <label>Kelas / Jabatan</label>
                            <input type="text" name="kelas" id="kelas" value="<?= htmlspecialchars($admin['kelas']) ?>" readonly>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="enableEdit()" class="save-btn" id="editBtn" style="background: var(--accent); margin-top: 0;">
                        <i class="fas fa-edit"></i> Edit Profil
                    </button>
                    <button type="submit" name="update" id="saveBtn" class="save-btn" style="display: none; margin-top: 0;">
                        <i class="fa-solid fa-save"></i> Simpan Perubahan
                    </button>
                    <button type="button" onclick="cancelEdit()" id="cancelBtn" class="save-btn" style="background: #6b7280; display: none; margin-top: 0;">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="footer-pattern"></div>
        <div class="footer-wrap">
            <div>
                <div class="footer-brand">
                    <div class="footer-brand-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    UKS SmartCare
                </div>
                <p>Sistem layanan kesehatan sekolah berbasis digital untuk pengelolaan obat, jadwal piket, dan pelayanan siswa yang lebih efisien.</p>
            </div>
            <div class="footer-contact">
                <h4>Kontak Kami</h4>
                <p><i class="fa-solid fa-location-dot"></i> SMKN 1 Cibinong, Jawa Barat</p>
                <p><i class="fa-solid fa-phone"></i> 0812-3456-7890</p>
                <p><i class="fa-solid fa-envelope"></i> uks.smartcare@gmail.com</p>
            </div>
            <div>
                <h4>Ikuti Kami</h4>
                <p style="margin-bottom: 16px;">Tetap terhubung untuk informasi terbaru</p>
                <div class="social-links">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2026 UKS SmartCare.
        </div>
    </footer>

    <script>
    // Profile Dropdown
    const profileContainer = document.getElementById('profileContainer');
    const profileDropdown = document.getElementById('profileDropdown');

    profileContainer.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('active');
    });

    document.addEventListener('click', () => profileDropdown.classList.remove('active'));

    // Modal functions
    function openProfile() {
        document.getElementById('profileModal').classList.add('active');
    }

    function closeProfile() {
        document.getElementById('profileModal').classList.remove('active');
        cancelEdit();
    }

    function enableEdit() {
        document.getElementById('nama_lengkap').removeAttribute('readonly');
        document.getElementById('username').removeAttribute('readonly');
        document.getElementById('kelas').removeAttribute('readonly');
        
        document.getElementById('nama_lengkap').style.background = '#fff';
        document.getElementById('username').style.background = '#fff';
        document.getElementById('kelas').style.background = '#fff';
        
        document.getElementById('editBtn').style.display = 'none';
        document.getElementById('saveBtn').style.display = 'block';
        document.getElementById('cancelBtn').style.display = 'block';
    }

    function cancelEdit() {
        document.getElementById('nama_lengkap').setAttribute('readonly', 'readonly');
        document.getElementById('username').setAttribute('readonly', 'readonly');
        document.getElementById('kelas').setAttribute('readonly', 'readonly');
        
        document.getElementById('nama_lengkap').style.background = '#f1f1f1';
        document.getElementById('username').style.background = '#f1f1f1';
        document.getElementById('kelas').style.background = '#f1f1f1';
        
        document.getElementById('editBtn').style.display = 'block';
        document.getElementById('saveBtn').style.display = 'none';
        document.getElementById('cancelBtn').style.display = 'none';
    }

    // Preview foto
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('previewImg').src = event.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // Particles
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('particles');
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (15 + Math.random() * 10) + 's';
            container.appendChild(particle);
        }
    });

    // Header scroll effect
    window.addEventListener('scroll', () => {
        document.getElementById('header').classList.toggle('scrolled', window.scrollY > 50);
    });
    </script>
</body>
</html>