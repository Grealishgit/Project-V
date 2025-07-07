<?php
// Test script for search functionality
require_once 'classes/ProductSearch.php';

try {
    $search = new ProductSearch();
    
    // Test basic search
    $params = [
        'search' => 'test',
        'limit' => 10,
        'offset' => 0
    ];
    
    echo "Testing search with params: " . json_encode($params) . "\n";
    
    $products = $search->searchProducts($params);
    echo "Products found: " . count($products) . "\n";
    
    $stats = $search->getSearchStats($params);
    echo "Stats: " . json_encode($stats) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
