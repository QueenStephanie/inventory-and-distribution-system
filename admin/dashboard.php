<?php

/**
 * Admin Dashboard
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Admin Dashboard');

$user = getCurrentUser();
$conn = getDBConnection();

// Get statistics
$pendingQuery = "SELECT COUNT(*) as count FROM stock_requisitions WHERE status = 'pending'";
$pendingCount = $conn->query($pendingQuery)->fetch_assoc()['count'];

$approvedQuery = "SELECT COUNT(*) as count FROM stock_requisitions WHERE status = 'approved'";
$approvedCount = $conn->query($approvedQuery)->fetch_assoc()['count'];

$lowStockQuery = "SELECT COUNT(*) as count FROM inventory i 
                  JOIN raw_materials rm ON i.material_id = rm.material_id 
                  JOIN branches b ON i.branch_id = b.branch_id
                  WHERE i.current_quantity <= rm.minimum_stock_level AND b.is_main_branch = TRUE";
$lowStockCount = $conn->query($lowStockQuery)->fetch_assoc()['count'];

// Get supplier statistics
$supplierCountQuery = "SELECT COUNT(*) as count FROM suppliers WHERE status = 'active'";
$supplierCount = $conn->query($supplierCountQuery)->fetch_assoc()['count'];

// Get pending procurement orders
$pendingProcurementQuery = "SELECT COUNT(*) as count FROM procurement_orders WHERE status IN ('pending', 'partial')";
$pendingProcurementCount = $conn->query($pendingProcurementQuery)->fetch_assoc()['count'];

// Get pending requisitions
$pendingReqQuery = "SELECT sr.*, b.branch_name, u.full_name as requested_by_name 
                    FROM stock_requisitions sr 
                    JOIN branches b ON sr.requesting_branch_id = b.branch_id
                    JOIN users u ON sr.requested_by = u.user_id
                    WHERE sr.status = 'pending' 
                    ORDER BY sr.request_date ASC";
$pendingRequisitions = $conn->query($pendingReqQuery);

// Get commissary inventory status
$inventoryQuery = "SELECT rm.material_name, rm.category, i.current_quantity, rm.unit_of_measure, rm.minimum_stock_level,
                   CASE 
                       WHEN i.current_quantity <= rm.minimum_stock_level THEN 'LOW'
                       WHEN i.current_quantity <= (rm.minimum_stock_level * 1.5) THEN 'MEDIUM'
                       ELSE 'ADEQUATE'
                   END as stock_status
                   FROM inventory i
                   JOIN raw_materials rm ON i.material_id = rm.material_id
                   JOIN branches b ON i.branch_id = b.branch_id
                   WHERE b.is_main_branch = TRUE AND rm.status = 'active'
                   ORDER BY stock_status, rm.material_name
                   LIMIT 10";
$inventoryItems = $conn->query($inventoryQuery);

// Get recent procurement orders
$recentProcurementQuery = "SELECT po.*, s.supplier_name 
                          FROM procurement_orders po
                          JOIN suppliers s ON po.supplier_id = s.supplier_id
                          ORDER BY po.order_date DESC
                          LIMIT 5";
$recentProcurementOrders = $conn->query($recentProcurementQuery);

include '../includes/header.php';
?>

<!-- Sidebar Menu -->
<li class="menu-item active">
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
        <?php if ($pendingCount > 0): ?>
            <span class="badge badge-warning"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
    </a>
</li>
<li class="menu-item">
    <a href="approved_requests.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M16 0H4C2.9 0 2 0.9 2 2v16c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V2c0-1.1-0.9-2-2-2zm-6 15l-5-5 1.41-1.41L10 12.17l6.59-6.59L18 7l-8 8z" />
        </svg>
        Approved Requests
        <?php if ($approvedCount > 0): ?>
            <span class="badge badge-info"><?php echo $approvedCount; ?></span>
        <?php endif; ?>
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
<li class="menu-item">
    <a href="procurement_orders.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M17 2H3C1.9 2 1 2.9 1 4v12c0 1.1 0.9 2 2 2h14c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H3V6h14v10z" />
        </svg>
        Procurement Orders
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<!-- Dashboard Content -->
<div class="page-header">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M20 4C11.16 4 4 11.16 4 20s7.16 16 16 16 16-7.16 16-16S28.84 4 20 4zm2 24h-4v-4h4v4zm0-8h-4V8h4v12z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $pendingCount; ?></div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>

    <div class="stat-card stat-info">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M20 4L4 14v18h12v-10h8v10h12V14L20 4z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $approvedCount; ?></div>
            <div class="stat-label">Awaiting Dispatch</div>
        </div>
    </div>

    <div class="stat-card stat-danger">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M20 4C11.16 4 4 11.16 4 20s7.16 16 16 16 16-7.16 16-16S28.84 4 20 4z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $lowStockCount; ?></div>
            <div class="stat-label">Low Stock (Commissary)</div>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M32 2H8C5.8 2 4 3.8 4 6v28c0 2.2 1.8 4 4 4h24c2.2 0 4-1.8 4-4V6c0-2.2-1.8-4-4-4zM18 26h-4v-4h4v4zm0-8h-4V10h4v8zm8 8h-4V18h4v8zm0-12h-4V10h4v4z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $supplierCount; ?></div>
            <div class="stat-label">Active Suppliers</div>
        </div>
    </div>

    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M34 4H6C3.8 4 2 5.8 2 8v24c0 2.2 1.8 4 4 4h28c2.2 0 4-1.8 4-4V8c0-2.2-1.8-4-4-4zm0 28H6V12h28v20z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $pendingProcurementCount; ?></div>
            <div class="stat-label">Pending Procurement</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="pending_requests.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M14 0H2C0.9 0 0 0.9 0 2v12c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V2c0-1.1-0.9-2-2-2z" />
        </svg>
        Review Pending Requests
    </a>
    <a href="approved_requests.php" class="btn btn-success">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M0 0v16l16-8L0 0z" />
        </svg>
        Dispatch Approved Stock
    </a>
</div>

<!-- Pending Requisitions -->
<div class="content-section">
    <div class="section-header">
        <h2>Pending Stock Requests</h2>
        <a href="pending_requests.php" class="btn btn-link">View All</a>
    </div>

    <?php if ($pendingRequisitions->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Branch</th>
                        <th>Requested By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = $pendingRequisitions->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['requisition_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['requested_by_name']); ?></td>
                            <td><?php echo formatDateTime($req['request_date']); ?></td>
                            <td>
                                <a href="review_request.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-primary">Review & Approve</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No pending requests at the moment</p>
        </div>
    <?php endif; ?>
</div>

<!-- Commissary Stock Status -->
<div class="content-section">
    <div class="section-header">
        <h2>Commissary Stock Status (Top Items)</h2>
        <a href="commissary_inventory.php" class="btn btn-link">View Full Inventory</a>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Min Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $inventoryItems->fetch_assoc()): ?>
                    <tr class="<?php echo $item['stock_status'] === 'LOW' ? 'row-danger' : ''; ?>">
                        <td><?php echo htmlspecialchars($item['material_name']); ?></td>
                        <td><span class="badge badge-light"><?php echo ucfirst($item['category']); ?></span></td>
                        <td><strong><?php echo formatNumber($item['current_quantity']); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></strong></td>
                        <td><?php echo formatNumber($item['minimum_stock_level']); ?></td>
                        <td>
                            <span class="badge badge-<?php
                                                        echo $item['stock_status'] === 'LOW' ? 'danger' : ($item['stock_status'] === 'MEDIUM' ? 'warning' : 'success');
                                                        ?>">
                                <?php echo $item['stock_status']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Procurement Orders -->
<div class="content-section">
    <div class="section-header">
        <h2>Recent Procurement Orders</h2>
        <a href="procurement_orders.php" class="btn btn-link">View All Orders</a>
    </div>

    <?php if ($recentProcurementOrders->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected Delivery</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recentProcurementOrders->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>
                                <?php if ($order['expected_delivery_date']): ?>
                                    <?php echo date('M d, Y', strtotime($order['expected_delivery_date'])); ?>
                                    <?php
                                    $daysUntil = ceil((strtotime($order['expected_delivery_date']) - time()) / 86400);
                                    if ($order['status'] === 'pending' && $daysUntil < 0):
                                    ?>
                                        <br><span class="badge badge-danger">OVERDUE</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>₱<?php echo number_format($order['total_cost'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $order['status']; ?>">
                                    <?php echo strtoupper($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_procurement_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <?php if (in_array($order['status'], ['pending', 'partial'])): ?>
                                    <a href="receive_procurement.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-success">Receive</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No procurement orders yet. <a href="create_procurement_order.php">Create your first order</a></p>
        </div>
    <?php endif; ?>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>