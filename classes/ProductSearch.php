<?php
require_once __DIR__ . '/../config/database.php';

class ProductSearch {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function searchProducts($params = []) {
        $conditions = [];
        $queryParams = [];
        
        // Base query
        $sql = "SELECT * FROM products WHERE 1=1";
        
        // Search by name or description
        if (!empty($params['search'])) {
            $conditions[] = "(name LIKE :search OR description LIKE :search)";
            $queryParams[':search'] = "%{$params['search']}%";
        }
        
        // Filter by category
        if (!empty($params['category'])) {
            $conditions[] = "category = :category";
            $queryParams[':category'] = $params['category'];
        }
        
        // Price range filter
        if (!empty($params['min_price'])) {
            $conditions[] = "price >= :min_price";
            $queryParams[':min_price'] = $params['min_price'];
        }
        
        if (!empty($params['max_price'])) {
            $conditions[] = "price <= :max_price";
            $queryParams[':max_price'] = $params['max_price'];
        }
        
        // Stock filter
        if (!empty($params['in_stock'])) {
            $conditions[] = "stock > 0";
        }
        
        if (!empty($params['low_stock'])) {
            $threshold = $params['stock_threshold'] ?? 10;
            $conditions[] = "stock <= :threshold";
            $queryParams[':threshold'] = $threshold;
        }
        
        // Add conditions to query
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        // Sorting
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortOrder = $params['sort_order'] ?? 'DESC';
        $sql .= " ORDER BY {$sortBy} {$sortOrder}";
        
        // Pagination
        if (!empty($params['limit'])) {
            $limit = intval($params['limit']);
            $offset = intval($params['offset'] ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $stmt = $this->db->query($sql, $queryParams);
        return $stmt->fetchAll();
    }
    
    public function getSearchStats($params = []) {
        $conditions = [];
        $queryParams = [];
        
        $sql = "SELECT 
                    COUNT(*) as total_products,
                    AVG(price) as avg_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    SUM(stock) as total_stock
                FROM products WHERE 1=1";
        
        // Apply same filters as search
        if (!empty($params['search'])) {
            $conditions[] = "(name LIKE :search OR description LIKE :search)";
            $queryParams[':search'] = "%{$params['search']}%";
        }
        
        if (!empty($params['category'])) {
            $conditions[] = "category = :category";
            $queryParams[':category'] = $params['category'];
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->db->query($sql, $queryParams);
        return $stmt->fetch();
    }
}
?>
