<?php
require_once 'config/database.php';

// Fix the admin_users table by adding missing columns
try {
    $db = new Database();
    
    echo "Fixing admin_users table structure...\n";
    
    // Add missing columns
    $alterQueries = [
        "ALTER TABLE admin_users ADD COLUMN full_name VARCHAR(100) AFTER password",
        "ALTER TABLE admin_users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER full_name",
        "ALTER TABLE admin_users ADD COLUMN last_login TIMESTAMP NULL AFTER is_active"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $db->query($query);
            echo "Executed: " . $query . "\n";
        } catch (Exception $e) {
            // Column might already exist, that's okay
            echo "Skipped (already exists): " . $query . "\n";
        }
    }
    
    echo "\nUpdated table structure:\n";
    echo "------------------------\n";
    
    $result = $db->query("DESCRIBE admin_users");
    while ($row = $result->fetch()) {
        echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . "\n";
    }
    
    echo "\nTable structure fixed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
