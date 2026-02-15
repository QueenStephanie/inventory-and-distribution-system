<?php
/**
 * Request Stock Page - Branch User
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('branch_user');

define('PAGE_TITLE', 'Request Stock');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $branchId = $user['branch_id'];
    $userId = $user['user_id'];
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $materials = $_POST['materials'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    // Validate at least one item
    $hasItems = false;
    foreach ($quantities as $quantity) {
        if (!empty($quantity) && floatval($quantity) > 0) {
            $hasItems = true;
            break;
        }
    }
    
    if (!$hasItems) {
        $message = 'Please add at least one item with a quantity greater than 0.';
        $messageType = 'error';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Generate requisition number
            $requisitionNumber = generateRequisitionNumber($conn);
            
            // Insert requisition
            $stmt = $conn->prepare("INSERT INTO stock_requisitions (requisition_number, requesting_branch_id, requested_by, notes) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siis", $requisitionNumber, $branchId, $userId, $notes);
            $stmt->execute();
            $requisitionId = $conn->insert_id;
            
            // Insert requisition items
            $stmt = $conn->prepare("INSERT INTO requisition_items (requisition_id, material_id, requested_quantity, unit_of_measure) 
                                   SELECT ?, ?, ?, unit_of_measure FROM raw_materials WHERE material_id = ?");
            
            foreach ($materials as $index => $materialId) {
                $quantity = floatval($quantities[$index] ?? 0);
                if ($quantity > 0) {
                    $stmt->bind_param("iidi", $requisitionId, $materialId, $quantity, $materialId);
                    $stmt->execute();
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $message = "Stock request #{$requisitionNumber} has been submitted successfully!";
            $messageType = 'success';
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Failed to submit request. Please try again.';
            $messageType = 'error';
            error_log("Request submission error: " . $e->getMessage());
        }
    }
}

// Get all active raw materials
$materialsQuery = "SELECT material_id, material_code, material_name, category, unit_of_measure FROM raw_materials WHERE status = 'active' ORDER BY category, material_name";
$materials = $conn->query($materialsQuery);

// Get current inventory for reference
$branchId = $user['branch_id'];
$inventoryQuery = "SELECT i.material_id, i.current_quantity FROM inventory i WHERE i.branch_id = ?";
$stmt = $conn->prepare($inventoryQuery);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$inventoryResult = $stmt->get_result();
$inventory = [];
while ($row = $inventoryResult->fetch_assoc()) {
    $inventory[$row['material_id']] = $row['current_quantity'];
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

<!-- Page Content -->
<div class="page-header">
    <h1>Request Stock from Main Commissary</h1>
    <p>Submit your stock requisition request</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="content-section">
    <form method="POST" action="" id="requestForm">
        <div class="form-group">
            <label>Branch</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['branch_name']); ?>" readonly>
        </div>

        <div class="form-group">
            <label>Requested By</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
        </div>

        <div class="section-header">
            <h3>Select Items to Request</h3>
            <button type="button" class="btn btn-sm btn-primary" onclick="selectAllItems()">Select All</button>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="materialsTable">
                <thead>
                    <tr>
                        <th width="50">Select</th>
                        <th>Code</th>
                        <th>Material Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Unit</th>
                        <th width="150">Quantity Needed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $materials->data_seek(0);
                    $index = 0;
                    while ($material = $materials->fetch_assoc()): 
                        $currentStock = $inventory[$material['material_id']] ?? 0;
                        $stockClass = $currentStock <= 10 ? 'text-danger' : '';
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="material-checkbox" name="materials[]" value="<?php echo $material['material_id']; ?>" onchange="toggleQuantityInput(this, <?php echo $index; ?>)">
                            </td>
                            <td><?php echo htmlspecialchars($material['material_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($material['material_name']); ?></strong></td>
                            <td><span class="badge badge-light"><?php echo ucfirst($material['category']); ?></span></td>
                            <td class="<?php echo $stockClass; ?>">
                                <strong><?php echo formatNumber($currentStock); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($material['unit_of_measure']); ?></td>
                            <td>
                                <input 
                                    type="number" 
                                    name="quantities[]" 
                                    id="qty_<?php echo $index; ?>"
                                    class="form-control form-control-sm" 
                                    min="0" 
                                    step="0.01" 
                                    placeholder="0.00"
                                    disabled
                                >
                            </td>
                        </tr>
                    <?php 
                        $index++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>

        <div class="form-group">
            <label for="notes">Notes / Special Instructions</label>
            <textarea name="notes" id="notes" class="form-control" rows="4" placeholder="Add any special instructions or notes about this request..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" name="submit_request" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M0 0v16l16-8L0 0z"/>
                </svg>
                Submit Request
            </button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleQuantityInput(checkbox, index) {
    const qtyInput = document.getElementById('qty_' + index);
    if (checkbox.checked) {
        qtyInput.disabled = false;
        qtyInput.focus();
    } else {
        qtyInput.disabled = true;
        qtyInput.value = '';
    }
}

function selectAllItems() {
    const checkboxes = document.querySelectorAll('.material-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach((checkbox, index) => {
        checkbox.checked = !allChecked;
        toggleQuantityInput(checkbox, index);
    });
}

// Form validation
document.getElementById('requestForm').addEventListener('submit', function(e) {
    const quantities = document.querySelectorAll('input[name="quantities[]"]');
    let hasQuantity = false;
    
    quantities.forEach(input => {
        if (!input.disabled && parseFloat(input.value) > 0) {
            hasQuantity = true;
        }
    });
    
    if (!hasQuantity) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select at least one item and enter a quantity greater than 0.',
            confirmButtonColor: '#FF6B35'
        });
        return false;
    }
});
</script>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
