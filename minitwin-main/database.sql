-- ระบบขายเสื้อ - Database Schema (Updated with Users, Categories, Payments)
CREATE DATABASE IF NOT EXISTS shirt_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shirt_shop;

-- ตารางผู้ใช้งาน / พนักงาน (สำหรับระบบ Login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    role ENUM('admin', 'cashier') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางสินค้า
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
);

-- ตารางคำสั่งซื้อ (เพิ่มระบบชำระเงิน, เงินที่รับ, เงินทอน)
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
);

-- ตารางรายการในคำสั่งซื้อ
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- เพิ่มผู้ใช้งานเริ่มต้น (รหัสผ่านเริ่มต้น: admin123 และ cashier123)
INSERT INTO users (username, password, fullname, role) VALUES
('admin', '$2y$10$w3U6Usm6nsqU3Yd2y1W0se2l.r0gWqj/c9H60E91a38/yWbYV70uO', 'ผู้ดูแลระบบ (Admin)', 'admin'),
('cashier', '$2y$10$fA1h1q765wWc.cM1tTep8uYqM24nZ7d0M6n2K6n3j2m7u41k6z8s.', 'พนักงานขายหน้าร้าน', 'cashier')
ON DUPLICATE KEY UPDATE username=username;

-- ข้อมูลตัวอย่างสินค้า
INSERT INTO products (name, description, category, price, stock, size, color, image) VALUES
('เสื้อยืด Classic White', 'เสื้อยืดคอกลม ผ้า Cotton 100% สวมใส่สบาย ระบายอากาศดีเยี่ยม', 'เสื้อยืด', 299.00, 50, 'M,L,XL', 'ขาว', ''),
('เสื้อยืด Graphic Tee Black', 'เสื้อยืดลายกราฟิกสตรีทแฟชั่น ทรงสวย เนื้อผ้านุ่ม', 'เสื้อยืด', 399.00, 30, 'S,M,L', 'ดำ', ''),
('เสื้อโปโล Premium Navy', 'เสื้อโปโลผ้านุ่มพิเศษ มีปก ปักโลโก้เรียบหรู ใส่ทำงานได้', 'เสื้อโปโล', 599.00, 20, 'M,L,XL,XXL', 'กรมท่า', ''),
('เสื้อ Oversized Minimal Beige', 'เสื้อทรง Oversized สไตล์เกาหลี ยอดฮิต ทรงหลวมใส่สบาย', 'Oversize', 450.00, 15, 'M,L', 'เบจ', ''),
('เสื้อฮาวาย Summer Floral', 'เสื้อฮาวายผ้าเรยอนลายดอกไม้ สีสันสดใส ใส่เที่ยวทะเล', 'เสื้อฮาวาย', 520.00, 25, 'S,M,L,XL', 'ลายดอก', ''),
('เสื้อแขนยาว Striped Longsleeve', 'เสื้อแขนยาวลายทาง ผ้ายืดเนื้อนิ่ม สำหรับใส่กันแดดหรือห้องแอร์', 'เสื้อแขนยาว', 480.00, 18, 'M,L,XL', 'ขาว-ดำ', ''),
('เสื้อกั๊ก Denim Vest', 'เสื้อกั๊กผ้ายีนส์ฟอก เพิ่มลูกเล่นการแต่งตัวสไตล์วินเทจ', 'แจ็คเก็ต/กั๊ก', 650.00, 8, 'L,XL', 'ยีนส์', '');
