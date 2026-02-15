<?php
/**
 * Unauthorized Access Page
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once 'config/database.php';
require_once 'config/auth.php';

startSecureSession();

// If not logged in, redirect to login
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - Inventory Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-box">
            <div class="error-icon">
                <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="40" cy="40" r="40" fill="#FF6B35" opacity="0.1"/>
                    <circle cx="40" cy="40" r="30" fill="#FF6B35" opacity="0.2"/>
                    <path d="M40 25V45M40 50V55" stroke="#FF6B35" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </div>
            
            <h1>Access Denied</h1>
            <p class="error-message">You do not have permission to access this page.</p>
            
            <div class="user-info">
                <p><strong>Current Role:</strong> <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></p>
                <?php if ($user['branch_name']): ?>
                    <p><strong>Branch:</strong> <?php echo htmlspecialchars($user['branch_name']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="error-actions">
                <a href="<?php echo getDashboardUrl($user['role']); ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 0L0 6v10h6v-6h4v6h6V6L8 0z"/>
                    </svg>
                    Go to Dashboard
                </a>
                <a href="logout.php" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M6 14H2V2h4V0H2C0.9 0 0 0.9 0 2v12c0 1.1 0.9 2 2 2h4v-2zm0-6l-2.5 2.5L5 12l5-5-5-5-1.5 1.5L6 6H0v2h6z"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
</body>
</html>
