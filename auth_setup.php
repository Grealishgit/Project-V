<?php
require_once 'config/database.php';

class AuthSetup {
    private $db;
    
    public function __construct() {
        try {
            // Connect to MySQL without specifying database
            $this->db = new PDO(
                "mysql:host=localhost;charset=utf8",
                'root',
                'Hunter42.',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function setup() {
        try {
            echo "Setting up authentication tables...\n";
            
            // Use the existing database
            $this->db->exec("USE product_dashboard");
            
            // Read and execute the authentication schema
            $sql = file_get_contents('database/auth_schema.sql');
            
            // Split the SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->db->exec($statement);
                }
            }
            
            echo "Authentication setup completed successfully!\n";
            
            // Test the admin user creation
            $testDb = new Database();
            $result = $testDb->query("SELECT COUNT(*) as count FROM admin_users");
            $count = $result->fetch()['count'];
            
            echo "Admin users created: {$count}\n";
            echo "Default admin credentials: admin@dashboard.com / admin123\n";
            
        } catch (Exception $e) {
            echo "Error setting up authentication: " . $e->getMessage() . "\n";
        }
    }
}

// Run setup if called directly
if (php_sapi_name() === 'cli') {
    $setup = new AuthSetup();
    $setup->setup();
} else {
    echo "Please run this script from the command line: php auth_setup.php\n";
}
?>
