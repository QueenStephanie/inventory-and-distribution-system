<?php

/**
 * Procurement Orders - Admin
 * View and manage procurement orders from suppliers
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Procurement Orders');

$user = getCurrentUser();
$conn = getDBConnection();

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$supplierFilter = intval($_GET['supplier'] ?? 0);

// Build query
$query = "SELECT po.*, s.supplier_name, s.supplier_code,
          u1.full_name as ordered_by_name,
          u2.full_name as received_by_name,
          COUNT(poi.item_id) as item_count,
          SUM(poi.ordered_quantity) as total_quantity,
          SUM(poi.received_quantity) as received_quantity
          FROM procurement_orders po
          JOIN suppliers s ON po.supplier_id = s.supplier_id
          LEFT JOIN users u1 ON po.ordered_by = u1.user_id
          LEFT JOIN users u2 ON po.received_by = u2.user_id
          LEFT JOIN procurement_order_items poi ON po.order_id = poi.order_id
          WHERE 1=1";

if ($statusFilter !== 'all') {
  $query .= " AND po.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

if ($supplierFilter > 0) {
  $query .= " AND po.supplier_id = " . $supplierFilter;
}

$query .= " GROUP BY po.order_id ORDER BY po.order_date DESC";

$orders = $conn->query($query);

// Get suppliers for filter
$suppliers = $conn->query("SELECT supplier_id, supplier_name, supplier_code FROM suppliers WHERE status = 'active' ORDER BY supplier_name");

// Get statistics
$statsQuery = "SELECT 
               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
               SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count,
               SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_count,
               SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
               FROM procurement_orders";
$stats = $conn->query($statsQuery)->fetch_assoc();

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
  <h1>📦 Procurement Orders</h1>
  <p>Manage purchase orders from suppliers</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
  <div class="stat-card stat-warning">
    <div class="stat-content">
      <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
      <div class="stat-label">Pending Orders</div>
    </div>
  </div>

  <div class="stat-card stat-info">
    <div class="stat-content">
      <div class="stat-value"><?php echo $stats['partial_count']; ?></div>
      <div class="stat-label">Partially Received</div>
    </div>
  </div>

  <div class="stat-card stat-success">
    <div class="stat-content">
      <div class="stat-value"><?php echo $stats['received_count']; ?></div>
      <div class="stat-label">Fully Received</div>
    </div>
  </div>

  <div class="stat-card stat-secondary">
    <div class="stat-content">
      <div class="stat-value"><?php echo $stats['cancelled_count']; ?></div>
      <div class="stat-label">Cancelled</div>
    </div>
  </div>
</div>

<!-- Actions Bar -->
<div class="actions-bar" style="margin: 20px 0; display: flex; justify-content: space-between; align-items: center;">
  <a href="create_procurement_order.php" class="btn btn-primary">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 5px;">
      <path d="M8 0C7.4 0 7 0.4 7 1v6H1C0.4 7 0 7.4 0 8s0.4 1 1 1h6v6c0 0.6 0.4 1 1 1s1-0.4 1-1V9h6c0.6 0 1-0.4 1-1s-0.4-1-1-1H9V1C9 0.4 8.6 0 8 0z" />
    </svg>
    Create New Procurement Order
  </a>
</div>

<!-- Filter Section -->
<div class="filter-section">
  <form method="GET" action="" class="filter-form">
    <div class="filter-group">
      <label>Status:</label>
      <select name="status" class="form-control">
        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
        <option value="partial" <?php echo $statusFilter === 'partial' ? 'selected' : ''; ?>>Partially Received</option>
        <option value="received" <?php echo $statusFilter === 'received' ? 'selected' : ''; ?>>Fully Received</option>
        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
      </select>
    </div>
    <div class="filter-group">
      <label>Supplier:</label>
      <select name="supplier" class="form-control">
        <option value="0">All Suppliers</option>
        <?php
        $suppliers->data_seek(0);
        while ($supplier = $suppliers->fetch_assoc()):
        ?>
          <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo $supplierFilter === $supplier['supplier_id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Apply Filters</button>
    <a href="procurement_orders.php" class="btn btn-secondary">Reset</a>
  </form>
</div>

<!-- Orders Table -->
<div class="content-section">
  <div class="section-header">
    <h3>Procurement Order List</h3>
  </div>

  <?php if ($orders->num_rows > 0): ?>
    <div class="table-responsive">
      <table class="data-table" id="ordersTable">
        <thead>
          <tr>
            <th>Order Number</th>
            <th>Supplier</th>
            <th>Order Date</th>
            <th>Expected Delivery</th>
            <th>Items</th>
            <th>Total Cost</th>
            <th>Progress</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($order = $orders->fetch_assoc()): ?>
            <?php
            $progress = 0;
            if ($order['total_quantity'] > 0) {
              $progress = ($order['received_quantity'] / $order['total_quantity']) * 100;
            }
            ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
              <td>
                <span class="badge badge-light"><?php echo htmlspecialchars($order['supplier_code']); ?></span><br>
                <?php echo htmlspecialchars($order['supplier_name']); ?>
              </td>
              <td><?php echo formatDate($order['order_date']); ?></td>
              <td>
                <?php
                if ($order['expected_delivery_date']) {
                  echo formatDate($order['expected_delivery_date']);
                  $daysUntil = (strtotime($order['expected_delivery_date']) - time()) / (60 * 60 * 24);
                  if ($daysUntil < 0 && $order['status'] !== 'received') {
                    echo '<br><span class="badge badge-danger">Overdue</span>';
                  } elseif ($daysUntil <= 3 && $order['status'] !== 'received') {
                    echo '<br><span class="badge badge-warning">Soon</span>';
                  }
                } else {
                  echo '-';
                }
                ?>
              </td>
              <td><span class="badge badge-light"><?php echo $order['item_count']; ?> items</span></td>
              <td><strong>₱<?php echo number_format($order['total_cost'], 2); ?></strong></td>
              <td>
                <div style="width: 100px;">
                  <div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
                    <div style="background: <?php echo $progress == 100 ? '#4CAF50' : '#FFC107'; ?>; width: <?php echo $progress; ?>%; height: 100%;"></div>
                  </div>
                  <small><?php echo round($progress); ?>%</small>
                </div>
              </td>
              <td>
                <?php
                $statusBadges = [
                  'pending' => 'badge-warning',
                  'partial' => 'badge-info',
                  'received' => 'badge-success',
                  'cancelled' => 'badge-secondary'
                ];
                $badgeClass = $statusBadges[$order['status']] ?? 'badge-light';
                ?>
                <span class="badge <?php echo $badgeClass; ?>">
                  <?php echo ucfirst($order['status']); ?>
                </span>
              </td>
              <td class="action-buttons">
                <a href="view_procurement_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                    <path d="M7 2C3.5 2 0.5 4.7 0 7c0.5 2.3 3.5 5 7 5s6.5-2.7 7-5c-0.5-2.3-3.5-5-7-5zm0 8c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3-1.3 3-3 3z" />
                  </svg>
                  View
                </a>
                <?php if ($order['status'] === 'pending' || $order['status'] === 'partial'): ?>
                  <a href="receive_procurement.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-success">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                      <path d="M13.5 2L6 9.5 2.5 6 0 8.5 6 14.5 16 4.5z" />
                    </svg>
                    Receive
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="alert alert-info">
      No procurement orders found. <a href="create_procurement_order.php">Create your first order</a>.
    </div>
  <?php endif; ?>
</div>

<script>
  // Initialize DataTable
  $(document).ready(function() {
    $('#ordersTable').DataTable({
      responsive: true,
      order: [
        [2, 'desc']
      ],
      pageLength: 25,
      language: {
        search: "Search orders:",
        lengthMenu: "Show _MENU_ orders per page"
      }
    });
  });
</script>

<style>
  .action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
  }
</style>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>