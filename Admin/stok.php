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

// AMBIL DATA ADMIN YANG LOGIN
$query_self = mysqli_query($conn, "SELECT * FROM admin WHERE id_admin = '$id_admin_sekarang'");
$admin = mysqli_fetch_assoc($query_self);

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit;
}

// ========== FUNGSI BACA & SIMPAN used_this_week KE FILE JSON ==========
$used_week_file = __DIR__ . '/used_week.json';

function getUsedWeek() {
    global $used_week_file;
    if (file_exists($used_week_file)) {
        $data = json_decode(file_get_contents($used_week_file), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function saveUsedWeek($data) {
    global $used_week_file;
    file_put_contents($used_week_file, json_encode($data));
}

// Baca data used_week saat ini
$used_week = getUsedWeek();

// Kategori yang perlu di-limit (biar tau mana yang perlu diupdate used_week nya)
$kategori_limit = ['tablet', 'sachet', 'lembar', 'gulung', 'swabs'];

function perluLimit($kategori) {
    global $kategori_limit;
    $kategori_lower = strtolower($kategori);
    foreach ($kategori_limit as $kat) {
        if (strpos($kategori_lower, $kat) !== false) return true;
    }
    return false;
}

// FUNGSI UPDATE STATUS BERDASARKAN STOK
function updateStatusObat($conn, $id_obat, $stok) {
    if ($stok > 10) {
        $status = 'tersedia';
    } elseif ($stok >= 1 && $stok <= 10) {
        $status = 'hampir_habis';
    } else {
        $status = 'habis';
    }
    mysqli_query($conn, "UPDATE obat SET status = '$status' WHERE id_obat = $id_obat");
}

// UPDATE STOK + UPDATE used_week
if (isset($_GET['tambah_id'])) {
    $id = (int)$_GET['tambah_id'];
    
    // Ambil kategori obat dulu
    $q_kat = mysqli_query($conn, "SELECT kategori FROM obat WHERE id_obat = $id");
    $kat_data = mysqli_fetch_assoc($q_kat);
    $kategori = strtolower($kat_data['kategori']);
    
    mysqli_query($conn, "UPDATE obat SET stok = stok + 1 WHERE id_obat = $id");
    
    // Jika kategori perlu limit, kurangi used_week (biar sisa limit nambah)
    if (perluLimit($kategori)) {
        if (!isset($used_week[$id])) $used_week[$id] = 0;
        if ($used_week[$id] > 0) {
            $used_week[$id]--;
        }
        saveUsedWeek($used_week);
    }
    
    $result = mysqli_query($conn, "SELECT stok FROM obat WHERE id_obat = $id");
    $row = mysqli_fetch_assoc($result);
    updateStatusObat($conn, $id, $row['stok']);
    header("Location: stok.php");
    exit;
}

if (isset($_GET['kurang_id'])) {
    $id = (int)$_GET['kurang_id'];
    
    // Ambil kategori obat dulu
    $q_kat = mysqli_query($conn, "SELECT kategori FROM obat WHERE id_obat = $id");
    $kat_data = mysqli_fetch_assoc($q_kat);
    $kategori = strtolower($kat_data['kategori']);
    
    mysqli_query($conn, "UPDATE obat SET stok = GREATEST(stok - 1, 0) WHERE id_obat = $id");
    
    // Jika kategori perlu limit, tambah used_week (biar sisa limit berkurang)
    if (perluLimit($kategori)) {
        if (!isset($used_week[$id])) $used_week[$id] = 0;
        $used_week[$id]++;
        saveUsedWeek($used_week);
    }
    
    $result = mysqli_query($conn, "SELECT stok FROM obat WHERE id_obat = $id");
    $row = mysqli_fetch_assoc($result);
    updateStatusObat($conn, $id, $row['stok']);
    header("Location: stok.php");
    exit;
}

if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    
    // Hapus juga dari used_week
    if (isset($used_week[$id])) {
        unset($used_week[$id]);
        saveUsedWeek($used_week);
    }
    
    mysqli_query($conn, "DELETE FROM obat WHERE id_obat = $id");
    header("Location: stok.php");
    exit;
}

// UPDATE STOK INLINE
if (isset($_GET['update_stock'])) {
    $id = (int)$_GET['update_stock'];
    $stok_baru = (int)$_GET['stok'];
    
    // Ambil stok lama dan kategori
    $q_old = mysqli_query($conn, "SELECT stok, kategori FROM obat WHERE id_obat = $id");
    $old_data = mysqli_fetch_assoc($q_old);
    $stok_lama = (int)$old_data['stok'];
    $kategori = strtolower($old_data['kategori']);
    
    $selisih = $stok_baru - $stok_lama;
    
    if ($stok_baru >= 0) {
        mysqli_query($conn, "UPDATE obat SET stok = $stok_baru WHERE id_obat = $id");
        updateStatusObat($conn, $id, $stok_baru);
        
        // Jika kategori perlu limit, sesuaikan used_week berdasarkan selisih
        if (perluLimit($kategori)) {
            if (!isset($used_week[$id])) $used_week[$id] = 0;
            // Kalau stok berkurang (selisih negatif) -> used_week + (selisih negatif jadi positif)
            // Kalau stok bertambah (selisih positif) -> used_week - selisih
            $used_week[$id] = max(0, $used_week[$id] - $selisih);
            saveUsedWeek($used_week);
        }
    }
    header("Location: stok.php");
    exit;
}

// SIMPAN OBAT BARU
if (isset($_POST['tambah_obat'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $stok = (int)$_POST['stok'];
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    
    if ($stok > 10) {
        $status = 'tersedia';
    } elseif ($stok >= 1 && $stok <= 10) {
        $status = 'hampir_habis';
    } else {
        $status = 'habis';
    }

    if (!empty($nama) && $stok >= 0) {
        mysqli_query($conn, "INSERT INTO obat (nama_obat, stok, kategori, status) VALUES ('$nama', '$stok', '$kategori', '$status')");
        // Obat baru, used_week default 0 (tidak perlu ditambah)
    }
    header("Location: stok.php");
    exit;
}

// HITUNG STATISTIK
$total_obat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM obat"))['total'];
$total_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok) as total FROM obat"))['total'];
$low_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM obat WHERE stok BETWEEN 1 AND 10"))['total'];
$out_of_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM obat WHERE stok = 0"))['total'];

// PENCARIAN DAN PENGURUTAN
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'nama_obat';

$query = "SELECT * FROM obat WHERE 1=1";
if (!empty($search_term)) {
    $query .= " AND (nama_obat LIKE '%$search_term%' OR kategori LIKE '%$search_term%')";
}
$query .= " ORDER BY $sort_by ASC";

// PAGINATION
$per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

$total_result = mysqli_query($conn, $query);
$total_rows = mysqli_num_rows($total_result);
$total_pages = ceil($total_rows / $per_page);
$query .= " LIMIT $start, $per_page";

$data_obat = mysqli_query($conn, $query);
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
    --warning: #f59e0b;
    --danger: #ef4444;
    --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.08);
    --shadow-xl: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
    --radius: 12px;
    --radius-lg: 16px;
    --transition: all 0.3s ease;
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
.bg-container { position: fixed; inset: 0; z-index: -1; overflow: hidden; }
.gradient-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.6; animation: orbFloat 20s ease-in-out infinite; }
.orb-1 { width: 600px; height: 600px; background: linear-gradient(135deg, rgba(220,38,38,0.3), rgba(251,113,133,0.2)); top: -200px; right: -100px; }
.orb-2 { width: 500px; height: 500px; background: linear-gradient(135deg, rgba(14,165,233,0.2), rgba(56,189,248,0.15)); bottom: -150px; left: -100px; animation-delay: -7s; }
.orb-3 { width: 400px; height: 400px; background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(52,211,153,0.1)); top: 40%; left: 30%; animation-delay: -14s; }
@keyframes orbFloat { 0%,100% { transform: translate(0,0) scale(1); } 25% { transform: translate(30px,-30px) scale(1.05); } 50% { transform: translate(-20px,20px) scale(0.95); } 75% { transform: translate(20px,30px) scale(1.02); } }
.particles { position: absolute; inset: 0; overflow: hidden; }
.particle { position: absolute; width: 3px; height: 3px; background: var(--primary); border-radius: 50%; opacity: 0.3; animation: particleRise 15s linear infinite; }
@keyframes particleRise { 0% { transform: translateY(100vh); opacity: 0; } 10% { opacity: 0.3; } 90% { opacity: 0.3; } 100% { transform: translateY(-100vh); opacity: 0; } }

