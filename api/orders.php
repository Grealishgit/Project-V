<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is authenticated
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // Create new order
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['items']) || empty($input['items'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Order items are required'
                ]);
                exit();
            }
            
            $customer_id = $_SESSION['admin_id'];
            $items = $input['items'];
            
            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += floatval($item['price']) * intval($item['quantity']);
            }
            
            $tax_amount = $subtotal * 0.10; // 10% tax
            $total_amount = $subtotal + $tax_amount;
            
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Create order using stored procedure
                $stmt = $db->prepare("CALL create_order(?, ?, ?, ?, @order_id, @order_number)");
                $stmt->execute([
                    $customer_id,
                    $subtotal,
                    $tax_amount,
                    $total_amount
                ]);
                
                // Get the created order ID and number
                $result = $db->query("SELECT @order_id AS order_id, @order_number AS order_number");
                $order = $result->fetch(PDO::FETCH_ASSOC);
                
                if (!$order || !$order['order_id']) {
                    throw new Exception('Failed to create order');
                }
                
                // Add items to order
                foreach ($items as $item) {
                    $stmt = $db->prepare("CALL add_order_item(?, ?, ?)");
                    $stmt->execute([
                        $order['order_id'],
                        $item['id'],
                        $item['quantity']
                    ]);
                }
                
                // Commit transaction
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Order placed successfully',
                    'order' => [
                        'id' => $order['order_id'],
                        'order_number' => $order['order_number'],
                        'subtotal' => number_format($subtotal, 2),
                        'tax' => number_format($tax_amount, 2),
                        'total' => number_format($total_amount, 2),
                        'status' => 'pending',
                        'payment_status' => 'pending'
                    ]
                ]);
                
            } catch (Exception $e) {
                // Rollback on error
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'list':
            // Get customer's orders
            $customer_id = $_SESSION['admin_id'];
            
            $sql = "SELECT * FROM customer_order_history WHERE customer_id = ? ORDER BY order_date DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$customer_id]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the orders
            foreach ($orders as &$order) {
                $order['formatted_subtotal'] = 'KSh ' . number_format($order['subtotal'], 2);
                $order['formatted_tax'] = 'KSh ' . number_format($order['tax_amount'], 2);
                $order['formatted_total'] = 'KSh ' . number_format($order['total_amount'], 2);
                $order['order_date_formatted'] = date('M d, Y H:i', strtotime($order['order_date']));
                
                // Add status badges
                $order['status_class'] = $order['order_status'];
                $order['payment_status_class'] = $order['payment_status'];
            }
            
            echo json_encode([
                'success' => true,
                'orders' => $orders
            ]);
            break;
            
        case 'details':
            // Get order details with items
            $order_id = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$order_id) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Order ID is required'
                ]);
                exit();
            }
            
            $customer_id = $_SESSION['admin_id'];
            $is_admin = $_SESSION['role'] === 'admin';
            
            // Get order details - admins can view all orders, customers only their own
            if ($is_admin) {
                $sql = "SELECT * FROM orders WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$order_id]);
            } else {
                $sql = "SELECT * FROM orders WHERE id = ? AND customer_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$order_id, $customer_id]);
            }
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Order not found'
                ]);
                exit();
            }
            
            // Get order items
            $sql = "SELECT * FROM order_items WHERE order_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format order
            $order['formatted_subtotal'] = 'KSh ' . number_format($order['subtotal'], 2);
            $order['formatted_tax'] = 'KSh ' . number_format($order['tax_amount'], 2);
            $order['formatted_total'] = 'KSh ' . number_format($order['total_amount'], 2);
            $order['order_date_formatted'] = date('M d, Y H:i', strtotime($order['order_date']));
            
            if ($order['payment_date']) {
                $order['payment_date_formatted'] = date('M d, Y H:i', strtotime($order['payment_date']));
            }
            
            // Format items
            foreach ($items as &$item) {
                $item['formatted_price'] = 'KSh ' . number_format($item['unit_price'], 2);
                $item['formatted_subtotal'] = 'KSh ' . number_format($item['subtotal'], 2);
            }
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
            break;
            
        case 'cancel':
            // Cancel order (only if payment is pending)
            $order_id = $_POST['id'] ?? null;
            
            if (!$order_id) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Order ID is required'
                ]);
                exit();
            }
            
            $customer_id = $_SESSION['admin_id'];
            
            // Check if order can be cancelled
            $sql = "SELECT * FROM orders WHERE id = ? AND customer_id = ? AND payment_status = 'pending'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$order_id, $customer_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Order not found or cannot be cancelled'
                ]);
                exit();
            }
            
            // Use reject_payment procedure to cancel and restore stock
            $stmt = $db->prepare("CALL reject_payment(?, ?, ?)");
            $stmt->execute([
                $order_id,
                $customer_id,
                'Cancelled by customer'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);
            break;
            
        // Admin actions
        case 'approve_payment':
            // Only admins can approve payments
            if ($_SESSION['role'] !== 'admin') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized'
                ]);
                exit();
            }
            
            $order_id = $_POST['order_id'] ?? null;
            $payment_method = $_POST['payment_method'] ?? 'Cash';
            
            if (!$order_id) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Order ID is required'
                ]);
                exit();
            }
            
            $admin_id = $_SESSION['admin_id'];
            
            // Approve payment using stored procedure
            $stmt = $db->prepare("CALL approve_payment(?, ?, ?)");
            $stmt->execute([
                $order_id,
                $admin_id,
                $payment_method
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment approved successfully'
            ]);
            break;
            
        case 'reject_payment':
            // Only admins can reject payments
            if ($_SESSION['role'] !== 'admin') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized'
                ]);
                exit();
            }
            
            $order_id = $_POST['order_id'] ?? null;
            $reason = $_POST['reason'] ?? 'Payment rejected by admin';
            
            if (!$order_id) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Order ID is required'
                ]);
                exit();
            }
            
            $admin_id = $_SESSION['admin_id'];
            
            // Reject payment using stored procedure
            $stmt = $db->prepare("CALL reject_payment(?, ?, ?)");
            $stmt->execute([
                $order_id,
                $admin_id,
                $reason
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment rejected successfully'
            ]);
            break;
            
        case 'pending_payments':
            // Get all pending payments (admin only)
            if ($_SESSION['role'] !== 'admin') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized'
                ]);
                exit();
            }
            
            $sql = "SELECT * FROM pending_payments ORDER BY hours_pending DESC";
            $stmt = $db->query($sql);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format orders
            foreach ($orders as &$order) {
                $order['formatted_total'] = 'KSh ' . number_format($order['total_amount'], 2);
                $order['order_date_formatted'] = date('M d, Y H:i', strtotime($order['order_date']));
            }
            
            echo json_encode([
                'success' => true,
                'orders' => $orders
            ]);
            break;
            
        case 'admin_list':
            // Get all orders (admin only)
            if ($_SESSION['role'] !== 'admin') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized'
                ]);
                exit();
            }
            
            $sql = "SELECT * FROM order_summary ORDER BY order_date DESC";
            $stmt = $db->query($sql);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format orders
            foreach ($orders as &$order) {
                $order['formatted_total'] = number_format($order['total_amount'], 2);
                $order['formatted_subtotal'] = number_format($order['subtotal'], 2);
                $order['formatted_tax'] = number_format($order['tax_amount'], 2);
                $order['order_date_formatted'] = date('M d, Y H:i', strtotime($order['order_date']));
            }
            
            echo json_encode([
                'success' => true,
                'orders' => $orders
            ]);
            break;
            
        case 'order_items':
            // Get all order items (admin only)
            if ($_SESSION['role'] !== 'admin') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized'
                ]);
                exit();
            }
            
            $sql = "SELECT 
                        oi.*,
                        o.order_number,
                        DATE_FORMAT(oi.created_at, '%M %d, %Y %H:%i') as created_at_formatted
                    FROM order_items oi
                    INNER JOIN orders o ON oi.order_id = o.id
                    ORDER BY oi.created_at DESC";
            $stmt = $db->query($sql);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format items
            foreach ($items as &$item) {
                $item['formatted_price'] = number_format($item['unit_price'], 2);
                $item['formatted_subtotal'] = number_format($item['subtotal'], 2);
            }
            
            echo json_encode([
                'success' => true,
                'items' => $items
            ]);
            break;
            
        case 'customers':
            // Get all customers with order stats (admin only)
            if ($_SESSION['role'] !== 'admin') {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized'
                ]);
                exit();
            }
            
            $sql = "SELECT 
                        u.id,
                        u.username,
                        u.full_name,
                        u.email,
                        u.created_at,
                        DATE_FORMAT(u.created_at, '%M %d, %Y') as created_at_formatted,
                        COUNT(DISTINCT o.id) as order_count,
                        COALESCE(SUM(o.total_amount), 0) as total_spent
                    FROM admin_users u
                    LEFT JOIN orders o ON u.id = o.customer_id AND o.payment_status = 'paid'
                    WHERE u.role = 'customer'
                    GROUP BY u.id
                    ORDER BY u.created_at DESC";
            $stmt = $db->query($sql);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format customers
            foreach ($customers as &$customer) {
                $customer['formatted_total_spent'] = number_format($customer['total_spent'], 2);
                $customer['status'] = 'active'; // All customer accounts are active by default
            }
            
            echo json_encode([
                'success' => true,
                'customers' => $customers
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>