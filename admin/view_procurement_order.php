<?php

/**
 * View Procurement Order Details - Admin
 * View complete order information and receiving history
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Procurement Order Details');

$user = getCurrentUser();
$conn = getDBConnection();

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
  $cancelStmt = $conn->prepare("UPDATE procurement_orders SET status = 'cancelled' WHERE order_id = ?");
  $cancelStmt->bind_param("i", $orderId);

  if ($cancelStmt->execute()) {
    header("Location: view_procurement_order.php?id={$orderId}");
    exit;
  }
}

// Get order details
$orderStmt = $conn->prepare("SELECT po.*, s.supplier_name, s.supplier_code, s.contact_person, s.phone, s.email,
                                    o.full_name as ordered_by_name, r.full_name as received_by_name
                             FROM procurement_orders po
                             JOIN suppliers s ON po.supplier_id = s.supplier_id
                             LEFT JOIN users o ON po.ordered_by = o.user_id
                             LEFT JOIN users r ON po.received_by = r.user_id
                             WHERE po.order_id = ?");
$orderStmt->bind_param("i", $orderId);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
  header("Location: procurement_orders.php");
  exit;
}

// Get order items with received quantities
$itemsStmt = $conn->prepare("SELECT poi.*, rm.material_code, rm.material_name, rm.unit_of_measure,
                                     (poi.ordered_quantity * poi.unit_cost) as line_total,
                                     (poi.ordered_quantity - poi.received_quantity) as pending_quantity
                             FROM procurement_order_items poi
                             JOIN raw_materials rm ON poi.material_id = rm.material_id
                             WHERE poi.order_id = ?
                             ORDER BY rm.material_name");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$items = $itemsStmt->get_result();

// Get batch history for this order
$batchStmt = $conn->prepare("SELECT ib.*, rm.material_name, rm.material_code
                             FROM inventory_batches ib
                             JOIN raw_materials rm ON ib.material_id = rm.material_id
                             WHERE ib.source_type = 'procurement' AND ib.source_id = ?
                             ORDER BY ib.created_at DESC");
$batchStmt->bind_param("i", $orderId);
$batchStmt->execute();
$batches = $batchStmt->get_result();

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
  <h1>📄 Procurement Order Details</h1>
  <div class="page-actions">
    <a href="procurement_orders.php" class="btn btn-secondary">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
        <path d="M8 0L0 8l8 8 1.5-1.5L3 8l6.5-6.5z" />
      </svg>
      Back to Orders
    </a>

    <?php if (in_array($order['status'], ['pending', 'partial'])): ?>
      <a href="receive_procurement.php?id=<?php echo $orderId; ?>" class="btn btn-success">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
          <path d="M13.5 2L6 9.5 2.5 6 0 8.5 6 14.5 16 4.5z" />
        </svg>
        Receive Items
      </a>
    <?php endif; ?>

    <?php if ($order['status'] === 'pending'): ?>
      <button onclick="cancelOrder()" class="btn btn-danger">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
          <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm4 10.9L10.9 12 8 9.1 5.1 12 4 10.9 6.9 8 4 5.1 5.1 4 8 6.9 10.9 4 12 5.1 9.1 8 12 10.9z" />
        </svg>
        Cancel Order
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Order Header Information -->
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
      <small class="text-muted"><?php echo htmlspecialchars($order['supplier_code']); ?></small>
    </div>
    <div class="info-item">
      <span class="info-label">Contact Person:</span>
      <span class="info-value"><?php echo htmlspecialchars($order['contact_person']); ?></span>
      <small class="text-muted"><?php echo htmlspecialchars($order['phone']); ?></small>
    </div>
    <div class="info-item">
      <span class="info-label">Order Date:</span>
      <span class="info-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
      <small class="text-muted"><?php echo date('g:i A', strtotime($order['order_date'])); ?></small>
    </div>
    <div class="info-item">
      <span class="info-label">Ordered By:</span>
      <span class="info-value"><?php echo htmlspecialchars($order['ordered_by_name']); ?></span>
    </div>
    <?php if ($order['expected_delivery_date']): ?>
      <div class="info-item">
        <span class="info-label">Expected Delivery:</span>
        <span class="info-value"><?php echo date('M d, Y', strtotime($order['expected_delivery_date'])); ?></span>
        <?php
        $daysUntil = ceil((strtotime($order['expected_delivery_date']) - time()) / 86400);
        if ($order['status'] === 'pending' && $daysUntil < 0):
        ?>
          <small class="badge badge-danger">OVERDUE</small>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($order['received_date']): ?>
      <div class="info-item">
        <span class="info-label">Received Date:</span>
        <span class="info-value"><?php echo date('M d, Y', strtotime($order['received_date'])); ?></span>
        <small class="text-muted"><?php echo date('g:i A', strtotime($order['received_date'])); ?></small>
      </div>
      <div class="info-item">
        <span class="info-label">Received By:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['received_by_name']); ?></span>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($order['notes']): ?>
    <div class="notes-section">
      <strong>📝 Notes:</strong>
      <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
    </div>
  <?php endif; ?>
</div>

<!-- Order Items -->
<div class="content-section">
  <div class="section-header">
    <h3>Order Items</h3>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Material</th>
          <th>Unit</th>
          <th class="text-right">Ordered Qty</th>
          <th class="text-right">Received Qty</th>
          <th class="text-right">Pending Qty</th>
          <th class="text-right">Unit Cost</th>
          <th class="text-right">Line Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $totalOrdered = 0;
        while ($item = $items->fetch_assoc()):
          $totalOrdered += $item['line_total'];
          $receivePercent = ($item['ordered_quantity'] > 0) ?
            ($item['received_quantity'] / $item['ordered_quantity']) * 100 : 0;
        ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($item['material_name']); ?></strong><br>
              <small class="text-muted"><?php echo htmlspecialchars($item['material_code']); ?></small>
            </td>
            <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
            <td class="text-right"><?php echo number_format($item['ordered_quantity'], 2); ?></td>
            <td class="text-right"><?php echo number_format($item['received_quantity'], 2); ?></td>
            <td class="text-right"><?php echo number_format($item['pending_quantity'], 2); ?></td>
            <td class="text-right">₱<?php echo number_format($item['unit_cost'], 2); ?></td>
            <td class="text-right">₱<?php echo number_format($item['line_total'], 2); ?></td>
            <td>
              <?php if ($receivePercent >= 100): ?>
                <span class="badge badge-success">Complete</span>
              <?php elseif ($receivePercent > 0): ?>
                <span class="badge badge-warning"><?php echo round($receivePercent); ?>%</span>
              <?php else: ?>
                <span class="badge badge-secondary">Pending</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="6" class="text-right"><strong>Total Cost:</strong></td>
          <td class="text-right"><strong>₱<?php echo number_format($totalOrdered, 2); ?></strong></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- Batch History -->
<?php if ($batches->num_rows > 0): ?>
  <div class="content-section">
    <div class="section-header">
      <h3>📦 Received Batches</h3>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Material</th>
            <th>Batch Number</th>
            <th>Quantity Received</th>
            <th>Current Quantity</th>
            <th>Expiry Date</th>
            <th>Unit Cost</th>
            <th>Received Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($batch = $batches->fetch_assoc()):
            $isExpired = false;
            $isNearExpiry = false;

            if ($batch['expiry_date']) {
              $expiryTime = strtotime($batch['expiry_date']);
              $now = time();
              $thirtyDays = 30 * 24 * 60 * 60;

              if ($expiryTime < $now) {
                $isExpired = true;
              } elseif (($expiryTime - $now) < $thirtyDays) {
                $isNearExpiry = true;
              }
            }

            $isDepleted = $batch['current_quantity'] <= 0;
          ?>
            <tr>
              <td>
                <strong><?php echo htmlspecialchars($batch['material_name']); ?></strong><br>
                <small class="text-muted"><?php echo htmlspecialchars($batch['material_code']); ?></small>
              </td>
              <td>
                <code><?php echo htmlspecialchars($batch['batch_number']); ?></code>
              </td>
              <td><?php echo number_format($batch['quantity_received'], 2); ?></td>
              <td><?php echo number_format($batch['current_quantity'], 2); ?></td>
              <td>
                <?php if ($batch['expiry_date']): ?>
                  <?php echo date('M d, Y', strtotime($batch['expiry_date'])); ?>
                  <?php if ($isExpired): ?>
                    <br><span class="badge badge-danger">EXPIRED</span>
                  <?php elseif ($isNearExpiry): ?>
                    <br><span class="badge badge-warning">NEAR EXPIRY</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">N/A</span>
                <?php endif; ?>
              </td>
              <td>₱<?php echo number_format($batch['unit_cost'], 2); ?></td>
              <td><?php echo date('M d, Y g:i A', strtotime($batch['created_at'])); ?></td>
              <td>
                <?php if ($isDepleted): ?>
                  <span class="badge badge-secondary">Depleted</span>
                <?php elseif ($isExpired): ?>
                  <span class="badge badge-danger">Expired</span>
                <?php else: ?>
                  <span class="badge badge-success">Active</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Cancel Order Form (hidden) -->
<form method="POST" action="" id="cancelOrderForm">
  <input type="hidden" name="cancel_order" value="1">
</form>

<script>
  function cancelOrder() {
    Swal.fire({
      icon: 'warning',
      title: 'Cancel Order?',
      text: 'Are you sure you want to cancel this procurement order? This action cannot be undone.',
      showCancelButton: true,
      confirmButtonText: 'Yes, Cancel Order',
      cancelButtonText: 'No, Keep Order',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d'
    }).then((result) => {
      if (result.isConfirmed) {
        $('#cancelOrderForm').submit();
      }
    });
  }
</script>

<style>
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .page-actions {
    display: flex;
    gap: 10px;
  }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
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

  .notes-section {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 6px;
  }

  .notes-section p {
    margin: 10px 0 0 0;
    color: #333;
  }

  .text-right {
    text-align: right;
  }

  code {
    background-color: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
  }

  @media (max-width: 768px) {
    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 15px;
    }

    .page-actions {
      width: 100%;
      flex-direction: column;
    }

    .info-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>