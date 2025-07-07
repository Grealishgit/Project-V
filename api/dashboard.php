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
            
            // Format currency values
            $stats['avg_price'] = 'KSh ' . number_format($stats['avg_price'], 2);
            
            $this->sendResponse(['success' => true, 'stats' => $stats]);
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
