<?php
session_start();

header('Content-Type: application/json');

// Debug session information
echo json_encode([
    'session_exists' => isset($_SESSION['admin_id']),
    'session_data' => [
        'admin_id' => $_SESSION['admin_id'] ?? 'not set',
        'username' => $_SESSION['username'] ?? 'not set',
        'full_name' => $_SESSION['full_name'] ?? 'not set',
        'email' => $_SESSION['email'] ?? 'not set',
        'role' => $_SESSION['role'] ?? 'not set'
    ],
    'all_session_keys' => array_keys($_SESSION)
], JSON_PRETTY_PRINT);
?>
