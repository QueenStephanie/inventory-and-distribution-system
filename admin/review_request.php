<?php
/**
 * Review & Approve Request - Admin
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Review Request');

$user = getCurrentUser();
$conn = getDBConnection();

$requestId = intval($_GET['id'] ?? 0);
$message = '';
$messageType = '';

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $approvedQuantities = $_POST['approved_quantities'] ?? [];
    
    if ($action === 'approve') {
        $conn->begin_transaction();
        
        try {
            // Update requisition status
            $hasPartialApproval = false;
            foreach ($approvedQuantities as $itemId => $approvedQty) {
                $approvedQty = floatval($approvedQty);
                
                // Get requested quantity
                $stmt = $conn->prepare("SELECT requested_quantity FROM requisition_items WHERE item_id = ?");
                $stmt->bind_param("i", $itemId);
                $stmt->execute();
                $requestedQty = $stmt->get_result()->fetch_assoc()['requested_quantity'];
                
                if ($approvedQty != $requestedQty) {
                    $hasPartialApproval = true;
                }
                
                // Update approved quantity
                $stmt = $conn->prepare("UPDATE requisition_items SET approved_quantity = ? WHERE item_id = ?");
                $stmt->bind_param("di", $approvedQty, $itemId);
                $stmt->execute();
            }
            
            $status = $hasPartialApproval ? 'partially_approved' : 'approved';
            $stmt = $conn->prepare("UPDATE stock_requisitions SET status = ?, approved_by = ?, approval_date = NOW() WHERE requisition_id = ?");
            $stmt->bind_param("sii", $status, $user['user_id'], $requestId);
            $stmt->execute();
            
            $conn->commit();
            
            $message = 'Request has been approved successfully!';
            $messageType = 'success';
            
            // Redirect to dispatch page
            header("Location: dispatch_request.php?id={$requestId}");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Failed to approve request. Please try again.';
            $messageType = 'error';
            error_log("Approval error: " . $e->getMessage());
        }
    } elseif ($action === 'reject') {
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("UPDATE stock_requisitions SET status = 'rejected', approved_by = ?, approval_date = NOW() WHERE requisition_id = ?");
            $stmt->bind_param("ii", $user['user_id'], $requestId);
            $stmt->execute();
            
            $conn->commit();
            
            header("Location: pending_requests.php?msg=rejected");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Failed to reject request.';
            $messageType = 'error';
        }
    }
}

// Get requisition details
$query = "SELECT sr.*, u.full_name as requested_by_name, b.branch_name, b.branch_id
          FROM stock_requisitions sr 
          JOIN users u ON sr.requested_by = u.user_id
          JOIN branches b ON sr.requesting_branch_id = b.branch_id
          WHERE sr.requisition_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $requestId);
$stmt->execute();
$requisition = $stmt->get_result()->fetch_assoc();

if (!$requisition || $requisition['status'] !== 'pending') {
    header("Location: pending_requests.php");
    exit();
}

// Get requisition items with commissary stock levels
$itemsQuery = "SELECT ri.*, rm.material_code, rm.material_name, rm.category, i.current_quantity as commissary_stock
               FROM requisition_items ri
               JOIN raw_materials rm ON ri.material_id = rm.material_id
               LEFT JOIN inventory i ON rm.material_id = i.material_id AND i.branch_id = (SELECT branch_id FROM branches WHERE is_main_branch = TRUE)
               WHERE ri.requisition_id = ?";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $requestId);
$stmt->execute();
$items = $stmt->get_result();

// Get branch current stock
$branchStockQuery = "SELECT i.material_id, i.current_quantity 
                     FROM inventory i 
                     WHERE i.branch_id = ?";
$stmt = $conn->prepare($branchStockQuery);
$stmt->bind_param("i", $requisition['branch_id']);
$stmt->execute();
$branchStockResult = $stmt->get_result();
$branchStock = [];
while ($row = $branchStockResult->fetch_assoc()) {
    $branchStock[$row['material_id']] = $row['current_quantity'];
}

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

<?php include '../includes/sidebar_end.php'; ?>

<!-- Page Content -->
<div class="page-header">
    <div>
        <h1>Review Stock Request</h1>
        <p>Requisition #<?php echo htmlspecialchars($requisition['requisition_number']); ?></p>
    </div>
    <a href="pending_requests.php" class="btn btn-secondary">
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
            <label>Request Date</label>
            <value><?php echo formatDateTime($requisition['request_date']); ?></value>
        </div>
        <div class="detail-item">
            <label>Status</label>
            <value><span class="badge badge-warning">Pending</span></value>
        </div>
        <?php if ($requisition['notes']): ?>
        <div class="detail-item" style="grid-column: 1 / -1;">
            <label>Notes from Branch</label>
            <value><?php echo nl2br(htmlspecialchars($requisition['notes'])); ?></value>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info">
    <strong>📋 Instructions:</strong> Review each item and adjust approved quantities as needed based on commissary stock levels. You can approve partially or in full.
</div>

<form method="POST" action="" id="approvalForm">
    <div class="content-section">
        <h2>Requested Items</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Material Name</th>
                        <th>Branch Stock</th>
                        <th>Requested Qty</th>
                        <th>Commissary Stock</th>
                        <th>Approved Qty</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $items->data_seek(0);
                    while ($item = $items->fetch_assoc()): 
                        $canFulfill = $item['commissary_stock'] >= $item['requested_quantity'];
                        $branchCurrentStock = $branchStock[$item['material_id']] ?? 0;
                    ?>
                        <tr class="<?php echo !$canFulfill ? 'row-warning' : ''; ?>">
                            <td><?php echo htmlspecialchars($item['material_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($item['material_name']); ?></strong></td>
                            <td><?php echo formatNumber($branchCurrentStock); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                            <td><strong><?php echo formatNumber($item['requested_quantity']); ?></strong> <?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                            <td>
                                <strong class="<?php echo !$canFulfill ? 'text-warning' : 'text-success'; ?>">
                                    <?php echo formatNumber($item['commissary_stock']); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?>
                                </strong>
                                <?php if (!$canFulfill): ?>
                                    <span class="text-warning">⚠ Insufficient</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input 
                                    type="number" 
                                    name="approved_quantities[<?php echo $item['item_id']; ?>]" 
                                    class="form-control form-control-sm" 
                                    min="0" 
                                    max="<?php echo $item['commissary_stock']; ?>"
                                    step="0.01" 
                                    value="<?php echo min($item['requested_quantity'], $item['commissary_stock']); ?>"
                                    required
                                    style="width: 120px;"
                                >
                            </td>
                            <td>
                                <button type="button" class="btn btn-xs btn-link" onclick="copyRequested(<?php echo $item['item_id']; ?>, <?php echo $item['requested_quantity']; ?>)">Use Requested</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" name="action" value="approve" class="btn btn-success">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M13.5 2L6 9.5 2.5 6 0 8.5 6 14.5 16 4.5z"/>
            </svg>
            Approve & Proceed to Dispatch
        </button>
        <button type="submit" name="action" value="reject" class="btn btn-danger" id="rejectBtn">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0L0 8l8 8 8-8-8-8zm4 10.5L10.5 12 8 9.5 5.5 12 4 10.5 6.5 8 4 5.5 5.5 4 8 6.5 10.5 4 12 5.5 9.5 8 12 10.5z"/>
            </svg>
            Reject Request
        </button>
        <a href="pending_requests.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
function copyRequested(itemId, quantity) {
    const input = document.querySelector(`input[name="approved_quantities[${itemId}]"]`);
    input.value = quantity;
}

// Handle reject button with SweetAlert2
document.getElementById('rejectBtn').addEventListener('click', async function(e) {
    e.preventDefault();
    
    const result = await Swal.fire({
        title: 'Reject Request',
        text: 'Are you sure you want to reject this request?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reject it',
        cancelButtonText: 'Cancel'
    });
    
    if (result.isConfirmed) {
        this.closest('form').submit();
    }
});
</script>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
