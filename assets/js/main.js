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
// Products pagination variables
let productsPerPage = 10;
let totalProducts = 0;
let totalPages = 0;
let productsStats = {
    totalCount: 0,
    totalStock: 0,
    inventoryValue: 0,
    avgPrice: 0,
    categoriesCount: 0,
    lowStockCount: 0
};

// Customer products pagination
let customerCurrentPage = 1;
let customerProductsPerPage = 12;

// Shopping Cart Manager
class CartManager {
    constructor() {
        this.cart = this.loadCart();
    }

    loadCart() {
        const saved = localStorage.getItem('shopping_cart');
        return saved ? JSON.parse(saved) : [];
    }

    saveCart() {
        localStorage.setItem('shopping_cart', JSON.stringify(this.cart));
        this.updateCartBadge();
    }

    addToCart(product, quantity = 1) {
        const existingItem = this.cart.find(item => item.id === product.id);

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                image: product.image,
                quantity: quantity,
                stock: product.stock
            });
        }

        this.saveCart();
        showMessage(`${product.name} added to cart!`, 'success');
        return true;
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.saveCart();
    }

    updateQuantity(productId, quantity) {
        const item = this.cart.find(item => item.id === productId);
        if (item) {
            item.quantity = Math.max(1, quantity);
            this.saveCart();
        }
    }

    getCartCount() {
        return this.cart.reduce((total, item) => total + item.quantity, 0);
    }

    getCartTotal() {
        return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    clearCart() {
        this.cart = [];
        this.saveCart();
    }

    updateCartBadge() {
        const cartBadge = document.querySelector('.cart-badge');
        const count = this.getCartCount();

        if (cartBadge) {
            cartBadge.textContent = count;
            cartBadge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }
}

// Initialize cart manager
const cartManager = new CartManager();

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', async function () {
    // Add loading class to body to hide content during auth check
    document.body.classList.add('app-loading');
    const appLoader = document.getElementById('app-loader');

    loadTheme();

    // Check authentication first
    const isAuthenticated = await authManager.checkAuth();

    if (isAuthenticated) {
        console.log('=== AUTHENTICATION SUCCESSFUL ===');
        console.log('Current User:', authManager.currentUser);
        console.log('User Role:', authManager.userRole);
        console.log('Is Admin:', authManager.isAdmin());
        console.log('Is Customer:', authManager.isCustomer());

        // Render role-based UI
        renderSidebar();
        updateNavbar();

        // Load appropriate content based on role
        if (authManager.isAdmin()) {
            console.log('Loading ADMIN dashboard...');
            loadDashboardStats();
            performSearch(); // Use enhanced search for initial load
        } else {
            console.log('Loading CUSTOMER products...');
            // Load customer-specific content
            showSection('view-products');
        }

        // Remove loading class and hide loader to show content with smooth transition
        setTimeout(() => {
            document.body.classList.remove('app-loading');
            if (appLoader) {
                appLoader.classList.add('hidden');
            }
        }, 100);
    } else {
        // Remove loading class and hide loader even if auth fails
        document.body.classList.remove('app-loading');
        if (appLoader) {
            appLoader.classList.add('hidden');
        }
    }

    initializeSidebar();

    // Initialize form submission handlers
    initializeEventListeners();
    
    // Initialize product preview functionality
    initializeProductPreview();

    // Initialize analytics if on analytics page
    if (document.querySelector('.content-section.active')?.id === 'analytics') {
        initializeAnalytics();
    }
});

// Initialize event listeners
function initializeEventListeners() {
    // Product form submission
    const productForm = document.getElementById('product-form');
    if (productForm) {
        productForm.addEventListener('submit', handleProductSubmit);
    }

    // Edit form submission
    const editForm = document.getElementById('edit-form');
    if (editForm) {
        editForm.addEventListener('submit', handleEditSubmit);
    }

    // Close modal when clicking outside
    window.addEventListener('click', function (event) {
        const modal = document.getElementById('edit-modal');
        if (event.target === modal) {
            closeModal();
        }

        // Close admin modal when clicking outside
        const adminModal = document.getElementById('admin-modal');
        if (event.target === adminModal) {
            closeAdminModal();
        }
    });
}

// Render sidebar based on user role
function renderSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    const isAdmin = authManager && authManager.isAdmin();
    const userRole = authManager ? authManager.getRole() : 'customer';

    // Update sidebar header
    const sidebarHeader = sidebar.querySelector('.sidebar-header h3');
    if (sidebarHeader) {
        sidebarHeader.innerHTML = `<i class="fas fa-chart-line"></i> ${isAdmin ? 'Admin Dashboard' : 'Customer Portal'}`;
    }

    // Generate menu items based on role
    const sidebarMenu = sidebar.querySelector('.sidebar-menu');
    if (!sidebarMenu) return;

    let menuHTML = '';

    if (isAdmin) {
        // Admin menu items
        menuHTML = `
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
        `;
    } else {
        // Customer menu items
        const cartCount = cartManager.getCartCount();
        menuHTML = `
            <li class="active">
                <a href="#view-products" onclick="showSection('view-products')">
                    <i class="fas fa-box-open"></i>
                    <span>All Products</span>
                </a>
            </li>
            <li>
                <a href="#order-product" onclick="showSection('order-product')">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                    <span class="cart-badge" style="display: ${cartCount > 0 ? 'inline-flex' : 'none'}">${cartCount}</span>
                </a>
            </li>
            <li>
                <a href="#view-orders" onclick="showSection('view-orders')">
                    <i class="fas fa-receipt"></i>
                    <span>View Orders</span>
                </a>
            </li>
            <li>
                <a href="#my-profile" onclick="showSection('my-profile')">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
        `;
    }

    sidebarMenu.innerHTML = menuHTML;

    // Update sidebar footer user info
    const sidebarUserName = sidebar.querySelector('.sidebar-user-name');
    if (sidebarUserName && authManager && authManager.currentUser) {
        sidebarUserName.textContent = authManager.currentUser.full_name || authManager.currentUser.username;
    }
}

// Update navbar based on user role
function updateNavbar() {
    const navbarTitle = document.querySelector('.navbar-brand h2');
    if (navbarTitle && authManager) {
        const isAdmin = authManager.isAdmin();
        navbarTitle.innerHTML = `<i class="fas fa-chart-line"></i> ${isAdmin ? 'Admin Dashboard' : 'Customer Dashboard'}`;
    }

    // Update navbar user display
    const navbarUserName = document.querySelector('.navbar-user .user-name');
    if (navbarUserName && authManager && authManager.currentUser) {
        navbarUserName.textContent = authManager.currentUser.full_name || authManager.currentUser.username;
    }
}

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
    } else if (sectionId === 'view-products') {
        loadCustomerProducts();
    } else if (sectionId === 'order-product') {
        displayCart();
    } else if (sectionId === 'view-orders') {
        loadCustomerOrders();
    } else if (sectionId === 'my-profile') {
        loadCustomerProfile();
    }

    // Update page title
    updatePageTitle(sectionId);
}

// Update page title based on section
function updatePageTitle(sectionId) {
    const titles = {
        'dashboard': 'Product Management System',
        'products': 'Products List',
        'analytics': 'Analytics Dashboard',
        'add-product': 'Add New Product',
        'view-products': 'Browse Products',
        'order-product': 'Shopping Cart',
        'view-orders': 'My Orders',
        'my-profile': 'My Profile'
    };

    const pageTitle = document.getElementById('page-title');
    if (pageTitle && titles[sectionId]) {
        pageTitle.textContent = titles[sectionId];
    }
}

// Note: loadDashboardStats function moved below with enhanced animations

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
    if (!container) return;

    container.innerHTML = '';

    if (!categories || categories.length === 0) {
        container.innerHTML = '<div class="no-data">No category data available</div>';
        return;
    }

    categories.slice(0, 5).forEach(category => {
        const categoryItem = document.createElement('div');
        categoryItem.className = 'category-item';
        categoryItem.innerHTML = `
            <div class="category-info">
                <span class="category-name">${category.category || 'Unknown'}</span>
                <span class="category-count">${category.product_count || 0} products</span>
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
    const container = document.getElementById('dashboard-low-stock-tbody');
    if (!container) return;

    container.innerHTML = '';

    if (!products || products.length === 0) {
        container.innerHTML = '<div class="no-data">No low stock items</div>';
        return;
    }

    products.slice(0, 5).forEach(product => {
        const alertItem = document.createElement('div');
        alertItem.className = 'alert-item';
        alertItem.innerHTML = `
            <div class="alert-content">
                <div class="alert-title">${product.name}</div>
                <div class="alert-details">
                    <span class="category-badge">${product.category}</span>
                    <span class="stock-badge ${product.stock <= 5 ? 'critical' : 'low'}">${product.stock} left</span>
                    <span class="price-badge">${formatKSh(product.price)}</span>
                </div>
            </div>
            <div class="alert-actions">
                <button class="btn btn-sm btn-warning" onclick="editProduct(${product.id})" title="Edit Product">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        `;
        container.appendChild(alertItem);
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
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">No products found</td></tr>';
        return;
    }

    products.slice(0, 5).forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="product-info">
                    ${product.image ?
            `<img src="uploads/${product.image}" alt="${product.name}" class="product-thumb">` :
            '<div class="product-thumb-icon no-image"><i class="fas fa-box"></i></div>'
                    }
                    <span class="product-name">${product.name}</span>
                </div>
            </td>
            <td><span class="category-badge">${product.category}</span></td>
            <td>${product.formatted_price || formatKSh(product.price)}</td>
            <td><span class="stock-badge ${product.stock <= 10 ? 'low-stock' : ''}">${product.stock}</span></td>
            <td><span class="badge ${product.stock > 0 ? 'in-stock' : 'out-of-stock'}">${product.stock > 0 ? 'In Stock' : 'Out of Stock'}</span></td>
        `;
        tbody.appendChild(row);
    });
}

