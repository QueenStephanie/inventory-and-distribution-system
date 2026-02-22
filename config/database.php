<?php

/**
 * Database Configuration
 * Web-Based Centralized Inventory and Stock Distribution Management System
 * 
 * This file loads configuration from environment variables if available.
 * Falls back to default values for backward compatibility.
 * 
 * For production, create a .env file based on .env.example
 */

// Load .env file if it exists (simple implementation)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Database credentials - supports environment variables with fallback defaults
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'inventory_system');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Application settings
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1');

// Create database connection
function getDBConnection()
{
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

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
function closeDBConnection($conn)
{
    if ($conn) {
        $conn->close();
    }
}

// Sanitize input to prevent XSS
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Generate unique requisition number
function generateRequisitionNumber($conn)
{
    $date = date('Ymd');
    $query = "SELECT COUNT(*) as count FROM stock_requisitions WHERE DATE(request_date) = CURDATE()";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $number = str_pad($row['count'] + 1, 4, '0', STR_PAD_LEFT);
    return "REQ-{$date}-{$number}";
}

// Format date for display
function formatDate($date)
{
    return date('F d, Y', strtotime($date));
}

// Format datetime for display
function formatDateTime($datetime)
{
    return date('F d, Y h:i A', strtotime($datetime));
}

// Format number with 2 decimal places
function formatNumber($number)
{
    return number_format($number, 2, '.', ',');
}
