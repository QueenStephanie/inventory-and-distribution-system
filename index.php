<?php
/**
 * Login Page
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once 'config/database.php';
require_once 'config/auth.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    $redirectUrl = getDashboardUrl($_SESSION['role']);
    header("Location: $redirectUrl");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDBConnection();
        $result = loginUser($conn, $username, $password);
        closeDBConnection($conn);
        
        if ($result['success']) {
            header("Location: " . $result['redirect']);
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo">
                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="60" height="60" rx="10" fill="#FF6B35"/>
                        <path d="M30 15L42 25V45H18V25L30 15Z" fill="white"/>
                        <rect x="25" y="35" width="10" height="10" fill="#FF6B35"/>
                    </svg>
                </div>
                <h1>Inventory Management System</h1>
                <p>Multi-Branch Stock Distribution</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm1 15H9v-2h2v2zm0-4H9V5h2v6z"/>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-1 15l-5-5 1.41-1.41L9 12.17l7.59-7.59L18 6l-9 9z"/>
                    </svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Enter your username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 0C3.58 0 0 3.58 0 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/>
                        <path d="M11.5 7.5L8 11l-2-2"/>
                    </svg>
                    Sign In
                </button>
            </form>

            <div class="login-footer">
                <div class="demo-credentials">
                    <h3>Demo Credentials</h3>
                    <div class="credentials-list">
                        <div class="credential-item">
                            <strong>Superadmin:</strong> superadmin / admin123
                        </div>
                        <div class="credential-item">
                            <strong>Admin:</strong> admin / admin123
                        </div>
                        <div class="credential-item">
                            <strong>Branch User:</strong> clarin_user / branch123
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="system-info">
            <p>&copy; <?php echo date('Y'); ?> Multi-Branch Fried Chicken Business. All rights reserved.</p>
            <p class="text-muted">Developed for Inventory and Stock Distribution Management</p>
        </div>
    </div>
</body>
</html>
