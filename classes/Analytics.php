<?php
require_once __DIR__ . '/../config/database.php';

class Analytics {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getDashboardAnalytics() {
        $analytics = [];
        
        // Product statistics
        $analytics['products'] = $this->getProductStats();
        
        // Category breakdown
        $analytics['categories'] = $this->getCategoryBreakdown();
        
        // Price analysis
        $analytics['price_analysis'] = $this->getPriceAnalysis();
        
        // Stock analysis
        $analytics['stock_analysis'] = $this->getStockAnalysis();
        
        // Recent activities
        $analytics['recent_activities'] = $this->getRecentActivities();
        
        return $analytics;
    }
    
    private function getProductStats() {
        $sql = "SELECT 
                    COUNT(*) as total_products,
                    AVG(price) as avg_price,
                    SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as in_stock_products,
                    SUM(CASE WHEN stock <= 10 THEN 1 ELSE 0 END) as low_stock_products,
                    SUM(price * stock) as total_inventory_value
                FROM products";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
    
    private function getCategoryBreakdown() {
        $sql = "SELECT 
                    category,
                    COUNT(*) as product_count,
                    AVG(price) as avg_price,
                    SUM(stock) as total_stock,
                    SUM(price * stock) as category_value
                FROM products 
                GROUP BY category 
                ORDER BY product_count DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    private function getPriceAnalysis() {
        $sql = "SELECT 
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    AVG(price) as avg_price,
                    COUNT(CASE WHEN price < 1000 THEN 1 END) as under_1k,
                    COUNT(CASE WHEN price BETWEEN 1000 AND 5000 THEN 1 END) as between_1k_5k,
                    COUNT(CASE WHEN price BETWEEN 5000 AND 10000 THEN 1 END) as between_5k_10k,
                    COUNT(CASE WHEN price > 10000 THEN 1 END) as over_10k
                FROM products";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
    
    private function getStockAnalysis() {
        $sql = "SELECT 
                    SUM(stock) as total_stock,
                    AVG(stock) as avg_stock,
                    COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
                    COUNT(CASE WHEN stock BETWEEN 1 AND 10 THEN 1 END) as low_stock,
                    COUNT(CASE WHEN stock BETWEEN 11 AND 50 THEN 1 END) as medium_stock,
                    COUNT(CASE WHEN stock > 50 THEN 1 END) as high_stock
                FROM products";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
    
    private function getRecentActivities() {
        // Get recently added products
        $sql = "SELECT 
                    'product_added' as activity_type,
                    name as item_name,
                    category,
                    price,
                    created_at as activity_date
                FROM products 
                ORDER BY created_at DESC 
                LIMIT 10";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getTopSellingCategories() {
        $sql = "SELECT 
                    category,
                    COUNT(*) as product_count,
                    SUM(price * stock) as total_value
                FROM products 
                GROUP BY category 
                ORDER BY total_value DESC";
        
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
    
    public function getInventoryValue() {
        $sql = "SELECT 
                    category,
                    SUM(price * stock) as category_value,
                    COUNT(*) as product_count
                FROM products 
                GROUP BY category
                UNION ALL
                SELECT 
                    'TOTAL' as category,
                    SUM(price * stock) as category_value,
                    COUNT(*) as product_count
                FROM products";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
?>
