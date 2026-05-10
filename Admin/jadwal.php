<?php
session_start();
include '../koneksi.php';

// CEK APAKAH SUDAH LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: loginadmin.php");
    exit;
}

// AMBIL ID DARI SESSION
$id_admin_sekarang = $_SESSION['id_admin'];

// Cek koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// PROSES TAMBAH/UPDATE JADWAL SENDIRI
if (isset($_POST['simpan_jadwal'])) {
    $minggu_ke = mysqli_real_escape_string($conn, $_POST['minggu_ke']);
    $kode_jaga = mysqli_real_escape_string($conn, $_POST['kode_jaga']);
    
    $cek = mysqli_query($conn, "SELECT * FROM jadwal WHERE id_admin = '$id_admin_sekarang'");
    
    if(mysqli_num_rows($cek) > 0) {
        $sql = "UPDATE jadwal SET minggu_ke='$minggu_ke', kode_jaga='$kode_jaga' WHERE id_admin='$id_admin_sekarang'";
    } else {
        $sql = "INSERT INTO jadwal (id_admin, minggu_ke, kode_jaga) VALUES ('$id_admin_sekarang', '$minggu_ke', '$kode_jaga')";
    }
    
    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Jadwal berhasil diperbarui!'); window.location='jadwal.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan jadwal: " . mysqli_error($conn) . "');</script>";
    }
}

// AMBIL DATA ADMIN YANG LOGIN
$query_self = mysqli_query($conn, "SELECT a.*, j.minggu_ke, j.kode_jaga 
                                   FROM admin a
                                   LEFT JOIN jadwal j ON a.id_admin = j.id_admin
                                   WHERE a.id_admin = '$id_admin_sekarang'");
$admin = mysqli_fetch_assoc($query_self);

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit;
}

// AMBIL DATA SEMUA ADMIN (untuk ditampilkan)
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nama_lengkap';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

$sort_query = "ORDER BY a.$sort $order";

