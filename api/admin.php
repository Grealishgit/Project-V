<?php
session_start(); // Start session to check logged-in user

// Debug mode - add this for debugging
if (isset($_GET['debug'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'session_data' => $_SESSION,
        'admin_id_set' => isset($_SESSION['admin_id']),
        'user_id_set' => isset($_SESSION['user_id']),
        'session_id' => session_id()
    ]);
    exit();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Initialize database
$database = new Database();
$db = $database->getConnection();

try {
    // Check if user is logged in via session
    if (!isset($_SESSION['admin_id'])) {
        // No session found, redirect to login
        echo json_encode([
            'success' => false,
            'error' => 'Not authenticated. Please login.',
            'redirect' => 'login.php'
        ]);
        exit();
    }
    
    // Get the current logged-in admin ID from session
    $admin_id = $_SESSION['admin_id'];
    
    // Fetch the specific admin user based on session using actual database columns
    $sql = "SELECT id, username, full_name, email, is_active, last_login, created_at, updated_at FROM admin_users WHERE id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$admin_id]);
    
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Check if admin is active
        if (!$admin['is_active']) {
            echo json_encode([
                'success' => false,
                'error' => 'Account is inactive',
                'redirect' => 'login.php'
            ]);
            exit();
        }
        
        // Format the dates
        $admin['created_at'] = date('M j, Y g:i A', strtotime($admin['created_at']));
        $admin['updated_at'] = date('M j, Y g:i A', strtotime($admin['updated_at']));
        $admin['last_login'] = $admin['last_login'] ? date('M j, Y g:i A', strtotime($admin['last_login'])) : 'Never';
        
        // Add some additional computed fields
        $admin['user_id'] = '#' . str_pad($admin['id'], 4, '0', STR_PAD_LEFT);
        $admin['status'] = $admin['is_active'] ? 'Active' : 'Inactive';
        $admin['role'] = 'Administrator'; // Set a default role since it's not in the database
        
        echo json_encode([
            'success' => true,
            'admin' => $admin
        ]);
    } else {
        // Admin not found in database - session might be corrupted
        session_destroy(); // Clear invalid session
        echo json_encode([
            'success' => false,
            'error' => 'Admin user not found. Please login again.',
            'redirect' => 'login.php'
        ]);
    } 
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>