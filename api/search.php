<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../classes/ProductSearch.php';

class SearchAPI {
    private $search;
    
    public function __construct() {
        $this->search = new ProductSearch();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet();
                    break;
                default:
                    $this->sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function handleGet() {
        $params = [];
        
        // Get search parameters
        if (isset($_GET['search'])) {
            $params['search'] = $_GET['search'];
        }
        
        if (isset($_GET['category'])) {
            $params['category'] = $_GET['category'];
        }
        
        if (isset($_GET['min_price'])) {
            $params['min_price'] = floatval($_GET['min_price']);
        }
        
        if (isset($_GET['max_price'])) {
            $params['max_price'] = floatval($_GET['max_price']);
        }
        
        if (isset($_GET['in_stock'])) {
            $params['in_stock'] = $_GET['in_stock'] === 'true';
        }
        
        if (isset($_GET['low_stock'])) {
            $params['low_stock'] = $_GET['low_stock'] === 'true';
        }
        
        if (isset($_GET['sort_by'])) {
            $params['sort_by'] = $_GET['sort_by'];
        }
        
        if (isset($_GET['sort_order'])) {
            $params['sort_order'] = $_GET['sort_order'];
        }
        
        if (isset($_GET['limit'])) {
            $params['limit'] = intval($_GET['limit']);
        }
        
        if (isset($_GET['offset'])) {
            $params['offset'] = intval($_GET['offset']);
        }
        
        // Get search results
        $products = $this->search->searchProducts($params);
        $stats = $this->search->getSearchStats($params);
        
        // Format prices with KSh
        foreach ($products as &$product) {
            $product['formatted_price'] = 'KSh ' . number_format($product['price'], 2);
            $product['inventory_value'] = 'KSh ' . number_format($product['price'] * $product['stock'], 2);
        }
        
        $this->sendResponse([
            'success' => true,
            'products' => $products,
            'stats' => [
                'total_products' => $stats['total_products'],
                'avg_price' => 'KSh ' . number_format($stats['avg_price'], 2),
                'min_price' => 'KSh ' . number_format($stats['min_price'], 2),
                'max_price' => 'KSh ' . number_format($stats['max_price'], 2),
                'total_stock' => $stats['total_stock']
            ]
        ]);
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Initialize and handle request
$api = new SearchAPI();
$api->handleRequest();
?>
