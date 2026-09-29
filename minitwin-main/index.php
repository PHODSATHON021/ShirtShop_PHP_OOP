<?php
/**
 * ShirtShop - Main Application (PHP Version with OOP & Auth)
 */
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Inventory.php';
require_once __DIR__ . '/classes/Sale.php';
require_once __DIR__ . '/classes/Report.php';

// ระบบตรวจสอบการเข้าสู่ระบบ (Authentication)
$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

$dbError = null;
$summary = [
    'total_revenue'  => 0,
    'today_revenue'  => 0,
    'total_orders'   => 0,
    'total_products' => 0,
    'low_stock'      => 0
];

try {
    $report = new Report();
    $summary = $report->getDashboardSummary();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShirtShop - ระบบขายเสื้อ (PHP OOP)</title>
    <meta name="description" content="ระบบจัดการร้านขายเสื้อ ครบทั้งสต็อก การขาย คิดเงินทอน พิมพ์ใบเสร็จ และรายงาน พัฒนาด้วย PHP OOP">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .db-alert {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(185, 28, 28, 0.3));
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .db-alert strong { color: #fff; font-size: 15px; }
        .db-alert ol { margin-left: 20px; margin-top: 8px; }
        .db-alert code { background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 4px; color: #fde047; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <span class="logo-icon">👕</span>
            <span class="logo-text">ShirtShop</span>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" data-page="dashboard" id="nav-dashboard">
                <span class="nav-icon">📊</span>
                <span class="nav-label">Dashboard</span>
            </a>
            <div class="nav-group-label">สต็อก/สินค้า (คนที่ 1)</div>
            <a href="#" class="nav-item" data-page="inventory" id="nav-inventory">
                <span class="nav-icon">📦</span>
                <span class="nav-label">จัดการสินค้า</span>
            </a>
            <div class="nav-group-label">การขาย (คนที่ 2)</div>
            <a href="#" class="nav-item" data-page="pos" id="nav-pos">
                <span class="nav-icon">🛒</span>
                <span class="nav-label">หน้าขาย (POS)</span>
            </a>
            <a href="#" class="nav-item" data-page="orders" id="nav-orders">
                <span class="nav-icon">📋</span>
                <span class="nav-label">คำสั่งซื้อ</span>
            </a>
            <div class="nav-group-label">รายงาน (คนที่ 3)</div>
            <a href="#" class="nav-item" data-page="report" id="nav-report">
                <span class="nav-icon">📈</span>
                <span class="nav-label">รายงานยอดขาย</span>
            </a>
            <div class="nav-group-label">สำหรับลูกค้า</div>
            <a href="shop.php" target="_blank" class="nav-item" style="color:#60a5fa;">
                <span class="nav-icon">🛍️</span>
                <span class="nav-label">หน้าร้านผู้ซื้อ ↗</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <header class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
            <h1 class="page-title" id="page-title">Dashboard</h1>
            <div class="topbar-right">
                <a href="shop.php" target="_blank" class="btn btn-outline btn-sm" style="border-radius:20px; border-color:var(--accent-blue); color:#93c5fd;" title="เปิดดูหน้าร้านออนไลน์สำหรับลูกค้า">
                    🛍️ หน้าร้านผู้ซื้อ ↗
                </a>
                <span class="badge" id="low-stock-badge" style="<?= ($summary['low_stock'] > 0) ? '' : 'display:none;' ?>">
                    ⚠️ สต็อกใกล้หมด <?= (int)$summary['low_stock'] ?> รายการ
                </span>
                <span class="time-display" id="time-display"></span>

                <!-- User Profile & Logout -->
                <div class="user-profile">
                    <span class="user-avatar">👤</span>
                    <span class="user-name"><?= htmlspecialchars($currentUser['fullname'] ?? 'ผู้ใช้งาน') ?></span>
                    <span class="user-role-badge"><?= htmlspecialchars($currentUser['role'] ?? 'staff') ?></span>
                    <a href="logout.php" class="btn-logout" title="ออกจากระบบ">🚪 ออก</a>
                </div>
            </div>
        </header>

        <div class="page-container">
            <?php if ($dbError): ?>
            <!-- Database Connection Warning Banner -->
            <div class="db-alert">
                <strong>⚠️ ยังไม่สามารถเชื่อมต่อฐานข้อมูล MySQL ได้</strong>
                <p>ข้อความแจ้งเตือน: <code><?= htmlspecialchars($dbError) ?></code></p>
                <div style="margin-top: 10px;">
                    <strong>วิธีเปิดใช้งานบน XAMPP:</strong>
                    <ol>
                        <li>เปิด <strong>XAMPP Control Panel</strong> แล้วกด <strong>Start</strong> ที่ <strong>MySQL</strong></li>
                        <li>เปิดเบราว์เซอร์ไปที่ <code>http://localhost/phpmyadmin</code></li>
                        <li>สร้างฐานข้อมูลชื่อ <code>shirt_shop</code> หรือ Import ไฟล์ <code>database.sql</code> ในโฟลเดอร์นี้</li>
                        <li>รีเฟรชหน้านี้ใหม่อีกครั้ง</li>
                    </ol>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dashboard Page -->
            <div class="page active" id="page-dashboard">
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon">💰</div>
                        <div class="stat-body">
                            <div class="stat-label">รายได้รวม</div>
                            <div class="stat-value" id="stat-total-revenue">฿<?= number_format($summary['total_revenue'], 2) ?></div>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon">📅</div>
                        <div class="stat-body">
                            <div class="stat-label">รายได้วันนี้</div>
                            <div class="stat-value" id="stat-today-revenue">฿<?= number_format($summary['today_revenue'], 2) ?></div>
                        </div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-body">
                            <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
                            <div class="stat-value" id="stat-total-orders"><?= (int)$summary['total_orders'] ?></div>
                        </div>
                    </div>
                    <div class="stat-card orange">
                        <div class="stat-icon">👕</div>
                        <div class="stat-body">
                            <div class="stat-label">สินค้าทั้งหมด</div>
                            <div class="stat-value" id="stat-total-products"><?= (int)$summary['total_products'] ?></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="card chart-card">
                        <div class="card-header">
                            <h2>ยอดขาย 7 วันล่าสุด</h2>
                        </div>
                        <div class="card-body">
                            <canvas id="sales-chart" height="100"></canvas>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h2>🏆 สินค้าขายดี Top 5</h2>
                        </div>
                        <div class="card-body" id="top-products-list">
                            <div class="loading">กำลังโหลด...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Page -->
            <div class="page" id="page-inventory">
                <div class="page-actions" style="flex-wrap: wrap;">
                    <div class="search-box">
                        <input type="text" id="inv-search" placeholder="🔍 ค้นหาสินค้า (ชื่อ, สี, ไซส์, หมวดหมู่)..." class="search-input">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-primary" onclick="openAddProductModal()">+ เพิ่มสินค้า</button>
                    </div>
                </div>

                <!-- Category Filters -->
                <div class="category-filter-bar" id="inv-category-bar">
                    <button class="category-pill active" data-cat="">ทั้งหมด</button>
                    <button class="category-pill" data-cat="เสื้อยืด">เสื้อยืด</button>
                    <button class="category-pill" data-cat="เสื้อโปโล">เสื้อโปโล</button>
                    <button class="category-pill" data-cat="Oversize">Oversize</button>
                    <button class="category-pill" data-cat="เสื้อฮาวาย">เสื้อฮาวาย</button>
                    <button class="category-pill" data-cat="เสื้อแขนยาว">เสื้อแขนยาว</button>
                    <button class="category-pill" data-cat="แจ็คเก็ต/กั๊ก">แจ็คเก็ต/กั๊ก</button>
                </div>

                <div class="card">
                    <div class="card-body p0">
                        <table class="table" id="products-table">
                            <thead>
                                <tr>
                                    <th>รูป</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>หมวดหมู่</th>
                                    <th>ราคา</th>
                                    <th>สต็อก</th>
                                    <th>ไซส์</th>
                                    <th>สี</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="products-tbody">
                                <tr><td colspan="8" class="text-center loading">กำลังโหลด...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- POS Page -->
            <div class="page" id="page-pos">
                <div class="pos-layout">
                    <div class="pos-products">
                        <div class="search-box" style="margin-bottom:1rem">
                            <input type="text" id="pos-search" placeholder="🔍 ค้นหาสินค้าขาย..." class="search-input">
                        </div>

                        <!-- POS Category Filter -->
                        <div class="category-filter-bar" id="pos-category-bar">
                            <button class="category-pill active" data-cat="">ทั้งหมด</button>
                            <button class="category-pill" data-cat="เสื้อยืด">เสื้อยืด</button>
                            <button class="category-pill" data-cat="เสื้อโปโล">เสื้อโปโล</button>
                            <button class="category-pill" data-cat="Oversize">Oversize</button>
                            <button class="category-pill" data-cat="เสื้อฮาวาย">เสื้อฮาวาย</button>
                            <button class="category-pill" data-cat="เสื้อแขนยาว">เสื้อแขนยาว</button>
                            <button class="category-pill" data-cat="แจ็คเก็ต/กั๊ก">แจ็คเก็ต/กั๊ก</button>
                        </div>

                        <div class="product-grid" id="pos-product-grid">
                            <div class="loading">กำลังโหลด...</div>
                        </div>
                    </div>

                    <!-- Cart & Payment -->
                    <div class="pos-cart">
                        <h3>🛒 ตะกร้าสินค้า</h3>
                        <div class="cart-items" id="cart-items">
                            <p class="empty-cart">ยังไม่มีสินค้าในตะกร้า</p>
                        </div>

                        <div class="cart-summary">
                            <div class="cart-total">
                                <span>รวมทั้งหมด</span>
                                <span class="total-amount" id="cart-total">฿0.00</span>
                            </div>

                            <div class="customer-form">
                                <input type="text" id="customer-name" placeholder="ชื่อลูกค้า (เว้นว่าง = ลูกค้าทั่วไป)" class="form-control">
                                <input type="text" id="customer-phone" placeholder="เบอร์โทรศัพท์ (ถ้ามี)" class="form-control">
                            </div>

                            <!-- Payment Method Selector -->
                            <div style="margin-top: 8px;">
                                <label style="font-size: 11px; color: var(--text-secondary); margin-bottom: 4px; display: block;">ช่องทางชำระเงิน:</label>
                                <div class="payment-selector" id="payment-method-selector">
                                    <div class="payment-opt active" data-method="cash">💵 เงินสด</div>
                                    <div class="payment-opt" data-method="promptpay">📱 พร้อมเพย์</div>
                                    <div class="payment-opt" data-method="credit">💳 บัตร</div>
                                </div>
                            </div>

                            <!-- Cash Calculation Field -->
                            <div id="cash-payment-section">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <label style="font-size: 11px; color: var(--text-secondary);">รับเงินสดมา (บาท):</label>
                                </div>
                                <input type="number" id="cash-received" class="form-control" placeholder="ใส่จำนวนเงินที่รับ" min="0" step="1">

                                <div class="quick-cash-row">
                                    <button type="button" class="btn-quick-cash" onclick="setCashAmount('exact')">พอดี</button>
                                    <button type="button" class="btn-quick-cash" onclick="setCashAmount(100)">฿100</button>
                                    <button type="button" class="btn-quick-cash" onclick="setCashAmount(500)">฿500</button>
                                    <button type="button" class="btn-quick-cash" onclick="setCashAmount(1000)">฿1,000</button>
                                </div>

                                <div class="change-display">
                                    <span>เงินทอน:</span>
                                    <span class="change-amount" id="display-change">฿0.00</span>
                                </div>
                            </div>

                            <button class="btn btn-success btn-block" onclick="submitOrder()" id="checkout-btn">
                                ✅ ชำระเงิน & ออกใบเสร็จ
                            </button>
                            <button class="btn btn-outline btn-block" onclick="clearCart()">🗑️ ล้างตะกร้า</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Page -->
            <div class="page" id="page-orders">
                <div class="page-actions">
                    <div class="filter-tabs" id="order-filter-tabs">
                        <button class="filter-tab active" data-status="">ทั้งหมด</button>
                        <button class="filter-tab" data-status="confirmed">ยืนยัน/สำเร็จ</button>
                        <button class="filter-tab" data-status="pending">รอยืนยัน</button>
                        <button class="filter-tab" data-status="cancelled">ยกเลิก</button>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ลูกค้า</th>
                                    <th>เบอร์โทร</th>
                                    <th>ยอดรวม</th>
                                    <th>ชำระเงิน</th>
                                    <th>สถานะ</th>
                                    <th>วันที่</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="orders-tbody">
                                <tr><td colspan="8" class="text-center loading">กำลังโหลด...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Report Page -->
            <div class="page" id="page-report">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                    <div class="report-tabs" style="margin-bottom:0;">
                        <button class="report-tab active" onclick="showReport('daily')">📅 รายวัน</button>
                        <button class="report-tab" onclick="showReport('monthly')">🗓️ รายเดือน</button>
                        <button class="report-tab" onclick="showReport('top')">🏆 สินค้าขายดี</button>
                        <button class="report-tab" onclick="showReport('lowstock')">⚠️ สต็อกใกล้หมด</button>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <a href="api/report.php?action=export_csv" class="btn btn-outline" download>
                            📥 ส่งออก Excel (CSV)
                        </a>
                        <button class="btn btn-outline" onclick="window.print()">
                            🖨️ พิมพ์หน้ารายงาน
                        </button>
                    </div>
                </div>

                <div id="report-daily" class="report-section active">
                    <div class="card">
                        <div class="card-header"><h2>รายงานยอดขายรายวัน (30 วันล่าสุด)</h2></div>
                        <div class="card-body p0">
                            <table class="table">
                                <thead><tr><th>วันที่</th><th>จำนวนออเดอร์</th><th>ยอดขาย</th></tr></thead>
                                <tbody id="report-daily-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="report-monthly" class="report-section" style="display:none">
                    <div class="card">
                        <div class="card-header"><h2>รายงานยอดขายรายเดือน</h2></div>
                        <div class="card-body p0">
                            <table class="table">
                                <thead><tr><th>เดือน</th><th>จำนวนออเดอร์</th><th>ยอดขาย</th></tr></thead>
                                <tbody id="report-monthly-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="report-top" class="report-section" style="display:none">
                    <div class="card">
                        <div class="card-header"><h2>สินค้าขายดี Top 5</h2></div>
                        <div class="card-body" id="report-top-body"></div>
                    </div>
                </div>

                <div id="report-lowstock" class="report-section" style="display:none">
                    <div class="card">
                        <div class="card-header"><h2>⚠️ สินค้าสต็อกใกล้หมด</h2></div>
                        <div class="card-body p0">
                            <table class="table">
                                <thead><tr><th>ชื่อสินค้า</th><th>สต็อก</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
                                <tbody id="report-lowstock-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal: Add/Edit Product -->
    <div class="modal-overlay" id="product-modal" style="display:none">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modal-title">เพิ่มสินค้า</h3>
                <button class="modal-close" onclick="closeProductModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="product-form" enctype="multipart/form-data">
                    <input type="hidden" id="product-id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>ชื่อสินค้า *</label>
                            <input type="text" id="p-name" class="form-control" required placeholder="เช่น เสื้อยืด Oversize">
                        </div>
                        <div class="form-group">
                            <label>หมวดหมู่</label>
                            <input type="text" id="p-category" class="form-control" placeholder="เช่น เสื้อยืด, เสื้อโปโล, Oversize">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>ราคา (บาท) *</label>
                            <input type="number" id="p-price" class="form-control" step="0.01" min="0" required placeholder="299">
                        </div>
                        <div class="form-group">
                            <label>จำนวนสต็อก *</label>
                            <input type="number" id="p-stock" class="form-control" min="0" required placeholder="50">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>สี</label>
                            <input type="text" id="p-color" class="form-control" placeholder="เช่น ขาว, ดำ, กรมท่า">
                        </div>
                        <div class="form-group">
                            <label>ไซส์ (คั่นด้วยเครื่องหมายจุลภาค)</label>
                            <input type="text" id="p-size" class="form-control" placeholder="เช่น S,M,L,XL">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>รายละเอียดสินค้า</label>
                        <textarea id="p-desc" class="form-control" rows="2" placeholder="เนื้อผ้า ทรงเสื้อ หรือข้อแนะนำการซัก"></textarea>
                    </div>
                    <div class="form-group">
                        <label>รูปสินค้า</label>
                        <input type="file" id="p-image" class="form-control" accept="image/*">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeProductModal()">ยกเลิก</button>
                <button class="btn btn-primary" onclick="saveProduct()">💾 บันทึกสินค้า</button>
            </div>
        </div>
    </div>

    <!-- Modal: Quick Restock -->
    <div class="modal-overlay" id="restock-modal" style="display:none">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3>➕ เติมสต็อกด่วน</h3>
                <button class="modal-close" onclick="closeModal('restock-modal')">✕</button>
            </div>
            <div class="modal-body">
                <p id="restock-product-name" style="font-weight: 600; margin-bottom: 12px;"></p>
                <input type="hidden" id="restock-product-id">
                <div class="form-group">
                    <label>จำนวนที่ต้องการเติม (ตัว)</label>
                    <input type="number" id="restock-amount" class="form-control" min="1" value="10" autofocus>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('restock-modal')">ยกเลิก</button>
                <button class="btn btn-success" onclick="submitRestock()">✅ ยืนยันการเติมสต็อก</button>
            </div>
        </div>
    </div>

    <!-- Modal: Order Detail & Receipt -->
    <div class="modal-overlay" id="order-detail-modal" style="display:none">
        <div class="modal" style="max-width: 480px;">
            <div class="modal-header">
                <h3 id="order-detail-title">รายละเอียดคำสั่งซื้อ</h3>
                <button class="modal-close" onclick="closeModal('order-detail-modal')">✕</button>
            </div>
            <div class="modal-body" id="order-detail-body"></div>
            <div class="modal-footer" id="order-detail-footer"></div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container" id="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
