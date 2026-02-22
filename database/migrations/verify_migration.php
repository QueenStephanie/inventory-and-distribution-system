<?php

/**
 * Verify Migration Status
 */

require_once '../../config/database.php';

echo "=== Migration Verification ===\n\n";

$conn = getDBConnection();

if (!$conn) {
  die("Error: Could not connect to database\n");
}

echo "✓ Connected to database\n\n";

// Check tables
echo "Checking new tables:\n";
$tables = [
  'suppliers',
  'procurement_orders',
  'procurement_order_items',
  'inventory_batches',
  'wastage_records',
  'variance_reasons'
];

$foundCount = 0;
foreach ($tables as $table) {
  $result = $conn->query("SHOW TABLES LIKE '$table'");
  $exists = ($result && $result->num_rows > 0);
  echo "  " . ($exists ? "✓" : "✗") . " $table\n";
  if ($exists) $foundCount++;
}

echo "\nTables found: $foundCount / " . count($tables) . "\n";

// Check views
echo "\nChecking views:\n";
$result = $conn->query("SHOW FULL TABLES WHERE table_type = 'VIEW'");
if ($result) {
  echo "  Found " . $result->num_rows . " views\n";
  while ($row = $result->fetch_array()) {
    echo "    - " . $row[0] . "\n";
  }
}

// Check procedures/functions
echo "\nChecking procedures:\n";
$result = $conn->query("SHOW PROCEDURE STATUS WHERE db = 'inventory_system'");
if ($result) {
  echo "  Found " . $result->num_rows . " procedures\n";
}

$result = $conn->query("SHOW FUNCTION STATUS WHERE db = 'inventory_system'");
if ($result) {
  echo "  Found " . $result->num_rows . " functions\n";
}

// Check if columns were added to existing tables
echo "\nChecking modified tables:\n";
$result = $conn->query("SHOW COLUMNS FROM inventory LIKE 'total_batches'");
echo "  " . ($result && $result->num_rows > 0 ? "✓" : "✗") . " inventory.total_batches\n";

$result = $conn->query("SHOW COLUMNS FROM requisition_items LIKE 'batch_id'");
echo "  " . ($result && $result->num_rows > 0 ? "✓" : "✗") . " requisition_items.batch_id\n";

$result = $conn->query("SHOW COLUMNS FROM stock_movements LIKE 'batch_id'");
echo "  " . ($result && $result->num_rows > 0 ? "✓" : "✗") . " stock_movements.batch_id\n";

echo "\n";

if ($foundCount == count($tables)) {
  echo "✓✓✓ MIGRATION SUCCESSFUL! ✓✓✓\n";
  echo "\nNext steps:\n";
  echo "1. Create initial batches for existing inventory\n";
  echo "2. Test the new supplier and procurement modules\n";
} else {
  echo "✗✗✗ MIGRATION INCOMPLETE ✗✗✗\n";
  echo "\nOnly $foundCount / " . count($tables) . " tables were created.\n";
  echo "Please review migration logs and try again.\n";
}

$conn->close();
