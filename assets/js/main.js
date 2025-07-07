// Global variables
let currentEditId = null;
let currentPage = 1;
let currentFilters = {};
let sidebarOpen = true;
// Dashboard specific pagination variables
let dashboardCurrentPage = 1;
let dashboardFilters = {};
let dashboardProductsTotal = 0;
let dashboardProductsPerPage = 10;

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', function () {
    loadTheme();
    loadDashboardStats();
    performSearch(); // Use enhanced search for initial load
    initializeSidebar();

    // Initialize form submission handlers
    initializeEventListeners();
});

// Initialize sidebar functionality
function initializeSidebar() {
    // Close sidebar on mobile by default
    if (window.innerWidth <= 768) {
        sidebarOpen = false;
        document.getElementById('sidebar').classList.remove('show');
        document.querySelector('.main-content').classList.add('expanded');
    }

    // Handle window resize
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            // Desktop - show sidebar
            sidebarOpen = true;
            document.getElementById('sidebar').classList.remove('show');
            document.querySelector('.main-content').classList.remove('expanded');
            hideSidebarOverlay();
        } else {
            // Mobile - hide sidebar
            sidebarOpen = false;
            document.getElementById('sidebar').classList.remove('show');
            document.querySelector('.main-content').classList.add('expanded');
            hideSidebarOverlay();
        }
    });

    // Close sidebar when clicking on overlay
    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('sidebar-overlay')) {
            toggleSidebar();
        }
    });
}

// Toggle sidebar visibility
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    if (window.innerWidth <= 768) {
        // Mobile behavior
        sidebar.classList.toggle('show');
        if (sidebar.classList.contains('show')) {
            showSidebarOverlay();
        } else {
            hideSidebarOverlay();
        }
    } else {
        // Desktop behavior
        sidebarOpen = !sidebarOpen;
        if (sidebarOpen) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
        } else {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }
}

// Show sidebar overlay for mobile
function showSidebarOverlay() {
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    overlay.classList.add('show');
}

// Hide sidebar overlay
function hideSidebarOverlay() {
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        overlay.classList.remove('show');
    }
}

// Navigation functions
function showSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });

    // Remove active class from all menu items
    const menuItems = document.querySelectorAll('.sidebar-menu li');
    menuItems.forEach(item => {
        item.classList.remove('active');
    });

    // Show selected section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
    }

    // Add active class to corresponding menu item
    const activeMenuItem = document.querySelector(`[onclick="showSection('${sectionId}')"]`);
    if (activeMenuItem) {
        activeMenuItem.parentElement.classList.add('active');
    }

    // Close sidebar on mobile after navigation
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.remove('show');
        hideSidebarOverlay();
    }

    // Load data based on section
    if (sectionId === 'products') {
        performSearch(); // Use enhanced search instead of loadProducts
    } else if (sectionId === 'dashboard') {
        loadDashboardStats();
    } else if (sectionId === 'analytics') {
        loadAnalytics();
    }

    // Update page title
    updatePageTitle(sectionId);
}

// Load dashboard statistics
function loadDashboardStats() {
    // Load basic stats
    fetch('api/dashboard.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-products').textContent = data.stats.total_products;
                document.getElementById('avg-price').textContent = data.stats.avg_price;
                document.getElementById('categories').textContent = data.stats.total_categories;
                document.getElementById('inventory-value').textContent = formatKSh(data.stats.total_inventory_value);

                // Update analytics overview
                document.getElementById('dashboard-in-stock').textContent = data.stats.in_stock_products;
                document.getElementById('dashboard-low-stock').textContent = data.stats.low_stock_products;
                document.getElementById('dashboard-out-stock').textContent = data.stats.total_products - data.stats.in_stock_products;
            }
        })
        .catch(error => {
            console.error('Error loading dashboard stats:', error);
        });

    // Load analytics data
    fetch('api/analytics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardAnalytics(data.analytics);
            }
        })
        .catch(error => {
            console.error('Error loading analytics:', error);
        });

    // Load low stock products for dashboard
    loadDashboardLowStock();

    // Load dashboard products
    loadDashboardProducts();
}

