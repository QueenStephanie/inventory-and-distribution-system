<?php

/**
 * Create Initial Batches for Existing Inventory
 * Run this ONCE after migration to create batch records for current inventory
 */

echo "=== Creating Initial Batches ===\n\n";

// Database configuration
$host = 'localhost';
$dbname = 'inventory_system';
$username = 'root';
$password = '';
$port = 3307;

try {
  $conn = new mysqli($host, $username, $password, $dbname, $port);

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
  }

  $conn->set_charset("utf8mb4");
  echo "✓ Connected to database\n\n";

  // Check if there's existing inventory without batches
  $checkQuery = "SELECT COUNT(*) as count FROM inventory WHERE current_quantity > 0";
  $result = $conn->query($checkQuery);
  $inventoryCount = $result->fetch_assoc()['count'];

  echo "Found $inventoryCount inventory records with stock\n\n";

  if ($inventoryCount == 0) {
    echo "No inventory to process. You're all set!\n";
    exit(0);
  }

  // Check if batches already exist
  $batchCheckQuery = "SELECT COUNT(*) as count FROM inventory_batches";
  $result = $conn->query($batchCheckQuery);
  $existingBatches = $result->fetch_assoc()['count'];

  echo "Existing batches in database: $existingBatches\n";

  if ($existingBatches > 0) {
    echo "\n⚠ Warning: Batches already exist!\n";
    echo "This script should only be run once after initial migration.\n";
    echo "If you want to re-create batches, delete existing batch records first.\n\n";
    echo "Continue anyway? This will create additional batches. (y/n): ";

    // For non-interactive execution, we'll skip if batches exist
    echo "Skipping to avoid duplicates.\n";
    exit(0);
  }

  echo "\nCreating initial batches for all inventory items...\n";

  // Get all inventory items with stock
  $inventoryQuery = "
        SELECT i.branch_id, i.material_id, i.current_quantity,
               rm.material_name, rm.category, rm.unit_of_measure
        FROM inventory i
        JOIN branches b ON i.branch_id = b.branch_id
        JOIN raw_materials rm ON i.material_id = rm.material_id
        WHERE i.current_quantity > 0 AND b.is_main_branch = TRUE
    ";

  $result = $conn->query($inventoryQuery);

  if (!$result) {
    die("Error fetching inventory: " . $conn->error . "\n");
  }

  $batchesCreated = 0;
  $errors = 0;

  // Insert batches one by one to avoid trigger conflicts
  while ($item = $result->fetch_assoc()) {
    $branchId = $item['branch_id'];
    $materialId = $item['material_id'];
    $quantity = $item['current_quantity'];
    $batchNumber = 'INITIAL-' . date('Ymd') . '-' . str_pad($materialId, 4, '0', STR_PAD_LEFT);

    $insertQuery = "
            INSERT INTO inventory_batches (
                branch_id, material_id, batch_number, initial_quantity,
                current_quantity, unit_cost, delivery_date
            ) VALUES (
                $branchId, $materialId, '$batchNumber', $quantity,
                $quantity, 0.00, CURDATE()
            )
        ";

    if ($conn->query($insertQuery)) {
      $batchesCreated++;
      if ($batchesCreated % 5 == 0) {
        echo "  Created $batchesCreated batches...\n";
      }
    } else {
      $errors++;
      echo "  Warning: Could not create batch for material ID $materialId: " . $conn->error . "\n";
    }
  }

  echo "✓ Created $batchesCreated initial batches";
  if ($errors > 0) {
    echo " ($errors errors)";
  }
  echo "\n\n";

  // Update inventory summary
  echo "Updating inventory summaries...\n";

  $updateQuery = "
        UPDATE inventory i
        JOIN (
            SELECT
                material_id,
                COUNT(*) as total_batches,
                SUM(current_quantity) as total_quantity,
                MIN(expiry_date) as oldest_expiry,
                SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as near_expiry
            FROM inventory_batches
            WHERE current_quantity > 0
            GROUP BY material_id
        ) batch_summary ON i.material_id = batch_summary.material_id
        JOIN branches b ON i.branch_id = b.branch_id
        SET
            i.total_batches = batch_summary.total_batches,
            i.oldest_expiry_date = batch_summary.oldest_expiry,
            i.near_expiry_count = batch_summary.near_expiry
        WHERE b.is_main_branch = TRUE
    ";

  if ($conn->query($updateQuery)) {
    echo "✓ Updated inventory summaries\n\n";
  } else {
    echo "Warning: Could not update summaries: " . $conn->error . "\n\n";
  }

  // Display created batches
  echo "=== Initial Batches Created ===\n\n";

  $listQuery = "
        SELECT
            rm.material_name,
            rm.category,
            ib.batch_number,
            ib.current_quantity,
            rm.unit_of_measure
        FROM inventory_batches ib
        JOIN raw_materials rm ON ib.material_id = rm.material_id
        WHERE ib.batch_number LIKE 'INITIAL-%'
        ORDER BY rm.category, rm.material_name
        LIMIT 20
    ";

  $result = $conn->query($listQuery);

  if ($result && $result->num_rows > 0) {
    echo str_pad("Material", 35) . str_pad("Category", 15) . str_pad("Batch Number", 25) . str_pad("Quantity", 15) . "\n";
    echo str_repeat("-", 90) . "\n";

    while ($row = $result->fetch_assoc()) {
      echo str_pad(substr($row['material_name'], 0, 34), 35);
      echo str_pad($row['category'], 15);
      echo str_pad($row['batch_number'], 25);
      echo str_pad($row['current_quantity'] . ' ' . $row['unit_of_measure'], 15);
      echo "\n";
    }

    if ($result->num_rows == 20) {
      echo "\n(Showing first 20 batches)\n";
    }
  }

  $conn->close();

  echo "\n✓✓✓ INITIAL BATCHES CREATED! ✓✓✓\n\n";
  echo "Next Steps:\n";
  echo "1. Set expiry dates for perishable items (chicken, oil, etc.)\n";
  echo "2. Review the created batches in the system\n";
  echo "3. Start using the supplier and procurement modules\n\n";
  echo "To set expiry dates, update the inventory_batches table:\n";
  echo "  UPDATE inventory_batches SET expiry_date = '2026-03-15' WHERE material_id = X;\n\n";
} catch (Exception $e) {
  echo "\n✗ ERROR: " . $e->getMessage() . "\n";
  exit(1);
}
