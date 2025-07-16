<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in and forced to change password
if(!isset($_SESSION['force_password_change']) || $_SESSION['force_password_change'] !== true) {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    try {
        $pdo = getDBConnection();
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if($admin && password_verify($currentPassword, $admin['password'])) {
            // Validate new password
            if(strlen($newPassword) < 8) {
                $error = 'New password must be at least 8 characters long';
            } elseif($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match';
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                
                // Set proper session and redirect
                $_SESSION['admin_logged_in'] = true;
                unset($_SESSION['force_password_change']);
                
                $message = 'Password changed successfully! Redirecting to dashboard...';
                header('Refresh: 2; URL=dashboard.php');
            }
        } else {
            $error = 'Current password is incorrect';
        }
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Billing Software</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Security Required</h1>
                <h2>Change Default Password</h2>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="security-notice">
                <p><strong>⚠️ Security Alert:</strong></p>
                <p>You are using the default password. For security reasons, you must change it before accessing the system.</p>
            </div>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                    <small>Password must be at least 8 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Change Password</button>
            </form>
            
            <div class="login-footer">
                <p><strong>Default password:</strong> password</p>
            </div>
        </div>
    </div>
    
    <style>
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
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