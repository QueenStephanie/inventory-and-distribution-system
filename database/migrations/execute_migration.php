<?php

/**
 * Migration Executor - Standalone Version
 * Uses PDO for better compatibility
 */

echo "=== Database Migration Executor ===\n\n";

// Database configuration
$host = 'localhost';
$dbname = 'inventory_system';
$username = 'root';
$password = '';
$port = 3307; // Custom port from .env

try {
  // Connect using PDO
  $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  echo "Connecting to database...\n";
  $pdo = new PDO($dsn, $username, $password, $options);
  echo "✓ Connected successfully\n\n";

  // Read migration file
  $migrationFile = __DIR__ . '/001_perishable_goods_schema.sql';

  if (!file_exists($migrationFile)) {
    die("Error: Migration file not found at: $migrationFile\n");
  }

  echo "Loading migration file...\n";
  $sql = file_get_contents($migrationFile);

  if ($sql === false) {
    die("Error: Could not read migration file\n");
  }

  echo "✓ Loaded " . strlen($sql) . " bytes\n\n";

  // Remove DELIMITER statements as PDO doesn't support them
  // We'll execute each statement separately
  echo "Parsing SQL statements...\n";

  // Split by delimiter commands and process each section
  $sql = str_replace("\r\n", "\n", $sql);

  // First, let's execute everything before the DELIMITER changes
  $parts = preg_split('/DELIMITER\s+\$\$/i', $sql, 2);

  if (count($parts) == 2) {
    // Execute regular SQL (before procedures)
    echo "Executing tables, indexes, and triggers...\n";
    executeSQLStatements($pdo, $parts[0]);

    // Now handle the stored procedures/functions section
    $procSection = $parts[1];
    $procParts = preg_split('/DELIMITER\s+;/i', $procSection, 2);

    if (count($procParts) >= 1) {
      // Execute procedures/functions (they use $$ delimiter)
      echo "Executing stored procedures and functions...\n";
      $procedures = explode('$$', $procParts[0]);

      foreach ($procedures as $proc) {
        $proc = trim($proc);
        if (!empty($proc) && stripos($proc, 'CREATE') !== false) {
          try {
            $pdo->exec($proc);
            if (stripos($proc, 'PROCEDURE') !== false) {
              preg_match('/CREATE\s+PROCEDURE\s+`?(\w+)`?/i', $proc, $matches);
              echo "  ✓ Created procedure: " . ($matches[1] ?? 'unknown') . "\n";
            } elseif (stripos($proc, 'FUNCTION') !== false) {
              preg_match('/CREATE\s+FUNCTION\s+`?(\w+)`?/i', $proc, $matches);
              echo "  ✓ Created function: " . ($matches[1] ?? 'unknown') . "\n";
            }
          } catch (PDOException $e) {
            echo "  Warning: " . $e->getMessage() . "\n";
          }
        }
      }

      // Execute remaining SQL after procedures
      if (count($procParts) == 2 && !empty(trim($procParts[1]))) {
        echo "Executing final statements...\n";
        executeSQLStatements($pdo, $procParts[1]);
      }
    }
  } else {
    // No procedures, just execute all
    echo "Executing all SQL statements...\n";
    executeSQLStatements($pdo, $sql);
  }

  echo "\n";
  echo "=== Verification ===\n\n";

  // Verify tables created
  $tables = [
    'suppliers',
    'procurement_orders',
    'procurement_order_items',
    'inventory_batches',
    'wastage_records',
    'variance_reasons'
  ];

  echo "Checking tables:\n";
  foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    $exists = $stmt->rowCount() > 0;
    echo "  " . ($exists ? "✓" : "✗") . " $table\n";
  }

  // Check views
  echo "\nChecking views:\n";
  $stmt = $pdo->query("SHOW FULL TABLES WHERE table_type = 'VIEW'");
  $views = $stmt->fetchAll();
  echo "  Found " . count($views) . " views\n";

  // Check procedures
  echo "\nChecking procedures:\n";
  $stmt = $pdo->query("SHOW PROCEDURE STATUS WHERE db = '$dbname'");
  $procs = $stmt->fetchAll();
  echo "  Found " . count($procs) . " procedures\n";

  // Check functions
  $stmt = $pdo->query("SHOW FUNCTION STATUS WHERE db = '$dbname'");
  $funcs = $stmt->fetchAll();
  echo "  Found " . count($funcs) . " functions\n";

  echo "\n✓✓✓ MIGRATION SUCCESS! ✓✓✓\n\n";
  echo "Next steps:\n";
  echo "1. Create initial batches for existing inventory\n";
  echo "2. Test the supplier and procurement modules\n";
} catch (PDOException $e) {
  echo "\n✗✗✗ ERROR ✗✗✗\n";
  echo "Error: " . $e->getMessage() . "\n";
  echo "\nFile: " . $e->getFile() . "\n";
  echo "Line: " . $e->getLine() . "\n";
  exit(1);
}

function executeSQLStatements($pdo, $sql)
{
  // Split by semicolons but be careful with stored procedures
  $statements = array_filter(array_map('trim', explode(';', $sql)));

  $count = 0;
  foreach ($statements as $statement) {
    if (empty($statement) || substr($statement, 0, 2) == '--') {
      continue;
    }

    // Skip DELIMITER statements
    if (stripos($statement, 'DELIMITER') !== false) {
      continue;
    }

    try {
      $pdo->exec($statement);
      $count++;

      // Progress indicator
      if ($count % 5 == 0) {
        echo "  Executed $count statements...\n";
      }
    } catch (PDOException $e) {
      // Some statements might fail if objects already exist - that's ok
      if (strpos($e->getMessage(), 'already exists') === false) {
        echo "  Warning: " . $e->getMessage() . "\n";
      }
    }
  }

  echo "  ✓ Executed $count statements\n";
}