/* HEADER */
header { position: fixed; top: 0; left: 0; width: 100%; z-index: 100; background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(226,232,240,0.5); transition: all 0.3s ease; }
header.scrolled { background: rgba(255,255,255,0.98); box-shadow: var(--shadow-lg); }
.navbar { max-width: 1200px; margin: auto; padding: 16px 6%; display: flex; align-items: center; justify-content: space-between; }
.logo { display: flex; align-items: center; gap: 12px; font-size: 22px; font-weight: 800; color: var(--primary); text-decoration: none; }
.logo:hover { transform: scale(1.02); }
.logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, var(--primary), #f87171); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 4px 12px rgba(220,38,38,0.3); }
.profile-container { position: relative; display: flex; align-items: center; gap: 12px; cursor: pointer; }
.welcome-text { font-size: 14px; line-height: 1.2; text-align: right; }
.welcome-text strong { display: block; font-weight: 600; color: var(--dark); }
.profile-icon { width: 45px; height: 45px; border-radius: 50%; overflow: hidden; background: var(--card); border: 2px solid var(--border); transition: all 0.3s ease; }
.profile-icon:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(220,38,38,0.2); }
.profile-icon img { width: 100%; height: 100%; object-fit: cover; }
.profile-dropdown { display: none; position: absolute; top: 55px; right: 0; background: var(--card); box-shadow: var(--shadow-xl); border-radius: var(--radius-lg); width: 220px; overflow: hidden; z-index: 101; border: 1px solid var(--border); }
.profile-dropdown ul { list-style: none; }
.profile-dropdown ul li a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; text-decoration: none; color: var(--dark); font-weight: 500; transition: all 0.3s ease; font-size: 14px; }
.profile-dropdown ul li a:hover { background: var(--primary-light); color: var(--primary); }
.profile-dropdown.active { display: block; }

