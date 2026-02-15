<?php
/**
 * View Request Details Page - Branch User
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('branch_user');

define('PAGE_TITLE', 'Request Details');

$user = getCurrentUser();
$conn = getDBConnection();

$requestId = intval($_GET['id'] ?? 0);

// Get requisition details
$query = "SELECT sr.*, u.full_name as requested_by_name, u2.full_name as approved_by_name, u3.full_name as dispatched_by_name, b.branch_name
          FROM stock_requisitions sr 
          JOIN users u ON sr.requested_by = u.user_id
          LEFT JOIN users u2 ON sr.approved_by = u2.user_id
          LEFT JOIN users u3 ON sr.dispatched_by = u3.user_id
          JOIN branches b ON sr.requesting_branch_id = b.branch_id
          WHERE sr.requisition_id = ? AND sr.requesting_branch_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $requestId, $user['branch_id']);
$stmt->execute();
$requisition = $stmt->get_result()->fetch_assoc();

if (!$requisition) {
    header("Location: view_requests.php");
    exit();
}

// Get requisition items
$itemsQuery = "SELECT ri.*, rm.material_code, rm.material_name, rm.category
               FROM requisition_items ri
               JOIN raw_materials rm ON ri.material_id = rm.material_id
               WHERE ri.requisition_id = ?";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $requestId);
$stmt->execute();
$items = $stmt->get_result();

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
    <div>
        <h1>Request Details</h1>
        <p>Requisition #<?php echo htmlspecialchars($requisition['requisition_number']); ?></p>
    </div>
    <a href="view_requests.php" class="btn btn-secondary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M16 7H3.83l5.59-5.59L8 0 0 8l8 8 1.41-1.41L3.83 9H16z"/>
        </svg>
        Back to List
    </a>
</div>

<div class="content-section">
    <div class="detail-grid">
        <div class="detail-item">
            <label>Branch</label>
            <value><?php echo htmlspecialchars($requisition['branch_name']); ?></value>
        </div>
        <div class="detail-item">
            <label>Requested By</label>
            <value><?php echo htmlspecialchars($requisition['requested_by_name']); ?></value>
        </div>
        <div class="detail-item">
            <label>Request Date</label>
            <value><?php echo formatDateTime($requisition['request_date']); ?></value>
        </div>
        <div class="detail-item">
            <label>Status</label>
            <value>
                <span class="badge badge-<?php 
                    echo $requisition['status'] === 'pending' ? 'warning' : 
                        ($requisition['status'] === 'approved' ? 'info' :
                        ($requisition['status'] === 'dispatched' ? 'success' : 
                        ($requisition['status'] === 'partially_approved' ? 'warning' : 'danger'))); 
                ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $requisition['status'])); ?>
                </span>
            </value>
        </div>
        <?php if ($requisition['approved_by_name']): ?>
        <div class="detail-item">
            <label>Approved By</label>
            <value><?php echo htmlspecialchars($requisition['approved_by_name']); ?></value>
        </div>
        <div class="detail-item">
            <label>Approval Date</label>
            <value><?php echo formatDateTime($requisition['approval_date']); ?></value>
        </div>
        <?php endif; ?>
        <?php if ($requisition['dispatched_by_name']): ?>
        <div class="detail-item">
            <label>Dispatched By</label>
            <value><?php echo htmlspecialchars($requisition['dispatched_by_name']); ?></value>
        </div>
        <div class="detail-item">
            <label>Dispatch Date</label>
            <value><?php echo formatDateTime($requisition['dispatch_date']); ?></value>
        </div>
        <?php endif; ?>
        <?php if ($requisition['notes']): ?>
        <div class="detail-item" style="grid-column: 1 / -1;">
            <label>Notes</label>
            <value><?php echo nl2br(htmlspecialchars($requisition['notes'])); ?></value>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="content-section">
    <h2>Requested Items</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Material Name</th>
                    <th>Category</th>
                    <th>Requested Qty</th>
                    <th>Approved Qty</th>
                    <th>Dispatched Qty</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['material_code']); ?></td>
                        <td><strong><?php echo htmlspecialchars($item['material_name']); ?></strong></td>
                        <td><span class="badge badge-light"><?php echo ucfirst($item['category']); ?></span></td>
                        <td><?php echo formatNumber($item['requested_quantity']); ?></td>
                        <td>
                            <?php 
                            if ($item['approved_quantity'] !== null) {
                                echo formatNumber($item['approved_quantity']);
                                if ($item['approved_quantity'] != $item['requested_quantity']) {
                                    echo ' <span class="text-warning">⚠</span>';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo $item['dispatched_quantity'] !== null ? formatNumber($item['dispatched_quantity']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
