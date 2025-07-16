<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';

// Handle invoice actions
if(isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if($action === 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Invoice deleted successfully';
        } catch(PDOException $e) {
            $error = 'Error deleting invoice: ' . $e->getMessage();
        }
    }
}

// Handle invoice form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = getDBConnection();
        
        if($action === 'create') {
            $invoiceNumber = generateInvoiceNumber();
            $amount = floatval($_POST['amount']);
            $taxAmount = floatval($_POST['tax_amount'] ?? 0);
            $totalAmount = $amount + $taxAmount;
            
            $stmt = $pdo->prepare("
                INSERT INTO invoices (invoice_number, member_id, service_id, amount, tax_amount, total_amount, invoice_date, due_date, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $invoiceNumber,
                $_POST['member_id'],
                $_POST['service_id'],
                $amount,
                $taxAmount,
                $totalAmount,
                $_POST['invoice_date'],
                $_POST['due_date'],
                $_POST['status'],
                $_POST['notes']
            ]);
            $message = 'Invoice created successfully';
            
        } elseif($action === 'update') {
            $amount = floatval($_POST['amount']);
            $taxAmount = floatval($_POST['tax_amount'] ?? 0);
            $totalAmount = $amount + $taxAmount;
            
            $stmt = $pdo->prepare("
                UPDATE invoices SET 
                member_id = ?, service_id = ?, amount = ?, tax_amount = ?, total_amount = ?,
                invoice_date = ?, due_date = ?, status = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['member_id'],
                $_POST['service_id'],
                $amount,
                $taxAmount,
                $totalAmount,
                $_POST['invoice_date'],
                $_POST['due_date'],
                $_POST['status'],
                $_POST['notes'],
                $_POST['id']
            ]);
            $message = 'Invoice updated successfully';
        }
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get filter parameters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$status = $_GET['status'] ?? '';
$memberId = $_GET['member_id'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];

if($startDate && $endDate) {
    $whereConditions[] = "DATE(i.invoice_date) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

if($status) {
    $whereConditions[] = "i.status = ?";
    $params[] = $status;
}

if($memberId) {
    $whereConditions[] = "i.member_id = ?";
    $params[] = $memberId;
}

if($search) {
    $whereConditions[] = "(i.invoice_number LIKE ? OR m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get invoices with filters
try {
    $pdo = getDBConnection();
    $query = "
        SELECT i.*, m.first_name, m.last_name, m.email, m.member_id as member_code, s.service_name 
        FROM invoices i 
        JOIN members m ON i.member_id = m.id 
        JOIN services s ON i.service_id = s.id 
        $whereClause 
        ORDER BY i.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();
    
    // Get filtered count
    $countQuery = "
        SELECT COUNT(*) as count 
        FROM invoices i 
        JOIN members m ON i.member_id = m.id 
        JOIN services s ON i.service_id = s.id 
        $whereClause
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $filteredCount = $countStmt->fetch()['count'];
    
    // Get all members for dropdown
    $membersStmt = $pdo->query("SELECT id, member_id, first_name, last_name, email FROM members ORDER BY first_name");
    $members = $membersStmt->fetchAll();
    
    // Get all services for dropdown
    $servicesStmt = $pdo->query("SELECT id, service_name, price FROM services WHERE status = 'active' ORDER BY service_name");
    $services = $servicesStmt->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $invoices = [];
    $filteredCount = 0;
    $members = [];
    $services = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices Management - Billing Software</title>
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
        <div class="page-header">
            <h2>Invoices Management</h2>
            <div class="page-actions">
                <button onclick="openModal('createInvoiceModal')" class="btn btn-primary">Create Invoice</button>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

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
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="member_id">Member</label>
                    <select id="member_id" name="member_id">
                        <option value="">All Members</option>
                        <?php foreach($members as $member): ?>
                        <option value="<?php echo $member['id']; ?>" <?php echo $memberId == $member['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($member['member_id'] . ' - ' . $member['first_name'] . ' ' . $member['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" placeholder="Search by invoice #, member name, or email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="invoices.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <p>Showing <?php echo $filteredCount; ?> invoice(s)</p>
        </div>

        <!-- Invoices Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>Invoices List</h3>
                <div class="table-actions">
                    <button onclick="exportInvoices()" class="btn btn-secondary btn-sm">Export</button>
                </div>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Member</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Tax</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?><br>
                            <small><?php echo htmlspecialchars($invoice['member_code']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($invoice['service_name']); ?></td>
                        <td>$<?php echo number_format($invoice['amount'], 2); ?></td>
                        <td>$<?php echo number_format($invoice['tax_amount'], 2); ?></td>
                        <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $invoice['status']; ?>">
                                <?php echo ucfirst(htmlspecialchars($invoice['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($invoice['due_date'])); ?></td>
                        <td>
                            <button onclick="viewInvoice(<?php echo $invoice['id']; ?>)" class="btn btn-primary btn-sm">View</button>
                            <button onclick="editInvoice(<?php echo htmlspecialchars(json_encode($invoice)); ?>)" class="btn btn-warning btn-sm">Edit</button>
                            <button onclick="deleteInvoice(<?php echo $invoice['id']; ?>)" class="btn btn-danger btn-sm">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Invoice Modal -->
    <div id="createInvoiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Create New Invoice</h3>
                <span class="close" onclick="closeModal('createInvoiceModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="member_id">Member</label>
                        <select id="member_id" name="member_id" required onchange="updateServicePrice()">
                            <option value="">Select Member</option>
                            <?php foreach($members as $member): ?>
                            <option value="<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['member_id'] . ' - ' . $member['first_name'] . ' ' . $member['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="service_id">Service</label>
                        <select id="service_id" name="service_id" required onchange="updateServicePrice()">
                            <option value="">Select Service</option>
                            <?php foreach($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                <?php echo htmlspecialchars($service['service_name'] . ' - $' . $service['price']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" id="amount" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="tax_amount">Tax Amount</label>
                        <input type="number" id="tax_amount" name="tax_amount" step="0.01" value="0.00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="invoice_date">Invoice Date</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <input type="date" id="due_date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Invoice</button>
                    <button type="button" onclick="closeModal('createInvoiceModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Invoice Modal -->
    <div id="editInvoiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Invoice</h3>
                <span class="close" onclick="closeModal('editInvoiceModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_member_id">Member</label>
                        <select id="edit_member_id" name="member_id" required>
                            <?php foreach($members as $member): ?>
                            <option value="<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['member_id'] . ' - ' . $member['first_name'] . ' ' . $member['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_service_id">Service</label>
                        <select id="edit_service_id" name="service_id" required>
                            <?php foreach($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                <?php echo htmlspecialchars($service['service_name'] . ' - $' . $service['price']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_amount">Amount</label>
                        <input type="number" id="edit_amount" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_tax_amount">Tax Amount</label>
                        <input type="number" id="edit_tax_amount" name="tax_amount" step="0.01" value="0.00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_invoice_date">Invoice Date</label>
                        <input type="date" id="edit_invoice_date" name="invoice_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_due_date">Due Date</label>
                        <input type="date" id="edit_due_date" name="due_date" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_notes">Notes</label>
                    <textarea id="edit_notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Invoice</button>
                    <button type="button" onclick="closeModal('editInvoiceModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html> 