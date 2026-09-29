<?php
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$error = '';

// หากล็อกอินอยู่แล้ว ให้เข้าสู่ระบบหน้าหลักได้ทันที
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// ตรวจสอบเมื่อส่งฟอร์มแบบดั้งเดิม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $res = $auth->login($username, $password);
    if ($res['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ShirtShop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body.login-body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at 50% 20%, #1e2436 0%, #0f1117 80%);
            padding: 20px;
        }
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            padding: 36px 32px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.4s ease;
        }
        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-logo {
            font-size: 48px;
            margin-bottom: 8px;
            display: inline-block;
            filter: drop-shadow(0 4px 10px rgba(79, 142, 247, 0.4));
        }
        .login-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 13px;
            margin-top: 4px;
        }
        .demo-box {
            background: rgba(79, 142, 247, 0.08);
            border: 1px dashed var(--accent-blue);
            border-radius: var(--radius-sm);
            padding: 12px;
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        .demo-box strong { color: var(--accent-blue); }
        .demo-accounts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
        }
        .btn-demo {
            background: var(--bg-hover);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            text-align: center;
            transition: all var(--transition);
        }
        .btn-demo:hover {
            border-color: var(--accent-blue);
            background: rgba(79, 142, 247, 0.15);
            color: #fff;
        }
        .error-alert {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--accent-red);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 18px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">👕</div>
            <h1 class="login-title">ShirtShop Login</h1>
            <p class="login-subtitle">ระบบจัดการร้านขายเสื้อผ้า (PHP OOP)</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-alert">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="login-form">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้งาน (Username)</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="เช่น admin" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน (Password)</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 14px; font-weight: 600;">
                🔑 เข้าสู่ระบบ
            </button>
        </form>

        <div class="demo-box">
            <strong>💡 บัญชีผู้ใช้สำหรับทดสอบ (Demo Accounts):</strong>
            <div class="demo-accounts">
                <button type="button" class="btn-demo" onclick="fillDemo('admin', 'admin123')">
                    👑 ผู้ดูแล (Admin)<br><small>admin / admin123</small>
                </button>
                <button type="button" class="btn-demo" onclick="fillDemo('cashier', 'cashier123')">
                    🛒 พนักงาน (Cashier)<br><small>cashier / cashier123</small>
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="shop.php" style="color: var(--shop-primary, #60a5fa); text-decoration: none; font-size: 13px; font-weight: 500;">
                🛍️ ไปยังหน้าร้านสำหรับลูกค้า (Customer Storefront) →
            </a>
        </div>
    </div>

    <script>
        function fillDemo(u, p) {
            document.getElementById('username').value = u;
            document.getElementById('password').value = p;
        }
    </script>
</body>
</html>
