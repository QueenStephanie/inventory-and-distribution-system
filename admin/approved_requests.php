<?php

/**
 * Approved Requests - Admin
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Approved Requests');

$user = getCurrentUser();
$conn = getDBConnection();

$query = "SELECT sr.*, b.branch_name, u.full_name as requested_by_name, u2.full_name as approved_by_name
          FROM stock_requisitions sr 
          JOIN branches b ON sr.requesting_branch_id = b.branch_id
          JOIN users u ON sr.requested_by = u.user_id
          LEFT JOIN users u2 ON sr.approved_by = u2.user_id
          WHERE sr.status IN ('approved', 'partially_approved') 
          ORDER BY sr.approval_date ASC";
$requisitions = $conn->query($query);

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
<li class="menu-item active">
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
<li class="menu-item">
    <a href="procurement_orders.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M17 2H3C1.9 2 1 2.9 1 4v12c0 1.1 0.9 2 2 2h14c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H3V6h14v10z" />
        </svg>
        Procurement Orders
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<div class="page-header">
    <h1>Approved Requests - Awaiting Dispatch</h1>
    <p>Dispatch approved stock to branches</p>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'dispatched'): ?>
    <div class="alert alert-success">Stock has been dispatched successfully!</div>
<?php endif; ?>

<div class="content-section">
    <?php if ($requisitions->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Branch</th>
                        <th>Requested By</th>
                        <th>Approved By</th>
                        <th>Approval Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = $requisitions->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['requisition_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['requested_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['approved_by_name']); ?></td>
                            <td><?php echo formatDateTime($req['approval_date']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $req['status'] === 'partially_approved' ? 'warning' : 'info'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="dispatch_request.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-success">Dispatch Stock</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No approved requests awaiting dispatch</p>
        </div>
    <?php endif; ?>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>