function updateDashboardPagination(stats) {
    dashboardProductsTotal = stats.total_products || 0;
    const totalPages = Math.ceil(dashboardProductsTotal / dashboardProductsPerPage);

    // Update pagination info (only if elements exist)
    const pageInfoEl = document.getElementById('dashboard-page-info');
    const productsCountEl = document.getElementById('dashboard-products-count');
    const prevBtnEl = document.getElementById('dashboard-prev-page');
    const nextBtnEl = document.getElementById('dashboard-next-page');

    if (pageInfoEl) pageInfoEl.textContent = `Page ${dashboardCurrentPage} of ${totalPages}`;
    if (productsCountEl) productsCountEl.textContent = `(${dashboardProductsTotal} products)`;

    // Update button states (only if buttons exist)
    if (prevBtnEl) prevBtnEl.disabled = dashboardCurrentPage === 1;
    if (nextBtnEl) nextBtnEl.disabled = dashboardCurrentPage >= totalPages;
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

    // Validate form before submission
    if (!validateProductForm()) {
        return;
    }

    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');

    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
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
                // Refresh the products list if we're on that page
                if (document.getElementById('products').classList.contains('active')) {
                    performSearch();
                }
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

// Form validation function
function validateProductForm() {
    const name = document.getElementById('product-name').value.trim();
    const category = document.getElementById('product-category').value;
    const price = parseFloat(document.getElementById('product-price').value);
    const stock = parseInt(document.getElementById('product-stock').value);

    // Clear previous errors
    clearFormErrors();

    let isValid = true;

    // Validate name
    if (!name || name.length < 2) {
        showFieldError('product-name', 'Product name must be at least 2 characters long');
        isValid = false;
    }

    // Validate category
    if (!category) {
        showFieldError('product-category', 'Please select a category');
        isValid = false;
    }

    // Validate price
    if (isNaN(price) || price <= 0) {
        showFieldError('product-price', 'Price must be a positive number');
        isValid = false;
    }

    // Validate stock
    if (isNaN(stock) || stock < 0) {
        showFieldError('product-stock', 'Stock must be a non-negative number');
        isValid = false;
    }

    return isValid;
}

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const formGroup = field.closest('.form-group');

    // Add error class
    field.classList.add('error');

    // Create error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    formGroup.appendChild(errorDiv);
}

function clearFormErrors() {
    const errorFields = document.querySelectorAll('.form-group input.error, .form-group select.error');
    errorFields.forEach(field => field.classList.remove('error'));

    const errorMessages = document.querySelectorAll('.field-error');
    errorMessages.forEach(msg => msg.remove());
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

                // Display current image
                const currentImageContainer = document.getElementById('edit-current-image');
                if (product.image) {
                    currentImageContainer.innerHTML = `
                        <div class="current-image-wrapper">
                            <img src="uploads/${product.image}" alt="${product.name}" class="current-product-image">
                            <p class="current-image-label">Current Image</p>
                        </div>
                    `;
                } else {
                    currentImageContainer.innerHTML = `
                        <div class="no-current-image">
                            <i class="fas fa-image"></i>
                            <p>No current image</p>
                        </div>
                    `;
                }

                // Reset file input and label
                const editImageInput = document.getElementById('edit-image');
                const editFileLabel = document.querySelector('.edit-file-label span');
                editImageInput.value = '';
                if (editFileLabel) {
                    editFileLabel.textContent = 'Choose New Image';
                }

                // Add event listener for image preview in edit modal
                editImageInput.removeEventListener('change', handleEditImagePreview);
                editImageInput.addEventListener('change', handleEditImagePreview);

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

                // Refresh the appropriate view
                const activeSection = document.querySelector('.content-section.active');
                if (activeSection && activeSection.id === 'products') {
                    performSearch(); // Refresh the products search
                } else if (activeSection && activeSection.id === 'dashboard') {
                    loadDashboardStats(); // Refresh dashboard
                    loadDashboardProducts(); // Refresh dashboard products
                } else {
                    loadProducts(); // Fallback
                }
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

// Admin Modal Functions
function openAdminModal() {
    const modal = document.getElementById('admin-modal');
    const loading = document.getElementById('admin-info-loading');
    const content = document.getElementById('admin-info-content');
    const error = document.getElementById('admin-error');

    // Show modal and loading state
    modal.style.display = 'block';
    loading.style.display = 'flex';
    content.style.display = 'none';
    error.style.display = 'none';

    // Fetch admin data
    fetch('api/admin.php')
        .then(response => {
            console.log('Admin API response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Admin API response data:', data);
            loading.style.display = 'none';

            if (data.success) {
                displayAdminInfo(data.admin);
                content.style.display = 'block';
            } else {
                // Check if we need to redirect to login
                if (data.redirect) {
                    console.log('Redirecting to:', data.redirect);
                    window.location.href = data.redirect;
                    return;
                }
                showAdminError(data.error || 'Failed to load admin information');
            }
        })
        .catch(error => {
            console.error('Error fetching admin data:', error);
            loading.style.display = 'none';
            showAdminError('Network error occurred');
        });
}

function closeAdminModal() {
    const modal = document.getElementById('admin-modal');
    modal.style.display = 'none';
}

function displayAdminInfo(admin) {
    document.getElementById('admin-avatar').src = 'https://avatar.iran.liara.run/public/18';
    document.getElementById('admin-user-id').textContent = admin.user_id;
    document.getElementById('admin-username').textContent = admin.username;
    document.getElementById('admin-email').textContent = admin.email;
    document.getElementById('admin-status').textContent = admin.status;
    document.getElementById('admin-created').textContent = admin.created_at;
    document.getElementById('admin-updated').textContent = admin.updated_at;

    // Style the status badge based on is_active field
    const statusElement = document.getElementById('admin-status');
    if (admin.status === 'Active') {
        statusElement.className = 'status-badge status-active';
    } else {
        statusElement.className = 'status-badge status-inactive';
    }
}

function showAdminError(message) {
    document.getElementById('admin-error-text').textContent = message;
    document.getElementById('admin-error').style.display = 'block';
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // Check if AuthManager is available
        if (typeof authManager !== 'undefined' && authManager.logout) {
            // Use the existing AuthManager logout method
            authManager.logout();
        } else {
            // Fallback logout method if AuthManager is not available
            fetch('api/auth.php?action=logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect to login page
                        window.location.href = 'login.php';
                    } else {
                        console.error('Logout failed:', data.message);
                        // Still redirect to login page even if logout API fails
                        window.location.href = 'login.php';
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    // Still redirect to login page even if request fails
                    window.location.href = 'login.php';
                });
        }
    }
}

// Reset form
function resetForm() {
    document.getElementById('product-form').reset();
    resetProductPreview();
    showSection('products');
}

// Reset product preview to default values
function resetProductPreview() {
    document.getElementById('preview-name').textContent = 'Product Name';
    document.getElementById('preview-category').textContent = 'Category';
    document.getElementById('preview-price').textContent = 'KSh 0.00';
    document.getElementById('preview-stock').textContent = '0 in stock';
    document.getElementById('preview-description').textContent = 'Product description will appear here...';
    document.getElementById('preview-unit-price').textContent = 'KSh 0.00';
    document.getElementById('preview-total-value').textContent = 'KSh 0.00';
    document.getElementById('preview-status').textContent = 'Not set';
    
    // Reset image
    const previewImage = document.getElementById('preview-image');
    previewImage.innerHTML = '<i class="fas fa-image"></i><span>No image selected</span>';
    
    // Reset badges
    const stockBadge = document.getElementById('preview-stock-badge');
    stockBadge.textContent = 'Out of Stock';
    stockBadge.className = 'stock-badge';
    
    document.getElementById('preview-value-badge').textContent = 'KSh 0.00 Total Value';
}

// Initialize product preview functionality
function initializeProductPreview() {
    // Get form elements
    const nameInput = document.getElementById('product-name');
    const categorySelect = document.getElementById('product-category');
    const priceInput = document.getElementById('product-price');
    const stockInput = document.getElementById('product-stock');
    const descriptionTextarea = document.getElementById('product-description');
    const imageInput = document.getElementById('product-image');
    
    // Add event listeners for real-time preview updates
    if (nameInput) {
        nameInput.addEventListener('input', updateProductPreview);
    }
    if (categorySelect) {
        categorySelect.addEventListener('change', updateProductPreview);
    }
    if (priceInput) {
        priceInput.addEventListener('input', updateProductPreview);
    }
    if (stockInput) {
        stockInput.addEventListener('input', updateProductPreview);
    }
    if (descriptionTextarea) {
        descriptionTextarea.addEventListener('input', updateProductPreview);
    }
    if (imageInput) {
        imageInput.addEventListener('change', handleImagePreview);
    }
}

