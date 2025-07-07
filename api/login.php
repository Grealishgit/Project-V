<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Username and password are required'
                ]);
                exit();
            }
            
            // Check in admin_users table
            $sql = "SELECT id, username, password, full_name, email, is_active FROM admin_users WHERE username = ? OR email = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_active'] == 1) {
                    // CREATE SESSION HERE
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Update last login
                    $updateSql = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
                    $updateStmt = $db->prepare($updateSql);
                    $updateStmt->execute([$user['id']]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'full_name' => $user['full_name'],
                            'email' => $user['email']
                        ]
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Account is inactive'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid username or password'
                ]);
            }
            break;
            
        case 'register':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $full_name = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            
            if (empty($username) || empty($password) || empty($email)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Username, password, and email are required'
                ]);
                exit();
            }
            
            // Check if user already exists
            $checkSql = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([$username, $email]);
            
            if ($checkStmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Username or email already exists'
                ]);
                exit();
            }
            
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertSql = "INSERT INTO admin_users (username, password, full_name, email, is_active) VALUES (?, ?, ?, ?, 1)";
            $insertStmt = $db->prepare($insertSql);
            
            if ($insertStmt->execute([$username, $hashedPassword, $full_name, $email])) {
                $newUserId = $db->lastInsertId();
                
                // CREATE SESSION FOR NEW USER
                $_SESSION['admin_id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful',
                    'user' => [
                        'id' => $newUserId,
                        'username' => $username,
                        'full_name' => $full_name,
                        'email' => $email
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Registration failed'
                ]);
            }
            break;
            
        case 'logout':
            // Clear session
            session_destroy();
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
            break;
            
        case 'validate':
            // Check if user session exists
            if (isset($_SESSION['admin_id'])) {
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id' => $_SESSION['admin_id'],
                        'username' => $_SESSION['username'],
                        'full_name' => $_SESSION['full_name'],
                        'email' => $_SESSION['email']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'No active session'
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
    error_log("Auth API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
