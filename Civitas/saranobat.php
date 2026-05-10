<?php
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

include '../koneksi.php';

// Ambil data obat dari database dengan JOIN ke saran_obat
function getMedications($search = '', $category = 'all') {
    global $conn;
    
    $query = "SELECT o.*, s.id_saran, s.penjelasan, s.kategori as saran_kategori, s.jenis as saran_jenis 
              FROM obat o 
              LEFT JOIN saran_obat s ON o.id_obat = s.id_obat 
              WHERE 1=1";
    
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $query .= " AND (o.nama_obat LIKE '%$search%' OR s.penjelasan LIKE '%$search%')";
    }
    
    if ($category !== 'all') {
        $category = mysqli_real_escape_string($conn, $category);
        $query .= " AND s.kategori = '$category'";
    }
    
    $query .= " ORDER BY o.nama_obat ASC";
    
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Ambil parameter pencarian dari GET, POST, atau COOKIE
$searchTerm = '';
$categoryFilter = 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchTerm = isset($_POST['search']) ? $_POST['search'] : '';
    $categoryFilter = isset($_POST['category']) ? $_POST['category'] : 'all';
    // Simpan ke cookie
    setcookie("search_term", $searchTerm, time() + (86400 * 7), "/");
    setcookie("category_filter", $categoryFilter, time() + (86400 * 7), "/");
} else {
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : (isset($_COOKIE['search_term']) ? $_COOKIE['search_term'] : '');
    $categoryFilter = isset($_GET['category']) ? $_GET['category'] : (isset($_COOKIE['category_filter']) ? $_COOKIE['category_filter'] : 'all');
}