// Update product preview with current form values
function updateProductPreview() {
    const name = document.getElementById('product-name')?.value || 'Product Name';
    const category = document.getElementById('product-category')?.value || 'Category';
    const price = parseFloat(document.getElementById('product-price')?.value) || 0;
    const stock = parseInt(document.getElementById('product-stock')?.value) || 0;
    const description = document.getElementById('product-description')?.value || 'Product description will appear here...';
    
    // Update preview elements
    document.getElementById('preview-name').textContent = name;
    document.getElementById('preview-category').textContent = category;
    document.getElementById('preview-price').textContent = formatKSh(price);
    document.getElementById('preview-stock').textContent = `${stock} in stock`;
    document.getElementById('preview-description').textContent = description;
    document.getElementById('preview-unit-price').textContent = formatKSh(price);
    
    // Calculate total value
    const totalValue = price * stock;
    document.getElementById('preview-total-value').textContent = formatKSh(totalValue);
    document.getElementById('preview-value-badge').textContent = `${formatKSh(totalValue)} Total Value`;
    
    // Update stock status
    const stockBadge = document.getElementById('preview-stock-badge');
    const statusElement = document.getElementById('preview-status');
    
    if (stock > 0) {
        stockBadge.textContent = 'In Stock';
        stockBadge.className = 'stock-badge in-stock';
        statusElement.textContent = stock > 10 ? 'Good Stock' : 'Low Stock';
    } else {
        stockBadge.textContent = 'Out of Stock';
        stockBadge.className = 'stock-badge';
        statusElement.textContent = 'Out of Stock';
    }
}

// Handle image preview
function handleImagePreview(event) {
    const file = event.target.files[0];
    const previewImage = document.getElementById('preview-image');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.innerHTML = `<img src="${e.target.result}" alt="Product Preview">`;
        };
        reader.readAsDataURL(file);
        
        // Update file input label
        const label = document.querySelector('.file-input-label span');
        if (label) {
            label.textContent = file.name;
        }
    } else {
        previewImage.innerHTML = '<i class="fas fa-image"></i><span>No image selected</span>';
        
        // Reset file input label
        const label = document.querySelector('.file-input-label span');
        if (label) {
            label.textContent = 'Choose Image';
        }
    }
}

// Handle image preview for edit modal
function handleEditImagePreview(event) {
    console.log('Image preview triggered'); // Debug log
    const file = event.target.files[0];
    const currentImageContainer = document.getElementById('edit-current-image');
    const editFileLabel = document.querySelector('.edit-file-label span');

    console.log('File selected:', file); // Debug log
    console.log('Container found:', currentImageContainer); // Debug log

    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            console.log('File loaded, updating preview'); // Debug log
            currentImageContainer.innerHTML = `
                <div class="new-image-wrapper">
                    <img src="${e.target.result}" alt="New Product Image" class="new-product-image">
                    <p class="new-image-label">New Image Preview</p>
                </div>
            `;
        };
        reader.readAsDataURL(file);

        // Update file input label
        if (editFileLabel) {
            editFileLabel.textContent = file.name;
        }
    } else {
        // Reset to show current image or no image message
        // This will be handled when the modal is opened
        if (editFileLabel) {
            editFileLabel.textContent = 'Choose New Image';
        }
    }
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
    // Check if elements exist before accessing them
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    const inStockCheckbox = document.getElementById('in-stock-only');
    const lowStockCheckbox = document.getElementById('low-stock-only');
    const sortBySelect = document.getElementById('sort-by');
    const sortOrderSelect = document.getElementById('sort-order');

    const searchTerm = searchInput ? searchInput.value : '';
    const category = categoryFilter ? categoryFilter.value : '';
    const minPrice = minPriceInput ? minPriceInput.value : '';
    const maxPrice = maxPriceInput ? maxPriceInput.value : '';
    const inStockOnly = inStockCheckbox ? inStockCheckbox.checked : false;
    const lowStockOnly = lowStockCheckbox ? lowStockCheckbox.checked : false;
    const sortBy = sortBySelect ? sortBySelect.value : 'id';
    const sortOrder = sortOrderSelect ? sortOrderSelect.value : 'DESC';

    currentFilters = {
        search: searchTerm,
        category: category,
        min_price: minPrice,
        max_price: maxPrice,
        in_stock: inStockOnly,
        low_stock: lowStockOnly,
        sort_by: sortBy,
        sort_order: sortOrder,
        limit: productsPerPage,
        offset: (currentPage - 1) * productsPerPage
    };

    // Build query string - only add parameters that have values
    const params = new URLSearchParams();
    Object.keys(currentFilters).forEach(key => {
        const value = currentFilters[key];
        if (value !== '' && value !== false && value !== null && value !== undefined) {
            params.append(key, value);
        }
    });

    fetch(`api/search.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.products);
                updateSearchStats(data.stats);
                updateProductsKPI(data.stats);
                updateProductsPagination(data.stats);
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

// ================ CUSTOMER PRODUCT FUNCTIONS ================

// Load customer products with search and filter
function loadCustomerProducts() {
    const searchInput = document.getElementById('customer-search-input');
    const categoryFilter = document.getElementById('customer-category-filter');
    const sortBy = document.getElementById('customer-sort-by');

    const searchTerm = searchInput ? searchInput.value : '';
    const category = categoryFilter ? categoryFilter.value : '';
    const sortOrder = sortBy ? sortBy.value : 'name_asc';

    const params = new URLSearchParams({
        search: searchTerm,
        category: category,
        sort: sortOrder,
        limit: customerProductsPerPage,
        offset: (customerCurrentPage - 1) * customerProductsPerPage
    });

    // Remove empty parameters
    for (let [key, value] of [...params.entries()]) {
        if (!value) params.delete(key);
    }

    fetch(`api/search.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCustomerProducts(data.products);
                updateCustomerPagination(data.stats);
            } else {
                showMessage('Failed to load products', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            showMessage('Failed to load products', 'error');
        });
}

// Display products as cards for customers
function displayCustomerProducts(products) {
    const container = document.getElementById('customer-products-grid');

    if (!container) {
        console.error('Customer products grid container not found');
        return;
    }

    container.innerHTML = '';

    if (products.length === 0) {
        container.innerHTML = '<div class="no-products"><i class="fas fa-box-open"></i><p>No products found</p></div>';
        return;
    }

    products.forEach(product => {
        const card = document.createElement('div');
        card.className = 'product-card';

        const outOfStock = product.stock === 0;
        const lowStock = product.stock > 0 && product.stock <= 10;

        card.innerHTML = `
            <div class="product-card-image">
                ${product.image ?
                `<img src="uploads/${product.image}" alt="${product.name}" loading="lazy">` :
                '<div class="no-image"><i class="fas fa-image"></i></div>'
            }
                ${outOfStock ? '<div class="stock-overlay">Out of Stock</div>' : ''}
                ${lowStock ? '<div class="stock-badge-card low">Low Stock</div>' : ''}
            </div>
            <div class="product-card-content">
                <h3 class="product-card-title">${product.name}</h3>
                <p class="product-card-category">
                    <i class="fas fa-tag"></i> ${product.category}
                </p>
                ${product.description ?
                `<p class="product-card-description">${product.description.substring(0, 100)}${product.description.length > 100 ? '...' : ''}</p>` :
                ''
            }
                <div class="product-card-footer">
                    <div class="product-card-price">${product.formatted_price}</div>
                    <button class="btn btn-primary ${outOfStock ? 'disabled' : ''}" 
                            onclick="addToCart(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price}, '${product.image || ''}', ${product.stock})"
                            ${outOfStock ? 'disabled' : ''}>
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </div>
        `;

        container.appendChild(card);
    });
}

// Add product to cart
function addToCart(id, name, price, image, stock) {
    if (stock === 0) {
        showMessage('This product is out of stock', 'error');
        return;
    }

    const product = { id, name, price, image, stock };
    cartManager.addToCart(product, 1);
}

// Display shopping cart
function displayCart() {
    const container = document.getElementById('cart-container');

    if (!container) {
        console.error('Cart container not found');
        return;
    }

    const cart = cartManager.cart;

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Add some products to get started!</p>
                <button class="btn btn-primary" onclick="showSection('view-products')">
                    <i class="fas fa-shopping-bag"></i> Browse Products
                </button>
            </div>
        `;
        return;
    }

    let cartHTML = '<div class="cart-items">';

    cart.forEach(item => {
        cartHTML += `
            <div class="cart-item">
                <div class="cart-item-image">
                    ${item.image ?
                `<img src="uploads/${item.image}" alt="${item.name}">` :
                '<div class="no-image"><i class="fas fa-image"></i></div>'
            }
                </div>
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <p class="cart-item-price">Ksh ${item.price.toFixed(2)}</p>
                </div>
                <div class="cart-item-quantity">
                    <button onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})" class="qty-btn">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" value="${item.quantity}" min="1" max="${item.stock}" 
                           onchange="updateCartQuantity(${item.id}, this.value)" class="qty-input">
                    <button onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})" class="qty-btn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="cart-item-total">
                    Ksh ${(item.price * item.quantity).toFixed(2)}
                </div>
                <button onclick="removeFromCart(${item.id})" class="cart-item-remove">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });

    cartHTML += '</div>';

    const total = cartManager.getCartTotal();

    cartHTML += `
        <div class="cart-summary">
            <div class="cart-summary-row">
                <span>Subtotal:</span>
                <span>Ksh ${total.toFixed(2)}</span>
            </div>
            <div class="cart-summary-row">
                <span>Tax (10%):</span>
                <span>Ksh ${(total * 0.1).toFixed(2)}</span>
            </div>
            <div class="cart-summary-row total">
                <span>Total:</span>
                <span>Ksh ${(total * 1.1).toFixed(2)}</span>
            </div>
            <div class="cart-actions">
                <button class="btn btn-secondary" onclick="cartManager.clearCart(); displayCart();">
                    <i class="fas fa-trash"></i> Clear Cart
                </button>
                <button class="btn btn-primary" onclick="proceedToCheckout()">
                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                </button>
            </div>
        </div>
    `;

    container.innerHTML = cartHTML;
}

