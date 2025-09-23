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
        if (!empty($params['search']) && trim($params['search']) !== '') {
            $conditions[] = "(name LIKE :search1 OR description LIKE :search2)";
            $queryParams[':search1'] = "%{$params['search']}%";
            $queryParams[':search2'] = "%{$params['search']}%";
        }
        
        // Filter by category
        if (!empty($params['category']) && trim($params['category']) !== '') {
            $conditions[] = "category = :category";
            $queryParams[':category'] = $params['category'];
        }
        
        // Price range filter
        if (!empty($params['min_price']) && is_numeric($params['min_price'])) {
            $conditions[] = "price >= :min_price";
            $queryParams[':min_price'] = floatval($params['min_price']);
        }
        
        if (!empty($params['max_price']) && is_numeric($params['max_price'])) {
            $conditions[] = "price <= :max_price";
            $queryParams[':max_price'] = floatval($params['max_price']);
        }
        
        // Stock filter
        if (isset($params['in_stock']) && $params['in_stock'] === true) {
            $conditions[] = "stock > 0";
        }
        
        if (isset($params['low_stock']) && $params['low_stock'] === true) {
            $threshold = isset($params['stock_threshold']) ? intval($params['stock_threshold']) : 10;
            $conditions[] = "stock <= :threshold";
            $queryParams[':threshold'] = $threshold;
        }
        
        // Add conditions to query
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        // Sorting - validate sort field to prevent SQL injection
        $allowedSortFields = ['id', 'name', 'category', 'price', 'stock', 'created_at'];
        $sortBy = in_array($params['sort_by'] ?? '', $allowedSortFields) ? $params['sort_by'] : 'id';
        $sortOrder = (($params['sort_order'] ?? '') === 'ASC') ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$sortBy} {$sortOrder}";
        
        // Pagination
        if (!empty($params['limit']) && is_numeric($params['limit'])) {
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
                    COALESCE(AVG(price), 0) as avg_price,
                    COALESCE(MIN(price), 0) as min_price,
                    COALESCE(MAX(price), 0) as max_price,
                    COALESCE(SUM(stock), 0) as total_stock,
                    COALESCE(SUM(price * stock), 0) as total_inventory_value,
                    COUNT(DISTINCT category) as categories_count,
                    COUNT(CASE WHEN stock <= 10 THEN 1 END) as low_stock_products
                FROM products WHERE 1=1";
        
        // Apply same filters as search
        if (!empty($params['search']) && trim($params['search']) !== '') {
            $conditions[] = "(name LIKE :search1 OR description LIKE :search2)";
            $queryParams[':search1'] = "%{$params['search']}%";
            $queryParams[':search2'] = "%{$params['search']}%";
        }
        
        if (!empty($params['category']) && trim($params['category']) !== '') {
            $conditions[] = "category = :category";
            $queryParams[':category'] = $params['category'];
        }
        
        // Price range filter
        if (!empty($params['min_price']) && is_numeric($params['min_price'])) {
            $conditions[] = "price >= :min_price";
            $queryParams[':min_price'] = floatval($params['min_price']);
        }
        
        if (!empty($params['max_price']) && is_numeric($params['max_price'])) {
            $conditions[] = "price <= :max_price";
            $queryParams[':max_price'] = floatval($params['max_price']);
        }
        
        // Stock filter
        if (isset($params['in_stock']) && $params['in_stock'] === true) {
            $conditions[] = "stock > 0";
        }
        
        if (isset($params['low_stock']) && $params['low_stock'] === true) {
            $threshold = isset($params['stock_threshold']) ? intval($params['stock_threshold']) : 10;
            $conditions[] = "stock <= :threshold";
            $queryParams[':threshold'] = $threshold;
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->db->query($sql, $queryParams);
        return $stmt->fetch();
    }
}
?>