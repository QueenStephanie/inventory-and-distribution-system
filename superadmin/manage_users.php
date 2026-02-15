<?php
/**
 * Manage Users - Superadmin
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('superadmin');

define('PAGE_TITLE', 'Manage Users');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = sanitizeInput($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $role = $_POST['role'];
        $branchId = $role === 'branch_user' ? intval($_POST['branch_id']) : null;
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $username, $password, $fullName, $email, $role, $branchId);
        
        if ($stmt->execute()) {
            $message = "User '{$username}' has been added successfully!";
            $messageType = 'success';
        } else {
            $message = 'Failed to add user. Username may already exist.';
            $messageType = 'error';
        }
    } elseif ($action === 'deactivate') {
        $userId = intval($_POST['user_id']);
        $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $message = 'User has been deactivated.';
        $messageType = 'success';
    } elseif ($action === 'activate') {
        $userId = intval($_POST['user_id']);
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $message = 'User has been activated.';
        $messageType = 'success';
    }
}

// Get all users
$usersQuery = "SELECT u.*, b.branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.branch_id ORDER BY u.role, u.full_name";
$users = $conn->query($usersQuery);

// Get branches for form
$branches = $conn->query("SELECT branch_id, branch_name FROM branches WHERE is_main_branch = FALSE ORDER BY branch_name");

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
<li class="menu-item active">
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
    <a href="system_reports.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M4 2h12c1.1 0 2 0.9 2 2v12c0 1.1-0.9 2-2 2H4c-1.1 0-2-0.9-2-2V4c0-1.1 0.9-2 2-2zm0 14h4V8H4v8zm6 0h4v-6h-4v6zm6 0h4v-4h-4v4z"/>
        </svg>
        System Reports
    </a>
</li>

<?php include '../includes/sidebar_end.php'; ?>

<div class="page-header">
    <h1>Manage Users</h1>
    <p>Add, edit, and manage system users</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Add User Form -->
<div class="content-section">
    <h2>Add New User</h2>
    <form method="POST" action="" class="user-form">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="6">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="role">Role *</label>
                <select name="role" id="role" class="form-control" required onchange="toggleBranchField()">
                    <option value="">Select Role</option>
                    <option value="branch_user">Branch User</option>
                    <option value="admin">Admin</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </div>
            <div class="form-group" id="branch-field" style="display:none;">
                <label for="branch_id">Assigned Branch *</label>
                <select name="branch_id" id="branch_id" class="form-control">
                    <option value="">Select Branch</option>
                    <?php 
                    $branches->data_seek(0);
                    while ($branch = $branches->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $branch['branch_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Add User</button>
    </form>
</div>

<!-- Existing Users -->
<div class="content-section">
    <h2>Existing Users</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Branch</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr class="<?php echo $u['status'] === 'inactive' ? 'row-muted' : ''; ?>">
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $u['role'])); ?></span></td>
                        <td><?php echo $u['branch_name'] ? htmlspecialchars($u['branch_name']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($u['email'] ?: '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $u['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $u['last_login'] ? formatDateTime($u['last_login']) : 'Never'; ?></td>
                        <td>
                            <?php if ($u['user_id'] !== $user['user_id']): ?>
                                <form method="POST" action="" style="display:inline;" class="user-action-form">
                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <button type="submit" name="action" value="deactivate" class="btn btn-xs btn-warning" data-confirm="Deactivate this user?">Deactivate</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="activate" class="btn btn-xs btn-success">Activate</button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleBranchField() {
    const role = document.getElementById('role').value;
    const branchField = document.getElementById('branch-field');
    const branchSelect = document.getElementById('branch_id');
    
    if (role === 'branch_user') {
        branchField.style.display = 'block';
        branchSelect.required = true;
    } else {
        branchField.style.display = 'none';
        branchSelect.required = false;
        branchSelect.value = '';
    }
}

// Handle user action confirmations with SweetAlert2
document.querySelectorAll('.user-action-form button[data-confirm]').forEach(button => {
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
