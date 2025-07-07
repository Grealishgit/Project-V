<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="products.csv"');
header('Access-Control-Allow-Origin: *');

require_once '../classes/Product.php';

class ExportAPI {
    private $product;
    
    public function __construct() {
        $this->product = new Product();
    }
    
    public function exportCSV() {
        try {
            $products = $this->product->getAll();
            
            // Open output stream
            $output = fopen('php://output', 'w');
            
            // Write CSV header
            fputcsv($output, ['ID', 'Name', 'Category', 'Price', 'Stock', 'Description', 'Image', 'Created At', 'Updated At']);
            
            // Write data rows
            foreach ($products as $product) {
                fputcsv($output, [
                    $product['id'],
                    $product['name'],
                    $product['category'],
                    $product['price'],
                    $product['stock'],
                    $product['description'],
                    $product['image'],
                    $product['created_at'],
                    $product['updated_at']
                ]);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// Initialize and export
$api = new ExportAPI();
$api->exportCSV();
?>
