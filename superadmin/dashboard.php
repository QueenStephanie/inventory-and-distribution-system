<?php
/**
 * Superadmin Dashboard
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('superadmin');

define('PAGE_TITLE', 'Superadmin Dashboard');

$user = getCurrentUser();
$conn = getDBConnection();

// Get statistics
$branchCount = $conn->query("SELECT COUNT(*) as count FROM branches WHERE is_main_branch = FALSE")->fetch_assoc()['count'];
$userCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$totalRequisitions = $conn->query("SELECT COUNT(*) as count FROM stock_requisitions")->fetch_assoc()['count'];

// Get recent variance issues
$varianceQuery = "SELECT psc.count_date, b.branch_name, rm.material_name, psc.variance,
                  CASE WHEN psc.variance < 0 THEN 'SHORTAGE' ELSE 'OVERAGE' END as type
                  FROM physical_stock_counts psc
                  JOIN branches b ON psc.branch_id = b.branch_id
                  JOIN raw_materials rm ON psc.material_id = rm.material_id
                  WHERE ABS(psc.variance) > 0
                  ORDER BY psc.count_date DESC, ABS(psc.variance) DESC
                  LIMIT 10";
$variances = $conn->query($varianceQuery);

// Calculate total variance (losses)
$totalLossQuery = "SELECT SUM(CASE WHEN variance < 0 THEN ABS(variance) ELSE 0 END) as total_loss
                   FROM physical_stock_counts
                   WHERE MONTH(count_date) = MONTH(CURRENT_DATE())
                   AND YEAR(count_date) = YEAR(CURRENT_DATE())";
$totalLoss = $conn->query($totalLossQuery)->fetch_assoc()['total_loss'] ?? 0;

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
<li class="menu-item">
    <a href="system_reports.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M4 2h12c1.1 0 2 0.9 2 2v12c0 1.1-0.9 2-2 2H4c-1.1 0-2-0.9-2-2V4c0-1.1 0.9-2 2-2zm0 14h4V8H4v8zm6 0h4v-6h-4v6zm6 0h4v-4h-4v4z"/>
        </svg>
        System Reports
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<div class="page-header">
    <h1>Superadmin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>! You have full system access.</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M20 4L4 14v18h12v-10h8v10h12V14L20 4z"/>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $branchCount; ?></div>
            <div class="stat-label">Active Branches</div>
        </div>
    </div>

    <div class="stat-card stat-info">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M20 4C14.48 4 10 8.48 10 14s4.48 10 10 10 10-4.48 10-10S25.52 4 20 4zm0 18c-8.84 0-16 3.58-16 8v4h32v-4c0-4.42-7.16-8-16-8z"/>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $userCount; ?></div>
            <div class="stat-label">Active Users</div>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M34 4H6C3.8 4 2 5.8 2 8v20c0 2.2 1.8 4 4 4h22l8 8V8c0-2.2-1.8-4-4-4z"/>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalRequisitions; ?></div>
            <div class="stat-label">Total Requisitions</div>
        </div>
    </div>

    <div class="stat-card stat-danger">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="currentColor">
                <path d="M20 4C11.16 4 4 11.16 4 20s7.16 16 16 16 16-7.16 16-16S28.84 4 20 4zm2 24h-4v-4h4v4zm0-8h-4V8h4v12z"/>
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totalLoss); ?></div>
            <div class="stat-label">Units Lost This Month</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="variance_report.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M14 2H2C0.9 2 0 2.9 0 4v8c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2z"/>
        </svg>
        View Variance Reports
    </a>
    <a href="manage_users.php" class="btn btn-success">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M14 7h-3V4c0-0.55-0.45-1-1-1H6c-0.55 0-1 0.45-1 1v3H2c-0.55 0-1 0.45-1 1v4c0 0.55 0.45 1 1 1h3v3c0 0.55 0.45 1 1 1h4c0.55 0 1-0.45 1-1v-3h3c0.55 0 1-0.45 1-1V8c0-0.55-0.45-1-1-1z"/>
        </svg>
        Add New User
    </a>
    <a href="manage_branches.php" class="btn btn-info">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M8 0L0 5v8h5v-5h6v5h5V5L8 0z"/>
        </svg>
        Manage Branches
    </a>
</div>

<!-- Recent Variance Issues -->
<div class="content-section">
    <div class="section-header">
        <h2>Recent Inventory Variances</h2>
        <a href="variance_report.php" class="btn btn-link">View Full Report</a>
    </div>
    
    <?php if ($variances->num_rows > 0): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Attention:</strong> The following discrepancies have been detected. Review them to identify potential shrinkage or errors.
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Material</th>
                        <th>Variance</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($variance = $variances->fetch_assoc()): ?>
                        <tr class="<?php echo $variance['type'] === 'SHORTAGE' ? 'row-danger' : 'row-warning'; ?>">
                            <td><?php echo formatDate($variance['count_date']); ?></td>
                            <td><?php echo htmlspecialchars($variance['branch_name']); ?></td>
                            <td><strong><?php echo htmlspecialchars($variance['material_name']); ?></strong></td>
                            <td><strong><?php echo formatNumber($variance['variance']); ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $variance['type'] === 'SHORTAGE' ? 'danger' : 'warning'; ?>">
                                    <?php echo $variance['type']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <strong>✓ Good News:</strong> No significant inventory discrepancies reported recently.
        </div>
    <?php endif; ?>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
