<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check if user is authenticated and is admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Admin access required.'
    ]);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Backup directory
$backupDir = '../backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

try {
    switch ($action) {
        case 'create':
            // Create database backup
            $input = json_decode(file_get_contents('php://input'), true);
            
            $includeProducts = $input['products'] ?? true;
            $includeOrders = $input['orders'] ?? true;
            $includeCustomers = $input['customers'] ?? true;
            $includeAnalytics = $input['analytics'] ?? true;
            
            // Generate filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $filepath = $backupDir . '/' . $filename;
            
            // Get database credentials from config
            $config = require_once '../config/database.php';
            $dbName = 'product_dashboard'; // Replace with your actual database name
            
            // Start building SQL
            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: {$dbName}\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            // Backup products table
            if ($includeProducts) {
                $sql .= "-- Products Table\n";
                $sql .= "DROP TABLE IF EXISTS `products_backup`;\n";
                $sql .= getTableStructure($db, 'products');
                $sql .= getTableData($db, 'products');
                $sql .= "\n";
            }
            
            // Backup orders tables
            if ($includeOrders) {
                $sql .= "-- Orders Tables\n";
                $tables = ['orders', 'order_items', 'payment_history', 'order_status_history'];
                foreach ($tables as $table) {
                    $sql .= getTableStructure($db, $table);
                    $sql .= getTableData($db, $table);
                    $sql .= "\n";
                }
            }
            
            // Backup customers (admin_users with role=customer)
            if ($includeCustomers) {
                $sql .= "-- Customers (Admin Users)\n";
                $sql .= getTableStructure($db, 'admin_users');
                $sql .= getCustomerData($db);
                $sql .= "\n";
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Save to file
            file_put_contents($filepath, $sql);
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename,
                'file_url' => 'api/backup.php?action=download&file=' . urlencode($filename),
                'size' => formatBytes(filesize($filepath))
            ]);
            break;
            
        case 'history':
            // Get backup history
            $backups = [];
            
            if (is_dir($backupDir)) {
                $files = scandir($backupDir);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                        $filepath = $backupDir . '/' . $file;
                        $backups[] = [
                            'filename' => $file,
                            'date' => date('M d, Y H:i:s', filemtime($filepath)),
                            'size' => formatBytes(filesize($filepath)),
                            'timestamp' => filemtime($filepath)
                        ];
                    }
                }
            }
            
            // Sort by timestamp (newest first)
            usort($backups, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            echo json_encode([
                'success' => true,
                'backups' => $backups
            ]);
            break;
            
        case 'download':
            // Download backup file
            $filename = $_GET['file'] ?? '';
            $filepath = $backupDir . '/' . basename($filename);
            
            if (!file_exists($filepath)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Backup file not found'
                ]);
                exit();
            }
            
            // Set headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit();
            
        case 'delete':
            // Delete backup file
            $filename = $_POST['filename'] ?? '';
            $filepath = $backupDir . '/' . basename($filename);
            
            if (!file_exists($filepath)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Backup file not found'
                ]);
                exit();
            }
            
            if (unlink($filepath)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Backup deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to delete backup file'
                ]);
            }
            break;
            
        case 'restore':
            // Restore database from backup
            if (!isset($_FILES['backup_file'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No backup file uploaded'
                ]);
                exit();
            }
            
            $file = $_FILES['backup_file'];
            
            // Validate file
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode([
                    'success' => false,
                    'error' => 'File upload error'
                ]);
                exit();
            }
            
            if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid file type. Only .sql files are allowed'
                ]);
                exit();
            }
            
            // Read SQL file
            $sql = file_get_contents($file['tmp_name']);
            
            // Execute SQL
            try {
                $db->exec($sql);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Database restored successfully'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to restore database: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Backup API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}

// Helper function to get table structure
function getTableStructure($db, $tableName) {
    $stmt = $db->query("SHOW CREATE TABLE `{$tableName}`");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return "DROP TABLE IF EXISTS `{$tableName}`;\n" . $row['Create Table'] . ";\n\n";
}

// Helper function to get table data
function getTableData($db, $tableName) {
    $sql = "-- Data for table `{$tableName}`\n";
    
    $stmt = $db->query("SELECT * FROM `{$tableName}`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) === 0) {
        return $sql . "-- No data\n\n";
    }
    
    foreach ($rows as $row) {
        $columns = array_keys($row);
        $values = array_map(function($value) use ($db) {
            if ($value === null) {
                return 'NULL';
            }
            return $db->quote($value);
        }, array_values($row));
        
        $sql .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
    }
    
    return $sql . "\n";
}

// Helper function to get customer data only
function getCustomerData($db) {
    $sql = "-- Customer data\n";
    
    $stmt = $db->query("SELECT * FROM `admin_users` WHERE role = 'customer'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) === 0) {
        return $sql . "-- No customer data\n\n";
    }
    
    foreach ($rows as $row) {
        $columns = array_keys($row);
        $values = array_map(function($value) use ($db) {
            if ($value === null) {
                return 'NULL';
            }
            return $db->quote($value);
        }, array_values($row));
        
        $sql .= "INSERT INTO `admin_users` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
    }
    
    return $sql . "\n";
}

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
