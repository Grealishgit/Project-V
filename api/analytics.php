<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/Analytics.php';

class AnalyticsAPI {
    private $analytics;
    
    public function __construct() {
        $this->analytics = new Analytics();
    }
    
    public function handleRequest() {
        try {
            if (isset($_GET['type'])) {
                $this->handleSpecificAnalytics($_GET['type']);
            } else {
                $this->handleDashboardAnalytics();
            }
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function handleDashboardAnalytics() {
        $analytics = $this->analytics->getDashboardAnalytics();
        
        // Format currency values
        $this->formatCurrency($analytics);
        
        $this->sendResponse(['success' => true, 'analytics' => $analytics]);
    }
    
    private function handleSpecificAnalytics($type) {
        switch ($type) {
            case 'categories':
                $data = $this->analytics->getTopSellingCategories();
                break;
            case 'low-stock':
                $threshold = $_GET['threshold'] ?? 10;
                $data = $this->analytics->getLowStockProducts($threshold);
                break;
            case 'inventory-value':
                $data = $this->analytics->getInventoryValue();
                break;
            default:
                $this->sendResponse(['success' => false, 'message' => 'Invalid analytics type'], 400);
                return;
        }
        
        // Format currency in the data
        $this->formatDataCurrency($data);
        
        $this->sendResponse(['success' => true, 'data' => $data]);
    }
    
    private function formatCurrency(&$analytics) {
        // Format product stats
        if (isset($analytics['products'])) {
            $analytics['products']['avg_price'] = 'KSh ' . number_format($analytics['products']['avg_price'], 2);
            $analytics['products']['total_inventory_value'] = 'KSh ' . number_format($analytics['products']['total_inventory_value'], 2);
        }
        
        // Format category breakdown
        if (isset($analytics['categories'])) {
            foreach ($analytics['categories'] as &$category) {
                $category['avg_price'] = 'KSh ' . number_format($category['avg_price'], 2);
                $category['category_value'] = 'KSh ' . number_format($category['category_value'], 2);
            }
        }
        
        // Format price analysis
        if (isset($analytics['price_analysis'])) {
            $priceAnalysis = &$analytics['price_analysis'];
            $priceAnalysis['min_price'] = 'KSh ' . number_format($priceAnalysis['min_price'], 2);
            $priceAnalysis['max_price'] = 'KSh ' . number_format($priceAnalysis['max_price'], 2);
            $priceAnalysis['avg_price'] = 'KSh ' . number_format($priceAnalysis['avg_price'], 2);
        }
        
        // Format recent activities
        if (isset($analytics['recent_activities'])) {
            foreach ($analytics['recent_activities'] as &$activity) {
                if (isset($activity['price'])) {
                    $activity['formatted_price'] = 'KSh ' . number_format($activity['price'], 2);
                }
            }
        }
    }
    
    private function formatDataCurrency(&$data) {
        foreach ($data as &$item) {
            if (isset($item['price'])) {
                $item['formatted_price'] = 'KSh ' . number_format($item['price'], 2);
            }
            if (isset($item['avg_price'])) {
                $item['formatted_avg_price'] = 'KSh ' . number_format($item['avg_price'], 2);
            }
            if (isset($item['category_value'])) {
                $item['formatted_category_value'] = 'KSh ' . number_format($item['category_value'], 2);
            }
            if (isset($item['total_value'])) {
                $item['formatted_total_value'] = 'KSh ' . number_format($item['total_value'], 2);
            }
            if (isset($item['remaining_value'])) {
                $item['formatted_remaining_value'] = 'KSh ' . number_format($item['remaining_value'], 2);
            }
        }
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Initialize and handle request
$api = new AnalyticsAPI();
$api->handleRequest();
?>
