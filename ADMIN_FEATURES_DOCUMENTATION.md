# Admin Order Management & System Tools - Documentation

## Overview
This document describes the new admin features for managing orders, viewing customers, and performing database backups.

---

## 1. Orders Management Page

### Location
**Navigation:** Admin Sidebar → Orders

### Features

#### Order Statistics Cards
- **Total Orders** - Count of all orders in the system
- **Pending Payment** - Orders awaiting payment approval
- **Paid Orders** - Orders with approved payments
- **Total Revenue** - Sum of all paid orders

#### Orders Table
Displays all customer orders with the following columns:
- Order Number
- Customer (Name & Email)
- Order Date
- Number of Items
- Total Amount
- Order Status (pending, processing, completed, cancelled)
- Payment Status (pending, paid, failed)
- Action Buttons

#### Filters
- **Order Status Filter** - Filter by order status
- **Payment Status Filter** - Filter by payment status
- **Refresh Button** - Reload orders data

#### Actions
1. **View Details** - Opens modal with complete order information
2. **Approve Payment** - Approve pending payment (prompts for payment method)
3. **Reject Payment** - Reject and cancel order (prompts for reason)

### API Endpoint
```
GET api/orders.php?action=admin_list
```

Returns all orders from the `order_summary` view.

---

## 2. Order Items Page

### Location
**Navigation:** Admin Sidebar → Order Items

### Features

#### Order Items Table
Displays all items from all orders with:
- Order Number
- Product Name
- Category
- Unit Price
- Quantity
- Subtotal
- Date Added

#### Search Functionality
- Search by product name or order number
- Real-time filtering with debounce

### API Endpoint
```
GET api/orders.php?action=order_items
```

Returns all order items joined with order information.

---

## 3. Customers Page

### Location
**Navigation:** Admin Sidebar → Customers

### Features

#### Customer Statistics Cards
- **Total Customers** - All customers in the system
- **Active Customers** - Customers with active status
- **With Orders** - Customers who have placed orders
- **New This Month** - Customers registered in current month

#### Customers Table
Displays all customer accounts with:
- Customer ID
- Username
- Full Name
- Email Address
- Order Count
- Total Amount Spent
- Join Date
- Account Status

#### Search Functionality
- Search by username, full name, or email
- Real-time filtering

### API Endpoint
```
GET api/orders.php?action=customers
```

Returns all users with `role = 'customer'` along with their order statistics.

---

## 4. Database Backup Page

### Location
**Navigation:** Admin Sidebar → Backup

### Features

#### Create Backup Section
**Options:**
- ☑ Products
- ☑ Orders
- ☑ Customers
- ☑ Analytics

**Functionality:**
- Select which tables to include in backup
- Creates SQL dump file
- Automatically downloads backup file
- Includes table structures and data

#### Restore Backup Section
**Functionality:**
- Upload .sql backup file
- Restore database from backup
- ⚠️ **WARNING:** Overwrites all current data

#### Backup History Section
**Displays:**
- Backup filename
- Creation date and time
- File size
- Action buttons (Download, Delete)

#### Important Information Panel
- Backup best practices
- Security recommendations
- Warning about data overwrite

### API Endpoints

#### Create Backup
```
POST api/backup.php?action=create
Body: {
  "products": true,
  "orders": true,
  "customers": true,
  "analytics": true
}
```

#### Get Backup History
```
GET api/backup.php?action=history
```

#### Download Backup
```
GET api/backup.php?action=download&file=backup_2025-10-06_10-30-00.sql
```

#### Delete Backup
```
POST api/backup.php?action=delete
Body: filename=backup_2025-10-06_10-30-00.sql
```

#### Restore Backup
```
POST api/backup.php?action=restore
Body: FormData with 'backup_file'
```

---

## Database Views Used

### order_summary
Complete view of orders with customer information:
```sql
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
    COUNT(oi.id) AS total_items,
    SUM(oi.quantity) AS total_quantity
FROM orders o
INNER JOIN admin_users u ON o.customer_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id
```

### pending_payments
View for pending payment approvals:
```sql
SELECT 
    o.id,
    o.order_number,
    o.order_date,
    u.username AS customer_username,
    u.full_name AS customer_name,
    o.total_amount,
    COUNT(oi.id) AS total_items,
    TIMESTAMPDIFF(HOUR, o.order_date, NOW()) AS hours_pending
FROM orders o
INNER JOIN admin_users u ON o.customer_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE o.payment_status = 'pending'
GROUP BY o.id
```

---

## Security Features

### Authentication & Authorization
1. **Session Validation** - All API endpoints check for valid session
2. **Role-Based Access** - Admin-only endpoints verify `role = 'admin'`
3. **Input Validation** - All inputs sanitized and validated
4. **SQL Injection Prevention** - PDO prepared statements used throughout

