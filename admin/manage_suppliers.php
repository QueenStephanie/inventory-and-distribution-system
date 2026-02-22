<?php

/**
 * Supplier Management - Admin
 * Manage suppliers for procurement orders
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('admin');

define('PAGE_TITLE', 'Manage Suppliers');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Add new supplier
    if ($action === 'add') {
      $supplierCode = strtoupper(sanitizeInput($_POST['supplier_code']));
      $supplierName = sanitizeInput($_POST['supplier_name']);
      $contactPerson = sanitizeInput($_POST['contact_person'] ?? '');
      $phone = sanitizeInput($_POST['phone'] ?? '');
      $email = sanitizeInput($_POST['email'] ?? '');
      $address = sanitizeInput($_POST['address'] ?? '');
      $paymentTerms = sanitizeInput($_POST['payment_terms'] ?? '');
      $notes = sanitizeInput($_POST['notes'] ?? '');

      // Check for duplicate code
      $checkStmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE supplier_code = ?");
      $checkStmt->bind_param("s", $supplierCode);
      $checkStmt->execute();

      if ($checkStmt->get_result()->num_rows > 0) {
        $message = 'Supplier code already exists. Please use a different code.';
        $messageType = 'error';
      } else {
        $stmt = $conn->prepare("INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email, address, payment_terms, notes) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $supplierCode, $supplierName, $contactPerson, $phone, $email, $address, $paymentTerms, $notes);

        if ($stmt->execute()) {
          $message = 'Supplier added successfully!';
          $messageType = 'success';
        } else {
          $message = 'Failed to add supplier. Please try again.';
          $messageType = 'error';
        }
      }
    }

    // Edit supplier
    elseif ($action === 'edit') {
      $supplierId = intval($_POST['supplier_id']);
      $supplierCode = strtoupper(sanitizeInput($_POST['supplier_code']));
      $supplierName = sanitizeInput($_POST['supplier_name']);
      $contactPerson = sanitizeInput($_POST['contact_person'] ?? '');
      $phone = sanitizeInput($_POST['phone'] ?? '');
      $email = sanitizeInput($_POST['email'] ?? '');
      $address = sanitizeInput($_POST['address'] ?? '');
      $paymentTerms = sanitizeInput($_POST['payment_terms'] ?? '');
      $notes = sanitizeInput($_POST['notes'] ?? '');

      // Check for duplicate code (excluding current supplier)
      $checkStmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE supplier_code = ? AND supplier_id != ?");
      $checkStmt->bind_param("si", $supplierCode, $supplierId);
      $checkStmt->execute();

      if ($checkStmt->get_result()->num_rows > 0) {
        $message = 'Supplier code already exists. Please use a different code.';
        $messageType = 'error';
      } else {
        $stmt = $conn->prepare("UPDATE suppliers SET supplier_code = ?, supplier_name = ?, contact_person = ?, 
                                       phone = ?, email = ?, address = ?, payment_terms = ?, notes = ? 
                                       WHERE supplier_id = ?");
        $stmt->bind_param("ssssssssi", $supplierCode, $supplierName, $contactPerson, $phone, $email, $address, $paymentTerms, $notes, $supplierId);

        if ($stmt->execute()) {
          $message = 'Supplier updated successfully!';
          $messageType = 'success';
        } else {
          $message = 'Failed to update supplier. Please try again.';
          $messageType = 'error';
        }
      }
    }

    // Toggle status
    elseif ($action === 'toggle_status') {
      $supplierId = intval($_POST['supplier_id']);
      $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';

      // Check if supplier has active procurement orders
      if ($newStatus === 'inactive') {
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM procurement_orders WHERE supplier_id = ? AND status IN ('pending', 'partial')");
        $checkStmt->bind_param("i", $supplierId);
        $checkStmt->execute();
        $activeOrders = $checkStmt->get_result()->fetch_assoc()['count'];

        if ($activeOrders > 0) {
          $message = "Cannot deactivate supplier with {$activeOrders} active procurement order(s).";
          $messageType = 'error';
        } else {
          $stmt = $conn->prepare("UPDATE suppliers SET status = ? WHERE supplier_id = ?");
          $stmt->bind_param("si", $newStatus, $supplierId);

          if ($stmt->execute()) {
            $message = 'Supplier status updated successfully!';
            $messageType = 'success';
          } else {
            $message = 'Failed to update status. Please try again.';
            $messageType = 'error';
          }
        }
      } else {
        $stmt = $conn->prepare("UPDATE suppliers SET status = ? WHERE supplier_id = ?");
        $stmt->bind_param("si", $newStatus, $supplierId);

        if ($stmt->execute()) {
          $message = 'Supplier status updated successfully!';
          $messageType = 'success';
        } else {
          $message = 'Failed to update status. Please try again.';
          $messageType = 'error';
        }
      }
    }
  }
}

// Get all suppliers with order counts
$suppliersQuery = "SELECT s.*, 
                   COUNT(DISTINCT po.order_id) as total_orders,
                   SUM(CASE WHEN po.status IN ('pending', 'partial') THEN 1 ELSE 0 END) as active_orders,
                   MAX(po.order_date) as last_order_date
                   FROM suppliers s
                   LEFT JOIN procurement_orders po ON s.supplier_id = po.supplier_id
                   GROUP BY s.supplier_id
                   ORDER BY s.status DESC, s.supplier_name";
$suppliers = $conn->query($suppliersQuery);

// Get supplier statistics
$statsQuery = "SELECT 
               SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
               SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
               COUNT(*) as total_count
               FROM suppliers";
$stats = $conn->query($statsQuery)->fetch_assoc();

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
<li class="menu-item active">
  <a href="manage_suppliers.php">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      <path d="M16 1H4C2.9 1 2 1.9 2 3v14c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2V3c0-1.1-0.9-2-2-2zM9 13H7v-2h2v2zm0-4H7V5h2v4zm4 4h-2V9h2v4zm0-6h-2V5h2v2z" />
    </svg>
    Manage Suppliers
  </a>
</li>
<li class="menu-item">
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
  <h1>🏢 Manage Suppliers</h1>
  <p>Manage supplier information for procurement orders</p>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?php echo $messageType; ?>">
    <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
  <div class="stat-card stat-success">
    <div class="stat-content">
      <div class="stat-value"><?php echo $stats['active_count']; ?></div>
      <div class="stat-label">Active Suppliers</div>
    </div>
  </div>

  <div class="stat-card stat-secondary">
    <div class="stat-content">
      <div class="stat-value"><?php echo $stats['inactive_count']; ?></div>
      <div class="stat-label">Inactive Suppliers</div>
    </div>
  </div>

  <div class="stat-card stat-info">
    <div class="stat-content">
      <div class="stat-value"><?php echo $stats['total_count']; ?></div>
      <div class="stat-label">Total Suppliers</div>
    </div>
  </div>
</div>

<!-- Actions Bar -->
<div class="actions-bar" style="margin: 20px 0;">
  <button type="button" class="btn btn-primary" onclick="showAddSupplierModal()">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 5px;">
      <path d="M8 0C7.4 0 7 0.4 7 1v6H1C0.4 7 0 7.4 0 8s0.4 1 1 1h6v6c0 0.6 0.4 1 1 1s1-0.4 1-1V9h6c0.6 0 1-0.4 1-1s-0.4-1-1-1H9V1C9 0.4 8.6 0 8 0z" />
    </svg>
    Add New Supplier
  </button>
</div>

<!-- Suppliers Table -->
<div class="content-section">
  <div class="section-header">
    <h3>Supplier List</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table" id="suppliersTable">
      <thead>
        <tr>
          <th>Code</th>
          <th>Supplier Name</th>
          <th>Contact Person</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Total Orders</th>
          <th>Active Orders</th>
          <th>Last Order</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($supplier = $suppliers->fetch_assoc()): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($supplier['supplier_code']); ?></strong></td>
            <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
            <td><?php echo htmlspecialchars($supplier['contact_person'] ?: '-'); ?></td>
            <td><?php echo htmlspecialchars($supplier['phone'] ?: '-'); ?></td>
            <td><?php echo htmlspecialchars($supplier['email'] ?: '-'); ?></td>
            <td><span class="badge badge-light"><?php echo $supplier['total_orders']; ?></span></td>
            <td>
              <?php if ($supplier['active_orders'] > 0): ?>
                <span class="badge badge-warning"><?php echo $supplier['active_orders']; ?></span>
              <?php else: ?>
                <span class="badge badge-light">0</span>
              <?php endif; ?>
            </td>
            <td><?php echo $supplier['last_order_date'] ? formatDate($supplier['last_order_date']) : 'Never'; ?></td>
            <td>
              <?php if ($supplier['status'] === 'active'): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-secondary">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="action-buttons">
              <button type="button" class="btn btn-sm btn-info" onclick='viewSupplier(<?php echo json_encode($supplier); ?>)'>
                <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                  <path d="M7 2C3.5 2 0.5 4.7 0 7c0.5 2.3 3.5 5 7 5s6.5-2.7 7-5c-0.5-2.3-3.5-5-7-5zm0 8c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3-1.3 3-3 3z" />
                </svg>
                View
              </button>
              <button type="button" class="btn btn-sm btn-primary" onclick='editSupplier(<?php echo json_encode($supplier); ?>)'>
                <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                  <path d="M0 11v3h3l8.7-8.7-3-3L0 11zM13.7 3.3c0.3-0.3 0.3-0.8 0-1.1l-1.9-1.9c-0.3-0.3-0.8-0.3-1.1 0l-1.5 1.5 3 3 1.5-1.5z" />
                </svg>
                Edit
              </button>
              <?php if ($supplier['status'] === 'active'): ?>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleStatus(<?php echo $supplier['supplier_id']; ?>, 'inactive', '<?php echo htmlspecialchars($supplier['supplier_name']); ?>')">
                  Deactivate
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-success" onclick="toggleStatus(<?php echo $supplier['supplier_id']; ?>, 'active', '<?php echo htmlspecialchars($supplier['supplier_name']); ?>')">
                  Activate
                </button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Supplier Modal -->
<div id="supplierModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 700px;">
    <span class="close" onclick="closeSupplierModal()">&times;</span>
    <h2 id="modalTitle">Add New Supplier</h2>

    <form id="supplierForm" method="POST" action="">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="supplier_id" id="supplierId" value="">

      <div class="form-row">
        <div class="form-group">
          <label for="supplier_code">Supplier Code <span class="required">*</span></label>
          <input type="text" name="supplier_code" id="supplier_code" class="form-control" required pattern="[A-Z0-9\-]+" title="Only uppercase letters, numbers, and hyphens">
          <small>Use uppercase letters and numbers (e.g., SUP-001)</small>
        </div>

        <div class="form-group">
          <label for="supplier_name">Supplier Name <span class="required">*</span></label>
          <input type="text" name="supplier_name" id="supplier_name" class="form-control" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="contact_person">Contact Person</label>
          <input type="text" name="contact_person" id="contact_person" class="form-control">
        </div>

        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="tel" name="phone" id="phone" class="form-control">
        </div>
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" class="form-control">
      </div>

      <div class="form-group">
        <label for="address">Address</label>
        <textarea name="address" id="address" class="form-control" rows="2"></textarea>
      </div>

      <div class="form-group">
        <label for="payment_terms">Payment Terms</label>
        <input type="text" name="payment_terms" id="payment_terms" class="form-control" placeholder="e.g., Net 30 days, COD">
      </div>

      <div class="form-group">
        <label for="notes">Notes</label>
        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M13.5 2L6 9.5 2.5 6 0 8.5 6 14.5 16 4.5z" />
          </svg>
          Save Supplier
        </button>
        <button type="button" class="btn btn-secondary" onclick="closeSupplierModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- View Supplier Modal -->
<div id="viewSupplierModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 600px;">
    <span class="close" onclick="closeViewModal()">&times;</span>
    <h2>Supplier Details</h2>

    <div id="supplierDetails"></div>

    <div class="form-actions">
      <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
    </div>
  </div>
</div>

<!-- Status Toggle Form (hidden) -->
<form id="statusForm" method="POST" action="" style="display: none;">
  <input type="hidden" name="action" value="toggle_status">
  <input type="hidden" name="supplier_id" id="statusSupplierId">
  <input type="hidden" name="new_status" id="statusNewStatus">
</form>

<script>
  // Initialize DataTable
  $(document).ready(function() {
    $('#suppliersTable').DataTable({
      responsive: true,
      order: [
        [1, 'asc']
      ],
      pageLength: 25,
      language: {
        search: "Search suppliers:",
        lengthMenu: "Show _MENU_ suppliers per page"
      }
    });
  });

  // Show add supplier modal
  function showAddSupplierModal() {
    document.getElementById('modalTitle').textContent = 'Add New Supplier';
    document.getElementById('formAction').value = 'add';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
    document.getElementById('supplier_code').removeAttribute('readonly');
    document.getElementById('supplierModal').style.display = 'flex';
  }

  // Edit supplier
  function editSupplier(supplier) {
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('supplierId').value = supplier.supplier_id;
    document.getElementById('supplier_code').value = supplier.supplier_code;
    document.getElementById('supplier_name').value = supplier.supplier_name;
    document.getElementById('contact_person').value = supplier.contact_person || '';
    document.getElementById('phone').value = supplier.phone || '';
    document.getElementById('email').value = supplier.email || '';
    document.getElementById('address').value = supplier.address || '';
    document.getElementById('payment_terms').value = supplier.payment_terms || '';
    document.getElementById('notes').value = supplier.notes || '';
    document.getElementById('supplierModal').style.display = 'flex';
  }

  // View supplier details
  function viewSupplier(supplier) {
    const details = `
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Supplier Code:</span>
                <span class="info-value"><strong>${supplier.supplier_code}</strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">Supplier Name:</span>
                <span class="info-value">${supplier.supplier_name}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Contact Person:</span>
                <span class="info-value">${supplier.contact_person || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">${supplier.phone || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">${supplier.email || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Address:</span>
                <span class="info-value">${supplier.address || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Terms:</span>
                <span class="info-value">${supplier.payment_terms || '-'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Orders:</span>
                <span class="info-value">${supplier.total_orders}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Active Orders:</span>
                <span class="info-value">${supplier.active_orders}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="badge badge-${supplier.status === 'active' ? 'success' : 'secondary'}">
                        ${supplier.status.charAt(0).toUpperCase() + supplier.status.slice(1)}
                    </span>
                </span>
            </div>
            ${supplier.notes ? `
            <div class="info-row" style="grid-column: 1/-1;">
                <span class="info-label">Notes:</span>
                <span class="info-value">${supplier.notes}</span>
            </div>
            ` : ''}
        </div>
    `;

    document.getElementById('supplierDetails').innerHTML = details;
    document.getElementById('viewSupplierModal').style.display = 'flex';
  }

  // Close modals
  function closeSupplierModal() {
    document.getElementById('supplierModal').style.display = 'none';
  }

  function closeViewModal() {
    document.getElementById('viewSupplierModal').style.display = 'none';
  }

  // Toggle supplier status
  function toggleStatus(supplierId, newStatus, supplierName) {
    const action = newStatus === 'active' ? 'activate' : 'deactivate';

    Swal.fire({
      title: `${action.charAt(0).toUpperCase() + action.slice(1)} Supplier?`,
      text: `Are you sure you want to ${action} "${supplierName}"?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#FF6B35',
      cancelButtonColor: '#6c757d',
      confirmButtonText: `Yes, ${action}!`,
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('statusSupplierId').value = supplierId;
        document.getElementById('statusNewStatus').value = newStatus;
        document.getElementById('statusForm').submit();
      }
    });
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    const supplierModal = document.getElementById('supplierModal');
    const viewModal = document.getElementById('viewSupplierModal');
    if (event.target == supplierModal) {
      closeSupplierModal();
    }
    if (event.target == viewModal) {
      closeViewModal();
    }
  }

  // Auto-uppercase supplier code
  document.getElementById('supplier_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
  });
</script>

<style>
  .info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding: 20px 0;
  }

  .info-row {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 10px;
    align-items: start;
  }

  .info-label {
    font-weight: 600;
    color: #666;
  }

  .info-value {
    color: #333;
  }

  .action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
  }

  .required {
    color: var(--danger-color);
  }
</style>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>