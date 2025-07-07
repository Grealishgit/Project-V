<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Test if products table exists and check structure
    $stmt = $conn->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Products table columns:\n";
    foreach ($columns as $column) {
        echo "- $column\n";
    }
    
    // Test a simple query
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "\nTotal products in database: " . $result['count'] . "\n";
    
    // Test a search query
    $sql = "SELECT * FROM products WHERE 1=1 ORDER BY id DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo "\nSample products:\n";
    foreach ($products as $product) {
        echo "- ID: {$product['id']}, Name: {$product['name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
