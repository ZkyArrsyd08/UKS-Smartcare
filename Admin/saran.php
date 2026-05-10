<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: loginadmin.php");
    exit;
}

 $id_admin_sekarang = $_SESSION['id_admin'];

 $query_self = mysqli_query($conn, "SELECT * FROM admin WHERE id_admin = '$id_admin_sekarang'");
 $admin = mysqli_fetch_assoc($query_self);

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit;
}

// UPDATE / INSERT SARAN (ke tabel saran_obat)
if (isset($_POST['update_saran'])) {
    $id_obat = (int)$_POST['id_obat'];
    $penjelasan = mysqli_real_escape_string($conn, $_POST['penjelasan']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);
    
    $check = mysqli_query($conn, "SELECT id_saran FROM saran_obat WHERE id_obat = $id_obat");
    
    if (mysqli_num_rows($check) > 0) {
        $query = "UPDATE saran_obat SET penjelasan='$penjelasan', kategori='$kategori', jenis='$jenis' WHERE id_obat=$id_obat";
    } else {
        $query = "INSERT INTO saran_obat (id_obat, penjelasan, kategori, jenis) VALUES ($id_obat, '$penjelasan', '$kategori', '$jenis')";
    }
    mysqli_query($conn, $query);
    header("Location: saran.php");
    exit;
}

// DELETE SARAN
if (isset($_GET['delete'])) {
    $id_saran = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM saran_obat WHERE id_saran = $id_saran");
    header("Location: saran.php");
    exit;
}

// AMBIL DATA OBAT + SARAN (JOIN)
 $query = "SELECT o.*, s.id_saran, s.penjelasan, s.kategori as saran_kategori, s.jenis as saran_jenis 
          FROM obat o 
          LEFT JOIN saran_obat s ON o.id_obat = s.id_obat 
          ORDER BY o.nama_obat ASC";
 $result = mysqli_query($conn, $query);
 $data_obat = mysqli_fetch_all($result, MYSQLI_ASSOC);

// HITUNG STATISTIK
 $total_obat = count($data_obat);
 $total_saran = count(array_filter($data_obat, fn($o) => !empty($o['penjelasan'])));
 $kategori_populer = array_count_values(array_column($data_obat, 'kategori'));
arsort($kategori_populer);
 $top_kategori = key($kategori_populer) ?: 'Belum Ada';

