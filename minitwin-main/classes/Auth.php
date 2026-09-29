<?php
require_once __DIR__ . '/Database.php';

/**
 * Class Auth (ระบบยืนยันตัวตนและการเข้าสู่ระบบ)
 * รับผิดชอบ: ตรวจสอบสิทธิ์, Session, Login และ Logout (OOP: Encapsulation)
 */
class Auth {
    private Database $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
    }

    /**
     * ดำเนินการเข้าสู่ระบบ
     */
    public function login(string $username, string $password): array {
        $username = trim($username);
        $password = trim($password);

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'];
        }

        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE username = ?",
                [$username]
            );

            if (!$user) {
                return ['success' => false, 'message' => 'ไม่พบชื่อผู้ใช้นี้ในระบบ'];
            }

            // ตรวจสอบรหัสผ่าน (รองรับ password_verify และ plaintext fallback สำหรับค่าเริ่มต้น)
            $passwordValid = false;
            if (password_verify($password, $user['password'])) {
                $passwordValid = true;
            } elseif ($password === $user['password']) {
                // อัปเดตรหัสผ่านเป็น hash เพื่อความปลอดภัย
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $this->db->execute("UPDATE users SET password = ? WHERE id = ?", [$newHash, $user['id']]);
                $passwordValid = true;
            }

            if (!$passwordValid) {
                return ['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง'];
            }

            // บันทึก Session
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['fullname']  = $user['fullname'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['logged_in'] = true;

            return [
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user'    => [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'fullname' => $user['fullname'],
                    'role'     => $user['role']
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }

    /**
     * ออกจากระบบ
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * ตรวจสอบว่าล็อกอินอยู่หรือไม่
     */
    public function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * ดึงข้อมูลผู้ใช้ปัจจุบัน
     */
    public function getCurrentUser(): ?array {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return [
            'id'       => $_SESSION['user_id'] ?? 0,
            'username' => $_SESSION['username'] ?? '',
            'fullname' => $_SESSION['fullname'] ?? '',
            'role'     => $_SESSION['role'] ?? 'staff'
        ];
    }

    /**
     * บังคับให้ต้องล็อกอิน หากไม่ได้ล็อกอินจะ redirect ไป login.php
     */
    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}
?>
