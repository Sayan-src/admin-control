<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit();
}

// Get dashboard statistics
try {
    $pdo = getDBConnection();
    
    // Total members
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
    $totalMembers = $stmt->fetch()['total'];
    
    // Active members
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM members WHERE status = 'active'");
    $activeMembers = $stmt->fetch()['active'];
    
    // Total invoices
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM invoices");
    $totalInvoices = $stmt->fetch()['total'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as revenue FROM invoices WHERE status = 'paid'");
    $totalRevenue = $stmt->fetch()['revenue'] ?? 0;
    
    // Recent members (last 5)
    $stmt = $pdo->query("SELECT * FROM members ORDER BY created_at DESC LIMIT 5");
    $recentMembers = $stmt->fetchAll();
    
    // Recent invoices (last 5)
    $stmt = $pdo->query("
        SELECT i.*, m.first_name, m.last_name, s.service_name 
        FROM invoices i 
        JOIN members m ON i.member_id = m.id 
        JOIN services s ON i.service_id = s.id 
        ORDER BY i.created_at DESC LIMIT 5
    ");
    $recentInvoices = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $totalMembers = 0;
    $activeMembers = 0;
    $totalInvoices = 0;
    $totalRevenue = 0;
    $recentMembers = [];
    $recentInvoices = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Billing Software</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <nav class="admin-nav">
            <h1>Billing Software Admin</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="members.php">Members</a>
                <a href="invoices.php">Invoices</a>
                <a href="reports.php">Reports</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>Dashboard Overview</h2>
            <div class="page-actions">
                <a href="members.php?action=add" class="btn btn-primary">Add Member</a>
                <a href="invoices.php?action=create" class="btn btn-success">Create Invoice</a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Total Members</div>
                        <div class="card-value"><?php echo $totalMembers; ?></div>
                    </div>
                    <div class="card-icon blue">👥</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Active Members</div>
                        <div class="card-value"><?php echo $activeMembers; ?></div>
                    </div>
                    <div class="card-icon green">✅</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Total Invoices</div>
                        <div class="card-value"><?php echo $totalInvoices; ?></div>
                    </div>
                    <div class="card-icon orange">📄</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Total Revenue</div>
                        <div class="card-value">$<?php echo number_format($totalRevenue, 2); ?></div>
                    </div>
                    <div class="card-icon purple">💰</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-sections">
            <!-- Recent Members -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Recent Members</h3>
                    <div class="table-actions">
                        <a href="members.php" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Membership</th>
                            <th>Status</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentMembers as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                            <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($member['membership_type'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $member['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($member['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($member['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Invoices -->
            <div class="table-container" style="margin-top: 20px;">
                <div class="table-header">
                    <h3>Recent Invoices</h3>
                    <div class="table-actions">
                        <a href="invoices.php" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Member</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentInvoices as $invoice): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['service_name']); ?></td>
                            <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $invoice['status']; ?>">
                                    <?php echo ucfirst(htmlspecialchars($invoice['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($invoice['invoice_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html> 