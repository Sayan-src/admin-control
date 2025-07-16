<?php
require_once 'config/database.php';

// Check if admin already exists
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users");
    $result = $stmt->fetch();
    
    if($result['count'] > 0) {
        die("Admin user already exists. For security reasons, this setup script is disabled.");
    }
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $fullName = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    if(strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long';
    } elseif(strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if($stmt->fetch()) {
                $error = 'Username or email already exists';
            } else {
                // Create admin user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO admin_users (username, password, email, full_name, role) 
                    VALUES (?, ?, ?, ?, 'super_admin')
                ");
                $stmt->execute([$username, $hashedPassword, $email, $fullName]);
                
                $message = 'Admin user created successfully! You can now login.';
                
                // Disable this script for security
                rename(__FILE__, __FILE__ . '.disabled');
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Billing Software</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Admin Setup</h1>
                <h2>Create First Admin User</h2>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                    <br><br>
                    <a href="auth/login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="security-notice">
                    <p><strong>🔒 Security Setup:</strong></p>
                    <p>This script will create your first admin user. After creation, this script will be automatically disabled for security.</p>
                </div>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required minlength="3">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="8">
                        <small>Password must be at least 8 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Create Admin User</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .security-notice {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #0c5460;
        }
        
        .security-notice p {
            margin: 5px 0;
        }
        
        .form-group small {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
    </style>
</body>
</html> 