<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit();
}

// User is logged in - role will be handled by JavaScript
// Session contains: role, admin_id, username, full_name, email
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Product Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>
</head>

<body>
    <!-- Loading Overlay -->
    <div id="app-loader" class="app-loader">
        <div class="loader-spinner"></div>
    </div>

    <!-- Top Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2><i class="fas fa-chart-line"></i> Admin Dashboard</h2>
        </div>
        <div class="navbar-content">
            <div class="navbar-search">
                <input type="text" placeholder="Search..." class="navbar-search-input">
                <button class="navbar-search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <div class="navbar-actions">
                <button class="navbar-btn" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>
                <!-- <button class="navbar-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button> -->
                <div class="navbar-user" onclick="openAdminModal()">
                    <img src="https://avatar.iran.liara.run/public/18" alt="User Avatar" class="user-avatar">
                    <span class="user-name">Admin</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Fixed Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-chart-line"></i> Dashboard</h3>
            </div>
            <ul class="sidebar-menu">
                <li class="active">
                    <a href="#dashboard" onclick="showSection('dashboard')">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#products" onclick="showSection('products')">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li>
                    <a href="#analytics" onclick="showSection('analytics')">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="#add-product" onclick="showSection('add-product')">
                        <i class="fas fa-plus"></i>
                        <span>Add Product</span>
                    </a>
                </li>
                <li>
                    <a href="#admin-orders" onclick="showSection('admin-orders')">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li>
                    <a href="#order-items" onclick="showSection('order-items')">
                        <i class="fas fa-boxes"></i>
                        <span>Order Items</span>
                    </a>
                </li>
                <li>
                    <a href="#customers" onclick="showSection('customers')">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li>
                    <a href="#backup" onclick="showSection('backup')">
                        <i class="fas fa-database"></i>
                        <span>Backup</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <img src="https://avatar.iran.liara.run/public/18" alt="User" class="sidebar-user-avatar">
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name">Admin</div>
                        <!-- <div class="sidebar-user-role">Administrator</div> -->
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Content Header -->
            <div class="content-header">
                <h1 id="page-title">Product Management System</h1>
                <div class="content-actions">
                    <button class="btn btn-primary" onclick="refreshCurrentSection()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <div class="dashboard-title">
                        <h1>Dashboard Overview</h1>
                        <p class="dashboard-subtitle">Welcome back! Here's what's happening with your inventory today.
                        </p>
                    </div>
                    <div class="dashboard-actions">
                        <button class="btn btn-primary" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-secondary" onclick="exportData()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Key Performance Indicators -->
                <div class="kpi-section">
                    <h2 class="section-title">Key Performance Indicators</h2>
                    <div class="kpi-grid">
                        <div class="kpi-card primary">
                            <div class="kpi-header">
                                <div class="kpi-icon">
                                    <i class="fas fa-cubes"></i>
                                </div>
                                <div class="kpi-trend positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+5.2%</span>
                                </div>
                            </div>
                            <div class="kpi-content">
                                <h3 id="total-products">0</h3>
                                <p>Total Products</p>
                                <div class="kpi-progress">
                                    <div class="progress-bar" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="kpi-card success">
                            <div class="kpi-header">
                                <div class="kpi-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="kpi-trend positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+12.8%</span>
                                </div>
                            </div>
                            <div class="kpi-content">
                                <h3 id="inventory-value">KSh 0</h3>
                                <p>Total Inventory Value</p>
                                <div class="kpi-progress">
                                    <div class="progress-bar" style="width: 88%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="kpi-card warning">
                            <div class="kpi-header">
                                <div class="kpi-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="kpi-trend negative">
                                    <i class="fas fa-arrow-down"></i>
                                    <span>-2.1%</span>
                                </div>
                            </div>
                            <div class="kpi-content">
                                <h3 id="low-stock-count">0</h3>
                                <p>Low Stock Items</p>
                                <div class="kpi-progress">
                                    <div class="progress-bar" style="width: 25%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="kpi-card info">
                            <div class="kpi-header">
                                <div class="kpi-icon">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div class="kpi-trend neutral">
                                    <i class="fas fa-minus"></i>
                                    <span>0%</span>
                                </div>
                            </div>
                            <div class="kpi-content">
                                <h3 id="categories">0</h3>
                                <p>Product Categories</p>
                                <div class="kpi-progress">
                                    <div class="progress-bar" style="width: 60%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="kpi-card secondary">
                            <div class="kpi-header">
                                <div class="kpi-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="kpi-trend positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+3.7%</span>
                                </div>
                            </div>
                            <div class="kpi-content">
                                <h3 id="avg-price">KSh 0</h3>
                                <p>Average Product Price</p>
                                <div class="kpi-progress">
                                    <div class="progress-bar" style="width: 65%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="kpi-card accent">
                            <div class="kpi-header">
                                <div class="kpi-icon">
                                    <i class="fas fa-warehouse"></i>
                                </div>
                                <div class="kpi-trend positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+8.4%</span>
                                </div>
                            </div>
                            <div class="kpi-content">
                                <h3 id="total-stock">0</h3>
                                <p>Total Stock Units</p>
                                <div class="kpi-progress">
                                    <div class="progress-bar" style="width: 82%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Dashboard -->
                <div class="analytics-dashboard">
                    <div class="analytics-row">
                        <!-- Stock Analysis Card -->
                        <div class="analytics-card stock-analysis">
                            <div class="card-header">
                                <h3>Stock Analysis</h3>
                                <div class="card-actions">
                                    <button class="card-action-btn" onclick="showLowStockDetails()">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="stock-metrics">
                                    <div class="stock-metric in-stock">
                                        <div class="metric-icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="metric-info">
                                            <span class="metric-value" id="dashboard-in-stock">0</span>
                                            <span class="metric-label">In Stock</span>
                                        </div>
                                    </div>
                                    <div class="stock-metric low-stock">
                                        <div class="metric-icon">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </div>
                                        <div class="metric-info">
                                            <span class="metric-value" id="dashboard-low-stock">0</span>
                                            <span class="metric-label">Low Stock</span>
                                        </div>
                                    </div>
                                    <div class="stock-metric out-of-stock">
                                        <div class="metric-icon">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                        <div class="metric-info">
                                            <span class="metric-value" id="dashboard-out-stock">0</span>
                                            <span class="metric-label">Out of Stock</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="stock-chart">
                                    <canvas id="stockChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Category Breakdown Card -->
                        <div class="analytics-card category-breakdown">
                            <div class="card-header">
                                <h3>Category Distribution</h3>
                                <div class="card-actions">
                                    <button class="card-action-btn">
                                        <i class="fas fa-chart-pie"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="category-chart">
                                    <canvas id="categoryChart" width="300" height="300"></canvas>
                                </div>

                            </div>
                        </div>

                        <!-- Revenue Analysis Card -->
                        <div class="analytics-card revenue-analysis">
                            <div class="card-header">
                                <h3>Revenue Analysis</h3>
                                <div class="card-actions">
                                    <button class="card-action-btn">
                                        <i class="fas fa-chart-bar"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-content">
                                <!-- <div class="revenue-metrics">
                                    <div class="revenue-item">
                                        <span class="revenue-label">Today's Potential</span>
                                        <span class="revenue-value" id="today-potential">KSh 0</span>
                                    </div>
                                    <div class="revenue-item">
                                        <span class="revenue-label">Week's Potential</span>
                                        <span class="revenue-value" id="week-potential">KSh 0</span>
                                    </div>
                                    <div class="revenue-item">
                                        <span class="revenue-label">Month's Potential</span>
                                        <span class="revenue-value" id="month-potential">KSh 0</span>
                                    </div>
                                </div> -->
                                <div id="dashboard-category-breakdown" class="category-list">

                                </div>
                                <!-- <div class="price-distribution">
                                    <div class="distribution-item">
                                        <span class="distribution-label">Under KSh 1,000</span>
                                        <span class="distribution-badge low" id="dashboard-price-under-1k">0</span>
                                    </div>
                                    <div class="distribution-item">
                                        <span class="distribution-label">KSh 1,000 - 5,000</span>
                                        <span class="distribution-badge medium" id="dashboard-price-1k-5k">0</span>
                                    </div>
                                    <div class="distribution-item">
                                        <span class="distribution-label">Above KSh 5,000</span>
                                        <span class="distribution-badge high" id="dashboard-price-over-5k">0</span>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Panel -->
                <div class="quick-actions-panel">
                    <h2 class="section-title">Quick Actions</h2>
                    <div class="quick-actions-grid">
                        <div class="quick-action-card" onclick="showSection('add-product')">
                            <div class="action-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="action-content">
                                <h4>Add Product</h4>
                                <p>Create a new product</p>
                            </div>
                        </div>
                        <div class="quick-action-card" onclick="showSection('products')">
                            <div class="action-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="action-content">
                                <h4>View Products</h4>
                                <p>Manage inventory</p>
                            </div>
                        </div>
                        <div class="quick-action-card" onclick="showSection('analytics')">
                            <div class="action-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="action-content">
                                <h4>Analytics</h4>
                                <p>View detailed reports</p>
                            </div>
                        </div>
                        <div class="quick-action-card" onclick="exportData()">
                            <div class="action-icon">
                                <i class="fas fa-download"></i>
                            </div>
                            <div class="action-content">
                                <h4>Export Data</h4>
                                <p>Download reports</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Recent Activity & Low Stock Alerts -->
                <div class="dashboard-footer">
                    <div class="dashboard-row">
                        <!-- Recent Products -->
                        <div class="dashboard-widget recent-products">
                            <div class="widget-header">
                                <h3>Recent Products</h3>
                                <button class="widget-action" onclick="showSection('products')">
                                    View All <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                            <div class="widget-content">
                                <div class="table-container">
                                    <table id="dashboard-products-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dashboard-products-tbody">
                                            <!-- Products will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Low Stock Alerts -->
                        <div class="dashboard-widget low-stock-alerts">
                            <div class="widget-header">
                                <h3>Low Stock Alerts</h3>
                                <span class="alert-badge" id="alert-count">0</span>
                            </div>
                            <div class="widget-content">
                                <div class="alert-list" id="dashboard-low-stock-tbody">
                                    <!-- Low stock alerts will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Products Section -->
            <section id="products" class="content-section">
                <div class="section-header">
                    <h2>Products Management</h2>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="showSection('analytics')">
                            <i class="fas fa-chart-bar"></i> Analytics
                        </button>
                        <button class="btn btn-primary" onclick="showSection('add-product')">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        <button class="btn btn-success" onclick="exportProductsCSV()" title="Export products to CSV">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                    </div>
                </div>

                <!-- Products KPI Dashboard -->
                <div class="products-kpi-grid">
                    <div class="kpi-card fade-in-scale interactive-hover">
                        <div class="kpi-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="products-total-count">0</div>
                            <div class="kpi-label">Total Products</div>
                            <div class="kpi-trend">
                                <span class="trend-indicator positive" id="products-trend">
                                    <i class="fas fa-arrow-up"></i> +0%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card fade-in-scale interactive-hover">
                        <div class="kpi-icon inventory">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="products-total-stock">0</div>
                            <div class="kpi-label">Total Stock Units</div>
                            <div class="kpi-trend">
                                <span class="trend-indicator" id="stock-trend">
                                    <i class="fas fa-minus"></i> 0%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card fade-in-scale interactive-hover">
                        <div class="kpi-icon value">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="products-inventory-value">KSh 0</div>
                            <div class="kpi-label">Inventory Value</div>
                            <div class="kpi-trend">
                                <span class="trend-indicator positive" id="value-trend">
                                    <i class="fas fa-arrow-up"></i> +0%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card fade-in-scale interactive-hover">
                        <div class="kpi-icon price">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="products-avg-price">KSh 0</div>
                            <div class="kpi-label">Average Price</div>
                            <div class="kpi-trend">
                                <span class="trend-indicator" id="price-trend">
                                    <i class="fas fa-minus"></i> 0%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card fade-in-scale interactive-hover">
                        <div class="kpi-icon categories">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="products-categories-count">0</div>
                            <div class="kpi-label">Categories</div>
                            <div class="kpi-trend">
                                <span class="trend-indicator neutral" id="categories-trend">
                                    <i class="fas fa-equals"></i> 0%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card fade-in-scale interactive-hover alert-card">
                        <div class="kpi-icon alert">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value" id="products-low-stock-count">0</div>
                            <div class="kpi-label">Low Stock Items</div>
                            <div class="kpi-trend">
                                <span class="trend-indicator warning" id="low-stock-trend">
                                    <i class="fas fa-exclamation"></i> Alert
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Search & Filters -->
                <div class="search-filters">
                    <div class="search-row">
                        <div class="search-input-group">
                            <input type="text" id="search-input" placeholder="Search products..."
                                onkeyup="performSearch()">
                            <i class="fas fa-search"></i>
                        </div>
                        <select id="category-filter" onchange="performSearch()">
                            <option value="">All Categories</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Books">Books</option>
                            <option value="Home">Home</option>
                            <option value="Sports">Sports</option>
                        </select>
                        <button class="btn btn-secondary" onclick="toggleAdvancedFilters()">
                            <i class="fas fa-filter"></i> Filters
                        </button>
                    </div>

                    <div id="advanced-filters" class="advanced-filters" style="display: none;">
                        <div class="filter-row">
                            <div class="price-range">
                                <label>Price Range (KSh):</label>
                                <input type="number" id="min-price" placeholder="Min" onchange="performSearch()">
                                <span>to</span>
                                <input type="number" id="max-price" placeholder="Max" onchange="performSearch()">
                            </div>
                            <div class="stock-filters">
                                <label>
                                    <input type="checkbox" id="in-stock-only" onchange="performSearch()">
                                    In Stock Only
                                </label>
                                <label>
                                    <input type="checkbox" id="low-stock-only" onchange="performSearch()">
                                    Low Stock Alert
                                </label>
                            </div>
                            <div class="sort-options">
                                <select id="sort-by" onchange="performSearch()">
                                    <option value="created_at">Date Added</option>
                                    <option value="name">Name</option>
                                    <option value="price">Price</option>
                                    <option value="stock">Stock</option>
                                    <option value="category">Category</option>
                                </select>
                                <select id="sort-order" onchange="performSearch()">
                                    <option value="DESC">Descending</option>
                                    <option value="ASC">Ascending</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
                            <button class="btn btn-primary" onclick="exportFilteredResults()">Export Results</button>
                        </div>
                    </div>
                </div>

                <!-- Search Stats -->
                <div id="search-stats" class="search-stats" style="display: none;"></div>

                <div class="table-container">
                    <table id="products-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price (KSh)</th>
                                <th>Stock</th>
                                <th>Inventory Value</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody">
                            <!-- Products will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Enhanced Pagination -->
                <div class="products-pagination-container">
                    <div class="pagination-info-card">
                        <div class="pagination-stats">
                            <span class="results-count">
                                Showing <span id="products-showing-start">0</span>-<span
                                    id="products-showing-end">0</span>
                                of <span id="products-total-results">0</span> products
                            </span>
                            <div class="page-size-selector">
                                <label>Show:</label>
                                <select id="products-per-page" onchange="changeProductsPerPage()">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span>per page</span>
                            </div>
                        </div>
                    </div>

                    <div class="pagination-controls">
                        <button id="products-first-page" class="pagination-btn" onclick="goToFirstPage()" disabled>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button id="products-prev-page" class="pagination-btn" onclick="changePage(-1)" disabled>
                            <i class="fas fa-angle-left"></i> Previous
                        </button>

                        <div class="page-numbers" id="products-page-numbers">
                            <!-- Page numbers will be generated here -->
                        </div>

                        <button id="products-next-page" class="pagination-btn" onclick="changePage(1)" disabled>
                            Next <i class="fas fa-angle-right"></i>
                        </button>
                        <button id="products-last-page" class="pagination-btn" onclick="goToLastPage()" disabled>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>

                    <div class="page-jump">
                        <label>Go to page:</label>
                        <input type="number" id="page-jump-input" min="1" onkeypress="handlePageJump(event)">
                        <button class="btn btn-sm" onclick="jumpToPage()">Go</button>
                    </div>
                </div>
            </section>

            <!-- Analytics Section -->
            <section id="analytics" class="content-section">
                <div class="section-header">
                    <h2>Analytics Dashboard</h2>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="refreshAnalytics()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-outline" onclick="exportAnalytics()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Key Performance Indicators -->
                <div class="kpi-grid1">
                    <div class="kpi-card fade-in-scale">
                        <div class="kpi-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value counter" id="analytics-total-products">0</div>
                            <div class="kpi-label">Total Products</div>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span id="products-trend">+5%</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card fade-in-scale">
                        <div class="kpi-icon inventory">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value counter" id="analytics-inventory-value">KSh 0</div>
                            <div class="kpi-label">Inventory Value</div>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span id="inventory-trend">+12%</span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card fade-in-scale">
                        <div class="kpi-icon price">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value counter" id="analytics-avg-price">KSh 0</div>
                            <div class="kpi-label">Average Price</div>
                            <div class="kpi-trend neutral">
                                <i class="fas fa-minus"></i>
                                <span id="price-trend">0%</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card fade-in-scale">
                        <div class="kpi-icon stock">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-value counter" id="analytics-low-stock">0</div>
                            <div class="kpi-label">Low Stock Items</div>
                            <div class="kpi-trend negative">
                                <i class="fas fa-arrow-down"></i>
                                <span id="stock-trend">-3%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Analytics Grid -->
                <div class="analytics-grid-modern">
                    <!-- Stock Overview Chart -->
                    <div class="analytics-card-modern chart-card fade-in-scale interactive-hover">
                        <div class="card-header-modern">
                            <h3>Stock Overview</h3>
                            <div class="chart-actions">
                                <button class="chart-btn btn-micro" onclick="toggleChartType('stock-chart', 'doughnut')"
                                    title="Donut Chart">
                                    <i class="fas fa-chart-pie"></i>
                                </button>
                                <button class="chart-btn btn-micro" onclick="toggleChartType('stock-chart', 'bar')"
                                    title="Bar Chart">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="stock-chart"></canvas>
                            <div class="chart-loading" id="stock-chart-loading" style="display: none;">
                                <div class="loading-spinner"></div>
                                <span style="margin-left: 12px;">Loading chart...</span>
                            </div>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <span class="legend-color in-stock"></span>
                                <span class="legend-label">In Stock</span>
                                <span class="legend-value" id="analytics-in-stock">0</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color low-stock"></span>
                                <span class="legend-label">Low Stock</span>
                                <span class="legend-value" id="legend-low-stock">0</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color out-stock"></span>
                                <span class="legend-label">Out of Stock</span>
                                <span class="legend-value" id="legend-out-stock">0</span>
                            </div>
                        </div>
                    </div>
                    <!-- Price Distribution Chart -->
                    <div class="analytics-card-modern chart-card fade-in-scale interactive-hover">
                        <div class="card-header-modern">
                            <h3>Price Distribution</h3>
                            <div class="card-subtitle">Product pricing breakdown</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="price-chart"></canvas>
                            <div class="chart-loading" id="price-chart-loading" style="display: none;">
                                <div class="loading-spinner"></div>
                                <span style="margin-left: 12px;">Loading chart...</span>
                            </div>
                        </div>
                        <div class="price-stats">
                            <div class="price-stat-item">
                                <span class="price-range">Under KSh 1K</span>
                                <span class="price-count" id="price-under-1k">0</span>
                            </div>
                            <div class="price-stat-item">
                                <span class="price-range">KSh 1K - 5K</span>
                                <span class="price-count" id="price-1k-5k">0</span>
                            </div>
                            <div class="price-stat-item">
                                <span class="price-range">KSh 5K - 10K</span>
                                <span class="price-count" id="price-5k-10k">0</span>
                            </div>
                            <div class="price-stat-item">
                                <span class="price-range">Over KSh 10K</span>
                                <span class="price-count" id="price-over-10k">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Category Performance -->
                    <div class="analytics-card-modern fade-in-scale interactive-hover">
                        <div class="card-header-modern">
                            <h3>Category Performance</h3>
                            <div class="card-subtitle">Top performing categories</div>
                        </div>
                        <div class="category-performance">
                            <div id="category-breakdown" class="category-list-modern">
                                <!-- Category data will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Trends -->
                    <div class="analytics-card-modern chart-card">
                        <div class="card-header-modern">
                            <h3>Inventory Trends</h3>
                            <div class="card-subtitle">Last 30 days</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="trend-chart"></canvas>
                            <div class="chart-loading" id="trend-chart-loading" style="display: none;">
                                <div class="loading-spinner"></div>
                                <span style="margin-left: 12px;">Loading trends...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Critical Alerts Section -->
                <div class="alerts-section">
                    <div class="section-title">
                        <h3>Critical Alerts</h3>
                        <span class="alert-count" id="alert-count">0</span>
                    </div>

                    <div class="alerts-grid">
                        <!-- Low Stock Alert Card -->
                        <div class="alert-card low-stock-alert fade-in-scale interactive-hover">
                            <div class="alert-header">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="alert-title">
                                    <h4>Low Stock Products</h4>
                                    <span class="alert-subtitle">Products running low</span>
                                </div>
                                <div class="alert-badge" id="low-stock-count">0</div>
                            </div>
                            <div class="alert-content">
                                <div class="low-stock-list" id="low-stock-preview">
                                    <!-- Low stock items will be loaded here -->
                                </div>
                            </div>
                            <div class="alert-actions">
                                <button class="btn-alert primary btn-micro" onclick="showLowStockDetails()">
                                    View All <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Price Anomalies Alert -->
                        <div class="alert-card price-alert fade-in-scale interactive-hover">
                            <div class="alert-header">
                                <div class="alert-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="alert-title">
                                    <h4>Price Insights</h4>
                                    <span class="alert-subtitle">Pricing recommendations</span>
                                </div>
                                <div class="alert-badge info" id="price-insights-count">0</div>
                            </div>
                            <div class="alert-content">
                                <div class="insight-list" id="price-insights">
                                    <!-- Price insights will be loaded here -->
                                </div>
                            </div>
                        </div>
                        <!-- Category Insights Alert -->
                        <div class="alert-card category-alert fade-in-scale interactive-hover">
                            <div class="alert-header">
                                <div class="alert-icon">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div class="alert-title">
                                    <h4>Category Insights</h4>
                                    <span class="alert-subtitle">Performance analysis</span>
                                </div>
                                <div class="alert-badge success" id="category-insights-count">0</div>
                            </div>
                            <div class="alert-content">
                                <div class="insight-list" id="category-insights">
                                    <!-- Category insights will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Low Stock Table -->
                <div class="low-stock-section-modern" style="display: none;" id="low-stock-detailed">
                    <div class="section-title">
                        <h3>Low Stock Details</h3>
                        <button class="btn btn-secondary" onclick="hideDetailedView()">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                    <div class="table-container-modern">
                        <table id="low-stock-table" class="modern-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Value</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="low-stock-tbody">
                                <!-- Low stock products will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="recent-activities">
                    <h3>Recent Activities</h3>
                    <div id="recent-activities-list" class="activities-list">
                        <!-- Recent activities will be loaded here -->
                    </div>
                </div>
            </section>

            <!-- Add Product Section -->
            <section id="add-product" class="content-section">
                <div class="section-header">
                    <h2>Add New Product</h2>
                    <p class="section-subtitle">Create a new product and see a live preview</p>
                </div>
                <div class="add-product-container">
                    <!-- Form Section (Left) -->
                    <div class="product-form-section">
                        <div class="form-container">
                            <h3 class="form-title">Product Details</h3>
                            <form id="product-form" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="product-name">Product Name</label>
                                    <input type="text" id="product-name" name="name" required
                                        placeholder="Enter product name">
                                </div>
                                <div class="form-group">
                                    <label for="product-category">Category</label>
                                    <select id="product-category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Electronics">Electronics</option>
                                        <option value="Clothing">Clothing</option>
                                        <option value="Books">Books</option>
                                        <option value="Home">Home</option>
                                        <option value="Sports">Sports</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="product-price">Price (KSh)</label>
                                        <input type="number" id="product-price" name="price" step="0.01" required
                                            placeholder="0.00">
                                    </div>
                                    <div class="form-group">
                                        <label for="product-stock">Stock Quantity</label>
                                        <input type="number" id="product-stock" name="stock" required placeholder="0">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="product-description">Description</label>
                                    <textarea id="product-description" name="description" rows="4"
                                        placeholder="Enter product description..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="product-image">Product Image</label>
                                    <div class="file-input-wrapper">
                                        <input type="file" id="product-image" name="image" accept="image/*">
                                        <label for="product-image" class="file-input-label">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Choose Image</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Product
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                        <i class="fas fa-times"></i> Clear Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Preview Section (Right) -->
                    <div class="product-preview-section">
                        <div class="preview-container">
                            <h3 class="preview-title">Live Preview</h3>
                            <div class="product-preview-card">
                                <div class="preview-image-container">
                                    <div id="preview-image" class="preview-image">
                                        <i class="fas fa-image"></i>
                                        <span>No image selected</span>
                                    </div>
                                </div>
                                <div class="preview-content">
                                    <div class="preview-header">
                                        <h4 id="preview-name" class="preview-product-name">Product Name</h4>
                                        <span id="preview-category" class="preview-category">Category</span>
                                    </div>
                                    <div class="preview-price-section">
                                        <span id="preview-price" class="preview-price">KSh 0.00</span>
                                        <div class="preview-stock">
                                            <i class="fas fa-box"></i>
                                            <span id="preview-stock">0 in stock</span>
                                        </div>
                                    </div>
                                    <div class="preview-description">
                                        <p id="preview-description">Product description will appear here...</p>
                                    </div>
                                    <div class="preview-badges">
                                        <span id="preview-stock-badge" class="stock-badge">Out of Stock</span>
                                        <span id="preview-value-badge" class="value-badge">KSh 0.00 Total Value</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview Stats -->
                            <div class="preview-stats">
                                <div class="preview-stat">
                                    <i class="fas fa-tag"></i>
                                    <span>Price per unit</span>
                                    <strong id="preview-unit-price">KSh 0.00</strong>
                                </div>
                                <div class="preview-stat">
                                    <i class="fas fa-warehouse"></i>
                                    <span>Total inventory value</span>
                                    <strong id="preview-total-value">KSh 0.00</strong>
                                </div>
                                <div class="preview-stat">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Stock status</span>
                                    <strong id="preview-status">Not set</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Customer: View Products Section -->
            <section id="view-products" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-box-open"></i> Browse Products</h2>
                </div>

                <!-- Search and Filter Bar -->
                <div class="customer-search-bar">
                    <div class="search-group">
                        <i class="fas fa-search"></i>
                        <input type="text" id="customer-search-input" placeholder="Search products..."
                            onkeyup="debounce(loadCustomerProducts, 300)()">
                    </div>
                    <select id="customer-category-filter" onchange="loadCustomerProducts()">
                        <option value="">All Categories</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Books">Books</option>
                        <option value="Home">Home</option>
                        <option value="Sports">Sports</option>
                    </select>
                    <select id="customer-sort-by" onchange="loadCustomerProducts()">
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="price_asc">Price (Low to High)</option>
                        <option value="price_desc">Price (High to Low)</option>
                        <option value="newest">Newest First</option>
                    </select>
                </div>

                <!-- Products Grid -->
                <div id="customer-products-grid" class="products-grid">
                    <!-- Products will be loaded here dynamically -->
                </div>

                <!-- Pagination -->
                <div id="customer-pagination" class="pagination">
                    <!-- Pagination will be loaded here -->
                </div>
            </section>

            <!-- Customer: Shopping Cart Section -->
            <section id="order-product" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
                </div>

                <div id="cart-container" class="cart-container">
                    <!-- Cart items will be loaded here dynamically -->
                </div>
            </section>

            <!-- Customer: View Orders Section -->
            <section id="view-orders" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-receipt"></i> My Orders</h2>
                </div>

                <div class="orders-list">
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No orders yet</h3>
                        <p>Your order history will appear here</p>
                        <button class="btn btn-primary" onclick="showSection('view-products')">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </button>
                    </div>
                </div>
            </section>

            <!-- Customer: My Profile Section -->
            <section id="my-profile" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-user"></i> My Profile</h2>
                </div>

                <div class="profile-container">
                    <div class="profile-card">
                        <div class="profile-header">
                            <img src="https://avatar.iran.liara.run/public/18" alt="Profile"
                                class="profile-avatar-large">
                            <h3 id="profile-name">Customer Name</h3>
                            <p id="profile-email">customer@example.com</p>
                        </div>
                        <div class="profile-details">
                            <div class="profile-detail-row">
                                <span><i class="fas fa-user"></i> Username:</span>
                                <strong id="profile-username">username</strong>
                            </div>
                            <div class="profile-detail-row">
                                <span><i class="fas fa-calendar"></i> Member Since:</span>
                                <strong id="profile-member-since">Jan 2025</strong>
                            </div>
                            <div class="profile-detail-row">
                                <span><i class="fas fa-shield-alt"></i> Account Status:</span>
                                <strong class="status-active">Active</strong>
                            </div>
                        </div>
                        <div class="profile-actions">
                            <button class="btn btn-primary" onclick="editProfile()">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                            <button class="btn btn-secondary" onclick="changePassword()">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Admin: Orders Management Section -->
            <section id="admin-orders" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-shopping-cart"></i> Orders Management</h2>
                    <p class="section-subtitle">Manage customer orders and payment approvals</p>
                </div>

                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="total-orders">0</h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="pending-orders">0</h3>
                            <p>Pending Payment</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="paid-orders">0</h3>
                            <p>Paid Orders</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="total-revenue">KSh 0</h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>All Orders</h3>
                        <div class="table-actions">
                            <select id="order-status-filter" onchange="loadAdminOrders()">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <select id="payment-status-filter" onchange="loadAdminOrders()">
                                <option value="">All Payments</option>
                                <option value="pending">Pending Payment</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                            <button class="btn btn-primary" onclick="loadAdminOrders()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <table id="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-tbody">
                            <tr>
                                <td colspan="8" class="text-center">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Admin: Order Items Section -->
            <section id="order-items" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-boxes"></i> Order Items</h2>
                    <p class="section-subtitle">View all items from customer orders</p>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>All Order Items</h3>
                        <div class="table-actions">
                            <input type="text" id="order-item-search" placeholder="Search by product or order..."
                                onkeyup="debounce(loadOrderItems, 300)()"
                                style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                            <button class="btn btn-primary" onclick="loadOrderItems()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <table id="order-items-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="order-items-tbody">
                            <tr>
                                <td colspan="7" class="text-center">Loading order items...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Admin: Customers Section -->
            <section id="customers" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Customers</h2>
                    <p class="section-subtitle">Manage customer accounts and information</p>
                </div>

                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="total-customers">0</h3>
                            <p>Total Customers</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="active-customers">0</h3>
                            <p>Active Customers</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="customers-with-orders">0</h3>
                            <p>With Orders</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="new-customers-month">0</h3>
                            <p>New This Month</p>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3>All Customers</h3>
                        <div class="table-actions">
                            <input type="text" id="customer-search" placeholder="Search customers..."
                                onkeyup="debounce(loadCustomers, 300)()"
                                style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                            <button class="btn btn-primary" onclick="loadCustomers()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <table id="customers-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="customers-tbody">
                            <tr>
                                <td colspan="8" class="text-center">Loading customers...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Admin: Backup Section -->
            <section id="backup" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-database"></i> Database Backup</h2>
                    <p class="section-subtitle">Backup and restore your database</p>
                </div>

                <div class="backup-container">
                    <div class="backup-card">
                        <div class="backup-card-header">
                            <i class="fas fa-download"></i>
                            <h3>Create Backup</h3>
                        </div>
                        <div class="backup-card-body">
                            <p>Create a complete backup of your database including all tables, data, and relationships.
                            </p>
                            <div class="backup-options">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="backup-products" checked>
                                    <span>Products</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="backup-orders" checked>
                                    <span>Orders</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="backup-customers" checked>
                                    <span>Customers</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="backup-analytics" checked>
                                    <span>Analytics</span>
                                </label>
                            </div>
                            <button class="btn btn-primary btn-large" onclick="createBackup()">
                                <i class="fas fa-download"></i> Create Backup
                            </button>
                        </div>
                    </div>

                    <div class="backup-card">
                        <div class="backup-card-header">
                            <i class="fas fa-upload"></i>
                            <h3>Restore Backup</h3>
                        </div>
                        <div class="backup-card-body">
                            <p>Restore your database from a previous backup file. This will overwrite current data.</p>
                            <div class="file-input-wrapper" style="margin: 20px 0;">
                                <input type="file" id="restore-file" accept=".sql">
                                <label for="restore-file" class="file-input-label">
                                    <i class="fas fa-file-upload"></i>
                                    <span>Choose Backup File</span>
                                </label>
                            </div>
                            <button class="btn btn-danger btn-large" onclick="restoreBackup()">
                                <i class="fas fa-upload"></i> Restore Backup
                            </button>
                        </div>
                    </div>

                    <div class="backup-card">
                        <div class="backup-card-header">
                            <i class="fas fa-history"></i>
                            <h3>Backup History</h3>
                        </div>
                        <div class="backup-card-body">
                            <div id="backup-history-list" class="backup-history-list">
                                <p class="text-center" style="color: #6c757d;">No backup history available</p>
                            </div>
                        </div>
                    </div>

                    <div class="backup-info-card">
                        <h4><i class="fas fa-info-circle"></i> Important Information</h4>
                        <ul>
                            <li><strong>Regular Backups:</strong> Create backups regularly to prevent data loss</li>
                            <li><strong>Secure Storage:</strong> Store backup files in a secure location</li>
                            <li><strong>Test Restores:</strong> Periodically test your backup files to ensure they work
                            </li>
                            <li><strong>Warning:</strong> Restoring a backup will overwrite all current database data
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal for editing products -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="edit-form">
                <input type="hidden" id="edit-id" name="id">
                <!-- Product Image Section - Prominent Top Section -->
                <div class="edit-image-section">
                    <div class="image-section-header">
                        <h4><i class="fas fa-image"></i> Product Image</h4>
                        <p class="image-section-subtitle">Current image and upload options</p>
                    </div>

                    <!-- Current/Preview Image Display -->
                    <div class="image-display-area">
                        <div class="current-image-container" id="edit-current-image">
                            <!-- Current image will be displayed here -->
                        </div>
                    </div>

                    <!-- Image Upload Controls -->
                    <div class="image-upload-controls">
                        <div class="file-input-container">
                            <input type="file" id="edit-image" name="image" accept="image/*" class="file-input">
                            <label for="edit-image" class="file-input-label edit-file-label">
                                <i class="fas fa-upload"></i>
                                <span>Choose New Image</span>
                            </label>
                        </div>
                        <div class="upload-help-text">
                            <small><i class="fas fa-info-circle"></i> Supported: JPG, PNG, GIF, WebP (Max 5MB)</small>
                        </div>
                    </div>
                </div>

                <!-- Product Details Section -->
                <div class="edit-details-section">
                    <div class="details-section-header">
                        <h4><i class="fas fa-edit"></i> Product Details</h4>
                    </div>
                    <div class="form-group">
                        <label for="edit-name">Product Name</label>
                        <input type="text" id="edit-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-category">Category</label>
                        <select id="edit-category" name="category" required>
                            <option value="Electronics">Electronics</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Books">Books</option>
                            <option value="Home">Home</option>
                            <option value="Sports">Sports</option>
                            <option value="Toys">Toys</option>
                            <option value="Beauty">Beauty</option>
                            <option value="Automotive">Automotive</option>
                            <option value="Garden">Garden</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-price">Price (KSh)</label>
                            <input type="number" id="edit-price" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-stock">Stock Quantity</label>
                            <input type="number" id="edit-stock" name="stock" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-description">Description</label>
                        <textarea id="edit-description" name="description" rows="4"></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin User Modal -->
    <div id="admin-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-shield"></i> Admin Profile</h3>
                <span class="modal-close" onclick="closeAdminModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="admin-info-loading" id="admin-info-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading admin information...</p>
                </div>
                <div class="admin-info-content" id="admin-info-content" style="display: none;">
                    <div class="admin-avatar-section">
                        <img id="admin-avatar" src="" alt="Admin Avatar" class="admin-modal-avatar">
                    </div>
                    <div class="admin-details">
                        <div class="admin-detail-item">
                            <label><i class="fas fa-id-badge"></i> Admin ID:</label>
                            <span id="admin-user-id"></span>
                        </div>
                        <div class="admin-detail-item"> <label><i class="fas fa-user"></i> Username:</label>
                            <span id="admin-username"></span>
                        </div>
                        <div class="admin-detail-item">
                            <label><i class="fas fa-envelope"></i> Email:</label>
                            <span id="admin-email"></span>
                        </div>
                        <!-- <div class="admin-detail-item">
                                                                                                                                    <label><i class="fas fa-user-tag"></i> Role:</label>
                                                                                                                                    <span id="admin-role"></span>
                                                                                                                                </div> -->
                        <div class="admin-detail-item">
                            <label><i class="fas fa-circle"></i> Status:</label>
                            <span id="admin-status" class="status-badge"></span>
                        </div>
                        <div class="admin-detail-item">
                            <label><i class="fas fa-calendar-plus"></i> Created:</label>
                            <span id="admin-created"></span>
                        </div>
                        <div class="admin-detail-item">
                            <label><i class="fas fa-calendar-edit"></i> Last Updated:</label>
                            <span id="admin-updated"></span>
                        </div>
                    </div>
                </div>
                <div class="admin-error" id="admin-error" style="display: none;">
                    <div class="error-message"> <i class="fas fa-exclamation-triangle"></i>
                        <span id="admin-error-text">Failed to load admin information.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="margin-bottom: 20px; padding: 10px;"> <button type="button"
                    class="btn btn-secondary" onclick="closeAdminModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>