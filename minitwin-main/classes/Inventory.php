<?php
require_once __DIR__ . '/ShopModule.php';
require_once __DIR__ . '/Product.php';

/**
 * Class Inventory (Class 1) - รับผิดชอบโดยคนที่ 1
 * สืบทอดคุณสมบัติมาจาก ShopModule (OOP: Inheritance)
 * หน้าที่: จัดการสต็อก หมวดหมู่ และสินค้าเสื้อ (Encapsulation of Inventory Logic)
 * Use Cases: UC-01.1 ถึง UC-01.7
 */
class Inventory extends ShopModule {

    public function __construct() {
        parent::__construct('Inventory Module (จัดการสต็อกสินค้า)');
    }

    /**
     * OOP: Polymorphism - การ Implement เมธอด getSummary() ตามที่แม่ (ShopModule) กำหนด
     */
    public function getSummary(): array {
        $totalItems = $this->db->fetchOne("SELECT COUNT(*) as total FROM products")['total'] ?? 0;
        $totalStock = $this->db->fetchOne("SELECT COALESCE(SUM(stock), 0) as total FROM products")['total'] ?? 0;
        $lowStock   = count($this->getLowStockProducts(5));

        return [
            'module'      => $this->getModuleName(),
            'total_items' => (int)$totalItems,
            'total_stock' => (int)$totalStock,
            'low_stock'   => $lowStock
        ];
    }

    // UC-01.4: ดูรายการสินค้าทั้งหมด (กรองตามหมวดหมู่ได้)
    public function getAllProducts(string $category = ''): array {
        if (!empty($category)) {
            $cat = $this->cleanInput($category);
            return $this->db->fetchAll(
                "SELECT * FROM products WHERE category = ? ORDER BY created_at DESC",
                [$cat]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM products ORDER BY created_at DESC"
        );
    }

    // UC-01.5: ค้นหาสินค้า (ชื่อ, สี, ไซส์, หมวดหมู่)
    public function searchProducts(string $keyword): array {
        $clean = $this->cleanInput($keyword);
        return $this->db->fetchAll(
            "SELECT * FROM products WHERE name LIKE ? OR color LIKE ? OR size LIKE ? OR category LIKE ? ORDER BY created_at DESC",
            ["%$clean%", "%$clean%", "%$clean%", "%$clean%"]
        );
    }

    // ดึงหมวดหมู่สินค้าทั้งหมดที่มี
    public function getCategories(): array {
        $rows = $this->db->fetchAll("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
        return array_column($rows, 'category');
    }

    // ดูสินค้าตาม ID
    public function getProductById(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM products WHERE id = ?",
            [$id]
        );
    }

    // UC-01.1: เพิ่มสินค้า โดยใช้ Product Model (OOP: Object Creation & Validation)
    public function addProduct(array $data): array {
        $errors = $this->validateProductData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $imagePath = $this->handleImageUpload($_FILES['image'] ?? null);

        // สร้าง Object จาก Product Class
        $product = new Product(
            $this->cleanInput($data['name'] ?? ''),
            (float)($data['price'] ?? 0),
            (int)($data['stock'] ?? 0),
            $this->cleanInput($data['size'] ?? ''),
            $this->cleanInput($data['color'] ?? ''),
            $this->cleanInput($data['description'] ?? ''),
            $imagePath,
            $this->cleanInput($data['category'] ?? 'ทั่วไป')
        );

        $id = $this->db->insert(
            "INSERT INTO products (name, description, category, price, stock, size, color, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $product->getName(),
                $product->getDescription(),
                $product->getCategory(),
                $product->getPrice(),
                $product->getStock(),
                $product->getSize(),
                $product->getColor(),
                $product->getImage()
            ]
        );

        return ['success' => true, 'id' => $id, 'message' => 'เพิ่มสินค้าสำเร็จ'];
    }

