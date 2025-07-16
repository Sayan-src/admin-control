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

// Handle member actions
if(isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if($action === 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Member deleted successfully';
        } catch(PDOException $e) {
            $error = 'Error deleting member: ' . $e->getMessage();
        }
    }
}

// Handle member form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = getDBConnection();
        
        if($action === 'add') {
            $memberId = generateMemberId();
            $stmt = $pdo->prepare("
                INSERT INTO members (member_id, first_name, last_name, email, phone, address, city, state, zip_code, membership_type, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $memberId,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['city'],
                $_POST['state'],
                $_POST['zip_code'],
                $_POST['membership_type'],
                $_POST['status']
            ]);
            $message = 'Member added successfully';
            
        } elseif($action === 'edit') {
            $stmt = $pdo->prepare("
                UPDATE members SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, 
                city = ?, state = ?, zip_code = ?, membership_type = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['city'],
                $_POST['state'],
                $_POST['zip_code'],
                $_POST['membership_type'],
                $_POST['status'],
                $_POST['id']
            ]);
            $message = 'Member updated successfully';
        }
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get filter parameters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$status = $_GET['status'] ?? '';
$membershipType = $_GET['membership_type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];

if($startDate && $endDate) {
    $whereConditions[] = "DATE(registration_date) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

if($status) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if($membershipType) {
    $whereConditions[] = "membership_type = ?";
    $params[] = $membershipType;
}

if($search) {
    $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR member_id LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get members with filters
try {
    $pdo = getDBConnection();
    $query = "SELECT * FROM members $whereClause ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
    
    // Get filtered count
    $countQuery = "SELECT COUNT(*) as count FROM members $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $filteredCount = $countStmt->fetch()['count'];
    
} catch(PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $members = [];
    $filteredCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - Billing Software</title>
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
            <h2>Members Management</h2>
            <div class="page-actions">
                <button onclick="openModal('addMemberModal')" class="btn btn-primary">Add New Member</button>
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
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="membership_type">Membership Type</label>
                    <select id="membership_type" name="membership_type">
                        <option value="">All Types</option>
                        <option value="basic" <?php echo $membershipType === 'basic' ? 'selected' : ''; ?>>Basic</option>
                        <option value="premium" <?php echo $membershipType === 'premium' ? 'selected' : ''; ?>>Premium</option>
                        <option value="enterprise" <?php echo $membershipType === 'enterprise' ? 'selected' : ''; ?>>Enterprise</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" placeholder="Search by name, email, or ID" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="members.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <p>Showing <?php echo $filteredCount; ?> member(s)</p>
        </div>

        <!-- Members Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>Members List</h3>
                <div class="table-actions">
                    <button onclick="exportMembers()" class="btn btn-secondary btn-sm">Export</button>
                </div>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Membership</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($members as $member): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                        <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                        <td><?php echo htmlspecialchars($member['phone']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($member['membership_type'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $member['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                <?php echo ucfirst(htmlspecialchars($member['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($member['registration_date'])); ?></td>
                        <td>
                            <button onclick="editMember(<?php echo htmlspecialchars(json_encode($member)); ?>)" class="btn btn-warning btn-sm">Edit</button>
                            <button onclick="deleteMember(<?php echo $member['id']; ?>)" class="btn btn-danger btn-sm">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div id="addMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Member</h3>
                <span class="close" onclick="closeModal('addMemberModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city">
                    </div>
                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state">
                    </div>
                    <div class="form-group">
                        <label for="zip_code">Zip Code</label>
                        <input type="text" id="zip_code" name="zip_code">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="membership_type">Membership Type</label>
                        <select id="membership_type" name="membership_type" required>
                            <option value="basic">Basic</option>
                            <option value="premium">Premium</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Member</button>
                    <button type="button" onclick="closeModal('addMemberModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div id="editMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Member</h3>
                <span class="close" onclick="closeModal('editMemberModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_first_name">First Name</label>
                        <input type="text" id="edit_first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_last_name">Last Name</label>
                        <input type="text" id="edit_last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="tel" id="edit_phone" name="phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <textarea id="edit_address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_city">City</label>
                        <input type="text" id="edit_city" name="city">
                    </div>
                    <div class="form-group">
                        <label for="edit_state">State</label>
                        <input type="text" id="edit_state" name="state">
                    </div>
                    <div class="form-group">
                        <label for="edit_zip_code">Zip Code</label>
                        <input type="text" id="edit_zip_code" name="zip_code">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_membership_type">Membership Type</label>
                        <select id="edit_membership_type" name="membership_type" required>
                            <option value="basic">Basic</option>
                            <option value="premium">Premium</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Member</button>
                    <button type="button" onclick="closeModal('editMemberModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html> 