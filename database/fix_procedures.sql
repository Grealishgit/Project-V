-- Fix Orders Stored Procedures -- This script drops and recreates the stored procedures with correct parameters USE product_dashboard;

-- Drop existing procedures DROP PROCEDURE IF EXISTS create_order;
DROP PROCEDURE IF EXISTS add_order_item;
DROP PROCEDURE IF EXISTS approve_payment;
DROP PROCEDURE IF EXISTS reject_payment;

-- Recreate procedures with correct definitions DELIMITER //

-- Procedure to create a new order (6 parameters: 4 IN, 2 OUT) CREATE PROCEDURE create_order(IN p_customer_id INT,
    IN p_subtotal DECIMAL(10, 2),
    IN p_tax_amount DECIMAL(10, 2),
    IN p_total_amount DECIMAL(10, 2),
    OUT p_order_id INT,
    OUT p_order_number VARCHAR(50)) BEGIN -- Generate unique order number SET p_order_number=CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));

-- Insert order INSERT INTO orders (customer_id,
    order_number,
    subtotal,
    tax_amount,
    total_amount,
    order_status,
    payment_status) VALUES (p_customer_id,
    p_order_number,
    p_subtotal,
    p_tax_amount,
    p_total_amount,
    'pending',
    'pending'
);

-- Get the inserted order ID SET p_order_id=LAST_INSERT_ID();
END //

-- Procedure to add item to order (3 parameters) CREATE PROCEDURE add_order_item(IN p_order_id INT,
    IN p_product_id INT,
    IN p_quantity INT) BEGIN DECLARE v_product_name VARCHAR(255);
DECLARE v_product_category VARCHAR(100);
DECLARE v_product_image VARCHAR(255);
DECLARE v_unit_price DECIMAL(10, 2);
DECLARE v_subtotal DECIMAL(10, 2);

-- Get product details SELECT name,
category,
image,
price INTO v_product_name,
v_product_category,
v_product_image,
v_unit_price FROM products WHERE id=p_product_id;

-- Calculate subtotal SET v_subtotal=v_unit_price * p_quantity;

-- Insert order item INSERT INTO order_items (order_id,
    product_id,
    product_name,
    product_category,
    product_image,
    unit_price,
    quantity,
    subtotal) VALUES (p_order_id,
    p_product_id,
    v_product_name,
    v_product_category,
    v_product_image,
    v_unit_price,
    p_quantity,
    v_subtotal);

-- Update product stock UPDATE products SET stock=stock - p_quantity WHERE id=p_product_id;
END //

-- Procedure to approve payment (3 parameters) CREATE PROCEDURE approve_payment(IN p_order_id INT,
    IN p_admin_id INT,
    IN p_payment_method VARCHAR(50)) BEGIN DECLARE v_old_status VARCHAR(20);

-- Get current payment status SELECT payment_status INTO v_old_status FROM orders WHERE id=p_order_id;

-- Update order payment status UPDATE orders SET payment_status='paid',
payment_method=p_payment_method,
payment_date=NOW(),
approved_by=p_admin_id,
approval_date=NOW(),
order_status='processing'
WHERE id=p_order_id;

-- Log payment history INSERT INTO payment_history (order_id, old_status, new_status, changed_by, change_reason) VALUES (p_order_id, v_old_status, 'paid', p_admin_id, 'Payment approved by admin');

-- Log order status change INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason) VALUES (p_order_id, 'pending', 'processing', p_admin_id, 'Payment approved, order processing started');
END //

-- Procedure to reject/cancel payment (3 parameters) CREATE PROCEDURE reject_payment(IN p_order_id INT,
    IN p_admin_id INT,
    IN p_reason TEXT) BEGIN DECLARE v_old_payment_status VARCHAR(20);
DECLARE v_old_order_status VARCHAR(20);

-- Get current statuses SELECT payment_status,
order_status INTO v_old_payment_status,
v_old_order_status FROM orders WHERE id=p_order_id;

-- Update order UPDATE orders SET payment_status='failed',
order_status='cancelled'
WHERE id=p_order_id;

-- Log payment history INSERT INTO payment_history (order_id, old_status, new_status, changed_by, change_reason) VALUES (p_order_id, v_old_payment_status, 'failed', p_admin_id, p_reason);

-- Log order status change INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason) VALUES (p_order_id, v_old_order_status, 'cancelled', p_admin_id, p_reason);

-- Restore product stock UPDATE products p INNER JOIN order_items oi ON p.id=oi.product_id SET p.stock=p.stock+oi.quantity WHERE oi.order_id=p_order_id;
END //

DELIMITER;

-- Verify procedures were created SELECT ROUTINE_NAME,
ROUTINE_TYPE,
ROUTINE_DEFINITION FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA='product_dashboard'
AND ROUTINE_NAME IN ('create_order', 'add_order_item', 'approve_payment', 'reject_payment');

-- Show procedure parameters SELECT SPECIFIC_NAME as procedure_name,
PARAMETER_NAME,
DATA_TYPE,
PARAMETER_MODE,
ORDINAL_POSITION FROM information_schema.PARAMETERS WHERE SPECIFIC_SCHEMA='product_dashboard'
AND SPECIFIC_NAME IN ('create_order', 'add_order_item', 'approve_payment', 'reject_payment') ORDER BY SPECIFIC_NAME,
ORDINAL_POSITION;