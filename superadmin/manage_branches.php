<?php
/**
 * Manage Branches - Superadmin
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('superadmin');

define('PAGE_TITLE', 'Manage Branches');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle branch actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $branchName = sanitizeInput($_POST['branch_name']);
        $location = sanitizeInput($_POST['location']);
        $contact = sanitizeInput($_POST['contact_number']);
        
        $conn->begin_transaction();
        
        try {
            // Insert branch
            $stmt = $conn->prepare("INSERT INTO branches (branch_name, branch_location, contact_number) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $branchName, $location, $contact);
            $stmt->execute();
            $branchId = $conn->insert_id;
            
            // Initialize inventory for new branch
            $stmt = $conn->prepare("INSERT INTO inventory (branch_id, material_id, current_quantity) 
                                   SELECT ?, material_id, 0.00 FROM raw_materials WHERE status = 'active'");
            $stmt->bind_param("i", $branchId);
            $stmt->execute();
            
            $conn->commit();
            
            $message = "Branch '{$branchName}' has been added successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Failed to add branch.';
            $messageType = 'error';
        }
    } elseif ($action === 'deactivate') {
        $branchId = intval($_POST['branch_id']);
        $stmt = $conn->prepare("UPDATE branches SET status = 'inactive' WHERE branch_id = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $message = 'Branch has been deactivated.';
        $messageType = 'success';
    } elseif ($action === 'activate') {
        $branchId = intval($_POST['branch_id']);
        $stmt = $conn->prepare("UPDATE branches SET status = 'active' WHERE branch_id = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $message = 'Branch has been activated.';
        $messageType = 'success';
    }
}

// Get all branches
$branches = $conn->query("SELECT * FROM branches ORDER BY is_main_branch DESC, branch_name");

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
<li class="menu-item active">
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
    <h1>Manage Branches</h1>
    <p>Add and manage branch locations</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Add Branch Form -->
<div class="content-section">
    <h2>Add New Branch</h2>
    <form method="POST" action="" class="branch-form">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label for="branch_name">Branch Name *</label>
                <input type="text" name="branch_name" id="branch_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" name="location" id="location" class="form-control" placeholder="e.g., Clarin, Misamis Occidental" required>
            </div>
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="text" name="contact_number" id="contact_number" class="form-control" placeholder="09XX-XXX-XXXX">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Add Branch</button>
    </form>
</div>

<!-- Existing Branches -->
<div class="content-section">
    <h2>Existing Branches</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Branch Name</th>
                    <th>Location</th>
                    <th>Contact Number</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($branch = $branches->fetch_assoc()): ?>
                    <tr class="<?php echo $branch['status'] === 'inactive' ? 'row-muted' : ''; ?>">
                        <td><strong><?php echo htmlspecialchars($branch['branch_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($branch['branch_location']); ?></td>
                        <td><?php echo htmlspecialchars($branch['contact_number'] ?: '-'); ?></td>
                        <td>
                            <?php if ($branch['is_main_branch']): ?>
                                <span class="badge badge-primary">Main Commissary</span>
                            <?php else: ?>
                                <span class="badge badge-light">Branch</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $branch['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($branch['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($branch['created_at']); ?></td>
                        <td>
                            <?php if (!$branch['is_main_branch']): ?>
                                <form method="POST" action="" style="display:inline;" class="branch-action-form">
                                    <input type="hidden" name="branch_id" value="<?php echo $branch['branch_id']; ?>">
                                    <?php if ($branch['status'] === 'active'): ?>
                                        <button type="submit" name="action" value="deactivate" class="btn btn-xs btn-warning" data-confirm="Deactivate this branch?">Deactivate</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="activate" class="btn btn-xs btn-success">Activate</button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Main Branch</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Handle branch action confirmations with SweetAlert2
document.querySelectorAll('.branch-action-form button[data-confirm]').forEach(button => {
    button.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const confirmMessage = this.getAttribute('data-confirm');
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: confirmMessage,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#FF6B35',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
        });
        
        if (result.isConfirmed) {
            this.closest('form').submit();
        }
    });
});
</script>

<?php
closeDBConnection($conn);
include '../includes/footer.php';
?>
