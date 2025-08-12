<?php
/**
 * Admin Login Page
 */

// Start output buffering to prevent header errors
ob_start();

require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = ERROR_MESSAGES['csrf_invalid'];
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        $result = attemptLogin($username, $password, $remember);
        
        if ($result['success']) {
            // Clear any output before redirect
            ob_end_clean();
            header('Location: index.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = SUCCESS_MESSAGES['logout_success'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DCS Statistics</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background-color: #2a2a2a;
            border-radius: 8px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #888;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: #ccc;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            background-color: #1a1a1a;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-login:hover {
            background-color: #45a049;
        }
        
        .btn-login:active {
            transform: translateY(1px);
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #f44336;
            color: white;
        }
        
        .alert-success {
            background-color: #4CAF50;
            color: white;
        }
        
        .footer-links {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
        }
        
        .footer-links a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .security-notice {
            margin-top: 20px;
            padding: 15px;
            background-color: #1a1a1a;
            border-radius: 4px;
            font-size: 12px;
            color: #888;
            text-align: center;
        }
        
        .security-notice .lock-icon {
            font-size: 16px;
            color: #4CAF50;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>DCS Statistics Management Panel</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= csrfField() ?>
            
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required autofocus 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Remember me for 30 days</label>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <div class="footer-links">
            <a href="<?php echo dirname(dirname($_SERVER['SCRIPT_NAME'])) . '../index.php'; ?>">← Back to Statistics</a>
        </div>
        
        <div class="security-notice">
            <span class="lock-icon">🔒</span>
            This is a secure area. All activities are logged.
        </div>
    </div>
    
    <script>
        // Add some client-side enhancements
        document.getElementById('username').focus();
        
        // Show password toggle (optional enhancement)
        const passwordInput = document.getElementById('password');
        const togglePassword = document.createElement('span');
        togglePassword.innerHTML = '👁';
        togglePassword.style.position = 'absolute';
        togglePassword.style.right = '10px';
        togglePassword.style.top = '50%';
        togglePassword.style.transform = 'translateY(-50%)';
        togglePassword.style.cursor = 'pointer';
        togglePassword.style.userSelect = 'none';
        
        passwordInput.parentElement.style.position = 'relative';
        passwordInput.parentElement.appendChild(togglePassword);
        
        let showPassword = false;
        togglePassword.addEventListener('click', () => {
            showPassword = !showPassword;
            passwordInput.type = showPassword ? 'text' : 'password';
            togglePassword.innerHTML = showPassword ? '👁‍🗨' : '👁';
        });
    </script>
</body>
</html>