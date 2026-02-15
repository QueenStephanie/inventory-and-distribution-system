<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Dashboard');
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - Inventory Management System</title>
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>/assets/css/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo getBaseUrl(); ?>/assets/js/main.js" defer></script>
</head>
<body>
    <div class="app-container">
        <!-- Top Navigation Bar -->
        <nav class="top-nav">
            <div class="nav-left">
                <div class="logo-small">
                    <svg width="40" height="40" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="60" height="60" rx="10" fill="#FF6B35"/>
                        <path d="M30 15L42 25V45H18V25L30 15Z" fill="white"/>
                    </svg>
                </div>
                <h1 class="system-title">Inventory Management</h1>
            </div>
            
            <div class="nav-right">
                <div class="user-info-nav">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <span class="user-role"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                    </div>
                </div>
                <a href="<?php echo getBaseUrl(); ?>/logout.php" class="btn btn-logout" title="Logout">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8 16H3V4h5V2H3C1.9 2 1 2.9 1 4v12c0 1.1 0.9 2 2 2h5v-2zm0-8l-3.5 3.5L6 13l5-5-5-5-1.5 1.5L8 8H0v2h8z"/>
                    </svg>
                    Logout
                </a>
            </div>
        </nav>

        <div class="content-wrapper">
            <!-- Sidebar Navigation -->
            <aside class="sidebar">
                <ul class="sidebar-menu">
