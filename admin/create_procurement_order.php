<?php

/**
 * Create Procurement Order - Admin
 * Create new purchase orders from suppliers
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Create Procurement Order');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
  $supplierId = intval($_POST['supplier_id']);
  $expectedDeliveryDate = sanitizeInput($_POST['expected_delivery_date']);
  $notes = sanitizeInput($_POST['notes'] ?? '');
  $materials = $_POST['materials'] ?? [];
  $quantities = $_POST['quantities'] ?? [];
  $unitCosts = $_POST['unit_costs'] ?? [];

  if ($supplierId === 0) {
    $message = 'Please select a supplier.';
    $messageType = 'error';
  } elseif (empty($materials) || count($materials) === 0) {
    $message = 'Please add at least one material to the order.';
    $messageType = 'error';
  } else {
    $conn->begin_transaction();

    try {
      // Generate order number
      $orderNumber = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

      // Check if order number exists
      $checkStmt = $conn->prepare("SELECT order_id FROM procurement_orders WHERE order_number = ?");
      $checkStmt->bind_param("s", $orderNumber);
      $checkStmt->execute();

      while ($checkStmt->get_result()->num_rows > 0) {
        $orderNumber = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $checkStmt->bind_param("s", $orderNumber);
        $checkStmt->execute();
      }

      // Calculate total cost
      $totalCost = 0;
      for ($i = 0; $i < count($materials); $i++) {
        if (!empty($materials[$i])) {
          $qty = floatval($quantities[$i] ?? 0);
          $cost = floatval($unitCosts[$i] ?? 0);
          $totalCost += ($qty * $cost);
        }
      }

      // Insert procurement order
      $stmt = $conn->prepare("INSERT INTO procurement_orders (order_number, supplier_id, expected_delivery_date, ordered_by, notes, total_cost) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sisssd", $orderNumber, $supplierId, $expectedDeliveryDate, $user['user_id'], $notes, $totalCost);
      $stmt->execute();
      $orderId = $conn->insert_id;

      // Insert order items
      $itemStmt = $conn->prepare("INSERT INTO procurement_order_items (order_id, material_id, ordered_quantity, unit_cost) 
                                       VALUES (?, ?, ?, ?)");

      $itemCount = 0;
      for ($i = 0; $i < count($materials); $i++) {
        $materialId = intval($materials[$i]);
        if ($materialId > 0) {
          $qty = floatval($quantities[$i] ?? 0);
          $cost = floatval($unitCosts[$i] ?? 0);

          if ($qty > 0) {
            $itemStmt->bind_param("iidd", $orderId, $materialId, $qty, $cost);
            $itemStmt->execute();
            $itemCount++;
          }
        }
      }

      if ($itemCount === 0) {
        throw new Exception('No valid items were added to the order.');
      }

      $conn->commit();

      $message = "Procurement order {$orderNumber} created successfully with {$itemCount} items!";
      $messageType = 'success';

      // Redirect to view order after short delay
      echo "<script>
                setTimeout(function() {
                    window.location.href = 'view_procurement_order.php?id={$orderId}';
                }, 2000);
            </script>";
    } catch (Exception $e) {
      $conn->rollback();
      $message = 'Failed to create procurement order: ' . $e->getMessage();
      $messageType = 'error';
      error_log("Procurement order error: " . $e->getMessage());
    }
  }
}

// Get suppliers
$suppliers = $conn->query("SELECT supplier_id, supplier_code, supplier_name FROM suppliers WHERE status = 'active' ORDER BY supplier_name");

// Get materials
$materials = $conn->query("SELECT material_id, material_code, material_name, category, unit_of_measure FROM raw_materials WHERE status = 'active' ORDER BY category, material_name");

include '../includes/header.php';
?>

<!-- Sidebar Menu -->
<li class="menu-item">
  <a href="dashboard.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M10 0L0 8v12h7v-7h6v7h7V8L10 0z" />
    </svg>
    Dashboard
  </a>
</li>
<li class="menu-item">
  <a href="pending_requests.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M18 2H2C0.9 2 0 2.9 0 4v12c0 1.1 0.9 2 2 2h16c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H2V6h16v10z" />
    </svg>
    Pending Requests
  </a>
</li>
<li class="menu-item">
  <a href="approved_requests.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M16 0H4C2.9 0 2 0.9 2 2v16c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V2c0-1.1-0.9-2-2-2zm-6 15l-5-5 1.41-1.41L10 12.17l6.59-6.59L18 7l-8 8z" />
    </svg>
    Approved Requests
  </a>
</li>
<li class="menu-item">
  <a href="all_requests.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M17 0H3C1.9 0 1 0.9 1 2v12c0 1.1 0.9 2 2 2h11l5 4V2c0-1.1-0.9-2-2-2z" />
    </svg>
    All Requests
  </a>
</li>
<li class="menu-item">
  <a href="commissary_inventory.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M2 2h16v16H2V2zm2 2v12h12V4H4z" />
    </svg>
    Commissary Inventory
  </a>
</li>
<li class="menu-item">
  <a href="manage_suppliers.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M16 1H4C2.9 1 2 1.9 2 3v14c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V3c0-1.1-0.9-2-2-2zM9 13H7v-2h2v2zm0-4H7V5h2v4zm4 4h-2V9h2v4zm0-6h-2V5h2v2z" />
    </svg>
    Manage Suppliers
  </a>
</li>
<li class="menu-item active">
  <a href="procurement_orders.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M17 2H3C1.9 2 1 2.9 1 4v12c0 1.1 0.9 2 2 2h14c1.1 0 2-0.9 2-2V4c0-1.1-0.9-2-2-2zm0 14H3V6h14v10z" />
    </svg>
    Procurement Orders
  </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<!-- Page Content -->
<div class="page-header">
  <h1>➕ Create Procurement Order</h1>
  <p>Create a new purchase order from a supplier</p>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?php echo $messageType; ?>">
    <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>

<div class="alert alert-info">
  <strong>📋 Instructions:</strong> Select a supplier, add materials with quantities and costs, then submit the order. You'll be able to receive items and enter batch/expiry information when the delivery arrives.
</div>

<div class="content-section">
  <form method="POST" action="" id="procurementOrderForm">
    <!-- Order Header -->
    <div class="section-header">
      <h3>Order Information</h3>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="supplier_id">Supplier <span class="required">*</span></label>
        <select name="supplier_id" id="supplier_id" class="form-control" required>
          <option value="">-- Select Supplier --</option>
          <?php while ($supplier = $suppliers->fetch_assoc()): ?>
            <option value="<?php echo $supplier['supplier_id']; ?>">
              <?php echo htmlspecialchars($supplier['supplier_code'] . ' - ' . $supplier['supplier_name']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="expected_delivery_date">Expected Delivery Date</label>
        <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
      </div>

      <div class="form-group">
        <label for="ordered_by">Ordered By</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
      </div>
    </div>

    <!-- Order Items -->
    <div class="section-header" style="margin-top: 30px;">
      <h3>Order Items</h3>
      <button type="button" class="btn btn-sm btn-success" onclick="addMaterialRow()">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
          <path d="M7 0C6.4 0 6 0.4 6 1v5H1C0.4 6 0 6.4 0 7s0.4 1 1 1h5v5c0 0.6 0.4 1 1 1s1-0.4 1-1V8h5c0.6 0 1-0.4 1-1s-0.4-1-1-1H8V1C8 0.4 7.6 0 7 0z" />
        </svg>
        Add Material
      </button>
    </div>

    <div class="table-responsive">
      <table class="table" id="materialsTable">
        <thead>
          <tr>
            <th width="40%">Material</th>
            <th width="15%">Unit</th>
            <th width="15%">Quantity <span class="required">*</span></th>
            <th width="15%">Unit Cost (₱) <span class="required">*</span></th>
            <th width="12%">Total Cost (₱)</th>
            <th width="3%"></th>
          </tr>
        </thead>
        <tbody id="materialRows">
          <!-- Material rows will be added here -->
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="text-right"><strong>Grand Total:</strong></td>
            <td><strong id="grandTotal">₱0.00</strong></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="alert alert-warning" id="noItemsWarning" style="display: none;">
      Please add at least one material to the order.
    </div>

    <!-- Notes -->
    <div class="form-group" style="margin-top: 20px;">
      <label for="notes">Notes / Special Instructions</label>
      <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Optional notes about this order..."></textarea>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
      <button type="submit" name="submit_order" class="btn btn-primary" id="submitBtn">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
          <path d="M13.5 2L6 9.5 2.5 6 0 8.5 6 14.5 16 4.5z" />
        </svg>
        Create Procurement Order
      </button>
      <a href="procurement_orders.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<script>
  const materialsData = <?php
                        $materials->data_seek(0);
                        $materialsArray = [];
                        while ($mat = $materials->fetch_assoc()) {
                          $materialsArray[] = $mat;
                        }
                        echo json_encode($materialsArray);
                        ?>;

  let rowCounter = 0;

  // Add initial row on page load
  $(document).ready(function() {
    addMaterialRow();
  });

  function addMaterialRow() {
    rowCounter++;
    const rowId = 'row_' + rowCounter;

    let materialsOptions = '<option value="">-- Select Material --</option>';
    materialsData.forEach(mat => {
      materialsOptions += `<option value="${mat.material_id}" data-unit="${mat.unit_of_measure}" data-category="${mat.category}">
            ${mat.material_code} - ${mat.material_name} (${mat.category})
        </option>`;
    });

    const row = `
        <tr id="${rowId}">
            <td>
                <select name="materials[]" class="form-control material-select" onchange="updateUnit('${rowId}')" required>
                    ${materialsOptions}
                </select>
            </td>
            <td>
                <input type="text" class="form-control unit-display" readonly placeholder="Unit">
            </td>
            <td>
                <input type="number" name="quantities[]" class="form-control quantity-input" step="0.01" min="0.01" placeholder="0.00" onchange="calculateRowTotal('${rowId}')" required>
            </td>
            <td>
                <input type="number" name="unit_costs[]" class="form-control cost-input" step="0.01" min="0" placeholder="0.00" onchange="calculateRowTotal('${rowId}')" required>
            </td>
            <td>
                <strong class="row-total">₱0.00</strong>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeRow('${rowId}')">
                    ×
                </button>
            </td>
        </tr>
    `;

    $('#materialRows').append(row);
    updateNoItemsWarning();
  }

  function removeRow(rowId) {
    $('#' + rowId).remove();
    calculateGrandTotal();
    updateNoItemsWarning();
  }

  function updateUnit(rowId) {
    const row = $('#' + rowId);
    const selectedOption = row.find('.material-select option:selected');
    const unit = selectedOption.data('unit') || '';
    row.find('.unit-display').val(unit);
  }

  function calculateRowTotal(rowId) {
    const row = $('#' + rowId);
    const qty = parseFloat(row.find('.quantity-input').val()) || 0;
    const cost = parseFloat(row.find('.cost-input').val()) || 0;
    const total = qty * cost;

    row.find('.row-total').text('₱' + total.toFixed(2));
    calculateGrandTotal();
  }

  function calculateGrandTotal() {
    let grandTotal = 0;
    $('.row-total').each(function() {
      const value = parseFloat($(this).text().replace('₱', '').replace(',', '')) || 0;
      grandTotal += value;
    });

    $('#grandTotal').text('₱' + grandTotal.toLocaleString('en', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }));
  }

  function updateNoItemsWarning() {
    const rowCount = $('#materialRows tr').length;
    if (rowCount === 0) {
      $('#noItemsWarning').show();
      $('#submitBtn').prop('disabled', true);
    } else {
      $('#noItemsWarning').hide();
      $('#submitBtn').prop('disabled', false);
    }
  }

  // Form validation
  $('#procurementOrderForm').on('submit', function(e) {
    const supplierId = $('#supplier_id').val();
    const rowCount = $('#materialRows tr').length;

    if (!supplierId) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Supplier Required',
        text: 'Please select a supplier for this order.',
        confirmButtonColor: '#FF6B35'
      });
      return false;
    }

    if (rowCount === 0) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'No Materials',
        text: 'Please add at least one material to the order.',
        confirmButtonColor: '#FF6B35'
      });
      return false;
    }

    // Check if all rows have quantity and cost
    let isValid = true;
    $('#materialRows tr').each(function() {
      const qty = parseFloat($(this).find('.quantity-input').val()) || 0;
      const cost = parseFloat($(this).find('.cost-input').val()) || 0;

      if (qty <= 0 || cost < 0) {
        isValid = false;
      }
    });

    if (!isValid) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Invalid Values',
        text: 'Please ensure all quantities are greater than 0 and all costs are valid.',
        confirmButtonColor: '#FF6B35'
      });
      return false;
    }
  });
</script>

<style>
  .table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
  }

  .table th,
  .table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
  }

  .table thead th {
    background-color: #f5f5f5;
    font-weight: 600;
  }

  .table tfoot td {
    background-color: #f9f9f9;
    font-weight: bold;
  }

  .text-right {
    text-align: right;
  }

  .required {
    color: var(--danger-color);
  }
</style>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>