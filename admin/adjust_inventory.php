<?php
/**
 * Adjust Inventory - Admin
 * Manual inventory adjustments for corrections
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Adjust Inventory');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle adjustment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_adjustment'])) {
    $branchId = intval($_POST['branch_id']);
    $materialId = intval($_POST['material_id']);
    $adjustmentType = $_POST['adjustment_type']; // 'add' or 'subtract'
    $quantity = floatval($_POST['quantity']);
    $reason = sanitizeInput($_POST['reason']);
    
    if ($quantity <= 0) {
        $message = 'Quantity must be greater than zero.';
        $messageType = 'error';
    } else {
        $conn->begin_transaction();
        
        try {
            // Get current quantity
            $stmt = $conn->prepare("SELECT current_quantity FROM inventory WHERE branch_id = ? AND material_id = ?");
            $stmt->bind_param("ii", $branchId, $materialId);
            $stmt->execute();
            $currentQty = $stmt->get_result()->fetch_assoc()['current_quantity'];
            
            // Calculate new quantity
            if ($adjustmentType === 'add') {
                $newQty = $currentQty + $quantity;
                $movementQty = $quantity;
            } else {
                $newQty = $currentQty - $quantity;
                $movementQty = -$quantity;
            }
            
            // Check for negative inventory
            if ($newQty < 0) {
                throw new Exception('Adjustment would result in negative inventory.');
            }
            
            // Update inventory
            $stmt = $conn->prepare("UPDATE inventory SET current_quantity = ? WHERE branch_id = ? AND material_id = ?");
            $stmt->bind_param("dii", $newQty, $branchId, $materialId);
            $stmt->execute();
            
            // Record stock movement
            $stmt = $conn->prepare("INSERT INTO stock_movements (branch_id, material_id, movement_type, quantity, previous_quantity, new_quantity, reference_type, performed_by, notes) 
                                   VALUES (?, ?, 'adjustment', ?, ?, ?, 'manual', ?, ?)");
            $stmt->bind_param("iidddiis", $branchId, $materialId, $movementQty, $currentQty, $newQty, $user['user_id'], $reason);
            $stmt->execute();
            
            $conn->commit();
            
            $message = 'Inventory has been adjusted successfully!';
            $messageType = 'success';
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Failed to adjust inventory: ' . $e->getMessage();
            $messageType = 'error';
            error_log("Inventory adjustment error: " . $e->getMessage());
        }
    }
}

// Get branches
$branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY is_main_branch DESC, branch_name");

// Get materials
$materials = $conn->query("SELECT material_id, material_code, material_name, category FROM raw_materials WHERE status = 'active' ORDER BY category, material_name");

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
<li class="menu-item">
    <a href="commissary_inventory.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2 2h16v16H2V2zm2 2v12h12V4H4z"/>
        </svg>
        Commissary Inventory
    </a>
</li>
<li class="menu-item active">
    <a href="adjust_inventory.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M17.414 2.586a2 2 0 010 2.828L8.828 14H6v-2.828l8.586-8.586a2 2 0 012.828 0zM4 16h12v2H4v-2z"/>
        </svg>
        Adjust Inventory
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<div class="page-header">
    <h1>Manual Inventory Adjustment</h1>
    <p>Make manual corrections to inventory levels</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="alert alert-warning">
    <strong>⚠️ Warning:</strong> Manual adjustments should only be made to correct errors or account for unusual circumstances. 
    All adjustments are logged and auditable.
</div>

<div class="content-section">
    <form method="POST" action="" id="adjustmentForm">
        <div class="form-row">
            <div class="form-group">
                <label for="branch_id">Branch *</label>
                <select name="branch_id" id="branch_id" class="form-control" required>
                    <option value="">Select Branch</option>
                    <?php 
                    $branches->data_seek(0);
                    while ($branch = $branches->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $branch['branch_id']; ?>">
                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="material_id">Material/Product *</label>
                <select name="material_id" id="material_id" class="form-control" required>
                    <option value="">Select Material</option>
                    <?php 
                    $materials->data_seek(0);
                    while ($material = $materials->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $material['material_id']; ?>">
                            <?php echo htmlspecialchars($material['material_code'] . ' - ' . $material['material_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="adjustment_type">Adjustment Type *</label>
                <select name="adjustment_type" id="adjustment_type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="add">Add Stock (Increase)</option>
                    <option value="subtract">Remove Stock (Decrease)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input type="number" name="quantity" id="quantity" class="form-control" step="0.01" min="0.01" required>
            </div>
        </div>

        <div class="form-group">
            <label for="reason">Reason for Adjustment *</label>
            <textarea name="reason" id="reason" class="form-control" rows="4" required placeholder="Explain why this adjustment is necessary (e.g., 'Damaged goods found during inspection', 'Inventory count correction', etc.)"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" name="submit_adjustment" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M13.854 3.646a.5.5 0 010 .708l-7 7a.5.5 0 01-.708 0l-3.5-3.5a.5.5 0 11.708-.708L6.5 10.293l6.646-6.647a.5.5 0 01.708 0z"/>
                </svg>
                Apply Adjustment
            </button>
            <button type="reset" class="btn btn-secondary">Clear Form</button>
        </div>
    </form>
</div>

<!-- Recent Adjustments -->
<div class="content-section">
    <h2>Recent Adjustments (Last 20)</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Branch</th>
                    <th>Material</th>
                    <th>Quantity</th>
                    <th>Previous</th>
                    <th>New</th>
                    <th>By</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $historyQuery = "SELECT sm.*, b.branch_name, rm.material_name, u.full_name
                                FROM stock_movements sm
                                JOIN branches b ON sm.branch_id = b.branch_id
                                JOIN raw_materials rm ON sm.material_id = rm.material_id
                                JOIN users u ON sm.performed_by = u.user_id
                                WHERE sm.movement_type = 'adjustment'
                                ORDER BY sm.movement_date DESC
                                LIMIT 20";
                $history = $conn->query($historyQuery);
                
                if ($history->num_rows > 0):
                    while ($row = $history->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo formatDateTime($row['movement_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['material_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $row['quantity'] > 0 ? 'success' : 'danger'; ?>">
                                <?php echo $row['quantity'] > 0 ? '+' : ''; ?><?php echo formatNumber($row['quantity']); ?>
                            </span>
                        </td>
                        <td><?php echo formatNumber($row['previous_quantity']); ?></td>
                        <td><?php echo formatNumber($row['new_quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['notes']); ?></td>
                    </tr>
                <?php 
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No adjustments found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