/* MAIN CONTENT */
.main-section { padding: 140px 6% 80px; max-width: 1200px; margin: auto; opacity: 0; transform: translateY(30px); animation: slideUp 0.8s ease forwards; }
.section-header { text-align: center; margin-bottom: 48px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.badge { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 16px; background: linear-gradient(135deg, var(--primary-light), var(--accent-light)); border-radius: 100px; font-size: 13px; font-weight: 600; color: var(--primary); margin-bottom: 20px; }
.section-title { font-size: clamp(28px,5vw,42px); font-weight: 800; line-height: 1.2; margin-bottom: 16px; background: linear-gradient(135deg, var(--dark), var(--muted)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-align: center; }
.section-sub { font-size: 16px; color: var(--muted); line-height: 1.7; max-width: 600px; margin: 0 auto; text-align: center; }

/* STATS CARDS */
.stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
.stat-card { background: var(--card); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-lg); border: 1px solid var(--border); transition: var(--transition); text-align: center; opacity: 0; transform: translateY(20px); animation: slideUp 0.6s ease forwards; }
.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }
.stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-xl); border-color: var(--primary); }
.stat-icon { width: 55px; height: 55px; background: linear-gradient(135deg, var(--primary-light), var(--accent-light)); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; }
.stat-icon i { font-size: 26px; color: var(--primary); }
.stat-value { font-size: 32px; font-weight: 800; color: var(--dark); margin-bottom: 8px; }
.stat-label { font-size: 14px; color: var(--muted); font-weight: 500; }

/* SEARCH AND SORT */
/* SEARCH AND SORT - V2 RAPI */
.search-sort-container {
    display: flex;
    gap: 16px;
    margin-bottom: 32px;
    align-items: center;
    justify-content: center;
    background: var(--card);
    padding: 20px 24px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border);
    flex-wrap: wrap;
}

.search-wrapper {
    flex: 1;
    min-width: 280px;
    position: relative;
}

.search-wrapper .search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 15px;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 14px 16px 14px 44px;
    border: 2px solid var(--border);
    border-radius: 60px;
    font-size: 14px;
    transition: var(--transition);
    background: var(--light);
    font-family: inherit;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.search-clear {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--muted);
    cursor: pointer;
    font-size: 14px;
    padding: 4px;
    border-radius: 50%;
    transition: all 0.2s ease;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-clear:hover {
    background: var(--danger-light);
    color: var(--danger);
}

.sort-wrapper {
    position: relative;
    min-width: 220px;
}

.sort-wrapper .sort-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 14px;
    pointer-events: none;
}

