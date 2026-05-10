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

// Ambil data admin yang sedang login dari tabel admin
$q_admin = mysqli_query($conn, "SELECT * FROM admin WHERE id_admin = '$id_admin_sekarang'");
$admin = mysqli_fetch_assoc($q_admin);

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit;
}

// AMBIL FILTER BULAN & TAHUN
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';

// FILTER KATEGORI
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'Semua';

// CEK APAKAH ADA PERMINTAAN EXPORT EXCEL
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $where_filter = "WHERE 1=1";
    if ($kategori_filter != 'Semua') {
        $where_filter .= " AND kategori = '$kategori_filter'";
    }
    if (!empty($filter_bulan) && !empty($filter_tahun)) {
        $where_filter .= " AND MONTH(created_at) = '$filter_bulan' AND YEAR(created_at) = '$filter_tahun'";
    } elseif (!empty($filter_bulan)) {
        $where_filter .= " AND MONTH(created_at) = '$filter_bulan'";
    } elseif (!empty($filter_tahun)) {
        $where_filter .= " AND YEAR(created_at) = '$filter_tahun'";
    }
    
    $export_query = mysqli_query($conn, "SELECT * FROM feedback $where_filter ORDER BY created_at DESC");
    
    $export_data = [];
    while ($row = mysqli_fetch_assoc($export_query)) {
        $export_data[] = $row;
    }
    
    $filter_text = '';
    if (!empty($filter_bulan) && !empty($filter_tahun)) {
        $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $filter_text = '_' . $nama_bulan[(int)$filter_bulan] . '_' . $filter_tahun;
    } elseif (!empty($filter_tahun)) {
        $filter_text = '_Tahun_' . $filter_tahun;
    } elseif (!empty($filter_bulan)) {
        $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $filter_text = '_' . $nama_bulan[(int)$filter_bulan];
    }
    if ($kategori_filter != 'Semua') {
        $filter_text .= '_' . $kategori_filter;
    }
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Feedback_UKS' . $filter_text . '_' . date('Y-m-d') . '.xls"');
    
    echo '<h2>Laporan Feedback UKS SmartCare</h2>';
    echo '<h3>Periode: ';
    if (!empty($filter_bulan)) {
        $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        echo $nama_bulan[(int)$filter_bulan];
    }
    if (!empty($filter_tahun)) echo ' ' . $filter_tahun;
    if (empty($filter_bulan) && empty($filter_tahun)) echo 'Semua Data';
    if ($kategori_filter != 'Semua') echo ' | Kategori: ' . $kategori_filter;
    echo '</h3>';
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>Tanggal</th>';
    echo '<th>Kategori</th>';
    echo '<th>Rating</th>';
    echo '<th>Pesan</th>';
    echo '</tr>';
    
    $no = 1;
    foreach ($export_data as $data) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . date('d/m/Y H:i', strtotime($data['created_at'])) . '</td>';
        echo '<td>' . htmlspecialchars($data['kategori']) . '</td>';
        echo '<td>' . $data['rating'] . '/5' . '</td>';
        echo '<td>' . htmlspecialchars($data['pesan'] ?? '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

// QUERY DATA FEEDBACK DENGAN FILTER
$where_filter = "WHERE 1=1";
if ($kategori_filter != 'Semua') {
    $where_filter .= " AND kategori = '$kategori_filter'";
}
if (!empty($filter_bulan) && !empty($filter_tahun)) {
    $where_filter .= " AND MONTH(created_at) = '$filter_bulan' AND YEAR(created_at) = '$filter_tahun'";
} elseif (!empty($filter_bulan)) {
    $where_filter .= " AND MONTH(created_at) = '$filter_bulan'";
} elseif (!empty($filter_tahun)) {
    $where_filter .= " AND YEAR(created_at) = '$filter_tahun'";
}

$query_feedback = mysqli_query($conn, "SELECT * FROM feedback $where_filter ORDER BY created_at DESC");
$feedback_data = [];
while ($row = mysqli_fetch_assoc($query_feedback)) {
    $feedback_data[] = $row;
}

// HITUNG STATISTIK
$q_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM feedback");
$total_feedback = mysqli_fetch_assoc($q_total)['total'];

$q_rating = mysqli_query($conn, "SELECT AVG(rating) as avg_rating FROM feedback");
$avg_rating = mysqli_fetch_assoc($q_rating)['avg_rating'];
$avg_rating = $avg_rating ? number_format($avg_rating, 1) : 0;

$q_today = mysqli_query($conn, "SELECT COUNT(*) as today FROM feedback WHERE DATE(created_at) = CURDATE()");
$today_feedback = mysqli_fetch_assoc($q_today)['today'];

$persen = $total_feedback > 0 ? round(($today_feedback / $total_feedback) * 100) : 0;

// Ambil daftar tahun yang tersedia
$q_tahun = mysqli_query($conn, "SELECT DISTINCT YEAR(created_at) as tahun FROM feedback ORDER BY tahun DESC");
$tahun_list = [];
while ($row = mysqli_fetch_assoc($q_tahun)) {
    $tahun_list[] = $row['tahun'];
}

// PROSES HAPUS FEEDBACK
if (isset($_POST['hapus_feedback']) && isset($_POST['id_feedback'])) {
    $id_feedback = intval($_POST['id_feedback']);
    
    if ($id_feedback > 0) {
        $delete_query = mysqli_query($conn, "DELETE FROM feedback WHERE id = '$id_feedback'");
        if ($delete_query) {
            echo "<script>
                alert('Feedback berhasil dihapus!');
                window.location.href='feedback.php?kategori=" . urlencode($kategori_filter) . "&bulan=$filter_bulan&tahun=$filter_tahun';
            </script>";
        } else {
            echo "<script>alert('Gagal menghapus feedback!');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Feedback | UKS SmartCare</title>
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
    --shadow-lg: 0 10px 20px rgba(0, 0, 0, 0.08);
    --shadow-xl: 0 15px 30px -12px rgba(0, 0, 0, 0.15);
    --radius: 12px;
    --radius-lg: 16px;
    --transition: all 0.3s ease;
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
    line-height: 1.6;
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
    width: 3px;
    height: 3px;
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


.profile-dropdown ul li a:hover {
    background: var(--primary-light);
    color: var(--primary);
}

.profile-dropdown.active { display: block; }

/* MAIN CONTENT */
.main-section {
    padding: 140px 5% 80px;
    max-width: 1400px;
    margin: 0 auto;
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.6s ease forwards;
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.badge-custom {
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
    font-size: clamp(28px, 5vw, 40px);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 16px;
    background: linear-gradient(135deg, var(--dark), var(--muted));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-align: center;
}

.section-sub {
    font-size: 16px;
    color: var(--muted);
    line-height: 1.7;
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
}

/* STATS CARDS */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--card);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border);
    transition: var(--transition);
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.6s ease forwards;
    text-align: center;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary);
}

.stat-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px auto;
}

