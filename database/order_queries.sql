-- Quick Reference: Common Order Queries
-- Copy and paste these queries for common operations

USE product_dashboard;

-- ============================================
-- CUSTOMER OPERATIONS
-- ============================================

-- 1. CREATE NEW ORDER (Customer places order)
CALL create_order(
    1,                                  -- customer_id (replace with actual customer ID)
    999.99,                             -- subtotal
    99.99,                              -- tax_amount (10%)
    1099.98,                            -- total_amount
    @new_order_id,                      -- OUTPUT: order ID
    @new_order_number                   -- OUTPUT: order number
);

-- Get the created order details
SELECT @new_order_id AS order_id, @new_order_number AS order_number;

-- 2. ADD ITEMS TO ORDER
CALL add_order_item(@new_order_id, 1, 2);  -- product_id=1, quantity=2
CALL add_order_item(@new_order_id, 3, 1);  -- product_id=3, quantity=1

-- 3. VIEW CUSTOMER'S ORDER HISTORY
SELECT * 
FROM customer_order_history 
WHERE customer_id = 1 
ORDER BY order_date DESC;

-- 4. GET SPECIFIC ORDER DETAILS
SELECT 
    o.*,
    oi.product_name,
    oi.quantity,
    oi.unit_price,
    oi.subtotal
FROM orders o
INNER JOIN order_items oi ON o.id = oi.order_id
WHERE o.id = 1;  -- Replace with actual order ID

-- 5. CHECK ORDER STATUS
SELECT 
    order_number,
    order_status,
    payment_status,
    delivery_status,
    total_amount
FROM orders
WHERE customer_id = 1 AND order_number = 'ORD-20251005-1234';

-- ============================================
-- ADMIN OPERATIONS
-- ============================================

-- 1. VIEW ALL PENDING PAYMENTS (Requires Admin Approval)
SELECT * FROM pending_payments ORDER BY hours_pending DESC;

-- 2. VIEW ALL ORDERS BY STATUS
SELECT * FROM order_summary WHERE payment_status = 'pending';
SELECT * FROM order_summary WHERE order_status = 'pending';
SELECT * FROM order_summary WHERE order_status = 'processing';

-- 3. APPROVE PAYMENT (Admin approves order payment)
CALL approve_payment(
    1,              -- order_id (the order to approve)
    1,              -- admin_id (admin user who is approving)
    'Cash'          -- payment_method (Cash, Credit Card, Bank Transfer, etc.)
);

-- 4. REJECT/CANCEL PAYMENT
CALL reject_payment(
    1,                          -- order_id
    1,                          -- admin_id
    'Insufficient payment'      -- reason for rejection
);

-- 5. VIEW ORDER WITH FULL DETAILS
SELECT 
    o.order_number,
    o.order_date,
    u.username AS customer,
    u.email AS customer_email,
    o.total_amount,
    o.order_status,
    o.payment_status,
    o.payment_method,
    approver.username AS approved_by,
    o.approval_date,
    GROUP_CONCAT(
        CONCAT(oi.product_name, ' (', oi.quantity, 'x @ KSh', oi.unit_price, ')')
        SEPARATOR ', '
    ) AS items
FROM orders o
INNER JOIN admin_users u ON o.customer_id = u.id
LEFT JOIN admin_users approver ON o.approved_by = approver.id
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE o.id = 1
GROUP BY o.id;

-- 6. UPDATE ORDER STATUS
UPDATE orders 
SET order_status = 'processing' 
WHERE id = 1 AND payment_status = 'paid';

UPDATE orders 
SET order_status = 'completed',
    delivery_status = 'delivered'
WHERE id = 1 AND order_status = 'processing';

-- 7. UPDATE DELIVERY STATUS
UPDATE orders 
SET delivery_status = 'shipped' 
WHERE id = 1 AND order_status = 'processing';

UPDATE orders 
SET delivery_status = 'delivered'
WHERE id = 1 AND delivery_status = 'shipped';

-- ============================================
-- REPORTING & ANALYTICS
-- ============================================

-- 1. ORDERS BY STATUS COUNT
SELECT 
    order_status,
    COUNT(*) AS count,
    SUM(total_amount) AS total_value
FROM orders
GROUP BY order_status;

-- 2. PAYMENT STATUS SUMMARY
SELECT 
    payment_status,
    COUNT(*) AS count,
    SUM(total_amount) AS total_value
FROM orders
GROUP BY payment_status;

-- 3. DAILY ORDERS REPORT
SELECT 
    DATE(order_date) AS date,
    COUNT(*) AS total_orders,
    SUM(total_amount) AS total_revenue,
    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) AS paid_revenue,
    SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) AS pending_revenue
FROM orders
WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(order_date)
ORDER BY date DESC;

-- 4. TOP CUSTOMERS BY ORDER VALUE
SELECT 
    u.username,
    u.full_name,
    COUNT(o.id) AS total_orders,
    SUM(o.total_amount) AS total_spent,
    AVG(o.total_amount) AS avg_order_value
