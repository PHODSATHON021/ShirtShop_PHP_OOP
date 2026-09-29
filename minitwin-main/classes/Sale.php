<?php
require_once __DIR__ . '/ShopModule.php';
require_once __DIR__ . '/Product.php';

/**
 * Class Sale (Class 2) - รับผิดชอบโดยคนที่ 2
 * สืบทอดคุณสมบัติมาจาก ShopModule (OOP: Inheritance)
 * หน้าที่: จัดการการขาย ตะกร้าสินค้า การคิดเงิน/เงินทอน และคำสั่งซื้อ (Encapsulation of Sale Logic)
 * Use Cases: UC-02.1 ถึง UC-02.6
 */
class Sale extends ShopModule {

    public function __construct() {
        parent::__construct('Sale Module (การขายและคำสั่งซื้อ)');
    }

    /**
     * OOP: Polymorphism - การ Implement เมธอด getSummary() ตามที่แม่ (ShopModule) กำหนด
     */
    public function getSummary(): array {
        $totalOrders   = $this->db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE status != 'cancelled'")['total'] ?? 0;
        $pendingOrders = $this->db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'")['total'] ?? 0;
        $totalSales    = $this->db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'")['total'] ?? 0;

        return [
            'module'         => $this->getModuleName(),
            'total_orders'   => (int)$totalOrders,
            'pending_orders' => (int)$pendingOrders,
            'total_sales'    => (float)$totalSales
        ];
    }

    // UC-02.3: บันทึกคำสั่งซื้อ + ระบบคำนวณเงินทอนและช่องทางชำระเงิน
    public function createOrder(
        string $customerName,
        string $customerPhone,
        array $items,
        string $paymentMethod = 'cash',
        float $amountReceived = 0.0,
        string $shippingAddress = ''
    ): array {
        $cleanName    = $this->cleanInput($customerName);
        $cleanPhone   = $this->cleanInput($customerPhone);
        $cleanAddress = $this->cleanInput($shippingAddress);
        $method       = in_array($paymentMethod, ['cash', 'promptpay', 'credit', 'cod']) ? $paymentMethod : 'cash';

        if (empty($cleanName)) {
            $cleanName = 'ลูกค้าทั่วไป (หน้าร้าน)';
        }
        if (empty($items)) {
            return ['success' => false, 'message' => 'ไม่มีสินค้าในตะกร้า'];
        }

        // ใช้งาน Transaction ร่วมกับ Shared Database Class
        $this->db->beginTransaction();
        try {
            $totalAmount = 0.0;
            $orderItems  = [];

            // ตรวจสอบสต็อกและคำนวณราคาด้วย Product Entity
            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantity  = (int)($item['quantity'] ?? 0);

                if ($quantity <= 0) continue;

                $row = $this->db->fetchOne(
                    "SELECT * FROM products WHERE id = ? FOR UPDATE",
                    [$productId]
                );

                if (!$row) {
                    throw new Exception("ไม่พบสินค้า ID: {$productId}");
                }

                $product = Product::fromArray($row);

                if (!$product->canDeduct($quantity)) {
                    throw new Exception("สต็อก '{$product->getName()}' ไม่เพียงพอ (คงเหลือ {$product->getStock()} ตัว)");
                }

                $subtotal     = $product->getPrice() * $quantity;
                $totalAmount += $subtotal;
                $orderItems[] = [
                    'product_id' => $product->getId(),
                    'quantity'   => $quantity,
                    'unit_price' => $product->getPrice(),
                    'name'       => $product->getName(),
                ];
            }

            if (empty($orderItems)) {
                throw new Exception("ไม่มีรายการสินค้าที่ถูกต้อง");
            }

            // คำนวณเงินทอน
            if ($method === 'cash') {
                if ($amountReceived > 0 && $amountReceived < $totalAmount) {
                    throw new Exception("ยอดเงินที่รับมา (฿" . number_format($amountReceived, 2) . ") น้อยกว่ายอดรวมที่ต้องชำระ (฿" . number_format($totalAmount, 2) . ")");
                }
                if ($amountReceived <= 0) {
                    $amountReceived = $totalAmount; // พอดี
                }
            } else {
                $amountReceived = $totalAmount;
            }
            $changeAmount = max(0.0, $amountReceived - $totalAmount);

            // บันทึก Order
            $orderId = $this->db->insert(
                "INSERT INTO orders (customer_name, customer_phone, total_amount, payment_method, amount_received, change_amount, shipping_address, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')",
                [$cleanName, $cleanPhone, $totalAmount, $method, $amountReceived, $changeAmount, $cleanAddress]
            );

            // บันทึกรายการสินค้าใน Order และตัดสต็อก
            foreach ($orderItems as $item) {
                $this->db->insert(
                    "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)",
                    [$orderId, $item['product_id'], $item['quantity'], $item['unit_price']]
                );
                $this->db->execute(
                    "UPDATE products SET stock = stock - ? WHERE id = ?",
                    [$item['quantity'], $item['product_id']]
                );
            }

            $this->db->commit();

            return [
                'success'         => true,
                'order_id'        => $orderId,
                'total'           => $totalAmount,
                'amount_received' => $amountReceived,
                'change'          => $changeAmount,
                'payment_method'  => $method,
                'customer_name'   => $cleanName,
                'items'           => $orderItems,
                'message'         => "ชำระเงินและสร้างคำสั่งซื้อ #{$orderId} สำเร็จ"
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // UC-02.4: ดูประวัติคำสั่งซื้อทั้งหมด
    public function getAllOrders(string $status = ''): array {
        if ($status) {
            return $this->db->fetchAll(
                "SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC",
                [$this->cleanInput($status)]
            );
        }
        return $this->db->fetchAll("SELECT * FROM orders ORDER BY created_at DESC");
    }

    // UC-02.6: ดูรายละเอียดคำสั่งซื้อเพื่อพิมพ์ใบเสร็จ (Receipt Data)
    public function getOrderDetail(int $orderId): ?array {
        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) return null;

        $order['items'] = $this->db->fetchAll(
            "SELECT oi.*, p.name, p.image, p.size, p.color FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?",
            [$orderId]
        );

        return $order;
    }

    // UC-02.5: ยืนยันคำสั่งซื้อ
    public function confirmOrder(int $orderId): array {
        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            return ['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ'];
        }
        if ($order['status'] !== 'pending') {
            return ['success' => false, 'message' => "คำสั่งซื้อนี้มีสถานะ: {$order['status']} อยู่แล้ว"];
        }

        $this->db->execute(
            "UPDATE orders SET status = 'confirmed' WHERE id = ?",
            [$orderId]
        );
        return ['success' => true, 'message' => "ยืนยันคำสั่งซื้อ #{$orderId} สำเร็จ"];
    }

