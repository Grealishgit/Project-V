# Order Management System - Database Schema Documentation

## Overview
This schema implements a complete order management system with payment approval workflow. By default, all orders are created with `payment_status = 'pending'` and require admin approval before being marked as paid.

## Database Tables

### 1. **orders**
Main table storing customer orders.

**Key Fields:**
- `order_number`: Unique order identifier (e.g., ORD-20251005-1234)
- `customer_id`: References the customer from admin_users table
- `order_status`: pending → processing → completed/cancelled
- `payment_status`: **pending** (default) → paid/failed/refunded
- `payment_date`: When admin approved the payment
- `approved_by`: Admin user who approved the payment
- `approval_date`: When the payment was approved
- `delivery_status`: pending → shipped → delivered/returned

**Status Flow:**
```
New Order Created:
  order_status: 'pending'
  payment_status: 'pending' ← DEFAULT

Admin Approves Payment:
  payment_status: 'pending' → 'paid'
  order_status: 'pending' → 'processing'
  
Order Completed:
  order_status: 'processing' → 'completed'
```

### 2. **order_items**
Stores individual products in each order.

**Features:**
- Captures product details at time of order (price, name, category)
- Stores quantity and calculates subtotal
- Automatically reduces product stock when order is created

### 3. **payment_history**
Tracks all payment status changes for audit trail.

**Records:**
- Old status → New status
- Who made the change (admin)
- Reason for change
- Timestamp

### 4. **order_status_history**
Tracks all order status changes.

**Records:**
- Status transitions
- Admin who made the change
- Reason for change
- Timestamp

## Stored Procedures

### 1. `create_order()`
Creates a new order with pending payment status.

**Usage:**
```sql
CALL create_order(
    1,                           -- customer_id
    999.99,                      -- subtotal
    99.99,                       -- tax_amount
    1099.98,                     -- total_amount
    '123 Main St',               -- delivery_address
    '+1234567890',               -- delivery_phone
    'Deliver in morning',        -- customer_notes
    @order_id,                   -- OUT: returns order ID
    @order_number                -- OUT: returns order number
);

SELECT @order_id, @order_number;
```

### 2. `add_order_item()`
Adds a product to an order and updates stock.

**Usage:**
```sql
CALL add_order_item(
    @order_id,                   -- order ID from create_order
    1,                           -- product_id
    2                            -- quantity
);
```

### 3. `approve_payment()`
Admin approves payment - changes status from pending to paid.

**Usage:**
```sql
CALL approve_payment(
    1,                           -- order_id
    1,                           -- admin_id (who approved)
    'Cash'                       -- payment_method
);
```

**What it does:**
- Sets `payment_status = 'paid'`
- Records payment date and admin who approved
- Changes `order_status` to 'processing'
- Logs changes to payment_history and order_status_history

### 4. `reject_payment()`
Admin rejects/cancels payment.

**Usage:**
```sql
CALL reject_payment(
    1,                           -- order_id
    1,                           -- admin_id
    'Insufficient funds'         -- reason
);
```

**What it does:**
- Sets `payment_status = 'failed'`
- Sets `order_status = 'cancelled'`
- Restores product stock (reverses the order)
- Logs all changes to history tables

## Views for Easy Querying

### 1. `order_summary`
Complete order overview with customer and approval details.

**Usage:**
```sql
SELECT * FROM order_summary 
WHERE payment_status = 'pending'
ORDER BY order_date DESC;
```

### 2. `pending_payments`
Shows all orders awaiting payment approval.

**Usage:**
```sql
SELECT * FROM pending_payments;
```

**Shows:**
- Order details
- Customer information
- Total amount
- How long payment has been pending (hours)

### 3. `customer_order_history`
View all orders for a specific customer.

**Usage:**
```sql
SELECT * FROM customer_order_history 
WHERE customer_id = 1
ORDER BY order_date DESC;
```

## Complete Order Creation Flow

### PHP/JavaScript Example:

```javascript
// 1. Customer places order
const orderData = {
    customer_id: currentUserId,
    items: cartManager.cart,
    delivery_address: '123 Main St',
    delivery_phone: '+1234567890',
    customer_notes: 'Ring doorbell'
};

// 2. Send to backend API
fetch('api/orders.php?action=create', {
    method: 'POST',
    body: JSON.stringify(orderData)
});
```

