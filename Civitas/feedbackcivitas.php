<?php

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Feedback | UKS SmartCare</title>

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

  <style>
    /* CSS TETAP SAMA SEPERTI YANG KAMU KIRIM SEBELUMNYA */
    /* Saya tidak menulis ulang seluruh CSS agar tidak terlalu panjang, 
       copy-paste bagian <style> dari kode sebelumnya di sini */
    
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

    /* GRID PATTERN */
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
      position: relative;
    }

    .nav-menu a:hover {
      color: var(--primary);
      background: var(--primary-light);
    }

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

    /* MAIN SECTION */
    .section {
      padding: 140px 6% 80px;
      max-width: 800px;
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
      max-width: 500px;
      margin: 0 auto;
    }

    /* FEEDBACK CARD */
    .feedback-card {
      background: var(--card);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-xl);
      border: 1px solid var(--border);
      overflow: hidden;
      opacity: 0;
      transform: translateY(40px);
      animation: slideUp 0.8s ease 0.2s forwards;
    }

    .card-header {
      background: linear-gradient(135deg, var(--primary), #ef4444);
      padding: 24px 32px;
      position: relative;
      overflow: hidden;
    }

    .card-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
      animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes shimmer {
      0%, 100% { transform: translateX(-10px); }
      50% { transform: translateX(10px); }
    }

    .card-header h3 {
      color: white;
      font-size: 18px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
      z-index: 1;
    }

    .card-header p {
      color: rgba(255, 255, 255, 0.85);
      font-size: 14px;
      margin-top: 8px;
      position: relative;
      z-index: 1;
    }

    .card-body {
      padding: 32px;
    }

    /* RATING SECTION */
    .rating-section {
      margin-bottom: 28px;
    }

    .rating-label {
      font-size: 14px;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .rating-label i {
      color: var(--accent);
    }

    .emoji-rating {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .emoji-btn {
      width: 56px;
      height: 56px;
      border: 2px solid var(--border);
      border-radius: 16px;
      background: var(--light);
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      position: relative;
      overflow: hidden;
    }

    .emoji-btn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .emoji-btn:hover {
      transform: translateY(-4px) scale(1.05);
      border-color: var(--primary);
      box-shadow: 0 8px 20px rgba(220, 38, 38, 0.2);
    }

    .emoji-btn.active {
      border-color: var(--primary);
      background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
      transform: scale(1.1);
      box-shadow: 0 8px 20px rgba(220, 38, 38, 0.25);
    }

    .emoji-btn span {
      position: relative;
      z-index: 1;
    }

    .rating-text {
      margin-top: 12px;
      font-size: 13px;
      color: var(--muted);
      min-height: 20px;
      transition: all 0.3s ease;
    }

    .rating-text.active {
      color: var(--primary);
      font-weight: 600;
    }

    /* CATEGORY PILLS */
    .category-section {
      margin-bottom: 28px;
    }

    .category-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .pill-btn {
      padding: 10px 20px;
      border: 2px solid var(--border);
      border-radius: 100px;
      background: var(--card);
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .pill-btn i {
      font-size: 12px;
      opacity: 0.7;
    }

    .pill-btn:hover {
      border-color: var(--primary);
      color: var(--primary);
      background: var(--primary-light);
    }

    .pill-btn.active {
      background: var(--primary);
      border-color: var(--primary);
      color: white;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }

    .pill-btn.active i { opacity: 1; }

    /* TEXTAREA */
    .textarea-section {
      margin-bottom: 28px;
    }

    .textarea-label {
      font-size: 14px;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .char-count {
      font-size: 12px;
      font-weight: 500;
      color: var(--muted);
      padding: 4px 10px;
      background: var(--light);
      border-radius: 6px;
    }

    .char-count.warning { color: #f59e0b; }
    .char-count.danger { color: var(--primary); }

    .textarea-wrapper {
      position: relative;
    }

    textarea {
      width: 100%;
      height: 180px;
      padding: 18px;
      border: 2px solid var(--border);
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 15px;
      resize: none;
      transition: all 0.3s ease;
      background: var(--light);
      line-height: 1.6;
    }

    textarea:focus {
      outline: none;
      border-color: var(--primary);
      background: white;
      box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
    }

    textarea::placeholder {
      color: #94a3b8;
    }

    /* SUBMIT BUTTON */
    .btn-submit {
      width: 100%;
      padding: 18px 32px;
      border: none;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--primary), #ef4444);
      color: white;
      font-family: inherit;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      position: relative;
      overflow: hidden;
    }

    .btn-submit::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s ease;
    }

    .btn-submit:hover::before {
      left: 100%;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(220, 38, 38, 0.35);
    }

    .btn-submit:active {
      transform: translateY(0);
    }

    .btn-submit:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    .btn-submit .spinner {
      display: none;
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255,255,255,0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    .btn-submit.loading .spinner { display: block; }
    .btn-submit.loading .btn-text { display: none; }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* SUCCESS MODAL */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(8px);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .modal-content {
      background: white;
      border-radius: var(--radius-lg);
      padding: 48px;
      text-align: center;
      max-width: 400px;
      transform: scale(0.9) translateY(20px);
      transition: transform 0.3s ease;
    }

    .modal-overlay.active .modal-content {
      transform: scale(1) translateY(0);
    }

    .success-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, var(--success), #34d399);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      animation: successPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes successPop {
      0% { transform: scale(0); }
      100% { transform: scale(1); }
    }

    .success-icon i {
      font-size: 36px;
      color: white;
      animation: checkDraw 0.5s ease 0.2s forwards;
      opacity: 0;
    }

    @keyframes checkDraw {
      to { opacity: 1; }
    }

    .modal-content h3 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 12px;
      color: var(--dark);
    }

    .modal-content p {
      color: var(--muted);
      margin-bottom: 28px;
      line-height: 1.6;
    }

    .modal-btn {
      padding: 14px 32px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 12px;
      font-family: inherit;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .modal-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }

    /* FOOTER - UPDATED TO RED */
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

      .card-body {
        padding: 24px;
      }

      .emoji-btn {
        width: 48px;
        height: 48px;
        font-size: 22px;
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
  <!-- ANIMATED BACKGROUND -->
  <div class="bg-container">
    <div class="gradient-orb orb-1"></div>
    <div class="gradient-orb orb-2"></div>
    <div class="gradient-orb orb-3"></div>
    <div class="grid-pattern"></div>
    <div class="particles" id="particles"></div>
  </div>

  <!-- HEADER -->
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

  <!-- MAIN CONTENT -->
  <main class="section">
    <div class="section-header">
      <div class="badge">
        <i class="fa-solid fa-comment-dots"></i>
        Sampaikan Pendapatmu
      </div>
      <h1 class="section-title">Feedback Civitas</h1>
      <p class="section-sub">Bantu kami meningkatkan layanan UKS dengan memberikan kritik, saran, atau apresiasi Anda.</p>
    </div>

    <div class="feedback-card">
      <div class="card-header">
        <h3><i class="fa-solid fa-pen-to-square"></i> Form Feedback</h3>
        <p>Feedback Anda sangat berarti untuk kemajuan layanan kami</p>
      </div>
      
      <!-- Form diubah: onsubmit dihandle JS, tidak perlu action HTML biasa -->
      <form class="card-body" id="feedbackForm">
        
        <!-- RATING -->
        <div class="rating-section">
          <label class="rating-label">
            <i class="fa-solid fa-star"></i>
            Bagaimana pengalaman Anda?
          </label>
          <div class="emoji-rating">
            <button type="button" class="emoji-btn" data-rating="1" data-text="Sangat Buruk - Kami minta maaf" onclick="setRating(this)">
              <span>&#128542;</span>
            </button>
            <button type="button" class="emoji-btn" data-rating="2" data-text="Buruk - Akan kami perbaiki" onclick="setRating(this)">
              <span>&#128533;</span>
            </button>
            <button type="button" class="emoji-btn" data-rating="3" data-text="Cukup - Terima kasih atas masukannya" onclick="setRating(this)">
              <span>&#128528;</span>
            </button>
            <button type="button" class="emoji-btn" data-rating="4" data-text="Baik - Senang mendengarnya!" onclick="setRating(this)">
              <span>&#128522;</span>
            </button>
            <button type="button" class="emoji-btn" data-rating="5" data-text="Sangat Baik - Luar biasa!" onclick="setRating(this)">
              <span>&#128525;</span>
            </button>
          </div>
          <p class="rating-text" id="ratingText">Pilih rating untuk memberikan penilaian</p>
        </div>

        <!-- CATEGORY -->
        <div class="category-section">
          <label class="rating-label">
            <i class="fa-solid fa-tags"></i>
            Kategori Feedback
          </label>
          <div class="category-pills">
            <button type="button" class="pill-btn" onclick="toggleCategory(this)">
              <i class="fa-solid fa-user-nurse"></i> Pelayanan
            </button>
            <button type="button" class="pill-btn" onclick="toggleCategory(this)">
              <i class="fa-solid fa-pills"></i> Ketersediaan Obat
            </button>
            <button type="button" class="pill-btn" onclick="toggleCategory(this)">
              <i class="fa-solid fa-clock"></i> Waktu Layanan
            </button>
            <button type="button" class="pill-btn" onclick="toggleCategory(this)">
              <i class="fa-solid fa-lightbulb"></i> Saran
            </button>
            <button type="button" class="pill-btn" onclick="toggleCategory(this)">
              <i class="fa-solid fa-bug"></i> Laporan Bug
            </button>
            <button type="button" class="pill-btn" onclick="toggleCategory(this)">
              <i class="fa-solid fa-ellipsis"></i> Lainnya
            </button>
          </div>
        </div>

        <!-- TEXTAREA -->
        <div class="textarea-section">
          <label class="textarea-label">
            <span>Detail Feedback</span>
            <span class="char-count" id="charCount">0 / 500</span>
          </label>
          <div class="textarea-wrapper">
            <textarea 
              name="pesan" 
              id="pesan"
              placeholder="Ceritakan pengalaman Anda atau berikan saran untuk peningkatan layanan UKS..."
              maxlength="500"
              oninput="updateCharCount()"
              required
            ></textarea>
          </div>
        </div>

        <!-- SUBMIT -->
        <button type="submit" class="btn-submit" id="submitBtn">
          <span class="btn-text"><i class="fa-solid fa-paper-plane"></i> Kirim Feedback</span>
          <span class="spinner"></span>
        </button>
      </form>
    </div>
  </main>

  <!-- FOOTER -->
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

  <!-- SUCCESS MODAL -->
  <div class="modal-overlay" id="successModal">
    <div class="modal-content">
      <div class="success-icon">
        <i class="fa-solid fa-check"></i>
      </div>
      <h3>Terima Kasih!</h3>
      <p>Feedback Anda telah berhasil dikirim. Kami sangat menghargai masukan Anda untuk meningkatkan layanan UKS.</p>
      <button class="modal-btn" onclick="closeModal()">Tutup</button>
    </div>
  </div>

  <script>
    // Initialize variables first
    let selectedRating = 0;
    let selectedCategories = [];

    // Create particles
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

    function setRating(btn) {
      document.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selectedRating = parseInt(btn.dataset.rating);
      const ratingText = document.getElementById('ratingText');
      ratingText.textContent = btn.dataset.text;
      ratingText.classList.add('active');
    }

    function toggleCategory(btn) {
      btn.classList.toggle('active');
      const categoryText = btn.textContent.trim();
      if (btn.classList.contains('active')) {
        if (!selectedCategories.includes(categoryText)) selectedCategories.push(categoryText);
      } else {
        selectedCategories = selectedCategories.filter(c => c !== categoryText);
      }
    }

    function updateCharCount() {
      const textarea = document.getElementById('pesan');
      const charCount = document.getElementById('charCount');
      const count = textarea.value.length;
      charCount.textContent = count + ' / 500';
      charCount.classList.remove('warning', 'danger');
      if (count > 450) charCount.classList.add('danger');
      else if (count > 350) charCount.classList.add('warning');
    }

    // MODIFIED HANDLE SUBMIT: Connects to PHP Backend
    function handleSubmit(e) {
      e.preventDefault();
      
      const submitBtn = document.getElementById('submitBtn');
      const textarea = document.getElementById('pesan');
      
      // Basic Validation
      if (selectedRating === 0) {
        alert("Silakan pilih rating pengalaman Anda.");
        return;
      }
      if (!textarea.value.trim()) {
        textarea.focus();
        return;
      }

      // Show loading state
      submitBtn.classList.add('loading');
      submitBtn.disabled = true;

      // Prepare data
      const dataToSend = {
        rating: selectedRating,
        kategori: selectedCategories.join(", "), // Join categories with comma
        pesan: textarea.value
      };

      // Send to Backend using Fetch API
      fetch('submit_feedback.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataToSend)
      })
      .then(response => response.json())
      .then(data => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;

        if (data.status === 'success') {
          // Show success modal
          document.getElementById('successModal').classList.add('active');
          
          // Reset form
          document.getElementById('feedbackForm').reset();
          document.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('active'));
          document.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active'));
          document.getElementById('ratingText').textContent = 'Pilih rating untuk memberikan penilaian';
          document.getElementById('ratingText').classList.remove('active');
          document.getElementById('charCount').textContent = '0 / 500';
          selectedRating = 0;
          selectedCategories = [];
        } else {
          alert('Terjadi kesalahan: ' + (data.message || 'Gagal mengirim data.'));
        }
      })
      .catch(error => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
        console.error('Error:', error);
        alert('Tidak dapat terhubung ke server. Cek koneksi atau console.');
      });
    }

    function closeModal() {
      document.getElementById('successModal').classList.remove('active');
    }

    // Event Listeners
    function handleScroll() {
      const header = document.getElementById('header');
      if (window.scrollY > 50) header.classList.add('scrolled');
      else header.classList.remove('scrolled');
    }

    document.getElementById('successModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    document.addEventListener('click', function(e) {
      const navMenu = document.getElementById('navMenu');
      const menuBtn = document.querySelector('.menu-btn');
      if (!navMenu.contains(e.target) && !menuBtn.contains(e.target)) {
        navMenu.classList.remove('active');
        document.getElementById('menuIcon').className = 'fa-solid fa-bars';
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeModal();
        document.getElementById('navMenu').classList.remove('active');
        document.getElementById('menuIcon').className = 'fa-solid fa-bars';
      }
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      createParticles();
      window.addEventListener('scroll', handleScroll);
      
      // Attach submit handler
      document.getElementById('feedbackForm').addEventListener('submit', handleSubmit);
    });
  </script>
</body>
</html>