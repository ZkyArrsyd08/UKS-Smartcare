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

// ========== FUNGSI BACA & SIMPAN used_week KE FILE JSON ==========
$used_week_file = '../admin/used_week.json';

function getUsedWeekFromFile() {
    global $used_week_file;
    if (file_exists($used_week_file)) {
        $data = json_decode(file_get_contents($used_week_file), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function saveUsedWeekToFile($data) {
    global $used_week_file;
    file_put_contents($used_week_file, json_encode($data));
}

$used_week = getUsedWeekFromFile();

// ========== LOGIKA RESET MINGGUAN ==========
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
        
        $used_week = [];
        saveUsedWeekToFile($used_week);
        
        setcookie("week_reset_info", "Minggu ke-$minggu_ke dimulai", time() + (86400 * 7), "/");
    }
}

// Update status permintaan yang expired (10 menit)
mysqli_query($conn, "
UPDATE permintaan_obat 
SET status_permintaan = 'expired' 
WHERE status_permintaan = 'pending' 
AND TIMESTAMPDIFF(MINUTE, waktu_pengajuan, NOW()) >= 10
");

// ========== KATEGORI ==========
$kategori_limit = ['tablet', 'sachet', 'lembar', 'gulung', 'swabs'];
$kategori_permanen = ['tube', 'botol', 'can'];

function perluLimit($kategori) {
    global $kategori_limit;
    $kategori_lower = strtolower($kategori);
    foreach ($kategori_limit as $kat) {
        if (strpos($kategori_lower, $kat) !== false) return true;
    }
    return false;
}

function isPermanen($kategori) {
    global $kategori_permanen;
    $kategori_lower = strtolower($kategori);
    foreach ($kategori_permanen as $kat) {
        if (strpos($kategori_lower, $kat) !== false) return true;
    }
    return false;
}

function getSisaLimit($id_obat, $stok_asli, $kategori, &$used_week) {
    if (!perluLimit($kategori)) {
        return $stok_asli;
    }
    
    $sudah_dipakai = isset($used_week[$id_obat]) ? (int)$used_week[$id_obat] : 0;
    $sisa = max(0, 20 - $sudah_dipakai);
    return min($sisa, $stok_asli);
}

function kurangiLimit($id_obat, $kategori, &$used_week) {
    if (!perluLimit($kategori)) {
        return true;
    }
    
    if (!isset($used_week[$id_obat])) {
        $used_week[$id_obat] = 0;
    }
    
    if ($used_week[$id_obat] >= 20) {
        return false;
    }
    
    $used_week[$id_obat]++;
    saveUsedWeekToFile($used_week);
    return true;
}

// FLAG UNTUK NOTIFIKASI BARU (hanya muncul sekali setelah submit)
$just_submitted = false;
$submit_status = null;

// Cek apakah form telah disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? mysqli_real_escape_string($conn, $_POST['nama']) : '';
    $role = isset($_POST['role']) ? mysqli_real_escape_string($conn, $_POST['role']) : 'siswa';
    $kelas = isset($_POST['kelas']) ? mysqli_real_escape_string($conn, $_POST['kelas']) : '';
    $jabatan = isset($_POST['jabatan']) ? mysqli_real_escape_string($conn, $_POST['jabatan']) : '';
    $keluhan = isset($_POST['keluhan']) ? mysqli_real_escape_string($conn, $_POST['keluhan']) : '';
    $id_obat = isset($_POST['obat']) ? mysqli_real_escape_string($conn, $_POST['obat']) : '';
    
    $errors = array();
    
    if (empty($nama)) $errors[] = "Nama lengkap harus diisi";
    if (empty($keluhan)) $errors[] = "Keluhan harus diisi";
    if (empty($id_obat)) $errors[] = "Silakan pilih obat";
    if ($role == 'siswa' && empty($kelas)) $errors[] = "Kelas harus diisi untuk siswa";
    if ($role == 'guru' && empty($jabatan)) $errors[] = "Jabatan harus diisi untuk guru";
    
    if (empty($errors)) {
      $q_obat = mysqli_query($conn, "SELECT id_obat, nama_obat, stok, kategori, status FROM obat WHERE id_obat = '$id_obat'");
      $obat_data = mysqli_fetch_assoc($q_obat);
      
      if (!$obat_data) {
          $error = "Obat tidak valid.";
      } else {
          // CEK JENIS KATEGORI
          if (perluLimit($obat_data['kategori'])) {
              // ========== KATEGORI LIMIT (tablet, sachet, dll) ==========
              if ($obat_data['stok'] <= 0) {
                  $error = "Maaf, stok obat ini sudah habis.";
              } else {
                  $sisa_limit = getSisaLimit($id_obat, $obat_data['stok'], $obat_data['kategori'], $used_week);
                  if ($sisa_limit <= 0) {
                      $error = "Maaf, limit mingguan untuk obat ini sudah habis (maksimal 20 per minggu). Silakan coba lagi minggu depan.";
                  } else {
                      if (kurangiLimit($id_obat, $obat_data['kategori'], $used_week)) {
                          // KURANGI STOK ASLI DI DATABASE
                          $stok_baru = $obat_data['stok'] - 1;
                          $update_stok = mysqli_query($conn, "UPDATE obat SET stok = '$stok_baru' WHERE id_obat = '$id_obat'");
                          
                          if ($update_stok) {
                              // Update status otomatis berdasarkan stok baru
                              if ($stok_baru <= 0) {
                                  mysqli_query($conn, "UPDATE obat SET status = 'habis' WHERE id_obat = '$id_obat'");
                              } elseif ($stok_baru <= 10) {
                                  mysqli_query($conn, "UPDATE obat SET status = 'hampir_habis' WHERE id_obat = '$id_obat'");
                              }
                              
                              $query = "INSERT INTO permintaan_obat 
                              (nama, status, kelas, jabatan, keluhan, id_obat, status_permintaan, waktu_pengajuan) 
                              VALUES 
                              ('$nama', '$role', '$kelas', '$jabatan', '$keluhan', '$id_obat', 'pending', NOW())";
                              
                              if (mysqli_query($conn, $query)) {
                                  $request_id = mysqli_insert_id($conn);
                                  $_SESSION['request_id'] = $request_id;
                                  $_SESSION['request_time'] = time();
                                  
                                  setcookie("request_id", $request_id, time() + (86400 * 7), "/");
                                  setcookie("request_time", time(), time() + (86400 * 7), "/");
                                  
                                  $just_submitted = true;
                                  $submit_status = 'success';
                                  
                                  header("Location: mintaobat.php?submitted=1");
                                  exit;
                              } else {
                                  $error = "Gagal mengirim permintaan obat. Error: " . mysqli_error($conn);
                              }
                          } else {
                              $error = "Gagal update stok obat.";
                          }
                      } else {
                          $error = "Maaf, limit mingguan untuk obat ini sudah habis (maksimal 20 per minggu).";
                      }
                  }
              }
          } else {
              // ========== KATEGORI PERMANEN (tube, botol, can) ==========
              // TIDAK MENGURANGI STOK, hanya catat permintaan
              $query = "INSERT INTO permintaan_obat 
              (nama, status, kelas, jabatan, keluhan, id_obat, status_permintaan, waktu_pengajuan) 
              VALUES 
              ('$nama', '$role', '$kelas', '$jabatan', '$keluhan', '$id_obat', 'pending', NOW())";
              
              if (mysqli_query($conn, $query)) {
                  $request_id = mysqli_insert_id($conn);
                  $_SESSION['request_id'] = $request_id;
                  $_SESSION['request_time'] = time();
                  
                  setcookie("request_id", $request_id, time() + (86400 * 7), "/");
                  setcookie("request_time", time(), time() + (86400 * 7), "/");
                  
                  $just_submitted = true;
                  $submit_status = 'success';
                  
                  header("Location: mintaobat.php?submitted=1");
                  exit;
              } else {
                  $error = "Gagal mengirim permintaan obat. Error: " . mysqli_error($conn);
              }
          }
      }
  }
}

// CEK APAKAH BARU SAJA SUBMIT (dari redirect)
$show_success_notif = isset($_GET['submitted']) && $_GET['submitted'] == 1;

// Ambil data request dari session/cookie
$request_id = isset($_SESSION['request_id']) ? $_SESSION['request_id'] : 
             (isset($_COOKIE['request_id']) ? $_COOKIE['request_id'] : null);
$request_time = isset($_SESSION['request_time']) ? $_SESSION['request_time'] : 
              (isset($_COOKIE['request_time']) ? $_COOKIE['request_time'] : null);

$current_status = null;
if ($request_id && isset($_SESSION['user_token'])) {
    $q_status = mysqli_query($conn, "
    SELECT status_permintaan 
    FROM permintaan_obat 
    WHERE id = '$request_id'
    ORDER BY waktu_pengajuan DESC 
    LIMIT 1
    ");
    if ($q_status) {
        $status_data = mysqli_fetch_assoc($q_status);
        $current_status = $status_data['status_permintaan'] ?? null;
        if ($current_status) {
            setcookie("request_status", $current_status, time() + (86400 * 7), "/");
        }
    }
}

if (!$current_status && isset($_COOKIE['request_status'])) {
    $current_status = $_COOKIE['request_status'];
}

$remaining_time = 0;
if ($request_id && $request_time && $current_status === 'pending') {
    $remaining_time = max(0, 600 - (time() - $request_time));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Minta Obat | UKS SmartCare</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <style>
    :root {
      --primary: #dc2626;
      --primary-dark: #b91c1c;
      --primary-light: #fef2f2;
      --accent: #0ea5e9;
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
      --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
      --shadow-lg: 0 20px 40px rgba(0,0,0,0.1);
      --shadow-xl: 0 25px 50px -12px rgba(0,0,0,0.25);
      --radius: 16px;
      --radius-lg: 24px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--dark);
      background: var(--light);
      overflow-x: hidden;
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
    .orb-1 { width: 600px; height: 600px; background: linear-gradient(135deg, rgba(220,38,38,0.3), rgba(251,113,133,0.2)); top: -200px; right: -100px; }
    .orb-2 { width: 500px; height: 500px; background: linear-gradient(135deg, rgba(14,165,233,0.2), rgba(56,189,248,0.15)); bottom: -150px; left: -100px; animation-delay: -7s; }
    .orb-3 { width: 400px; height: 400px; background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(52,211,153,0.1)); top: 40%; left: 30%; animation-delay: -14s; }
    @keyframes orbFloat { 0%,100% { transform: translate(0,0) scale(1); } 25% { transform: translate(30px,-30px) scale(1.05); } 50% { transform: translate(-20px,20px) scale(0.95); } 75% { transform: translate(20px,30px) scale(1.02); } }
    
    .particles { position: absolute; inset: 0; overflow: hidden; }
    .particle { position: absolute; width: 4px; height: 4px; background: var(--primary); border-radius: 50%; opacity: 0.3; animation: particleRise 15s linear infinite; }
    @keyframes particleRise { 0% { transform: translateY(100vh) rotate(0deg); opacity: 0; } 10% { opacity: 0.3; } 90% { opacity: 0.3; } 100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; } }
    
    .grid-pattern { position: absolute; inset: 0; background-image: linear-gradient(rgba(220,38,38,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(220,38,38,0.03) 1px, transparent 1px); background-size: 50px 50px; }

    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 100;
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(226,232,240,0.5);
      transition: all 0.3s ease;
    }
    header.scrolled { background: rgba(255,255,255,0.95); box-shadow: var(--shadow); }
    .navbar { max-width: 1200px; margin: auto; padding: 16px 6%; display: flex; align-items: center; justify-content: space-between; }
    .logo { display: flex; align-items: center; gap: 12px; font-size: 22px; font-weight: 800; color: var(--primary); text-decoration: none; }
    .logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, var(--primary), #f87171); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
    .nav-menu { display: flex; gap: 8px; }
    .nav-menu a { text-decoration: none; font-size: 14px; font-weight: 600; color: var(--muted); padding: 10px 18px; border-radius: 10px; transition: all 0.3s ease; }
    .nav-menu a:hover, .nav-menu a.active { color: var(--primary); background: var(--primary-light); }
    .menu-btn { display: none; width: 44px; height: 44px; border: none; background: var(--primary-light); border-radius: 12px; cursor: pointer; color: var(--primary); font-size: 18px; }

    .section { padding: 140px 6% 80px; max-width: 800px; margin: auto; }
    .section-header { text-align: center; margin-bottom: 48px; opacity: 0; transform: translateY(30px); animation: slideUp 0.8s ease forwards; }
    .badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: linear-gradient(135deg, var(--primary-light), #e0f2fe); border-radius: 100px; font-size: 13px; font-weight: 600; color: var(--primary); margin-bottom: 20px; }
    .section-title { font-size: clamp(28px,5vw,42px); font-weight: 800; margin-bottom: 16px; background: linear-gradient(135deg, var(--dark), var(--muted)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .section-sub { font-size: 16px; color: var(--muted); max-width: 500px; margin: 0 auto; }

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

    .form-card { background: var(--card); border-radius: var(--radius-lg); box-shadow: var(--shadow-xl); border: 1px solid var(--border); overflow: hidden; opacity: 0; transform: translateY(40px); animation: slideUp 0.8s ease 0.2s forwards; }
    .card-header { background: linear-gradient(135deg, var(--primary), #ef4444); padding: 24px 32px; color: white; }
    .card-header h3 { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .card-header p { font-size: 14px; margin-top: 8px; opacity: 0.85; }
    .card-body { padding: 32px; }

    .role-section { margin-bottom: 28px; }
    .role-label { font-size: 14px; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .role-buttons { display: flex; gap: 12px; }
    .role-btn { flex: 1; padding: 14px 20px; border: 2px solid var(--border); border-radius: 14px; background: var(--light); cursor: pointer; font-family: inherit; font-weight: 600; color: var(--muted); transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .role-btn.active { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }

    .form-group { margin-bottom: 24px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 10px; }
    .form-input { width: 100%; padding: 14px 18px; border: 2px solid var(--border); border-radius: 12px; font-family: inherit; font-size: 15px; background: var(--light); transition: all 0.3s ease; }
    .form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(220,38,38,0.1); }
    textarea.form-input { resize: none; height: 120px; }

    .medicine-section { margin-bottom: 28px; }
    .medicine-label { font-size: 14px; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .medicine-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; max-height: 350px; overflow-y: auto; padding: 8px; border: 2px solid var(--border); border-radius: 12px; background: var(--light); }
    .medicine-item { padding: 16px; border: 2px solid var(--border); border-radius: 12px; background: white; cursor: pointer; transition: all 0.3s ease; }
    .medicine-item:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow); }
    .medicine-item.selected { border-color: var(--primary); background: var(--primary-light); }
    .medicine-item.unavailable { opacity: 0.5; cursor: not-allowed; background: #f1f5f9; }
    .medicine-item.unavailable:hover { transform: none; border-color: var(--border); }
    .medicine-name { font-weight: 700; color: var(--dark); font-size: 15px; margin-bottom: 8px; }
    .medicine-stock { font-size: 13px; display: flex; align-items: center; gap: 6px; }
    .medicine-stock i { font-size: 12px; }
    .medicine-stock.available { color: var(--success); }
    .medicine-stock.low { color: var(--warning); }
    .medicine-stock.habis { color: var(--danger); }

    .stock-badge { display: flex; align-items: center; gap: 8px; margin-top: 16px; padding: 12px 20px; border-radius: 100px; font-size: 13px; font-weight: 600; background: var(--light); border: 1px solid var(--border); }
    .stock-badge.available { background: var(--success-light); color: var(--success); border-color: var(--success); }
    .stock-badge.low { background: var(--warning-light); color: var(--warning); border-color: var(--warning); }
    .stock-badge.habis { background: var(--danger-light); color: var(--danger); border-color: var(--danger); }

    .error-message { margin-bottom: 24px; padding: 14px 18px; background: var(--danger-light); border-radius: 12px; font-size: 14px; color: var(--danger); display: flex; align-items: center; gap: 10px; }

    .btn-group { display: flex; gap: 14px; margin-top: 32px; }
    .btn { flex: 1; padding: 16px 24px; border: none; border-radius: 14px; font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .btn-primary { background: linear-gradient(135deg, var(--primary), #ef4444); color: white; box-shadow: 0 8px 20px rgba(220,38,38,0.3); }
    .btn-primary:hover { transform: translateY(-2px); }

    .timer-box { margin-top: 28px; background: linear-gradient(135deg, var(--danger-light), white); border: 2px solid rgba(220,38,38,0.2); border-radius: var(--radius); padding: 24px; text-align: center; display: none; }
    .timer-box.active { display: block; }
    .timer-display { font-size: 48px; font-weight: 800; color: var(--primary); font-variant-numeric: tabular-nums; }

    /* NOTIFICATION TOAST - HANYA MUNCUL SEKALI */
    .toast-notification {
      position: fixed;
      top: 100px;
      right: 20px;
      max-width: 380px;
      background: white;
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-xl);
      padding: 20px 24px;
      z-index: 1000;
      transform: translateX(120%);
      transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      border-left: 4px solid;
    }
    .toast-notification.show {
      transform: translateX(0);
    }
    .toast-success {
      border-left-color: var(--success);
    }
    .toast-success .toast-icon {
      background: var(--success);
    }
    .toast-error {
      border-left-color: var(--danger);
    }
    .toast-error .toast-icon {
      background: var(--danger);
    }
    .toast-info {
      border-left-color: var(--warning);
    }
    .toast-info .toast-icon {
      background: var(--warning);
    }
    .toast-content {
      display: flex;
      gap: 16px;
      align-items: center;
    }
    .toast-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 20px;
      flex-shrink: 0;
    }
    .toast-message {
      flex: 1;
    }
    .toast-message h4 {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 4px;
    }
    .toast-message p {
      font-size: 13px;
      color: var(--muted);
    }
    .toast-close {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      color: var(--muted);
      padding: 4px;
    }

    .success-notification { margin-bottom: 28px; background: linear-gradient(135deg, var(--success-light), white); border: 2px solid rgba(16,185,129,0.2); border-radius: var(--radius); padding: 24px; text-align: center; display: none; }
    .success-notification.active { display: block; }
    .notification-icon { font-size: 48px; margin-bottom: 16px; }
    .notification-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; }

    .search-container {
    margin-bottom: 16px;
    position: relative;
}
.search-container i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 16px;
}
.search-container input {
    padding-left: 42px !important;
}

.no-result {
    text-align: center;
    padding: 40px;
    color: var(--muted);
    font-size: 14px;
}

    footer {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        position: relative;
        overflow: hidden;
        margin-top: 60px;
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

    @keyframes slideUp { to { opacity: 1; transform: translateY(0); } }
    
    @media (max-width: 768px) {
      .menu-btn { display: flex; align-items: center; justify-content: center; }
      .nav-menu { position: absolute; top: calc(100% + 10px); right: 6%; background: white; flex-direction: column; padding: 16px; border-radius: 16px; box-shadow: var(--shadow-xl); display: none; min-width: 180px; }
      .nav-menu.active { display: flex; }
      .section { padding: 120px 5% 60px; }
      .card-body { padding: 24px; }
      .btn-group { flex-direction: column; }
      .medicine-grid { grid-template-columns: 1fr; }
      .toast-notification { left: 20px; right: 20px; max-width: none; }
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
      <a href="#" class="logo"><div class="logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>UKS SmartCare</a>
      <button class="menu-btn" onclick="toggleMenu()"><i class="fa-solid fa-bars" id="menuIcon"></i></button>
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
      <div class="badge"><i class="fa-solid fa-file-prescription"></i> Layanan Kesehatan</div>
      <h1 class="section-title">Permintaan Obat</h1>
      <p class="section-sub">Ajukan permintaan obat secara resmi melalui sistem UKS SmartCare.</p>
    </div>

    <div class="alert-note">
      <div class="alert-icon"><i class="fa-solid fa-info"></i></div>
      <div class="alert-text">Layanan ini digunakan oleh <strong>siswa & guru</strong>. Obat hanya dapat diambil melalui petugas UKS setelah permintaan dikonfirmasi. </div>
    </div>

    <div class="form-card">
      <div class="card-header">
        <h3><i class="fa-solid fa-pen-to-square"></i> Form Permintaan Obat</h3>
        <p>Isi data dengan lengkap untuk memproses permintaan</p>
      </div>
      
      <div class="card-body">
        <?php if (isset($error) && !empty($error)): ?>
          <div class="error-message"><i class="fa-solid fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- STATUS PERMANENT (INI AKAN TETAP MUNCUL SETELAH REFRESH, SESUAI STATUS DI DATABASE) -->
        <?php if ($current_status === 'approved'): ?>
          <div class="success-notification active" style="background: linear-gradient(135deg, var(--success-light), white);">
            <div class="notification-icon" style="color: var(--success);"><i class="fa-solid fa-check-circle"></i></div>
            <h3 class="notification-title">Permintaan Disetujui</h3>
            <p>Permintaan obat Anda telah disetujui. Silakan ambil obat di lokasi UKS.</p>
          </div>
        <?php elseif ($current_status === 'rejected'): ?>
          <div class="success-notification active" style="background: linear-gradient(135deg, var(--danger-light), white);">
            <div class="notification-icon" style="color: var(--danger);"><i class="fa-solid fa-times-circle"></i></div>
            <h3 class="notification-title">Permintaan Ditolak</h3>
            <p>Maaf, permintaan obat Anda ditolak. Silakan ajukan kembali.</p>
          </div>
        <?php elseif ($current_status === 'expired'): ?>
          <div class="success-notification active" style="background: linear-gradient(135deg, var(--warning-light), white);">
            <div class="notification-icon" style="color: var(--warning);"><i class="fa-solid fa-clock"></i></div>
            <h3 class="notification-title">Waktu Habis</h3>
            <p>Waktu pengambilan obat telah habis. Silakan ajukan permintaan baru.</p>
          </div>
        <?php endif; ?>
        
        <form id="requestForm" method="POST" action="">
          <div class="role-section">
            <label class="role-label"><i class="fa-solid fa-user-tag"></i> Status Anda</label>
            <div class="role-buttons">
              <button type="button" class="role-btn active" id="btnSiswa" onclick="selectRole('siswa')"><i class="fa-solid fa-user-graduate"></i> Siswa</button>
              <button type="button" class="role-btn" id="btnGuru" onclick="selectRole('guru')"><i class="fa-solid fa-chalkboard-user"></i> Guru</button>
            </div>
          </div>
          
          <input type="hidden" name="role" id="roleHidden" value="siswa">

          <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" class="form-input" id="nama" name="nama" placeholder="Masukkan nama lengkap" required>
          </div>

          <div class="form-group" id="kelasGroup">
            <label for="kelas">Kelas</label>
            <input type="text" class="form-input" id="kelas" name="kelas" placeholder="Contoh: XII RPL 1">
          </div>

          <div class="form-group" id="jabatanGroup" style="display: none;">
            <label for="jabatan">Jabatan</label>
            <input type="text" class="form-input" id="jabatan" name="jabatan" placeholder="Contoh: Wali Kelas, Guru BK">
          </div>

          <div class="form-group">
            <label for="keluhan">Keluhan</label>
            <textarea class="form-input" id="keluhan" name="keluhan" placeholder="Jelaskan keluhan yang dirasakan..." required></textarea>
          </div>

          <div class="medicine-section">
    <label class="medicine-label"><i class="fa-solid fa-pills"></i> Pilih Obat</label>
    
    <!-- SEARCH BAR -->
    <div class="search-container">
        <i class="fa-solid fa-search"></i>
        <input type="text" id="searchInput" class="form-input" placeholder="Cari nama obat..." autocomplete="off">
    </div>
    
    <div class="medicine-grid" id="medicineGrid">
    <?php
    // Tampilkan semua obat
    $q_obat = mysqli_query($conn, "SELECT id_obat, nama_obat, stok, kategori, status FROM obat ORDER BY nama_obat ASC");
    if ($q_obat && mysqli_num_rows($q_obat) > 0) {
        while ($row = mysqli_fetch_assoc($q_obat)) {
            $stok_asli = $row['stok'];
            $kategori = $row['kategori'];
            $is_permanen = !perluLimit($kategori); // tube, botol, can
            
            // Hitung sisa limit mingguan (khusus kategori limit)
            $sudah_dipakai = isset($used_week[$row['id_obat']]) ? (int)$used_week[$row['id_obat']] : 0;
            $sisa_limit = max(0, 20 - $sudah_dipakai);
            
            if ($is_permanen) {
                // Kategori permanen: stok tidak berkurang otomatis
                $stok_tampil = $stok_asli;
                $is_available = true;
                $stock_class = 'available';
                $stock_icon = "fa-check-circle";
                $stock_info = "Tersedia ({$stok_tampil})";
            } else {
                // Kategori limit
                $stok_tampil = min($sisa_limit, $stok_asli);
                $is_available = ($stok_tampil > 0 && $sisa_limit > 0);
                
                if ($stok_tampil > 10) {
                    $stock_class = 'available';
                    $stock_icon = "fa-check-circle";
                    $stock_info = "Tersedia ({$stok_tampil})";
                } elseif ($stok_tampil >= 1 && $stok_tampil <= 10) {
                    $stock_class = 'low';
                    $stock_icon = "fa-exclamation-triangle";
                    $stock_info = "Hampir Habis ({$stok_tampil})";
                } else {
                    $stock_class = 'habis';
                    $stock_icon = "fa-times-circle";
                    $stock_info = "Habis";
                }
            }
            
            $item_class = $is_available ? '' : 'unavailable';
            
            // Info limit mingguan (khusus limit)
            $limit_info = '';
            if (!$is_permanen && $sisa_limit > 0 && $sisa_limit <= 5) {
                $limit_info = " <small style='color:#f59e0b;'>(sisa limit: {$sisa_limit}/20)</small>";
            } elseif (!$is_permanen && $sisa_limit <= 0) {
                $limit_info = " <small style='color:#ef4444;'>(limit habis)</small>";
            }
            
            echo "
            <div class='medicine-item {$item_class}' data-id='{$row['id_obat']}' data-stok='{$stok_tampil}' data-kategori='{$kategori}' data-nama='{$row['nama_obat']}' data-permanen='".($is_permanen ? '1' : '0')."' onclick='selectMedicine(this)'>
                <div class='medicine-name'>
                    {$row['nama_obat']} 
                    <small style='font-weight:normal;color:#64748b;'>(".strtolower($kategori).")</small>
                    {$limit_info}
                </div>
                <div class='medicine-stock {$stock_class}'>
                    <i class='fa-solid {$stock_icon}'></i>
                    {$stock_info}
                </div>
            </div>
            ";
        }
    } else {
        echo "<div style='padding: 20px; text-align: center; color: var(--muted);'>Belum ada data obat</div>";
    }
    ?>
    </div>
    
    <input type="hidden" id="obat" name="obat" required>
    <div class="stock-badge" id="stockBadge">
        <i class="fa-solid fa-info-circle"></i> Klik salah satu obat di atas untuk memilih
    </div>
</div>

          <div class="btn-group">
            <button type="submit" class="btn btn-primary" id="submitBtn"><i class="fa-solid fa-paper-plane"></i> Kirim Permintaan</button>
          </div>

          <div class="timer-box <?php echo $remaining_time > 0 ? 'active' : ''; ?>" id="timerBox">
            <div class="timer-title">Waktu Pengambilan Tersisa</div>
            <div class="timer-display" id="timerDisplay">
              <?php 
              if ($remaining_time > 0) {
                  echo sprintf('%02d:%02d', floor($remaining_time/60), $remaining_time%60);
              } else { 
                  if ($current_status === 'pending') {
                      echo 'HABIS';
                  } else {
                      echo '00:00';
                  }
              }
              ?>
            </div>
            <div class="timer-note">Segera ambil obat di UKS sebelum waktu habis</div>
          </div>
        </form>
      </div>
    </div>
  </main>

  <!-- TOAST NOTIFICATION - HANYA MUNCUL SAAT SUBMIT, TIDAK SETELAH REFRESH -->
  <?php if ($show_success_notif): ?>
  <div class="toast-notification toast-success" id="successToast">
    <div class="toast-content">
      <div class="toast-icon"><i class="fa-solid fa-check"></i></div>
      <div class="toast-message">
        <h4>Berhasil!</h4>
        <p>Permintaan obat Anda telah terkirim. Silakan tunggu konfirmasi dari petugas UKS.</p>
      </div>
      <button class="toast-close" onclick="closeToast()"><i class="fa-solid fa-times"></i></button>
    </div>
  </div>
  <?php endif; ?>

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
    let selectedMedicine = null;
    let selectedStok = 0;
    let selectedKategori = '';
    let timerInterval = null;
    let request_id = <?php echo $request_id ? "'$request_id'" : 'null'; ?>;
    let current_status = <?php echo $current_status ? "'$current_status'" : 'null'; ?>;
    let request_time = <?php echo $request_time ? "'$request_time'" : 'null'; ?>;

    function createParticles() {
      const container = document.getElementById('particles');
      if (!container) return;
      for (let i = 0; i < 20; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.animationDelay = Math.random() * 15 + 's';
        p.style.animationDuration = (15 + Math.random() * 10) + 's';
        p.style.width = (2 + Math.random() * 4) + 'px';
        p.style.height = p.style.width;
        container.appendChild(p);
      }
    }

    function toggleMenu() {
      const navMenu = document.getElementById('navMenu');
      const menuIcon = document.getElementById('menuIcon');
      navMenu.classList.toggle('active');
      menuIcon.className = navMenu.classList.contains('active') ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
    }

    function selectRole(role) {
      document.getElementById('btnSiswa').classList.toggle('active', role === 'siswa');
      document.getElementById('btnGuru').classList.toggle('active', role === 'guru');
      document.getElementById('kelasGroup').style.display = role === 'siswa' ? 'block' : 'none';
      document.getElementById('jabatanGroup').style.display = role === 'guru' ? 'block' : 'none';
      document.getElementById('roleHidden').value = role;
      document.getElementById('kelas').required = (role === 'siswa');
      document.getElementById('jabatan').required = (role === 'guru');
    }

    function selectMedicine(element) {
    // Cek dulu apakah item ini unavailable (habis/limit habis)
    if (element.classList.contains('unavailable')) {
        const isPermanen = element.getAttribute('data-permanen') === '1';
        if (isPermanen) {
            // Untuk kategori permanen, tetap bisa dipilih meskipun stok info
            // Tapi kalau memang unavailable karena limit & stok habis
        }
        showToast('Tidak Tersedia', 'Obat ini sedang tidak tersedia untuk minggu ini.', 'error');
        return;
    }
    
    document.querySelectorAll('.medicine-item').forEach(item => item.classList.remove('selected'));
    element.classList.add('selected');
    selectedMedicine = element.dataset.id;
    selectedStok = parseInt(element.dataset.stok);
    selectedKategori = element.dataset.kategori;
    const isPermanen = element.getAttribute('data-permanen') === '1';
    document.getElementById('obat').value = selectedMedicine;
    
    const stockBadge = document.getElementById('stockBadge');
    if (isPermanen) {
        stockBadge.className = 'stock-badge available';
        stockBadge.innerHTML = `<i class="fa-solid fa-flask"></i> Obat ${selectedKategori} (stok manual - ${selectedStok} unit/sisa)`;
    } else if (selectedStok > 10) {
        stockBadge.className = 'stock-badge available';
        stockBadge.innerHTML = `<i class="fa-solid fa-check-circle"></i> Stok tersedia: ${selectedStok} unit (${selectedKategori})`;
    } else if (selectedStok >= 1 && selectedStok <= 10) {
        stockBadge.className = 'stock-badge low';
        stockBadge.innerHTML = `<i class="fa-solid fa-exclamation-triangle"></i> Stok hampir habis: ${selectedStok} unit (${selectedKategori})`;
    } else {
        stockBadge.className = 'stock-badge habis';
        stockBadge.innerHTML = `<i class="fa-solid fa-times-circle"></i> Stok habis: ${selectedStok} unit (${selectedKategori})`;
    }
}

    function showToast(title, message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `toast-notification toast-${type}`;
      toast.innerHTML = `
        <div class="toast-content">
          <div class="toast-icon"><i class="fa-solid ${type === 'success' ? 'fa-check' : (type === 'error' ? 'fa-exclamation-triangle' : 'fa-info')}"></i></div>
          <div class="toast-message">
            <h4>${title}</h4>
            <p>${message}</p>
          </div>
          <button class="toast-close" onclick="this.parentElement.parentElement.remove()"><i class="fa-solid fa-times"></i></button>
        </div>
      `;
      document.body.appendChild(toast);
      setTimeout(() => toast.classList.add('show'), 10);
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
      }, 5000);
    }

    function closeToast() {
      const toast = document.getElementById('successToast');
      if (toast) {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
      }
    }

    function submitRequest() { 
      document.getElementById('requestForm').submit(); 
    }

    function startTimer(seconds) {
      const timerBox = document.getElementById('timerBox');
      const timerDisplay = document.getElementById('timerDisplay');
      timerBox.classList.add('active');
      let remaining = seconds;
      if (timerInterval) clearInterval(timerInterval);
      timerInterval = setInterval(() => {
        if (remaining <= 0) {
          clearInterval(timerInterval);
          timerDisplay.textContent = 'HABIS';
          timerDisplay.style.color = 'var(--danger)';
          setTimeout(() => { location.reload(); }, 1000);
        } else {
          const minutes = Math.floor(remaining / 60);
          const secs = remaining % 60;
          timerDisplay.textContent = String(minutes).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
          remaining--;
        }
      }, 1000);
    }

    function handleScroll() {
      const header = document.getElementById('header');
      if (header) header.classList.toggle('scrolled', window.scrollY > 50);
    }

// FUNGSI SEARCH OBAT
const searchInput = document.getElementById('searchInput');
const medicineItems = document.querySelectorAll('.medicine-item');

function filterMedicine() {
    const keyword = searchInput.value.toLowerCase().trim();
    
    medicineItems.forEach(item => {
        const namaObat = item.getAttribute('data-nama').toLowerCase();
        
        if (keyword === '' || namaObat.includes(keyword)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
    
    // Cek apakah ada hasil yang tampil
    const visibleItems = document.querySelectorAll('.medicine-item[style*="display: none"]');
    const allItems = document.querySelectorAll('.medicine-item');
    const visibleCount = allItems.length - visibleItems.length;
    
    // Hapus notifikasi sebelumnya jika ada
    const existingNoResult = document.querySelector('.no-result');
    if (existingNoResult) existingNoResult.remove();
    
    if (visibleCount === 0 && keyword !== '') {
        const grid = document.getElementById('medicineGrid');
        const noResultDiv = document.createElement('div');
        noResultDiv.className = 'no-result';
        noResultDiv.innerHTML = '<i class="fa-solid fa-face-frown" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>Obat "' + keyword + '" tidak ditemukan';
        grid.appendChild(noResultDiv);
    }
}

// Event listener untuk search
if (searchInput) {
    searchInput.addEventListener('keyup', filterMedicine);
    searchInput.addEventListener('input', filterMedicine);
}

    // TAMPILKAN TOAST SETELAH LOAD (jika ada)
    document.addEventListener('DOMContentLoaded', function() {
      createParticles();
      window.addEventListener('scroll', handleScroll);
      
      // Tampilkan toast sukses jika ada
      const successToast = document.getElementById('successToast');
      if (successToast) {
        setTimeout(() => successToast.classList.add('show'), 100);
        setTimeout(() => {
          if (successToast) {
            successToast.classList.remove('show');
            setTimeout(() => successToast.remove(), 400);
          }
        }, 5000);
      }
      
      document.getElementById('requestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const nama = document.getElementById('nama').value.trim();
        const keluhan = document.getElementById('keluhan').value.trim();
        const role = document.getElementById('roleHidden').value;
        const kelas = document.getElementById('kelas').value.trim();
        const jabatan = document.getElementById('jabatan').value.trim();
        
        if (!nama) { showToast('Validasi Gagal', 'Nama lengkap harus diisi', 'error'); return; }
        if (!keluhan) { showToast('Validasi Gagal', 'Keluhan harus diisi', 'error'); return; }
        if (role === 'siswa' && !kelas) { showToast('Validasi Gagal', 'Kelas harus diisi untuk siswa', 'error'); return; }
        if (role === 'guru' && !jabatan) { showToast('Validasi Gagal', 'Jabatan harus diisi untuk guru', 'error'); return; }
        if (!selectedMedicine) { showToast('Validasi Gagal', 'Silakan pilih obat terlebih dahulu', 'error'); return; }
        
        if (confirm('Apakah Anda yakin ingin mengirim permintaan obat ini?')) {
          this.submit();
        }
      });
      
      if (request_id && request_time && current_status === 'pending') {
        const elapsed = Math.floor(Date.now() / 1000) - parseInt(request_time);
        const remaining = Math.max(0, 600 - elapsed);
        if (remaining > 0) startTimer(remaining);
      }
    });
    
    document.addEventListener('click', function(e) {
      const navMenu = document.getElementById('navMenu');
      const menuBtn = document.querySelector('.menu-btn');
      if (navMenu && menuBtn && !navMenu.contains(e.target) && !menuBtn.contains(e.target)) {
        navMenu.classList.remove('active');
        const menuIcon = document.getElementById('menuIcon');
        if (menuIcon) menuIcon.className = 'fa-solid fa-bars';
      }
    });
  </script>
</body>
</html>