    // UC-01.2: แก้ไขสินค้า
    public function updateProduct(int $id, array $data): array {
        $errors = $this->validateProductData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $existing = $this->getProductById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'ไม่พบสินค้า'];
        }

        $imagePath = $existing['image'];
        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->handleImageUpload($_FILES['image']);
        }

        $this->db->execute(
            "UPDATE products SET name=?, description=?, category=?, price=?, stock=?, size=?, color=?, image=? WHERE id=?",
            [
                $this->cleanInput($data['name']),
                $this->cleanInput($data['description'] ?? ''),
                $this->cleanInput($data['category'] ?? 'ทั่วไป'),
                (float)$data['price'],
                (int)$data['stock'],
                $this->cleanInput($data['size'] ?? ''),
                $this->cleanInput($data['color'] ?? ''),
                $imagePath,
                $id
            ]
        );

        return ['success' => true, 'message' => 'แก้ไขสินค้าสำเร็จ'];
    }

    // UC-01.3: ลบสินค้า
    public function deleteProduct(int $id): array {
        $product = $this->getProductById($id);
        if (!$product) {
            return ['success' => false, 'message' => 'ไม่พบสินค้า'];
        }

        if ($product['image'] && file_exists(__DIR__ . '/../uploads/' . $product['image'])) {
            unlink(__DIR__ . '/../uploads/' . $product['image']);
        }

        $this->db->execute("DELETE FROM products WHERE id = ?", [$id]);
        return ['success' => true, 'message' => 'ลบสินค้าสำเร็จ'];
    }

    // UC-01.6: อัปเดตสต็อก (เพิ่ม/ลด/กำหนดค่า)
    public function updateStock(int $id, int $quantity, string $action = 'set'): array {
        $productData = $this->getProductById($id);
        if (!$productData) {
            return ['success' => false, 'message' => 'ไม่พบสินค้า'];
        }

        $product = Product::fromArray($productData);

        if ($action === 'add') {
            $this->db->execute("UPDATE products SET stock = stock + ? WHERE id = ?", [$quantity, $id]);
        } elseif ($action === 'reduce') {
            if (!$product->canDeduct($quantity)) {
                return ['success' => false, 'message' => 'สต็อกไม่เพียงพอ'];
            }
            $this->db->execute("UPDATE products SET stock = stock - ? WHERE id = ?", [$quantity, $id]);
        } else {
            $this->db->execute("UPDATE products SET stock = ? WHERE id = ?", [$quantity, $id]);
        }

        return ['success' => true, 'message' => 'อัปเดตสต็อกสำเร็จ'];
    }

    // UC-01.7: เติมสต็อกด่วน (Quick Restock)
    public function restock(int $id, int $amount): array {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'จำนวนที่เติมต้องมากกว่า 0'];
        }
        return $this->updateStock($id, $amount, 'add');
    }

    // ดึงสินค้าสต็อกใกล้หมด
    public function getLowStockProducts(int $threshold = 5): array {
        return $this->db->fetchAll(
            "SELECT * FROM products WHERE stock <= ? ORDER BY stock ASC",
            [$threshold]
        );
    }

    // Encapsulation: ซ่อน Method ตรวจสอบความถูกต้องไว้ภายในคลาส
    private function validateProductData(array $data): array {
        $errors = [];
        if (empty(trim($data['name'] ?? ''))) {
            $errors[] = 'กรุณากรอกชื่อสินค้า';
        }
        if (!is_numeric($data['price'] ?? '') || (float)$data['price'] <= 0) {
            $errors[] = 'ราคาต้องมากกว่า 0';
        }
        if (!is_numeric($data['stock'] ?? '') || (int)$data['stock'] < 0) {
            $errors[] = 'จำนวนสต็อกต้องไม่ติดลบ';
        }
        return $errors;
    }

    // Encapsulation: ซ่อน Method อัปโหลดไฟล์ไว้ภายในคลาส
    private function handleImageUpload(?array $file): string {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return '';

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed)) return '';

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('shirt_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

        return $filename;
    }
}
?>
