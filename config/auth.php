<?php
/**
 * Session Configuration and Authentication Functions
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

// Start session with secure settings
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Check if user has one of multiple roles
function hasAnyRole($roles) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    return in_array($_SESSION['role'], $roles);
}

// Require login - redirect to login page if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /inventory and distribution system/index.php");
        exit();
    }
}

// Require specific role - redirect if user doesn't have the role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: /inventory and distribution system/unauthorized.php");
        exit();
    }
}

// Require one of multiple roles
function requireAnyRole($roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        header("Location: /inventory and distribution system/unauthorized.php");
        exit();
    }
}

// Get current user's information
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
        'branch_id' => $_SESSION['branch_id'] ?? null,
        'branch_name' => $_SESSION['branch_name'] ?? null
    ];
}

// Login user
function loginUser($conn, $username, $password) {
    // Sanitize username
    $username = sanitizeInput($username);
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT u.user_id, u.username, u.password, u.full_name, u.role, u.branch_id, u.status, b.branch_name 
                            FROM users u 
                            LEFT JOIN branches b ON u.branch_id = b.branch_id 
                            WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Your account has been deactivated. Please contact administrator.'];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];
            $_SESSION['branch_name'] = $user['branch_name'];
            $_SESSION['created'] = time();
            
            // Update last login
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user['user_id']);
            $updateStmt->execute();
            
            // Redirect based on role
            $redirectUrl = getDashboardUrl($user['role']);
            
            return ['success' => true, 'redirect' => $redirectUrl];
        } else {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
    } else {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
}

// Logout user
function logoutUser() {
    startSecureSession();
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: /inventory and distribution system/index.php");
    exit();
}

// Get dashboard URL based on user role
function getDashboardUrl($role) {
    switch ($role) {
        case 'superadmin':
            return '/inventory and distribution system/superadmin/dashboard.php';
        case 'admin':
            return '/inventory and distribution system/admin/dashboard.php';
        case 'branch_user':
            return '/inventory and distribution system/branch/dashboard.php';
        default:
            return '/inventory and distribution system/index.php';
    }
}

// Get base URL for the application
function getBaseUrl() {
    return '/inventory and distribution system';
}
?>
