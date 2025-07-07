<?php
// Test script for search functionality
require_once 'classes/ProductSearch.php';

try {
    $search = new ProductSearch();
    
    // Test basic search
    $params = [
        'search' => 'lap',
        'sort_by' => 'created_at',
        'sort_order' => 'DESC',
        'limit' => 20,
        'offset' => 0
    ];
    
    echo "Testing search with params: " . json_encode($params) . "\n";
    
    $products = $search->searchProducts($params);
    echo "Products found: " . count($products) . "\n";
    
    if (!empty($products)) {
        echo "First product: " . $products[0]['name'] . "\n";
    }
    
    $stats = $search->getSearchStats($params);
    echo "Stats: " . json_encode($stats) . "\n";
    
    echo "Search test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
