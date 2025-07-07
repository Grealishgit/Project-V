<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../classes/AdminUser.php';

class AuthAPI {
    private $adminUser;
    
    public function __construct() {
        $this->adminUser = new AdminUser();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'POST':
                    $this->handlePost($action);
                    break;
                case 'GET':
                    $this->handleGet($action);
                    break;
                case 'PUT':
                    $this->handlePut($action);
                    break;
                case 'DELETE':
                    $this->handleDelete($action);
                    break;
                default:
                    $this->sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            }
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    
    private function handlePost($action) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'register':
                $this->register($input);
                break;
            case 'login':
                $this->login($input);
                break;
            case 'logout':
                $this->logout($input);
                break;
            default:
                $this->sendResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'validate':
                $this->validateSession();
                break;
            case 'profile':
                $this->getProfile();
                break;
            case 'users':
                $this->getUsers();
                break;
            default:
                $this->sendResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }
    
    private function handlePut($action) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'profile':
                $this->updateProfile($input);
                break;
            case 'password':
                $this->changePassword($input);
                break;
            default:
                $this->sendResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }
    
    private function register($input) {
        $required = ['username', 'email', 'password'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $this->sendResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
                return;
            }
        }
        
        // Validate email
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
            return;
        }
        
        // Validate password strength
        if (strlen($input['password']) < 6) {
            $this->sendResponse(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
            return;
        }
        
        $userId = $this->adminUser->register(
            $input['username'],
            $input['email'],
            $input['password'],
            $input['full_name'] ?? null
        );
        
        $this->sendResponse([
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $userId
        ]);
    }
    
    private function login($input) {
        $required = ['username', 'password'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $this->sendResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
                return;
            }
        }
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $result = $this->adminUser->login($input['username'], $input['password'], $ipAddress, $userAgent);
        
        // Store session token in session
        $_SESSION['admin_session_token'] = $result['session_token'];
        $_SESSION['admin_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['full_name'] = $result['user']['full_name'];
        $_SESSION['email'] = $result['user']['email'];
        
        $this->sendResponse([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $result['user']['id'],
                'username' => $result['user']['username'],
                'email' => $result['user']['email'],
                'full_name' => $result['user']['full_name']
            ],
            'session_token' => $result['session_token']
        ]);
    }
    
    private function logout($input) {
        $sessionToken = $_SESSION['admin_session_token'] ?? $input['session_token'] ?? null;
        
        if ($sessionToken) {
            $this->adminUser->logout($sessionToken);
        }
        
        // Clear session
        session_destroy();
        
        $this->sendResponse(['success' => true, 'message' => 'Logout successful']);
    }
    
    private function validateSession() {
        $sessionToken = $_SESSION['admin_session_token'] ?? null;
        
        if (!$sessionToken) {
            $this->sendResponse(['success' => false, 'message' => 'No active session'], 401);
            return;
        }
        
        $session = $this->adminUser->validateSession($sessionToken);
        
        if (!$session) {
            session_destroy();
            $this->sendResponse(['success' => false, 'message' => 'Session expired'], 401);
            return;
        }
        
        $this->sendResponse([
            'success' => true,
            'user' => [
                'id' => $session['id'],
                'username' => $session['username'],
                'email' => $session['email'],
                'full_name' => $session['full_name']
            ]
        ]);
    }
    
    private function getProfile() {
        $sessionToken = $_SESSION['admin_session_token'] ?? null;
        
        if (!$sessionToken) {
            $this->sendResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        $session = $this->adminUser->validateSession($sessionToken);
        
        if (!$session) {
            $this->sendResponse(['success' => false, 'message' => 'Session expired'], 401);
            return;
        }
        
        $user = $this->adminUser->getUserById($session['id']);
        
        $this->sendResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'last_login' => $user['last_login'],
                'created_at' => $user['created_at']
            ]
        ]);
    }
    
    private function updateProfile($input) {
        $sessionToken = $_SESSION['admin_session_token'] ?? null;
        
        if (!$sessionToken) {
            $this->sendResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        $session = $this->adminUser->validateSession($sessionToken);
        
        if (!$session) {
            $this->sendResponse(['success' => false, 'message' => 'Session expired'], 401);
            return;
        }
        
        $required = ['full_name', 'email'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $this->sendResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
                return;
            }
        }
        
        // Validate email
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
            return;
        }
        
        $this->adminUser->updateProfile($session['id'], $input['full_name'], $input['email']);
        
        $this->sendResponse(['success' => true, 'message' => 'Profile updated successfully']);
    }
    
    private function changePassword($input) {
        $sessionToken = $_SESSION['admin_session_token'] ?? null;
        
        if (!$sessionToken) {
            $this->sendResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        $session = $this->adminUser->validateSession($sessionToken);
        
        if (!$session) {
            $this->sendResponse(['success' => false, 'message' => 'Session expired'], 401);
            return;
        }
        
        $required = ['current_password', 'new_password'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $this->sendResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
                return;
            }
        }
        
        // Validate new password strength
        if (strlen($input['new_password']) < 6) {
            $this->sendResponse(['success' => false, 'message' => 'New password must be at least 6 characters long'], 400);
            return;
        }
        
        $this->adminUser->changePassword($session['id'], $input['current_password'], $input['new_password']);
        
        $this->sendResponse(['success' => true, 'message' => 'Password changed successfully']);
    }
    
    private function getUsers() {
        $sessionToken = $_SESSION['admin_session_token'] ?? null;
        
        if (!$sessionToken) {
            $this->sendResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        $session = $this->adminUser->validateSession($sessionToken);
        
        if (!$session) {
            $this->sendResponse(['success' => false, 'message' => 'Session expired'], 401);
            return;
        }
        
        $users = $this->adminUser->getAllUsers();
        
        $this->sendResponse(['success' => true, 'users' => $users]);
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Initialize and handle request
$api = new AuthAPI();
$api->handleRequest();
?>