.stat-icon i {
    font-size: 24px;
    color: var(--primary);
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    color: var(--muted);
}

/* FILTER SECTION */
.filter-wrapper {
    background: var(--card);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
    padding: 20px 24px;
    margin-bottom: 32px;
    box-shadow: var(--shadow);
}

.filter-row {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 150px;
}

.filter-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--muted);
    margin-bottom: 8px;
}

.filter-group select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    color: var(--dark);
    background: var(--card);
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-group select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.filter-buttons {
    display: flex;
    gap: 12px;
}

.btn-filter {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-filter-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
}

.btn-filter-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.btn-filter-reset {
    background: linear-gradient(135deg, var(--muted), #475569);
    color: white;
}

.btn-filter-reset:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
}

/* CATEGORY FILTER */
.category-filter {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.filter-btn-category {
    padding: 10px 20px;
    border: 2px solid var(--border);
    border-radius: 100px;
    background: var(--card);
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: var(--muted);
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.filter-btn-category i {
    font-size: 12px;
    opacity: 0.7;
}

.filter-btn-category:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--primary-light);
}

.filter-btn-category.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.2);
}

.filter-info {
    background: var(--primary-light);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.filter-info-text {
    font-size: 14px;
    color: var(--primary);
    font-weight: 500;
}

.filter-info-text i {
    margin-right: 8px;
}

/* FEEDBACK LIST */
.feedback-header-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.feedback-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-export-excel {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-export-excel:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.feedback-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
}

.feedback-card {
    background: var(--card);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border);
    transition: var(--transition);
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.6s ease forwards;
}

