<?php

/**
 * Migration Runner Script
 * Executes the 001_perishable_goods_schema.sql migration
 */

require_once '../../config/database.php';

echo "=== Database Migration Runner ===\n\n";
echo "Database: " . DB_NAME . "\n";
echo "Starting migration...\n\n";

try {
  // Connect to database using existing config
  $conn = getDBConnection();

  if (!$conn) {
    die("Connection failed: Could not connect to database\n");
  }

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
  }

  echo "✓ Connected to database\n\n";

  // Read migration file
  $migrationFile = __DIR__ . '/001_perishable_goods_schema.sql';

  if (!file_exists($migrationFile)) {
    die("Error: Migration file not found: $migrationFile\n");
  }

  $sql = file_get_contents($migrationFile);

  if ($sql === false) {
    die("Error: Could not read migration file\n");
  }

  echo "✓ Loaded migration file (" . strlen($sql) . " bytes)\n\n";

  // Execute migration (multi-query)
  echo "Executing migration...\n";

  if ($conn->multi_query($sql)) {
    $statementCount = 0;
    do {
      $statementCount++;
      if ($result = $conn->store_result()) {
        $result->free();
      }

      if ($conn->more_results()) {
        // Print progress every 10 statements
        if ($statementCount % 10 == 0) {
          echo "  Processed $statementCount statements...\n";
        }
      }
    } while ($conn->next_result());

    // Check for errors
    if ($conn->errno) {
      die("Error during migration: " . $conn->error . "\n");
    }

    echo "\n✓ Migration completed successfully!\n";
    echo "  Total statements executed: $statementCount\n\n";

    // Verify some tables were created
    echo "Verifying migration...\n";

    $tables = [
      'suppliers',
      'procurement_orders',
      'procurement_order_items',
      'inventory_batches',
      'wastage_records',
      'variance_reasons'
    ];

    foreach ($tables as $table) {
      $result = $conn->query("SHOW TABLES LIKE '$table'");
      if ($result && $result->num_rows > 0) {
        echo "  ✓ Table '$table' exists\n";
      } else {
        echo "  ✗ Table '$table' NOT FOUND\n";
      }
    }

    // Check views
    echo "\nChecking views...\n";
    $result = $conn->query("SHOW FULL TABLES WHERE table_type = 'VIEW'");
    $viewCount = $result ? $result->num_rows : 0;
    echo "  Found $viewCount views\n";

    // Check procedures
    echo "\nChecking procedures/functions...\n";
    $result = $conn->query("SHOW PROCEDURE STATUS WHERE db = 'inventory_system'");
    $procCount = $result ? $result->num_rows : 0;
    echo "  Found $procCount stored procedures\n";

    $result = $conn->query("SHOW FUNCTION STATUS WHERE db = 'inventory_system'");
    $funcCount = $result ? $result->num_rows : 0;
    echo "  Found $funcCount functions\n";

    echo "\n=== Migration Summary ===\n";
    echo "Status: SUCCESS ✓\n";
    echo "Tables created: " . count($tables) . "\n";
    echo "Views created: $viewCount\n";
    echo "Procedures: $procCount\n";
    echo "Functions: $funcCount\n";
    echo "\nYou can now proceed with creating initial batches for existing inventory.\n";
  } else {
    die("Error executing migration: " . $conn->error . "\n");
  }

  closeDBConnection($conn);
} catch (Exception $e) {
  die("Fatal error: " . $e->getMessage() . "\n");
}
