<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../classes/Product.php';

class ProductAPI {
    private $product;
    
    public function __construct() {
        $this->product = new Product();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet();
                    break;
                case 'POST':
                    $this->handlePost();
                    break;
                case 'PUT':
                    $this->handlePut();
                    break;
                case 'DELETE':
                    $this->handleDelete();
                    break;
                default:
                    $this->sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function handleGet() {
        if (isset($_GET['id'])) {
            // Get single product
            $product = $this->product->getById($_GET['id']);
            if ($product) {
                $this->sendResponse(['success' => true, 'product' => $product]);
            } else {
                $this->sendResponse(['success' => false, 'message' => 'Product not found'], 404);
            }
        } elseif (isset($_GET['search'])) {
            // Search products
            $products = $this->product->searchProducts($_GET['search']);
            $this->sendResponse(['success' => true, 'products' => $products]);
        } elseif (isset($_GET['category'])) {
            // Get products by category
            $products = $this->product->getProductsByCategory($_GET['category']);
            $this->sendResponse(['success' => true, 'products' => $products]);
        } else {
            // Get all products
            $products = $this->product->getAll();
            
            // Format currency for display
            foreach ($products as &$product) {
                $product['formatted_price'] = 'KSh ' . number_format($product['price'], 2);
                $product['inventory_value'] = 'KSh ' . number_format($product['price'] * $product['stock'], 2);
            }
            
            $this->sendResponse(['success' => true, 'products' => $products]);
        }
    }
    
    private function handlePost() {
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $this->handleUpdate();
            return;
        }
        
        // Validate required fields
        $required = ['name', 'category', 'price', 'stock'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $this->sendResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
                return;
            }
        }
        
        // Handle file upload
        $imageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageName = $this->handleFileUpload($_FILES['image']);
            if (!$imageName) {
                $this->sendResponse(['success' => false, 'message' => 'Failed to upload image'], 400);
                return;
            }
        }
        
        // Prepare data
        $data = [
            'name' => trim($_POST['name']),
            'category' => trim($_POST['category']),
            'price' => floatval($_POST['price']),
            'stock' => intval($_POST['stock']),
            'description' => trim($_POST['description'] ?? ''),
            'image' => $imageName
        ];
        
        // Create product
        $productId = $this->product->create($data);
        
        if ($productId) {
            $this->sendResponse(['success' => true, 'message' => 'Product created successfully', 'id' => $productId]);
        } else {
            $this->sendResponse(['success' => false, 'message' => 'Failed to create product'], 500);
        }
    }
    
    private function handleUpdate() {
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $this->sendResponse(['success' => false, 'message' => 'Product ID is required'], 400);
            return;
        }
        
        $id = intval($_POST['id']);
        
        // Validate required fields
        $required = ['name', 'category', 'price', 'stock'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $this->sendResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
                return;
            }
        }
        
        // Prepare data
        $data = [
            'name' => trim($_POST['name']),
            'category' => trim($_POST['category']),
            'price' => floatval($_POST['price']),
            'stock' => intval($_POST['stock']),
            'description' => trim($_POST['description'] ?? '')
        ];
        
        // Update product
        $success = $this->product->update($id, $data);
        
        if ($success) {
            $this->sendResponse(['success' => true, 'message' => 'Product updated successfully']);
        } else {
            $this->sendResponse(['success' => false, 'message' => 'Failed to update product'], 500);
        }
    }
    
    private function handleDelete() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || empty($input['id'])) {
            $this->sendResponse(['success' => false, 'message' => 'Product ID is required'], 400);
            return;
        }
        
        $id = intval($input['id']);
        $success = $this->product->delete($id);
        
        if ($success) {
            $this->sendResponse(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            $this->sendResponse(['success' => false, 'message' => 'Failed to delete product'], 500);
        }
    }
    
    private function handleFileUpload($file) {
        $uploadDir = '../uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filename;
        }
        
        return false;
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Initialize and handle request
$api = new ProductAPI();
$api->handleRequest();
?>
