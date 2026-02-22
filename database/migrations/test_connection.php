<?php
require_once '../../config/database.php';

echo "Testing connection...\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_PASS: " . (empty(DB_PASS) ? "(empty)" : "(set)") . "\n\n";

$conn = getDBConnection();
if ($conn) {
  echo "✓ Connected!\n";
  $result = $conn->query("SELECT DATABASE() as db");
  if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ Database: " . $row['db'] . "\n";
  }
  closeDBConnection($conn);
} else {
  echo "✗ Connection failed\n";
}
