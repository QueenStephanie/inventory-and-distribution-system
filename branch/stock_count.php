<?php
/**
 * Physical Stock Count Page - Branch User
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('branch_user');

define('PAGE_TITLE', 'Physical Stock Count');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_count'])) {
    $branchId = $user['branch_id'];
    $userId = $user['user_id'];
    $countDate = $_POST['count_date'] ?? date('Y-m-d');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $materials = $_POST['materials'] ?? [];
    $physicalCounts = $_POST['physical_counts'] ?? [];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get system quantities and insert physical counts
        $stmt = $conn->prepare("INSERT INTO physical_stock_counts (branch_id, material_id, count_date, system_quantity, physical_quantity, counted_by, notes) 
                               SELECT ?, i.material_id, ?, i.current_quantity, ?, ?, ?
                               FROM inventory i
                               WHERE i.branch_id = ? AND i.material_id = ?
                               ON DUPLICATE KEY UPDATE physical_quantity = ?, notes = ?");
        
        $countInserted = 0;
        foreach ($materials as $index => $materialId) {
            $physicalQty = floatval($physicalCounts[$index] ?? 0);
            $stmt->bind_param("isdisisds", $branchId, $countDate, $physicalQty, $userId, $notes, $branchId, $materialId, $physicalQty, $notes);
            $stmt->execute();
            $countInserted++;
        }
        
        // Commit transaction
        $conn->commit();
        
        $message = "Physical stock count for {$countInserted} items has been recorded successfully!";
        $messageType = 'success';
        
        // Clear form
        $_POST = [];
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Failed to record stock count. Please try again.';
        $messageType = 'error';
        error_log("Stock count error: " . $e->getMessage());
    }
}

// Get current inventory with system quantities
$branchId = $user['branch_id'];
$query = "SELECT rm.material_id, rm.material_code, rm.material_name, rm.category, rm.unit_of_measure, i.current_quantity
          FROM raw_materials rm
          JOIN inventory i ON rm.material_id = i.material_id
          WHERE i.branch_id = ? AND rm.status = 'active'
          ORDER BY rm.category, rm.material_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$materials = $stmt->get_result();

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
<li class="menu-item">
    <a href="view_requests.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M18 2H2C0.9 2 0 2.9 0 4v12c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H2V6h16v10z"/>
        </svg>
        View Requests
    </a>
</li>
<li class="menu-item active">
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
    <h1>Physical Stock Count</h1>
    <p>Enter the actual physical count of inventory items</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <strong>Instructions:</strong> Count your physical inventory and enter the actual quantities found. The system will automatically calculate variances between expected and actual stock levels.
</div>

<div class="content-section">
    <form method="POST" action="" id="stockCountForm">
        <div class="form-row">
            <div class="form-group">
                <label for="count_date">Count Date</label>
                <input type="date" name="count_date" id="count_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Counted By</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
            </div>
        </div>

        <div class="section-header">
            <h3>Enter Physical Counts</h3>
        </div>

        <div class="table-responsive">
            <table class="data-table no-datatables">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Material Name</th>
                        <th>Category</th>
                        <th>System Quantity</th>
                        <th>Unit</th>
                        <th>Physical Count</th>
                        <th>Variance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $index = 0;
                    while ($material = $materials->fetch_assoc()): 
                    ?>
                        <tr id="row_<?php echo $index; ?>">
                            <td><?php echo htmlspecialchars($material['material_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($material['material_name']); ?></strong></td>
                            <td><span class="badge badge-light"><?php echo ucfirst($material['category']); ?></span></td>
                            <td class="system-qty"><strong><?php echo formatNumber($material['current_quantity']); ?></strong></td>
                            <td><?php echo htmlspecialchars($material['unit_of_measure']); ?></td>
                            <td>
                                <input type="hidden" name="materials[]" value="<?php echo $material['material_id']; ?>">
                                <input type="hidden" class="system-quantity" value="<?php echo $material['current_quantity']; ?>">
                                <input 
                                    type="number" 
                                    name="physical_counts[]" 
                                    class="form-control form-control-sm physical-count" 
                                    min="0" 
                                    step="0.01" 
                                    placeholder="0.00"
                                    onchange="calculateVariance(<?php echo $index; ?>)"
                                    required
                                >
                            </td>
                            <td class="variance-cell" id="variance_<?php echo $index; ?>">-</td>
                        </tr>
                    <?php 
                        $index++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Add any notes about discrepancies or special circumstances..."></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" name="submit_count" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M13.5 2L6 9.5 2.5 6 0 8.5 6 14.5 16 4.5z"/>
                </svg>
                Submit Stock Count
            </button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function calculateVariance(index) {
    const row = document.getElementById('row_' + index);
    const systemQty = parseFloat(row.querySelector('.system-quantity').value);
    const physicalQty = parseFloat(row.querySelector('.physical-count').value) || 0;
    const variance = physicalQty - systemQty;
    const varianceCell = document.getElementById('variance_' + index);
    
    let varianceText = '';
    let varianceClass = '';
    
    if (variance === 0) {
        varianceText = '0.00 ✓';
        varianceClass = 'text-success';
    } else if (variance > 0) {
        varianceText = '+' + variance.toFixed(2) + ' (Overage)';
        varianceClass = 'text-info';
    } else {
        varianceText = variance.toFixed(2) + ' (Shortage)';
        varianceClass = 'text-danger';
    }
    
    varianceCell.innerHTML = '<strong>' + varianceText + '</strong>';
    varianceCell.className = 'variance-cell ' + varianceClass;
}

// Auto-fill system quantities to physical count for quick review
async function autoFillSystemQuantities() {
    const result = await Swal.fire({
        title: 'Auto-Fill Confirmation',
        text: 'This will copy all system quantities to physical count. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#FF6B35',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, auto-fill',
        cancelButtonText: 'Cancel'
    });
    
    if (result.isConfirmed) {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            const systemQty = row.querySelector('.system-quantity').value;
            const physicalInput = row.querySelector('.physical-count');
            physicalInput.value = systemQty;
            calculateVariance(index);
        });
        
        Swal.fire({
            icon: 'success',
            title: 'Done!',
            text: 'All system quantities have been copied to physical count.',
            confirmButtonColor: '#FF6B35',
            timer: 2000,
            timerProgressBar: true
        });
    }
}
</script>

<div class="tip-box">
    <strong>💡 Tip:</strong> You can use the browser's "Find" function (Ctrl+F) to quickly locate specific materials in the table.
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
