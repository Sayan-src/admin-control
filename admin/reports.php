<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit();
}

// Get filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$reportType = $_GET['report_type'] ?? 'members';

$stats = [];
$data = [];

try {
    $pdo = getDBConnection();
    
    if($reportType === 'members') {
        // Member registration statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_registrations,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_members,
                COUNT(CASE WHEN membership_type = 'basic' THEN 1 END) as basic_members,
                COUNT(CASE WHEN membership_type = 'premium' THEN 1 END) as premium_members,
                COUNT(CASE WHEN membership_type = 'enterprise' THEN 1 END) as enterprise_members
            FROM members 
            WHERE DATE(registration_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats = $stmt->fetch();
        
        // Member registrations by date
        $stmt = $pdo->prepare("
            SELECT 
                DATE(registration_date) as date,
                COUNT(*) as count
            FROM members 
            WHERE DATE(registration_date) BETWEEN ? AND ?
            GROUP BY DATE(registration_date)
            ORDER BY date
        ");
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
        
    } elseif($reportType === 'invoices') {
        // Invoice statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_invoices,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_invoices,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'paid' THEN total_amount ELSE NULL END) as avg_invoice_amount
            FROM invoices 
            WHERE DATE(invoice_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats = $stmt->fetch();
        
        // Revenue by date
        $stmt = $pdo->prepare("
            SELECT 
                DATE(invoice_date) as date,
                COUNT(*) as invoice_count,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as revenue
            FROM invoices 
            WHERE DATE(invoice_date) BETWEEN ? AND ?
            GROUP BY DATE(invoice_date)
            ORDER BY date
        ");
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
        
    } elseif($reportType === 'services') {
        // Service usage statistics
        $stmt = $pdo->prepare("
            SELECT 
                s.service_name,
                COUNT(i.id) as usage_count,
                SUM(i.total_amount) as total_revenue,
                AVG(i.total_amount) as avg_amount
            FROM services s
            LEFT JOIN invoices i ON s.id = i.service_id 
                AND DATE(i.invoice_date) BETWEEN ? AND ?
            GROUP BY s.id, s.service_name
            ORDER BY usage_count DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
    }
    
} catch(PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Billing Software</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <div class="page-header">
            <h2>Reports & Analytics</h2>
            <div class="page-actions">
                <button onclick="exportReport()" class="btn btn-primary">Export Report</button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="report_type">Report Type</label>
                    <select id="report_type" name="report_type">
                        <option value="members" <?php echo $reportType === 'members' ? 'selected' : ''; ?>>Member Registrations</option>
                        <option value="invoices" <?php echo $reportType === 'invoices' ? 'selected' : ''; ?>>Invoice Analytics</option>
                        <option value="services" <?php echo $reportType === 'services' ? 'selected' : ''; ?>>Service Usage</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>

        <!-- Report Content -->
        <?php if($reportType === 'members' && $stats): ?>
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Total Registrations</div>
                            <div class="card-value"><?php echo $stats['total_registrations']; ?></div>
                        </div>
                        <div class="card-icon blue">👥</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Active Members</div>
                            <div class="card-value"><?php echo $stats['active_members']; ?></div>
                        </div>
                        <div class="card-icon green">✅</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Basic Members</div>
                            <div class="card-value"><?php echo $stats['basic_members']; ?></div>
                        </div>
                        <div class="card-icon orange">📊</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Premium Members</div>
                            <div class="card-value"><?php echo $stats['premium_members']; ?></div>
                        </div>
                        <div class="card-icon purple">⭐</div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <h3>Member Registrations by Date</h3>
                </div>
                <canvas id="memberChart" width="400" height="200"></canvas>
            </div>

        <?php elseif($reportType === 'invoices' && $stats): ?>
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Total Invoices</div>
                            <div class="card-value"><?php echo $stats['total_invoices']; ?></div>
                        </div>
                        <div class="card-icon blue">📄</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Paid Invoices</div>
                            <div class="card-value"><?php echo $stats['paid_invoices']; ?></div>
                        </div>
                        <div class="card-icon green">💰</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Total Revenue</div>
                            <div class="card-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                        </div>
                        <div class="card-icon orange">💵</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Avg Invoice</div>
                            <div class="card-value">$<?php echo number_format($stats['avg_invoice_amount'], 2); ?></div>
                        </div>
                        <div class="card-icon purple">📊</div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <h3>Revenue Analytics</h3>
                </div>
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>

        <?php elseif($reportType === 'services' && $data): ?>
            <div class="table-container">
                <div class="table-header">
                    <h3>Service Usage Report</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Usage Count</th>
                            <th>Total Revenue</th>
                            <th>Average Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $service): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                            <td><?php echo $service['usage_count']; ?></td>
                            <td>$<?php echo number_format($service['total_revenue'], 2); ?></td>
                            <td>$<?php echo number_format($service['avg_amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Chart configurations
        <?php if($reportType === 'members' && $data): ?>
        const memberCtx = document.getElementById('memberChart').getContext('2d');
        new Chart(memberCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($data, 'date')); ?>,
                datasets: [{
                    label: 'Registrations',
                    data: <?php echo json_encode(array_column($data, 'count')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if($reportType === 'invoices' && $data): ?>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($data, 'date')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($data, 'revenue')); ?>,
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        function exportReport() {
            const urlParams = new URLSearchParams(window.location.search);
            const startDate = urlParams.get('start_date');
            const endDate = urlParams.get('end_date');
            const reportType = urlParams.get('report_type');
            
            // Create export URL
            const exportUrl = `export_report.php?start_date=${startDate}&end_date=${endDate}&report_type=${reportType}`;
            window.open(exportUrl, '_blank');
        }
    </script>
</body>
</html> 