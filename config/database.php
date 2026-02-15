<?php
/**
 * Database Configuration
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventory_system');

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to utf8
        $conn->set_charset("utf8");
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}

// Close database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Sanitize input to prevent XSS
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Generate unique requisition number
function generateRequisitionNumber($conn) {
    $date = date('Ymd');
    $query = "SELECT COUNT(*) as count FROM stock_requisitions WHERE DATE(request_date) = CURDATE()";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $number = str_pad($row['count'] + 1, 4, '0', STR_PAD_LEFT);
    return "REQ-{$date}-{$number}";
}

// Format date for display
function formatDate($date) {
    return date('F d, Y', strtotime($date));
}

// Format datetime for display
function formatDateTime($datetime) {
    return date('F d, Y h:i A', strtotime($datetime));
}

// Format number with 2 decimal places
function formatNumber($number) {
    return number_format($number, 2, '.', ',');
}
?>
