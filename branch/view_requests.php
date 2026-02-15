<?php
/**
 * View Requests Page - Branch User
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('branch_user');

define('PAGE_TITLE', 'View Requests');

$user = getCurrentUser();
$conn = getDBConnection();

// Get all requisitions for this branch
$branchId = $user['branch_id'];
$query = "SELECT sr.*, u.full_name as approved_by_name, u2.full_name as dispatched_by_name
          FROM stock_requisitions sr 
          LEFT JOIN users u ON sr.approved_by = u.user_id
          LEFT JOIN users u2 ON sr.dispatched_by = u2.user_id
          WHERE sr.requesting_branch_id = ? 
          ORDER BY sr.request_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$requisitions = $stmt->get_result();

include '../includes/header.php';
?>

<!-- Sidebar Menu -->
<li class="menu-item">
    <a href="dashboard.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 0L0 8v12h7v-7h6v7h7V8L10 0z"/>
        </svg>
        Dashboard
    </a>
</li>
<li class="menu-item">
    <a href="request_stock.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M17 0H3C1.9 0 1 0.9 1 2v12c0 1.1 0.9 2 2 2h11l5 4V2c0-1.1-0.9-2-2-2zm-1 12H4V10h12v2zm0-3H4V7h12v2zm0-3H4V4h12v2z"/>
        </svg>
        Request Stock
    </a>
</li>
<li class="menu-item active">
    <a href="view_requests.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M18 2H2C0.9 2 0 2.9 0 4v12c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H2V6h16v10z"/>
        </svg>
        View Requests
    </a>
</li>
<li class="menu-item">
    <a href="stock_count.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M17 0H3C1.9 0 1.01 0.9 1.01 2L1 18c0 1.1 0.89 2 1.99 2H17c1.1 0 2-0.9 2-2V2c0-1.1-0.9-2-2-2zm-5 14H3v-2h9v2zm5-4H3v-2h14v2zm0-4H3V4h14v2z"/>
        </svg>
        Physical Stock Count
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<!-- Page Content -->
<div class="page-header">
    <h1>Stock Requests History</h1>
    <p>View all your stock requisition requests</p>
</div>

<div class="content-section">
    <?php if ($requisitions->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Date Requested</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Approval Date</th>
                        <th>Dispatched By</th>
                        <th>Dispatch Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = $requisitions->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['requisition_number']); ?></strong></td>
                            <td><?php echo formatDateTime($req['request_date']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $req['status'] === 'pending' ? 'warning' : 
                                        ($req['status'] === 'approved' ? 'info' :
                                        ($req['status'] === 'dispatched' ? 'success' : 
                                        ($req['status'] === 'partially_approved' ? 'warning' : 'danger'))); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $req['approved_by_name'] ?: '-'; ?></td>
                            <td><?php echo $req['approval_date'] ? formatDateTime($req['approval_date']) : '-'; ?></td>
                            <td><?php echo $req['dispatched_by_name'] ?: '-'; ?></td>
                            <td><?php echo $req['dispatch_date'] ? formatDateTime($req['dispatch_date']) : '-'; ?></td>
                            <td>
                                <a href="view_request_details.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-info">View Details</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#f0f0f0"/>
                <path d="M30 15v20M30 40v5" stroke="#999" stroke-width="3" stroke-linecap="round"/>
            </svg>
            <p>No stock requests found</p>
            <a href="request_stock.php" class="btn btn-primary">Create New Request</a>
        </div>
    <?php endif; ?>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
