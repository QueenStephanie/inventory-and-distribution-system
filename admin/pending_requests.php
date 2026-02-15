<?php
/**
 * Pending Requests - Admin
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Pending Requests');

$user = getCurrentUser();
$conn = getDBConnection();

// Get all pending requisitions
$query = "SELECT sr.*, b.branch_name, u.full_name as requested_by_name,
          (SELECT COUNT(*) FROM requisition_items WHERE requisition_id = sr.requisition_id) as item_count
          FROM stock_requisitions sr 
          JOIN branches b ON sr.requesting_branch_id = b.branch_id
          JOIN users u ON sr.requested_by = u.user_id
          WHERE sr.status = 'pending' 
          ORDER BY sr.request_date ASC";
$requisitions = $conn->query($query);

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
<li class="menu-item active">
    <a href="pending_requests.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M18 2H2C0.9 2 0 2.9 0 4v12c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H2V6h16v10z"/>
        </svg>
        Pending Requests
    </a>
</li>
<li class="menu-item">
    <a href="approved_requests.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M16 0H4C2.9 0 2 0.9 2 2v16c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V2c0-1.1-0.9-2-2-2zm-6 15l-5-5 1.41-1.41L10 12.17l6.59-6.59L18 7l-8 8z"/>
        </svg>
        Approved Requests
    </a>
</li>
<li class="menu-item">
    <a href="all_requests.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M17 0H3C1.9 0 1 0.9 1 2v12c0 1.1 0.9 2 2 2h11l5 4V2c0-1.1-0.9-2-2-2z"/>
        </svg>
        All Requests
    </a>
</li>
<li class="menu-item">
    <a href="commissary_inventory.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2 2h16v16H2V2zm2 2v12h12V4H4z"/>
        </svg>
        Commissary Inventory
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<!-- Page Content -->
<div class="page-header">
    <h1>Pending Stock Requests</h1>
    <p>Review and approve stock requisitions from branches</p>
</div>

<div class="content-section">
    <?php if ($requisitions->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Branch</th>
                        <th>Requested By</th>
                        <th>Request Date</th>
                        <th>Items</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = $requisitions->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['requisition_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['requested_by_name']); ?></td>
                            <td><?php echo formatDateTime($req['request_date']); ?></td>
                            <td><span class="badge badge-light"><?php echo $req['item_count']; ?> items</span></td>
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
            <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#4CAF50" opacity="0.1"/>
                <path d="M30 15L45 30L30 45L15 30L30 15Z" fill="#4CAF50" opacity="0.3"/>
            </svg>
            <p>No pending requests at the moment</p>
            <p class="text-muted">All requests have been processed</p>
        </div>
    <?php endif; ?>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