// Update cart item quantity
function updateCartQuantity(productId, newQuantity) {
    const quantity = parseInt(newQuantity);

    if (quantity < 1) {
        if (confirm('Remove this item from cart?')) {
            cartManager.removeFromCart(productId);
            displayCart();
        }
        return;
    }

    const item = cartManager.cart.find(i => i.id === productId);
    if (item && quantity > item.stock) {
        showMessage(`Only ${item.stock} items available in stock`, 'error');
        displayCart();
        return;
    }

    cartManager.updateQuantity(productId, quantity);
    displayCart();
}

// Remove item from cart
function removeFromCart(productId) {
    if (confirm('Remove this item from cart?')) {
        cartManager.removeFromCart(productId);
        displayCart();
    }
}

// Proceed to checkout
async function proceedToCheckout() {
    if (cartManager.cart.length === 0) {
        showMessage('Your cart is empty', 'error');
        return;
    }

    // Confirm order
    const total = cartManager.getCartTotal();
    const tax = total * 0.1;
    const grandTotal = total + tax;

    if (!confirm(`Place order for KSh ${grandTotal.toFixed(2)}?\n\nSubtotal: KSh ${total.toFixed(2)}\nTax (10%): KSh ${tax.toFixed(2)}\nTotal: KSh ${grandTotal.toFixed(2)}`)) {
        return;
    }

    try {
        // Show loading message
        showMessage('Placing order...', 'info');

        // Prepare order data
        const orderData = {
            items: cartManager.cart
        };

        // Send order to API
        const response = await fetch('api/orders.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });

        const result = await response.json();

        if (result.success) {
            // Show success message
            showOrderSuccessModal(result.order);

            // Clear cart
            cartManager.clearCart();
            displayCart();

            // Show success message
            showMessage(`Order ${result.order.order_number} placed successfully!`, 'success');
        } else {
            showMessage('Failed to place order: ' + result.error, 'error');
        }

    } catch (error) {
        console.error('Checkout error:', error);
        showMessage('Failed to place order. Please try again.', 'error');
    }
}

// Show order success modal
function showOrderSuccessModal(order) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'order-success-modal';
    modal.style.display = 'flex';

    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px; max-height: 50vh; overflow-y: auto; border-radius: 8px;">
            <div class="modal-header" style="background: #27ae60; color: white;">
                <h3><i class="fas fa-check-circle"></i> Order Placed Successfully!</h3>
                <span class="close" onclick="closeOrderSuccessModal()">&times;</span>
            </div>
            <div class="modal-body" style="text-align: center; padding: 30px;">
                <div style="font-size: 3rem; color: #27ae60; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Thank you for your order!</h3>
                <p style="margin: 20px 0;">Your order has been placed successfully.</p>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p><strong>Order Number:</strong> ${order.order_number}</p>
                    <p><strong>Total Amount:</strong> KSh ${order.total}</p>
                    <p><strong>Status:</strong> <span class="status-badge pending">Pending Payment</span></p>
                </div>
                
                <p style="color: #666; font-size: 0.9rem;">
                    Your order is pending payment approval. You will be notified once the admin approves your payment.
                </p>
                
                <div style="margin-top: 30px;">
                    <button class="btn btn-primary" onclick="closeOrderSuccessModal(); showSection('view-orders');">
                        <i class="fas fa-list"></i> View My Orders
                    </button>
                    <button class="btn btn-secondary" onclick="closeOrderSuccessModal(); showSection('view-products');">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

// Close order success modal
function closeOrderSuccessModal() {
    const modal = document.getElementById('order-success-modal');
    if (modal) {
        modal.remove();
    }
}

// Load customer orders
async function loadCustomerOrders() {
    try {
        const response = await fetch('api/orders.php?action=list');
        const result = await response.json();

        if (result.success) {
            displayCustomerOrders(result.orders);
        } else {
            showMessage('Failed to load orders: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        showMessage('Failed to load orders', 'error');
    }
}

// Display customer orders
function displayCustomerOrders(orders) {
    const container = document.querySelector('#view-orders .orders-list');

    if (!container) {
        console.error('Orders container not found');
        return;
    }

    if (orders.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No orders yet</h3>
                <p>Your order history will appear here</p>
                <button class="btn btn-primary" onclick="showSection('view-products')">
                    <i class="fas fa-shopping-bag"></i> Start Shopping
                </button>
            </div>
        `;
        return;
    }

    let ordersHTML = '';

    orders.forEach(order => {
        const statusClass = order.order_status;
        const paymentClass = order.payment_status;

        ordersHTML += `
            <div class="order-card" onclick="viewOrderDetails(${order.id})">
                <div class="order-card-header">
                    <div>
                        <h4>${order.order_number}</h4>
                        <p class="order-date">${order.order_date_formatted}</p>
                    </div>
                    <div class="order-status-badges">
                        <span class="status-badge ${statusClass}">${order.order_status}</span>
                        <span class="status-badge ${paymentClass}">${order.payment_status}</span>
                    </div>
                </div>
                <div class="order-card-body">
                    <div class="order-info-row">
                        <span><i class="fas fa-box"></i> ${order.total_items} item(s)</span>
                        <span><i class="fas fa-cubes"></i> ${order.total_quantity} unit(s)</span>
                    </div>
                    <div class="order-info-row">
                        <span>Subtotal:</span>
                        <span>${order.formatted_subtotal}</span>
                    </div>
                    <div class="order-info-row">
                        <span>Tax:</span>
                        <span>${order.formatted_tax}</span>
                    </div>
                    <div class="order-info-row total">
                        <span>Total:</span>
                        <strong>${order.formatted_total}</strong>
                    </div>
                </div>
                <div class="order-card-footer">
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); viewOrderDetails(${order.id})">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    ${order.payment_status === 'pending' ?
                `<button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); cancelOrder(${order.id})">
                            <i class="fas fa-times"></i> Cancel
                        </button>` :
                ''}
                </div>
            </div>
        `;
    });

    container.innerHTML = ordersHTML;
}

// View order details
async function viewOrderDetails(orderId) {
    try {
        const response = await fetch(`api/orders.php?action=details&id=${orderId}`);
        const result = await response.json();

        if (result.success) {
            showOrderDetailsModal(result.order, result.items);
        } else {
            showMessage('Failed to load order details: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error loading order details:', error);
        showMessage('Failed to load order details', 'error');
    }
}

// Show order details modal
function showOrderDetailsModal(order, items) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'order-details-modal';
    modal.style.display = 'flex';

    let itemsHTML = '';
    items.forEach(item => {
        itemsHTML += `
            <div class="order-detail-item">
                <div class="order-detail-item-image">
                    ${item.product_image ?
                `<img src="uploads/${item.product_image}" alt="${item.product_name}">` :
                '<div class="no-image"><i class="fas fa-image"></i></div>'}
                </div>
                <div class="order-detail-item-info">
                    <h5>${item.product_name}</h5>
                    <p>${item.product_category}</p>
                </div>
                <div class="order-detail-item-qty">
                    ${item.quantity} × ${item.formatted_price}
                </div>
                <div class="order-detail-item-total">
                    ${item.formatted_subtotal}
                </div>
            </div>
        `;
    });

    modal.innerHTML = `
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Order Details</h3>
                <span class="close" onclick="closeOrderDetailsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="order-details-header">
                    <div>
                        <h4>Order ${order.order_number}</h4>
                        <p>${order.order_date_formatted}</p>
                    </div>
                    <div>
                        <span class="status-badge ${order.order_status}">${order.order_status}</span>
                        <span class="status-badge ${order.payment_status}">${order.payment_status}</span>
                    </div>
                </div>
                
                <div class="order-details-items">
                    <h5>Order Items</h5>
                    ${itemsHTML}
                </div>
                
                <div class="order-details-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>${order.formatted_subtotal}</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (10%):</span>
                        <span>${order.formatted_tax}</span>
                    </div>
                    <div class="summary-row total">
                        <strong>Total:</strong>
                        <strong>${order.formatted_total}</strong>
                    </div>
                </div>
                
                ${order.payment_date ?
            `<div class="order-payment-info">
                        <p><i class="fas fa-check-circle"></i> Payment approved on ${order.payment_date_formatted}</p>
                        ${order.payment_method ? `<p>Payment Method: ${order.payment_method}</p>` : ''}
                    </div>` :
            '<div class="order-payment-info pending"><p><i class="fas fa-clock"></i> Awaiting payment approval from admin</p></div>'}
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeOrderDetailsModal()">Close</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

// Close order details modal
function closeOrderDetailsModal() {
    const modal = document.getElementById('order-details-modal');
    if (modal) {
        modal.remove();
    }
}

// Cancel order
async function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('api/orders.php?action=cancel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({ id: orderId })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Order cancelled successfully', 'success');
            loadCustomerOrders(); // Reload orders
        } else {
            showMessage('Failed to cancel order: ' + result.error, 'error');
        }
    } catch (error) {
        console.error('Error cancelling order:', error);
        showMessage('Failed to cancel order', 'error');
    }
}

