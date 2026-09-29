<?php
/**
 * Class Product (Model / Entity)
 * ตัวแทนของข้อมูลสินค้าเสื้อ 1 ชิ้น (OOP: Encapsulation)
 * ซ่อนรายละเอียดคุณลักษณะเป็น private และเปิดให้เข้าถึงผ่าน Getter / Setter
 */
class Product {
    private ?int $id;
    private string $name;
    private string $description;
    private string $category;
    private float $price;
    private int $stock;
    private string $size;
    private string $color;
    private string $image;

    public function __construct(
        string $name,
        float $price,
        int $stock,
        string $size = '',
        string $color = '',
        string $description = '',
        string $image = '',
        string $category = 'ทั่วไป',
        ?int $id = null
    ) {
        $this->name        = $name;
        $this->price       = $price;
        $this->stock       = $stock;
        $this->size        = $size;
        $this->color       = $color;
        $this->description = $description;
        $this->image       = $image;
        $this->category    = !empty($category) ? $category : 'ทั่วไป';
        $this->id          = $id;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getCategory(): string { return $this->category; }
    public function getPrice(): float { return $this->price; }
    public function getStock(): int { return $this->stock; }
    public function getSize(): string { return $this->size; }
    public function getColor(): string { return $this->color; }
    public function getImage(): string { return $this->image; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setCategory(string $category): void { $this->category = $category; }
    public function setPrice(float $price): void { $this->price = $price; }
    public function setStock(int $stock): void { $this->stock = max(0, $stock); }
    public function setImage(string $image): void { $this->image = $image; }

    /**
     * Business Logic Method: ตรวจสอบว่าสินค้าใกล้หมดสต็อกหรือไม่
     */
    public function isLowStock(int $threshold = 5): bool {
        return $this->stock <= $threshold;
    }

    /**
     * Business Logic Method: ตรวจสอบว่ามีสต็อกพอที่จะตัดขายหรือไม่
     */
    public function canDeduct(int $quantity): bool {
        return $this->stock >= $quantity;
    }

    /**
     * แปลงข้อมูล Entity เป็น Array
     */
    public function toArray(): array {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'category'    => $this->category,
            'price'       => $this->price,
            'stock'       => $this->stock,
            'size'        => $this->size,
            'color'       => $this->color,
            'image'       => $this->image,
            'is_low_stock'=> $this->isLowStock()
        ];
    }

    /**
     * Factory Method สร้าง Object Product จาก Array ข้อมูลฐานข้อมูล
     */
    public static function fromArray(array $data): Product {
        return new self(
            $data['name'] ?? '',
            (float)($data['price'] ?? 0),
            (int)($data['stock'] ?? 0),
            $data['size'] ?? '',
            $data['color'] ?? '',
            $data['description'] ?? '',
            $data['image'] ?? '',
            $data['category'] ?? 'ทั่วไป',
            isset($data['id']) ? (int)$data['id'] : null
        );
    }
}
?>
