<?php
/**
 * Password Hash Generator Utility
 * 
 * This script generates bcrypt password hashes for use in the database.
 * Use this when you need to create new user accounts or change passwords.
 * 
 * Usage:
 * 1. Open in browser: http://localhost/inventory%20and%20distribution%20system/utilities/generate_password_hash.php
 * 2. Enter the password you want to hash
 * 3. Copy the generated hash into your SQL INSERT statement
 */

$hash = '';
$password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $error = 'Please enter a password';
    } else {
        // Generate bcrypt hash (same algorithm used in the system)
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 5px;
            border-left: 4px solid #48bb78;
        }
        
        .result-title {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .hash-value {
            background: white;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #e2e8f0;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #2d3748;
            margin-top: 10px;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c53030;
        }
        
        .info-box {
            background: #bee3f8;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #3182ce;
        }
        
        .info-box h3 {
            color: #2c5282;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .info-box p {
            color: #2c5282;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .copy-btn {
            background: #48bb78;
            padding: 8px 16px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .copy-btn:hover {
            background: #38a169;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>
        <p class="subtitle">Generate secure bcrypt password hashes for your database</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Enter Password:</label>
                <input 
                    type="text" 
                    id="password" 
                    name="password" 
                    value="<?php echo htmlspecialchars($password); ?>"
                    placeholder="e.g., admin123"
                    required
                    autocomplete="off"
                >
            </div>
            
            <button type="submit">Generate Hash</button>
        </form>
        
        <?php if ($hash): ?>
            <div class="result">
                <div class="result-title">✅ Generated Hash:</div>
                <div class="hash-value" id="hashValue"><?php echo htmlspecialchars($hash); ?></div>
                <button class="copy-btn" onclick="copyHash()">Copy to Clipboard</button>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📘 How to Use This Hash:</h3>
            <p>
                1. Copy the generated hash above<br>
                2. Use it in your SQL INSERT or UPDATE statement:<br>
                <code style="background: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                    UPDATE users SET password = 'PASTE_HASH_HERE' WHERE username = 'username';
                </code><br>
                3. The user can now login with the password you entered above
            </p>
        </div>
    </div>
    
    <script>
        function copyHash() {
            const hashValue = document.getElementById('hashValue').textContent;
            navigator.clipboard.writeText(hashValue).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Hash copied to clipboard!',
                    confirmButtonColor: '#667eea',
                    timer: 2000,
                    timerProgressBar: true
                });
            }).catch(err => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = hashValue;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Hash copied to clipboard!',
                    confirmButtonColor: '#667eea',
                    timer: 2000,
                    timerProgressBar: true
                });
            });
        }
    </script>
</body>
</html>
