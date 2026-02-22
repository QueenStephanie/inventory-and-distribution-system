<?php

/**
 * Password Reset Utility
 * Use this to reset any user's password
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Password Reset</title>";
echo "<style>body{font-family:Arial;margin:40px;background:#f5f5f5;}";
echo ".container{background:white;padding:30px;border-radius:8px;max-width:600px;}";
echo ".success{color:#4CAF50;padding:10px;background:#E8F5E9;border-radius:4px;margin:10px 0;}";
echo ".error{color:#F44336;padding:10px;background:#FFEBEE;border-radius:4px;margin:10px 0;}";
echo ".info{color:#2196F3;padding:10px;background:#E3F2FD;border-radius:4px;margin:10px 0;}";
echo "table{width:100%;border-collapse:collapse;margin:20px 0;}";
echo "th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd;}";
echo "th{background:#FF6B35;color:white;}";
echo ".btn{padding:8px 16px;background:#FF6B35;color:white;border:none;border-radius:4px;cursor:pointer;text-decoration:none;display:inline-block;}";
echo ".btn:hover{background:#E55A2B;}</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔐 Password Reset Utility</h1>";

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['new_password'])) {
  $username = $_POST['username'];
  $newPassword = $_POST['new_password'];

  if (strlen($newPassword) < 6) {
    echo "<div class='error'>❌ Password must be at least 6 characters!</div>";
  } else {
    $conn = getDBConnection();
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashedPassword, $username);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
      echo "<div class='success'>✅ Password for user '<strong>$username</strong>' has been reset to '<strong>$newPassword</strong>'</div>";
      echo "<div class='info'>You can now login with these credentials!</div>";
    } else {
      echo "<div class='error'>❌ Failed to reset password. User not found or no changes made.</div>";
    }

    closeDBConnection($conn);
  }
}

// Test current password
if (isset($_GET['test_password'])) {
  echo "<h2>🧪 Testing Password 'admin123'</h2>";

  $conn = getDBConnection();
  $result = $conn->query("SELECT username, password FROM users WHERE username='superadmin'");

  if ($row = $result->fetch_assoc()) {
    $isValid = password_verify('admin123', $row['password']);

    if ($isValid) {
      echo "<div class='success'>✅ Password 'admin123' IS VALID for superadmin!</div>";
      echo "<div class='info'>If you still can't login, there might be an issue with the login form or session.</div>";
    } else {
      echo "<div class='error'>❌ Password 'admin123' is NOT VALID for superadmin!</div>";
      echo "<div class='info'>The password hash in the database doesn't match 'admin123'. Use the form below to reset it.</div>";
    }
  }

  closeDBConnection($conn);
}

// Display all users
$conn = getDBConnection();
$result = $conn->query("SELECT username, role, full_name, status FROM users ORDER BY role, username");

echo "<h2>👥 Current Users</h2>";
echo "<table>";
echo "<tr><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th></tr>";

while ($row = $result->fetch_assoc()) {
  echo "<tr>";
  echo "<td><strong>" . htmlspecialchars($row['username']) . "</strong></td>";
  echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
  echo "<td>" . ucfirst(str_replace('_', ' ', $row['role'])) . "</td>";
  echo "<td>" . $row['status'] . "</td>";
  echo "</tr>";
}

echo "</table>";

closeDBConnection($conn);

// Reset form
echo "<h2>🔄 Reset Password</h2>";
echo "<form method='POST' action=''>";
echo "<p><label>Username: <input type='text' name='username' required style='width:200px;padding:8px;'></label></p>";
echo "<p><label>New Password: <input type='text' name='new_password' required style='width:200px;padding:8px;' placeholder='min 6 characters'></label></p>";
echo "<p><button type='submit' class='btn'>Reset Password</button></p>";
echo "</form>";

echo "<hr style='margin:30px 0;'>";
echo "<p><a href='?test_password=1' class='btn'>🧪 Test Password 'admin123'</a></p>";
echo "<p><a href='index.php' class='btn'>← Back to Login</a></p>";

echo "</div></body></html>";