// Update customer pagination
function updateCustomerPagination(stats) {
    const totalProducts = stats.total_products || 0;
    const totalPages = Math.ceil(totalProducts / customerProductsPerPage);

    const paginationContainer = document.getElementById('customer-pagination');
    if (!paginationContainer) return;

    let paginationHTML = '';

    if (totalPages > 1) {
        paginationHTML += `<button onclick="customerChangePage(${customerCurrentPage - 1})" ${customerCurrentPage === 1 ? 'disabled' : ''}>Previous</button>`;

        for (let i = 1; i <= Math.min(totalPages, 5); i++) {
            paginationHTML += `<button onclick="customerChangePage(${i})" class="${i === customerCurrentPage ? 'active' : ''}">${i}</button>`;
        }

        if (totalPages > 5) {
            paginationHTML += '<span>...</span>';
            paginationHTML += `<button onclick="customerChangePage(${totalPages})">${totalPages}</button>`;
        }

        paginationHTML += `<button onclick="customerChangePage(${customerCurrentPage + 1})" ${customerCurrentPage === totalPages ? 'disabled' : ''}>Next</button>`;
    }

    paginationContainer.innerHTML = paginationHTML;
}

// Change customer page
function customerChangePage(page) {
    const totalPages = Math.ceil(totalProducts / customerProductsPerPage);
    if (page < 1 || page > totalPages) return;

    customerCurrentPage = page;
    loadCustomerProducts();
}

// Load customer profile
function loadCustomerProfile() {
    if (!authManager || !authManager.currentUser) return;

    const user = authManager.currentUser;

    const profileName = document.getElementById('profile-name');
    const profileEmail = document.getElementById('profile-email');
    const profileUsername = document.getElementById('profile-username');
    const profileMemberSince = document.getElementById('profile-member-since');

    if (profileName) profileName.textContent = user.full_name || user.username;
    if (profileEmail) profileEmail.textContent = user.email;
    if (profileUsername) profileUsername.textContent = user.username;

    // Format member since date if available
    if (profileMemberSince && user.created_at) {
        const date = new Date(user.created_at);
        profileMemberSince.textContent = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long'
        });
    }
}

// Edit profile (placeholder)
function editProfile() {
    showMessage('Profile editing feature coming soon!', 'info');
}

// Change password (placeholder)
function changePassword() {
    showMessage('Password change feature coming soon!', 'info');
}

// ================ END CUSTOMER FUNCTIONS ================

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

// Export products to CSV
function exportProductsCSV() {
    // Show loading message
    showMessage('Preparing CSV export for Excel...', 'info');

    try {
        // Get current filters from the search/filter form
        const searchInput = document.getElementById('search-input');
        const categoryFilter = document.getElementById('category-filter');
        const minPrice = document.getElementById('min-price');
        const maxPrice = document.getElementById('max-price');
        const inStockOnly = document.getElementById('in-stock-only');
        const lowStockOnly = document.getElementById('low-stock-only');
        const sortBy = document.getElementById('sort-by');
        const sortOrder = document.getElementById('sort-order');

        // Build parameters for export
        const params = new URLSearchParams();

        if (searchInput && searchInput.value.trim()) {
            params.append('search', searchInput.value.trim());
        }

        if (categoryFilter && categoryFilter.value) {
            params.append('category', categoryFilter.value);
        }

        if (minPrice && minPrice.value) {
            params.append('min_price', minPrice.value);
        }

        if (maxPrice && maxPrice.value) {
            params.append('max_price', maxPrice.value);
        }

        if (inStockOnly && inStockOnly.checked) {
            params.append('in_stock_only', '1');
        }

        if (lowStockOnly && lowStockOnly.checked) {
            params.append('low_stock_only', '1');
        }

        if (sortBy && sortBy.value) {
            params.append('sort_by', sortBy.value);
        }

        if (sortOrder && sortOrder.value) {
            params.append('sort_order', sortOrder.value);
        }

        // Create download link with proper Excel-compatible filename
        const link = document.createElement('a');
        const timestamp = new Date().toISOString().split('T')[0];
        const timeString = new Date().toLocaleTimeString('en-GB', { hour12: false }).replace(/:/g, '-');
        const hasFilters = params.toString().length > 0;
        const filename = hasFilters ?
            `filtered_products_${timestamp}_${timeString}.csv` :
            `all_products_${timestamp}_${timeString}.csv`;

        link.href = `api/export.php?${params.toString()}`;
        link.download = filename;
        link.style.display = 'none';

        // Add to DOM, click, and remove
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Success message with additional info
        const filterInfo = hasFilters ? ' (with current filters applied)' : ' (all products)';
        showMessage(`CSV export started successfully${filterInfo}! File ready for Excel.`, 'success');

        // Log export action for debugging
        console.log(`CSV Export: ${filename}`, {
            filters: Object.fromEntries(params.entries()),
            timestamp: new Date().toISOString()
        });

    } catch (error) {
        console.error('Export error:', error);
        showMessage('Failed to export products. Please check your connection and try again.', 'error');
    }
}

// Analytics functionality
function loadAnalytics() {
    // Show loading state
    const analyticsSection = document.getElementById('analytics');
    if (analyticsSection) {
        // Add loading class if available
        analyticsSection.classList.add('loading');
    }

    fetch('api/analytics.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateAnalyticsDisplay(data.analytics);

                // Initialize charts if chart manager is available
                if (typeof chartManager !== 'undefined' && chartManager) {
                    if (data.analytics.products) {
                        chartManager.updateStockChart(data.analytics.products);
                    }
                    if (data.analytics.price_analysis) {
                        chartManager.updatePriceChart(data.analytics.price_analysis);
                    }
                }
            } else {
                console.error('Analytics API error:', data.message);
                showMessage('Failed to load analytics: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Analytics error:', error);
            showMessage('Failed to load analytics. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            // Remove loading state
            if (analyticsSection) {
                analyticsSection.classList.remove('loading');
            }
        });
}

function updateAnalyticsDisplay(analytics) {
    // Helper function to safely update element content
    function safeUpdateElement(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    // Update product stats
    if (analytics.products) {
        safeUpdateElement('analytics-total-products', analytics.products.total_products);
        safeUpdateElement('analytics-in-stock', analytics.products.in_stock_products);
        safeUpdateElement('analytics-low-stock', analytics.products.low_stock_products);
        safeUpdateElement('analytics-inventory-value', analytics.products.total_inventory_value);

        // Update KPI cards if they exist
        safeUpdateElement('kpi-total-products', analytics.products.total_products);
        safeUpdateElement('kpi-in-stock', analytics.products.in_stock_products);
        safeUpdateElement('kpi-low-stock', analytics.products.low_stock_products);
        safeUpdateElement('kpi-inventory-value', analytics.products.total_inventory_value);
    }

    // Update price analysis
    if (analytics.price_analysis) {
        const priceAnalysis = analytics.price_analysis;
        safeUpdateElement('analytics-avg-price', priceAnalysis.avg_price);
        safeUpdateElement('analytics-price-range', `${priceAnalysis.min_price} - ${priceAnalysis.max_price}`);

        safeUpdateElement('price-under-1k', priceAnalysis.under_1k || 0);
        safeUpdateElement('price-1k-5k', priceAnalysis.between_1k_5k || 0);
        safeUpdateElement('price-5k-10k', priceAnalysis.between_5k_10k || 0);
        safeUpdateElement('price-over-10k', priceAnalysis.over_10k || 0);

        // Update KPI price elements
        safeUpdateElement('kpi-avg-price', priceAnalysis.avg_price);
        safeUpdateElement('kpi-min-price', priceAnalysis.min_price);
        safeUpdateElement('kpi-max-price', priceAnalysis.max_price);
    }

    // Update stock analysis
    if (analytics.stock_analysis) {
        const stockAnalysis = analytics.stock_analysis;
        safeUpdateElement('analytics-total-stock', stockAnalysis.total_stock || 0);

        safeUpdateElement('stock-out', stockAnalysis.out_of_stock || 0);
        safeUpdateElement('stock-low', stockAnalysis.low_stock || 0);
        safeUpdateElement('stock-medium', stockAnalysis.medium_stock || 0);
        safeUpdateElement('stock-high', stockAnalysis.high_stock || 0);
    }

    // Update category breakdown
    if (analytics.categories) {
        updateCategoryBreakdown(analytics.categories);
    }

    // Load low stock alerts
    loadLowStockAlerts();
}

function updateCategoryBreakdown(categories) {
    const container = document.getElementById('category-breakdown');
    if (!container) return;

    container.innerHTML = '';

    categories.slice(0, 5).forEach(category => {
        const categoryItem = document.createElement('div');
        categoryItem.className = 'category-item';
        categoryItem.innerHTML = `
            <div class="category-info">
                <span class="category-name">${category.category}</span>
                <span class="category-count">${category.product_count} products</span>
            </div>
            <div class="category-value">${category.category_value || 'KSh 0'}</div>
        `;
        container.appendChild(categoryItem);
    });
}


function loadLowStockAlerts() {
    fetch('api/analytics.php?type=low-stock&threshold=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateLowStockTable(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading low stock alerts:', error);
        });
}

