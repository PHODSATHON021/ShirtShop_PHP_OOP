<?php
// api/sale.php - API สำหรับ Sale Class
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Sale.php';

$sale   = new Sale();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'products') {
                echo json_encode($sale->getAvailableProducts($_GET['category'] ?? ''));
            } elseif ($action === 'detail' && isset($_GET['id'])) {
                $detail = $sale->getOrderDetail((int)$_GET['id']);
                echo json_encode($detail ?? ['error' => 'ไม่พบคำสั่งซื้อ']);
            } elseif ($action === 'summary') {
                echo json_encode($sale->getSalesSummary($_GET['start'] ?? '', $_GET['end'] ?? ''));
            } elseif ($action === 'top') {
                echo json_encode($sale->getTopSellingProducts((int)($_GET['limit'] ?? 5)));
            } else {
                echo json_encode($sale->getAllOrders($_GET['status'] ?? ''));
            }
            break;

        case 'POST':
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            if ($action === 'order') {
                $result = $sale->createOrder(
                    $body['customer_name']    ?? '',
                    $body['customer_phone']   ?? '',
                    $body['items']            ?? [],
                    $body['payment_method']   ?? 'cash',
                    (float)($body['amount_received'] ?? 0),
                    $body['shipping_address'] ?? ''
                );
                http_response_code($result['success'] ? 201 : 400);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Unknown action']);
            }
            break;

        case 'PUT':
            $id = (int)($_GET['id'] ?? 0);
            if ($action === 'confirm') {
                $result = $sale->confirmOrder($id);
            } elseif ($action === 'cancel') {
                $result = $sale->cancelOrder($id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Unknown action']);
                exit;
            }
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
