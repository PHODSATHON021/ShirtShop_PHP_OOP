<?php
require_once __DIR__ . '/ShopModule.php';

/**
 * Class Report (Class 3) - รับผิดชอบโดยคนที่ 3
 * สืบทอดคุณสมบัติมาจาก ShopModule (OOP: Inheritance)
 * หน้าที่: รายงาน สถิติ และวิเคราะห์ยอดขาย (Encapsulation of Analytical Queries)
 * Use Cases: UC-03.1 ถึง UC-03.5
 */
class Report extends ShopModule {

    public function __construct() {
        // เรียก Constructor ของคลาสแม่ (ShopModule) เพื่อเชื่อมต่อ Database และตั้งชื่อโมดูล
        parent::__construct('Report Module (รายงานและสถิติ)');
    }

    /**
     * OOP: Polymorphism - การ Implement เมธอด getSummary() ตามที่แม่ (ShopModule) กำหนด
     */
    public function getSummary(): array {
        $dash = $this->getDashboardSummary();
        return [
            'module'         => $this->getModuleName(),
            'total_revenue'  => $dash['total_revenue'],
            'today_revenue'  => $dash['today_revenue'],
            'total_orders'   => $dash['total_orders']
        ];
    }

    // UC-03.4: รายงานภาพรวม Dashboard
    public function getDashboardSummary(): array {
        $totalRevenue = $this->db->fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'"
        )['total'] ?? 0;

        $totalOrders = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM orders WHERE status != 'cancelled'"
        )['total'] ?? 0;

        $todayRevenue = $this->db->fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders
             WHERE status != 'cancelled' AND DATE(created_at) = CURDATE()"
        )['total'] ?? 0;

        $totalProducts = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM products"
        )['total'] ?? 0;

        $lowStock = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM products WHERE stock <= 5"
        )['total'] ?? 0;

        return [
            'total_revenue'  => (float)$totalRevenue,
            'total_orders'   => (int)$totalOrders,
            'today_revenue'  => (float)$todayRevenue,
            'total_products' => (int)$totalProducts,
            'low_stock'      => (int)$lowStock,
        ];
    }

    // UC-03.1: รายงานยอดขายรายวัน (กำหนดจำนวนวันย้อนหลังได้)
    public function getDailySalesReport(int $days = 30): array {
        return $this->db->fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                COALESCE(SUM(total_amount), 0) as revenue
             FROM orders
             WHERE status != 'cancelled'
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$days]
        );
    }

    // UC-03.1: รายงานยอดขายรายเดือน
    public function getMonthlySalesReport(int $year = 0): array {
        $targetYear = $year > 0 ? $year : (int)date('Y');
        return $this->db->fetchAll(
            "SELECT 
                MONTH(created_at) as month,
                MONTHNAME(created_at) as month_name,
                COUNT(*) as order_count,
                COALESCE(SUM(total_amount), 0) as revenue
             FROM orders
             WHERE status != 'cancelled' AND YEAR(created_at) = ?
             GROUP BY MONTH(created_at), MONTHNAME(created_at)
             ORDER BY month ASC",
            [$targetYear]
        );
    }

    // UC-03.2: รายงานสินค้าขายดี Top N
    public function getTopSellingProducts(int $limit = 5): array {
        $safeLimit = max(1, min(50, $limit));
        return $this->db->fetchAll(
            "SELECT 
                p.id,
                p.name,
                p.color,
                p.size,
                p.image,
                COALESCE(SUM(oi.quantity), 0) AS total_sold,
                COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             JOIN orders o   ON oi.order_id   = o.id
             WHERE o.status != 'cancelled'
             GROUP BY p.id, p.name, p.color, p.size, p.image
             ORDER BY total_sold DESC
             LIMIT ?",
            [$safeLimit]
        );
    }

    // UC-03.3: รายงานสต็อกสินค้าใกล้หมด
    public function getLowStockReport(int $threshold = 5): array {
        return $this->db->fetchAll(
            "SELECT *, 
                CASE 
                    WHEN stock = 0 THEN 'หมดสต็อก'
                    WHEN stock <= 3 THEN 'วิกฤต'
                    ELSE 'ใกล้หมด'
                END as status_label
             FROM products
             WHERE stock <= ?
             ORDER BY stock ASC",
            [$threshold]
        );
    }

    // UC-03.5: เตรียมข้อมูลสำหรับแสดงผลบน Chart.js (7 วันล่าสุด)
    public function getChartData(): array {
        $rows = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COALESCE(SUM(total_amount), 0) as revenue
             FROM orders
             WHERE status != 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );

        $data   = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('d/m', strtotime($date));
            $found    = array_filter($rows, fn($r) => $r['date'] === $date);
            $data[]   = $found ? (float)array_values($found)[0]['revenue'] : 0.0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    // UC-03.6: ส่งออกรายงานยอดขายเป็น CSV (Export CSV with BOM for Thai support in Excel)
    public function exportSalesReportCSV(): string {
        $orders = $this->db->fetchAll(
            "SELECT o.id, o.customer_name, o.customer_phone, o.total_amount, o.payment_method, o.status, o.created_at,
                    COUNT(oi.id) as item_count
             FROM orders o
             LEFT JOIN order_items oi ON o.id = oi.order_id
             WHERE o.status != 'cancelled'
             GROUP BY o.id
             ORDER BY o.created_at DESC"
        );

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $output .= "เลขที่คำสั่งซื้อ,ชื่อลูกค้า,เบอร์โทรศัพท์,จำนวนรายการ,ยอดรวม (บาท),ช่องทางชำระ,สถานะ,วันที่สั่งซื้อ\n";

        foreach ($orders as $o) {
            $output .= sprintf(
                "\"#%s\",\"%s\",\"%s\",%d,%.2f,\"%s\",\"%s\",\"%s\"\n",
                $o['id'],
                str_replace('"', '""', $o['customer_name']),
                $o['customer_phone'] ?? '-',
                $o['item_count'],
                $o['total_amount'],
                $o['payment_method'],
                $o['status'],
                $o['created_at']
            );
        }

        return $output;
    }
}
?>
