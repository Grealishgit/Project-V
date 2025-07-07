<?php
// Test uploads directory and add product functionality
require_once 'classes/Product.php';

// Test uploads directory
$uploadsDir = 'uploads/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

if (!is_writable($uploadsDir)) {
    echo "Uploads directory is not writable. Please check permissions.\n";
} else {
    echo "Uploads directory is writable.\n";
}

// Test product creation
try {
    $product = new Product();
    
    $testData = [
        'name' => 'Test Product',
        'category' => 'Electronics',
        'price' => 99.99,
        'stock' => 10,
        'description' => 'This is a test product',
        'image' => null
    ];
    
    echo "Testing product creation...\n";
    $productId = $product->create($testData);
    
    if ($productId) {
        echo "Product created successfully with ID: $productId\n";
        
        // Clean up - delete the test product
        $product->delete($productId);
        echo "Test product deleted.\n";
    } else {
        echo "Failed to create product.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Add product functionality test completed.\n";
?>
