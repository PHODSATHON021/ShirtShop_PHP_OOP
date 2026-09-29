<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ModuleInterface.php';

/**
 * Abstract Class ShopModule
 * คลาสแม่ (Base Class) สำหรับแต่ละโมดูลในระบบร้านขายเสื้อ (OOP: Inheritance & Abstraction)
 * มีการนำ Class Database (Shared Class) มาใช้งานร่วมกัน
 */
abstract class ShopModule implements ModuleInterface {
    // Encapsulation: กำหนด visibility เป็น protected เพื่อให้คลาสลูกเข้าถึงได้
    protected Database $db;
    protected string $moduleName;

    /**
     * Constructor รับชื่อโมดูลและดึง Database Instance (Shared Class)
     */
    public function __construct(string $moduleName) {
        $this->db = Database::getInstance();
        $this->moduleName = $moduleName;
    }

    /**
     * ดึงชื่อโมดูล
     */
    public function getModuleName(): string {
        return $this->moduleName;
    }

    /**
     * Abstract Method บังคับให้คลาสลูกแต่ละคลาสต้องเขียน Logic สรุปข้อมูลของตัวเอง (OOP: Polymorphism)
     */
    abstract public function getSummary(): array;

    /**
     * Helper Method สำหรับตรวจสอบข้อความว่าง (Encapsulation / Code Reuse)
     */
    protected function cleanInput(string $data): string {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
?>
