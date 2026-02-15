<?php
/**
 * Branch User Dashboard
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('branch_user');

define('PAGE_TITLE', 'Branch Dashboard');

$user = getCurrentUser();
$conn = getDBConnection();

// Get branch statistics
$branchId = $user['branch_id'];

// Count pending requisitions
$pendingQuery = "SELECT COUNT(*) as count FROM stock_requisitions WHERE requesting_branch_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pendingQuery);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['count'];

// Count low stock items
$lowStockQuery = "SELECT COUNT(*) as count FROM inventory i 
                  JOIN raw_materials rm ON i.material_id = rm.material_id 
                  WHERE i.branch_id = ? AND i.current_quantity <= rm.minimum_stock_level";
$stmt = $conn->prepare($lowStockQuery);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$lowStockCount = $stmt->get_result()->fetch_assoc()['count'];

// Get recent requisitions
$recentQuery = "SELECT sr.*, u.full_name as approved_by_name 
                FROM stock_requisitions sr 
                LEFT JOIN users u ON sr.approved_by = u.user_id
                WHERE sr.requesting_branch_id = ? 
                ORDER BY sr.request_date DESC LIMIT 5";
$stmt = $conn->prepare($recentQuery);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$recentRequisitions = $stmt->get_result();

// Get current inventory levels
$inventoryQuery = "SELECT rm.material_name, rm.category, i.current_quantity, rm.unit_of_measure, rm.minimum_stock_level,
                   CASE 
                       WHEN i.current_quantity <= rm.minimum_stock_level THEN 'LOW'
                       WHEN i.current_quantity <= (rm.minimum_stock_level * 1.5) THEN 'MEDIUM'
                       ELSE 'ADEQUATE'
                   END as stock_status
                   FROM inventory i
                   JOIN raw_materials rm ON i.material_id = rm.material_id
                   WHERE i.branch_id = ? AND rm.status = 'active'
                   ORDER BY stock_status, rm.material_name";
$stmt = $conn->prepare($inventoryQuery);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$inventoryItems = $stmt->get_result();

include '../includes/header.php';
?>

<!-- Sidebar Menu -->
<li class="menu-item active">
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
<li class="menu-item">
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

<!-- Dashboard Content -->
<div class="page-header">
    <h1>Branch Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
    <p class="branch-info"><strong>Branch:</strong> <?php echo htmlspecialchars($user['branch_name']); ?></p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M20 4L4 14v18h12v-10h8v10h12V14L20 4z"/>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $pendingCount; ?></div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>

    <div class="stat-card stat-danger">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M20 4C11.16 4 4 11.16 4 20s7.16 16 16 16 16-7.16 16-16S28.84 4 20 4zm2 24h-4v-4h4v4zm0-8h-4V8h4v12z"/>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $lowStockCount; ?></div>
            <div class="stat-label">Low Stock Items</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="request_stock.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M8 0L0 6v10h6v-6h4v6h6V6L8 0z"/>
        </svg>
        New Stock Request
    </a>
    <a href="stock_count.php" class="btn btn-secondary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M14 0H2C0.9 0 0.01 0.9 0.01 2L0 14c0 1.1 0.89 2 1.99 2H14c1.1 0 2-0.9 2-2V2c0-1.1-0.9-2-2-2z"/>
        </svg>
        Input Physical Count
    </a>
</div>

<!-- Recent Requisitions -->
<div class="content-section">
    <div class="section-header">
        <h2>Recent Stock Requests</h2>
        <a href="view_requests.php" class="btn btn-link">View All</a>
    </div>
    
    <?php if ($recentRequisitions->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = $recentRequisitions->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['requisition_number']); ?></strong></td>
                            <td><?php echo formatDateTime($req['request_date']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $req['status'] === 'pending' ? 'warning' : 
                                        ($req['status'] === 'approved' ? 'info' :
                                        ($req['status'] === 'dispatched' ? 'success' : 'danger')); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $req['approved_by_name'] ?: 'N/A'; ?></td>
                            <td>
                                <a href="view_request_details.php?id=<?php echo $req['requisition_id']; ?>" class="btn btn-sm btn-info">View</a>
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
            <p>No stock requests yet</p>
            <a href="request_stock.php" class="btn btn-primary">Create First Request</a>
        </div>
    <?php endif; ?>
</div>

<!-- Current Inventory -->
<div class="content-section">
    <div class="section-header">
        <h2>Current Inventory Levels</h2>
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
                        <td><?php echo formatNumber($item['minimum_stock_level']); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $item['stock_status'] === 'LOW' ? 'danger' : 
                                    ($item['stock_status'] === 'MEDIUM' ? 'warning' : 'success'); 
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

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
