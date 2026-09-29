<?php
/**
 * Class Database (Shared)
 * ใช้ร่วมกันทั้งระบบ (OOP: Singleton Pattern & Encapsulation)
 * รับผิดชอบ: เชื่อมต่อฐานข้อมูล MySQL, จัดการ Query และ Auto Migration
 */
class Database {
    private static $instance = null;
    private $conn;

    private $host     = 'localhost';
    private $dbname   = 'shirt_shop';
    private $username = 'root';
    private $password = '';
    private $charset  = 'utf8mb4';

    private function __construct() {
        try {
            // เชื่อมต่อ MySQL Server ก่อนเพื่อตรวจสอบ/สร้าง Database
            $initDsn = "mysql:host={$this->host};charset={$this->charset}";
            $initPdo = new PDO($initDsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $initPdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // เชื่อมต่อไปยัง Database shirt_shop
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // ตรวจสอบและสร้างตารางอัตโนมัติ (Auto Schema Initialization)
            $this->ensureSchema();
        } catch (PDOException $e) {
            throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูล MySQL: ' . $e->getMessage());
        }
    }

    // Singleton pattern - ใช้ instance เดียวกันทั้งระบบ
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->conn;
    }

    /**
     * สร้างตารางและข้อมูลตั้งต้นอัตโนมัติ (Plug & Play)
     */
    private function ensureSchema(): void {
        // ตาราง users
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                fullname VARCHAR(100) NOT NULL,
                role ENUM('admin', 'cashier') DEFAULT 'admin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // ตรวจสอบและเพิ่มผู้ใช้เริ่มต้น (admin / cashier)
        $userCount = (int)$this->fetchOne("SELECT COUNT(*) as c FROM users")['c'];
        if ($userCount === 0) {
            $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
            $cashierPass = password_hash('cashier123', PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', $adminPass, 'ผู้ดูแลระบบ (Admin)', 'admin']);
            $stmt->execute(['cashier', $cashierPass, 'พนักงานขายหน้าร้าน', 'cashier']);
        }

        // ตาราง products
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(100) DEFAULT 'ทั่วไป',
                price DECIMAL(10,2) NOT NULL,
                stock INT NOT NULL DEFAULT 0,
                size VARCHAR(50),
                color VARCHAR(50),
                image VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // ตรวจสอบคอลัมน์ category ใน products
        try {
            $this->conn->exec("ALTER TABLE products ADD COLUMN category VARCHAR(100) DEFAULT 'ทั่วไป'");
        } catch (Exception $e) {}

        // ตรวจสอบและเพิ่มสินค้าตัวอย่าง
        $productCount = (int)$this->fetchOne("SELECT COUNT(*) as c FROM products")['c'];
        if ($productCount === 0) {
            $this->conn->exec("
                INSERT INTO products (name, description, category, price, stock, size, color, image) VALUES
                ('เสื้อยืด Classic White', 'เสื้อยืดคอกลม ผ้า Cotton 100% สวมใส่สบาย ระบายอากาศดีเยี่ยม', 'เสื้อยืด', 299.00, 50, 'M,L,XL', 'ขาว', ''),
                ('เสื้อยืด Graphic Tee Black', 'เสื้อยืดลายกราฟิกสตรีทแฟชั่น ทรงสวย เนื้อผ้านุ่ม', 'เสื้อยืด', 399.00, 30, 'S,M,L', 'ดำ', ''),
                ('เสื้อโปโล Premium Navy', 'เสื้อโปโลผ้านุ่มพิเศษ มีปก ปักโลโก้เรียบหรู ใส่ทำงานได้', 'เสื้อโปโล', 599.00, 20, 'M,L,XL,XXL', 'กรมท่า', ''),
                ('เสื้อ Oversized Minimal Beige', 'เสื้อทรง Oversized สไตล์เกาหลี ยอดฮิต ทรงหลวมใส่สบาย', 'Oversize', 450.00, 15, 'M,L', 'เบจ', ''),
                ('เสื้อฮาวาย Summer Floral', 'เสื้อฮาวายผ้าเรยอนลายดอกไม้ สีสันสดใส ใส่เที่ยวทะเล', 'เสื้อฮาวาย', 520.00, 25, 'S,M,L,XL', 'ลายดอก', ''),
                ('เสื้อแขนยาว Striped Longsleeve', 'เสื้อแขนยาวลายทาง ผ้ายืดเนื้อนิ่ม', 'เสื้อแขนยาว', 480.00, 18, 'M,L,XL', 'ขาว-ดำ', ''),
                ('เสื้อกั๊ก Denim Vest', 'เสื้อกั๊กผ้ายีนส์ฟอก สไตล์วินเทจ', 'แจ็คเก็ต/กั๊ก', 650.00, 8, 'L,XL', 'ยีนส์', '')
            ");
        }

        // ตาราง orders
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_name VARCHAR(255) NOT NULL,
                customer_phone VARCHAR(20),
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
                payment_method ENUM('cash', 'promptpay', 'credit') DEFAULT 'cash',
                amount_received DECIMAL(10,2) NOT NULL DEFAULT 0,
                change_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
                status ENUM('pending','confirmed','cancelled') DEFAULT 'confirmed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // ตรวจสอบคอลัมน์ payment ใน orders
        try { $this->conn->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cash'"); } catch (Exception $e) {}
        try { $this->conn->exec("ALTER TABLE orders ADD COLUMN amount_received DECIMAL(10,2) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $this->conn->exec("ALTER TABLE orders ADD COLUMN change_amount DECIMAL(10,2) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $this->conn->exec("ALTER TABLE orders ADD COLUMN shipping_address TEXT"); } catch (Exception $e) {}

        // ตาราง order_items
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )
        ");
    }

    // ดึงข้อมูลหลายแถว
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ดึงข้อมูลแถวเดียว
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // Insert/Update/Delete
    public function execute(string $sql, array $params = []): int {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->rowCount();
    }

    // Insert แล้วคืน ID ล่าสุด
    public function insert(string $sql, array $params = []): int {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int) $this->conn->lastInsertId();
    }

    public function beginTransaction(): void {
        $this->conn->beginTransaction();
    }

    public function commit(): void {
        $this->conn->commit();
    }

    public function rollback(): void {
        $this->conn->rollBack();
    }
}
?>
