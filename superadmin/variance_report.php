<?php
/**
 * Variance Report - Superadmin
 * Critical feature to identify inventory shrinkage and losses
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('superadmin');

define('PAGE_TITLE', 'Variance Report');

$user = getCurrentUser();
$conn = getDBConnection();

// Filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$branchFilter = intval($_GET['branch'] ?? 0);
$varianceType = $_GET['variance_type'] ?? 'all';

// Build query
$query = "SELECT psc.count_date, b.branch_name, rm.material_name, rm.category,
          psc.system_quantity, psc.physical_quantity, psc.variance,
          CASE 
              WHEN psc.variance < 0 THEN 'SHORTAGE'
              WHEN psc.variance > 0 THEN 'OVERAGE'
              ELSE 'MATCHED'
          END as variance_type,
          u.full_name as counted_by
          FROM physical_stock_counts psc
          JOIN branches b ON psc.branch_id = b.branch_id
          JOIN raw_materials rm ON psc.material_id = rm.material_id
          JOIN users u ON psc.counted_by = u.user_id
          WHERE psc.count_date BETWEEN ? AND ?";

if ($branchFilter > 0) {
    $query .= " AND psc.branch_id = " . $branchFilter;
}

if ($varianceType === 'shortage') {
    $query .= " AND psc.variance < 0";
} elseif ($varianceType === 'overage') {
    $query .= " AND psc.variance > 0";
} elseif ($varianceType === 'matched') {
    $query .= " AND psc.variance = 0";
}

$query .= " ORDER BY psc.count_date DESC, ABS(psc.variance) DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$variances = $stmt->get_result();

// Calculate totals
$totalsQuery = "SELECT 
                SUM(CASE WHEN variance < 0 THEN ABS(variance) ELSE 0 END) as total_shortage,
                SUM(CASE WHEN variance > 0 THEN variance ELSE 0 END) as total_overage,
                COUNT(CASE WHEN variance < 0 THEN 1 END) as shortage_count,
                COUNT(CASE WHEN variance > 0 THEN 1 END) as overage_count,
                COUNT(CASE WHEN variance = 0 THEN 1 END) as matched_count
                FROM physical_stock_counts
                WHERE count_date BETWEEN ? AND ?";
if ($branchFilter > 0) {
    $totalsQuery .= " AND branch_id = " . $branchFilter;
}

$stmt = $conn->prepare($totalsQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// Get branches for filter
$branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name");

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
    <h1>📊 Inventory Variance Report</h1>
    <p>Track discrepancies between expected and actual inventory levels</p>
</div>

<div class="alert alert-info">
    <strong>🎯 Purpose:</strong> This report identifies inventory shrinkage (missing items) by comparing system records against physical counts. 
    Negative variances indicate potential theft, spoilage, or recording errors.
</div>

<!-- Filter Section -->
<div class="filter-section">
    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <label>From Date:</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
        </div>
        <div class="filter-group">
            <label>To Date:</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="filter-group">
            <label>Branch:</label>
            <select name="branch" class="form-control">
                <option value="0">All Branches</option>
                <?php 
                $branches->data_seek(0);
                while ($branch = $branches->fetch_assoc()): 
                ?>
                    <option value="<?php echo $branch['branch_id']; ?>" <?php echo $branchFilter === $branch['branch_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Type:</label>
            <select name="variance_type" class="form-control">
                <option value="all" <?php echo $varianceType === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="shortage" <?php echo $varianceType === 'shortage' ? 'selected' : ''; ?>>Shortages Only</option>
                <option value="overage" <?php echo $varianceType === 'overage' ? 'selected' : ''; ?>>Overages Only</option>
                <option value="matched" <?php echo $varianceType === 'matched' ? 'selected' : ''; ?>>Matched Only</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Apply Filters</button>
        <a href="variance_report.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card stat-danger">
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totals['total_shortage']); ?></div>
            <div class="stat-label">Total Units Lost (Shortage)</div>
            <div class="stat-extra"><?php echo $totals['shortage_count']; ?> incidents</div>
        </div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-content">
            <div class="stat-value"><?php echo formatNumber($totals['total_overage']); ?></div>
            <div class="stat-label">Total Units Over (Overage)</div>
            <div class="stat-extra"><?php echo $totals['overage_count']; ?> incidents</div>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-content">
            <div class="stat-value"><?php echo $totals['matched_count']; ?></div>
            <div class="stat-label">Perfect Matches</div>
            <div class="stat-extra">No discrepancies</div>
        </div>
    </div>
</div>

<!-- Variance Table -->
<div class="content-section">
    <div class="section-header">
        <h2>Detailed Variance Records</h2>
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
    </div>
    
    <?php if ($variances->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Material</th>
                        <th>Category</th>
                        <th>Expected</th>
                        <th>Actual</th>
                        <th>Variance</th>
                        <th>Type</th>
                        <th>Counted By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($variance = $variances->fetch_assoc()): ?>
                        <tr class="<?php 
                            echo $variance['variance_type'] === 'SHORTAGE' ? 'row-danger' : 
                                ($variance['variance_type'] === 'OVERAGE' ? 'row-warning' : ''); 
                        ?>">
                            <td><?php echo formatDate($variance['count_date']); ?></td>
                            <td><?php echo htmlspecialchars($variance['branch_name']); ?></td>
                            <td><strong><?php echo htmlspecialchars($variance['material_name']); ?></strong></td>
                            <td><span class="badge badge-light"><?php echo ucfirst($variance['category']); ?></span></td>
                            <td><?php echo formatNumber($variance['system_quantity']); ?></td>
                            <td><?php echo formatNumber($variance['physical_quantity']); ?></td>
                            <td>
                                <strong class="<?php 
                                    echo $variance['variance_type'] === 'SHORTAGE' ? 'text-danger' : 
                                        ($variance['variance_type'] === 'OVERAGE' ? 'text-warning' : 'text-success'); 
                                ?>">
                                    <?php echo $variance['variance'] > 0 ? '+' : ''; ?><?php echo formatNumber($variance['variance']); ?>
                                </strong>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $variance['variance_type'] === 'SHORTAGE' ? 'danger' : 
                                        ($variance['variance_type'] === 'OVERAGE' ? 'warning' : 'success'); 
                                ?>">
                                    <?php echo $variance['variance_type']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($variance['counted_by']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No variance records found for the selected period</p>
            <p class="text-muted">Adjust the filters or check if physical counts have been recorded</p>
        </div>
    <?php endif; ?>
</div>

<!-- Analysis Tips -->
<div class="tip-box">
    <h3>💡 How to Use This Report:</h3>
    <ul>
        <li><strong>Red rows (Shortages):</strong> Investigate immediately. These represent missing inventory that could indicate theft, waste, or recording errors.</li>
        <li><strong>Yellow rows (Overages):</strong> Review for potential data entry mistakes or unreported stock receipts.</li>
        <li><strong>Look for patterns:</strong> Recurring shortages at specific branches or for specific materials may indicate systemic issues.</li>
        <li><strong>Track by date:</strong> Use date filters to compare week-over-week or month-over-month trends.</li>
    </ul>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
