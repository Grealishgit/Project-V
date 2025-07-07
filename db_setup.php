<?php
// Simple database setup script
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'product_dashboard';

try {
    // First, connect to MySQL server without database
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
    echo "Database '$database' created successfully.\n";
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        description TEXT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Products table created successfully.\n";
    
    // Check if we have any products, if not insert sample data
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "Inserting sample data...\n";
        
        $sampleProducts = [
            ['iPhone 14 Pro', 'Electronics', 999.99, 50, 'Latest iPhone with advanced camera system'],
            ['Samsung Galaxy S23', 'Electronics', 849.99, 30, 'Flagship Android smartphone'],
            ['MacBook Pro 16"', 'Electronics', 2499.99, 15, 'Professional laptop for creative professionals'],
            ['Nike Air Max 90', 'Clothing', 129.99, 100, 'Classic running shoes with Air Max technology'],
            ['Adidas Ultraboost 22', 'Clothing', 189.99, 75, 'High-performance running shoes'],
            ['The Great Gatsby', 'Books', 12.99, 200, 'Classic American novel by F. Scott Fitzgerald'],
            ['To Kill a Mockingbird', 'Books', 14.99, 150, 'Pulitzer Prize-winning novel'],
            ['Coffee Table', 'Home', 299.99, 25, 'Modern wooden coffee table'],
            ['Dining Chair Set', 'Home', 459.99, 20, 'Set of 4 comfortable dining chairs'],
            ['Tennis Racket', 'Sports', 89.99, 40, 'Professional tennis racket for intermediate players'],
            ['Basketball', 'Sports', 29.99, 60, 'Official size basketball for indoor/outdoor play'],
            ['Yoga Mat', 'Sports', 39.99, 80, 'Non-slip yoga mat for home workouts']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, description) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($sampleProducts as $product) {
            $stmt->execute($product);
        }
        
        echo "Sample products inserted successfully.\n";
    } else {
        echo "Products table already contains data ($count products).\n";
    }
    
    echo "\nDatabase setup completed successfully!\n";
    echo "You can now run your application with: php -S localhost:8000\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