FROM orders o
INNER JOIN admin_users u ON o.customer_id = u.id
GROUP BY u.id
ORDER BY total_spent DESC
LIMIT 10;

-- 5. BEST SELLING PRODUCTS (From Orders)
SELECT 
    p.name,
    p.category,
    SUM(oi.quantity) AS total_sold,
    SUM(oi.subtotal) AS total_revenue
FROM order_items oi
INNER JOIN products p ON oi.product_id = p.id
INNER JOIN orders o ON oi.order_id = o.id
WHERE o.payment_status = 'paid'
GROUP BY p.id
ORDER BY total_sold DESC
LIMIT 10;

-- 6. PENDING REVENUE (Money waiting for approval)
SELECT 
    SUM(total_amount) AS pending_revenue,
    COUNT(*) AS pending_count
FROM orders
WHERE payment_status = 'pending';

-- 7. ADMIN APPROVAL STATISTICS
SELECT 
    u.username AS admin,
    COUNT(*) AS orders_approved,
    SUM(o.total_amount) AS total_approved_value,
    MIN(o.approval_date) AS first_approval,
    MAX(o.approval_date) AS last_approval
FROM orders o
INNER JOIN admin_users u ON o.approved_by = u.id
WHERE o.payment_status = 'paid'
GROUP BY u.id
ORDER BY orders_approved DESC;

-- 8. ORDERS AWAITING APPROVAL (With Time)
SELECT 
    order_number,
    customer_name,
    total_amount,
    order_date,
    TIMESTAMPDIFF(HOUR, order_date, NOW()) AS hours_waiting,
    TIMESTAMPDIFF(DAY, order_date, NOW()) AS days_waiting
FROM pending_payments
ORDER BY order_date ASC;

-- ============================================
-- AUDIT & HISTORY QUERIES
-- ============================================

-- 1. VIEW PAYMENT HISTORY FOR AN ORDER
SELECT 
    ph.*,
    u.username AS changed_by_user
FROM payment_history ph
LEFT JOIN admin_users u ON ph.changed_by = u.id
WHERE ph.order_id = 1
ORDER BY ph.changed_at DESC;

-- 2. VIEW ORDER STATUS HISTORY
SELECT 
    osh.*,
    u.username AS changed_by_user
FROM order_status_history osh
LEFT JOIN admin_users u ON osh.changed_by = u.id
WHERE osh.order_id = 1
ORDER BY osh.changed_at DESC;

-- 3. COMPLETE AUDIT TRAIL FOR ORDER
SELECT 
    'Payment' AS change_type,
    CONCAT(old_status, ' → ', new_status) AS change,
    u.username AS changed_by,
    change_reason,
    changed_at
FROM payment_history ph
LEFT JOIN admin_users u ON ph.changed_by = u.id
WHERE ph.order_id = 1

UNION ALL

SELECT 
    'Order Status' AS change_type,
    CONCAT(old_status, ' → ', new_status) AS change,
    u.username AS changed_by,
    change_reason,
    changed_at
FROM order_status_history osh
LEFT JOIN admin_users u ON osh.changed_by = u.id
WHERE osh.order_id = 1

ORDER BY changed_at DESC;

-- ============================================
-- MAINTENANCE QUERIES
-- ============================================

-- 1. DELETE OLD CANCELLED ORDERS (Optional - use with caution)
-- DELETE FROM orders 
-- WHERE order_status = 'cancelled' 
-- AND order_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- 2. ARCHIVE OLD COMPLETED ORDERS (Create archive table first)
-- INSERT INTO orders_archive SELECT * FROM orders 
-- WHERE order_status = 'completed' 
-- AND order_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- 3. RESET AUTO INCREMENT (if needed)
-- ALTER TABLE orders AUTO_INCREMENT = 1;

-- 4. CHECK ORPHANED ORDER ITEMS
SELECT oi.* 
FROM order_items oi
LEFT JOIN orders o ON oi.order_id = o.id
WHERE o.id IS NULL;

-- 5. FIX STOCK DISCREPANCIES (if any)
-- This query shows products where stock might be incorrect due to cancelled orders
SELECT 
    p.id,
    p.name,
    p.stock AS current_stock,
    IFNULL(SUM(oi.quantity), 0) AS total_ordered,
    p.stock + IFNULL(SUM(oi.quantity), 0) AS calculated_stock
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status NOT IN ('cancelled')
GROUP BY p.id
HAVING current_stock != calculated_stock;

-- ============================================
-- TESTING QUERIES
-- ============================================

-- 1. CREATE TEST ORDER
CALL create_order(
    1, 100, 10, 110,
    @test_order_id, @test_order_number
);
SELECT @test_order_id, @test_order_number;

-- 2. ADD TEST ITEMS
CALL add_order_item(@test_order_id, 1, 1);

-- 3. APPROVE TEST ORDER
CALL approve_payment(@test_order_id, 1, 'Test Payment');

-- 4. VIEW TEST ORDER
SELECT * FROM orders WHERE id = @test_order_id;

-- 5. DELETE TEST ORDER (Clean up)
DELETE FROM orders WHERE id = @test_order_id;
