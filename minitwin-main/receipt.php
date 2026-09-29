<?php
/**
 * ShirtShop - Dedicated Printable Receipt Page
 * แสดงและพิมพ์ใบเสร็จรับเงินมาตรฐาน (Thermal Receipt Format)
 */
require_once __DIR__ . '/classes/Sale.php';

$orderId = (int)($_GET['id'] ?? 0);
$autoPrint = isset($_GET['autoprint']) && $_GET['autoprint'] == '1';

$sale = new Sale();
$order = $orderId > 0 ? $sale->getOrderDetail($orderId) : null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรับเงิน #<?= $order ? $order['id'] : 'N/A' ?> - ShirtShop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Noto Sans Thai', 'Courier New', monospace;
            background: #f1f5f9;
            color: #111;
            padding: 24px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        .action-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print:hover { background: #1d4ed8; }
        .btn-close {
            background: #64748b;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-close:hover { background: #475569; }

        .receipt-card {
            background: #fff;
            width: 100%;
            max-width: 360px;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            line-height: 1.5;
            font-size: 13px;
        }
        .r-center { text-align: center; }
        .r-title { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .r-sub { font-size: 11px; color: #555; }
        .r-divider { border-top: 1px dashed #94a3b8; margin: 12px 0; }
        .r-double-divider { border-top: 2px solid #1e293b; margin: 12px 0; }
        .r-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .r-items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 13px;
        }
        .r-items-table th {
            text-align: left;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 0;
            color: #475569;
            font-size: 12px;
        }
        .r-items-table td {
            padding: 6px 0;
            border-bottom: 1px dashed #f1f5f9;
        }
        .r-total {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }
        .r-footer {
            text-align: center;
            margin-top: 16px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
        }

        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
            }
            .action-bar { display: none !important; }
            .receipt-card {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 10px !important;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <button class="btn-print" onclick="window.print()">🖨️ สั่งพิมพ์ใบเสร็จ (Print)</button>
        <button class="btn-close" onclick="window.close()">✕ ปิดหน้าต่าง</button>
    </div>

    <?php if (!$order): ?>
        <div class="receipt-card r-center">
            <h3 style="color:#ef4444; margin-bottom:8px;">❌ ไม่พบข้อมูลคำสั่งซื้อ</h3>
            <p style="color:#64748b; font-size:13px;">กรุณาตรวจสอบหมายเลขคำสั่งซื้อใหม่อีกครั้ง</p>
        </div>
    <?php else: 
        $methodNames = ['cash' => '💵 เงินสด', 'promptpay' => '📱 พร้อมเพย์', 'credit' => '💳 บัตรเครดิต', 'cod' => '🚚 เก็บเงินปลายทาง (COD)'];
        $methodText = $methodNames[$order['payment_method']] ?? $order['payment_method'];
        $received = (float)($order['amount_received'] ?: $order['total_amount']);
        $change   = (float)($order['change_amount'] ?: 0);
    ?>
        <div class="receipt-card">
            <div class="r-center">
                <div class="r-title">👕 SHIRTSHOP</div>
                <div class="r-sub">ระบบขายเสื้อผ้าแฟชั่นและเครื่องแต่งกาย</div>
                <div class="r-sub">โทร: 02-123-4567 | เลขประจำตัวผู้เสียภาษี: 0105562000000</div>
            </div>

            <div class="r-divider"></div>

            <div class="r-row"><span>เลขที่ใบเสร็จ:</span><strong>#<?= $order['id'] ?></strong></div>
            <div class="r-row"><span>วันที่:</span><span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?> น.</span></div>
            <div class="r-row"><span>ลูกค้า:</span><strong><?= htmlspecialchars($order['customer_name']) ?></strong></div>
            <?php if (!empty($order['customer_phone'])): ?>
                <div class="r-row"><span>เบอร์โทร:</span><span><?= htmlspecialchars($order['customer_phone']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($order['shipping_address'])): ?>
                <div class="r-row" style="flex-direction: column; gap: 2px;">
                    <span style="color:#64748b;">ที่อยู่จัดส่ง:</span>
                    <span style="font-size: 11px;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></span>
                </div>
            <?php endif; ?>
            <div class="r-row"><span>วิธีชำระเงิน:</span><span><?= $methodText ?></span></div>

            <div class="r-divider"></div>

            <table class="r-items-table">
                <thead>
                    <tr>
                        <th>รายการ</th>
                        <th style="text-align:center;">จำนวน</th>
                        <th style="text-align:right;">ราคา</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($item['name']) ?>
                            <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                <br><small style="color:#64748b"><?= htmlspecialchars($item['color'] ?? '') ?> <?= htmlspecialchars($item['size'] ?? '') ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;"><?= (int)$item['quantity'] ?></td>
                        <td style="text-align:right;">฿<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="r-double-divider"></div>

            <div class="r-row r-total">
                <span>ยอดชำระสุทธิ:</span>
                <span>฿<?= number_format($order['total_amount'], 2) ?></span>
            </div>

            <?php if ($order['payment_method'] === 'cash'): ?>
                <div class="r-row" style="color:#475569;">
                    <span>รับเงินมา:</span>
                    <span>฿<?= number_format($received, 2) ?></span>
                </div>
                <div class="r-row" style="color:#16a34a; font-weight:600;">
                    <span>เงินทอน:</span>
                    <span>฿<?= number_format($change, 2) ?></span>
                </div>
            <?php endif; ?>

            <div class="r-divider"></div>

            <div class="r-footer">
                🙏 ขอบพระคุณที่ไว้วางใจใช้บริการ<br>
                สินค้าซื้อแล้วกรุณาเก็บใบเสร็จไว้เพื่อเป็นหลักฐาน
            </div>
        </div>
    <?php endif; ?>

    <?php if ($autoPrint && $order): ?>
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
    <?php endif; ?>

</body>
</html>
