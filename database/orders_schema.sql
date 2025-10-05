-- Orders Schema for Customer Order Management
-- This schema handles customer orders with payment approval workflow

USE product_dashboard;

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Order totals
    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL,
    
    -- Order status
    order_status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    
    -- Payment information
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_date TIMESTAMP NULL DEFAULT NULL,
    approved_by INT NULL DEFAULT NULL, -- Admin user ID who approved payment
    approval_date TIMESTAMP NULL DEFAULT NULL,
    
    -- Delivery information (optional)
    delivery_status ENUM('pending', 'shipped', 'delivered', 'returned') DEFAULT 'pending',
    
    -- Timestamps
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (customer_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Indexes for faster queries
    INDEX idx_customer (customer_id),
    INDEX idx_order_number (order_number),
    INDEX idx_order_date (order_date),
    INDEX idx_order_status (order_status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_delivery_status (delivery_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create order_items table (products in each order)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    
    -- Product details at time of order (in case product changes later)
    product_name VARCHAR(255) NOT NULL,
    product_category VARCHAR(100) NOT NULL,
    product_image VARCHAR(255) NULL,
    
    -- Pricing
    unit_price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL, -- unit_price * quantity
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    
    -- Indexes
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create payment_history table (track all payment status changes)
CREATE TABLE IF NOT EXISTS payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    
    -- Status change
    old_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL,
    new_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL,
    
    -- Who made the change
    changed_by INT NULL, -- Admin user ID
    change_reason TEXT NULL,
    
    -- Timestamp
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_order (order_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create order_status_history table (track all order status changes)
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    
    -- Status change
    old_status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL,
    new_status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL,
    
    -- Who made the change
    changed_by INT NULL, -- Admin user ID
    change_reason TEXT NULL,
    
    -- Timestamp
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_order (order_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- Procedure to create a new order
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS create_order(
    IN p_customer_id INT,
    IN p_subtotal DECIMAL(10,2),
    IN p_tax_amount DECIMAL(10,2),
    IN p_total_amount DECIMAL(10,2),
    OUT p_order_id INT,
    OUT p_order_number VARCHAR(50)
)
BEGIN
    -- Generate unique order number
    SET p_order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Insert order
    INSERT INTO orders (
        customer_id, 
        order_number, 
        subtotal, 
        tax_amount, 
        total_amount,
        order_status,
        payment_status
    ) VALUES (
        p_customer_id,
        p_order_number,
        p_subtotal,
        p_tax_amount,
        p_total_amount,
        'pending',
        'pending'
    );
    
    -- Get the inserted order ID
    SET p_order_id = LAST_INSERT_ID();
END //

-- Procedure to add item to order
CREATE PROCEDURE IF NOT EXISTS add_order_item(
    IN p_order_id INT,
    IN p_product_id INT,
    IN p_quantity INT
)
BEGIN
    DECLARE v_product_name VARCHAR(255);
    DECLARE v_product_category VARCHAR(100);
    DECLARE v_product_image VARCHAR(255);
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_subtotal DECIMAL(10,2);
    
    -- Get product details
    SELECT name, category, image, price
    INTO v_product_name, v_product_category, v_product_image, v_unit_price
    FROM products
    WHERE id = p_product_id;
    
    -- Calculate subtotal
    SET v_subtotal = v_unit_price * p_quantity;
    
    -- Insert order item
    INSERT INTO order_items (
        order_id,
        product_id,
        product_name,
        product_category,
        product_image,
        unit_price,
        quantity,
        subtotal
    ) VALUES (
        p_order_id,
        p_product_id,
        v_product_name,
        v_product_category,
        v_product_image,
        v_unit_price,
        p_quantity,
        v_subtotal
    );
    
    -- Update product stock
    UPDATE products 
    SET stock = stock - p_quantity 
    WHERE id = p_product_id;
END //

-- Procedure to approve payment
CREATE PROCEDURE IF NOT EXISTS approve_payment(
    IN p_order_id INT,
    IN p_admin_id INT,
    IN p_payment_method VARCHAR(50)
)
BEGIN
    DECLARE v_old_status VARCHAR(20);
    
    -- Get current payment status
    SELECT payment_status INTO v_old_status
    FROM orders
    WHERE id = p_order_id;
    
    -- Update order payment status
    UPDATE orders
    SET payment_status = 'paid',
        payment_method = p_payment_method,
        payment_date = NOW(),
        approved_by = p_admin_id,
        approval_date = NOW(),
        order_status = 'processing'
    WHERE id = p_order_id;
    
    -- Log payment history
    INSERT INTO payment_history (order_id, old_status, new_status, changed_by, change_reason)
    VALUES (p_order_id, v_old_status, 'paid', p_admin_id, 'Payment approved by admin');
    
    -- Log order status change
    INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason)
    VALUES (p_order_id, 'pending', 'processing', p_admin_id, 'Payment approved, order processing started');
END //

-- Procedure to reject/cancel payment
CREATE PROCEDURE IF NOT EXISTS reject_payment(
    IN p_order_id INT,
    IN p_admin_id INT,
    IN p_reason TEXT
)
BEGIN
    DECLARE v_old_payment_status VARCHAR(20);
    DECLARE v_old_order_status VARCHAR(20);
    
    -- Get current statuses
    SELECT payment_status, order_status 
    INTO v_old_payment_status, v_old_order_status
    FROM orders
    WHERE id = p_order_id;
    
    -- Update order
    UPDATE orders
    SET payment_status = 'failed',
        order_status = 'cancelled'
    WHERE id = p_order_id;
    
    -- Log payment history
    INSERT INTO payment_history (order_id, old_status, new_status, changed_by, change_reason)
    VALUES (p_order_id, v_old_payment_status, 'failed', p_admin_id, p_reason);
    
    -- Log order status change
    INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason)
    VALUES (p_order_id, v_old_order_status, 'cancelled', p_admin_id, p_reason);
    
    -- Restore product stock
    UPDATE products p
    INNER JOIN order_items oi ON p.id = oi.product_id
    SET p.stock = p.stock + oi.quantity
    WHERE oi.order_id = p_order_id;
END //

DELIMITER ;

-- ============================================
-- VIEWS FOR EASIER QUERYING
-- ============================================

-- View for order summary
CREATE OR REPLACE VIEW order_summary AS
SELECT 
    o.id,
    o.order_number,
    o.order_date,
    o.customer_id,
    u.username AS customer_username,
    u.full_name AS customer_name,
    u.email AS customer_email,
    o.subtotal,
    o.tax_amount,
    o.total_amount,
    o.order_status,
    o.payment_status,
    o.payment_method,
    o.payment_date,
    o.delivery_status,
    approver.username AS approved_by_username,
    o.approval_date,
    COUNT(oi.id) AS total_items,
    SUM(oi.quantity) AS total_quantity
FROM orders o
INNER JOIN admin_users u ON o.customer_id = u.id
LEFT JOIN admin_users approver ON o.approved_by = approver.id
LEFT JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id;

-- View for pending payments (for admin review)
CREATE OR REPLACE VIEW pending_payments AS
SELECT 
    o.id,
    o.order_number,
    o.order_date,
    u.username AS customer_username,
    u.full_name AS customer_name,
    u.email AS customer_email,
    o.total_amount,
    COUNT(oi.id) AS total_items,
    TIMESTAMPDIFF(HOUR, o.order_date, NOW()) AS hours_pending
FROM orders o
INNER JOIN admin_users u ON o.customer_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE o.payment_status = 'pending'
GROUP BY o.id
ORDER BY o.order_date DESC;

-- View for customer order history
CREATE OR REPLACE VIEW customer_order_history AS
SELECT 
    o.id,
    o.order_number,
    o.order_date,
    o.customer_id,
    o.subtotal,
    o.tax_amount,
    o.total_amount,
    o.order_status,
    o.payment_status,
    o.payment_date,
    o.delivery_status,
    COUNT(oi.id) AS total_items,
    SUM(oi.quantity) AS total_quantity
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id
ORDER BY o.order_date DESC;

-- ============================================
-- SAMPLE DATA (OPTIONAL - FOR TESTING)
-- ============================================

-- Uncomment below to insert sample orders for testing

/*
-- Insert a sample pending order
CALL create_order(
    1, -- customer_id (make sure this exists in admin_users)
    999.99, -- subtotal
    99.99, -- tax
    1099.98, -- total
    @order_id,
    @order_number
);

-- Add items to the order
CALL add_order_item(@order_id, 1, 2); -- Product ID 1, Quantity 2
CALL add_order_item(@order_id, 3, 1); -- Product ID 3, Quantity 1
*/

-- ============================================
-- USEFUL QUERIES
-- ============================================

-- Get all pending payments
-- SELECT * FROM pending_payments;

-- Get order details with items
-- SELECT o.*, oi.* 
-- FROM orders o 
-- INNER JOIN order_items oi ON o.id = oi.order_id 
-- WHERE o.id = 1;

-- Approve a payment
-- CALL approve_payment(1, 1, 'Cash'); -- order_id, admin_id, payment_method

-- Reject a payment
-- CALL reject_payment(1, 1, 'Insufficient funds'); -- order_id, admin_id, reason

-- Get customer's order history
-- SELECT * FROM customer_order_history WHERE customer_id = 1;

-- Get orders by status
-- SELECT * FROM order_summary WHERE order_status = 'pending';
-- SELECT * FROM order_summary WHERE payment_status = 'pending';