.feedback-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary);
}

.feedback-card:nth-child(1) { animation-delay: 0.1s; }
.feedback-card:nth-child(2) { animation-delay: 0.2s; }
.feedback-card:nth-child(3) { animation-delay: 0.3s; }
.feedback-card:nth-child(4) { animation-delay: 0.4s; }
.feedback-card:nth-child(5) { animation-delay: 0.5s; }
.feedback-card:nth-child(6) { animation-delay: 0.6s; }

.feedback-header-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.feedback-date {
    font-size: 13px;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--light);
    padding: 4px 10px;
    border-radius: 20px;
}

.feedback-rating {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--primary-light);
    border-radius: 20px;
    border: 1px solid rgba(220, 38, 38, 0.1);
}

.feedback-rating i {
    color: var(--primary);
    font-size: 14px;
}

.feedback-rating span {
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
}

.feedback-text {
    font-size: 15px;
    line-height: 1.6;
    color: var(--dark);
    margin-bottom: 20px;
    padding: 12px;
    background: var(--light);
    border-radius: var(--radius);
}

.feedback-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.meta-tag {
    padding: 6px 12px;
    background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: var(--primary);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-delete-card {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 6px;
    background: transparent;
    color: #ef4444;
    border: 1px solid #ef4444;
}

.btn-delete-card:hover {
    background: #fef2f2;
    transform: translateY(-2px);
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
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
    text-align: center;
}

.empty-state p {
    color: var(--muted);
    font-size: 15px;
    text-align: center;
    max-width: 400px;
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
    max-width: 1400px;
    margin: 0 auto;
    padding: 64px 5% 32px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 40px;
    position: relative;
    z-index: 1;
}

.footer-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 16px;
}

.footer-brand-icon {
    width: 42px;
    height: 42px;
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
    font-size: 15px;
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
    font-size: 15px;
}

