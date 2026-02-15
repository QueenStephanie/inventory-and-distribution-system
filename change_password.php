<?php
/**
 * Change Password - All Users
 */

require_once 'config/database.php';
require_once 'config/auth.php';

startSecureSession();
requireLogin();

$user = getCurrentUser();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = 'All fields are required.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        
        if (password_verify($currentPassword, $userData['password'])) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashedPassword, $user['user_id']);
            
            if ($stmt->execute()) {
                $message = 'Password changed successfully!';
                $messageType = 'success';
                $_POST = []; // Clear form
            } else {
                $message = 'Failed to update password. Please try again.';
                $messageType = 'error';
            }
        } else {
            $message = 'Current password is incorrect.';
            $messageType = 'error';
        }
    }
}

// Get role-specific dashboard URL
$dashboardUrl = getDashboardUrl($user['role']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Inventory Management System</title>
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="change-password-page">
    <div class="login-page">
        <div class="login-container">
            <div class="login-box">
                <div class="login-header">
                    <div class="logo">
                        <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="60" height="60" rx="10" fill="#FF6B35"/>
                            <path d="M30 15L42 25V45H18V25L30 15Z" fill="white"/>
                            <rect x="25" y="35" width="10" height="10" fill="#FF6B35"/>
                        </svg>
                    </div>
                    <h1>Change Password</h1>
                    <p>Update your account password</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            class="form-control" 
                            required 
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-control" 
                            minlength="6"
                            required
                        >
                        <small style="color: #757575;">Must be at least 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            minlength="6"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M11 1a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5V3a3 3 0 0 1 6 0v4a.5.5 0 0 1-1 0V3a2 2 0 0 0-2-2z"/>
                        </svg>
                        Change Password
                    </button>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo $dashboardUrl; ?>" class="btn btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                            </svg>
                            Back to Dashboard
                        </a>
                    </div>
                </form>

                <div class="login-info" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                    <p style="margin: 0; font-size: 12px; color: #757575;">
                        <strong>Logged in as:</strong> <?php echo htmlspecialchars($user['full_name']); ?> 
                        (<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>)
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            let strength = 'weak';
            let color = '#F44336';
            
            if (password.length >= 8) {
                strength = 'medium';
                color = '#FFC107';
            }
            if (password.length >= 12 && /[A-Z]/.test(password) && /[0-9]/.test(password)) {
                strength = 'strong';
                color = '#4CAF50';
            }
            
            // You can add visual feedback here if desired
        });

        // Validate password match
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'New passwords do not match!',
                    confirmButtonColor: '#FF6B35'
                });
            }
        });
    </script>
</body>
</html>
<?php
closeDBConnection($conn);
?>