function updateDashboardAnalytics(analytics) {
    // Update category breakdown
    if (analytics.categories) {
        updateDashboardCategoryBreakdown(analytics.categories);
    }

    // Update price distribution
    if (analytics.price_analysis) {
        updateDashboardPriceDistribution(analytics.price_analysis);
    }
}

function updateDashboardCategoryBreakdown(categories) {
    const container = document.getElementById('dashboard-category-breakdown');
    container.innerHTML = '';

    categories.slice(0, 5).forEach(category => {
        const categoryItem = document.createElement('div');
        categoryItem.className = 'category-item';
        categoryItem.innerHTML = `
            <div class="category-info">
                <span class="category-name">${category.category}</span>
                <span class="category-count">${category.product_count} products</span>
            </div>
            <div class="category-value">${formatKSh(category.category_value || 0)}</div>
        `;
        container.appendChild(categoryItem);
    });
}

function updateDashboardPriceDistribution(priceAnalysis) {
    // Calculate price distribution
    const under1k = priceAnalysis.under_1k || 0;
    const between1k5k = priceAnalysis.between_1k_5k || 0;
    const over5k = (priceAnalysis.between_5k_10k || 0) + (priceAnalysis.over_10k || 0);

    document.getElementById('dashboard-price-under-1k').textContent = under1k;
    document.getElementById('dashboard-price-1k-5k').textContent = between1k5k;
    document.getElementById('dashboard-price-over-5k').textContent = over5k;
}

function loadDashboardLowStock() {
    fetch('api/analytics.php?type=low-stock&threshold=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardLowStockTable(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading low stock data:', error);
        });
}

function updateDashboardLowStockTable(products) {
    const tbody = document.getElementById('dashboard-low-stock-tbody');
    tbody.innerHTML = '';

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">No low stock items</td></tr>';
        return;
    }

    products.slice(0, 5).forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.name}</td>
            <td><span class="category-badge">${product.category}</span></td>
            <td><span class="stock-badge ${product.stock <= 5 ? 'critical' : 'low'}">${product.stock}</span></td>
            <td>${formatKSh(product.price)}</td>
            <td>
                <button class="btn btn-sm btn-warning" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Dashboard Products Pagination Functions