    // UC-02.5: ยกเลิกคำสั่งซื้อ (คืนสต็อกอัตโนมัติด้วย Transaction)
    public function cancelOrder(int $orderId): array {
        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            return ['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ'];
        }
        if ($order['status'] === 'cancelled') {
            return ['success' => false, 'message' => 'คำสั่งซื้อนี้ถูกยกเลิกไปแล้ว'];
        }

        $this->db->beginTransaction();
        try {
            // คืนสต็อกตามจำนวนที่สั่งซื้อ
            $items = $this->db->fetchAll(
                "SELECT * FROM order_items WHERE order_id = ?",
                [$orderId]
            );
            foreach ($items as $item) {
                $this->db->execute(
                    "UPDATE products SET stock = stock + ? WHERE id = ?",
                    [(int)$item['quantity'], (int)$item['product_id']]
                );
            }

            $this->db->execute(
                "UPDATE orders SET status = 'cancelled' WHERE id = ?",
                [$orderId]
            );

            $this->db->commit();
            return ['success' => true, 'message' => "ยกเลิกคำสั่งซื้อ #{$orderId} และคืนสต็อกสินค้าเรียบร้อย"];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ดึงสินค้าที่มีสต็อกพร้อมขาย (สำหรับหน้า POS)
    public function getAvailableProducts(string $category = ''): array {
        if (!empty($category)) {
            return $this->db->fetchAll(
                "SELECT * FROM products WHERE stock > 0 AND category = ? ORDER BY name ASC",
                [$this->cleanInput($category)]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM products WHERE stock > 0 ORDER BY name ASC"
        );
    }
}
?>
