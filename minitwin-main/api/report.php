<?php
// api/report.php - API สำหรับ Report Class
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../classes/Report.php';

$report = new Report();
$action = $_GET['action'] ?? 'dashboard';

try {
    switch ($action) {
        case 'dashboard':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($report->getDashboardSummary());
            break;
        case 'daily':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($report->getDailySalesReport((int)($_GET['days'] ?? 30)));
            break;
        case 'monthly':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($report->getMonthlySalesReport((int)($_GET['year'] ?? 0)));
            break;
        case 'top_products':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($report->getTopSellingProducts((int)($_GET['limit'] ?? 5)));
            break;
        case 'low_stock':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($report->getLowStockReport((int)($_GET['threshold'] ?? 5)));
            break;
        case 'chart':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($report->getChartData());
            break;
        case 'export_csv':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="sales_report_' . date('Ymd_His') . '.csv"');
            echo $report->exportSalesReportCSV();
            exit;
        default:
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
