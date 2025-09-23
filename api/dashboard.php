<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/Product.php';

class DashboardAPI {
    private $product;
    
    public function __construct() {
        $this->product = new Product();
    }
    
    public function handleRequest() {
        try {
            $stats = $this->product->getStats();

            // Format the response to match JavaScript expectations
            $response = [
                'success' => true,
                'totalProducts' => $stats['total_products'],
                'totalValue' => 'KSh ' . number_format($stats['total_inventory_value'], 2),
                'lowStock' => $stats['low_stock_products'],
                'categories' => $stats['total_categories'],
                'avgPrice' => 'KSh ' . number_format($stats['avg_price'], 2),
                'totalStock' => $stats['in_stock_products'], // Using in_stock as total active stock

                // Additional data for analytics
                'inStock' => $stats['in_stock_products'],
                'outOfStock' => $stats['total_products'] - $stats['in_stock_products'],
                'totalInventoryValue' => $stats['total_inventory_value'],
                'averagePrice' => $stats['avg_price']
            ];

            $this->sendResponse($response);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Initialize and handle request
$api = new DashboardAPI();
$api->handleRequest();
?>
