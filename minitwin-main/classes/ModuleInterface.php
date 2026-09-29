<?php
/**
 * Interface ModuleInterface
 * กำหนดสัญญาการทำงานร่วมกันของโมดูลในระบบ (OOP: Abstraction & Interface)
 */
interface ModuleInterface {
    /**
     * คืนชื่อของโมดูล
     */
    public function getModuleName(): string;

    /**
     * คืนค่าข้อมูลสรุปภาพรวมของโมดูลนั้นๆ (OOP: Polymorphism)
     */
    public function getSummary(): array;
}
?>
