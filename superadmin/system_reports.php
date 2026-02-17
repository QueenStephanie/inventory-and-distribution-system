<?php
/**
 * System Reports - Superadmin
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('superadmin');

define('PAGE_TITLE', 'System Reports');

$user = getCurrentUser();
$conn = getDBConnection();

// Get system statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalBranches = $conn->query("SELECT COUNT(*) as count FROM branches WHERE is_main_branch = FALSE")->fetch_assoc()['count'];
$totalRequisitions = $conn->query("SELECT COUNT(*) as count FROM stock_requisitions")->fetch_assoc()['count'];
$dispatchedRequisitions = $conn->query("SELECT COUNT(*) as count FROM stock_requisitions WHERE status = 'dispatched'")->fetch_assoc()['count'];
$totalMaterials = $conn->query("SELECT COUNT(*) as count FROM raw_materials WHERE status = 'active'")->fetch_assoc()['count'];
$totalStockMovements = $conn->query("SELECT COUNT(*) as count FROM stock_movements")->fetch_assoc()['count'];

// Get branch performance
$branchPerfQuery = "SELECT b.branch_name,
                    COUNT(DISTINCT sr.requisition_id) as total_requests,
                    SUM(CASE WHEN sr.status = 'dispatched' THEN 1 ELSE 0 END) as completed_requests,
                    COUNT(DISTINCT psc.count_id) as stock_counts
                    FROM branches b
                    LEFT JOIN stock_requisitions sr ON b.branch_id = sr.requesting_branch_id
                    LEFT JOIN physical_stock_counts psc ON b.branch_id = psc.branch_id
                    WHERE b.is_main_branch = FALSE
                    GROUP BY b.branch_id
                    ORDER BY total_requests DESC";
$branchPerf = $conn->query($branchPerfQuery);

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
    <a href="variance_report.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M18 2H2C0.9 2 0 2.9 0 4v12c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zM9 13H7v-2h2v2zm0-4H7V5h2v4zm4 4h-2V9h2v4zm0-6h-2V5h2v2z"/>
        </svg>
        Variance Reports
    </a>
</li>
<li class="menu-item">
    <a href="manage_users.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 0C7.79 0 6 1.79 6 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 10c-4.42 0-8 1.79-8 4v2h16v-2c0-2.21-3.58-4-8-4z"/>
        </svg>
        Manage Users
    </a>
</li>
<li class="menu-item">
    <a href="manage_branches.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 0L0 6v10h6v-6h8v6h6V6L10 0z"/>
        </svg>
        Manage Branches
    </a>
</li>
<li class="menu-item">
    <a href="manage_materials.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M18 2H2C0.9 2 0 2.9 0 4v12c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm-1 13H3V5h14v10z"/>
        </svg>
        Manage Materials
    </a>
</li>
<li class="menu-item active">
    <a href="system_reports.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M4 2h12c1.1 0 2 0.9 2 2v12c0 1.1-0.9 2-2 2H4c-1.1 0-2-0.9-2-2V4c0-1.1 0.9-2 2-2zm0 14h4V8H4v8zm6 0h4v-6h-4v6zm6 0h4v-4h-4v4z"/>
        </svg>
        System Reports
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<div class="page-header">
    <h1>System Reports & Analytics</h1>
    <p>Overall system performance and statistics</p>
</div>

<!-- System Overview -->
<div class="content-section">
    <h2>System Overview</h2>
    <div class="stats-grid">
        <div class="stat-card stat-info">
            <div class="stat-content">
                <div class="stat-value"><?php echo $totalBranches; ?></div>
                <div class="stat-label">Total Branches</div>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-content">
                <div class="stat-value"><?php echo $totalUsers; ?></div>
                <div class="stat-label">System Users</div>
            </div>
        </div>
        <div class="stat-card stat-primary">
            <div class="stat-content">
                <div class="stat-value"><?php echo $totalMaterials; ?></div>
                <div class="stat-label">Active Materials</div>
            </div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-content">
                <div class="stat-value"><?php echo $totalRequisitions; ?></div>
                <div class="stat-label">Total Requisitions</div>
            </div>
        </div>
    </div>
</div>

<!-- Requisition Statistics -->
<div class="content-section">
    <h2>Requisition Performance</h2>
    <div class="stats-grid">
        <div class="stat-card stat-success">
            <div class="stat-content">
                <div class="stat-value"><?php echo $dispatchedRequisitions; ?></div>
                <div class="stat-label">Completed Requisitions</div>
                <div class="stat-extra">
                    <?php 
                    $completionRate = $totalRequisitions > 0 ? round(($dispatchedRequisitions / $totalRequisitions) * 100) : 0;
                    echo $completionRate; 
                    ?>% completion rate
                </div>
            </div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-content">
                <div class="stat-value"><?php echo $totalStockMovements; ?></div>
                <div class="stat-label">Total Stock Movements</div>
                <div class="stat-extra">All inventory transactions</div>
            </div>
        </div>
    </div>
</div>

<!-- Branch Performance -->
<div class="content-section">
    <h2>Branch Performance</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Branch Name</th>
                    <th>Total Requests</th>
                    <th>Completed Requests</th>
                    <th>Stock Counts Submitted</th>
                    <th>Completion Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($perf = $branchPerf->fetch_assoc()): 
                    $rate = $perf['total_requests'] > 0 ? round(($perf['completed_requests'] / $perf['total_requests']) * 100) : 0;
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($perf['branch_name']); ?></strong></td>
                        <td><?php echo $perf['total_requests']; ?></td>
                        <td><?php echo $perf['completed_requests']; ?></td>
                        <td><?php echo $perf['stock_counts']; ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $rate; ?>%"></div>
                                <span class="progress-text"><?php echo $rate; ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.progress-bar {
    width: 100%;
    height: 30px;
    background-color: #f0f0f0;
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background-color: #4CAF50;
    transition: width 0.3s ease;
}
.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: bold;
    color: #333;
}
</style>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
