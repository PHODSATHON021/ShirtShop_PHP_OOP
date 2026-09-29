<?php
/**
 * ShirtShop - หน้าร้านสำหรับผู้ซื้อ (Customer Online Storefront)
 * ใช้งาน OOP Classes: Inventory (ค้นหา/ดูสินค้า), Sale (สั่งซื้อสินค้า), Database (Shared)
 */
require_once __DIR__ . '/classes/Inventory.php';
require_once __DIR__ . '/classes/Sale.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$isStaffLoggedIn = $auth->isLoggedIn();
$currentUser = $isStaffLoggedIn ? $auth->getCurrentUser() : null;

$inventory = new Inventory();
$categories = $inventory->getCategories();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShirtShop - ร้านขายเสื้อผ้าแฟชั่นออนไลน์</title>
    <meta name="description" content="เลือกซื้อเสื้อยืด เสื้อโปโล เสื้อฮาวาย คุณภาพดี สั่งซื้อง่าย จัดส่งรวดเร็วทั่วประเทศ">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --shop-primary: #3b82f6;
            --shop-accent:  #10b981;
        }
        body.shop-body {
            background: #0b0d14;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Customer Navigation */
        .shop-navbar {
            background: rgba(18, 21, 30, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .shop-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
            font-size: 20px;
            font-weight: 700;
        }
        .shop-brand .icon { font-size: 28px; }
        .shop-search {
            flex: 1;
            max-width: 480px;
            position: relative;
        }
        .shop-search input {
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 10px 18px 10px 42px;
            border-radius: 25px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all var(--transition);
        }
        .shop-search input:focus {
            border-color: var(--shop-primary);
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.3);
        }
        .shop-search .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        .shop-nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-cart-toggle {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            transition: all var(--transition);
        }
        .btn-cart-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
        }
        .cart-badge {
            background: #ef4444;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 12px;
        }

        /* ============================================
           HERO BANNER — Premium Redesign
           ============================================ */
        .shop-hero {
            background: linear-gradient(135deg, #0d0a2e 0%, #0f172a 40%, #0c1a2e 100%);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
            padding: 80px 28px 70px;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 380px;
            display: flex;
            align-items: center;
        }

        /* Glowing background orbs */
        .shop-hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            background: radial-gradient(circle, rgba(99, 102, 241, 0.18) 0%, transparent 65%);
            animation: heroPulse 6s ease-in-out infinite;
        }
        .shop-hero::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            bottom: -120px;
            right: 10%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, transparent 65%);
            animation: heroPulse 8s ease-in-out infinite reverse;
        }
        @keyframes heroPulse {
            0%, 100% { transform: translateX(-50%) scale(1); opacity: 1; }
            50% { transform: translateX(-50%) scale(1.15); opacity: 0.7; }
        }

        /* Floating particles */
        .hero-particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .hero-particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(147, 197, 253, 0.6);
            border-radius: 50%;
            animation: floatUp linear infinite;
        }
        .hero-particle:nth-child(1)  { left: 10%; animation-duration: 7s;  animation-delay: 0s;   width: 2px; height: 2px; }
        .hero-particle:nth-child(2)  { left: 20%; animation-duration: 10s; animation-delay: 1.5s; width: 4px; height: 4px; background: rgba(167, 139, 250, 0.5); }
        .hero-particle:nth-child(3)  { left: 33%; animation-duration: 8s;  animation-delay: 3s;   width: 2px; height: 2px; }
        .hero-particle:nth-child(4)  { left: 48%; animation-duration: 12s; animation-delay: 0.5s; width: 3px; height: 3px; background: rgba(52, 211, 153, 0.5); }
        .hero-particle:nth-child(5)  { left: 60%; animation-duration: 9s;  animation-delay: 2s;   width: 2px; height: 2px; }
        .hero-particle:nth-child(6)  { left: 72%; animation-duration: 11s; animation-delay: 4s;   width: 4px; height: 4px; background: rgba(251, 191, 36, 0.4); }
        .hero-particle:nth-child(7)  { left: 83%; animation-duration: 7.5s;animation-delay: 1s;   width: 2px; height: 2px; }
        .hero-particle:nth-child(8)  { left: 91%; animation-duration: 9.5s;animation-delay: 3.5s; width: 3px; height: 3px; background: rgba(167, 139, 250, 0.5); }
        @keyframes floatUp {
            0%   { transform: translateY(120%) scale(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 0.6; }
            100% { transform: translateY(-20px) scale(1.2); opacity: 0; }
        }

        /* Decorative grid lines */
        .hero-grid-lines {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(99,102,241,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,0.06) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 30%, transparent 100%);
        }

        .shop-hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        /* Animated badge */
        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, rgba(99,102,241,0.25), rgba(167,139,250,0.15));
            color: #c4b5fd;
            border: 1px solid rgba(167, 139, 250, 0.4);
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
            text-transform: uppercase;
            animation: tagGlow 3s ease-in-out infinite;
            box-shadow: 0 0 20px rgba(167, 139, 250, 0.15);
        }
        .hero-tag .tag-dot {
            width: 7px;
            height: 7px;
            background: #a78bfa;
            border-radius: 50%;
            animation: dotPulse 1.5s ease-in-out infinite;
            box-shadow: 0 0 8px #a78bfa;
        }
        @keyframes tagGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(167,139,250,0.15); }
            50% { box-shadow: 0 0 30px rgba(167,139,250,0.35); }
        }
        @keyframes dotPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.7; }
        }

        /* Hero title — big gradient text */
        .hero-title {
            font-size: clamp(28px, 5vw, 52px);
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #c7d2fe 40%, #a78bfa 70%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 30px rgba(167,139,250,0.3));
            animation: titleShimmer 4s ease-in-out infinite;
        }
        @keyframes titleShimmer {
            0%, 100% { filter: drop-shadow(0 0 30px rgba(167,139,250,0.3)); }
            50% { filter: drop-shadow(0 0 45px rgba(167,139,250,0.55)); }
        }

        .hero-desc {
            color: rgba(203, 213, 225, 0.8);
            font-size: 15px;
            line-height: 1.75;
            max-width: 580px;
            margin: 0 auto 28px;
        }

        /* CTA Buttons */
        .hero-cta-group {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 44px;
        }
        .hero-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            border: none;
            padding: 13px 28px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(99,102,241,0.4);
            position: relative;
            overflow: hidden;
        }
        .hero-btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .hero-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 14px 35px rgba(99,102,241,0.55); }
        .hero-btn-primary:hover::before { opacity: 1; }
        .hero-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.07);
            color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.15);
            padding: 12px 26px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
        }
        .hero-btn-secondary:hover { background: rgba(255,255,255,0.13); border-color: rgba(255,255,255,0.3); transform: translateY(-2px); color: #fff; }

        /* Stats row */
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 0;
            border-top: 1px solid rgba(255,255,255,0.07);
            padding-top: 28px;
            flex-wrap: wrap;
        }
        .hero-stat-item {
            padding: 0 32px;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,0.07);
        }
        .hero-stat-item:last-child { border-right: none; }
        .hero-stat-num {
            display: block;
            font-size: 26px;
            font-weight: 900;
            background: linear-gradient(135deg, #6366f1, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }
        .hero-stat-label {
            font-size: 11px;
            color: rgba(148, 163, 184, 0.8);
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-top: 2px;
            display: block;
        }
        @media (max-width: 600px) {
            .hero-stat-item { padding: 10px 20px; border-right: none; }
            .shop-hero { padding: 60px 20px 50px; min-height: 0; }
        }

        /* Category Filter Bar */
        .shop-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 28px 20px 60px;
            flex: 1;
            width: 100%;
        }
        .category-scroll-bar {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 24px;
        }
        .cat-chip {
            padding: 8px 18px;
            border-radius: 25px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: all var(--transition);
        }
        .cat-chip:hover, .cat-chip.active {
            background: var(--shop-primary);
            color: #fff;
            border-color: var(--shop-primary);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Products Grid */
        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }
        .catalog-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.25s ease;
            position: relative;
        }
        .catalog-card:hover {
            transform: translateY(-5px);
            border-color: var(--shop-primary);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
        }
        .catalog-img-box {
            height: 180px;
            background: linear-gradient(135deg, #1e2230, #161822);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .catalog-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .catalog-img-box .emoji-large {
            font-size: 68px;
            filter: drop-shadow(0 8px 12px rgba(0,0,0,0.3));
        }
        .stock-tag {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(16, 185, 129, 0.9);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 12px;
            backdrop-filter: blur(4px);
        }
        .stock-tag.low {
            background: rgba(245, 158, 11, 0.9);
        }
        .stock-tag.out {
            background: rgba(239, 68, 68, 0.9);
        }
        .catalog-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .catalog-cat {
            color: #60a5fa;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .catalog-title {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 6px;
            line-height: 1.3;
        }
        .catalog-desc {
            color: var(--text-secondary);
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 12px;
            flex: 1;
        }
        .catalog-meta {
            display: flex;
            gap: 12px;
            font-size: 11px;
            color: var(--text-secondary);
            margin-bottom: 14px;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 8px;
        }
        .catalog-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
        }
        .catalog-price {
            font-size: 19px;
            font-weight: 700;
            color: var(--accent-green);
        }
        .btn-add-cart {
            background: var(--shop-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all var(--transition);
        }
        .btn-add-cart:hover {
            background: #2563eb;
            transform: scale(1.02);
        }
        .btn-add-cart:disabled {
            background: var(--border);
            color: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
        }

        /* Cart Drawer */
        .cart-drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: none;
            justify-content: flex-end;
            animation: fadeIn 0.2s ease;
        }
        .cart-drawer {
            background: var(--bg-card);
            border-left: 1px solid var(--border);
            width: 100%;
            max-width: 440px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            box-shadow: -8px 0 30px rgba(0,0,0,0.6);
            animation: slideLeft 0.3s ease;
        }
        @keyframes slideLeft {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        .drawer-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .drawer-header h3 { font-size: 17px; font-weight: 700; }
        .drawer-body {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }
        .drawer-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            background: rgba(18, 21, 30, 0.6);
        }

        /* Order Success Modal */
        .success-box {
            text-align: center;
            padding: 10px 0;
        }
        .success-icon {
            font-size: 56px;
            margin-bottom: 12px;
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes popIn {
            0% { transform: scale(0.3); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* PromptPay QR Mock */
        .qr-card {
            background: #fff;
            color: #111;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            margin: 12px 0;
            border: 2px solid #003d79;
        }
        .qr-card img {
            width: 160px;
            height: 160px;
            margin: 8px auto;
            display: block;
        }

        /* Footer */
        .shop-footer {
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            padding: 24px 20px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 13px;
            margin-top: auto;
        }
    </style>
</head>
<body class="shop-body">

    <!-- Customer Navigation -->
    <header class="shop-navbar">
        <a href="shop.php" class="shop-brand">
            <span class="icon">👕</span>
            <span>ShirtShop</span>
        </a>

        <div class="shop-search">
            <span class="search-icon">🔍</span>
            <input type="text" id="cust-search" placeholder="ค้นหาเสื้อยืด, เสื้อโปโล, ไซส์, สี...">
        </div>

        <div class="shop-nav-actions">
            <!-- Order tracking link -->
            <button class="btn btn-outline btn-sm" onclick="openTrackModal()" style="border-radius: 20px;">
                📦 ตรวจสอบคำสั่งซื้อ
            </button>

            <!-- Cart Toggle Button -->
            <button class="btn-cart-toggle" onclick="toggleCart(true)">
                <span>🛒 ตะกร้า</span>
                <span class="cart-badge" id="cart-badge-count">0</span>
            </button>

            <!-- Backend / Staff Link -->
            <?php if ($isStaffLoggedIn): ?>
                <a href="index.php" class="btn btn-outline btn-sm" style="border-radius: 20px; border-color:var(--accent-blue); color:#93c5fd;" title="กลับหน้าระบบจัดการ">
                    ⚙️ ระบบหลังร้าน
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm" style="border-radius: 20px;" title="เข้าสู่ระบบสำหรับพนักงาน">
                    🔑 พนักงาน
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero Banner —— Premium Redesign -->
    <section class="shop-hero">
        <!-- Decorative grid -->
        <div class="hero-grid-lines"></div>

        <!-- Floating particles -->
        <div class="hero-particles">
            <span class="hero-particle"></span>
            <span class="hero-particle"></span>
            <span class="hero-particle"></span>
            <span class="hero-particle"></span>
            <span class="hero-particle"></span>
            <span class="hero-particle"></span>
            <span class="hero-particle"></span>
            <span class="hero-particle"></span>
        </div>

        <div class="shop-hero-content">
            <!-- Animated badge -->
            <div class="hero-tag">
                <span class="tag-dot"></span>
                New Season Collection 2026
            </div>

            <!-- Main heading -->
            <h1 class="hero-title">เสื้อผ้าแฟชั่นคุณภาพ<br>ใส่สบาย&ensp;ทรงสวย</h1>

            <p class="hero-desc">
                เนื้อผ้า Cotton 100% คุณภาพเกรดพรีเมียม ระบายอากาศดีเยี่ยม<br>
                มีให้เลือกหลากหลายสไตล์ ทั้งเสื้อยืด เสื้อโปโล Oversize และเสื้อฮาวาย<br>
                จัดส่งรวดเร็วทั่วไทย 🇹🇭
            </p>

            <!-- CTA Buttons -->
            <div class="hero-cta-group">
                <button class="hero-btn-primary" onclick="document.getElementById('catalog-grid').scrollIntoView({behavior:'smooth'})">
                    🛍️ ช้อปเลย
                </button>
                <button class="hero-btn-secondary" onclick="toggleCart(true)">
                    🛒 ดูตะกร้าของฉัน
                </button>
            </div>

            <!-- Stats row -->
            <div class="hero-stats">
                <div class="hero-stat-item">
                    <span class="hero-stat-num">500+</span>
                    <span class="hero-stat-label">สินค้าในร้าน</span>
                </div>
                <div class="hero-stat-item">
                    <span class="hero-stat-num">Cotton 100%</span>
                    <span class="hero-stat-label">คุณภาพเกรดพรีเมียม</span>
                </div>
                <div class="hero-stat-item">
                    <span class="hero-stat-num">1-3 วัน</span>
                    <span class="hero-stat-label">จัดส่งรวดเร็ว</span>
                </div>
                <div class="hero-stat-item">
                    <span class="hero-stat-num">4.9 ⭐</span>
                    <span class="hero-stat-label">คะแนนความพึงพอใจ</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Storefront Content -->
    <main class="shop-container">
        <!-- Category Filter Chips -->
        <div class="category-scroll-bar" id="cust-category-bar">
            <button class="cat-chip active" data-cat="">ทั้งหมด (All)</button>
            <button class="cat-chip" data-cat="เสื้อยืด">เสื้อยืด</button>
            <button class="cat-chip" data-cat="เสื้อโปโล">เสื้อโปโล</button>
            <button class="cat-chip" data-cat="Oversize">Oversize</button>
            <button class="cat-chip" data-cat="เสื้อฮาวาย">เสื้อฮาวาย</button>
            <button class="cat-chip" data-cat="เสื้อแขนยาว">เสื้อแขนยาว</button>
            <button class="cat-chip" data-cat="แจ็คเก็ต/กั๊ก">แจ็คเก็ต/กั๊ก</button>
        </div>

        <!-- Catalog Products Grid -->
        <div class="catalog-grid" id="catalog-grid">
            <div class="loading" style="grid-column: 1/-1;">กำลังโหลดสินค้า...</div>
        </div>
    </main>

    <!-- Cart Drawer Modal -->
    <div class="cart-drawer-overlay" id="cart-drawer" onclick="handleDrawerOverlayClick(event)">
        <div class="cart-drawer">
            <div class="drawer-header">
                <h3>🛒 ตะกร้าสินค้าของคุณ</h3>
                <button class="modal-close" onclick="toggleCart(false)">✕</button>
            </div>

            <div class="drawer-body">
                <div id="drawer-cart-items">
                    <p class="empty-cart">ยังไม่มีสินค้าในตะกร้า</p>
                </div>

                <!-- Checkout Form in Cart Drawer -->
                <div id="checkout-form-section" style="display:none; margin-top: 20px; border-top: 1px solid var(--border); padding-top: 16px;">
                    <h4 style="font-size: 14px; margin-bottom: 12px; color: var(--accent-blue);">📍 ข้อมูลการจัดส่งและชำระเงิน</h4>
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล ผู้รับ *</label>
                        <input type="text" id="cust-order-name" class="form-control" placeholder="เช่น นายสมชาย ใจดี" required>
                    </div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์ติดต่อ *</label>
                        <input type="tel" id="cust-order-phone" class="form-control" placeholder="เช่น 081-234-5678" required>
                    </div>
                    <div class="form-group">
                        <label>ที่อยู่จัดส่งพัสดุ *</label>
                        <textarea id="cust-order-address" class="form-control" rows="2" placeholder="บ้านเลขที่, ถนน, แขวง/ตำบล, อำเภอ, จังหวัด, รหัสไปรษณีย์" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>เลือกวิธีชำระเงิน *</label>
                        <div class="payment-selector" id="cust-payment-selector">
                            <div class="payment-opt active" data-method="promptpay" onclick="selectCustPayment('promptpay')">
                                📱 พร้อมเพย์ QR
                            </div>
                            <div class="payment-opt" data-method="cod" onclick="selectCustPayment('cod')">
                                🚚 เก็บเงินปลายทาง
                            </div>
                            <div class="payment-opt" data-method="credit" onclick="selectCustPayment('credit')">
                                💳 บัตรเครดิต
                            </div>
                        </div>
                    </div>

                    <!-- PromptPay QR Demo Box -->
                    <div id="promptpay-box" class="qr-card">
                        <div style="font-weight:700; font-size:13px; color:#003d79;">สแกน QR เพื่อชำระเงิน (PromptPay)</div>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=ShirtShop_Payment_Order" alt="QR Code">
                        <div style="font-size:12px; color:#333;">บัญชี: บริษัท เชิ้ตช็อป แฟชั่น จำกัด</div>
                    </div>
                </div>
            </div>

            <div class="drawer-footer">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <span style="color:var(--text-secondary);">ยอดรวมทั้งสิ้น:</span>
                    <span style="font-size:22px; font-weight:700; color:var(--accent-green);" id="drawer-total-amount">฿0.00</span>
                </div>
                <button class="btn btn-success btn-block" id="btn-proceed-checkout" onclick="proceedOrSubmitOrder()" style="padding:12px; font-size:14px; font-weight:600;">
                    🛍️ ดำเนินการสั่งซื้อ
                </button>
            </div>
        </div>
    </div>

    <!-- Order Success Modal -->
    <div class="modal-overlay" id="order-success-modal" style="display:none;">
        <div class="modal" style="max-width: 440px;">
            <div class="modal-header">
                <h3>🎉 คำสั่งซื้อสำเร็จ!</h3>
                <button class="modal-close" onclick="closeModal('order-success-modal')">✕</button>
            </div>
            <div class="modal-body">
                <div class="success-box">
                    <div class="success-icon">✅</div>
                    <h3 style="margin-bottom:6px; color:#fff;">ขอบคุณสำหรับการสั่งซื้อ</h3>
                    <p style="color:var(--text-secondary); font-size:13px; margin-bottom:14px;">เราได้รับคำสั่งซื้อของคุณเรียบร้อยแล้ว และกำลังจัดเตรียมสินค้าเพื่อจัดส่ง</p>
                    
                    <div style="background:var(--bg-hover); border:1px solid var(--border); border-radius:8px; padding:14px; text-align:left; font-size:13px; line-height:1.6;">
                        <div><strong>หมายเลขคำสั่งซื้อ:</strong> <span id="success-order-id" style="color:var(--accent-blue); font-weight:700;">#0</span></div>
                        <div><strong>ผู้รับ:</strong> <span id="success-cust-name"></span></div>
                        <div><strong>ยอดรวม:</strong> <span id="success-order-total" style="color:var(--accent-green); font-weight:700;">฿0.00</span></div>
                        <div><strong>ช่องทางชำระ:</strong> <span id="success-order-pay"></span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="justify-content:center; gap:10px;">
                <button class="btn btn-outline" onclick="closeModal('order-success-modal')">เลือกซื้อสินค้าต่อ</button>
                <button class="btn btn-primary" onclick="printCustomerReceipt()">🖨️ พิมพ์ใบเสร็จ</button>
            </div>
        </div>
    </div>

    <!-- Order Tracking Modal -->
    <div class="modal-overlay" id="track-modal" style="display:none;">
        <div class="modal" style="max-width: 480px;">
            <div class="modal-header">
                <h3>📦 ตรวจสอบสถานะคำสั่งซื้อ</h3>
                <button class="modal-close" onclick="closeModal('track-modal')">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>กรอกเลขที่คำสั่งซื้อ (เช่น 1, 2) หรือเบอร์โทรศัพท์:</label>
                    <div style="display:flex; gap:8px;">
                        <input type="text" id="track-query" class="form-control" placeholder="เลขที่คำสั่งซื้อ หรือเบอร์โทร">
                        <button class="btn btn-primary" onclick="searchOrderStatus()">ค้นหา</button>
                    </div>
                </div>
                <div id="track-result" style="margin-top:16px;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('track-modal')">ปิด</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Footer -->
    <footer class="shop-footer">
        <p>© 2026 ShirtShop. พัฒนาด้วย PHP 8 Object-Oriented Programming (OOP Architecture)</p>
        <p style="font-size:11px; margin-top:4px; opacity:0.8;">3 Classes (Inventory, Sale, Report) + 1 Shared Database Class</p>
    </footer>

    <!-- Customer Storefront Script -->
    <script>
        const API_SALE = 'api/sale.php';
        let shopCart = [];
        let allCatalogProducts = [];
        let currentCustCat = '';
        let custPaymentMethod = 'promptpay';
        let lastCreatedOrderId = null;

        // โหลดสินค้าหน้าร้าน
        async function loadCatalog(category = '') {
            let url = `${API_SALE}?action=products`;
            if (category) url += `&category=${encodeURIComponent(category)}`;
            
            try {
                const res = await fetch(url);
                const products = await res.json();
                allCatalogProducts = products;
                renderCatalog(products);
            } catch (err) {
                document.getElementById('catalog-grid').innerHTML = '<p class="loading">เกิดข้อผิดพลาดในการโหลดสินค้า</p>';
            }
        }

        function renderCatalog(products) {
            const grid = document.getElementById('catalog-grid');
            if (!products.length) {
                grid.innerHTML = '<p class="loading" style="grid-column:1/-1;">ไม่พบสินค้าในหมวดหมู่นี้</p>';
                return;
            }

            grid.innerHTML = products.map(p => {
                const isOut = p.stock <= 0;
                const isLow = p.stock > 0 && p.stock <= 5;
                const stockLabel = isOut ? 'สินค้าหมด' : (isLow ? `เหลือ ${p.stock} ตัวสุดท้าย` : `มีสินค้า ${p.stock} ตัว`);
                const stockClass = isOut ? 'out' : (isLow ? 'low' : '');

                return `
                <div class="catalog-card" id="product-${p.id}">
                    <div class="catalog-img-box">
                        ${p.image ? `<img src="uploads/${p.image}" alt="${p.name}">` : `<div class="emoji-large">👕</div>`}
                        <span class="stock-tag ${stockClass}">${stockLabel}</span>
                    </div>
                    <div class="catalog-body">
                        <div class="catalog-cat">${p.category || 'ทั่วไป'}</div>
                        <h3 class="catalog-title">${p.name}</h3>
                        <p class="catalog-desc">${p.description || 'เสื้อผ้าแฟชั่นสวมใส่สบาย ตัดเย็บประณีต'}</p>
                        <div class="catalog-meta">
                            <span>ไซส์: <strong>${p.size || 'Free Size'}</strong></span>
                            <span>สี: <strong>${p.color || '-'}</strong></span>
                        </div>
                        <div class="catalog-footer">
                            <span class="catalog-price">฿${parseFloat(p.price).toLocaleString('th-TH', {minimumFractionDigits: 2})}</span>
                            <button class="btn-add-cart" ${isOut ? 'disabled' : ''} onclick="addToShopCart(${p.id})">
                                ${isOut ? 'สินค้าหมด' : '🛒 เพิ่มลงตะกร้า'}
                            </button>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }

        // ค้นหาสินค้า Real-time
        let custSearchTimer;
        document.getElementById('cust-search').addEventListener('input', e => {
            clearTimeout(custSearchTimer);
            custSearchTimer = setTimeout(() => {
                const kw = e.target.value.toLowerCase().trim();
                const filtered = allCatalogProducts.filter(p =>
                    p.name.toLowerCase().includes(kw) ||
                    (p.color || '').toLowerCase().includes(kw) ||
                    (p.size || '').toLowerCase().includes(kw) ||
                    (p.category || '').toLowerCase().includes(kw)
                );
                renderCatalog(filtered);
            }, 200);
        });

        // หมวดหมู่ Filter
        document.getElementById('cust-category-bar').addEventListener('click', e => {
            const chip = e.target.closest('.cat-chip');
            if (!chip) return;
            document.querySelectorAll('#cust-category-bar .cat-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentCustCat = chip.dataset.cat;
            loadCatalog(currentCustCat);
        });

        // ตะกร้าสินค้าออนไลน์
        function toggleCart(open) {
            const drawer = document.getElementById('cart-drawer');
            drawer.style.display = open ? 'flex' : 'none';
            if (open) renderShopCart();
        }

        function handleDrawerOverlayClick(e) {
            if (e.target.id === 'cart-drawer') toggleCart(false);
        }

        function addToShopCart(productId) {
            const product = allCatalogProducts.find(p => p.id === productId);
            if (!product) return;

            const existing = shopCart.find(c => c.product_id === productId);
            if (existing) {
                if (existing.quantity >= product.stock) {
                    showToast('ขออภัย สินค้าในสต็อกไม่เพียงพอ', 'error'); return;
                }
                existing.quantity++;
            } else {
                shopCart.push({
                    product_id: productId,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity: 1,
                    max: product.stock
                });
            }
            updateCartBadge();
            showToast(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว`, 'success');
        }

        function updateCartBadge() {
            const count = shopCart.reduce((s, c) => s + c.quantity, 0);
            document.getElementById('cart-badge-count').textContent = count;
        }

        function changeShopCartQty(productId, delta) {
            const idx = shopCart.findIndex(c => c.product_id === productId);
            if (idx === -1) return;
            shopCart[idx].quantity += delta;
            if (shopCart[idx].quantity <= 0) {
                shopCart.splice(idx, 1);
            } else if (shopCart[idx].quantity > shopCart[idx].max) {
                shopCart[idx].quantity = shopCart[idx].max;
                showToast('สต็อกไม่เพียงพอ', 'error');
            }
            updateCartBadge();
            renderShopCart();
        }

        function renderShopCart() {
            const el = document.getElementById('drawer-cart-items');
            const total = shopCart.reduce((s, c) => s + c.price * c.quantity, 0);

            if (!shopCart.length) {
                el.innerHTML = '<p class="empty-cart" style="padding:40px 0; color:var(--text-secondary); text-align:center;">ยังไม่มีสินค้าในตะกร้า<br><small>กด "🛒 เพิ่มลงตะกร้า" เพื่อเลือกสินค้า</small></p>';
                document.getElementById('checkout-form-section').style.display = 'none';
                document.getElementById('btn-proceed-checkout').textContent = 'เลือกสินค้าหน้าร้าน';
            } else {
                el.innerHTML = shopCart.map(c => `
                    <div class="cart-item">
                        <div class="cart-item-name">
                            <strong>${c.name}</strong><br>
                            <span style="color:var(--accent-green)">฿${c.price.toLocaleString('th-TH', {minimumFractionDigits:2})}</span>
                        </div>
                        <div class="qty-control">
                            <button class="qty-btn" onclick="changeShopCartQty(${c.product_id}, -1)">-</button>
                            <span class="qty-num">${c.quantity}</span>
                            <button class="qty-btn" onclick="changeShopCartQty(${c.product_id}, 1)">+</button>
                        </div>
                        <div class="cart-item-price">฿${(c.price * c.quantity).toLocaleString('th-TH', {minimumFractionDigits:2})}</div>
                    </div>
                `).join('');

                document.getElementById('checkout-form-section').style.display = 'block';
                document.getElementById('btn-proceed-checkout').textContent = '✅ ยืนยันคำสั่งซื้อ';
            }

            document.getElementById('drawer-total-amount').textContent = '฿' + total.toLocaleString('th-TH', {minimumFractionDigits: 2});
        }

        // เลือกวิธีชำระเงิน
        function selectCustPayment(method) {
            custPaymentMethod = method;
            document.querySelectorAll('#cust-payment-selector .payment-opt').forEach(opt => {
                opt.classList.toggle('active', opt.dataset.method === method);
            });
            document.getElementById('promptpay-box').style.display = (method === 'promptpay') ? 'block' : 'none';
        }

        // ดำเนินการสั่งซื้อ
        async function proceedOrSubmitOrder() {
            if (!shopCart.length) {
                toggleCart(false);
                return;
            }

            const name = document.getElementById('cust-order-name').value.trim();
            const phone = document.getElementById('cust-order-phone').value.trim();
            const address = document.getElementById('cust-order-address').value.trim();
            const total = shopCart.reduce((s, c) => s + c.price * c.quantity, 0);

            if (!name) { showToast('กรุณากรอกชื่อ-นามสกุล ผู้รับ', 'error'); return; }
            if (!phone) { showToast('กรุณากรอกเบอร์โทรศัพท์ติดต่อ', 'error'); return; }
            if (!address) { showToast('กรุณากรอกที่อยู่จัดส่งพัสดุ', 'error'); return; }

            const btn = document.getElementById('btn-proceed-checkout');
            btn.disabled = true;
            btn.textContent = 'กำลังส่งข้อมูลคำสั่งซื้อ...';

            try {
                const res = await fetch(`${API_SALE}?action=order`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_name:    name,
                        customer_phone:   phone,
                        shipping_address: address,
                        payment_method:   custPaymentMethod,
                        amount_received:  total,
                        items: shopCart.map(c => ({ product_id: c.product_id, quantity: c.quantity }))
                    })
                });

                const data = await res.json();
                btn.disabled = false;
                btn.textContent = '✅ ยืนยันคำสั่งซื้อ';

                if (data.success) {
                    lastCreatedOrderId = data.order_id;
                    shopCart = [];
                    updateCartBadge();
                    toggleCart(false);
                    loadCatalog(currentCustCat); // รีเฟรชสต็อกหน้าร้าน

                    // เปิดหน้า Success Modal
                    document.getElementById('success-order-id').textContent = `#${data.order_id}`;
                    document.getElementById('success-cust-name').textContent = name;
                    document.getElementById('success-order-total').textContent = '฿' + total.toLocaleString('th-TH', {minimumFractionDigits: 2});
                    document.getElementById('success-order-pay').textContent = (custPaymentMethod === 'promptpay' ? '📱 พร้อมเพย์ QR' : (custPaymentMethod === 'cod' ? '🚚 เก็บเงินปลายทาง (COD)' : '💳 บัตรเครดิต'));
                    document.getElementById('order-success-modal').style.display = 'flex';
                } else {
                    showToast(data.message || 'เกิดข้อผิดพลาดในการสั่งซื้อ', 'error');
                }
            } catch (err) {
                btn.disabled = false;
                btn.textContent = '✅ ยืนยันคำสั่งซื้อ';
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }

        // ตรวจสอบสถานะคำสั่งซื้อ (Order Tracking)
        function openTrackModal() {
            document.getElementById('track-query').value = '';
            document.getElementById('track-result').innerHTML = '';
            document.getElementById('track-modal').style.display = 'flex';
        }

        async function searchOrderStatus() {
            const q = document.getElementById('track-query').value.trim();
            if (!q) { showToast('กรุณากรอกเลขคำสั่งซื้อหรือเบอร์โทร', 'error'); return; }

            const resDiv = document.getElementById('track-result');
            resDiv.innerHTML = '<p class="loading">กำลังค้นหา...</p>';

            try {
                // หากเป็นตัวเลขล้วน ลองค้นหา order_id ก่อน
                let order = null;
                if (!isNaN(q)) {
                    const res = await fetch(`${API_SALE}?action=detail&id=${q}`);
                    const data = await res.json();
                    if (!data.error) order = data;
                }

                if (!order) {
                    const allOrdersRes = await fetch(API_SALE);
                    const all = await allOrdersRes.json();
                    order = all.find(o => o.id == q || (o.customer_phone && o.customer_phone.includes(q)));
                }

                if (order) {
                    const statusMap = { pending: '🟡 รอดำเนินการ', confirmed: '🟢 ยืนยันแล้ว / กำลังเตรียมจัดส่ง', cancelled: '🔴 ยกเลิก' };
                    resDiv.innerHTML = `
                        <div style="background:var(--bg-hover); border:1px solid var(--border); border-radius:8px; padding:16px; font-size:13px; line-height:1.7;">
                            <div style="font-size:15px; font-weight:700; color:#fff; margin-bottom:6px;">คำสั่งซื้อ #${order.id}</div>
                            <div><strong>สถานะ:</strong> ${statusMap[order.status] || order.status}</div>
                            <div><strong>ลูกค้า:</strong> ${order.customer_name}</div>
                            <div><strong>เบอร์โทร:</strong> ${order.customer_phone || '-'}</div>
                            <div><strong>ยอดสุทธิ:</strong> ฿${parseFloat(order.total_amount).toLocaleString('th-TH', {minimumFractionDigits:2})}</div>
                            <div><strong>วันที่สั่งซื้อ:</strong> ${new Date(order.created_at).toLocaleString('th-TH')}</div>
                        </div>
                    `;
                } else {
                    resDiv.innerHTML = '<div style="color:var(--accent-red); padding:10px 0; text-align:center;">❌ ไม่พบข้อมูลคำสั่งซื้อ กรุณาตรวจสอบเลขคำสั่งซื้อหรือเบอร์โทรอีกครั้ง</div>';
                }
            } catch (err) {
                resDiv.innerHTML = '<div style="color:var(--accent-red); padding:10px 0;">เกิดข้อผิดพลาดในการค้นหา</div>';
            }
        }

        function printCustomerReceipt() {
            if (lastCreatedOrderId) {
                window.open(`index.php?print_receipt=${lastCreatedOrderId}`, '_blank');
            } else {
                window.print();
            }
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3000);
        }

        // เริ่มต้นโหลดสินค้า
        loadCatalog();
    </script>
</body>
</html>
