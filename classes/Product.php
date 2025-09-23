<?php
require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($data) {
        $sql = "INSERT INTO products (name, category, price, stock, description, image, created_at) 
                VALUES (:name, :category, :price, :stock, :description, :image, NOW())";
        
        $params = [
            ':name' => $data['name'],
            ':category' => $data['category'],
            ':price' => $data['price'],
            ':stock' => $data['stock'],
            ':description' => $data['description'] ?? null,
            ':image' => $data['image'] ?? null
        ];
        
        $stmt = $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function getAll() {
        $sql = "SELECT * FROM products ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $this->db->query($sql, [':id' => $id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        // Base SQL parts
        $setParts = [
            'name = :name',
            'category = :category',
            'price = :price',
            'stock = :stock',
            'description = :description',
            'updated_at = NOW()'
        ];
        
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':category' => $data['category'],
            ':price' => $data['price'],
            ':stock' => $data['stock'],
            ':description' => $data['description'] ?? null
        ];

        // Add image to update if provided
        if (isset($data['image']) && $data['image'] !== null) {
            $setParts[] = 'image = :image';
            $params[':image'] = $data['image'];
        }

        $sql = "UPDATE products SET " . implode(', ', $setParts) . " WHERE id = :id";

        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount() > 0;
    }
    
    public function delete($id) {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->db->query($sql, [':id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_products,
                    CASE 
                        WHEN COUNT(*) > 0 THEN AVG(price)
                        ELSE 0
                    END as avg_price,
                    COUNT(DISTINCT category) as total_categories,
                    SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as in_stock_products,
                    SUM(CASE WHEN stock <= 10 THEN 1 ELSE 0 END) as low_stock_products,
                    COALESCE(SUM(price * stock), 0) as total_inventory_value
                FROM products";
        
        $stmt = $this->db->query($sql);
        $stats = $stmt->fetch();
        
        // Ensure all values are numeric and handle nulls
        $stats['total_products'] = (int)($stats['total_products'] ?? 0);
        $stats['avg_price'] = (float)($stats['avg_price'] ?? 0);
        $stats['total_categories'] = (int)($stats['total_categories'] ?? 0);
        $stats['in_stock_products'] = (int)($stats['in_stock_products'] ?? 0);
        $stats['low_stock_products'] = (int)($stats['low_stock_products'] ?? 0);
        $stats['total_inventory_value'] = (float)($stats['total_inventory_value'] ?? 0);
        
        return $stats;
    }
    
    public function getCategoryBreakdown() {
        $sql = "SELECT 
                    category,
                    COUNT(*) as product_count,
                    COALESCE(AVG(price), 0) as avg_price,
                    COALESCE(SUM(stock), 0) as total_stock,
                    COALESCE(SUM(price * stock), 0) as category_value
                FROM products 
                GROUP BY category 
                ORDER BY product_count DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getLowStockProducts($threshold = 10) {
        $sql = "SELECT 
                    id, name, category, price, stock,
                    (price * stock) as remaining_value
                FROM products 
                WHERE stock <= :threshold 
                ORDER BY stock ASC";
        
        $stmt = $this->db->query($sql, [':threshold' => $threshold]);
        return $stmt->fetchAll();
    }
    
    public function searchProducts($filters) {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR category LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['in_stock'])) {
            $sql .= " AND stock > 0";
        }
        
        if (!empty($filters['low_stock'])) {
            $sql .= " AND stock <= 10";
        }
        
        // Add sorting
        $sort_by = $filters['sort_by'] ?? 'created_at';
        $sort_order = $filters['sort_order'] ?? 'DESC';
        $sql .= " ORDER BY $sort_by $sort_order";
        
        // Add limit and offset
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }
        
        if (!empty($filters['offset'])) {
            $sql .= " OFFSET :offset";
            $params[':offset'] = (int)$filters['offset'];
        }
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function getSearchStats($filters) {
        $sql = "SELECT 
                    COUNT(*) as total_products,
                    COALESCE(AVG(price), 0) as avg_price,
                    COALESCE(MIN(price), 0) as min_price,
                    COALESCE(MAX(price), 0) as max_price,
                    COALESCE(SUM(stock), 0) as total_stock
                FROM products WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR category LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['in_stock'])) {
            $sql .= " AND stock > 0";
        }
        
        if (!empty($filters['low_stock'])) {
            $sql .= " AND stock <= 10";
        }
        
        $stmt = $this->db->query($sql, $params);
        $stats = $stmt->fetch();
        
        // Format the stats with currency
        $stats['avg_price'] = 'KSh ' . number_format($stats['avg_price'], 2);
        $stats['min_price'] = 'KSh ' . number_format($stats['min_price'], 2);
        $stats['max_price'] = 'KSh ' . number_format($stats['max_price'], 2);
        
        return $stats;
    }

    public function getProductsByCategory($category)
    {
        $sql = "SELECT * FROM products WHERE category = :category ORDER BY created_at DESC";
        $stmt = $this->db->query($sql, [':category' => $category]);
        return $stmt->fetchAll();
    }
}
?>