function loadDashboardProducts() {
    const searchTerm = document.getElementById('dashboard-search')?.value || '';
    const category = document.getElementById('dashboard-category-filter')?.value || '';

    dashboardFilters = {
        search: searchTerm,
        category: category,
        limit: dashboardProductsPerPage,
        offset: (dashboardCurrentPage - 1) * dashboardProductsPerPage
    };

    // Build query string
    const params = new URLSearchParams();
    Object.keys(dashboardFilters).forEach(key => {
        if (dashboardFilters[key] !== '' && dashboardFilters[key] !== false) {
            params.append(key, dashboardFilters[key]);
        }
    });

    fetch(`api/search.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDashboardProducts(data.products);
                updateDashboardPagination(data.stats);
            } else {
                console.error('Error loading dashboard products:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading dashboard products:', error);
        });
}

function displayDashboardProducts(products) {
    const tbody = document.getElementById('dashboard-products-tbody');
    tbody.innerHTML = '';

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No products found</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.id}</td>
            <td>
                ${product.image ?
                `<img src="uploads/${product.image}" alt="${product.name}" class="product-image">` :
                '<div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">No Image</div>'
            }
            </td>
            <td>${product.name}</td>
            <td><span class="category-badge">${product.category}</span></td>
            <td>${product.formatted_price || formatKSh(product.price)}</td>
            <td><span class="stock-badge ${product.stock <= 10 ? 'low-stock' : ''}">${product.stock}</span></td>
            <td><span class="badge ${product.stock > 0 ? 'in-stock' : 'out-of-stock'}">${product.stock > 0 ? 'In Stock' : 'Out of Stock'}</span></td>
            <td>
                <button class="btn btn-sm btn-warning" onclick="editProduct(${product.id})" title="Edit Product">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})" title="Delete Product">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updateDashboardPagination(stats) {
    dashboardProductsTotal = stats.total_products || 0;
    const totalPages = Math.ceil(dashboardProductsTotal / dashboardProductsPerPage);

    // Update pagination info
    document.getElementById('dashboard-page-info').textContent = `Page ${dashboardCurrentPage} of ${totalPages}`;
    document.getElementById('dashboard-products-count').textContent = `(${dashboardProductsTotal} products)`;

    // Update button states
    document.getElementById('dashboard-prev-page').disabled = dashboardCurrentPage === 1;
    document.getElementById('dashboard-next-page').disabled = dashboardCurrentPage >= totalPages;
}

function changeDashboardPage(direction) {
    dashboardCurrentPage += direction;
    if (dashboardCurrentPage < 1) dashboardCurrentPage = 1;

    const totalPages = Math.ceil(dashboardProductsTotal / dashboardProductsPerPage);
    if (dashboardCurrentPage > totalPages) dashboardCurrentPage = totalPages;

    loadDashboardProducts();
}

function searchDashboardProducts() {
    dashboardCurrentPage = 1; // Reset to first page on search
    loadDashboardProducts();
}

function filterDashboardProducts() {
    dashboardCurrentPage = 1; // Reset to first page on filter
    loadDashboardProducts();
}

// Load products
function loadProducts() {
    fetch('api/products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProducts(data.products);
            } else {
                showMessage('Error loading products: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            showMessage('Error loading products', 'error');
        });
}

// Display products in table
function displayProducts(products) {
    const tbody = document.getElementById('products-tbody');
    tbody.innerHTML = '';

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No products found</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.id}</td>
            <td>
                ${product.image ?
                `<img src="uploads/${product.image}" alt="${product.name}" class="product-image">` :
                '<div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">No Image</div>'
            }
            </td>
            <td>${product.name}</td>
            <td>${product.category}</td>
            <td>${formatKSh(product.price)}</td>
            <td>${product.stock}</td>
            <td>${formatKSh(parseFloat(product.price) * parseInt(product.stock))}</td>
            <td>
                <button class="btn btn-warning" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger" onclick="deleteProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Handle product form submission
function handleProductSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');

    // Show loading state
    submitBtn.innerHTML = '<div class="loading"></div> Saving...';
    submitBtn.disabled = true;

    fetch('api/products.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Product added successfully!', 'success');
                resetForm();
                loadProducts();
                loadDashboardStats();
            } else {
                showMessage('Error adding product: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error adding product', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Product';
            submitBtn.disabled = false;
        });
}

// Edit product
function editProduct(id) {
    fetch(`api/products.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;

                // Populate edit form
                document.getElementById('edit-id').value = product.id;
                document.getElementById('edit-name').value = product.name;
                document.getElementById('edit-category').value = product.category;
                document.getElementById('edit-price').value = product.price;
                document.getElementById('edit-stock').value = product.stock;
                document.getElementById('edit-description').value = product.description;

                // Show modal
                document.getElementById('edit-modal').style.display = 'block';
                currentEditId = id;
            } else {
                showMessage('Error loading product: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error loading product', 'error');
        });
}

// Handle edit form submission
function handleEditSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append('action', 'update');

    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<div class="loading"></div> Updating...';
    submitBtn.disabled = true;

    fetch('api/products.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Product updated successfully!', 'success');
                closeModal();
                loadProducts();
                loadDashboardStats();
            } else {
                showMessage('Error updating product: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error updating product', 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = 'Update Product';
            submitBtn.disabled = false;
        });
}

// Delete product
function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch('api/products.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Product deleted successfully!', 'success');
                    loadProducts();
                    loadDashboardStats();
                } else {
                    showMessage('Error deleting product: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error deleting product', 'error');
            });
    }
}

// Close modal
function closeModal() {
    document.getElementById('edit-modal').style.display = 'none';
    currentEditId = null;
}

// Reset form
function resetForm() {
    document.getElementById('product-form').reset();
    showSection('products');
}

// Show message
function showMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());

    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.display = 'block';

    // Insert at the top of the active section
    const activeSection = document.querySelector('.content-section.active');
    activeSection.insertBefore(messageDiv, activeSection.firstChild);

    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
        messageDiv.remove();
    }, 5000);
}

