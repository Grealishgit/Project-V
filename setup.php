<?php
require_once 'config/database.php';

class DatabaseSetup {
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
            echo "Setting up database...\n";
            
            // Read and execute the SQL schema
            $sql = file_get_contents('database/schema.sql');
            
            // Split the SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->db->exec($statement);
                }
            }
            
            echo "Database setup completed successfully!\n";
            
            // Test connection to the new database
            $testDb = new Database();
            $result = $testDb->query("SELECT COUNT(*) as count FROM products");
            $count = $result->fetch()['count'];
            
            echo "Sample data loaded: {$count} products\n";
            
        } catch (Exception $e) {
            echo "Error setting up database: " . $e->getMessage() . "\n";
        }
    }
}

// Run setup if called directly
if (php_sapi_name() === 'cli') {
    $setup = new DatabaseSetup();
    $setup->setup();
} else {
    echo "Please run this script from the command line: php setup.php\n";
}
?>
