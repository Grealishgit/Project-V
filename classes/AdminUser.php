<?php
require_once __DIR__ . '/../config/database.php';

class AdminUser {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function register($username, $email, $password, $fullName = null) {
        // Check if username or email already exists
        if ($this->userExists($username, $email)) {
            throw new Exception('Username or email already exists');
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO admin_users (username, email, password, full_name, created_at) 
                VALUES (:username, :email, :password, :full_name, NOW())";
        
        $params = [
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':full_name' => $fullName
        ];
        
        $stmt = $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function login($usernameOrEmail, $password, $ipAddress = null, $userAgent = null) {
        // Log login attempt
        $this->logLoginAttempt($usernameOrEmail, $ipAddress, $userAgent, false);
        
        // Check if account is locked (more than 5 failed attempts in last 15 minutes)
        if ($this->isAccountLocked($usernameOrEmail)) {
            throw new Exception('Account temporarily locked due to too many failed login attempts');
        }
        
        // Find user by username or email
        $sql = "SELECT id, username, email, password, full_name, is_active 
                FROM admin_users 
                WHERE (username = :username OR email = :email) AND is_active = 1";
        
        $stmt = $this->db->query($sql, [':username' => $usernameOrEmail, ':email' => $usernameOrEmail]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Invalid username/email or password');
        }
        
        // Log successful login attempt
        $this->logLoginAttempt($usernameOrEmail, $ipAddress, $userAgent, true);
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Create session
        $sessionToken = $this->createSession($user['id'], $ipAddress, $userAgent);
        
        return [
            'user' => $user,
            'session_token' => $sessionToken
        ];
    }
    
    public function logout($sessionToken) {
        $sql = "DELETE FROM admin_sessions WHERE session_token = :token";
        $this->db->query($sql, [':token' => $sessionToken]);
    }
    
    public function validateSession($sessionToken) {
        $sql = "SELECT u.id, u.username, u.email, u.full_name, u.is_active, s.expires_at
                FROM admin_sessions s
                JOIN admin_users u ON s.user_id = u.id
                WHERE s.session_token = :token AND s.expires_at > NOW() AND u.is_active = 1";
        
        $stmt = $this->db->query($sql, [':token' => $sessionToken]);
        $session = $stmt->fetch();
        
        if (!$session) {
            // Clean up expired session
            $this->cleanupExpiredSessions();
            return false;
        }
        
        // Extend session expiry
        $this->extendSession($sessionToken);
        
        return $session;
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current password
        $sql = "SELECT password FROM admin_users WHERE id = :id";
        $stmt = $this->db->query($sql, [':id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE admin_users SET password = :password, updated_at = NOW() WHERE id = :id";
        $this->db->query($sql, [':password' => $hashedPassword, ':id' => $userId]);
        
        // Invalidate all sessions except current one
        $this->invalidateUserSessions($userId);
    }
    
    public function updateProfile($userId, $fullName, $email) {
        // Check if email is already used by another user
        $sql = "SELECT id FROM admin_users WHERE email = :email AND id != :id";
        $stmt = $this->db->query($sql, [':email' => $email, ':id' => $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Email already in use');
        }
        
        $sql = "UPDATE admin_users SET full_name = :full_name, email = :email, updated_at = NOW() WHERE id = :id";
        $this->db->query($sql, [':full_name' => $fullName, ':email' => $email, ':id' => $userId]);
    }
    
    private function userExists($username, $email) {
        $sql = "SELECT id FROM admin_users WHERE username = :username OR email = :email";
        $stmt = $this->db->query($sql, [':username' => $username, ':email' => $email]);
        return $stmt->fetch() !== false;
    }
    
    private function createSession($userId, $ipAddress, $userAgent) {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $sql = "INSERT INTO admin_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                VALUES (:user_id, :token, :ip, :agent, :expires)";
        
        $params = [
            ':user_id' => $userId,
            ':token' => $sessionToken,
            ':ip' => $ipAddress,
            ':agent' => $userAgent,
            ':expires' => $expiresAt
        ];
        
        $this->db->query($sql, $params);
        return $sessionToken;
    }
    
    private function extendSession($sessionToken) {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $sql = "UPDATE admin_sessions SET expires_at = :expires WHERE session_token = :token";
        $this->db->query($sql, [':expires' => $expiresAt, ':token' => $sessionToken]);
    }
    
    private function updateLastLogin($userId) {
        $sql = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
        $this->db->query($sql, [':id' => $userId]);
    }
    
    private function logLoginAttempt($identifier, $ipAddress, $userAgent, $success) {
        $sql = "INSERT INTO admin_login_attempts (email, ip_address, user_agent, success)
                VALUES (:email, :ip, :agent, :success)";
        
        $params = [
            ':email' => $identifier,
            ':ip' => $ipAddress,
            ':agent' => $userAgent,
            ':success' => $success ? 1 : 0
        ];
        
        $this->db->query($sql, $params);
    }
    
    private function isAccountLocked($identifier) {
        $sql = "SELECT COUNT(*) as attempts FROM admin_login_attempts 
                WHERE email = :email AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        
        $stmt = $this->db->query($sql, [':email' => $identifier]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= 5;
    }
    
    private function cleanupExpiredSessions() {
        $sql = "DELETE FROM admin_sessions WHERE expires_at < NOW()";
        $this->db->query($sql);
    }
    
    private function invalidateUserSessions($userId) {
        $sql = "DELETE FROM admin_sessions WHERE user_id = :id";
        $this->db->query($sql, [':id' => $userId]);
    }
    
    public function getAllUsers() {
        $sql = "SELECT id, username, email, full_name, is_active, last_login, created_at 
                FROM admin_users ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getUserById($id) {
        $sql = "SELECT id, username, email, full_name, is_active, last_login, created_at 
                FROM admin_users WHERE id = :id";
        $stmt = $this->db->query($sql, [':id' => $id]);
        return $stmt->fetch();
    }
    
    public function toggleUserStatus($id) {
        $sql = "UPDATE admin_users SET is_active = NOT is_active WHERE id = :id";
        $this->db->query($sql, [':id' => $id]);
    }
}
?>