// Enhanced search and filter functionality
function performSearch() {
    const searchTerm = document.getElementById('search-input').value;
    const category = document.getElementById('category-filter').value;
    const minPrice = document.getElementById('min-price').value;
    const maxPrice = document.getElementById('max-price').value;
    const inStockOnly = document.getElementById('in-stock-only').checked;
    const lowStockOnly = document.getElementById('low-stock-only').checked;
    const sortBy = document.getElementById('sort-by').value;
    const sortOrder = document.getElementById('sort-order').value;

    currentFilters = {
        search: searchTerm,
        category: category,
        min_price: minPrice,
        max_price: maxPrice,
        in_stock: inStockOnly,
        low_stock: lowStockOnly,
        sort_by: sortBy,
        sort_order: sortOrder,
        limit: 20,
        offset: (currentPage - 1) * 20
    };

    // Build query string
    const params = new URLSearchParams();
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key] !== '' && currentFilters[key] !== false) {
            params.append(key, currentFilters[key]);
        }
    });

    fetch(`api/search.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.products);
                updateSearchStats(data.stats);
            } else {
                showMessage('Search failed: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            showMessage('Search failed', 'error');
        });
}

function displaySearchResults(products) {
    const tbody = document.getElementById('products-tbody');
    tbody.innerHTML = '';

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No products found matching your criteria</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.id}</td>
            <td>
                ${product.image ?
                `<img src="uploads/${product.image}" alt="${product.name}" class="product-image">` :
                '<div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">No Image</div>'
            }
            </td>
            <td>${product.name}</td>
            <td>
                <span class="category-badge">${product.category}</span>
            </td>
            <td>${product.formatted_price}</td>
            <td>
                <span class="stock-badge ${product.stock <= 10 ? 'low-stock' : ''}">${product.stock}</span>
            </td>
            <td>${product.inventory_value}</td>
            <td>
                <button class="btn btn-warning" onclick="editProduct(${product.id})" title="Edit Product">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger" onclick="deleteProduct(${product.id})" title="Delete Product">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updateSearchStats(stats) {
    const statsDiv = document.getElementById('search-stats');
    if (stats.total_products > 0) {
        statsDiv.innerHTML = `
            <strong>Search Results:</strong> ${stats.total_products} products found | 
            Average Price: ${stats.avg_price} | 
            Price Range: ${stats.min_price} - ${stats.max_price} | 
            Total Stock: ${stats.total_stock} units
        `;
        statsDiv.style.display = 'block';
    } else {
        statsDiv.style.display = 'none';
    }
}

function toggleAdvancedFilters() {
    const filtersDiv = document.getElementById('advanced-filters');
    if (filtersDiv.style.display === 'none') {
        filtersDiv.style.display = 'block';
    } else {
        filtersDiv.style.display = 'none';
    }
}

function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    document.getElementById('in-stock-only').checked = false;
    document.getElementById('low-stock-only').checked = false;
    document.getElementById('sort-by').value = 'created_at';
    document.getElementById('sort-order').value = 'DESC';

    currentPage = 1;
    performSearch();
}

function exportFilteredResults() {
    const params = new URLSearchParams(currentFilters);
    params.append('export', 'true');

    const link = document.createElement('a');
    link.href = `api/export.php?${params.toString()}`;
    link.download = `products_export_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
}

// Analytics functionality
function loadAnalytics() {
    fetch('api/analytics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAnalyticsDisplay(data.analytics);
            } else {
                showMessage('Failed to load analytics: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Analytics error:', error);
            showMessage('Failed to load analytics', 'error');
        });
}

