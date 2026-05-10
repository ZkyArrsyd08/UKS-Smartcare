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

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// AMBIL FILTER BULAN & TAHUN
$filter_bulan = isset($_GET['bulan']) ? clean_input($_GET['bulan']) : '';
$filter_tahun = isset($_GET['tahun']) ? clean_input($_GET['tahun']) : '';

// CEK APAKAH ADA PERMINTAAN EXPORT EXCEL
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Query dengan filter
    $where_filter = "WHERE po.status_permintaan IN ('approved', 'rejected', 'expired')";
    if (!empty($filter_bulan) && !empty($filter_tahun)) {
        $where_filter .= " AND MONTH(po.waktu_pengajuan) = '$filter_bulan' AND YEAR(po.waktu_pengajuan) = '$filter_tahun'";
    } elseif (!empty($filter_bulan)) {
        $where_filter .= " AND MONTH(po.waktu_pengajuan) = '$filter_bulan'";
    } elseif (!empty($filter_tahun)) {
        $where_filter .= " AND YEAR(po.waktu_pengajuan) = '$filter_tahun'";
    }
    
    // Ambil semua data history sesuai filter
    $export_query = mysqli_query($conn, "
        SELECT po.*, o.nama_obat
        FROM permintaan_obat po
        LEFT JOIN obat o ON po.id_obat = o.id_obat
        $where_filter
        ORDER BY po.waktu_pengajuan DESC
    ");
    
    $export_data = [];
    while ($row = mysqli_fetch_assoc($export_query)) {
        $export_data[] = $row;
    }
    
    // Export ke Excel
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
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="History_Permintaan_Obat' . $filter_text . '_' . date('Y-m-d') . '.xls"');
    
    echo '<h2>Laporan History Permintaan Obat</h2>';
    if (!empty($filter_bulan) || !empty($filter_tahun)) {
        echo '<h3>Periode: ';
        if (!empty($filter_bulan)) {
            $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            echo $nama_bulan[(int)$filter_bulan];
        }
        if (!empty($filter_tahun)) echo ' ' . $filter_tahun;
        if (empty($filter_bulan) && empty($filter_tahun)) echo 'Semua Data';
        echo '</h3>';
    }
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>Nama</th>';
    echo '<th>Jabatan/Status</th>';
    echo '<th>Kelas</th>';
    echo '<th>Obat yang Diminta</th>';
    echo '<th>Keluhan</th>';
    echo '<th>Status</th>';
    echo '<th>Waktu Pengajuan</th>';
    echo '<th>Waktu Pengambilan</th>';
    echo '</tr>';
    
    $no = 1;
    foreach ($export_data as $data) {
        $status_text = '';
        if ($data['status_permintaan'] == 'approved') $status_text = 'Disetujui';
        elseif ($data['status_permintaan'] == 'rejected') $status_text = 'Ditolak';
        elseif ($data['status_permintaan'] == 'expired') $status_text = 'Hangus (Lewat 10 Menit)';
        else $status_text = ucfirst($data['status_permintaan']);
        
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($data['nama']) . '</td>';
        echo '<td>' . htmlspecialchars($data['status'] == 'siswa' ? 'Siswa' : 'Guru') . '</td>';
        echo '<td>' . htmlspecialchars($data['kelas'] ?: ($data['jabatan'] ?: '-')) . '</td>';
        echo '<td>' . htmlspecialchars($data['nama_obat'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($data['keluhan']) . '</td>';
        echo '<td>' . $status_text . '</td>';
        echo '<td>' . date('d/m/Y H:i', strtotime($data['waktu_pengajuan'])) . '</td>';
        echo '<td>' . ($data['waktu_pengambilan'] ? date('d/m/Y H:i', strtotime($data['waktu_pengambilan'])) : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

// ========== UPDATE STATUS YANG EXPIRED (LEBIH DARI 10 MENIT) ==========
mysqli_query($conn, "
UPDATE permintaan_obat 
SET status_permintaan = 'expired' 
WHERE status_permintaan = 'pending' 
AND TIMESTAMPDIFF(MINUTE, waktu_pengajuan, NOW()) >= 10
");

// Ambil data permintaan obat yang pending
$q_permintaan = mysqli_query($conn, "
SELECT po.*, o.nama_obat, o.stok, o.kategori
FROM permintaan_obat po
LEFT JOIN obat o ON po.id_obat = o.id_obat
WHERE po.status_permintaan = 'pending'
ORDER BY po.waktu_pengajuan DESC
");

if (!$q_permintaan) {
    die("Query error: " . mysqli_error($conn));
}

$permintaan_data = [];
while ($row = mysqli_fetch_assoc($q_permintaan)) {
    $waktu_pengajuan = strtotime($row['waktu_pengajuan']);
    $waktu_sekarang = time();
    $waktu_tersisa = max(0, 600 - ($waktu_sekarang - $waktu_pengajuan));

    $row['waktu_tersisa'] = $waktu_tersisa;
    $row['menit_tersisa'] = floor($waktu_tersisa / 60);
    $row['detik_tersisa'] = $waktu_tersisa % 60;

    $permintaan_data[] = $row;
}

// Ambil data history pesanan obat DENGAN FILTER
$where_filter = "WHERE po.status_permintaan IN ('approved', 'rejected', 'expired')";
if (!empty($filter_bulan) && !empty($filter_tahun)) {
    $where_filter .= " AND MONTH(po.waktu_pengajuan) = '$filter_bulan' AND YEAR(po.waktu_pengajuan) = '$filter_tahun'";
} elseif (!empty($filter_bulan)) {
    $where_filter .= " AND MONTH(po.waktu_pengajuan) = '$filter_bulan'";
} elseif (!empty($filter_tahun)) {
    $where_filter .= " AND YEAR(po.waktu_pengajuan) = '$filter_tahun'";
}

$q_history = mysqli_query($conn, "
SELECT po.*, o.nama_obat
FROM permintaan_obat po
LEFT JOIN obat o ON po.id_obat = o.id_obat
$where_filter
ORDER BY po.waktu_pengajuan DESC
");

if (!$q_history) {
    die("Query history error: " . mysqli_error($conn));
}

$history_data = [];
while ($row = mysqli_fetch_assoc($q_history)) {
    $history_data[] = $row;
}

// Ambil daftar tahun yang tersedia untuk filter
$q_tahun = mysqli_query($conn, "
SELECT DISTINCT YEAR(waktu_pengajuan) as tahun 
FROM permintaan_obat 
WHERE status_permintaan IN ('approved', 'rejected', 'expired')
ORDER BY tahun DESC
");
$tahun_list = [];
while ($row = mysqli_fetch_assoc($q_tahun)) {
    $tahun_list[] = $row['tahun'];
}

// ========== PROSES APPROVAL ATAU REJECT ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_permintaan = clean_input($_POST['id_permintaan'] ?? '');
    $aksi = clean_input($_POST['aksi'] ?? '');
    
    if (empty($id_permintaan) || empty($aksi) || !in_array($aksi, ['approve', 'reject'])) {
        $error = "Input tidak valid";
    } else {
        // Cek apakah masih pending dan belum expired
        $check_query = mysqli_query($conn, "
            SELECT po.*, o.kategori, o.id_obat 
            FROM permintaan_obat po
            JOIN obat o ON po.id_obat = o.id_obat
            WHERE po.id = '$id_permintaan' 
            AND po.status_permintaan = 'pending'
            AND TIMESTAMPDIFF(MINUTE, po.waktu_pengajuan, NOW()) < 10
        ");
        
        if (mysqli_num_rows($check_query) === 0) {
            $error = "Permintaan tidak ditemukan, sudah diproses, atau sudah expired (melewati 10 menit).";
        } else {
            $permintaan_check = mysqli_fetch_assoc($check_query);
            
            if ($aksi === 'approve') {
                // Ambil kategori obat dulu
                $q_kategori = mysqli_query($conn, "
                    SELECT o.kategori, o.id_obat, o.stok
                    FROM permintaan_obat po
                    JOIN obat o ON po.id_obat = o.id_obat
                    WHERE po.id = '$id_permintaan'
                ");
                $obat_data = mysqli_fetch_assoc($q_kategori);
                $kategori = strtolower($obat_data['kategori']);
                
                // Cek apakah termasuk kategori yang BOLEH ngurang stok
                $kategori_boleh_ngurang = ['tablet', 'sachet', 'lembar', 'gulung', 'swabs'];
                $boleh_ngurang = false;
                foreach ($kategori_boleh_ngurang as $kat) {
                    if (strpos($kategori, $kat) !== false) {
                        $boleh_ngurang = true;
                        break;
                    }
                }
                
                mysqli_begin_transaction($conn);
                
                try {
                    // Update status permintaan
                    mysqli_query($conn, "
                        UPDATE permintaan_obat 
                        SET status_permintaan = 'approved', 
                            waktu_pengambilan = NOW()
                        WHERE id = '$id_permintaan'
                    ");
                    
                    // KURANGI STOK ASLI hanya untuk kategori yang boleh
                    if ($boleh_ngurang) {
                        mysqli_query($conn, "
                            UPDATE obat 
                            SET stok = stok - 1 
                            WHERE id_obat = '{$obat_data['id_obat']}'
                        ");
                    }
                    
                    mysqli_commit($conn);
                    $success = "Permintaan berhasil disetujui.";
                    
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = $e->getMessage();
                }
            } else {
                // REJECT
                $query = mysqli_query($conn, "
                    UPDATE permintaan_obat 
                    SET status_permintaan = 'rejected'
                    WHERE id = '$id_permintaan'
                ");
                
                if (!$query) {
                    $error = "Error reject: " . mysqli_error($conn);
                } else {
                    $success = "Permintaan obat berhasil ditolak.";
                }
            }
        }
    }
    
    // Redirect dengan mempertahankan filter
    $redirect_url = "terimaobat.php";
    if (!empty($filter_bulan)) $redirect_url .= "?bulan=" . $filter_bulan;
    if (!empty($filter_tahun)) {
        if (!empty($filter_bulan)) $redirect_url .= "&tahun=" . $filter_tahun;
        else $redirect_url .= "?tahun=" . $filter_tahun;
    }
    header("Location: " . $redirect_url);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Terima Obat | UKS SmartCare</title>

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

    /* MAIN SECTION */
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
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
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

    /* ALERT NOTE */
    .alert-note {
      margin-bottom: 32px;
      background: linear-gradient(135deg, var(--warning-light), white);
      border: 1px solid rgba(245, 158, 11, 0.2);
      border-radius: var(--radius);
      padding: 20px 24px;
      display: flex;
      gap: 14px;
      align-items: center;
      opacity: 0;
      transform: translateY(30px);
      animation: slideUp 0.8s ease 0.1s forwards;
    }

    .alert-icon {
      width: 44px;
      height: 44px;
      background: var(--warning);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
      flex-shrink: 0;
    }

    .alert-text {
      font-size: 14px;
      color: var(--dark);
      font-weight: 500;
    }

    /* TAB NAVIGATION */
    .tab-navigation {
      display: flex;
      gap: 12px;
      margin-bottom: 32px;
      border-bottom: 2px solid var(--border);
      padding-bottom: 16px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .tab-btn {
      padding: 12px 24px;
      border: none;
      background: none;
      font-family: inherit;
      font-size: 15px;
      font-weight: 600;
      color: var(--muted);
      cursor: pointer;
      transition: all 0.3s ease;
      border-radius: 12px;
      position: relative;
    }

    .tab-btn:hover {
      color: var(--dark);
      background: var(--light);
    }

    .tab-btn.active {
      color: var(--primary);
      background: var(--primary-light);
    }

    .tab-btn.active::after {
      content: '';
      position: absolute;
      bottom: -16px;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--primary);
      border-radius: 3px;
    }

    /* TAB CONTENT */
    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
      animation: slideUp 0.5s ease;
    }

    /* REQUEST LIST */
    .request-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
      gap: 24px;
      margin-bottom: 48px;
    }

    .request-card {
      background: var(--card);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-xl);
      border: 1px solid var(--border);
      overflow: hidden;
      opacity: 0;
      transform: translateY(30px);
      animation: slideUp 0.8s ease forwards;
    }

    .request-card:nth-child(1) { animation-delay: 0.1s; }
    .request-card:nth-child(2) { animation-delay: 0.2s; }
    .request-card:nth-child(3) { animation-delay: 0.3s; }
    .request-card:nth-child(4) { animation-delay: 0.4s; }

    .request-header {
      padding: 24px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
    }

    .request-info {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .request-name {
      font-size: 16px;
      font-weight: 600;
      color: var(--dark);
    }

    .request-role {
      font-size: 13px;
      color: var(--muted);
      background: var(--light);
      padding: 4px 10px;
      border-radius: 20px;
    }

    .request-time {
      font-size: 13px;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 6px;
      background: var(--light);
      padding: 6px 12px;
      border-radius: 20px;
    }

    .request-body {
      padding: 24px;
    }

    .request-detail {
      margin-bottom: 20px;
    }

    .request-label {
      font-size: 14px;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .request-value {
      font-size: 15px;
      color: var(--dark);
      line-height: 1.5;
    }

    .request-actions {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .action-btn {
      flex: 1;
      padding: 12px 20px;
      border: none;
      border-radius: 12px;
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-approve {
      background: linear-gradient(135deg, var(--success), #059669);
      color: white;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-approve:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
    }

    .btn-approve:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    .btn-reject {
      background: linear-gradient(135deg, var(--danger), #dc2626);
      color: white;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-reject:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
    }

    /* FILTER SECTION */
    .filter-section {
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 20px 24px;
      margin-bottom: 32px;
      display: flex;
      align-items: flex-end;
      gap: 16px;
      flex-wrap: wrap;
      opacity: 0;
      transform: translateY(30px);
      animation: slideUp 0.8s ease 0.1s forwards;
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

    /* HISTORY SECTION */
    .history-section {
      margin-top: 0;
    }

    .history-header-wrapper {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      flex-wrap: wrap;
      gap: 16px;
    }

    .history-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .history-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
      gap: 24px;
      margin-bottom: 48px;
    }

    .history-card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 16px;
      opacity: 0;
      transform: translateY(20px);
      animation: slideUp 0.6s ease forwards;
    }

    .history-card:nth-child(1) { animation-delay: 0.1s; }
    .history-card:nth-child(2) { animation-delay: 0.2s; }
    .history-card:nth-child(3) { animation-delay: 0.3s; }

    .history-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      flex-wrap: wrap;
      gap: 8px;
    }

    .history-user {
      font-size: 15px;
      font-weight: 600;
      color: var(--dark);
    }

    .history-status {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .status-approved {
      background: var(--success-light);
      color: var(--success);
    }

    .status-rejected {
      background: var(--danger-light);
      color: var(--danger);
    }

    .status-expired {
      background: var(--warning-light);
      color: var(--warning);
    }

    .history-detail {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.5;
    }

    .history-detail strong {
      color: var(--dark);
    }

    .history-detail div {
      margin-bottom: 6px;
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      opacity: 0;
      transform: translateY(30px);
      animation: slideUp 0.8s ease 0.2s forwards;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100%;
    }

    .empty-icon {
      font-size: 64px;
      color: var(--muted);
      margin-bottom: 24px;
    }

    .empty-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 12px;
      text-align: center;
    }

    .empty-text {
      font-size: 15px;
      color: var(--muted);
      max-width: 400px;
      margin: 0 auto;
      text-align: center;
    }

    /* SUCCESS MESSAGE */
    .success-message {
      margin-bottom: 24px;
      background: linear-gradient(135deg, var(--success-light), white);
      border: 1px solid rgba(16, 185, 129, 0.2);
      border-radius: var(--radius);
      padding: 16px 24px;
      display: flex;
      gap: 12px;
      align-items: center;
      opacity: 0;
      transform: translateY(20px);
      animation: slideUp 0.5s ease forwards;
    }

    .success-message.show {
      opacity: 1;
      transform: translateY(0);
    }

    .success-icon {
      width: 36px;
      height: 36px;
      background: var(--success);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 16px;
      flex-shrink: 0;
    }

    .success-text {
      font-size: 14px;
      color: var(--dark);
      font-weight: 500;
    }

    /* ERROR MESSAGE */
    .error-message {
      margin-bottom: 24px;
      background: linear-gradient(135deg, var(--danger-light), white);
      border: 1px solid rgba(239, 68, 68, 0.2);
      border-radius: var(--radius);
      padding: 16px 24px;
      display: flex;
      gap: 12px;
      align-items: center;
      opacity: 0;
      transform: translateY(20px);
      animation: slideUp 0.5s ease forwards;
    }

    .error-message.show {
      opacity: 1;
      transform: translateY(0);
    }

    .error-icon {
      width: 36px;
      height: 36px;
      background: var(--danger);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 16px;
      flex-shrink: 0;
    }

    .error-text {
      font-size: 14px;
      color: var(--dark);
      font-weight: 500;
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

    /* ANIMATIONS */
    @keyframes slideUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .section {
        padding: 120px 5% 60px;
      }

      .main-section {
        padding: 120px 5% 60px;
    }

      .tab-navigation {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        border: none;
        padding-bottom: 0;
      }

      .tab-btn {
        width: 100%;
        text-align: center;
        border-radius: 12px;
      }

      .tab-btn.active::after {
        display: none;
      }

      .request-list, .history-list {
        grid-template-columns: 1fr;
      }

      .request-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
      }

      .request-actions {
        flex-direction: column;
      }

      .footer-wrap {
        padding: 48px 5% 24px;
        gap: 32px;
      }

      .filter-section {
        flex-direction: column;
        align-items: stretch;
      }

      .filter-buttons {
        flex-direction: column;
      }

      .btn-filter, .btn-export-excel {
        justify-content: center;
      }

      .history-header-wrapper {
        flex-direction: column;
        align-items: flex-start;
      }

      .btn-export-excel {
        width: 100%;
        justify-content: center;
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

  <main class="section">
    <div class="section-header">
      <div class="badge">
        <i class="fa-solid fa-file-prescription"></i>
        Kelola Permintaan Obat
      </div>
      <h1 class="section-title">Terima Obat</h1>
      <p class="section-sub">Tolong proses permintaan obat dari siswa dan guru yang masih dalam waktu 10 menit. Jika tidak diproses dalam 10 menit, permintaan akan hangus (expired).</p>
    </div>

    <?php if (isset($success)): ?>
      <div class="success-message show">
        <div class="success-icon"><i class="fa-solid fa-check"></i></div>
        <div class="success-text"><?= htmlspecialchars($success) ?></div>
      </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
      <div class="error-message show">
        <div class="error-icon"><i class="fa-solid fa-exclamation-triangle"></i></div>
        <div class="error-text"><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <div class="alert-note">
      <div class="alert-icon"><i class="fa-solid fa-info"></i></div>
      <div class="alert-text">
        <strong>Perhatian:</strong> Semua permintaan obat harus diproses dalam waktu <strong>10 menit</strong> dari waktu pengajuan. Jika melebihi batas waktu, permintaan akan otomatis hangus (expired) dan tidak dapat diproses lagi.
      </div>
    </div>

    <div class="tab-navigation">
      <button class="tab-btn active" data-tab="pending">
        <i class="fa-solid fa-clock"></i> Permintaan Menunggu (<?= count($permintaan_data) ?>)
      </button>
      <button class="tab-btn" data-tab="history">
        <i class="fa-solid fa-history"></i> History Pesanan
      </button>
    </div>

    <!-- PENDING REQUESTS TAB -->
    <div id="pending" class="tab-content active">
      <div class="request-list">
        <?php if (empty($permintaan_data)): ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
            <h3 class="empty-title">Tidak Ada Permintaan Obat</h3>
            <p class="empty-text">Tidak ada permintaan obat yang menunggu persetujuan saat ini.</p>
          </div>
        <?php else: ?>
          <?php foreach ($permintaan_data as $permintaan): ?>
            <div class="request-card" data-expiry="<?= $permintaan['waktu_tersisa'] ?>">
              <div class="request-header">
                <div class="request-info">
                  <div class="request-name"><?= htmlspecialchars($permintaan['nama']) ?></div>
                  <div class="request-role"><?= htmlspecialchars($permintaan['jabatan'] ?? ($permintaan['status'] === 'siswa' ? 'Siswa' : 'Guru')) ?></div>
                </div>
                <div class="request-time">
                  <i class="fa-solid fa-clock"></i>
                  <?= date('d M Y H:i', strtotime($permintaan['waktu_pengajuan'])) ?>
                </div>
              </div>
              
              <div class="request-body">
                <div class="request-detail">
                  <div class="request-label"><i class="fa-solid fa-user-graduate"></i> Kelas / Jabatan</div>
                  <div class="request-value"><?= htmlspecialchars($permintaan['kelas'] ?: ($permintaan['jabatan'] ?: '-')) ?></div>
                </div>
                
                <div class="request-detail">
                  <div class="request-label"><i class="fa-solid fa-notes-medical"></i> Keluhan</div>
                  <div class="request-value"><?= htmlspecialchars($permintaan['keluhan']) ?></div>
                </div>
                
                <div class="request-detail">
                  <div class="request-label"><i class="fa-solid fa-pills"></i> Obat yang Diminta</div>
                  <div class="request-value"><?= htmlspecialchars($permintaan['nama_obat'] ?? 'Obat tidak ditemukan') ?></div>
                </div>
                
                <div class="request-detail">
                  <div class="request-label"><i class="fa-solid fa-hourglass-half"></i> Waktu Tersisa</div>
                  <div class="request-value">
                    <span class="timer-countdown" data-seconds="<?= $permintaan['waktu_tersisa'] ?>" style="font-weight: 700; color: var(--primary); font-size: 18px;">
                      <?= sprintf('%02d:%02d', $permintaan['menit_tersisa'], $permintaan['detik_tersisa']) ?>
                    </span>
                  </div>
                </div>
                
                <div class="request-actions">
                  <form method="POST" style="flex: 1;" class="approve-form">
                    <input type="hidden" name="id_permintaan" value="<?= $permintaan['id'] ?>">
                    <button type="submit" name="aksi" value="approve" class="action-btn btn-approve">
                      <i class="fa-solid fa-check"></i> Terima / Setujui
                    </button>
                  </form>
                  <form method="POST" style="flex: 1;" class="reject-form">
                    <input type="hidden" name="id_permintaan" value="<?= $permintaan['id'] ?>">
                    <button type="submit" name="aksi" value="reject" class="action-btn btn-reject">
                      <i class="fa-solid fa-times"></i> Tolak
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- HISTORY TAB -->
    <div id="history" class="tab-content">
      <div class="filter-section">
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
          <a href="terimaobat.php" class="btn-filter btn-filter-reset">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </a>
        </div>
      </div>

      <?php if (!empty($filter_bulan) || !empty($filter_tahun)): ?>
        <div class="filter-info">
          <div class="filter-info-text">
            <i class="fa-solid fa-chart-line"></i>
            Menampilkan data untuk: 
            <?php 
              $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
              if (!empty($filter_bulan)) echo $nama_bulan[(int)$filter_bulan];
              if (!empty($filter_tahun)) echo ' ' . $filter_tahun;
              if (empty($filter_bulan) && empty($filter_tahun)) echo 'Semua Data';
            ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="history-section">
        <div class="history-header-wrapper">
          <h2 class="history-title">
            <i class="fa-solid fa-history"></i>
            History Pesanan Obat
          </h2>
          <a href="?export=excel&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="btn-export-excel">
            <i class="fa-solid fa-file-excel"></i> Export ke Excel
          </a>
        </div>
        
        <div class="history-list">
          <?php if (empty($history_data)): ?>
            <div class="empty-state">
              <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
              <h3 class="empty-title">Belum Ada History Pesanan</h3>
              <p class="empty-text">
                <?php if (!empty($filter_bulan) || !empty($filter_tahun)): ?>
                  Tidak ada data untuk periode yang dipilih.
                <?php else: ?>
                  Belum ada pesanan obat yang telah diproses.
                <?php endif; ?>
              </p>
            </div>
          <?php else: ?>
            <?php foreach ($history_data as $history): ?>
              <div class="history-card">
                <div class="history-header">
                  <div class="history-user"><?= htmlspecialchars($history['nama']) ?></div>
                  <div class="history-status status-<?= $history['status_permintaan'] ?>">
                    <?php 
                      if ($history['status_permintaan'] == 'approved') echo "Disetujui";
                      elseif ($history['status_permintaan'] == 'rejected') echo "Ditolak";
                      elseif ($history['status_permintaan'] == 'expired') echo "Hangus (Lewat 10 Menit)";
                      else echo ucfirst($history['status_permintaan']);
                    ?>
                  </div>
                </div>
                
                <div class="history-detail">
                  <div><strong>Obat:</strong> <?= htmlspecialchars($history['nama_obat'] ?? '-') ?></div>
                  <div><strong>Keluhan:</strong> <?= htmlspecialchars($history['keluhan']) ?></div>
                  <div><strong>Kelas/Jabatan:</strong> <?= htmlspecialchars($history['kelas'] ?: ($history['jabatan'] ?: '-')) ?></div>
                  <div><strong>Waktu Pengajuan:</strong> <?= date('d F Y H:i', strtotime($history['waktu_pengajuan'])) ?></div>
                  <?php if ($history['status_permintaan'] === 'approved' && !empty($history['waktu_pengambilan'])): ?>
                    <div><strong>Waktu Pengambilan:</strong> <?= date('d F Y H:i', strtotime($history['waktu_pengambilan'])) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
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
    // Profile dropdown
    const profileContainer = document.getElementById('profileContainer');
    const profileDropdown = document.getElementById('profileDropdown');

    if (profileContainer && profileDropdown) {
      profileContainer.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('active');
      });
      document.addEventListener('click', () => profileDropdown.classList.remove('active'));
    }

    // Tab navigation
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
      button.addEventListener('click', () => {
        const targetTab = button.getAttribute('data-tab');
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));
        button.classList.add('active');
        document.getElementById(targetTab).classList.add('active');
        
        // Simpan tab aktif ke localStorage
        localStorage.setItem('activeTab', targetTab);
      });
    });

    // Cek tab aktif dari localStorage
    const savedTab = localStorage.getItem('activeTab');
    if (savedTab && savedTab === 'history') {
      document.querySelector('.tab-btn[data-tab="history"]').click();
    }

    // Timer countdown untuk setiap request
    function updateAllTimers() {
      const timers = document.querySelectorAll('.timer-countdown');
      let hasExpired = false;
      
      timers.forEach(timer => {
        let seconds = parseInt(timer.dataset.seconds);
        if (seconds > 0) {
          seconds--;
          timer.dataset.seconds = seconds;
          const minutes = Math.floor(seconds / 60);
          const secs = seconds % 60;
          timer.textContent = `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
          
          if (seconds === 0) {
            hasExpired = true;
          }
        }
      });
      
      if (hasExpired) {
        location.reload();
      }
    }

    setInterval(updateAllTimers, 1000);

    // Filter functionality
    const btnApplyFilter = document.getElementById('btn_apply_filter');
    if (btnApplyFilter) {
      btnApplyFilter.addEventListener('click', () => {
        const bulan = document.getElementById('filter_bulan').value;
        const tahun = document.getElementById('filter_tahun').value;
        let url = 'terimaobat.php';
        let params = [];
        if (bulan) params.push('bulan=' + bulan);
        if (tahun) params.push('tahun=' + tahun);
        if (params.length > 0) url += '?' + params.join('&');
        window.location.href = url;
      });
    }

    // Particles
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

    function handleScroll() {
      const header = document.getElementById('header');
      if (window.scrollY > 50) header.classList.add('scrolled');
      else header.classList.remove('scrolled');
    }

    document.addEventListener('DOMContentLoaded', function() {
      createParticles();
      window.addEventListener('scroll', handleScroll);
    });
  </script>
</body>
</html>