### Backend API (api/orders.php):

```php
// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.10; // 10% tax
$total = $subtotal + $tax;

// Create order (starts with pending payment)
$stmt = $db->prepare("CALL create_order(?, ?, ?, ?, ?, ?, ?, @order_id, @order_number)");
$stmt->execute([
    $customer_id, 
    $subtotal, 
    $tax, 
    $total,
    $delivery_address,
    $delivery_phone,
    $customer_notes
]);

// Get the created order ID and number
$result = $db->query("SELECT @order_id AS order_id, @order_number AS order_number");
$order = $result->fetch(PDO::FETCH_ASSOC);

// Add items to order
foreach ($items as $item) {
    $stmt = $db->prepare("CALL add_order_item(?, ?, ?)");
    $stmt->execute([
        $order['order_id'],
        $item['product_id'],
        $item['quantity']
    ]);
}
```

## Admin Payment Approval Flow

### View Pending Payments:
```sql
SELECT * FROM pending_payments;
```

### Approve Payment:
```sql
CALL approve_payment(
    1,              -- order_id
    1,              -- admin_id
    'Credit Card'   -- payment_method
);
```

### Check Payment History:
```sql
SELECT * FROM payment_history WHERE order_id = 1;
```

## Installation

1. **Run the schema:**
   ```bash
   mysql -u your_user -p product_dashboard < database/orders_schema.sql
   ```

2. **Verify tables created:**
   ```sql
   SHOW TABLES LIKE '%order%';
   ```

3. **Check procedures:**
   ```sql
   SHOW PROCEDURE STATUS WHERE Db = 'product_dashboard';
   ```

## Status Reference

### Order Status Values:
- `pending` - Order created, awaiting payment approval
- `processing` - Payment approved, order being prepared
- `completed` - Order fulfilled and delivered
- `cancelled` - Order cancelled by customer or admin

### Payment Status Values:
- `pending` - **DEFAULT** - Awaiting admin approval
- `paid` - Admin approved payment
- `failed` - Payment rejected/failed
- `refunded` - Payment was refunded

### Delivery Status Values:
- `pending` - Not yet shipped
- `shipped` - In transit
- `delivered` - Delivered to customer
- `returned` - Returned by customer

## Security Considerations

1. **Foreign Keys:** Cascade deletes for order items, protect products
2. **Audit Trail:** All status changes are logged
3. **Stock Management:** Automatically updates and restores stock
4. **Role-Based:** Only admins can approve/reject payments

## Useful Queries

### Get all orders for a customer:
```sql
SELECT * FROM customer_order_history WHERE customer_id = 1;
```

### Get orders pending approval:
```sql
SELECT * FROM pending_payments;
```

### Get order with all items:
```sql
SELECT o.*, oi.* 
FROM orders o 
INNER JOIN order_items oi ON o.id = oi.order_id 
WHERE o.order_number = 'ORD-20251005-1234';
```

### Get payment approval statistics:
```sql
SELECT 
    payment_status,
    COUNT(*) as count,
    SUM(total_amount) as total_value
FROM orders
GROUP BY payment_status;
```

### Get admin approval activity:
```sql
SELECT 
    u.username,
    COUNT(*) as orders_approved,
    SUM(o.total_amount) as total_approved
FROM orders o
INNER JOIN admin_users u ON o.approved_by = u.id
WHERE o.payment_status = 'paid'
GROUP BY u.username;
```

## Next Steps

To complete the order management system, you'll need to create:

1. **API endpoints** (`api/orders.php`):
   - `create` - Place new order
   - `list` - Get customer's orders
   - `details` - Get order details
   - `approve_payment` - Admin approves payment
   - `reject_payment` - Admin rejects payment

2. **Customer UI** (already created):
   - View orders section
   - Order confirmation page
   - Order tracking

3. **Admin UI**:
   - Pending payments dashboard
   - Order management
   - Payment approval interface

Would you like me to create these API endpoints and UI components next?