if(!empty($search)) {
    $query_semua = mysqli_query($conn, "SELECT a.id_admin, a.nama_lengkap, a.kelas, a.foto, 
                                               j.minggu_ke, j.kode_jaga
                                        FROM admin a
                                        LEFT JOIN jadwal j ON a.id_admin = j.id_admin
                                        WHERE a.nama_lengkap LIKE '%$search%' 
                                        $sort_query");
} else {
    $query_semua = mysqli_query($conn, "SELECT a.id_admin, a.nama_lengkap, a.kelas, a.foto, 
                                               j.minggu_ke, j.kode_jaga
                                        FROM admin a
                                        LEFT JOIN jadwal j ON a.id_admin = j.id_admin
                                        $sort_query");
}

if (!$query_semua) {
    die("Query error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Jaga | UKS SmartCare</title>
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

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--light);
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
    50% { transform: translate(-20px, 20px) scale(0.95); }
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

header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 100;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
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

.profile-dropdown ul li a:hover {
    background: var(--primary-light);
    color: var(--primary);
}

.profile-dropdown.active { display: block; }

.main-section {
    padding: 140px 6% 80px;
    max-width: 1200px;
    margin: auto;
}

.section-header {
    text-align: center;
    margin-bottom: 48px;
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
}

.section-title {
    font-size: clamp(28px, 5vw, 42px);
    font-weight: 800;
    margin-bottom: 16px;
    background: linear-gradient(135deg, var(--dark), var(--muted));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.section-sub {
    font-size: 16px;
    color: var(--muted);
    max-width: 500px;
    margin: 0 auto;
}

.search-sort-container {
    display: flex;
    gap: 16px;
    margin-bottom: 32px;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
}

.search-input {
    flex: 1;
    min-width: 250px;
    max-width: 400px;
    padding: 12px 20px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    font-size: 14px;
    background: #f9f9f9;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    background: #fff;
}

.sort-buttons {
    display: flex;
    gap: 8px;
}

.sort-btn {
    padding: 12px 20px;
    border: 2px solid var(--border);
    background: var(--card);
    color: var(--dark);
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sort-btn.active {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border-color: var(--primary);
}

.table-container {
    background: var(--card);
    border-radius: var(--radius-lg);
    overflow-x: auto;
    box-shadow: var(--shadow-xl);
    border: 1px solid var(--border);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

th {
    background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
    color: var(--primary);
    font-weight: 600;
    text-align: left;
    padding: 18px 24px;
    font-size: 14px;
}

td {
    padding: 16px 24px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
}

tr:hover td {
    background: var(--primary-light);
}

.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.status-available {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.status-unavailable {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.kode-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    display: inline-block;
}

.table-action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-edit {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.btn-view {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
}

.btn-disabled {
    background: #cbd5e1;
    color: #475569;
    cursor: not-allowed;
    opacity: 0.6;
}

.card-layout {
    display: none;
}

.petugas-card {
    background: var(--card);
    border-radius: var(--radius-lg);
    padding: 20px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border);
    margin-bottom: 16px;
}

.petugas-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 12px;
}

.petugas-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid var(--border);
}

.petugas-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.petugas-info {
    flex: 1;
}

.petugas-name {
    font-weight: 600;
    font-size: 16px;
    color: var(--dark);
}

.petugas-class {
    font-size: 14px;
    color: var(--muted);
}

.petugas-schedule {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.schedule-item {
    flex: 1;
    min-width: 120px;
    padding: 10px;
    background: var(--light);
    border-radius: 8px;
    font-size: 14px;
}

.schedule-item strong {
    display: block;
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 4px;
}

.petugas-actions {
    display: flex;
    gap: 8px;
}

.card-action-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.card-edit-btn {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.card-view-btn {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
}

.card-disabled-btn {
    background: #cbd5e1;
    color: #475569;
    cursor: not-allowed;
    opacity: 0.6;
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

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
    display: flex;
    align-items: center;
    gap: 10px;
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

.modal select {
    width: 100%;
    padding: 14px;
    margin-bottom: 20px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: inherit;
    font-size: 15px;
    background: #f9f9f9;
}

.modal select:focus {
    outline: none;
    border-color: var(--primary);
    background: #fff;
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
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.close {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: var(--muted);
}

.close:hover {
    color: var(--primary);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 64px;
    color: var(--muted);
    margin-bottom: 24px;
}

.empty-state h3 {
    color: var(--dark);
    margin-bottom: 12px;
    font-size: 22px;
}

footer {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    margin-top: 60px;
    position: relative;
}

.footer-pattern {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
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
    background: rgba(255,255,255,0.2);
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
    color: rgba(255,255,255,0.85);
}

.footer-contact p {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
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
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-links a:hover {
    background: white;
    color: var(--primary);
    transform: translateY(-4px);
}

.footer-bottom {
    text-align: center;
    padding: 24px 6%;
    border-top: 1px solid rgba(255,255,255,0.2);
    font-size: 13px;
    color: rgba(255,255,255,0.7);
    position: relative;
    z-index: 1;
}

@media (max-width: 768px) {
    .welcome-text {
        display: none;
    }

    .main-section {
        padding: 120px 5% 60px;
    }

    .search-sort-container {
        flex-direction: column;
        align-items: stretch;
    }

    .search-input {
        max-width: 100%;
    }

    .table-container {
        display: none;
    }
    
    .card-layout {
        display: block;
    }
}

@media (max-width: 480px) {
    .main-section {
        padding: 110px 4% 50px;
    }

    .footer-wrap {
        padding: 40px 4% 20px;
        gap: 32px;
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
            <a href="halamanadmin.php" class="logo">
                <div class="logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                UKS SmartCare
            </a>
            <div class="profile-container" id="profileContainer">
                <div class="welcome-text">Halo, Admin!<br><strong><?= htmlspecialchars($admin['nama_lengkap'] ?? '') ?></strong></div>
                <div class="profile-icon">
                    <img src="../assets/img/<?= htmlspecialchars($admin['foto'] ?? 'default.png') ?>" alt="Profile">
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <ul>
                        <li><a href="halamanadmin.php"><i class="fa-solid fa-home"></i> Beranda</a></li>
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
        <div class="section-header">
            <div class="badge">
                <i class="fa-solid fa-calendar-alt"></i> Jadwal Piket
            </div>
            <h1 class="section-title">Jadwal Jaga Petugas</h1>
            <p class="section-sub">Anda hanya bisa mengedit jadwal Anda sendiri. Jadwal petugas lain hanya bisa dilihat.</p>
        </div>

        <div class="search-sort-container">
            <input type="text" class="search-input" placeholder="🔍 Cari nama petugas..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   onkeyup="searchJadwal(this.value)">
            
            <div class="sort-buttons">
                <button class="sort-btn <?= ($sort == 'nama_lengkap' && $order == 'ASC') ? 'active' : '' ?>" 
                        onclick="sortJadwal('nama_lengkap', 'ASC')">
                    <i class="fas fa-sort-alpha-down"></i> A-Z
                </button>
                <button class="sort-btn <?= ($sort == 'nama_lengkap' && $order == 'DESC') ? 'active' : '' ?>" 
                        onclick="sortJadwal('nama_lengkap', 'DESC')">
                    <i class="fas fa-sort-alpha-up"></i> Z-A
                </button>
            </div>
        </div>

        <!-- TABLE DESKTOP -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Petugas</th>
                        <th>Kelas</th>
                        <th>Minggu Ke</th>
                        <th>Kode Jaga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    if(mysqli_num_rows($query_semua) > 0) {
                        mysqli_data_seek($query_semua, 0);
                        while($row = mysqli_fetch_assoc($query_semua)): 
                            $status_class = (!empty($row['minggu_ke'])) ? 'status-available' : 'status-unavailable';
                            $status_text = (!empty($row['minggu_ke'])) ? 'Aktif' : 'Tidak Aktif';
                            $is_self = ($row['id_admin'] == $id_admin_sekarang);
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <img src="../assets/img/<?= htmlspecialchars($row['foto'] ?? 'default.png') ?>" style="width:35px; height:35px; border-radius:50%; object-fit:cover;">
                                <span><?= htmlspecialchars($row['nama_lengkap'] ?? '') ?></span>
                                <?php if($is_self): ?>
                                    <span style="background:var(--primary-light); color:var(--primary); padding:2px 8px; border-radius:12px; font-size:10px; font-weight:600;">Anda</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['kelas'] ?? '') ?></td>
                        <td><span class="kode-badge"><?= htmlspecialchars($row['minggu_ke'] ?? '') ?: '-' ?></span></td>
                        <td><span class="kode-badge"><?= htmlspecialchars($row['kode_jaga'] ?? '') ?: '-' ?></span></td>
                        <td>
                            <span class="status-badge <?= $status_class ?>">
                                <i class="fas fa-circle" style="font-size: 8px;"></i>
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td>
                            <?php if($is_self): ?>
                                <button class="table-action-btn btn-edit" onclick="openModal()">
                                    <i class="fas fa-edit"></i> Edit Jadwal Saya
                                </button>
                            <?php else: ?>
                                <button class="table-action-btn btn-view" disabled style="cursor:not-allowed; opacity:0.6;">
                                    <i class="fas fa-lock"></i> Hanya Bisa Dilihat
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else { 
                    ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px;">
                            <div class="empty-state">
                                <i class="fa-solid fa-users-slash"></i>
                                <h3>Belum Ada Data Petugas</h3>
                                <p>Belum ada petugas yang terdaftar.</p>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- CARD LAYOUT MOBILE -->
        <div class="card-layout">
            <?php 
            if(mysqli_num_rows($query_semua) > 0) {
                mysqli_data_seek($query_semua, 0);
                while($row = mysqli_fetch_assoc($query_semua)): 
                    $status_text = (!empty($row['minggu_ke'])) ? 'Aktif' : 'Tidak Aktif';
                    $is_self = ($row['id_admin'] == $id_admin_sekarang);
            ?>
            <div class="petugas-card">
                <div class="petugas-header">
                    <div class="petugas-avatar">
                        <img src="../assets/img/<?= htmlspecialchars($row['foto'] ?? 'default.png') ?>" alt="Profile">
                    </div>
                    <div class="petugas-info">
                        <div class="petugas-name">
                            <?= htmlspecialchars($row['nama_lengkap'] ?? '') ?>
                            <?php if($is_self): ?>
                                <span style="background:var(--primary-light); color:var(--primary); padding:2px 8px; border-radius:12px; font-size:10px; font-weight:600; margin-left:8px;">Anda</span>
                            <?php endif; ?>
                        </div>
                        <div class="petugas-class"><?= htmlspecialchars($row['kelas'] ?? '') ?></div>
                    </div>
                </div>
                
                <div class="petugas-schedule">
                    <div class="schedule-item">
                        <strong><i class="fa-regular fa-calendar"></i> Minggu Ke</strong>
                        <div><?= htmlspecialchars($row['minggu_ke'] ?? '') ?: '-' ?></div>
                    </div>
                    <div class="schedule-item">
                        <strong><i class="fa-solid fa-tag"></i> Kode Jaga</strong>
                        <div><?= htmlspecialchars($row['kode_jaga'] ?? '') ?: '-' ?></div>
                    </div>
                </div>
                
                <div class="petugas-actions">
                    <?php if($is_self): ?>
                        <button class="card-action-btn card-edit-btn" onclick="openModal()">
                            <i class="fas fa-edit"></i> Edit Jadwal Saya
                        </button>
                    <?php else: ?>
                        <div class="card-disabled-btn">
                            <i class="fas fa-lock"></i> Hanya Bisa Dilihat
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
                endwhile;
            } else { 
            ?>
            <div class="empty-state">
                <i class="fa-solid fa-users-slash"></i>
                <h3>Belum Ada Data Petugas</h3>
                <p>Belum ada petugas yang terdaftar.</p>
            </div>
            <?php } ?>
        </div>
    </main>

    <!-- MODAL EDIT JADWAL SENDIRI (HANYA UNTUK ADMIN YANG LOGIN) -->
    <div class="modal-bg" id="modalJadwal">
        <div class="modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3><i class="fa-regular fa-calendar-plus"></i> Edit Jadwal Saya</h3>
            <p>Isi jadwal piket Anda sendiri.</p>
            <form method="POST">
                <label>Minggu Ke</label>
                <select name="minggu_ke" required>
                    <option value="">-- Pilih Minggu --</option>
                    <option value="1" <?= (($admin['minggu_ke'] ?? '') == '1') ? 'selected' : '' ?>>Minggu 1 (Ganjil)</option>
                    <option value="2" <?= (($admin['minggu_ke'] ?? '') == '2') ? 'selected' : '' ?>>Minggu 2 (Genap)</option>
                </select>
                
                <label>Kode Jaga</label>
                <select name="kode_jaga" required>
                    <option value="">-- Pilih Kode Jaga --</option>
                    <optgroup label="Senin">
                        <option value="1A" <?= (($admin['kode_jaga'] ?? '') == '1A') ? 'selected' : '' ?>>1A - Senin (Shift 1: 07:30-09:30)</option>
                        <option value="1B" <?= (($admin['kode_jaga'] ?? '') == '1B') ? 'selected' : '' ?>>1B - Senin (Shift 2: 09:30-12:00)</option>
                        <option value="1C" <?= (($admin['kode_jaga'] ?? '') == '1C') ? 'selected' : '' ?>>1C - Senin (Shift 3: 12:00-14:30)</option>
                    </optgroup>
                    <optgroup label="Selasa">
                        <option value="2A" <?= (($admin['kode_jaga'] ?? '') == '2A') ? 'selected' : '' ?>>2A - Selasa (Shift 1: 07:30-09:30)</option>
                        <option value="2B" <?= (($admin['kode_jaga'] ?? '') == '2B') ? 'selected' : '' ?>>2B - Selasa (Shift 2: 09:30-12:00)</option>
                        <option value="2C" <?= (($admin['kode_jaga'] ?? '') == '2C') ? 'selected' : '' ?>>2C - Selasa (Shift 3: 12:00-14:30)</option>
                    </optgroup>
                    <optgroup label="Rabu">
                        <option value="3A" <?= (($admin['kode_jaga'] ?? '') == '3A') ? 'selected' : '' ?>>3A - Rabu (Shift 1: 07:30-09:30)</option>
                        <option value="3B" <?= (($admin['kode_jaga'] ?? '') == '3B') ? 'selected' : '' ?>>3B - Rabu (Shift 2: 09:30-12:00)</option>
                        <option value="3C" <?= (($admin['kode_jaga'] ?? '') == '3C') ? 'selected' : '' ?>>3C - Rabu (Shift 3: 12:00-14:30)</option>
                    </optgroup>
                    <optgroup label="Kamis">
                        <option value="4A" <?= (($admin['kode_jaga'] ?? '') == '4A') ? 'selected' : '' ?>>4A - Kamis (Shift 1: 07:30-09:30)</option>
                        <option value="4B" <?= (($admin['kode_jaga'] ?? '') == '4B') ? 'selected' : '' ?>>4B - Kamis (Shift 2: 09:30-12:00)</option>
                        <option value="4C" <?= (($admin['kode_jaga'] ?? '') == '4C') ? 'selected' : '' ?>>4C - Kamis (Shift 3: 12:00-14:30)</option>
                    </optgroup>
                    <optgroup label="Jumat">
                        <option value="5A" <?= (($admin['kode_jaga'] ?? '') == '5A') ? 'selected' : '' ?>>5A - Jumat (Shift 1: 07:30-09:30)</option>
                        <option value="5B" <?= (($admin['kode_jaga'] ?? '') == '5B') ? 'selected' : '' ?>>5B - Jumat (Shift 2: 09:30-12:00)</option>
                        <option value="5C" <?= (($admin['kode_jaga'] ?? '') == '5C') ? 'selected' : '' ?>>5C - Jumat (Shift 3: 12:00-14:30)</option>
                    </optgroup>
                </select>
                
                <button type="submit" name="simpan_jadwal" class="save-btn">
                    <i class="fa-solid fa-save"></i> Simpan Jadwal Saya
                </button>
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
                <p>Sistem layanan kesehatan sekolah berbasis digital.</p>
            </div>
            <div class="footer-contact">
                <h4>Kontak Kami</h4>
                <p><i class="fa-solid fa-location-dot"></i> SMKN 1 Cibinong</p>
                <p><i class="fa-solid fa-envelope"></i> uks.smartcare@gmail.com</p>
            </div>
            <div>
                <h4>Ikuti Kami</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
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

    if (profileContainer) {
        profileContainer.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });
        document.addEventListener('click', () => profileDropdown.classList.remove('active'));
    }

    function openModal() { 
        document.getElementById('modalJadwal').classList.add('active'); 
    }
    
    function closeModal() { 
        document.getElementById('modalJadwal').classList.remove('active'); 
    }

    function searchJadwal(searchTerm) {
        const url = new URL(window.location.href);
        if (searchTerm) url.searchParams.set('search', searchTerm);
        else url.searchParams.delete('search');
        window.location.href = url.toString();
    }

    function sortJadwal(sortBy, order) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortBy);
        url.searchParams.set('order', order);
        window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('particles');
        if (container) {
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }
    });

    window.addEventListener('scroll', () => {
        const header = document.getElementById('header');
        if (header) {
            header.classList.toggle('scrolled', window.scrollY > 50);
        }
    });
    </script>
</body>
</html>