// Ambil data obat
$medications = getMedications($searchTerm, $categoryFilter);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saran Obat | UKS SmartCare</title>
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
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --purple: #8b5cf6;
            --purple-light: #ede9fe;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0,0,0,0.25);
            --radius: 16px;
            --radius-lg: 24px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        
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
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(167, 139, 250, 0.1));
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
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.3; }
            90% { opacity: 0.3; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
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
            box-shadow: var(--shadow);
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

        /* MAIN SECTION */
        .section {
            padding: 140px 6% 80px;
            max-width: 1200px;
            margin: auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 48px;
            animation: slideUp 0.8s ease forwards;
        }

        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
            from { opacity: 0; transform: translateY(30px); }
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--purple-light), var(--accent-light));
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            color: var(--purple);
            margin-bottom: 20px;
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
            max-width: 600px;
            margin: 0 auto;
        }

        /* SEARCH & FILTER */
        .filter-container {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--border);
            margin-bottom: 40px;
            animation: slideUp 0.8s ease 0.1s forwards;
            opacity: 0;
            transform: translateY(30px);
            animation-fill-mode: forwards;
        }

        .filter-wrapper {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            border: 2px solid var(--border);
            border-radius: 14px;
            font-family: inherit;
            font-size: 15px;
            background: var(--light);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 16px;
        }

        .filter-select {
            min-width: 200px;
            padding: 16px 44px 16px 20px;
            border: 2px solid var(--border);
            border-radius: 14px;
            font-family: inherit;
            font-size: 15px;
            background: var(--light);
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
        }

        .btn-cari {
            padding: 16px 28px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cari:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
        }

        /* MEDICATION GRID */
        .medication-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            animation: slideUp 0.8s ease 0.2s forwards;
            opacity: 0;
            transform: translateY(30px);
            animation-fill-mode: forwards;
        }

        .med-card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .med-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .med-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .med-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary);
            flex-shrink: 0;
        }

        .med-info {
            flex: 1;
        }

        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .category-badge.Demam { background: var(--danger-light); color: var(--danger); }
        .category-badge.Nyeri { background: var(--purple-light); color: var(--purple); }
        .category-badge.Pencernaan { background: var(--success-light); color: var(--success); }
        .category-badge.Luka { background: var(--accent-light); color: var(--accent); }
        .category-badge.Vitamin { background: var(--warning-light); color: var(--warning); }

        .med-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .med-stok {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
        }

        .stok-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .stok-badge.tersedia { background: var(--success-light); color: var(--success); }
        .stok-badge.hampir-habis { background: var(--warning-light); color: var(--warning); }
        .stok-badge.habis { background: var(--danger-light); color: var(--danger); }

        .med-desc {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
            margin: 16px 0;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .med-type {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .btn-stock {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, var(--primary), #ef4444);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-stock:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.35);
        }

        /* ALERT NOTE */
        .alert-note {
            margin-top: 48px;
            background: linear-gradient(135deg, var(--warning-light), white);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: var(--radius);
            padding: 24px 28px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            animation: slideUp 0.8s ease 0.3s forwards;
            opacity: 0;
            transform: translateY(30px);
            animation-fill-mode: forwards;
        }

        .alert-icon {
            width: 48px;
            height: 48px;
            background: var(--warning);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }

        .alert-content h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .alert-content p {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
        }

        /* FOOTER */
        footer {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            margin-top: 80px;
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
            font-size: 16px;
        }

        footer h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
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

        /* RESPONSIVE */
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
                min-width: 180px;
                border: 1px solid var(--border);
            }

            .nav-menu.active { display: flex; }

            .section {
                padding: 120px 5% 60px;
            }

            .filter-wrapper {
                flex-direction: column;
            }

            .medication-grid {
                grid-template-columns: 1fr;
            }

            .alert-note {
                flex-direction: column;
                text-align: center;
                align-items: center;
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
            <a href="../index.php" class="logo">
                <div class="logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                UKS SmartCare
            </a>
            <button class="menu-btn" onclick="toggleMenu()" aria-label="Toggle menu">
                <i class="fa-solid fa-bars" id="menuIcon"></i>
            </button>
            <nav class="nav-menu" id="navMenu">
                <a href="../index.php#home">Beranda</a>
                <a href="../index.php#fitur">Fitur</a>
                <a href="../index.php#jadwal">Jadwal</a>
                <a href="../index.php#kontak">Tentang</a>
            </nav>
        </div>
    </header>

    <main class="section">
        <div class="section-header">
            <div class="badge">
                <i class="fa-solid fa-prescription-bottle-medical"></i>
                Panduan Obat
            </div>
            <h1 class="section-title">Saran & Rekomendasi Obat</h1>
            <p class="section-sub">Daftar Obat di UKS Sesuai panduan kegunaan dan fungsi obat.</p>
        </div>

        <div class="filter-container">
            <form method="GET" action="" class="filter-wrapper">
                <div class="search-box">
                    <i class="fa-solid fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input" placeholder="Cari keluhan atau nama obat..." value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
                <select name="category" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?= $categoryFilter == 'all' ? 'selected' : '' ?>>Semua Kategori</option>
                    <option value="Demam" <?= $categoryFilter == 'Demam' ? 'selected' : '' ?>>Demam</option>
                    <option value="Nyeri" <?= $categoryFilter == 'Nyeri' ? 'selected' : '' ?>>Nyeri</option>
                    <option value="Pencernaan" <?= $categoryFilter == 'Pencernaan' ? 'selected' : '' ?>>Pencernaan</option>
                    <option value="Luka" <?= $categoryFilter == 'Luka' ? 'selected' : '' ?>>Luka</option>
                    <option value="Vitamin" <?= $categoryFilter == 'Vitamin' ? 'selected' : '' ?>>Vitamin</option>
                </select>
                <button type="submit" class="btn-cari"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
            </form>
        </div>

        <div class="medication-grid" id="medGrid">
            <?php if (empty($medications)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px;">
                    <i class="fa-solid fa-pills" style="font-size: 48px; color: var(--muted); margin-bottom: 16px;"></i>
                    <p style="color: var(--muted);">Tidak ada data obat yang sesuai dengan filter.</p>
                </div>
            <?php else: ?>
                <?php foreach ($medications as $med): 
                    // Tentukan status stok
                    if ($med['stok'] > 10) {
                        $stokStatus = 'tersedia';
                        $stokText = 'Tersedia';
                    } elseif ($med['stok'] >= 1) {
                        $stokStatus = 'hampir-habis';
                        $stokText = 'Hampir Habis';
                    } else {
                        $stokStatus = 'habis';
                        $stokText = 'Habis';
                    }
                    
                    // Gunakan saran_kategori jika ada, fallback ke 'Umum'
                    $kategoriSaran = !empty($med['saran_kategori']) ? $med['saran_kategori'] : 'Umum';
                    $jenisObat = !empty($med['saran_jenis']) ? $med['saran_jenis'] : 'Non-Resep';
                    $penjelasan = !empty($med['penjelasan']) ? $med['penjelasan'] : 'Belum ada informasi saran untuk obat ini. Silakan konsultasikan dengan petugas UKS.';
                ?>
                    <article class="med-card">
                        <div class="card-header">
                            <div class="med-icon">
                                <i class="fa-solid fa-pills"></i>
                            </div>
                            <div class="med-info">
                                <span class="category-badge <?= $kategoriSaran ?>"><?= $kategoriSaran ?></span>
                                <h3 class="med-name"><?= htmlspecialchars($med['nama_obat']) ?></h3>
                                <div class="med-stok">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                    <span>Stok: <?= $med['stok'] ?></span>
                                    <span class="stok-badge <?= $stokStatus ?>"><?= $stokText ?></span>
                                </div>
                            </div>
                        </div>
                        <p class="med-desc"><?= htmlspecialchars($penjelasan) ?></p>
                        <div class="card-footer">
                            <div class="med-type">
                                <i class="fa-solid fa-capsules"></i>
                                <?= htmlspecialchars($jenisObat) ?>
                            </div>
                            <a href="stokobat.php?obat=<?= urlencode($med['nama_obat']) ?>" class="btn-stock">
                                <i class="fa-solid fa-box"></i> Lihat Stok
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="alert-note">
            <div class="alert-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="alert-content">
                <h4>Perhatian Penting</h4>
                <p>Informasi ini hanya sebagai panduan awal. Pemberian obat tetap dilakukan oleh petugas UKS sesuai dengan prosedur dan kondisi siswa. Konsultasikan keluhan Anda kepada petugas UKS sebelum mengonsumsi obat.</p>
            </div>
        </div>
    </main>

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
        function createParticles() {
            const container = document.getElementById('particles');
            if (!container) return;
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                particle.style.width = (2 + Math.random() * 4) + 'px';
                particle.style.height = particle.style.width;
                container.appendChild(particle);
            }
        }

        function toggleMenu() {
            const navMenu = document.getElementById('navMenu');
            const menuIcon = document.getElementById('menuIcon');
            navMenu.classList.toggle('active');
            menuIcon.className = navMenu.classList.contains('active') ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        }

        function handleScroll() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }

        document.addEventListener('click', function(e) {
            const navMenu = document.getElementById('navMenu');
            const menuBtn = document.querySelector('.menu-btn');
            if (navMenu && menuBtn && !navMenu.contains(e.target) && !menuBtn.contains(e.target)) {
                navMenu.classList.remove('active');
                document.getElementById('menuIcon').className = 'fa-solid fa-bars';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            window.addEventListener('scroll', handleScroll);
        });
    </script>
</body>
</html>