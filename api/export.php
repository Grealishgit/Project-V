<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/Product.php';

class ExportAPI {
    private $product;
    
    public function __construct() {
        $this->product = new Product();
    }
    
    public function exportCSV() {
        try {
            // Validate product instance
            if (!$this->product) {
                throw new Exception('Product class not properly initialized');
            }

            // Get filters from URL parameters
            $filters = [];

            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }

            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $filters['category'] = $_GET['category'];
            }

            if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
                $filters['min_price'] = (float)$_GET['min_price'];
            }

            if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
                $filters['max_price'] = (float)$_GET['max_price'];
            }

            if (isset($_GET['in_stock_only']) && $_GET['in_stock_only'] == '1') {
                $filters['in_stock'] = true;
            }

            if (isset($_GET['low_stock_only']) && $_GET['low_stock_only'] == '1') {
                $filters['low_stock'] = true;
            }

            if (isset($_GET['sort_by']) && !empty($_GET['sort_by'])) {
                $filters['sort_by'] = $_GET['sort_by'];
            }

            if (isset($_GET['sort_order']) && !empty($_GET['sort_order'])) {
                $filters['sort_order'] = $_GET['sort_order'];
            }

            // Get products based on filters or all products
            if (!empty($filters)) {
                $products = $this->product->searchProducts($filters);
                $filename = 'filtered_products_export_' . date('Y-m-d') . '.csv';
            } else {
                $products = $this->product->getAll();
                $filename = 'products_export_' . date('Y-m-d') . '.csv';
            }

            // Set CSV headers for Excel compatibility
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            
            // Open output stream
            $output = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fwrite($output, "\xEF\xBB\xBF");

            // Write CSV header with proper escaping
            fputcsv($output, ['ID', 'Name', 'Category', 'Price (KSh)', 'Stock', 'Description', 'Image', 'Created At', 'Updated At'], ',', '"', '\\');

            // Write data rows with proper formatting and escaping
            foreach ($products as $product) {
                // Format data for Excel compatibility
                $row = [
                    (string)$product['id'],
                    (string)$product['name'],
                    (string)$product['category'],
                    (string)number_format((float)$product['price'], 2, '.', ''),
                    (string)$product['stock'],
                    (string)($product['description'] ?? ''),
                    (string)($product['image'] ?? ''),
                    (string)$product['created_at'],
                    (string)$product['updated_at']
                ];

                fputcsv($output, $row, ',', '"', '\\');
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            // Reset headers for error response
            if (!headers_sent()) {
                header_remove(); // Remove all previously set headers
                header('Content-Type: application/json');
                header('HTTP/1.1 500 Internal Server Error');
            }

            echo json_encode([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
}

// Initialize and export
$api = new ExportAPI();
$api->exportCSV();
?>