### Backup Security
1. **Admin-Only Access** - Backup features restricted to admin role
2. **File Type Validation** - Only .sql files allowed for restore
3. **Secure Directory** - Backups stored outside web root (recommended)
4. **Confirmation Dialogs** - Critical actions require user confirmation

---

## File Structure

### New/Modified Files

```
api/
├── orders.php (modified)
│   ├── admin_list action
│   ├── order_items action
│   └── customers action
└── backup.php (new)
    ├── create action
    ├── restore action
    ├── history action
    ├── download action
    └── delete action

assets/
├── js/
│   └── main.js (modified)
│       ├── loadAdminOrders()
│       ├── loadOrderItems()
│       ├── loadCustomers()
│       ├── createBackup()
│       ├── restoreBackup()
│       └── loadBackupHistory()
└── css/
    └── style.css (modified)
        ├── .backup-container
        ├── .backup-card
        └── responsive styles

backups/ (new directory - auto-created)
└── backup_YYYY-MM-DD_HH-MM-SS.sql
```

---

## Usage Workflow

### Approving Orders
1. Admin navigates to **Orders** page
2. Reviews pending payment orders
3. Clicks **Approve** button on order
4. Enters payment method (Cash, M-Pesa, Bank Transfer, etc.)
5. Order status changes to "Processing"
6. Payment status changes to "Paid"
7. Customer receives updated order status

### Rejecting Orders
1. Admin navigates to **Orders** page
2. Clicks **Reject** button on pending order
3. Enters rejection reason
4. Confirms cancellation
5. Order status changes to "Cancelled"
6. Payment status changes to "Failed"
7. Product stock is restored

### Creating Backups
1. Admin navigates to **Backup** page
2. Selects tables to include
3. Clicks **Create Backup**
4. Backup file automatically downloads
5. File saved to `backups/` directory
6. Entry appears in Backup History

### Restoring Backups
1. Admin navigates to **Backup** page
2. Clicks **Choose Backup File**
3. Selects .sql file
4. Clicks **Restore Backup**
5. Confirms restoration (warning shown)
6. Database restored from backup
7. Page reloads with restored data

---

## Error Handling

### Orders Management
- Invalid order ID → "Order not found"
- Unauthorized access → "Unauthorized"
- Database error → "An error occurred" + error message

### Backup Operations
- No file selected → "Please select a backup file"
- Invalid file type → "Only .sql files are allowed"
- Upload error → "File upload error"
- Restoration error → "Failed to restore database" + details

---

## Performance Considerations

1. **Database Indexing** - All foreign keys and frequently queried columns indexed
2. **Pagination** - Consider adding pagination for large datasets
3. **Caching** - Statistics can be cached to reduce database load
4. **Backup Size** - Large databases may require streaming backup creation

---

## Future Enhancements

### Suggested Improvements
1. **Email Notifications** - Notify customers of order status changes
2. **Export to CSV** - Export orders and customers to CSV
3. **Advanced Filters** - Date range, customer filters
4. **Order Editing** - Allow admin to modify order details
5. **Bulk Actions** - Approve/reject multiple orders at once
6. **Scheduled Backups** - Automatic daily/weekly backups
7. **Cloud Backup** - Upload backups to cloud storage
8. **Backup Encryption** - Encrypt backup files for security
9. **Audit Logs** - Track all admin actions
10. **Dashboard Charts** - Visual representations of order data

---

## Testing Checklist

### Orders Management
- [ ] View all orders
- [ ] Filter by order status
- [ ] Filter by payment status
- [ ] View order details
- [ ] Approve payment
- [ ] Reject payment
- [ ] Verify stock restoration on rejection

### Order Items
- [ ] View all order items
- [ ] Search by product name
- [ ] Search by order number

### Customers
- [ ] View all customers
- [ ] View customer statistics
- [ ] Search customers
- [ ] Verify order counts
- [ ] Verify total spent calculations

### Backup
- [ ] Create full backup
- [ ] Create partial backup (selected tables)
- [ ] Download backup file
- [ ] View backup history
- [ ] Delete old backup
- [ ] Restore from backup
- [ ] Verify data after restoration

---

## Troubleshooting

### Orders Not Loading
1. Check browser console for errors
2. Verify API endpoint: `api/orders.php?action=admin_list`
3. Check admin role in session
4. Review PHP error logs

### Backup Creation Fails
1. Check directory permissions (`backups/` folder)
2. Verify database connection
3. Check PHP memory limit for large databases
4. Review error logs

### Restore Fails
1. Verify .sql file format
2. Check database user permissions
3. Ensure file upload size limits
4. Review SQL syntax in backup file

---

**Version:** 1.0  
**Last Updated:** October 6, 2025  
**Status:** ✅ Complete and Ready for Testing
