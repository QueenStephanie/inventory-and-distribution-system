<?php

/**
 * Database Test Script
 * Run this to check if your database is set up correctly
 */

require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";
echo "<pre>";

try {
  $conn = getDBConnection();
  echo "✓ Database connection successful!\n\n";

  // Check if database exists
  $result = $conn->query("SELECT DATABASE() as db");
  $row = $result->fetch_assoc();
  echo "✓ Connected to database: " . $row['db'] . "\n\n";

  // Check if users table exists
  $result = $conn->query("SHOW TABLES LIKE 'users'");
  if ($result->num_rows > 0) {
    echo "✓ Users table exists\n\n";

    // Check if any users exist
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "✓ Total users in database: " . $row['count'] . "\n\n";

    if ($row['count'] > 0) {
      // List all usernames
      $result = $conn->query("SELECT username, role, full_name FROM users WHERE status='active'");
      echo "📋 Available Users:\n";
      echo str_repeat("-", 60) . "\n";
      while ($row = $result->fetch_assoc()) {
        echo "Username: " . str_pad($row['username'], 20) . " | Role: " . str_pad($row['role'], 15) . " | Name: " . $row['full_name'] . "\n";
      }
      echo str_repeat("-", 60) . "\n";
      echo "\n✅ DEFAULT PASSWORD FOR ALL USERS: admin123\n\n";
      echo "🔐 Try logging in with:\n";
      echo "   Username: superadmin\n";
      echo "   Password: admin123\n";
    } else {
      echo "⚠ No users found in database!\n";
      echo "You need to import sample-data.sql\n";
    }
  } else {
    echo "✗ Users table does not exist!\n\n";
    echo "❌ DATABASE NOT INITIALIZED!\n\n";
    echo "You need to run the following steps:\n";
    echo "1. Open XAMPP Control Panel\n";
    echo "2. Start Apache and MySQL\n";
    echo "3. Click 'Admin' button for MySQL (opens phpMyAdmin)\n";
    echo "4. Import these files in order:\n";
    echo "   a) database/schema.sql\n";
    echo "   b) database/sample-data.sql\n";
  }

  closeDBConnection($conn);
} catch (Exception $e) {
  echo "✗ Connection Error: " . $e->getMessage() . "\n\n";
  echo "❌ POSSIBLE ISSUES:\n";
  echo "1. MySQL is not running in XAMPP\n";
  echo "2. MySQL port is not 3307 (check your XAMPP MySQL config)\n";
  echo "3. Database 'inventory_system' does not exist\n\n";
  echo "TO FIX:\n";
  echo "1. Open XAMPP Control Panel\n";
  echo "2. Make sure MySQL is running on port 3307\n";
  echo "3. Import database/schema.sql in phpMyAdmin\n";
  echo "4. Import database/sample-data.sql in phpMyAdmin\n";
}

echo "</pre>";