function updateAnalyticsDisplay(analytics) {
    // Update product stats
    if (analytics.products) {
        document.getElementById('analytics-total-products').textContent = analytics.products.total_products;
        document.getElementById('analytics-in-stock').textContent = analytics.products.in_stock_products;
        document.getElementById('analytics-low-stock').textContent = analytics.products.low_stock_products;
        document.getElementById('analytics-inventory-value').textContent = formatCompactKSh(analytics.products.total_inventory_value);
    }

    // Update price analysis
    if (analytics.price_analysis) {
        const priceAnalysis = analytics.price_analysis;
        document.getElementById('analytics-avg-price').textContent = formatKSh(priceAnalysis.avg_price);
        document.getElementById('analytics-price-range').textContent = `${formatKSh(priceAnalysis.min_price)} - ${formatKSh(priceAnalysis.max_price)}`;

        document.getElementById('price-under-1k').textContent = priceAnalysis.under_1k;
        document.getElementById('price-1k-5k').textContent = priceAnalysis.between_1k_5k;
        document.getElementById('price-5k-10k').textContent = priceAnalysis.between_5k_10k;
        document.getElementById('price-over-10k').textContent = priceAnalysis.over_10k;
    }

    // Update stock analysis
    if (analytics.stock_analysis) {
        const stockAnalysis = analytics.stock_analysis;
        document.getElementById('analytics-total-stock').textContent = stockAnalysis.total_stock;

        document.getElementById('stock-out').textContent = stockAnalysis.out_of_stock;
        document.getElementById('stock-low').textContent = stockAnalysis.low_stock;
        document.getElementById('stock-medium').textContent = stockAnalysis.medium_stock;
        document.getElementById('stock-high').textContent = stockAnalysis.high_stock;
    }

    // Update categories
    if (analytics.categories) {
        updateCategoryBreakdown(analytics.categories);
    }

    // Update recent activities
    if (analytics.recent_activities) {
        updateRecentActivities(analytics.recent_activities);
    }

    // Load low stock products
    loadLowStockProducts();

    // Update dashboard stats as well
    updateDashboardFromAnalytics(analytics);

    // Show analytics insights
    displayAnalyticsInsights(analytics);
}

// Update dashboard stats from analytics data
function updateDashboardFromAnalytics(analytics) {
    if (analytics.products) {
        const totalProducts = document.getElementById('total-products');
        const avgPrice = document.getElementById('avg-price');
        const categories = document.getElementById('categories');

        if (totalProducts) totalProducts.textContent = analytics.products.total_products;
        if (avgPrice) avgPrice.textContent = analytics.price_analysis ? formatKSh(analytics.price_analysis.avg_price) : 'KSh 0';
        if (categories) categories.textContent = analytics.categories ? analytics.categories.length : 0;
    }
}

// Display analytics insights
function displayAnalyticsInsights(analytics) {
    const insights = generateInsights(analytics);
    displayInsightsPanel(insights);
}

// Generate insights from analytics data
function generateInsights(analytics) {
    const insights = [];

    // Stock insights
    if (analytics.stock_analysis && analytics.stock_analysis.low_stock > 0) {
        insights.push({
            type: 'warning',
            title: 'Low Stock Alert',
            message: `${analytics.stock_analysis.low_stock} products are running low on stock`,
            action: 'View Low Stock',
            actionFunction: 'showLowStockProducts'
        });
    }

    // Price insights
    if (analytics.price_analysis) {
        const priceAnalysis = analytics.price_analysis;
        if (priceAnalysis.under_1k > priceAnalysis.over_10k * 3) {
            insights.push({
                type: 'info',
                title: 'Price Distribution',
                message: 'Most products are priced under KSh 1,000. Consider premium product lines.',
                action: 'View Price Analysis',
                actionFunction: 'showPriceAnalysis'
            });
        }
    }

    // Category insights
    if (analytics.categories && analytics.categories.length > 0) {
        const topCategory = analytics.categories[0];
        insights.push({
            type: 'success',
            title: 'Top Category',
            message: `${topCategory.category} has the most products (${topCategory.product_count})`,
            action: 'View Category',
            actionFunction: `filterByCategory('${topCategory.category}')`
        });
    }

    // Inventory value insights
    if (analytics.products && analytics.products.total_inventory_value) {
        const value = parseFloat(analytics.products.total_inventory_value.replace(/[^\d.]/g, ''));
        if (value > 100000) {
            insights.push({
                type: 'info',
                title: 'High Inventory Value',
                message: `Total inventory value: ${analytics.products.total_inventory_value}`,
                action: 'View Inventory Report',
                actionFunction: 'showInventoryReport'
            });
        }
    }

    return insights;
}

