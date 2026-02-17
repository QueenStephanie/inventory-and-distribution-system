<?php
/**
 * Dispatch Request - Admin
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Dispatch Request');

$user = getCurrentUser();
$conn = getDBConnection();

$requestId = intval($_GET['id'] ?? 0);
$message = '';
$messageType = '';

// Handle dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispatch'])) {
    $conn->begin_transaction();
    
    try {
        // Get requisition details
        $reqQuery = "SELECT requesting_branch_id, status FROM stock_requisitions WHERE requisition_id = ?";
        $stmt = $conn->prepare($reqQuery);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $reqResult = $stmt->get_result()->fetch_assoc();
        if (!$reqResult || !in_array($reqResult['status'], ['approved', 'partially_approved'])) {
            throw new Exception('Requisition is not in an approved state.');
        }
        $branchId = $reqResult['requesting_branch_id'];
        
        // Get main commissary branch ID
        $commQuery = "SELECT branch_id FROM branches WHERE is_main_branch = TRUE LIMIT 1";
        $commResult = $conn->query($commQuery)->fetch_assoc();
        if (!$commResult) {
            throw new Exception('Main commissary branch not found.');
        }
        $commBranchId = $commResult['branch_id'];
        
        // Get approved items
        $itemsQuery = "SELECT material_id, approved_quantity, unit_of_measure FROM requisition_items WHERE requisition_id = ?";
        $stmt = $conn->prepare($itemsQuery);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $items = $stmt->get_result();
        
        // --- PRE-CHECK: Verify sufficient commissary stock for ALL items before dispatching ---
        $itemsToDispatch = [];
        $insufficientItems = [];
        while ($item = $items->fetch_assoc()) {
            $materialId = $item['material_id'];
            $quantity = $item['approved_quantity'];
            
            // Check commissary inventory row exists and has enough stock
            $checkStmt = $conn->prepare("SELECT current_quantity FROM inventory WHERE branch_id = ? AND material_id = ?");
            $checkStmt->bind_param("ii", $commBranchId, $materialId);
            $checkStmt->execute();
            $stockRow = $checkStmt->get_result()->fetch_assoc();
            
            if (!$stockRow) {
                $insufficientItems[] = "Material ID {$materialId}: no inventory record in commissary";
            } elseif ($stockRow['current_quantity'] < $quantity) {
                $insufficientItems[] = "Material ID {$materialId}: available {$stockRow['current_quantity']}, requested {$quantity}";
            }
            
            // Also verify branch inventory row exists
            $branchCheckStmt = $conn->prepare("SELECT current_quantity FROM inventory WHERE branch_id = ? AND material_id = ?");
            $branchCheckStmt->bind_param("ii", $branchId, $materialId);
            $branchCheckStmt->execute();
            if (!$branchCheckStmt->get_result()->fetch_assoc()) {
                $insufficientItems[] = "Material ID {$materialId}: no inventory record for target branch";
            }
            
            $itemsToDispatch[] = $item;
        }
        
        if (!empty($insufficientItems)) {
            throw new Exception('Insufficient commissary stock: ' . implode('; ', $insufficientItems));
        }
        
        if (empty($itemsToDispatch)) {
            throw new Exception('No items to dispatch for this requisition.');
        }
        // --- END PRE-CHECK ---
        
        // Process each item (stock is verified sufficient above)
        foreach ($itemsToDispatch as $item) {
            $materialId = $item['material_id'];
            $quantity = $item['approved_quantity'];
            
            // Get previous commissary quantity BEFORE update (with row lock)
            $prevQuery = "SELECT current_quantity FROM inventory WHERE branch_id = ? AND material_id = ? FOR UPDATE";
            $stmt = $conn->prepare($prevQuery);
            $stmt->bind_param("ii", $commBranchId, $materialId);
            $stmt->execute();
            $commRow = $stmt->get_result()->fetch_assoc();
            if (!$commRow || $commRow['current_quantity'] < $quantity) {
                throw new Exception("Stock changed during dispatch for material ID {$materialId}. Aborting.");
            }
            $prevQtyComm = $commRow['current_quantity'];
            
            // Deduct from commissary inventory
            $stmt = $conn->prepare("UPDATE inventory SET current_quantity = current_quantity - ? WHERE branch_id = ? AND material_id = ?");
            $stmt->bind_param("dii", $quantity, $commBranchId, $materialId);
            $stmt->execute();
            
            // Calculate new commissary quantity
            $newQtyComm = $prevQtyComm - $quantity;
            
            // Record commissary stock movement (dispatch) - negative quantity for deduction
            $negQuantity = -$quantity;
            $stmt = $conn->prepare("INSERT INTO stock_movements (branch_id, material_id, movement_type, quantity, previous_quantity, new_quantity, reference_type, reference_id, performed_by, notes) 
                                   VALUES (?, ?, 'dispatch', ?, ?, ?, 'requisition', ?, ?, 'Stock dispatched to branch')");
            $stmt->bind_param("iiiddii", $commBranchId, $materialId, $negQuantity, $prevQtyComm, $newQtyComm, $requestId, $user['user_id']);
            $stmt->execute();
            
            // Get previous branch quantity BEFORE update (with row lock)
            $prevQuery = "SELECT current_quantity FROM inventory WHERE branch_id = ? AND material_id = ? FOR UPDATE";
            $stmt = $conn->prepare($prevQuery);
            $stmt->bind_param("ii", $branchId, $materialId);
            $stmt->execute();
            $branchRow = $stmt->get_result()->fetch_assoc();
            if (!$branchRow) {
                throw new Exception("Branch inventory row missing for material ID {$materialId}.");
            }
            $prevQtyBranch = $branchRow['current_quantity'];
            
            // Add to branch inventory
            $stmt = $conn->prepare("UPDATE inventory SET current_quantity = current_quantity + ? WHERE branch_id = ? AND material_id = ?");
            $stmt->bind_param("dii", $quantity, $branchId, $materialId);
            $stmt->execute();
            
            // Calculate new branch quantity
            $newQtyBranch = $prevQtyBranch + $quantity;
            
            // Record branch stock movement (receive) - positive quantity for addition
            $stmt = $conn->prepare("INSERT INTO stock_movements (branch_id, material_id, movement_type, quantity, previous_quantity, new_quantity, reference_type, reference_id, performed_by, notes) 
                                   VALUES (?, ?, 'receive', ?, ?, ?, 'requisition', ?, ?, 'Stock received from commissary')");
            $stmt->bind_param("iiiddii", $branchId, $materialId, $quantity, $prevQtyBranch, $newQtyBranch, $requestId, $user['user_id']);
            $stmt->execute();
            
            // Update dispatched quantity in requisition items
            $stmt = $conn->prepare("UPDATE requisition_items SET dispatched_quantity = ? WHERE requisition_id = ? AND material_id = ?");
            $stmt->bind_param("dii", $quantity, $requestId, $materialId);
            $stmt->execute();
        }
        
        // Update requisition status
        $stmt = $conn->prepare("UPDATE stock_requisitions SET status = 'dispatched', dispatched_by = ?, dispatch_date = NOW() WHERE requisition_id = ?");
        $stmt->bind_param("ii", $user['user_id'], $requestId);
        $stmt->execute();
        
        $conn->commit();
        
        header("Location: approved_requests.php?msg=dispatched");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Failed to dispatch stock: ' . htmlspecialchars($e->getMessage());
        $messageType = 'error';
        error_log("Dispatch error: " . $e->getMessage());
    }
}

// Get requisition details
$query = "SELECT sr.*, u.full_name as requested_by_name, u2.full_name as approved_by_name, b.branch_name
          FROM stock_requisitions sr 
          JOIN users u ON sr.requested_by = u.user_id
          LEFT JOIN users u2 ON sr.approved_by = u2.user_id
          JOIN branches b ON sr.requesting_branch_id = b.branch_id
          WHERE sr.requisition_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $requestId);
$stmt->execute();
$requisition = $stmt->get_result()->fetch_assoc();

if (!$requisition || !in_array($requisition['status'], ['approved', 'partially_approved'])) {
    header("Location: approved_requests.php");
    exit();
}

// Get items to dispatch
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
    <a href="pending_requests.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M18 2H2C0.9 2 0 2.9 0 4v12c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H2V6h16v10z"/>
        </svg>
        Pending Requests
    </a>
</li>
<li class="menu-item active">
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
    <div>
        <h1>Dispatch Stock</h1>
        <p>Requisition #<?php echo htmlspecialchars($requisition['requisition_number']); ?></p>
    </div>
    <a href="approved_requests.php" class="btn btn-secondary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M16 7H3.83l5.59-5.59L8 0 0 8l8 8 1.41-1.41L3.83 9H16z"/>
        </svg>
        Back to List
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="content-section">
    <div class="detail-grid">
        <div class="detail-item">
            <label>Branch</label>
            <value><strong><?php echo htmlspecialchars($requisition['branch_name']); ?></strong></value>
        </div>
        <div class="detail-item">
            <label>Requested By</label>
            <value><?php echo htmlspecialchars($requisition['requested_by_name']); ?></value>
        </div>
        <div class="detail-item">
            <label>Approved By</label>
            <value><?php echo htmlspecialchars($requisition['approved_by_name']); ?></value>
        </div>
        <div class="detail-item">
            <label>Approval Date</label>
            <value><?php echo formatDateTime($requisition['approval_date']); ?></value>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <strong>📦 Dispatch Instructions:</strong> Review the approved quantities below. Click "Confirm Dispatch" to transfer stock from the commissary to the branch. This action will update inventory levels.
</div>

<div class="content-section">
    <h2>Items to Dispatch</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Material Name</th>
                    <th>Category</th>
                    <th>Requested</th>
                    <th>To Dispatch</th>
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
                            <strong class="text-success"><?php echo formatNumber($item['approved_quantity']); ?></strong>
                            <?php if ($item['approved_quantity'] != $item['requested_quantity']): ?>
                                <span class="text-warning">⚠ Adjusted</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="" id="dispatchForm">
    <input type="hidden" name="dispatch" value="1">
    <div class="form-actions">
        <button type="submit" class="btn btn-success">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M0 0v16l16-8L0 0z"/>
            </svg>
            Confirm Dispatch
        </button>
        <a href="approved_requests.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
document.getElementById('dispatchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const result = await Swal.fire({
        title: 'Confirm Dispatch',
        text: 'Are you sure you want to dispatch this stock? This will update inventory levels.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FF6B35',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, dispatch it',
        cancelButtonText: 'Cancel'
    });
    
    if (result.isConfirmed) {
        this.submit();
    }
});
</script>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
