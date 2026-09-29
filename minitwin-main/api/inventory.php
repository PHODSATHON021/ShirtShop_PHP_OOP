<?php
// api/inventory.php - API สำหรับ Inventory Class
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Inventory.php';

$inventory = new Inventory();
$method    = $_SERVER['REQUEST_METHOD'];
$action    = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'search') {
                echo json_encode($inventory->searchProducts($_GET['q'] ?? ''));
            } elseif ($action === 'categories') {
                echo json_encode($inventory->getCategories());
            } elseif ($action === 'low_stock') {
                echo json_encode($inventory->getLowStockProducts((int)($_GET['threshold'] ?? 5)));
            } elseif (isset($_GET['id'])) {
                $product = $inventory->getProductById((int)$_GET['id']);
                echo json_encode($product ?? ['error' => 'ไม่พบสินค้า']);
            } else {
                echo json_encode($inventory->getAllProducts($_GET['category'] ?? ''));
            }
            break;

        case 'POST':
            if ($action === 'restock') {
                $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
                $amount = (int)($body['amount'] ?? 0);
                $result = $inventory->restock($id, $amount);
                http_response_code($result['success'] ? 200 : 400);
                echo json_encode($result);
            } else {
                $data   = $_POST;
                $result = $inventory->addProduct($data);
                http_response_code($result['success'] ? 201 : 400);
                echo json_encode($result);
            }
            break;

        case 'PUT':
            parse_str(file_get_contents('php://input'), $data);
            $id = (int)($_GET['id'] ?? 0);
            if ($action === 'stock') {
                $result = $inventory->updateStock($id, (int)($data['quantity'] ?? 0), $data['action_type'] ?? 'set');
            } elseif ($action === 'restock') {
                $result = $inventory->restock($id, (int)($data['amount'] ?? 0));
            } else {
                $result = $inventory->updateProduct($id, $data);
            }
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;

        case 'DELETE':
            $id     = (int)($_GET['id'] ?? 0);
            $result = $inventory->deleteProduct($id);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
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