function updateLowStockTable(products) {
    const tbody = document.getElementById('low-stock-tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No low stock items</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.name}</td>
            <td><span class="category-badge">${product.category}</span></td>
            <td><span class="stock-badge ${product.stock <= 5 ? 'critical' : 'low'}">${product.stock}</span></td>
            <td>${product.formatted_price || formatKSh(product.price)}</td>
            <td>${product.formatted_remaining_value || formatKSh(product.price * product.stock)}</td>
            <td>
                <button class="btn btn-sm btn-warning" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function loadLowStockProducts() {

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

// Enhanced pagination functions
function changePage(direction) {
    const newPage = currentPage + direction;
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        performSearch();
    }
}

function goToFirstPage() {
    if (currentPage !== 1) {
        currentPage = 1;
        performSearch();
    }
}

function goToLastPage() {
    if (currentPage !== totalPages && totalPages > 0) {
        currentPage = totalPages;
        performSearch();
    }
}

function jumpToPage() {
    const pageInput = document.getElementById('page-jump-input');
    const page = parseInt(pageInput.value);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        performSearch();
        pageInput.value = '';
    }
}

function handlePageJump(event) {
    if (event.key === 'Enter') {
        jumpToPage();
    }
}

function changeProductsPerPage() {
    const select = document.getElementById('products-per-page');
    productsPerPage = parseInt(select.value);
    currentPage = 1; // Reset to first page
    performSearch();
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        performSearch();
    }
}

function updatePageInfo() {
    // This function is kept for backward compatibility
    updateProductsPagination();
}

// Update Products KPI Cards
function updateProductsKPI(stats) {
    // Helper function to safely update elements
    function safeUpdate(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    // Helper function to animate value changes
    function animateValue(element, start, end, duration = 1000) {
        if (!element) return;

        const startTime = performance.now();
        const startValue = parseInt(start) || 0;
        const endValue = parseInt(end) || 0;

        function updateValue(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const currentValue = Math.floor(startValue + (endValue - startValue) * progress);
            element.textContent = currentValue;

            if (progress < 1) {
                requestAnimationFrame(updateValue);
            }
        }

        requestAnimationFrame(updateValue);
    }

    if (stats) {
        // Update basic stats
        safeUpdate('products-total-count', stats.total_products || 0);
        safeUpdate('products-total-stock', stats.total_stock || 0);
        safeUpdate('products-inventory-value', stats.total_inventory_value || 'KSh 0');
        safeUpdate('products-avg-price', stats.avg_price || 'KSh 0');
        safeUpdate('products-categories-count', stats.categories_count || 0);
        safeUpdate('products-low-stock-count', stats.low_stock_products || 0);

        // Update global stats for comparison
        productsStats = {
            totalCount: stats.total_products || 0,
            totalStock: stats.total_stock || 0,
            inventoryValue: parseFloat((stats.total_inventory_value || '0').replace(/[^\d.]/g, '')),
            avgPrice: parseFloat((stats.avg_price || '0').replace(/[^\d.]/g, '')),
            categoriesCount: stats.categories_count || 0,
            lowStockCount: stats.low_stock_products || 0
        };

        // Update trend indicators (simplified for now)
        updateTrendIndicators();
    }
}

// Update trend indicators
function updateTrendIndicators() {
    // This is a simplified version - in a real app, you'd compare with historical data
    const trends = [
        { id: 'products-trend', value: '+2.1%', type: 'positive' },
        { id: 'stock-trend', value: '-1.5%', type: 'negative' },
        { id: 'value-trend', value: '+5.3%', type: 'positive' },
        { id: 'price-trend', value: '+0.8%', type: 'positive' },
        { id: 'categories-trend', value: '0%', type: 'neutral' },
        { id: 'low-stock-trend', value: productsStats.lowStockCount > 0 ? 'Alert' : 'Clear', type: productsStats.lowStockCount > 0 ? 'warning' : 'positive' }
    ];

    trends.forEach(trend => {
        const element = document.getElementById(trend.id);
        if (element) {
            element.textContent = trend.value;
            element.className = `trend-indicator ${trend.type}`;

            // Update icon based on type
            const icon = element.querySelector('i');
            if (icon) {
                icon.className = trend.type === 'positive' ? 'fas fa-arrow-up' :
                    trend.type === 'negative' ? 'fas fa-arrow-down' :
                        trend.type === 'warning' ? 'fas fa-exclamation' : 'fas fa-minus';
            }
        }
    });
}

// Enhanced pagination display
function updateProductsPagination(stats) {
    if (!stats) return;

    totalProducts = stats.total_products || 0;
    totalPages = Math.ceil(totalProducts / productsPerPage);

    // Update pagination info
    const showingStart = totalProducts > 0 ? ((currentPage - 1) * productsPerPage) + 1 : 0;
    const showingEnd = Math.min(currentPage * productsPerPage, totalProducts);

    document.getElementById('products-showing-start').textContent = showingStart;
    document.getElementById('products-showing-end').textContent = showingEnd;
    document.getElementById('products-total-results').textContent = totalProducts;

    // Update button states
    document.getElementById('products-first-page').disabled = currentPage === 1;
    document.getElementById('products-prev-page').disabled = currentPage === 1;
    document.getElementById('products-next-page').disabled = currentPage >= totalPages;
    document.getElementById('products-last-page').disabled = currentPage >= totalPages;

    // Update page jump input max value
    const pageJumpInput = document.getElementById('page-jump-input');
    if (pageJumpInput) {
        pageJumpInput.max = totalPages;
        pageJumpInput.placeholder = `1-${totalPages}`;
    }

    // Generate page numbers
    generatePageNumbers();
}

// Generate page number buttons
function generatePageNumbers() {
    const pageNumbersContainer = document.getElementById('products-page-numbers');
    if (!pageNumbersContainer) return;

    pageNumbersContainer.innerHTML = '';

    if (totalPages <= 1) return;

    const maxVisiblePages = 7;
    let startPage, endPage;

    if (totalPages <= maxVisiblePages) {
        startPage = 1;
        endPage = totalPages;
    } else {
        const maxPagesBeforeCurrentPage = Math.floor(maxVisiblePages / 2);
        const maxPagesAfterCurrentPage = Math.ceil(maxVisiblePages / 2) - 1;

        if (currentPage <= maxPagesBeforeCurrentPage) {
            startPage = 1;
            endPage = maxVisiblePages;
        } else if (currentPage + maxPagesAfterCurrentPage >= totalPages) {
            startPage = totalPages - maxVisiblePages + 1;
            endPage = totalPages;
        } else {
            startPage = currentPage - maxPagesBeforeCurrentPage;
            endPage = currentPage + maxPagesAfterCurrentPage;
        }
    }

    // Add first page and ellipsis if needed
    if (startPage > 1) {
        pageNumbersContainer.appendChild(createPageButton(1));
        if (startPage > 2) {
            pageNumbersContainer.appendChild(createEllipsis());
        }
    }

    // Add page number buttons
    for (let i = startPage; i <= endPage; i++) {
        pageNumbersContainer.appendChild(createPageButton(i));
    }

    // Add ellipsis and last page if needed
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            pageNumbersContainer.appendChild(createEllipsis());
        }
        pageNumbersContainer.appendChild(createPageButton(totalPages));
    }
}

// Create page button element
function createPageButton(page) {
    const button = document.createElement('button');
    button.className = `page-number ${page === currentPage ? 'active' : ''}`;
    button.textContent = page;
    button.onclick = () => goToPage(page);
    return button;
}

// Create ellipsis element
function createEllipsis() {
    const span = document.createElement('span');
    span.className = 'page-number ellipsis';
    span.textContent = '...';
    return span;
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

// Global analytics variables
let chartManager = null;
let analyticsManager = null;

// Initialize analytics when needed
function initializeAnalytics() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Please ensure Chart.js is included before main.js');
        return;
    }

    if (!chartManager) {
        console.log('Initializing Chart Manager...');
        chartManager = new AnalyticsChartManager();
        chartManager.initCharts();
    }
    if (!analyticsManager) {
        console.log('Initializing Analytics Manager...');
        analyticsManager = new AnalyticsDataManager();
        analyticsManager.init();
    }
}

