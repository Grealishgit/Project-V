# Product Dashboard Admin System

A comprehensive admin dashboard for managing products with MySQL database integration. Built with PHP, HTML, CSS, and JavaScript.

## Features

- **Dashboard Overview**: View statistics including total products, average price, and categories
- **Product Management**: Full CRUD operations (Create, Read, Update, Delete)
- **Image Upload**: Support for product images with automatic file handling
- **Responsive Design**: Modern, mobile-friendly interface
- **Search & Filter**: Search products by name, category, or description
- **Export Functionality**: Export product data to CSV format
- **Clean Architecture**: Well-structured file organization

## File Structure

```
Project-V/
├── index.html              # Main dashboard interface
├── setup.php              # Database setup script
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── main.js        # JavaScript functionality
├── config/
│   └── database.php       # Database configuration
├── classes/
│   └── Product.php        # Product model class
├── api/
│   ├── products.php       # Product API endpoints
│   ├── dashboard.php      # Dashboard statistics API
│   └── export.php         # CSV export functionality
├── database/
│   └── schema.sql         # Database schema and sample data
└── uploads/               # Product images directory (auto-created)
```

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server

### Setup Instructions

1. **Clone or download the project** to your web server directory

2. **Database Setup**:
   - Start your MySQL server
   - Run the setup script:
     ```bash
     php setup.php
     ```
   - This will create the database, tables, and insert sample data

3. **Configure Database** (if needed):
   - Edit `config/database.php` to match your MySQL credentials
   - Default settings:
     - Host: localhost
     - Database: product_dashboard
     - Username: root
     - Password: (empty)

4. **Start the Web Server**:
   - For PHP built-in server:
     ```bash
     php -S localhost:8000
     ```
   - Or use your preferred web server (Apache/Nginx)

5. **Access the Dashboard**:
   - Open your browser and navigate to `http://localhost:8000` (or your server URL)

## Usage

### Dashboard
- View key statistics about your products
- Quick overview of total products, average price, and categories

### Product Management
- **Add Products**: Click "Add Product" to create new products with images
- **Edit Products**: Click the edit button on any product to modify details
- **Delete Products**: Click the delete button to remove products (with confirmation)
- **View Products**: Browse all products in a responsive table format

### Features
- **Image Upload**: Supports JPEG, PNG, GIF, and WebP formats (max 5MB)
- **Form Validation**: Client-side and server-side validation
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Export Data**: Export product data to CSV format

## API Endpoints

### Products API (`/api/products.php`)
- `GET /api/products.php` - Get all products
- `GET /api/products.php?id=1` - Get specific product
- `GET /api/products.php?search=query` - Search products
- `POST /api/products.php` - Create new product
- `POST /api/products.php` (with action=update) - Update product
- `DELETE /api/products.php` - Delete product

### Dashboard API (`/api/dashboard.php`)
- `GET /api/dashboard.php` - Get dashboard statistics

### Export API (`/api/export.php`)
- `GET /api/export.php` - Export products to CSV

## Database Schema

### Products Table
- `id` - Primary key
- `name` - Product name
- `category` - Product category
- `price` - Product price (decimal)
- `stock` - Stock quantity
- `description` - Product description
- `image` - Image filename
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Security Features

- SQL injection prevention using prepared statements
- File upload validation (type and size)
- Input sanitization and validation
- Error handling and logging

## Customization

### Adding New Categories
Edit the category options in `index.php` and update the database as needed.

### Styling
Modify `assets/css/style.css` to customize the appearance.

### Functionality
Extend `assets/js/main.js` and the PHP classes to add new features.

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check MySQL server is running
   - Verify database credentials in `config/database.php`
   - Ensure the database exists (run `setup.php`)

2. **Image Upload Issues**:
   - Check if `uploads/` directory exists and is writable
   - Verify file permissions
   - Check file size and type restrictions

3. **API Errors**:
   - Check browser console for JavaScript errors
   - Verify API endpoints are accessible
   - Check PHP error logs

### File Permissions
Ensure the following directories are writable:
- `uploads/` (for image uploads)
- PHP session directory (if using sessions)

## Contributing

Feel free to submit issues and enhancement requests. To contribute:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions, please open an issue in the project repository.
