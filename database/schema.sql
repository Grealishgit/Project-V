-- Create database
CREATE DATABASE IF NOT EXISTS product_dashboard;
USE product_dashboard;

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_name (name),
    INDEX idx_price (price),
    INDEX idx_stock (stock)
);

-- Insert sample data
INSERT INTO products (name, category, price, stock, description) VALUES
('iPhone 14 Pro', 'Electronics', 999.99, 50, 'Latest iPhone with advanced camera system'),
('Samsung Galaxy S23', 'Electronics', 849.99, 30, 'Flagship Android smartphone'),
('MacBook Pro 16"', 'Electronics', 2499.99, 15, 'Professional laptop for creative professionals'),
('Nike Air Max 90', 'Clothing', 129.99, 100, 'Classic running shoes with Air Max technology'),
('Adidas Ultraboost 22', 'Clothing', 189.99, 75, 'High-performance running shoes'),
('The Great Gatsby', 'Books', 12.99, 200, 'Classic American novel by F. Scott Fitzgerald'),
('To Kill a Mockingbird', 'Books', 14.99, 150, 'Pulitzer Prize-winning novel'),
('Coffee Table', 'Home', 299.99, 25, 'Modern wooden coffee table'),
('Dining Chair Set', 'Home', 459.99, 20, 'Set of 4 comfortable dining chairs'),
('Tennis Racket', 'Sports', 89.99, 40, 'Professional tennis racket for intermediate players'),
('Basketball', 'Sports', 29.99, 60, 'Official size basketball for indoor/outdoor play'),
('Yoga Mat', 'Sports', 39.99, 80, 'Non-slip yoga mat for home workouts');

-- Create admin users table (optional for future use)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Create categories table (optional for future use)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and gadgets'),
('Clothing', 'Apparel and accessories'),
('Books', 'Books and literature'),
('Home', 'Home and furniture items'),
('Sports', 'Sports and fitness equipment');

-- Create product views table for analytics (optional)
CREATE TABLE IF NOT EXISTS product_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    view_date DATE NOT NULL,
    view_count INT DEFAULT 1,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_date (product_id, view_date)
);

-- Create indexes for better performance
CREATE INDEX idx_products_created_at ON products(created_at);
CREATE INDEX idx_products_updated_at ON products(updated_at);
CREATE INDEX idx_product_views_date ON product_views(view_date);
CREATE INDEX idx_product_views_product ON product_views(product_id);