// Enhanced showSection function
const originalShowSection = showSection;
showSection = function (sectionId) {
    originalShowSection(sectionId);

    // Load specific data when sections are shown
    if (sectionId === 'analytics') {
        // Initialize analytics components first
        initializeAnalytics();
        // Then load data
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

// Modern Analytics Chart Management
class AnalyticsChartManager {
    constructor() {
        this.charts = {};
        this.chartColors = {
            primary: '#667eea',
            secondary: '#764ba2',
            success: '#27ae60',
            warning: '#f39c12',
            danger: '#e74c3c',
            info: '#3498db'
        };
    }

    // Initialize all charts
    initCharts() {
        try {
            console.log('Initializing charts...');
            this.initStockChart();
            this.initPriceChart();
            this.initTrendChart();
            console.log('Charts initialized successfully');
        } catch (error) {
            console.error('Error initializing charts:', error);
        }
    }

    // Stock Overview Chart
    initStockChart() {
        const ctx = document.getElementById('stock-chart');
        if (!ctx) {
            console.warn('Stock chart canvas not found');
            return;
        }

        // Destroy previous chart if exists
        if (this.charts.stock) {
            this.charts.stock.destroy();
        }
        console.log('Creating stock chart...');
        this.charts.stock = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        this.chartColors.success,
                        this.chartColors.warning,
                        this.chartColors.danger
                    ],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    duration: 1000
                }
            }
        });
    }

    // Price Distribution Chart
    initPriceChart() {
        const ctx = document.getElementById('price-chart');
        if (!ctx) {
            console.warn('Price chart canvas not found');
            return;
        }

        console.log('Creating price chart...');
        this.charts.price = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Under 1K', '1K-5K', '5K-10K', 'Over 10K'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        this.chartColors.danger,
                        this.chartColors.warning,
                        this.chartColors.info,
                        this.chartColors.success
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    // Inventory Trend Chart
    initTrendChart() {
        const ctx = document.getElementById('trend-chart');
        if (!ctx) {
            console.warn('Trend chart canvas not found');
            return;
        }

        console.log('Creating trend chart...');
        // Generate sample trend data for last 30 days
        const trendData = this.generateTrendData();

        this.charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    label: 'Inventory Value',
                    data: trendData.values,
                    borderColor: this.chartColors.primary,
                    backgroundColor: `${this.chartColors.primary}20`,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: this.chartColors.primary,
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => {
                                return `Value: KSh ${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            callback: function (value) {
                                return 'KSh ' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    // Generate sample trend data
    generateTrendData() {
        const labels = [];
        const values = [];
        const now = new Date();

        for (let i = 29; i >= 0; i--) {
            const date = new Date(now);
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));

            // Generate realistic trend data with some variation
            const baseValue = 250000 + (Math.sin(i / 10) * 50000);
            const variation = (Math.random() - 0.5) * 30000;
            values.push(Math.max(0, Math.round(baseValue + variation)));
        }

        return { labels, values };
    }

    // Update stock chart data
    updateStockChart(data) {
        if (!this.charts.stock) return;

        const inStock = data.in_stock_products || 0;
        const lowStock = data.low_stock_products || 0;
        const outOfStock = (data.total_products || 0) - inStock - lowStock;

        this.charts.stock.data.datasets[0].data = [inStock, lowStock, outOfStock];
        this.charts.stock.update('active');

        // Update legend values with null checks
        const inStockElement = document.getElementById('analytics-in-stock');
        const lowStockElement = document.getElementById('legend-low-stock');
        const outStockElement = document.getElementById('legend-out-stock');

        if (inStockElement) inStockElement.textContent = inStock;
        if (lowStockElement) lowStockElement.textContent = lowStock;
        if (outStockElement) outStockElement.textContent = outOfStock;
    }

    // Update price chart data
    updatePriceChart(priceAnalysis) {
        if (!this.charts.price) return;

        const data = [
            priceAnalysis.under_1k || 0,
            priceAnalysis.between_1k_5k || 0,
            priceAnalysis.between_5k_10k || 0,
            priceAnalysis.over_10k || 0
        ];

        this.charts.price.data.datasets[0].data = data;
        this.charts.price.update('active');

        // Update price stats with null checks
        const priceUnder1k = document.getElementById('price-under-1k');
        const price1k5k = document.getElementById('price-1k-5k');
        const price5k10k = document.getElementById('price-5k-10k');
        const priceOver10k = document.getElementById('price-over-10k');

        if (priceUnder1k) priceUnder1k.textContent = data[0];
        if (price1k5k) price1k5k.textContent = data[1];
        if (price5k10k) price5k10k.textContent = data[2];
        if (priceOver10k) priceOver10k.textContent = data[3];
    }

    // Toggle chart type
    toggleChartType(chartId, newType) {
        const chart = this.charts[chartId.replace('-chart', '')];
        if (!chart) return;

        chart.config.type = newType;
        chart.update('active');
    }

    // Destroy all charts
    destroyCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
        this.charts = {};
    }
}

// Modern Analytics Data Manager
class AnalyticsDataManager {
    constructor() {
        this.chartManager = new AnalyticsChartManager();
        this.animationQueues = new Map();
    }

    // Initialize analytics
    init() {
        this.chartManager.initCharts();
        this.loadAnalytics();
    }

    // Load analytics data
    async loadAnalytics() {
        try {
            showMessage('Loading analytics...', 'info');

            const [mainData, categories, lowStock] = await Promise.all([
                fetch('api/analytics.php').then(r => r.json()),
                fetch('api/analytics.php?type=categories').then(r => r.json()),
                fetch('api/analytics.php?type=low-stock&threshold=10').then(r => r.json())
            ]);

            if (mainData.success) {
                this.updateAllDisplays(mainData.analytics);

                if (categories.success) {
                    this.updateCategoryPerformance(categories.data);
                }

                if (lowStock.success) {
                    this.updateLowStockAlerts(lowStock.data);
                }

                showMessage('Analytics loaded successfully!', 'success');
            } else {
                throw new Error(mainData.message || 'Failed to load analytics');
            }
        } catch (error) {
            console.error('Analytics loading error:', error);
            showMessage('Failed to load analytics', 'error');
        }
    }

    // Update all displays
    updateAllDisplays(analytics) {
        this.updateKPICards(analytics);
        this.updateCharts(analytics);
        this.generateInsights(analytics);
    }

    // Update KPI cards with animation
    updateKPICards(analytics) {
        if (analytics.products) {
            this.animateValue('analytics-total-products', analytics.products.total_products);
            this.animateValue('analytics-inventory-value', analytics.products.total_inventory_value, true);
        }

        if (analytics.price_analysis) {
            this.animateValue('analytics-avg-price', analytics.price_analysis.avg_price, true);
        }

        if (analytics.products) {
            this.animateValue('analytics-low-stock', analytics.products.low_stock_products);
        }
    }

    // Animate value changes
    animateValue(elementId, targetValue, isCurrency = false) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const startValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
        const numericTarget = parseInt(targetValue.toString().replace(/[^\d]/g, ''));

        if (startValue === numericTarget) return;

        const duration = 1500;
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const currentValue = Math.round(startValue + (numericTarget - startValue) * easeOut);

            if (isCurrency) {
                element.textContent = targetValue; // Use formatted currency from server
            } else {
                element.textContent = currentValue.toLocaleString();
            }

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    // Update charts
    updateCharts(analytics) {
        if (analytics.products) {
            this.chartManager.updateStockChart(analytics.products);
        }

        if (analytics.price_analysis) {
            this.chartManager.updatePriceChart(analytics.price_analysis);
        }
    }

    // Update category performance
    updateCategoryPerformance(categories) {
        const container = document.getElementById('category-breakdown');
        if (!container) return;

        container.innerHTML = '';

        const maxValue = Math.max(...categories.map(c => c.product_count));

        categories.slice(0, 5).forEach((category, index) => {
            const progressWidth = (category.product_count / maxValue) * 100;

            const categoryItem = document.createElement('div');
            categoryItem.className = 'category-item-modern';
            categoryItem.style.animationDelay = `${index * 100}ms`;

            categoryItem.innerHTML = `
                <div class="category-info-modern">
                    <div class="category-name-modern">${category.category}</div>
                    <div class="category-count-modern">${category.product_count} products</div>
                    <div class="category-progress">
                        <div class="category-progress-bar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="category-value-modern">${category.category_value || 'KSh 0'}</div>
            `;

            container.appendChild(categoryItem);

            // Animate progress bar
            setTimeout(() => {
                const progressBar = categoryItem.querySelector('.category-progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${progressWidth}%`;
                }
            }, index * 100 + 500);
        });
    }

    // Update low stock alerts
    updateLowStockAlerts(lowStockProducts) {
        this.updateLowStockPreview(lowStockProducts);
        this.updateAlertCounts(lowStockProducts);
    }

    // Update low stock preview
    updateLowStockPreview(products) {
        const container = document.getElementById('low-stock-preview');
        if (!container) return;

        container.innerHTML = '';

        if (products.length === 0) {
            container.innerHTML = '<div class="low-stock-item"><span>No low stock items</span></div>';
            return;
        }

        products.slice(0, 3).forEach(product => {
            const item = document.createElement('div');
            item.className = 'low-stock-item';
            item.innerHTML = `
                <span class="low-stock-name">${product.name}</span>
                <span class="low-stock-count">${product.stock} left</span>
            `;
            container.appendChild(item);
        });

        if (products.length > 3) {
            const moreItem = document.createElement('div');
            moreItem.className = 'low-stock-item';
            moreItem.innerHTML = `
                <span class="low-stock-name">and ${products.length - 3} more...</span>
                <span class="low-stock-count"></span>
            `;
            container.appendChild(moreItem);
        }
    }

    // Update alert counts
    updateAlertCounts(lowStockProducts) {
        const alertCount = document.getElementById('alert-count');
        const lowStockCount = document.getElementById('low-stock-count');

        if (alertCount) alertCount.textContent = lowStockProducts.length;
        if (lowStockCount) lowStockCount.textContent = lowStockProducts.length;
    }

    // Generate insights
    generateInsights(analytics) {
        const insights = [];

        // Stock insights
        if (analytics.products && analytics.products.low_stock_products > 0) {
            insights.push(`${analytics.products.low_stock_products} products need restocking`);
        }

        // Price insights
        if (analytics.price_analysis) {
            const priceAnalysis = analytics.price_analysis;
            if (priceAnalysis.under_1k > priceAnalysis.over_10k * 2) {
                insights.push('Consider adding premium product lines');
            }
        }

        this.updateInsights('price-insights', insights.slice(0, 2));
        this.updateInsights('category-insights', ['Top categories performing well', 'Inventory distribution balanced']);
    }

    // Update insights display
    updateInsights(containerId, insights) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';

        insights.forEach(insight => {
            const item = document.createElement('div');
            item.className = 'insight-item';
            item.textContent = insight;
            container.appendChild(item);
        });
    }

    // Refresh analytics
    async refresh() {
        await this.loadAnalytics();
    }
}

// Modern Analytics Functions
function initModernAnalytics() {
    if (!analyticsManager) {
        analyticsManager = new AnalyticsDataManager();
        analyticsManager.init();
    }
}

// Manual chart initialization function (for debugging)
window.initCharts = function () {
    console.log('Manual chart initialization...');
    initializeAnalytics();
    if (chartManager) {
        chartManager.initCharts();
    }
};

