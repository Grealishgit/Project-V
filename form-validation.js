// Enhanced form validation and UX improvements for add-product
function validateProductForm() {
    const form = document.getElementById('product-form');
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

// Add to main.js after the handleProductSubmit function
