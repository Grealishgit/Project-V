<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    // Check if admin_login_attempts table exists
    echo "Checking admin_login_attempts table...\n";
    $result = $db->query("SHOW TABLES LIKE 'admin_login_attempts'");
    if ($result->fetch()) {
        echo "✓ admin_login_attempts table exists\n";
        
        // Show structure
        $result = $db->query("DESCRIBE admin_login_attempts");
        echo "\nTable structure:\n";
        while ($row = $result->fetch()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "✗ admin_login_attempts table does not exist\n";
        
        // Create it
        echo "Creating admin_login_attempts table...\n";
        $sql = "CREATE TABLE admin_login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            success BOOLEAN DEFAULT FALSE,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_ip_address (ip_address),
            INDEX idx_attempted_at (attempted_at)
        )";
        
        $db->query($sql);
        echo "✓ admin_login_attempts table created\n";
    }
    
    // Check admin_sessions table
    echo "\nChecking admin_sessions table...\n";
    $result = $db->query("SHOW TABLES LIKE 'admin_sessions'");
    if ($result->fetch()) {
        echo "✓ admin_sessions table exists\n";
    } else {
        echo "✗ admin_sessions table does not exist\n";
        
        // Create it
        echo "Creating admin_sessions table...\n";
        $sql = "CREATE TABLE admin_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
            INDEX idx_session_token (session_token),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        )";
        
        $db->query($sql);
        echo "✓ admin_sessions table created\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