// DATA EDIT
 $edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT o.*, s.id_saran, s.penjelasan, s.kategori as saran_kategori, s.jenis as saran_jenis 
                   FROM obat o LEFT JOIN saran_obat s ON o.id_obat = s.id_obat WHERE o.id_obat = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_data = mysqli_fetch_assoc($edit_result);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Saran Obat | Admin UKS SmartCare</title>
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

        .profile-dropdown.active { display: block; }
        .profile-dropdown ul li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .profile-dropdown ul li a:hover { background: var(--primary-light); color: var(--primary); }

        /* MAIN */
        .main-section {
            padding: 140px 6% 80px;
            max-width: 1200px;
            margin: auto;
            animation: slideUp 0.8s ease forwards;
        }
        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
            from { opacity: 0; transform: translateY(30px); }
        }

        /* CONTENT */
        .content-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .content-header h1 {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .content-header p {
            font-size: 16px;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto;
        }

        /* SEARCH AND FILTER */
        .search-filter-container {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .search-box {
            flex: 1;
            min-width: 280px;
            max-width: 500px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            border: 2px solid var(--border);
            border-radius: var(--radius-lg);
            font-size: 14px;
            font-family: inherit;
            background: var(--card);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
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
            min-width: 180px;
            padding: 14px 20px;
            border: 2px solid var(--border);
            border-radius: var(--radius-lg);
            font-size: 14px;
            font-family: inherit;
            background: var(--card);
            color: var(--dark);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
        }

        .add-button {
            margin-left: auto;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .add-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.3);
        }

        /* CARDS GRID */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .drug-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .drug-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .drug-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .drug-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .drug-icon i {
            font-size: 24px;
            color: var(--primary);
        }

        .drug-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .drug-description {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .drug-badges {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-kategori {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-jenis {
            background: #f3f4f6;
            color: #4b5563;
        }

        .drug-actions {
            display: flex;
            gap: 12px;
        }

        .btn-edit {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            flex: 1;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            flex: 1;
        }

        .btn-edit:hover, .btn-delete:hover { transform: translateY(-2px); filter: brightness(0.95); }

        /* MODAL */
        .modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-bg.active { display: flex; }
        .modal {
            background: var(--card);
            padding: 32px;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 550px;
            position: relative;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-xl);
        }
        .modal h3 { font-size: 24px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .close { position: absolute; top: 18px; right: 18px; font-size: 28px; cursor: pointer; color: var(--muted); }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-size: 14px; font-weight: 600; color: var(--muted); display: block; margin-bottom: 8px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
        }
        .save-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
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
        
        @media (max-width: 768px) { 
            .navbar { padding: 12px 5%; } 
            .welcome-text { display: none; } 
            .main-section { padding: 120px 5% 60px; } 
            .search-filter-container { flex-direction: column; }
            .add-button { margin-left: 0; width: 100%; }
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
        <a href="halamanadmin.php" class="logo"><div class="logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>UKS SmartCare</a>
        <div class="profile-container" id="profileContainer">
            <div class="welcome-text">Halo, Admin!<br><strong><?= htmlspecialchars($admin['nama_lengkap']) ?></strong></div>
            <div class="profile-icon"><img src="../assets/img/<?= $admin['foto'] ?: 'default.png' ?>" alt="Profile"></div>
            <div class="profile-dropdown" id="profileDropdown">
                <ul><li><a href="halamanadmin.php"><i class="fa-solid fa-home"></i> Beranda</a></li>
                    <li><a href="terimaobat.php"><i class="fa-solid fa-file-prescription"></i> Terima Obat</a></li>
                    <li><a href="feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a></li>
                    <li><a href="jadwal.php"><i class="fa-solid fa-calendar"></i> Jadwal</a></li>
                    <li><a href="stok.php"><i class="fa-solid fa-pills"></i> Stok Obat</a></li>
                    <li><a href="saran.php"><i class="fa-solid fa-comment-dots"></i> Saran Obat</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<main class="main-section">
    <div class="content-header">
        <h1>Data Saran Obat</h1>
        <p>Kelola saran obat dan informasi kesehatan untuk siswa</p>
    </div>

    <div class="search-filter-container">
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" placeholder="Cari nama obat atau deskripsi...">
        </div>
        <select class="filter-select">
            <option>Semua Kategori</option>
            <option>Umum</option>
            <option>Demam</option>
            <option>Pencernaan</option>
            <option>Luka</option>
            <option>Nyeri</option>
            <option>Vitamin</option>
        </select>
    </div>

    <div class="cards-grid">
        <?php if (empty($data_obat)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 60px;">
                <i class="fa-solid fa-pills" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                <p>Belum ada data obat</p>
            </div>
        <?php else: foreach($data_obat as $obat):
            $kategoriColors = [
                'Umum' => 'bg-blue-100 text-blue-800',
                'Demam' => 'bg-red-100 text-red-800',
                'Pencernaan' => 'bg-green-100 text-green-800',
                'Luka' => 'bg-yellow-100 text-yellow-800',
                'Nyeri' => 'bg-purple-100 text-purple-800',
                'Vitamin' => 'bg-orange-100 text-orange-800'
            ];
            
            $jenisColors = [
                'Non-Resep' => 'bg-gray-100 text-gray-800',
                'Resep' => 'bg-blue-100 text-blue-800',
                'Suplemen' => 'bg-green-100 text-green-800',
                'Cairan' => 'bg-cyan-100 text-cyan-800'
            ];
        ?>
            <div class="drug-card">
                <div class="drug-icon">
                    <i class="fa-solid fa-pills"></i>
                </div>
                <h3 class="drug-name"><?= htmlspecialchars($obat['nama_obat']) ?></h3>
                <p class="drug-description">
                    <?= htmlspecialchars(substr($obat['penjelasan'] ?? 'Belum ada informasi', 0, 100)) ?>
                    <?= strlen($obat['penjelasan'] ?? '') > 100 ? '...' : '' ?>
                </p>
                <div class="drug-badges">
                    <?php if(!empty($obat['saran_kategori'])): ?>
                        <span class="badge badge-kategori">
                            <?= htmlspecialchars($obat['saran_kategori']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if(!empty($obat['saran_jenis'])): ?>
                        <span class="badge badge-jenis">
                            <?= htmlspecialchars($obat['saran_jenis']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="drug-actions">
                    <a href="?edit=<?= $obat['id_obat'] ?>" class="btn-edit">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <?php if(!empty($obat['id_saran'])): ?>
                        <a href="?delete=<?= $obat['id_saran'] ?>" class="btn-delete" onclick="return confirm('Yakin hapus saran?')">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</main>

<div class="modal-bg" id="saranModal">
    <div class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3><i class="fa-solid fa-notes-medical"></i> Kelola Saran Obat</h3>
        <p style="margin-bottom:20px; color:var(--muted);">Isi informasi dan saran penggunaan untuk siswa.</p>
        <form method="POST">
            <?php if(isset($edit_data)): ?>
                <input type="hidden" name="id_obat" value="<?= $edit_data['id_obat'] ?>">
                <div class="form-group"><label>Nama Obat</label><input type="text" value="<?= htmlspecialchars($edit_data['nama_obat']) ?>" disabled style="background:#f1f5f9;"></div>
                <div class="form-group"><label>Saran / Informasi Obat</label><textarea name="penjelasan" rows="5" placeholder="Contoh: Paracetamol untuk demam dan nyeri..."><?= htmlspecialchars($edit_data['penjelasan'] ?? '') ?></textarea></div>
                <div class="form-group"><label>Kategori Saran</label>
                    <select name="kategori">
                        <option <?= ($edit_data['saran_kategori']??'')=='Umum'?'selected':'' ?>>Umum</option>
                        <option <?= ($edit_data['saran_kategori']??'')=='Demam'?'selected':'' ?>>Demam</option>
                        <option <?= ($edit_data['saran_kategori']??'')=='Pencernaan'?'selected':'' ?>>Pencernaan</option>
                        <option <?= ($edit_data['saran_kategori']??'')=='Luka'?'selected':'' ?>>Luka</option>
                        <option <?= ($edit_data['saran_kategori']??'')=='Nyeri'?'selected':'' ?>>Nyeri</option>
                        <option <?= ($edit_data['saran_kategori']??'')=='Vitamin'?'selected':'' ?>>Vitamin</option>
                    </select>
                </div>
                <div class="form-group"><label>Jenis Obat</label>
                    <select name="jenis">
                        <option <?= ($edit_data['saran_jenis']??'')=='Non-Resep'?'selected':'' ?>>Non-Resep</option>
                        <option <?= ($edit_data['saran_jenis']??'')=='Resep'?'selected':'' ?>>Resep</option>
                        <option <?= ($edit_data['saran_jenis']??'')=='Suplemen'?'selected':'' ?>>Suplemen</option>
                        <option <?= ($edit_data['saran_jenis']??'')=='Cairan'?'selected':'' ?>>Cairan</option>
                    </select>
                </div>
                <button type="submit" name="update_saran" value="1" class="save-btn"><i class="fa-solid fa-save"></i> Simpan Saran</button>
            <?php endif; ?>
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
    const profileContainer = document.getElementById('profileContainer');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if(profileContainer) {
        profileContainer.addEventListener('click', (e) => { 
            e.stopPropagation(); 
            profileDropdown.classList.toggle('active'); 
        });
    }
    
    document.addEventListener('click', () => profileDropdown?.classList.remove('active'));
    
    function openModal() { 
        document.getElementById('saranModal').classList.add('active'); 
    }
    
    function closeModal() { 
        document.getElementById('saranModal').classList.remove('active'); 
    }
    
    <?php if(isset($_GET['edit'])): ?>
        document.addEventListener('DOMContentLoaded', () => openModal());
    <?php endif; ?>
    
    for(let i = 0; i < 25; i++) { 
        let p = document.createElement('div'); 
        p.className = 'particle'; 
        p.style.left = Math.random() * 100 + '%'; 
        p.style.animationDelay = Math.random() * 15 + 's'; 
        document.getElementById('particles')?.appendChild(p); 
    }
    
    window.addEventListener('scroll', () => {
        document.getElementById('header')?.classList.toggle('scrolled', window.scrollY > 50);
    });
</script>
</body>
</html>