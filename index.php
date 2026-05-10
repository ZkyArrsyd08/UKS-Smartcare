<?php
include "koneksi.php";
session_start();

// Inisialisasi session dan cookie untuk pengguna
if (!isset($_SESSION['user_token'])) {
    $_SESSION['user_token'] = bin2hex(random_bytes(16));
    
    $cookieParams = [
        'expires' => time() + (86400 * 7),
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    setcookie("user_token", $_SESSION['user_token'], $cookieParams);
}

// 1. Tentukan minggu (ganjil/genap) berdasarkan date('W')
// Untuk testing paksa minggu 1 dulu (karena hari ini Jumat 8 Mei 2026)
$isMinggu2 = false; // Ganti ke true kalo mau minggu genap
// $isMinggu2 = (date('W') % 2 == 0); // UNCOMMENT UNTUK PRODUCTION

// 2. QUERY sesuai struktur database (minggu_ke, bukan minggu_ke1/minggu_ke2)
$target_minggu = $isMinggu2 ? '2' : '1';
$sql = "SELECT 
            DISTINCT a.id_admin,
            a.nama_lengkap, 
            a.kelas, 
            a.foto, 
            j.kode_jaga
        FROM jadwal j
        INNER JOIN admin a ON a.id_admin = j.id_admin
        WHERE j.minggu_ke = '$target_minggu'";

$q = mysqli_query($conn, $sql);

// Debug error
if (!$q) {
    echo "Error Query: " . mysqli_error($conn);
    exit;
}

$jadwal = [];
if (mysqli_num_rows($q) > 0) {
    while($r = mysqli_fetch_assoc($q)){
        // Handle Foto - PERBAIKI PATH
        $fotoDb = $r['foto'];
        
        // Base path ke folder foto (relative dari file ini)
        // File ini ada di folder Admin/, jadi ../assets/img/ itu bener
        $baseUrl = "../assets/img/";
        
        // Cek apakah foto ada dan bukan default
        if (empty($fotoDb) || $fotoDb == 'default.png' || $fotoDb == '') {
            $r['foto_url'] = $baseUrl . "default.png";
        } else {
            // Langsung gabungin baseUrl dengan nama file
            // Karena di database cuma nama file doang
            $r['foto_url'] = $baseUrl . $fotoDb;
        }
        $jadwal[] = $r;
    }
}

// DEBUG: Cek data yang keluar (hapus nanti)
// echo "<pre>";
// print_r($jadwal);
// echo "</pre>";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>UKS SmartCare - Dashboard Civitas</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
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
        --success: #10b981;
        --success-light: #d1fae5;
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

    @keyframes orbFloat {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        50% {
            transform: translate(-20px, 20px) scale(0.95);
        }
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
        0% {
            transform: translateY(100vh);
            opacity: 0;
        }

        10% {
            opacity: 0.3;
        }

        90% {
            opacity: 0.3;
        }

        100% {
            transform: translateY(-100vh);
            opacity: 0;
        }
    }

    /* HEADER */
    header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 100;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(226, 232, 240, 0.5);
        transition: all 0.3s ease;
    }

    header.scrolled {
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

    .nav-menu {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .nav-menu a {
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        color: var(--muted);
        padding: 10px 18px;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .nav-menu a:hover,
    .nav-menu a.active {
        color: var(--primary);
        background: var(--primary-light);
    }

    /* Tombol Daftar yang diberi style khusus */
    .nav-menu .daftar-btn {
    background: linear-gradient(135deg, var(--primary), #ef4444);
    color: white !important;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    transition: all 0,1s ease;
}

.nav-menu .daftar-btn:hover {
    background: linear-gradient(135deg, var(--primary-dark), #dc2626);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.4);
}

    .menu-btn {
        display: none;
        width: 44px;
        height: 44px;
        border: none;
        background: var(--primary-light);
        border-radius: 12px;
        cursor: pointer;
        color: var(--primary);
        font-size: 18px;
    }

    /* HERO */
    .hero {
        min-height: 100vh;
        padding: 160px 6% 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .hero-content {
        max-width: 700px;
        opacity: 0;
        transform: translateY(30px);
        animation: slideUp 0.8s ease forwards;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 24px;
    }

    .hero-title {
        font-size: clamp(36px, 6vw, 56px);
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 20px;
    }

    .hero-sub {
        font-size: 18px;
        color: var(--muted);
        line-height: 1.7;
        margin-bottom: 36px;
    }

    .hero-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 16px 32px;
        background: linear-gradient(135deg, var(--primary), #ef4444);
        color: white;
        text-decoration: none;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
        transition: all 0.3s ease;
    }

    .hero-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(220, 38, 38, 0.4);
    }

    /* SECTIONS */
    .section {
        padding: 100px 6%;
        max-width: 1200px;
        margin: auto;
    }

    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .section-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: var(--accent-light);
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 16px;
    }

    .section-title {
        font-size: clamp(28px, 4vw, 38px);
        font-weight: 800;
        margin-bottom: 12px;
    }

    .section-sub {
        font-size: 16px;
        color: var(--muted);
        max-width: 600px;
        margin: 0 auto;
    }

    /* FEATURES GRID */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 24px;
        opacity: 0;
        animation: slideUp 0.8s ease 0.2s forwards;
    }

    .feature-card {
        background: var(--card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: 32px;
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .feature-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--shadow-lg);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: var(--primary);
        margin-bottom: 20px;
    }

    .feature-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .feature-desc {
        font-size: 14px;
        color: var(--muted);
        line-height: 1.6;
    }

    /* SCHEDULE SECTION */
    #jadwal {
        background: linear-gradient(180deg, transparent, rgba(220, 38, 38, 0.03));
        padding: 100px 6%;
    }

    .schedule-container {
        max-width: 1000px;
        margin: auto;
    }

    .clock-card {
        background: var(--card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: 24px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 40px;
        box-shadow: var(--shadow-lg);
        opacity: 0;
        animation: slideUp 0.8s ease 0.1s forwards;
        flex-wrap: wrap;
    }

    .clock-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .clock-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-light);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: var(--primary);
    }

    .clock-display {
        font-size: 32px;
        font-weight: 800;
        color: var(--dark);
    }

    .clock-info {
        text-align: right;
    }

    .clock-day {
        font-size: 16px;
        font-weight: 700;
    }

    .clock-week {
        font-size: 13px;
        color: var(--muted);
    }

    /* SCHEDULE GRID */
    .schedule-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        opacity: 0;
        animation: slideUp 0.8s ease 0.2s forwards;
    }

    .schedule-card {
        background: var(--card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: 28px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .schedule-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }

    .schedule-avatar {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 40px;
        color: var(--primary);
        overflow: hidden;
    }

    .schedule-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .schedule-name {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 4px;
    }

    .schedule-class {
        font-size: 14px;
        color: var(--muted);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 16px;
        padding: 6px 14px;
        background: var(--success-light);
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
        color: var(--success);
    }

    .status-dot {
        width: 6px;
        height: 6px;
        background: var(--success);
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    /* FOOTER */
    footer {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        position: relative;
        overflow: hidden;
    }

    .footer-pattern {
        position: absolute;
        inset: 0;
        background-image: radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
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
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .social-links {
        display: flex;
        gap: 12px;
        margin-top: 8px;
        flex-wrap: wrap;
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

    /* ANIMATIONS & RESPONSIVE */
    @keyframes slideUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 6%;
            background: white;
            flex-direction: column;
            padding: 16px;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            gap: 4px;
            display: none;
            border: 1px solid var(--border);
            min-width: 180px;
        }

        .nav-menu.active {
            display: flex;
        }

        .nav-menu .daftar-btn {
            width: 100%;
            text-align: center;
            padding: 12px;
        }

        .clock-card {
            flex-direction: column;
            text-align: center;
        }

        .clock-left {
            flex-direction: column;
        }

        .clock-info {
            text-align: center;
        }

        .schedule-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .hero {
            padding: 120px 4% 40px;
        }

        .section {
            padding: 60px 4%;
        }

        #jadwal {
            padding: 60px 4%;
        }

        .footer-wrap {
            padding: 40px 4% 20px;
        }
    }
    </style>
</head>

<body>
    <div class="bg-container">
        <div class="gradient-orb orb-1"></div>
        <div class="gradient-orb orb-2"></div>
        <div class="particles" id="particles"></div>
    </div>

    <header id="header">
        <div class="navbar">
            <a href="#" class="logo">
                <div class="logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                UKS SmartCare
            </a>
            <button class="menu-btn" onclick="toggleMenu()" aria-label="Toggle menu">
                <i class="fa-solid fa-bars" id="menuIcon"></i>
            </button>
            <nav class="nav-menu" id="navMenu">
                <a href="#home">Beranda</a>
                <a href="#fitur">Fitur</a>
                <a href="#jadwal">Jadwal</a>
                <a href="../program/admin/loginadmin.php" class="daftar-btn">Daftar</a>
            </nav>
        </div>
    </header>

    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-badge"><i class="fa-solid fa-shield-heart"></i> Sistem Kesehatan Sekolah</div>
            <h1 class="hero-title">Selamat Datang di <span style="color:var(--primary)">UKS SmartCare</span></h1>
            <p class="hero-sub">Layanan kesehatan sekolah yang modern, cepat, dan terjadwal rapi.</p>
            <a href="#fitur" class="hero-btn"><i class="fa-solid fa-rocket"></i> Mulai Eksplorasi</a>
        </div>
    </section>

    <section class="section" id="fitur">
        <div class="section-header">
            <div class="section-badge"><i class="fa-solid fa-grid-2"></i> Layanan Kami</div>
            <h2 class="section-title">Fitur Unggulan</h2>
            <p class="section-sub">Berbagai layanan UKS yang bisa diakses secara digital oleh seluruh civitas sekolah.</p>
        </div>
        <div class="features-grid">
            <a href="../program/civitas/mintaobat.php" class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-pills"></i></div>
                <h3 class="feature-title">Minta Obat</h3>
                <p class="feature-desc">Ajukan permintaan obat secara digital dan lacak status pengambilannya.</p>
            </a>
            <a href="../program/civitas/stokobat.php" class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                <h3 class="feature-title">Stok Obat</h3>
                <p class="feature-desc">Pantau ketersediaan obat di UKS secara real-time kapan saja.</p>
            </a>
            <a href="../program/civitas/saranobat.php" class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-lightbulb"></i></div>
                <h3 class="feature-title">Saran Obat</h3>
                <p class="feature-desc">Panduan rekomendasi obat untuk keluhan kesehatan ringan.</p>
            </a>
            <a href="../program/civitas/feedbackcivitas.php" class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-comments"></i></div>
                <h3 class="feature-title">Feedback</h3>
                <p class="feature-desc">Sampaikan kritik dan saran untuk meningkatkan layanan UKS.</p>
            </a>
        </div>
    </section>

    <section id="jadwal">
        <div class="schedule-container">
            <div class="section-header">
                <div class="section-badge"><i class="fa-solid fa-calendar-check"></i> Jadwal Piket</div>
                <h2 class="section-title">Petugas UKS Hari Ini</h2>
                <p class="section-sub">Jadwal piket bergilir setiap minggu untuk memastikan layanan optimal.</p>
            </div>

            <div class="clock-card">
                <div class="clock-left">
                    <div class="clock-icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="clock-display" id="clock">00:00:00</div>
                </div>
                <div class="clock-info">
                    <div class="clock-day" id="hari">-</div>
                    <div class="clock-week" id="mingguText">-</div>
                </div>
            </div>

            <div class="schedule-grid" id="scheduleGrid">
                <!-- Akan diisi JavaScript -->
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-pattern"></div>
        <div class="footer-wrap">
            <div>
                <div class="footer-brand">
                    <div class="footer-brand-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    UKS SmartCare
                </div>
                <p>Sistem layanan kesehatan sekolah berbasis digital untuk pengelolaan obat, jadwal piket, dan pelayanan
                    siswa yang lebih efisien.</p>
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
            &copy; 2025 UKS SmartCare.
        </div>
    </footer>

    <script>
    // Data dari PHP - dengan foto URL
    const jadwalDB = <?php echo json_encode($jadwal); ?>;
    const isMinggu2 = <?php echo json_encode($isMinggu2); ?>;
    const hariIndo = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    
    console.log("Data Jadwal dari DB:", jadwalDB);
    console.log("Jumlah data:", jadwalDB.length);

    function createParticles() {
        const container = document.getElementById('particles');
        if (!container) return;
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (15 + Math.random() * 10) + 's';
            container.appendChild(particle);
        }
    }

    function toggleMenu() {
        const navMenu = document.getElementById('navMenu');
        const menuIcon = document.getElementById('menuIcon');
        if (navMenu) navMenu.classList.toggle('active');
        if (menuIcon) {
            menuIcon.className = navMenu?.classList.contains('active') ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        }
    }

    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const clockEl = document.getElementById('clock');
        const hariEl = document.getElementById('hari');
        const mingguTextEl = document.getElementById('mingguText');
        
        if (clockEl) clockEl.textContent = `${hours}:${minutes}:${seconds} WIB`;
        if (hariEl) hariEl.textContent = hariIndo[now.getDay()];
        if (mingguTextEl) mingguTextEl.textContent = isMinggu2 ? "Minggu Genap (Minggu ke-2)" : "Minggu Ganjil (Minggu ke-1)";
    }

    // Helper untuk mendapatkan kode_jaga yang seharusnya bertugas berdasarkan hari dan jam
    function getExpectedKodeJaga(day, hour, minute) {
        // day: 1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat
        const dayMap = {
            1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat'
        };
        
        const hariNama = dayMap[day];
        if (!hariNama) return null;
        
        const totalMinutes = hour * 60 + minute;
        
        const shiftByHari = {
            'Senin': { 1: '1A', 2: '1B', 3: '1C' },
            'Selasa': { 1: '2A', 2: '2B', 3: '2C' },
            'Rabu': { 1: '3A', 2: '3B', 3: '3C' },
            'Kamis': { 1: '4A', 2: '4B', 3: '4C' },
            'Jumat': { 1: '5A', 2: '5B', 3: '5C' }
        };
        
        let shiftNum = null;
        if (totalMinutes >= 450 && totalMinutes < 570) shiftNum = 1;
        else if (totalMinutes >= 570 && totalMinutes < 720) shiftNum = 2;
        else if (totalMinutes >= 720 && totalMinutes < 870) shiftNum = 3;
        
        if (shiftNum && shiftByHari[hariNama]) {
            return shiftByHari[hariNama][shiftNum];
        }
        return null;
    }

    function updateSchedule() {
        updateClock();
        
        const now = new Date();
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();
        const currentDay = now.getDay(); // 0 Minggu - 6 Sabtu
        
        const scheduleGrid = document.getElementById('scheduleGrid');
        if (!scheduleGrid) return;
        
        // Cek weekend (Sabtu = 6, Minggu = 0)
        if (currentDay === 0 || currentDay === 6) {
            scheduleGrid.innerHTML = `
                <div class="schedule-card">
                    <div class="schedule-avatar"><i class="fa-solid fa-calendar-xmark"></i></div>
                    <h3 class="schedule-name">Libur Akhir Pekan</h3>
                    <p class="schedule-class">Tidak ada jadwal piket</p>
                    <div class="status-badge" style="background:#fee2e2; color:#dc2626;">
                        <span class="status-dot" style="background:#dc2626;"></span> Libur
                    </div>
                </div>
            `;
            return;
        }
        
        // Dapatkan kode_jaga yang seharusnya bertugas
        const expectedKodeJaga = getExpectedKodeJaga(currentDay, currentHour, currentMinute);
        
        if (!expectedKodeJaga) {
            scheduleGrid.innerHTML = `
                <div class="schedule-card">
                    <div class="schedule-avatar"><i class="fa-solid fa-clock"></i></div>
                    <h3 class="schedule-name">Di Luar Jam Piket</h3>
                    <p class="schedule-class">Jam piket: 07:30 - 14:30</p>
                    <div class="status-badge" style="background:#fee2e2; color:#dc2626;">
                        <span class="status-dot" style="background:#dc2626;"></span> Tidak Aktif
                    </div>
                </div>
            `;
            return;
        }
        
        // Filter petugas yang memiliki kode_jaga sesuai dengan yang diharapkan
        const activePetugas = jadwalDB.filter(petugas => petugas.kode_jaga === expectedKodeJaga);
        
        console.log("Expected Kode Jaga:", expectedKodeJaga);
        console.log("Active Petugas:", activePetugas);
        
        if (activePetugas.length === 0) {
            scheduleGrid.innerHTML = `
                <div class="schedule-card">
                    <div class="schedule-avatar"><i class="fa-solid fa-user-slash"></i></div>
                    <h3 class="schedule-name">Tidak Ada Petugas</h3>
                    <p class="schedule-class">Kelompok ${expectedKodeJaga} tidak ada jadwal</p>
                    <div class="status-badge" style="background:#fee2e2; color:#dc2626;">
                        <span class="status-dot" style="background:#dc2626;"></span> Kosong
                    </div>
                </div>
            `;
            return;
        }
        
        // Tampilkan petugas yang bertugas (max 3 orang) dengan FOTO
        scheduleGrid.innerHTML = activePetugas.slice(0, 3).map(petugas => {
            // Tentukan sumber foto - PERBAIKI LOGIKA
            let fotoHtml = '';
            if (petugas.foto_url && petugas.foto_url !== '../assets/img/default.png' && petugas.foto_url !== '../assets/img/') {
                fotoHtml = `<img src="${petugas.foto_url}" alt="${petugas.nama_lengkap}" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fa-solid fa-user-nurse\'></i>'">`;
            } else {
                fotoHtml = '<i class="fa-solid fa-user-nurse"></i>';
            }
            
            return `
            <div class="schedule-card">
                <div class="schedule-avatar">
                    ${fotoHtml}
                </div>
                <h3 class="schedule-name">${petugas.nama_lengkap || '-'}</h3>
                <p class="schedule-class">${petugas.kelas || '-'}</p>
                <div class="status-badge">
                    <span class="status-dot"></span> Piket Aktif (${petugas.kode_jaga})
                </div>
            </div>
        `}).join('');
    }
    
    // Smooth scroll untuk anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('navMenu')?.classList.remove('active');
            }
        });
    });

    window.addEventListener('scroll', () => {
        const header = document.getElementById('header');
        if (header) {
            header.classList.toggle('scrolled', window.scrollY > 50);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        createParticles();
        updateSchedule();
        setInterval(updateSchedule, 1000);
    });
    </script>
</body>

</html>