<?php
/**
 * Manage Raw Materials - Superadmin
 */

require_once '../config/database.php';
require_once '../config/auth.php';

startSecureSession();
requireRole('superadmin');

define('PAGE_TITLE', 'Manage Raw Materials');

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle material actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $materialCode = sanitizeInput($_POST['material_code']);
        $materialName = sanitizeInput($_POST['material_name']);
        $category = $_POST['category'];
        $unit = sanitizeInput($_POST['unit_of_measure']);
        $minLevel = floatval($_POST['minimum_stock_level']);
        
        $conn->begin_transaction();
        
        try {
            // Insert material
            $stmt = $conn->prepare("INSERT INTO raw_materials (material_code, material_name, category, unit_of_measure, minimum_stock_level) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssd", $materialCode, $materialName, $category, $unit, $minLevel);
            $stmt->execute();
            $materialId = $conn->insert_id;
            
            // Initialize inventory for all branches
            $stmt = $conn->prepare("INSERT INTO inventory (branch_id, material_id, current_quantity) 
                                   SELECT branch_id, ?, 0.00 FROM branches");
            $stmt->bind_param("i", $materialId);
            $stmt->execute();
            
            $conn->commit();
            
            $message = "Material '{$materialName}' has been added successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Failed to add material. Code may already exist.';
            $messageType = 'error';
        }
    } elseif ($action === 'edit') {
        $materialId = intval($_POST['material_id']);
        $materialName = sanitizeInput($_POST['material_name']);
        $category = $_POST['category'];
        $unit = sanitizeInput($_POST['unit_of_measure']);
        $minLevel = floatval($_POST['minimum_stock_level']);
        
        $stmt = $conn->prepare("UPDATE raw_materials SET material_name = ?, category = ?, unit_of_measure = ?, minimum_stock_level = ? WHERE material_id = ?");
        $stmt->bind_param("sssdi", $materialName, $category, $unit, $minLevel, $materialId);
        
        if ($stmt->execute()) {
            $message = 'Material has been updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update material.';
            $messageType = 'error';
        }
    } elseif ($action === 'deactivate') {
        $materialId = intval($_POST['material_id']);
        $stmt = $conn->prepare("UPDATE raw_materials SET status = 'inactive' WHERE material_id = ?");
        $stmt->bind_param("i", $materialId);
        $stmt->execute();
        $message = 'Material has been deactivated.';
        $messageType = 'success';
    } elseif ($action === 'activate') {
        $materialId = intval($_POST['material_id']);
        $stmt = $conn->prepare("UPDATE raw_materials SET status = 'active' WHERE material_id = ?");
        $stmt->bind_param("i", $materialId);
        $stmt->execute();
        $message = 'Material has been activated.';
        $messageType = 'success';
    }
}

// Get all materials
$materials = $conn->query("SELECT material_id, material_code, material_name, category, unit_of_measure, minimum_stock_level, status, created_at FROM raw_materials ORDER BY status, category, material_name");

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
<li class="menu-item">
    <a href="manage_branches.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 0L0 6v10h6v-6h8v6h6V6L10 0z"/>
        </svg>
        Manage Branches
    </a>
</li>
<li class="menu-item active">
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
    <h1>Manage Raw Materials & Products</h1>
    <p>Add, edit, and manage inventory items</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Add New Material Form -->
<div class="content-section">
    <h2>Add New Material</h2>
    <form method="POST" action="" class="form-horizontal">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label for="material_code">Material Code *</label>
                <input type="text" name="material_code" id="material_code" class="form-control" placeholder="e.g., CHK-001" required>
            </div>
            <div class="form-group">
                <label for="material_name">Material Name *</label>
                <input type="text" name="material_name" id="material_name" class="form-control" placeholder="e.g., Whole Chicken" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <select name="category" id="category" class="form-control" required>
                    <option value="">Select Category</option>
                    <option value="chicken">Chicken</option>
                    <option value="oil">Oil</option>
                    <option value="flour">Flour</option>
                    <option value="spices">Spices</option>
                    <option value="packaging">Packaging</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="unit_of_measure">Unit of Measure *</label>
                <input type="text" name="unit_of_measure" id="unit_of_measure" class="form-control" placeholder="e.g., kg, liters, pieces" required>
            </div>
            <div class="form-group">
                <label for="minimum_stock_level">Minimum Stock Level *</label>
                <input type="number" name="minimum_stock_level" id="minimum_stock_level" class="form-control" step="0.01" min="0" placeholder="0.00" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Add Material</button>
    </form>
</div>

<!-- Materials List -->
<div class="content-section">
    <h2>All Materials</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Min Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($material = $materials->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($material['material_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                        <td><span class="badge badge-light"><?php echo ucfirst($material['category']); ?></span></td>
                        <td><?php echo htmlspecialchars($material['unit_of_measure']); ?></td>
                        <td><?php echo formatNumber($material['minimum_stock_level']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $material['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($material['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($material['status'] === 'active'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="material_id" value="<?php echo $material['material_id']; ?>">
                                    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Deactivate this material?')">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="material_id" value="<?php echo $material['material_id']; ?>">
                                    <button type="submit" class="btn btn-xs btn-success">Activate</button>
                                </form>
                            <?php endif; ?>
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
