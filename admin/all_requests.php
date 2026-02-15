<?php
/**
 * All Requests History - Admin
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'All Requests');

$user = getCurrentUser();
$conn = getDBConnection();

// Filter handling
$statusFilter = $_GET['status'] ?? 'all';
$branchFilter = intval($_GET['branch'] ?? 0);

$query = "SELECT sr.*, b.branch_name, u.full_name as requested_by_name
          FROM stock_requisitions sr 
          JOIN branches b ON sr.requesting_branch_id = b.branch_id
          JOIN users u ON sr.requested_by = u.user_id
          WHERE 1=1";

if ($statusFilter !== 'all') {
    $query .= " AND sr.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

if ($branchFilter > 0) {
    $query .= " AND sr.requesting_branch_id = " . $branchFilter;
}

$query .= " ORDER BY sr.request_date DESC LIMIT 100";
$requisitions = $conn->query($query);

// Get branches for filter
$branches = $conn->query("SELECT branch_id, branch_name FROM branches WHERE is_main_branch = FALSE ORDER BY branch_name");

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
<li class="menu-item active">
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

<div class="page-header">
    <h1>All Stock Requests</h1>
    <p>View complete history of requisitions</p>
</div>

<div class="filter-section">
    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <label>Status:</label>
            <select name="status" class="form-control">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="dispatched" <?php echo $statusFilter === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                <option value="rejected" <?php echo$statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Branch:</label>
            <select name="branch" class="form-control">
                <option value="0">All Branches</option>
                <?php while ($branch = $branches->fetch_assoc()): ?>
                    <option value="<?php echo $branch['branch_id']; ?>" <?php echo $branchFilter === $branch['branch_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="all_requests.php" class="btn btn-secondary">Clear</a>
    </form>
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
                            <td><?php echo formatDateTime($req['request_date']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $req['status'] === 'pending' ? 'warning' : 
                                        ($req['status'] === 'approved' || $req['status'] === 'partially_approved' ? 'info' :
                                        ($req['status'] === 'dispatched' ? 'success' : 'danger')); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <a href="review_request.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                <?php elseif (in_array($req['status'], ['approved', 'partially_approved'])): ?>
                                    <a href="dispatch_request.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-success">Dispatch</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No requests found matching your criteria</p>
        </div>
    <?php endif; ?>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
