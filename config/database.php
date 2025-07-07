<?php
class Database {
    private $host = 'localhost';
    private $dbname = 'product_dashboard';
    private $username = 'root';
    private $password = 'Hunter42.';
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare SQL statement: " . implode(', ', $this->conn->errorInfo()));
            }
            
            $result = $stmt->execute($params);
            if (!$result) {
                throw new Exception("Failed to execute SQL statement: " . implode(', ', $stmt->errorInfo()));
            }
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
}
?>
