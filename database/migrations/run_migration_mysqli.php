<?php

/**
 * Migration Executor - mysqli multi_query version
 */

echo "=== Database Migration Executor (mysqli) ===\n\n";

//  Database configuration from .env
$host = 'localhost';
$dbname = 'inventory_system';
$username = 'root';
$password = '';
$port = 3307;

try {
  echo "Connecting to database on port $port...\n";
  $conn = new mysqli($host, $username, $password, $dbname, $port);

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
  }

  $conn->set_charset("utf8mb4");
  echo "✓ Connected successfully\n\n";

  // Read migration file
  $migrationFile = __DIR__ . '/001_perishable_goods_schema.sql';

  if (!file_exists($migrationFile)) {
    die("Error: Migration file not found\n");
  }

  echo "Loading migration file...\n";
  $sql = file_get_contents($migrationFile);

  if ($sql === false) {
    die("Error: Could not read migration file\n");
  }

  echo "✓ Loaded " . strlen($sql) . " bytes\n\n";

  // Remove DELIMITER statements as mysqli doesn't handle them well
  echo "Processing SQL...\n";
  $sql = preg_replace('/DELIMITER\s+\$\$/i', '', $sql);
  $sql = preg_replace('/DELIMITER\s+;/i', '', $sql);

  // Replace $$ with ; in procedure definitions
  $sql = str_replace('$$', ';', $sql);

  echo "Executing migration (this may take a minute)...\n";

  // Execute using multi_query
  if ($conn->multi_query($sql)) {
    $count = 0;
    do {
      $count++;
      // Store result if any
      if ($result = $conn->store_result()) {
        $result->free();
      }

      // Show progress
      if ($count % 10 == 0) {
        echo "  Processed $count queries...\n";
      }

      // Check for errors
      if ($conn->errno) {
        echo "  Warning on query $count: " . $conn->error . "\n";
      }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->errno) {
      echo "Error after all queries: " . $conn->error . "\n";
    }

    echo "✓ Completed (processed $count queries)\n\n";
  } else {
    die("Error executing migration: " . $conn->error . "\n");
  }

  echo "=== Verification ===\n\n";

  // Verify tables
  $tables = [
    'suppliers',
    'procurement_orders',
    'procurement_order_items',
    'inventory_batches',
    'wastage_records',
    'variance_reasons'
  ];

  echo "Checking tables:\n";
  $tableCount = 0;
  foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = ($result && $result->num_rows > 0);
    echo "  " . ($exists ? "✓" : "✗") . " $table\n";
    if ($exists) $tableCount++;
  }

  // Check columns added to existing tables
  echo "\nChecking modified tables:\n";
  $modCount = 0;

  $result = $conn->query("SHOW COLUMNS FROM inventory LIKE 'total_batches'");
  $col1 = ($result && $result->num_rows > 0);
  echo "  " . ($col1 ? "✓" : "✗") . " inventory.total_batches\n";
  if ($col1) $modCount++;

  $result = $conn->query("SHOW COLUMNS FROM requisition_items LIKE 'batch_id'");
  $col2 = ($result && $result->num_rows > 0);
  echo "  " . ($col2 ? "✓" : "✗") . " requisition_items.batch_id\n";
  if ($col2) $modCount++;

  $result = $conn->query("SHOW COLUMNS FROM stock_movements LIKE 'batch_id'");
  $col3 = ($result && $result->num_rows > 0);
  echo "  " . ($col3 ? "✓" : "✗") . " stock_movements.batch_id\n";
  if ($col3) $modCount++;

  // Check views
  echo "\nChecking views:\n";
  $result = $conn->query("SHOW FULL TABLES WHERE table_type = 'VIEW'");
  $viewCount = $result ? $result->num_rows : 0;
  echo "  Found $viewCount views\n";

  // Check procedures/functions
  echo "\nChecking procedures/functions:\n";
  $result = $conn->query("SHOW PROCEDURE STATUS WHERE db = '$dbname'");
  $procCount = $result ? $result->num_rows : 0;
  echo "  Procedures: $procCount\n";

  $result = $conn->query("SHOW FUNCTION STATUS WHERE db = '$dbname'");
  $funcCount = $result ? $result->num_rows : 0;
  echo "  Functions: $funcCount\n";

  $conn->close();

  // Summary
  echo "\n=== Summary ===\n";
  echo "Tables created: $tableCount / " . count($tables) . "\n";
  echo "Tables modified: $modCount / 3\n";
  echo "Views: $viewCount\n";
  echo "Procedures: $procCount\n";
  echo "Functions: $funcCount\n\n";

  if ($tableCount == count($tables) && $modCount == 3) {
    echo "✓✓✓ MIGRATION SUCCESSFUL! ✓✓✓\n\n";
    echo "Next steps:\n";
    echo "1. Create initial batches for existing inventory\n";
    echo "2. Set expiry dates for perishable items\n";
    echo "3. Test the supplier and procurement modules\n";
  } else {
    echo "⚠ MIGRATION PARTIALLY COMPLETE ⚠\n\n";
    echo "Some objects may not have been created.\n";
    echo "Check the warnings above or try running the migration again.\n";
  }
} catch (Exception $e) {
  echo "\n✗✗✗ ERROR ✗✗✗\n";
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}
