<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit();
}

$invoiceId = $_GET['id'] ?? 0;
$invoice = null;
$error = '';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT i.*, m.first_name, m.last_name, m.email, m.phone, m.address, m.city, m.state, m.zip_code, m.member_id as member_code, s.service_name, s.description as service_description
        FROM invoices i 
        JOIN members m ON i.member_id = m.id 
        JOIN services s ON i.service_id = s.id 
        WHERE i.id = ?
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    
    if(!$invoice) {
        $error = 'Invoice not found';
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
    <title>Invoice #<?php echo $invoice ? $invoice['invoice_number'] : 'Not Found'; ?> - Billing Software</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .invoice-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        
        .invoice-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .invoice-content {
            padding: 30px;
        }
        
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-section p {
            margin: 5px 0;
            color: #666;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .invoice-table th,
        .invoice-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .invoice-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .invoice-total {
            margin-top: 30px;
            text-align: right;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .total-row.final {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }
        
        .invoice-actions {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #e2e3e5; color: #383d41; }
        
        @media print {
            .admin-header,
            .invoice-actions {
                display: none !important;
            }
            
            .invoice-container {
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
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

    <?php if($error): ?>
        <div class="main-content">
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <a href="invoices.php" class="btn btn-primary">Back to Invoices</a>
        </div>
    <?php elseif($invoice): ?>
        <div class="invoice-container">
            <div class="invoice-header">
                <h1>INVOICE</h1>
                <p>Billing Software - Professional Billing Solutions</p>
            </div>
            
            <div class="invoice-content">
                <div class="invoice-info">
                    <div class="info-section">
                        <h3>Bill To:</h3>
                        <p><strong><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($invoice['member_code']); ?></p>
                        <p><?php echo htmlspecialchars($invoice['email']); ?></p>
                        <?php if($invoice['phone']): ?>
                            <p><?php echo htmlspecialchars($invoice['phone']); ?></p>
                        <?php endif; ?>
                        <?php if($invoice['address']): ?>
                            <p><?php echo htmlspecialchars($invoice['address']); ?></p>
                            <p><?php echo htmlspecialchars($invoice['city'] . ', ' . $invoice['state'] . ' ' . $invoice['zip_code']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-section">
                        <h3>Invoice Details:</h3>
                        <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($invoice['invoice_date'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime($invoice['due_date'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge status-<?php echo $invoice['status']; ?>">
                                <?php echo ucfirst(htmlspecialchars($invoice['status'])); ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Service</th>
                            <th style="text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['service_description'] ?: $invoice['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['service_name']); ?></td>
                            <td style="text-align: right;">$<?php echo number_format($invoice['amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="invoice-total">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($invoice['amount'], 2); ?></span>
                    </div>
                    <?php if($invoice['tax_amount'] > 0): ?>
                    <div class="total-row">
                        <span>Tax:</span>
                        <span>$<?php echo number_format($invoice['tax_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row final">
                        <span>Total:</span>
                        <span>$<?php echo number_format($invoice['total_amount'], 2); ?></span>
                    </div>
                </div>
                
                <?php if($invoice['notes']): ?>
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                    <h4>Notes:</h4>
                    <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="invoice-actions">
                <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
                <a href="invoices.php" class="btn btn-secondary">Back to Invoices</a>
                <button onclick="editInvoice(<?php echo htmlspecialchars(json_encode($invoice)); ?>)" class="btn btn-warning">Edit Invoice</button>
            </div>
        </div>
    <?php endif; ?>

    <script src="../assets/js/admin.js"></script>
</body>
</html> 