// Display insights panel
function displayInsightsPanel(insights) {
    // Create insights panel if it doesn't exist
    let insightsPanel = document.getElementById('analytics-insights');
    if (!insightsPanel) {
        insightsPanel = document.createElement('div');
        insightsPanel.id = 'analytics-insights';
        insightsPanel.className = 'insights-panel';

        // Insert after analytics grid
        const analyticsGrid = document.querySelector('.analytics-grid');
        if (analyticsGrid) {
            analyticsGrid.insertAdjacentElement('afterend', insightsPanel);
        }
    }

    // Clear existing insights
    insightsPanel.innerHTML = '';

    if (insights.length === 0) {
        insightsPanel.innerHTML = '<div class="no-insights">No insights available at this time.</div>';
        return;
    }

    // Add insights header
    const header = document.createElement('h3');
    header.textContent = 'Analytics Insights';
    header.className = 'insights-header';
    insightsPanel.appendChild(header);

    // Add insights
    insights.forEach(insight => {
        const insightDiv = document.createElement('div');
        insightDiv.className = `insight-item insight-${insight.type}`;
        insightDiv.innerHTML = `
            <div class="insight-content">
                <div class="insight-title">${insight.title}</div>
                <div class="insight-message">${insight.message}</div>
                <button class="insight-action btn btn-sm" onclick="${insight.actionFunction}">
                    ${insight.action}
                </button>
            </div>
        `;
        insightsPanel.appendChild(insightDiv);
    });
}

// Enhanced analytics refresh function
function refreshAnalytics() {
    showMessage('Refreshing analytics...', 'info');

    // Show loading state
    const refreshButton = document.querySelector('#analytics .btn-secondary');
    if (refreshButton) {
        refreshButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        refreshButton.disabled = true;
    }

    // Load analytics with additional data
    Promise.all([
        fetch('api/analytics.php'),
        fetch('api/analytics.php?type=categories'),
        fetch('api/analytics.php?type=low-stock'),
        fetch('api/analytics.php?type=inventory-value')
    ])
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(([mainAnalytics, categories, lowStock, inventoryValue]) => {
            if (mainAnalytics.success) {
                // Merge additional data
                const enhancedAnalytics = {
                    ...mainAnalytics.analytics,
                    categories: categories.success ? categories.data : [],
                    lowStock: lowStock.success ? lowStock.data : [],
                    inventoryValue: inventoryValue.success ? inventoryValue.data : {}
                };

                updateAnalyticsDisplay(enhancedAnalytics);
                showMessage('Analytics refreshed successfully!', 'success');
            } else {
                throw new Error(mainAnalytics.message || 'Failed to refresh analytics');
            }
        })
        .catch(error => {
            console.error('Analytics refresh error:', error);
            showMessage('Failed to refresh analytics: ' + error.message, 'error');
        })
        .finally(() => {
            // Reset button state
            if (refreshButton) {
                refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                refreshButton.disabled = false;
            }
        });
}

// Additional analytics functions
function showLowStockProducts() {
    // Switch to products section and filter by low stock
    showSection('products');

    // Apply low stock filter
    setTimeout(() => {
        const stockFilter = document.getElementById('stock-filter');
        if (stockFilter) {
            stockFilter.value = 'low';
            performSearch();
        }
    }, 100);
}

function showPriceAnalysis() {
    // Create and show price analysis modal
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Price Analysis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="price-analysis-chart"></div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Show modal (you'd need to implement the chart here)
    $(modal).modal('show');

    // Remove modal when closed
    $(modal).on('hidden.bs.modal', function () {
        document.body.removeChild(modal);
    });
}

function filterByCategory(category) {
    // Switch to products section and filter by category
    showSection('products');

    setTimeout(() => {
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.value = category;
            performSearch();
        }
    }, 100);
}

function showInventoryReport() {
    // Create inventory report modal
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Inventory Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="inventory-report-content">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Loading inventory report...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="exportInventoryReport()">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Show modal
    $(modal).modal('show');

    // Load inventory report data
    loadInventoryReport();

    // Remove modal when closed
    $(modal).on('hidden.bs.modal', function () {
        document.body.removeChild(modal);
    });
}

