<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Product Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .auth-container {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 500px;
        margin: 20px;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .auth-header h1 {
        color: #333;
        margin-bottom: 10px;
        font-size: 2rem;
    }

    .auth-header p {
        color: #666;
        font-size: 0.9rem;
    }

    .auth-tabs {
        display: flex;
        border-bottom: 2px solid #f0f0f0;
        margin-bottom: 30px;
    }

    .auth-tab {
        flex: 1;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        border: none;
        background: none;
        font-size: 1rem;
        color: #666;
        transition: all 0.3s ease;
    }

    .auth-tab.active {
        color: #667eea;
        border-bottom: 2px solid #667eea;
    }

    .auth-form {
        display: none;
    }

    .auth-form.active {
        display: block;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .auth-btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.3s ease;
        margin-top: 10px;
    }

    .auth-btn:hover {
        transform: translateY(-2px);
    }

    .auth-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .alert {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: none;
    }

    .alert.error {
        background: #fee;
        border: 1px solid #fcc;
        color: #c33;
    }

    .alert.success {
        background: #efe;
        border: 1px solid #cfc;
        color: #3c3;
    }

    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #fff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .password-strength {
        margin-top: 5px;
        font-size: 0.8rem;
    }

    .strength-weak {
        color: #e74c3c;
    }

    .strength-medium {
        color: #f39c12;
    }

    .strength-strong {
        color: #27ae60;
    }

    .forgot-password {
        text-align: center;
        margin-top: 20px;
    }

    .forgot-password a {
        color: #667eea;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .forgot-password a:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-chart-line"></i> Admin Panel</h1>
            <p>Product Management System</p>
        </div>

        <div class="auth-tabs">
            <button class="auth-tab active" onclick="showAuthForm('login')">Login</button>
            <button class="auth-tab" onclick="showAuthForm('register')">Register</button>
        </div>

        <div id="alert-container"></div>

        <!-- Login Form -->
        <form id="login-form" class="auth-form active">
            <div class="form-group">
                <label for="login-username">Username or Email</label>
                <input type="text" id="login-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit" class="auth-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <!-- Register Form -->
        <form id="register-form" class="auth-form">
            <div class="form-group">
                <label for="register-username">Username</label>
                <input type="text" id="register-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="register-email">Email</label>
                <input type="email" id="register-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="register-fullname">Full Name</label>
                <input type="text" id="register-fullname" name="full_name">
            </div>
            <div class="form-group">
                <label for="register-password">Password</label>
                <input type="password" id="register-password" name="password" required>
                <div id="password-strength" class="password-strength"></div>
            </div>
            <div class="form-group">
                <label for="register-confirm-password">Confirm Password</label>
                <input type="password" id="register-confirm-password" name="confirm_password" required>
            </div>
            <button type="submit" class="auth-btn">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <div class="forgot-password">
            <a href="#" onclick="showForgotPassword()">Forgot Password?</a>
        </div>
    </div>

    <script>
    // Show/Hide auth forms
    function showAuthForm(type) {
        // Remove active class from all tabs and forms
        document.querySelectorAll('.auth-tab').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));

        // Add active class to selected tab and form
        document.querySelector(`[onclick="showAuthForm('${type}')"]`).classList.add('active');
        document.getElementById(`${type}-form`).classList.add('active');

        // Clear alerts
        clearAlerts();
    }

    // Show alert messages
    function showAlert(message, type = 'error') {
        const alertContainer = document.getElementById('alert-container');
        alertContainer.innerHTML = `
                <div class="alert ${type}">
                    ${message}
                </div>
            `;
        alertContainer.querySelector('.alert').style.display = 'block';
    }

    // Clear alerts
    function clearAlerts() {
        document.getElementById('alert-container').innerHTML = '';
    }

    // Password strength checker
    function checkPasswordStrength(password) {
        const strengthDiv = document.getElementById('password-strength');

        if (password.length === 0) {
            strengthDiv.textContent = '';
            return;
        }

        let strength = 0;

        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;

        // Character variety checks
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        if (strength < 3) {
            strengthDiv.textContent = 'Weak password';
            strengthDiv.className = 'password-strength strength-weak';
        } else if (strength < 5) {
            strengthDiv.textContent = 'Medium password';
            strengthDiv.className = 'password-strength strength-medium';
        } else {
            strengthDiv.textContent = 'Strong password';
            strengthDiv.className = 'password-strength strength-strong';
        }
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Password strength checker
        document.getElementById('register-password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        // Login form submission
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            try {
                btn.innerHTML = '<div class="loading"></div> Logging in...';
                btn.disabled = true;

                const formData = new FormData(this);
                formData.append('action', 'login');

                const response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showAlert(result.error || result.message);
                }
            } catch (error) {
                console.error('Login error:', error);
                showAlert('Login failed. Please try again.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // Register form submission
        document.getElementById('register-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            try {
                btn.innerHTML = '<div class="loading"></div> Registering...';
                btn.disabled = true;

                const formData = new FormData(this);
                const password = formData.get('password');
                const confirmPassword = formData.get('confirm_password');

                // Check if passwords match
                if (password !== confirmPassword) {
                    showAlert('Passwords do not match');
                    return;
                }

                formData.append('action', 'register');

                const response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Registration successful! You can now login.', 'success');
                    setTimeout(() => {
                        showAuthForm('login');
                    }, 2000);
                } else {
                    showAlert(result.error || result.message);
                }
            } catch (error) {
                console.error('Registration error:', error);
                showAlert('Registration failed. Please try again.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    });

    function showForgotPassword() {
        showAlert('Password reset functionality will be implemented soon.', 'success');
    }
    </script>
</body>

</html>