<?php
/**
 * Commissary Inventory - Admin
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Commissary Inventory');

$user = getCurrentUser();
$conn = getDBConnection();

// Get commissary inventory
$query = "SELECT rm.material_id, rm.material_code, rm.material_name, rm.category, rm.unit_of_measure, 
          i.current_quantity, rm.minimum_stock_level,
          CASE 
              WHEN i.current_quantity <= rm.minimum_stock_level THEN 'LOW'
              WHEN i.current_quantity <= (rm.minimum_stock_level * 1.5) THEN 'MEDIUM'
              ELSE 'ADEQUATE'
          END as stock_status
          FROM inventory i
          JOIN raw_materials rm ON i.material_id = rm.material_id
          JOIN branches b ON i.branch_id = b.branch_id
          WHERE b.is_main_branch = TRUE AND rm.status = 'active'
          ORDER BY stock_status, rm.category, rm.material_name";
$inventory = $conn->query($query);

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
<li class="menu-item">
    <a href="all_requests.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M17 0H3C1.9 0 1 0.9 1 2v12c0 1.1 0.9 2 2 2h11l5 4V2c0-1.1-0.9-2-2-2z"/>
        </svg>
        All Requests
    </a>
</li>
<li class="menu-item active">
    <a href="commissary_inventory.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2 2h16v16H2V2zm2 2v12h12V4H4z"/>
        </svg>
        Commissary Inventory
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<div class="page-header">
    <h1>Main Commissary Inventory</h1>
    <p>Current stock levels at the main commissary</p>
</div>

<div class="content-section">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Material Name</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Unit</th>
                    <th>Min Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $inventory->fetch_assoc()): ?>
                    <tr class="<?php echo $item['stock_status'] === 'LOW' ? 'row-danger' : ($item['stock_status'] === 'MEDIUM' ? 'row-warning' : ''); ?>">
                        <td><?php echo htmlspecialchars($item['material_code']); ?></td>
                        <td><strong><?php echo htmlspecialchars($item['material_name']); ?></strong></td>
                        <td><span class="badge badge-light"><?php echo ucfirst($item['category']); ?></span></td>
                        <td><strong><?php echo formatNumber($item['current_quantity']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                        <td><?php echo formatNumber($item['minimum_stock_level']); ?></td>
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