function loadInventoryReport() {
    fetch('api/analytics.php?type=inventory-value')
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('inventory-report-content');
            if (data.success) {
                content.innerHTML = generateInventoryReportHTML(data.data);
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load inventory report</div>';
            }
        })
        .catch(error => {
            console.error('Error loading inventory report:', error);
            const content = document.getElementById('inventory-report-content');
            content.innerHTML = '<div class="alert alert-danger">Error loading inventory report</div>';
        });
}

function generateInventoryReportHTML(data) {
    // Generate comprehensive inventory report HTML
    return `
        <div class="inventory-report">
            <div class="report-summary">
                <h6>Inventory Summary</h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="summary-item">
                            <div class="summary-label">Total Products</div>
                            <div class="summary-value">${data.total_products || 0}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-item">
                            <div class="summary-label">Total Value</div>
                            <div class="summary-value">${data.total_value || 'KSh 0'}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-item">
                            <div class="summary-label">Average Value</div>
                            <div class="summary-value">${data.average_value || 'KSh 0'}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-item">
                            <div class="summary-label">Categories</div>
                            <div class="summary-value">${data.categories_count || 0}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="report-details mt-4">
                <h6>Category Breakdown</h6>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Products</th>
                                <th>Total Stock</th>
                                <th>Total Value</th>
                                <th>Avg Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.categories ? data.categories.map(cat => `
                                <tr>
                                    <td>${cat.category}</td>
                                    <td>${cat.product_count}</td>
                                    <td>${cat.total_stock}</td>
                                    <td>${cat.total_value}</td>
                                    <td>${cat.avg_price}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="5">No data available</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

function exportInventoryReport() {
    // Implementation for exporting inventory report
    showMessage('Exporting inventory report...', 'info');

    fetch('api/export.php?type=inventory-report')
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'inventory-report.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showMessage('Inventory report exported successfully!', 'success');
        })
        .catch(error => {
            console.error('Export error:', error);
            showMessage('Failed to export inventory report', 'error');
        });
}

// Pagination functions
function changePage(direction) {
    currentPage += direction;
    if (currentPage < 1) currentPage = 1;

    performSearch();
    updatePageInfo();
}

function updatePageInfo() {
    document.getElementById('page-info').textContent = `Page ${currentPage}`;

    // Update button states
    document.getElementById('prev-page').disabled = currentPage === 1;
}

// Dark mode toggle
function toggleDarkMode() {
    document.body.classList.toggle('dark-theme');
    const isDark = document.body.classList.contains('dark-theme');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// Load theme on page load
function loadTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
}

// Format currency for display
function formatCurrency(amount) {
    return 'KSh ' + parseFloat(amount).toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Utility function to format currency in KSh
function formatKSh(amount) {
    if (isNaN(amount)) return 'KSh 0';
    return 'KSh ' + parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Utility function to format large numbers with K, M suffixes
function formatCompactKSh(amount) {
    if (isNaN(amount)) return 'KSh 0';

    const num = parseFloat(amount);
    if (num >= 1000000) {
        return 'KSh ' + (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return 'KSh ' + (num / 1000).toFixed(1) + 'K';
    } else {
        return formatKSh(num);
    }
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Auto-search with debounce
const debouncedSearch = debounce(performSearch, 300);

// Enhanced showSection function
const originalShowSection = showSection;
showSection = function (sectionId) {
    originalShowSection(sectionId);

    // Load specific data when sections are shown
    if (sectionId === 'analytics') {
        loadAnalytics();
    } else if (sectionId === 'products') {
        performSearch(); // Use enhanced search instead of loadProducts
    }
};

// Notification system
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation' : 'info'}-circle"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="notification-close">×</button>
    `;

    // Add notification styles if not already added
    if (!document.querySelector('.notification-styles')) {
        const style = document.createElement('style');
        style.className = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                padding: 15px 20px;
                display: flex;
                align-items: center;
                gap: 10px;
                z-index: 10000;
                max-width: 400px;
                animation: slideIn 0.3s ease;
            }
            .notification.success { border-left: 4px solid #27ae60; }
            .notification.error { border-left: 4px solid #e74c3c; }
            .notification.info { border-left: 4px solid #3498db; }
            .notification-close {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                margin-left: auto;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// Update the original showMessage to use the new notification system
const originalShowMessage = showMessage;
showMessage = function (message, type) {
    showNotification(message, type);
};