.sort-dropdown {
    width: 100%;
    padding: 14px 16px 14px 44px;
    border: 2px solid var(--border);
    border-radius: 60px;
    font-size: 14px;
    background: var(--card);
    cursor: pointer;
    transition: var(--transition);
    font-weight: 500;
    font-family: inherit;
    appearance: none;
    -webkit-appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="%2364748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
    background-repeat: no-repeat;
    background-position: right 16px center;
}

.sort-dropdown:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

@media (max-width: 768px) {
    .search-sort-container {
        flex-direction: column;
        align-items: stretch;
        padding: 16px;
    }
    .search-wrapper, .sort-wrapper {
        width: 100%;
    }
    .search-input, .sort-dropdown {
        padding: 12px 16px 12px 42px;
    }
}
.action-buttons { display: flex; gap: 12px; margin-bottom: 24px; justify-content: flex-end; flex-wrap: wrap; }
.action-btn { padding: 12px 24px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: var(--transition); display: flex; align-items: center; gap: 8px; font-size: 14px; }
.btn-add { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; box-shadow: 0 4px 12px rgba(220,38,38,0.25); }
.btn-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(220,38,38,0.35); }

/* CARDS GRID */
.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px; }
.medicine-card { background: var(--card); border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow-lg); border: 1px solid var(--border); transition: var(--transition); position: relative; overflow: hidden; }
.medicine-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-xl); border-color: var(--primary); }
.medicine-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.medicine-name { font-size: 18px; font-weight: 700; color: var(--dark); line-height: 1.3; }
.medicine-actions button { width: 32px; height: 32px; border: none; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); font-size: 14px; background: transparent; color: var(--danger); border: 1px solid var(--danger); }
.medicine-actions button:hover { background: var(--danger); color: white; transform: scale(1.05); }
.medicine-body { margin-bottom: 16px; }
.stock-control { display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 12px; }
.stock-btn { width: 40px; height: 40px; border: none; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition); font-size: 16px; font-weight: bold; }
.btn-plus { background: linear-gradient(135deg, var(--success), #059669); color: white; box-shadow: 0 2px 8px rgba(16,185,129,0.3); }
.btn-plus:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16,185,129,0.4); }
.btn-minus { background: linear-gradient(135deg, var(--warning), #d97706); color: white; box-shadow: 0 2px 8px rgba(245,158,11,0.3); }
.btn-minus:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(245,158,11,0.4); }
.stock-value { font-size: 28px; font-weight: 800; color: var(--primary); min-width: 60px; text-align: center; cursor: pointer; padding: 8px; border-radius: 12px; transition: all 0.3s ease; }
.stock-value:hover { background: var(--primary-light); }
.inline-edit { display: none; align-items: center; justify-content: center; gap: 10px; margin-top: 12px; }
.inline-edit.active { display: flex; }
.inline-edit input { width: 80px; padding: 10px; border: 2px solid var(--border); border-radius: 10px; text-align: center; font-weight: 600; font-size: 16px; }
.inline-edit button { padding: 8px 16px; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: var(--transition); }
.btn-save { background: linear-gradient(135deg, var(--success), #059669); color: white; }
.btn-save:hover { transform: translateY(-2px); }
.btn-cancel { background: var(--muted); color: white; }
.btn-cancel:hover { background: var(--danger); transform: translateY(-2px); }
.quick-edit { text-align: center; margin-top: 8px; }
.quick-edit-text { font-size: 11px; color: var(--muted); cursor: pointer; transition: all 0.3s ease; display: inline-block; padding: 6px 12px; border-radius: 20px; background: var(--light); }
.quick-edit-text:hover { background: var(--primary-light); color: var(--primary); }
.medicine-meta { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border); }
.meta-item { padding: 5px 12px; background: var(--light); border-radius: 20px; font-size: 12px; font-weight: 500; display: flex; align-items: center; gap: 6px; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-safe { background: rgba(16,185,129,0.15); color: #059669; }
.status-low { background: rgba(245,158,11,0.15); color: #d97706; }
.status-out { background: rgba(239,68,68,0.15); color: #dc2626; }

/* PAGINATION */
.pagination-container { display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 24px; }
.pagination-btn { padding: 8px 14px; border: 2px solid var(--border); border-radius: 10px; background: var(--card); cursor: pointer; transition: var(--transition); font-size: 14px; font-weight: 600; }
.pagination-btn:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
.pagination-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

/* MODAL */
.modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 200; padding: 20px; backdrop-filter: blur(4px); }
.modal-bg.active { display: flex; }
.modal { background: var(--card); padding: 32px; border-radius: var(--radius-lg); width: 100%; max-width: 450px; position: relative; border: 1px solid var(--border); box-shadow: var(--shadow-xl); }
.modal h3 { font-size: 24px; font-weight: 700; color: var(--dark); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
.modal p { font-size: 13px; color: var(--muted); margin-bottom: 24px; }
.modal label { font-size: 14px; font-weight: 600; color: var(--muted); display: block; margin-bottom: 8px; }
.modal input, .modal select { width: 100%; padding: 12px 14px; margin-bottom: 20px; border: 2px solid var(--border); border-radius: var(--radius); font-family: inherit; font-size: 14px; transition: var(--transition); background: var(--light); }
.modal input:focus, .modal select:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(220,38,38,0.1); }
.save-btn { width: 100%; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: 12px; border: none; border-radius: var(--radius); cursor: pointer; font-weight: 600; font-size: 15px; margin-top: 20px; transition: var(--transition); display: flex; align-items: center; justify-content: center; gap: 8px; }
.save-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(220,38,38,0.3); }
.close { position: absolute; top: 16px; right: 16px; font-size: 24px; cursor: pointer; color: var(--muted); transition: var(--transition); }
.close:hover { color: var(--primary); transform: scale(1.1); }

/* FOOTER */
footer { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; margin-top: 60px; position: relative; overflow: hidden; }
.footer-pattern { position: absolute; inset: 0; background-image: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 50%); }
.footer-wrap { max-width: 1200px; margin: auto; padding: 64px 6% 32px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 48px; position: relative; z-index: 1; }
.footer-brand { display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; margin-bottom: 16px; }
.footer-brand-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
footer h4 { font-size: 16px; font-weight: 700; margin-bottom: 20px; }
footer p { font-size: 14px; line-height: 1.8; color: rgba(255,255,255,0.85); }
.footer-contact p { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
.footer-contact i { color: rgba(255,255,255,0.9); margin-top: 4px; font-size: 14px; }
.social-links { display: flex; gap: 12px; margin-top: 8px; }
.social-links a { width: 42px; height: 42px; border-radius: 12px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; transition: all 0.3s ease; text-decoration: none; }
.social-links a:hover { background: white; color: var(--primary); transform: translateY(-4px); }
.footer-bottom { text-align: center; padding: 24px 6%; border-top: 1px solid rgba(255,255,255,0.2); font-size: 13px; color: rgba(255,255,255,0.7); position: relative; z-index: 1; }
@keyframes slideUp { to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
    .navbar { padding: 16px 5%; }
    .welcome-text { display: none; }
    .main-section { padding: 120px 5% 60px; }
    .section-title { font-size: 26px; }
    .stats-container { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .stat-card { padding: 16px; }
    .stat-value { font-size: 24px; }
    .cards-grid { grid-template-columns: 1fr; gap: 16px; }
    .search-sort-container { flex-direction: column; align-items: stretch; }
    .search-input { max-width: 100%; }
    .action-buttons { justify-content: center; }
    .modal { width: 90%; padding: 24px; }
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
            <div class="badge"><i class="fa-solid fa-pills"></i> Manajemen Stok</div>
            <h1 class="section-title">Stok Obat UKS</h1>
            <p class="section-sub">Kelola stok obat, tambah obat baru, dan pantau ketersediaan obat dengan mudah</p>
        </div>

        <div class="stats-container">
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-pills"></i></div><div class="stat-value"><?= $total_obat ?></div><div class="stat-label">Total Obat</div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-boxes"></i></div><div class="stat-value"><?= $total_stok ?></div><div class="stat-label">Total Stok</div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-exclamation-triangle"></i></div><div class="stat-value"><?= $low_stock ?></div><div class="stat-label">Stok Rendah</div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-times-circle"></i></div><div class="stat-value"><?= $out_of_stock ?></div><div class="stat-label">Stok Habis</div></div>
        </div>

        <div class="search-sort-container">
    <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" class="search-input" placeholder="Cari nama obat atau kategori..." value="<?= htmlspecialchars($search_term) ?>" autocomplete="off">
        <?php if (!empty($search_term)): ?>
            <button class="search-clear" onclick="clearSearch()"><i class="fas fa-times"></i></button>
        <?php endif; ?>
    </div>
    <div class="sort-wrapper">
        <i class="fas fa-arrow-down-wide-short sort-icon"></i>
        <select id="sortDropdown" class="sort-dropdown">
            <option value="nama_obat" <?= $sort_by == 'nama_obat' ? 'selected' : '' ?>>Urutkan berdasarkan Nama</option>
            <option value="stok" <?= $sort_by == 'stok' ? 'selected' : '' ?>>Urutkan berdasarkan Stok (Terendah)</option>
            <option value="kategori" <?= $sort_by == 'kategori' ? 'selected' : '' ?>>Urutkan berdasarkan Kategori</option>
        </select>
    </div>
</div>

        <div class="action-buttons">
            <button class="action-btn btn-add" onclick="openModal()"><i class="fa-solid fa-plus"></i> Tambah Obat Baru</button>
        </div>

        <div class="cards-grid">
            <?php if (mysqli_num_rows($data_obat) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($data_obat)): ?>
                <?php
                $stok = $row['stok'];
                if ($stok > 10) { $statusText = 'Tersedia'; $statusClass = 'status-safe'; }
                elseif ($stok >= 1 && $stok <= 10) { $statusText = 'Hampir Habis'; $statusClass = 'status-low'; }
                else { $statusText = 'Habis'; $statusClass = 'status-out'; }
                ?>
                <div class="medicine-card">
                    <div class="medicine-card-header">
                        <div class="medicine-name"><?= htmlspecialchars($row['nama_obat']) ?></div>
                        <div class="medicine-actions">
                            <button onclick="hapusObat(<?= $row['id_obat'] ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="medicine-body">
                        <div class="stock-control">
                            <button class="stock-btn btn-minus" onclick="decreaseStock(<?= $row['id_obat'] ?>)"><i class="fas fa-minus"></i></button>
                            <div class="stock-value" id="stock-<?= $row['id_obat'] ?>" ondblclick="quickEditStock(<?= $row['id_obat'] ?>)"><?= $row['stok'] ?></div>
                            <button class="stock-btn btn-plus" onclick="increaseStock(<?= $row['id_obat'] ?>)"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="inline-edit" id="edit-<?= $row['id_obat'] ?>">
                            <input type="number" id="input-<?= $row['id_obat'] ?>" min="0" value="<?= $row['stok'] ?>">
                            <button class="btn-save" onclick="saveStock(<?= $row['id_obat'] ?>)">Simpan</button>
                            <button class="btn-cancel" onclick="cancelEdit(<?= $row['id_obat'] ?>)">Batal</button>
                        </div>
                        <div class="quick-edit"><span class="quick-edit-text" onclick="quickEditStock(<?= $row['id_obat'] ?>)">💡 Klik 2x pada stok untuk edit</span></div>
                    </div>
                    <div class="medicine-meta">
                        <div class="meta-item"><i class="fas fa-tag"></i><span><?= htmlspecialchars($row['kategori']) ?></span></div>
                        <div class="meta-item"><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; grid-column: 1/-1;">
                    <i class="fa-solid fa-inbox" style="font-size: 64px; color: var(--muted); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--dark); margin-bottom: 12px;">Belum Ada Data Obat</h3>
                    <p style="color: var(--muted);">Klik tombol "Tambah Obat Baru" untuk mulai menambahkan obat.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <?php if ($page > 1): ?>
                <button class="pagination-btn" onclick="changePage(<?= $page - 1 ?>)">« Sebelumnya</button>
            <?php endif; ?>
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <button class="pagination-btn <?= $page == $i ? 'active' : '' ?>" onclick="changePage(<?= $i ?>)"><?= $i ?></button>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <button class="pagination-btn" onclick="changePage(<?= $page + 1 ?>)">Selanjutnya »</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <div class="modal-bg" id="modalTambah">
        <div class="modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3><i class="fa-solid fa-plus-circle"></i> Tambah Obat Baru</h3>
            <p>Status akan otomatis terisi berdasarkan jumlah stok yang dimasukkan.</p>
            <form method="POST">
                <label>Nama Obat</label>
                <input type="text" name="nama_obat" placeholder="Contoh: Paracetamol" required>
                <label>Stok Awal</label>
                <input type="number" name="stok" placeholder="Jumlah stok awal" required min="0">
                <label>Kategori</label>
                <select name="kategori" required>
                    <option value="">Pilih Kategori</option>
                    <option value="Tablet">Tablet</option>
                    <option value="Kapsul">Kapsul</option>
                    <option value="Sirup">Sirup</option>
                    <option value="Salep">Salep</option>
                    <option value="Obat Cair">Obat Cair</option>
                    <option value="Vitamin">Vitamin</option>
                    <option value="Antibiotik">Antibiotik</option>
                    <option value="Pereda Nyeri">Pereda Nyeri</option>
                    <option value="Obat Demam">Obat Demam</option>
                    <option value="Obat Flu">Obat Flu</option>
                    <option value="Obat Luka">Obat Luka</option>
                    <option value="Alat Kesehatan">Alat Kesehatan</option>
                </select>
                <button type="submit" name="tambah_obat" class="save-btn"><i class="fa-solid fa-save"></i> Simpan Obat</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="footer-pattern"></div>
        <div class="footer-wrap">
            <div><div class="footer-brand"><div class="footer-brand-icon"><i class="fa-solid fa-heart-pulse"></i></div>UKS SmartCare</div><p>Sistem layanan kesehatan sekolah berbasis digital untuk pengelolaan obat, jadwal piket, dan pelayanan siswa yang lebih efisien.</p></div>
            <div class="footer-contact"><h4>Kontak Kami</h4><p><i class="fa-solid fa-location-dot"></i> SMKN 1 Cibinong, Jawa Barat</p><p><i class="fa-solid fa-phone"></i> 0812-3456-7890</p><p><i class="fa-solid fa-envelope"></i> uks.smartcare@gmail.com</p></div>
            <div><h4>Ikuti Kami</h4><p style="margin-bottom: 16px;">Tetap terhubung untuk informasi terbaru</p><div class="social-links"><a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a><a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a><a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a><a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a></div></div>
        </div>
        <div class="footer-bottom">&copy; 2026 UKS SmartCare.</div>
    </footer>

    <script>
    const profileContainer = document.getElementById('profileContainer');
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileContainer && profileDropdown) {
        profileContainer.addEventListener('click', (e) => { e.stopPropagation(); profileDropdown.classList.toggle('active'); });
        document.addEventListener('click', () => profileDropdown.classList.remove('active'));
    }
    function openModal() { document.getElementById('modalTambah').classList.add('active'); }
    function closeModal() { document.getElementById('modalTambah').classList.remove('active'); }
    function increaseStock(id) { window.location.href = `?tambah_id=${id}`; }
    function decreaseStock(id) { window.location.href = `?kurang_id=${id}`; }
    function hapusObat(id) { if (confirm('Yakin ingin menghapus obat ini?')) window.location.href = `?hapus_id=${id}`; }
    function quickEditStock(id) { document.getElementById(`edit-${id}`).classList.add('active'); document.getElementById(`input-${id}`).focus(); }
    function saveStock(id) { let val = parseInt(document.getElementById(`input-${id}`).value); if (isNaN(val) || val < 0) { alert('Stok harus angka positif'); return; } window.location.href = `?update_stock=${id}&stok=${val}`; }
    function cancelEdit(id) { document.getElementById(`edit-${id}`).classList.remove('active'); }
    const searchInput = document.getElementById('searchInput');
if (searchInput) {
    let timer;
    searchInput.addEventListener('keyup', (e) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            let url = new URL(window.location);
            if (searchInput.value.trim()) {
                url.searchParams.set('search', searchInput.value.trim());
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }, 400);
    });
}
const sortDropdown = document.getElementById('sortDropdown');
    if (sortDropdown) { sortDropdown.addEventListener('change', () => { let url = new URL(window.location); url.searchParams.set('sort', sortDropdown.value); url.searchParams.set('page','1'); window.location.href = url.toString(); }); }
    function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        let url = new URL(window.location);
        url.searchParams.delete('search');
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }
}
    function changePage(page) { let url = new URL(window.location); url.searchParams.set('page', page); window.location.href = url.toString(); }
    document.addEventListener('DOMContentLoaded', () => { const container = document.getElementById('particles'); if (container) { for (let i = 0; i < 20; i++) { let p = document.createElement('div'); p.className = 'particle'; p.style.left = Math.random() * 100 + '%'; p.style.animationDelay = Math.random() * 15 + 's'; p.style.animationDuration = (15 + Math.random() * 10) + 's'; container.appendChild(p); } } });
    window.addEventListener('scroll', () => { const header = document.getElementById('header'); if (header) header.classList.toggle('scrolled', window.scrollY > 50); });
    </script>
</body>
</html>