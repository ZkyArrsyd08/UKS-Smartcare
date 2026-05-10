<?php
session_start();

// Inisialisasi session dan cookie untuk pengguna
if (!isset($_SESSION['user_token'])) {
    $_SESSION['user_token'] = bin2hex(random_bytes(16));
    
    setcookie("user_token", $_SESSION['user_token'], [
        'expires' => time() + (86400 * 7),
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

include '../koneksi.php';

// ========== BACA FILE used_week.json DARI ADMIN ==========
$used_week_file = '../admin/used_week.json'; // ← PATH INI WAJIB BENER

function getUsedWeekFromFile() {
    global $used_week_file;
    if (file_exists($used_week_file)) {
        $data = json_decode(file_get_contents($used_week_file), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

$used_week = getUsedWeekFromFile();

// ========== LOGIKA RESET MINGGUAN (SETIAP SENIN) ==========
$cookie_minggu_ke = 'minggu_ke_smartcare';
$cookie_tgl_mulai = 'tgl_mulai_minggu_smartcare';

if (!isset($_COOKIE[$cookie_minggu_ke]) || !isset($_COOKIE[$cookie_tgl_mulai])) {
    $minggu_ke = 1;
    $tgl_mulai_minggu = date('Y-m-d', strtotime('monday this week'));
    setcookie($cookie_minggu_ke, $minggu_ke, time() + (86400 * 365), "/");
    setcookie($cookie_tgl_mulai, $tgl_mulai_minggu, time() + (86400 * 365), "/");
} else {
    $minggu_ke = (int)$_COOKIE[$cookie_minggu_ke];
    $tgl_mulai_minggu = $_COOKIE[$cookie_tgl_mulai];
    
    $senin_berikutnya = date('Y-m-d', strtotime('next monday', strtotime($tgl_mulai_minggu)));
    $hari_ini = date('Y-m-d');
    
    if ($hari_ini >= $senin_berikutnya) {
        $minggu_ke++;
        $tgl_mulai_minggu = $senin_berikutnya;
        
        setcookie($cookie_minggu_ke, $minggu_ke, time() + (86400 * 365), "/");
        setcookie($cookie_tgl_mulai, $tgl_mulai_minggu, time() + (86400 * 365), "/");
        setcookie("week_reset_info", "🔄 Minggu ke-$minggu_ke: Stok tablet/sachet/lembar/gulung/swabs direset ke 20", time() + (86400 * 7), "/");
        
        // RESET FILE used_week.json juga
        file_put_contents($used_week_file, json_encode([]));
        $used_week = [];
    }
}

// Query Ambil Data Obat
$sql = "SELECT * FROM obat ORDER BY id_obat DESC";
$result = mysqli_query($conn, $sql);

$total_obat = 0;
$tersedia_count = 0;
$hampir_habis_count = 0;
$habis_count = 0;
$obat_data = [];

// Kategori yang perlu di-limit (max 20 per minggu)
$kategori_limit = ['tablet', 'sachet', 'lembar', 'gulung', 'swabs'];
$kategori_permanen = ['tube', 'botol', 'can'];

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $kategori = strtolower($row['kategori']);
        $stok_asli = (int)$row['stok'];
        $id_obat = $row['id_obat'];
        
        // Cek apakah perlu limit
        $perlu_limit = false;
        foreach ($kategori_limit as $kat) {
            if (strpos($kategori, $kat) !== false) {
                $perlu_limit = true;
                break;
            }
        }
        
        // Cek apakah permanen
        $is_permanen = false;
        foreach ($kategori_permanen as $kat) {
            if (strpos($kategori, $kat) !== false) {
                $is_permanen = true;
                break;
            }
        }
        
        // LOGIKA STOK TAMPIL (BACA DARI FILE JSON)
        if ($perlu_limit) {
            $sudah_dipakai = isset($used_week[$id_obat]) ? (int)$used_week[$id_obat] : 0;
            $sisa_limit = max(0, 20 - $sudah_dipakai);
            $stok_tampil = min($sisa_limit, $stok_asli);
            $stok_tersembunyi = max(0, $stok_asli - $sisa_limit);
        } elseif ($is_permanen) {
            $stok_tampil = $stok_asli;
            $stok_tersembunyi = 0;
        } else {
            $stok_tampil = $stok_asli;
            $stok_tersembunyi = 0;
        }
        
        $row['stok_tampil'] = $stok_tampil;
        $row['stok_asli'] = $stok_asli;
        $row['stok_tersembunyi'] = $stok_tersembunyi;
        $row['perlu_limit'] = $perlu_limit;
        $row['is_permanen'] = $is_permanen;
        $obat_data[] = $row;
        
        $total_obat++;
        
        if($stok_tampil > 10) {
            $tersedia_count++;
        } elseif($stok_tampil >= 1 && $stok_tampil <= 10) {
            $hampir_habis_count++;
        } else {
            $habis_count++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Obat | UKS SmartCare</title>
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
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
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
            animation-delay: 0s;
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

        .grid-pattern {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(220, 38, 38, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(220, 38, 38, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
        }

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
            transition: transform 0.3s ease;
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
            transition: all 0.3s ease;
        }

        .menu-btn:hover {
            background: var(--primary);
            color: white;
        }

        .section {
            padding: 140px 6% 80px;
            max-width: 1200px;
            margin: auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 48px;
            opacity: 0;
            transform: translateY(30px);
            animation: slideUp 0.8s ease forwards;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
            border: 1px solid rgba(220, 38, 38, 0.1);
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

        .info-reset {
            background: linear-gradient(135deg, var(--accent-light), #e0f2fe);
            border-left: 4px solid var(--accent);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 13px;
            color: var(--accent);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            text-align: center;
        }

        .info-reset i {
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
            opacity: 0;
            transform: translateY(30px);
            animation: slideUp 0.8s ease 0.1s forwards;
        }

        .stat-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 24px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .stat-card.total::before { background: var(--accent); }
        .stat-card.available::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.danger::before { background: var(--danger); }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }

        .stat-card.total .stat-icon { background: var(--accent-light); color: var(--accent); }
        .stat-card.available .stat-icon { background: var(--success-light); color: var(--success); }
        .stat-card.warning .stat-icon { background: var(--warning-light); color: var(--warning); }
        .stat-card.danger .stat-icon { background: var(--danger-light); color: var(--danger); }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--muted);
            font-weight: 500;
        }

        .search-container {
    margin-bottom: 32px;
    opacity: 0;
    transform: translateY(30px);
    animation: slideUp 0.8s ease 0.15s forwards;
}

.search-wrapper {
    position: relative;
    max-width: 500px;
    margin: 0 auto;
}

.search-input {
    width: 100%;
    padding: 16px 24px 16px 52px;
    border: 2px solid var(--border);
    border-radius: 60px;
    font-family: inherit;
    font-size: 15px;
    background: var(--card);
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
}

.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 16px;
    pointer-events: none;
}

        .search-box {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            opacity: 0;
            transform: translateY(30px);
            animation: slideUp 0.8s ease 0.2s forwards;
        }

        .medicine-card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .medicine-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(220, 38, 38, 0.2);
        }

        .medicine-card.hidden {
    display: none;
}

.search-empty-message {
    grid-column: 1 / -1;
}

        .card-top {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .medicine-icon {
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

        .medicine-info {
            flex: 1;
            min-width: 0;
        }

        .medicine-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .badge-cadangan {
            font-size: 10px;
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 20px;
            color: #475569;
            font-weight: 500;
            white-space: nowrap;
        }

        .medicine-category {
            font-size: 13px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .medicine-category i {
            font-size: 11px;
        }

        .stock-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: var(--light);
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .stock-label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .stock-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
        }

        .stock-unit {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
        }

        .stock-note {
            font-size: 11px;
            color: var(--muted);
            margin-top: -8px;
            margin-bottom: 12px;
            text-align: right;
            padding-right: 4px;
        }

        .status-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            width: 100%;
        }

        .status-badge i {
            font-size: 12px;
        }

        .status-available {
            background: var(--success-light);
            color: var(--success);
        }

        .status-low {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-out {
            background: var(--danger-light);
            color: var(--danger);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: var(--muted);
        }

        .empty-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .empty-text {
            font-size: 14px;
            color: var(--muted);
        }

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

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .menu-btn {
                width: 44px;
                height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
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

            .nav-menu a {
                padding: 12px 16px;
                border-radius: 10px;
            }

            .section {
                padding: 120px 5% 60px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 28px;
            }

            .inventory-grid {
                grid-template-columns: 1fr;
            }

            .footer-wrap {
                padding: 48px 5% 24px;
                gap: 32px;
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
        <div class="grid-pattern"></div>
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
                <i class="fa-solid fa-pills"></i>
                Inventaris Obat
            </div>
            <h1 class="section-title">Stok Obat UKS</h1>
            <p class="section-sub">Daftar Obat Yang Tersedia di UKS</p>
        </div>

        <?php if (isset($_COOKIE['week_reset_info'])): ?>
        <div class="info-reset">
            <i class="fa-solid fa-calendar-week"></i>
            <?= htmlspecialchars($_COOKIE['week_reset_info']); ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="stat-value"><?= $total_obat; ?></div>
                <div class="stat-label">Total Jenis Obat</div>
            </div>
            <div class="stat-card available">
                <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
                <div class="stat-value"><?= $tersedia_count; ?></div>
                <div class="stat-label">Stok Tersedia (>10)</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fa-solid fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?= $hampir_habis_count; ?></div>
                <div class="stat-label">Hampir Habis (1-10)</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fa-solid fa-times-circle"></i></div>
                <div class="stat-value"><?= $habis_count; ?></div>
                <div class="stat-label">Stok Habis (0)</div>
            </div>
        </div>

        <div class="search-container">
    <div class="search-wrapper">
        <i class="fa-solid fa-search search-icon"></i>
        <input type="text" id="searchInput" class="search-input" placeholder="Cari nama obat atau kategori..." autocomplete="off">
    </div>
</div>

        <div class="inventory-grid" id="inventoryGrid">
            <?php 
            if (count($obat_data) > 0) {
                foreach ($obat_data as $row) {
                    $stok_asli = $row['stok_asli'];
                    $stok_tampil = $row['stok_tampil'];
                    $stok_tersembunyi = $row['stok_tersembunyi'];
                    $kategori = strtolower($row['kategori']);
                    $perlu_limit = $row['perlu_limit'];
                    $is_permanen = $row['is_permanen'];
                    
                    if($stok_tampil > 10) {
                        $status_class = "status-available";
                        $status_text  = "Tersedia";
                        $status_icon  = "fa-check-circle";
                    } elseif($stok_tampil >= 1 && $stok_tampil <= 10) {
                        $status_class = "status-low";
                        $status_text  = "Hampir Habis";
                        $status_icon  = "fa-exclamation-triangle";
                    } else {
                        $status_class = "status-out";
                        $status_text  = "Habis";
                        $status_icon  = "fa-times-circle";
                    }

                    $icon_class = "fa-pills";
                    $unit_text = "unit";
                    
                    if (strpos($kategori, 'tablet') !== false) {
                        $icon_class = "fa-tablets";
                        $unit_text = "tablet";
                    } elseif (strpos($kategori, 'sachet') !== false) {
                        $icon_class = "fa-envelope";
                        $unit_text = "sachet";
                    } elseif (strpos($kategori, 'lembar') !== false) {
                        $icon_class = "fa-receipt";
                        $unit_text = "lembar";
                    } elseif (strpos($kategori, 'gulung') !== false) {
                        $icon_class = "fa-scroll";
                        $unit_text = "gulung";
                    } elseif (strpos($kategori, 'swabs') !== false) {
                        $icon_class = "fa-hand-holding-heart";
                        $unit_text = "swabs";
                    } elseif (strpos($kategori, 'tube') !== false) {
                        $icon_class = "fa-pump-medical";
                        $unit_text = "tube";
                    } elseif (strpos($kategori, 'botol') !== false) {
                        $icon_class = "fa-bottle-droplet";
                        $unit_text = "botol";
                    } elseif (strpos($kategori, 'can') !== false) {
                        $icon_class = "fa-box";
                        $unit_text = "can";
                    } elseif (strpos($kategori, 'sirup') !== false || strpos($kategori, 'cair') !== false) {
                        $icon_class = "fa-bottle-droplet";
                        $unit_text = "botol";
                    } elseif (strpos($kategori, 'kapsul') !== false) {
                        $icon_class = "fa-capsules";
                        $unit_text = "kapsul";
                    } elseif (strpos($kategori, 'salep') !== false) {
                        $icon_class = "fa-pump-medical";
                        $unit_text = "tube";
                    } elseif (strpos($kategori, 'vitamin') !== false) {
                        $icon_class = "fa-leaf";
                        $unit_text = "tablet";
                    }
                    
                    if ($perlu_limit) {
                        $stock_label = "Sisa Stok Minggu Ini";
                    } elseif ($is_permanen) {
                        $stock_label = "Stok Tersedia (Permanen)";
                    } else {
                        $stock_label = "Stok Tersedia";
                    }
            ?>
                <div class="medicine-card" data-name="<?= strtolower($row['nama_obat']); ?>" data-category="<?= strtolower($row['kategori']); ?>">
                    <div class="card-top">
                        <div class="medicine-icon">
                            <i class="fa-solid <?= $icon_class; ?>"></i>
                        </div>
                        <div class="medicine-info">
                            <h3 class="medicine-name">
                                <?= htmlspecialchars($row['nama_obat']); ?>
                            </h3>
                            <p class="medicine-category">
                                <i class="fa-solid fa-tag"></i>
                                <?= htmlspecialchars($row['kategori']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="stock-info">
                        <span class="stock-label"><?= $stock_label; ?></span>
                        <div>
                            <span class="stock-value"><?= $stok_tampil; ?></span>
                            <span class="stock-unit"><?= $unit_text; ?></span>
                        </div>
                    </div>
                    
                    <?php if ($stok_tersembunyi > 0): ?>
                    <div class="stock-note">
                        <i class="fa-solid fa-warehouse"></i> Total stok: <?= $stok_asli; ?> <?= $unit_text; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="status-badge <?= $status_class; ?>">
                        <i class="fa-solid <?= $status_icon; ?>"></i>
                        <?= $status_text; ?>
                    </div>
                </div>
            <?php 
                } 
            } else {
            ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-box-open"></i></div>
                    <h3 class="empty-title">Belum Ada Data Obat</h3>
                    <p class="empty-text">Data obat akan ditampilkan setelah petugas menambahkan ke sistem.</p>
                </div>
            <?php 
            }
            ?>
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

        function searchMedicine() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.medicine-card');
    let hasResults = false;
    
    cards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        const category = card.getAttribute('data-category') || '';
        
        if (searchTerm === '' || name.includes(searchTerm) || category.includes(searchTerm)) {
            card.classList.remove('hidden');
            hasResults = true;
        } else {
            card.classList.add('hidden');
        }
    });
    
    // Tampilkan pesan jika tidak ada hasil
    let emptyMsg = document.querySelector('.search-empty-message');
    if (!hasResults && searchTerm !== '') {
        if (!emptyMsg) {
            const grid = document.getElementById('inventoryGrid');
            emptyMsg = document.createElement('div');
            emptyMsg.className = 'search-empty-message';
            emptyMsg.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-search"></i></div>
                    <h3 class="empty-title">Obat Tidak Ditemukan</h3>
                    <p class="empty-text">Tidak ada obat dengan nama "${searchTerm}"</p>
                </div>
            `;
            grid.appendChild(emptyMsg);
        } else {
            emptyMsg.style.display = 'block';
        }
    } else if (emptyMsg) {
        emptyMsg.style.display = 'none';
    }
}

// Event listener untuk search input
document.addEventListener('DOMContentLoaded', function() {
    createParticles();
    window.addEventListener('scroll', handleScroll);
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', searchMedicine);
        searchInput.addEventListener('input', searchMedicine);
    }
});

        function handleScroll() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) header.classList.add('scrolled');
            else header.classList.remove('scrolled');
        }

        document.addEventListener('click', function(e) {
            const navMenu = document.getElementById('navMenu');
            const menuBtn = document.querySelector('.menu-btn');
            if (navMenu && menuBtn && !navMenu.contains(e.target) && !menuBtn.contains(e.target)) {
                navMenu.classList.remove('active');
                const menuIcon = document.getElementById('menuIcon');
                if (menuIcon) menuIcon.className = 'fa-solid fa-bars';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            window.addEventListener('scroll', handleScroll);
        });
    </script>
</body>
</html>