.social-links {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.social-links a {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    transition: var(--transition);
    text-decoration: none;
}

.social-links a:hover {
    background: white;
    color: var(--primary);
    transform: translateY(-3px);
}

.footer-bottom {
    text-align: center;
    padding: 24px 5%;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    position: relative;
    z-index: 1;
}

/* ANIMATIONS */
@keyframes slideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .navbar {
        padding: 14px 5%;
    }

    .welcome-text {
        display: none;
    }

    .main-section {
        padding: 120px 5% 50px;
    }

    .section-header {
        margin-bottom: 32px;
    }

    .section-title {
        font-size: 26px;
    }

    .section-sub {
        font-size: 15px;
    }

    .stats-container {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .stat-card {
        padding: 20px;
    }

    .stat-value {
        font-size: 28px;
    }

    .filter-row {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }

    .filter-buttons {
        flex-direction: column;
    }

    .btn-filter, .btn-export-excel {
        justify-content: center;
    }

    .category-filter {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .filter-btn-category {
        width: 100%;
        justify-content: center;
    }

    .feedback-header-wrapper {
        flex-direction: column;
        align-items: flex-start;
    }

    .btn-export-excel {
        width: 100%;
        justify-content: center;
    }

    .feedback-list {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .feedback-card {
        padding: 20px;
    }

    .feedback-meta {
        flex-direction: column;
        align-items: flex-start;
    }

    .footer-wrap {
        padding: 48px 4% 24px;
        gap: 32px;
    }
}

@media (max-width: 480px) {
    .main-section {
        padding: 110px 4% 40px;
    }

    .section-title {
        font-size: 24px;
    }

    .section-sub {
        font-size: 14px;
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
            <a href="halamanadmin.php" class="logo">
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
            <div class="badge-custom">
                <i class="fa-solid fa-comment-dots"></i> Feedback & Saran
            </div>
            <h1 class="section-title">Dashboard Feedback</h1>
            <p class="section-sub">Lihat dan kelola semua feedback dari siswa untuk meningkatkan layanan UKS</p>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-comment-dots"></i>
                </div>
                <div class="stat-value"><?= $total_feedback ?></div>
                <div class="stat-label">Total Feedback</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-star"></i>
                </div>
                <div class="stat-value"><?= $avg_rating ?></div>
                <div class="stat-label">Rating Rata-rata</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-value"><?= $today_feedback ?></div>
                <div class="stat-label">Baru Hari Ini</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $persen ?>%</div>
                <div class="stat-label">Feedback Rate</div>
            </div>
        </div>

        <!-- FILTER BULAN & TAHUN -->
        <div class="filter-wrapper">
            <div class="filter-row">
                <div class="filter-group">
                    <label><i class="fa-regular fa-calendar"></i> Bulan</label>
                    <select name="bulan" id="filter_bulan">
                        <option value="">Semua Bulan</option>
                        <option value="1" <?= $filter_bulan == '1' ? 'selected' : '' ?>>Januari</option>
                        <option value="2" <?= $filter_bulan == '2' ? 'selected' : '' ?>>Februari</option>
                        <option value="3" <?= $filter_bulan == '3' ? 'selected' : '' ?>>Maret</option>
                        <option value="4" <?= $filter_bulan == '4' ? 'selected' : '' ?>>April</option>
                        <option value="5" <?= $filter_bulan == '5' ? 'selected' : '' ?>>Mei</option>
                        <option value="6" <?= $filter_bulan == '6' ? 'selected' : '' ?>>Juni</option>
                        <option value="7" <?= $filter_bulan == '7' ? 'selected' : '' ?>>Juli</option>
                        <option value="8" <?= $filter_bulan == '8' ? 'selected' : '' ?>>Agustus</option>
                        <option value="9" <?= $filter_bulan == '9' ? 'selected' : '' ?>>September</option>
                        <option value="10" <?= $filter_bulan == '10' ? 'selected' : '' ?>>Oktober</option>
                        <option value="11" <?= $filter_bulan == '11' ? 'selected' : '' ?>>November</option>
                        <option value="12" <?= $filter_bulan == '12' ? 'selected' : '' ?>>Desember</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fa-regular fa-calendar"></i> Tahun</label>
                    <select name="tahun" id="filter_tahun">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($tahun_list as $tahun): ?>
                            <option value="<?= $tahun ?>" <?= $filter_tahun == $tahun ? 'selected' : '' ?>><?= $tahun ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-buttons">
                    <button type="button" class="btn-filter btn-filter-primary" id="btn_apply_filter">
                        <i class="fa-solid fa-filter"></i> Terapkan Filter
                    </button>
                    <a href="feedback.php?kategori=<?= urlencode($kategori_filter) ?>" class="btn-filter btn-filter-reset">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
            </div>
        </div>

        <!-- KATEGORI FILTER -->
        <div class="category-filter">
            <a href="?kategori=Semua&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="filter-btn-category <?= $kategori_filter == 'Semua' ? 'active' : '' ?>">
                <i class="fa-solid fa-list"></i> Semua
            </a>
            <a href="?kategori=Pelayanan&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="filter-btn-category <?= $kategori_filter == 'Pelayanan' ? 'active' : '' ?>">
                <i class="fa-solid fa-handshake"></i> Pelayanan
            </a>
            <a href="?kategori=Obat&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="filter-btn-category <?= $kategori_filter == 'Obat' ? 'active' : '' ?>">
                <i class="fa-solid fa-pills"></i> Obat
            </a>
            <a href="?kategori=Waktu&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="filter-btn-category <?= $kategori_filter == 'Waktu' ? 'active' : '' ?>">
                <i class="fa-solid fa-clock"></i> Waktu
            </a>
            <a href="?kategori=Saran&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="filter-btn-category <?= $kategori_filter == 'Saran' ? 'active' : '' ?>">
                <i class="fa-solid fa-lightbulb"></i> Saran
            </a>
            <a href="?kategori=Bug&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="filter-btn-category <?= $kategori_filter == 'Bug' ? 'active' : '' ?>">
                <i class="fa-solid fa-bug"></i> Bug
            </a>
        </div>

        <!-- FILTER INFO -->
        <?php if (!empty($filter_bulan) || !empty($filter_tahun) || $kategori_filter != 'Semua'): ?>
            <div class="filter-info">
                <div class="filter-info-text">
                    <i class="fa-solid fa-chart-line"></i>
                    Menampilkan data untuk: 
                    <?php 
                        $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        if (!empty($filter_bulan)) echo $nama_bulan[(int)$filter_bulan];
                        if (!empty($filter_tahun)) echo ' ' . $filter_tahun;
                        if (empty($filter_bulan) && empty($filter_tahun)) echo 'Semua Periode';
                        if ($kategori_filter != 'Semua') echo ' | Kategori: ' . $kategori_filter;
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- FEEDBACK LIST -->
        <div class="feedback-header-wrapper">
            <h2 class="feedback-title">
                <i class="fa-solid fa-list"></i>
                Daftar Feedback
            </h2>
            <a href="?export=excel&kategori=<?= urlencode($kategori_filter) ?>&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="btn-export-excel">
                <i class="fa-solid fa-file-excel"></i> Export ke Excel
            </a>
        </div>

        <div class="feedback-list">
            <?php if (empty($feedback_data)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <h3>Belum Ada Feedback</h3>
                    <p>
                        <?php if (!empty($filter_bulan) || !empty($filter_tahun) || $kategori_filter != 'Semua'): ?>
                            Tidak ada feedback untuk periode dan kategori yang dipilih.
                        <?php else: ?>
                            Tunggu feedback dari siswa untuk mulai muncul di sini.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($feedback_data as $feedback): ?>
                    <div class="feedback-card">
                        <div class="feedback-header-card">
                            <div class="feedback-date">
                                <i class="fa-solid fa-calendar"></i> <?= date('d M Y H:i', strtotime($feedback['created_at'])) ?>
                            </div>
                            <div class="feedback-rating">
                                <i class="fa-solid fa-star"></i>
                                <span><?= $feedback['rating'] ?>/5</span>
                            </div>
                        </div>
                        <div class="feedback-text">
                            "<?= htmlspecialchars($feedback['pesan'] ?? '-') ?>"
                        </div>
                        <div class="feedback-meta">
                            <div class="meta-tag">
                                <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($feedback['kategori']) ?>
                            </div>
                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus feedback ini?')">
                                <input type="hidden" name="id_feedback" value="<?= $feedback['id'] ?>">
                                <button type="submit" name="hapus_feedback" class="btn-delete-card">
                                    <i class="fa-solid fa-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
    // Profile Dropdown Logic
    const profileContainer = document.getElementById('profileContainer');
    const profileDropdown = document.getElementById('profileDropdown');

    if (profileContainer && profileDropdown) {
        profileContainer.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });

        document.addEventListener('click', () => profileDropdown.classList.remove('active'));
    }

    // Filter Logic
    const btnApplyFilter = document.getElementById('btn_apply_filter');
    if (btnApplyFilter) {
        btnApplyFilter.addEventListener('click', () => {
            const bulan = document.getElementById('filter_bulan').value;
            const tahun = document.getElementById('filter_tahun').value;
            const kategori = '<?= $kategori_filter ?>';
            let url = 'feedback.php';
            let params = [];
            if (kategori && kategori !== 'Semua') params.push('kategori=' + encodeURIComponent(kategori));
            if (bulan) params.push('bulan=' + bulan);
            if (tahun) params.push('tahun=' + tahun);
            if (params.length > 0) url += '?' + params.join('&');
            window.location.href = url;
        });
    }

    // Particles Animation
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('particles');
        if (container) {
            for (let i = 0; i < 30; i++) {
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
    });

    // Header Scroll Effect
    window.addEventListener('scroll', () => {
        const header = document.getElementById('header');
        if (header) {
            header.classList.toggle('scrolled', window.scrollY > 50);
        }
    });
    </script>
</body>
</html>