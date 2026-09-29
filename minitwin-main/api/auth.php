<?php
// api/auth.php - API สำหรับระบบ Authentication
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once __DIR__ . '/../classes/Auth.php';

$auth   = new Auth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'me') {
                $user = $auth->getCurrentUser();
                echo json_encode([
                    'logged_in' => $auth->isLoggedIn(),
                    'user'      => $user
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Unknown action']);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            if ($action === 'login') {
                $username = $input['username'] ?? '';
                $password = $input['password'] ?? '';
                $result   = $auth->login($username, $password);
                http_response_code($result['success'] ? 200 : 401);
                echo json_encode($result);
            } elseif ($action === 'logout') {
                $auth->logout();
                echo json_encode(['success' => true, 'message' => 'ออกจากระบบแล้ว']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Unknown action']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
