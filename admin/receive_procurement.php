<?php

/**
 * Receive Procurement Order - Admin
 * Receive delivery, enter batch numbers and expiry dates
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Receive Procurement Order');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle receiving submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_order'])) {
  $receivedQuantities = $_POST['received_quantities'] ?? [];
  $batchNumbers = $_POST['batch_numbers'] ?? [];
  $expiryDates = $_POST['expiry_dates'] ?? [];
  $itemIds = $_POST['item_ids'] ?? [];

  $conn->begin_transaction();

  try {
    $allReceived = true;
    $receivedItemCount = 0;

    foreach ($itemIds as $index => $itemId) {
      $itemId = intval($itemId);
      $receivedQty = floatval($receivedQuantities[$index] ?? 0);
      $batchNumber = sanitizeInput($batchNumbers[$index] ?? '');
      $expiryDate = sanitizeInput($expiryDates[$index] ?? '');

      if ($receivedQty > 0) {
        // Get item details
        $itemStmt = $conn->prepare("SELECT poi.material_id, poi.ordered_quantity, poi.received_quantity, 
                                                   poi.unit_cost, rm.material_name
                                           FROM procurement_order_items poi
                                           JOIN raw_materials rm ON poi.material_id = rm.material_id
                                           WHERE poi.item_id = ?");
        $itemStmt->bind_param("i", $itemId);
        $itemStmt->execute();
        $item = $itemStmt->get_result()->fetch_assoc();

        if (!$item) {
          throw new Exception("Invalid item ID: {$itemId}");
        }

        $materialId = $item['material_id'];
        $orderedQty = floatval($item['ordered_quantity']);
        $previouslyReceived = floatval($item['received_quantity']);

        // Validate received quantity
        if ($receivedQty > ($orderedQty - $previouslyReceived)) {
          throw new Exception("Received quantity for {$item['material_name']} exceeds ordered quantity.");
        }

        // Validate batch number for perishables
        if (empty($batchNumber)) {
          $batchNumber = 'BATCH-' . date('Ymd') . '-' . str_pad($itemId, 4, '0', STR_PAD_LEFT);
        }

        // Update order item received quantity
        $updateItemStmt = $conn->prepare("UPDATE procurement_order_items 
                                                  SET received_quantity = received_quantity + ?,
                                                      updated_at = CURRENT_TIMESTAMP
                                                  WHERE item_id = ?");
        $updateItemStmt->bind_param("di", $receivedQty, $itemId);
        $updateItemStmt->execute();

        // Create inventory batch
        $insertBatchStmt = $conn->prepare("INSERT INTO inventory_batches 
                                                   (material_id, batch_number, quantity_received, current_quantity, 
                                                    expiry_date, unit_cost, source_type, source_id)
                                                   VALUES (?, ?, ?, ?, ?, ?, 'procurement', ?)");

        $expiryDateParam = !empty($expiryDate) ? $expiryDate : null;
        $insertBatchStmt->bind_param(
          "isddsdi",
          $materialId,
          $batchNumber,
          $receivedQty,
          $receivedQty,
          $expiryDateParam,
          $item['unit_cost'],
          $orderId
        );
        $insertBatchStmt->execute();

        // Update main inventory
        $checkInvStmt = $conn->prepare("SELECT quantity FROM commissary_inventory WHERE material_id = ?");
        $checkInvStmt->bind_param("i", $materialId);
        $checkInvStmt->execute();
        $invResult = $checkInvStmt->get_result();

        if ($invResult->num_rows > 0) {
          $updateInvStmt = $conn->prepare("UPDATE commissary_inventory 
                                                     SET quantity = quantity + ?,
                                                         last_updated = CURRENT_TIMESTAMP
                                                     WHERE material_id = ?");
          $updateInvStmt->bind_param("di", $receivedQty, $materialId);
          $updateInvStmt->execute();
        } else {
          $insertInvStmt = $conn->prepare("INSERT INTO commissary_inventory (material_id, quantity, last_updated)
                                                     VALUES (?, ?, CURRENT_TIMESTAMP)");
          $insertInvStmt->bind_param("id", $materialId, $receivedQty);
          $insertInvStmt->execute();
        }

        $receivedItemCount++;

        // Check if this item still has pending quantity
        $newReceivedQty = $previouslyReceived + $receivedQty;
        if ($newReceivedQty < $orderedQty) {
          $allReceived = false;
        }
      } else {
        $allReceived = false;
      }
    }

    // Update order status
    $newStatus = $allReceived ? 'received' : 'partial';
    $updateOrderStmt = $conn->prepare("UPDATE procurement_orders 
                                          SET status = ?,
                                              received_date = IF(? = 'received', CURRENT_TIMESTAMP, received_date),
                                              received_by = ?
                                          WHERE order_id = ?");
    $updateOrderStmt->bind_param("ssii", $newStatus, $newStatus, $user['user_id'], $orderId);
    $updateOrderStmt->execute();

    $conn->commit();

    $statusText = $allReceived ? 'fully received' : 'partially received';
    $message = "Order {$statusText} successfully! Processed {$receivedItemCount} items.";
    $messageType = 'success';

    echo "<script>
            setTimeout(function() {
                window.location.href = 'view_procurement_order.php?id={$orderId}';
            }, 2000);
        </script>";
  } catch (Exception $e) {
    $conn->rollback();
    $message = 'Failed to receive order: ' . $e->getMessage();
    $messageType = 'error';
    error_log("Receive procurement error: " . $e->getMessage());
  }
}

// Get order details
$orderStmt = $conn->prepare("SELECT po.*, s.supplier_name, s.supplier_code,
                                    u.full_name as ordered_by_name
                             FROM procurement_orders po
                             JOIN suppliers s ON po.supplier_id = s.supplier_id
                             LEFT JOIN users u ON po.ordered_by = u.user_id
                             WHERE po.order_id = ?");
$orderStmt->bind_param("i", $orderId);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
  header("Location: procurement_orders.php");
  exit;
}

// Check if order can be received
if (!in_array($order['status'], ['pending', 'partial'])) {
  header("Location: view_procurement_order.php?id={$orderId}");
  exit;
}

// Get order items
$itemsStmt = $conn->prepare("SELECT poi.*, rm.material_code, rm.material_name, rm.unit_of_measure,
                                     (poi.ordered_quantity - poi.received_quantity) as pending_quantity
                             FROM procurement_order_items poi
                             JOIN raw_materials rm ON poi.material_id = rm.material_id
                             WHERE poi.order_id = ?
                             ORDER BY rm.material_name");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$items = $itemsStmt->get_result();

include '../includes/header.php';
?>

<!-- Sidebar Menu -->
<li class="menu-item">
  <a href="dashboard.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M10 0L0 8v12h7v-7h6v7h7V8L10 0z" />
    </svg>
    Dashboard
  </a>
</li>
<li class="menu-item">
  <a href="pending_requests.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M18 2H2C0.9 2 0 2.9 0 4v12c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H2V6h16v10z" />
    </svg>
    Pending Requests
  </a>
</li>
<li class="menu-item">
  <a href="approved_requests.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M16 0H4C2.9 0 2 0.9 2 2v16c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V2c0-1.1-0.9-2-2-2zm-6 15l-5-5 1.41-1.41L10 12.17l6.59-6.59L18 7l-8 8z" />
    </svg>
    Approved Requests
  </a>
</li>
<li class="menu-item">
  <a href="all_requests.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M17 0H3C1.9 0 1 0.9 1 2v12c0 1.1 0.9 2 2 2h11l5 4V2c0-1.1-0.9-2-2-2z" />
    </svg>
    All Requests
  </a>
</li>
<li class="menu-item">
  <a href="commissary_inventory.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M2 2h16v16H2V2zm2 2v12h12V4H4z" />
    </svg>
    Commissary Inventory
  </a>
</li>
<li class="menu-item">
  <a href="manage_suppliers.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M16 1H4C2.9 1 2 1.9 2 3v14c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V3c0-1.1-0.9-2-2-2zM9 13H7v-2h2v2zm0-4H7V5h2v4zm4 4h-2V9h2v4zm0-6h-2V5h2v2z" />
    </svg>
    Manage Suppliers
  </a>
</li>
<li class="menu-item active">
  <a href="procurement_orders.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M17 2H3C1.9 2 1 2.9 1 4v12c0 1.1 0.9 2 2 2h14c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H3V6h14v10z" />
    </svg>
    Procurement Orders
  </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<!-- Page Content -->
<div class="page-header">
  <h1>📦 Receive Procurement Order</h1>
  <p>Enter received quantities, batch numbers, and expiry dates</p>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?php echo $messageType; ?>">
    <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>

<!-- Order Info Card -->
<div class="content-section">
  <div class="section-header">
    <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
    <span class="badge badge-<?php echo $order['status']; ?>">
      <?php echo strtoupper($order['status']); ?>
    </span>
  </div>

  <div class="info-grid">
    <div class="info-item">
      <span class="info-label">Supplier:</span>
      <span class="info-value"><?php echo htmlspecialchars($order['supplier_name']); ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Order Date:</span>
      <span class="info-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Ordered By:</span>
      <span class="info-value"><?php echo htmlspecialchars($order['ordered_by_name']); ?></span>
    </div>
    <?php if ($order['expected_delivery_date']): ?>
      <div class="info-item">
        <span class="info-label">Expected Delivery:</span>
        <span class="info-value"><?php echo date('M d, Y', strtotime($order['expected_delivery_date'])); ?></span>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="alert alert-info">
  <strong>🔔 Important:</strong> Enter the actual received quantities. For perishable items, batch numbers and expiry dates are required for tracking. If batch number is left empty, one will be auto-generated.
</div>

<!-- Receiving Form -->
<form method="POST" action="" id="receiveForm">
  <div class="content-section">
    <div class="section-header">
      <h3>Items to Receive</h3>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th width="30%">Material</th>
            <th width="10%">Unit</th>
            <th width="10%">Ordered</th>
            <th width="10%">Already Received</th>
            <th width="10%">Receive Now <span class="required">*</span></th>
            <th width="15%">Batch Number</th>
            <th width="15%">Expiry Date</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $hasItems = false;
          while ($item = $items->fetch_assoc()):
            if ($item['pending_quantity'] > 0):
              $hasItems = true;
          ?>
              <tr>
                <input type="hidden" name="item_ids[]" value="<?php echo $item['item_id']; ?>">
                <td>
                  <strong><?php echo htmlspecialchars($item['material_name']); ?></strong><br>
                  <small class="text-muted"><?php echo htmlspecialchars($item['material_code']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                <td><?php echo number_format($item['ordered_quantity'], 2); ?></td>
                <td><?php echo number_format($item['received_quantity'], 2); ?></td>
                <td>
                  <input type="number"
                    name="received_quantities[]"
                    class="form-control"
                    step="0.01"
                    min="0"
                    max="<?php echo $item['pending_quantity']; ?>"
                    placeholder="0.00"
                    required>
                  <small class="text-muted">Max: <?php echo number_format($item['pending_quantity'], 2); ?></small>
                </td>
                <td>
                  <input type="text"
                    name="batch_numbers[]"
                    class="form-control"
                    placeholder="Auto-generate"
                    maxlength="50">
                </td>
                <td>
                  <input type="date"
                    name="expiry_dates[]"
                    class="form-control expiry-date"
                    min="<?php echo date('Y-m-d'); ?>">
                </td>
              </tr>
          <?php
            endif;
          endwhile;
          ?>

          <?php if (!$hasItems): ?>
            <tr>
              <td colspan="7" class="text-center">
                All items have been fully received.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($hasItems): ?>
      <div class="form-actions">
        <button type="submit" name="receive_order" class="btn btn-success">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M13.5 2L6 9.5 2.5 6 0 8.5 6 14.5 16 4.5z" />
          </svg>
          Receive Items
        </button>
        <a href="view_procurement_order.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
      </div>
    <?php else: ?>
      <div class="form-actions">
        <a href="procurement_orders.php" class="btn btn-primary">Back to Orders</a>
      </div>
    <?php endif; ?>
  </div>
</form>

<script>
  // Form validation
  $('#receiveForm').on('submit', function(e) {
    let hasQuantity = false;
    let hasInvalidQuantity = false;

    $('input[name="received_quantities[]"]').each(function() {
      const val = parseFloat($(this).val()) || 0;
      const max = parseFloat($(this).attr('max')) || 0;

      if (val > 0) {
        hasQuantity = true;
      }

      if (val > max) {
        hasInvalidQuantity = true;
      }
    });

    if (!hasQuantity) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'No Quantities Entered',
        text: 'Please enter at least one received quantity.',
        confirmButtonColor: '#FF6B35'
      });
      return false;
    }

    if (hasInvalidQuantity) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Invalid Quantity',
        text: 'Received quantity cannot exceed pending quantity.',
        confirmButtonColor: '#FF6B35'
      });
      return false;
    }

    // Check for expiry date warnings
    const today = new Date();
    const thirtyDaysFromNow = new Date();
    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);

    let hasNearExpiry = false;
    $('.expiry-date').each(function() {
      const expiryVal = $(this).val();
      if (expiryVal) {
        const expiryDate = new Date(expiryVal);
        if (expiryDate <= thirtyDaysFromNow) {
          hasNearExpiry = true;
        }
      }
    });

    if (hasNearExpiry) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Near Expiry Warning',
        text: 'Some items have expiry dates within 30 days. Do you want to proceed?',
        showCancelButton: true,
        confirmButtonText: 'Yes, Proceed',
        cancelButtonText: 'Review',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
      }).then((result) => {
        if (result.isConfirmed) {
          $('#receiveForm')[0].submit();
        }
      });
      return false;
    }
  });
</script>

<style>
  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
  }

  .info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .info-label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 500;
  }

  .info-value {
    font-size: 1rem;
    color: #333;
    font-weight: 600;
  }

  .text-muted {
    color: #666;
    font-size: 0.875rem;
  }

  .required {
    color: var(--danger-color);
  }
</style>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>