// Export analytics function
function exportAnalytics() {
    // Create CSV data
    const csvData = generateAnalyticsCSV();

    // Create download link
    const blob = new Blob([csvData], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `analytics-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);

    showMessage('Analytics exported successfully!', 'success');
}

// Generate CSV data
function generateAnalyticsCSV() {
    const headers = ['Metric', 'Value', 'Timestamp'];
    const timestamp = new Date().toISOString();

    const rows = [
        headers.join(','),
        `Total Products,${document.getElementById('analytics-total-products')?.textContent || 0},${timestamp}`,
        `Inventory Value,${document.getElementById('analytics-inventory-value')?.textContent || 'KSh 0'},${timestamp}`,
        `Average Price,${document.getElementById('analytics-avg-price')?.textContent || 'KSh 0'},${timestamp}`,
        `Low Stock Items,${document.getElementById('analytics-low-stock')?.textContent || 0},${timestamp}`
    ];

    return rows.join('\n');
}

// Toggle chart type function
function toggleChartType(chartId, newType) {
    if (analyticsManager && analyticsManager.chartManager) {
        analyticsManager.chartManager.toggleChartType(chartId, newType);
    }
}

// Show low stock details
function showLowStockDetails() {
    const detailsSection = document.getElementById('low-stock-detailed');
    if (detailsSection) {
        detailsSection.style.display = 'block';
        detailsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Hide detailed view
function hideDetailedView() {
    const detailsSection = document.getElementById('low-stock-detailed');
    if (detailsSection) {
        detailsSection.style.display = 'none';
    }
}

// Enhanced refresh analytics function
function refreshAnalytics() {
    if (analyticsManager) {
        analyticsManager.refresh();
    } else {
        // Fallback to original function
        loadAnalytics();
    }
}

// Modern Dashboard Functions
function refreshDashboard() {
    showMessage('Refreshing dashboard...', 'info');
    loadDashboardStats();
    loadDashboardProducts();
    loadLowStockAlerts();

    // Add loading animation to KPI cards
    const kpiCards = document.querySelectorAll('.kpi-card');
    kpiCards.forEach(card => {
        card.style.opacity = '0.7';
        setTimeout(() => {
            card.style.opacity = '1';
        }, 500);
    });

    setTimeout(() => {
        showMessage('Dashboard refreshed successfully!', 'success');
    }, 1000);
}

function exportData() {
    // Placeholder for export functionality
    showMessage('Export functionality coming soon!', 'info');

    // In a real application, this would export data as CSV/Excel
    // For now, we'll just show a mock implementation
    setTimeout(() => {
        showMessage('Data exported successfully!', 'success');
    }, 2000);
}

// Enhanced dashboard stats loading with animations
function loadDashboardStats() {
    fetch('api/dashboard.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animate KPI values
                animateValue('total-products', 0, data.totalProducts || 0);
                animateValue('inventory-value', 0, parseFloat((data.totalValue || '0').replace(/[^\d.]/g, '')));
                animateValue('low-stock-count', 0, data.lowStock || 0);
                animateValue('categories', 0, data.categories || 0);
                animateValue('avg-price', 0, parseFloat((data.avgPrice || '0').replace(/[^\d.]/g, '')));
                animateValue('total-stock', 0, data.totalStock || 0);

                // Update additional metrics
                updateRevenueMetrics(data);
                updateStockMetrics(data);
                updatePriceDistribution(data);

                // Update progress bars with animation
                updateProgressBars(data);

                // Update category distribution if available
                if (data.categoryBreakdown) {
                    updateCategoryChart(data.categoryBreakdown);
                    updateDashboardCategoryBreakdown(data.categoryBreakdown);
                }

                // Load additional dashboard sections
                loadDashboardLowStock();
                loadDashboardProducts();
            }
        })
        .catch(error => {
            console.error('Error loading dashboard stats:', error);
        });
}

// Animate number values
function animateValue(elementId, start, end, duration = 2000) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const startValue = parseInt(start) || 0;
    const endValue = parseInt(end) || 0;
    const startTime = performance.now();

    function updateValue(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function for smooth animation
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const currentValue = Math.floor(startValue + (endValue - startValue) * easeOutQuart);

        if (elementId.includes('value') || elementId.includes('price')) {
            element.textContent = formatKSh(currentValue);
        } else {
            element.textContent = currentValue;
        }

        if (progress < 1) {
            requestAnimationFrame(updateValue);
        }
    }

    requestAnimationFrame(updateValue);
}

// Update revenue metrics
function updateRevenueMetrics(data) {
    const todayPotential = parseFloat((data.totalValue || '0').replace(/[^\d.]/g, ''));
    const weekPotential = todayPotential * 7;
    const monthPotential = todayPotential * 30;

    setTimeout(() => {
        const todayEl = document.getElementById('today-potential');
        const weekEl = document.getElementById('week-potential');
        const monthEl = document.getElementById('month-potential');

        if (todayEl) todayEl.textContent = formatKSh(todayPotential);
        if (weekEl) weekEl.textContent = formatKSh(weekPotential);
        if (monthEl) monthEl.textContent = formatKSh(monthPotential);
    }, 500);
}

// Update stock metrics
function updateStockMetrics(data) {
    setTimeout(() => {
        const inStockEl = document.getElementById('dashboard-in-stock');
        const lowStockEl = document.getElementById('dashboard-low-stock');
        const outStockEl = document.getElementById('dashboard-out-stock');

        if (inStockEl) inStockEl.textContent = data.inStock || 0;
        if (lowStockEl) lowStockEl.textContent = data.lowStock || 0;
        if (outStockEl) outStockEl.textContent = data.outOfStock || 0;
    }, 300);
}

// Update price distribution
function updatePriceDistribution(data) {
    setTimeout(() => {
        const under1kEl = document.getElementById('dashboard-price-under-1k');
        const between1k5kEl = document.getElementById('dashboard-price-1k-5k');
        const over5kEl = document.getElementById('dashboard-price-over-5k');

        if (under1kEl) under1kEl.textContent = data.priceUnder1k || 0;
        if (between1k5kEl) between1k5kEl.textContent = data.price1k5k || 0;
        if (over5kEl) over5kEl.textContent = data.priceOver5k || 0;
    }, 700);
}

// Update progress bars with animation
function updateProgressBars(data) {
    const progressBars = document.querySelectorAll('.progress-bar');

    progressBars.forEach((bar, index) => {
        setTimeout(() => {
            // Calculate progress based on data (mock calculation)
            const progress = Math.min(((index + 1) * 15 + Math.random() * 20), 100);
            bar.style.width = progress + '%';
        }, index * 200);
    });
}

// Enhanced low stock alerts
function loadLowStockAlerts() {
    fetch('api/dashboard.php?type=low-stock')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateLowStockAlerts(data.products || []);
                document.getElementById('alert-count').textContent = data.products?.length || 0;
            }
        })
        .catch(error => {
            console.error('Error loading low stock alerts:', error);
        });
}

// Update low stock alerts display
function updateLowStockAlerts(products) {
    const alertList = document.getElementById('dashboard-low-stock-tbody');
    if (!alertList) return;

    alertList.innerHTML = '';

    if (products.length === 0) {
        alertList.innerHTML = '<div class="alert-item">No low stock alerts</div>';
        return;
    }

    products.slice(0, 5).forEach(product => {
        const alertItem = document.createElement('div');
        alertItem.className = 'alert-item';
        alertItem.innerHTML = `
            <div class="alert-product">
                <strong>${product.name}</strong>
                <span class="alert-category">${product.category}</span>
            </div>
            <div class="alert-stock">
                <span class="stock-count ${product.stock <= 5 ? 'critical' : 'low'}">${product.stock} left</span>
                <button class="btn btn-sm btn-warning" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i> Update
                </button>
            </div>
        `;
        alertList.appendChild(alertItem);
    });
}

// Initialize dashboard charts (if Chart.js is available)
function initializeDashboardCharts() {
    // Stock Chart
    const stockCtx = document.getElementById('stockChart');
    if (stockCtx && typeof Chart !== 'undefined') {
        new Chart(stockCtx, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [60, 25, 15],
                    backgroundColor: ['#4caf50', '#ffa726', '#f44336'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Category Chart - Will be created dynamically with real data
    // Static chart removed to prevent conflicts with dynamic chart
}

// Update category chart with real data
function updateCategoryChart(categoryData) {
    const categoryCtx = document.getElementById('categoryChart');
    if (!categoryCtx || typeof Chart === 'undefined' || !categoryData) return;

    // Extract data for chart
    const labels = categoryData.map(cat => cat.category || 'Unknown');
    const data = categoryData.map(cat => cat.product_count || 0);
    const colors = [
        '#667eea', '#56cc9d', '#ffa726', '#4facfe', '#f093fb',
        '#ff7979', '#6c5ce7', '#fdcb6e', '#e17055', '#fd79a8'
    ];

    // Destroy existing chart if it exists
    if (window.categoryChart && typeof window.categoryChart.destroy === 'function') {
        window.categoryChart.destroy();
    }

    // Create new chart with real data
    window.categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Enhanced showSection with dashboard initialization
function enhancedShowSection(sectionId) {
    showSection(sectionId);

    // Initialize dashboard features when showing dashboard
    if (sectionId === 'dashboard') {
        setTimeout(() => {
            initializeDashboardCharts();
            refreshDashboard();
        }, 100);
    }
}

// Modern analytics is already initialized through the enhanced showSection function above
