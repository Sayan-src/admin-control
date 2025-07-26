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
                INSERT INTO members (member_id, first_name, last_name, email, phone, address, country, city, membership_type, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $memberId,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['country'],
                $_POST['city'],
                // $_POST['zip_code'],
                $_POST['membership_type'],
                $_POST['status']
            ]);
            $message = 'Member added successfully';
            
        } elseif($action === 'edit') {
            $stmt = $pdo->prepare("
                UPDATE members SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, country = ?,
                city = ?,   membership_type = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['country'],
                $_POST['city'],
                // $_POST['zip_code'],
                $_POST['membership_type'],
                $_POST['status'],
                $_POST['id']
            ]);
            $message = 'Member updated successfully';
        }
    } catch(PDOException $e) {
        die('Database Error: ' . $e->getMessage());
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
                        <option value="life member" <?php echo $membershipType === 'life member' ? 'selected' : ''; ?>>Life Member</option>
                        <option value="executive member" <?php echo $membershipType === 'executive member' ? 'selected' : ''; ?>>Executive Member</option>
                        <option value="associate member" <?php echo $membershipType === 'associate member' ? 'selected' : ''; ?>>Associate Member</option>
                        <option value="student member" <?php echo $membershipType === 'student memberr' ? 'selected' : ''; ?>>Student Member</option>
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
    <!-- Add Member Modal - FIXED VERSION -->
<div id="addMemberModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New Member</h3>
            <span class="close" onclick="closeModal('addMemberModal')">&times;</span>
        </div>
        <form method="POST" id="addMemberForm">
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
                    <label for="country">Country</label>
                    <input list="countryList" id="country" name="country" required>
                    <datalist id="countryList">
  <option value="Afghanistan">
  <option value="Albania">
  <option value="Algeria">
  <option value="Andorra">
  <option value="Angola">
  <option value="Argentina">
  <option value="Armenia">
  <option value="Australia">
  <option value="Austria">
  <option value="Azerbaijan">
  <option value="Bahamas">
  <option value="Bahrain">
  <option value="Bangladesh">
  <option value="Barbados">
  <option value="Belarus">
  <option value="Belgium">
  <option value="Belize">
  <option value="Benin">
  <option value="Bhutan">
  <option value="Bolivia">
  <option value="Bosnia and Herzegovina">
  <option value="Botswana">
  <option value="Brazil">
  <option value="Brunei">
  <option value="Bulgaria">
  <option value="Burkina Faso">
  <option value="Burundi">
  <option value="Cambodia">
  <option value="Cameroon">
  <option value="Canada">
  <option value="Cape Verde">
  <option value="Central African Republic">
  <option value="Chad">
  <option value="Chile">
  <option value="China">
  <option value="Colombia">
  <option value="Comoros">
  <option value="Costa Rica">
  <option value="Croatia">
  <option value="Cuba">
  <option value="Cyprus">
  <option value="Czech Republic">
  <option value="Denmark">
  <option value="Djibouti">
  <option value="Dominica">
  <option value="Dominican Republic">
  <option value="Ecuador">
  <option value="Egypt">
  <option value="El Salvador">
  <option value="Equatorial Guinea">
  <option value="Eritrea">
  <option value="Estonia">
  <option value="Eswatini">
  <option value="Ethiopia">
  <option value="Fiji">
  <option value="Finland">
  <option value="France">
  <option value="Gabon">
  <option value="Gambia">
  <option value="Georgia">
  <option value="Germany">
  <option value="Ghana">
  <option value="Greece">
  <option value="Grenada">
  <option value="Guatemala">
  <option value="Guinea">
  <option value="Guyana">
  <option value="Haiti">
  <option value="Honduras">
  <option value="Hungary">
  <option value="Iceland">
  <option value="India">
  <option value="Indonesia">
  <option value="Iran">
  <option value="Iraq">
  <option value="Ireland">
  <option value="Israel">
  <option value="Italy">
  <option value="Jamaica">
  <option value="Japan">
  <option value="Jordan">
  <option value="Kazakhstan">
  <option value="Kenya">
  <option value="Kiribati">
  <option value="Kuwait">
  <option value="Kyrgyzstan">
  <option value="Laos">
  <option value="Latvia">
  <option value="Lebanon">
  <option value="Lesotho">
  <option value="Liberia">
  <option value="Libya">
  <option value="Liechtenstein">
  <option value="Lithuania">
  <option value="Luxembourg">
  <option value="Madagascar">
  <option value="Malawi">
  <option value="Malaysia">
  <option value="Maldives">
  <option value="Mali">
  <option value="Malta">
  <option value="Marshall Islands">
  <option value="Mauritania">
  <option value="Mauritius">
  <option value="Mexico">
  <option value="Micronesia">
  <option value="Moldova">
  <option value="Monaco">
  <option value="Mongolia">
  <option value="Montenegro">
  <option value="Morocco">
  <option value="Mozambique">
  <option value="Myanmar">
  <option value="Namibia">
  <option value="Nauru">
  <option value="Nepal">
  <option value="Netherlands">
  <option value="New Zealand">
  <option value="Nicaragua">
  <option value="Niger">
  <option value="Nigeria">
  <option value="North Korea">
  <option value="North Macedonia">
  <option value="Norway">
  <option value="Oman">
  <option value="Pakistan">
  <option value="Palau">
  <option value="Palestine">
  <option value="Panama">
  <option value="Papua New Guinea">
  <option value="Paraguay">
  <option value="Peru">
  <option value="Philippines">
  <option value="Poland">
  <option value="Portugal">
  <option value="Qatar">
  <option value="Romania">
  <option value="Russia">
  <option value="Rwanda">
  <option value="Saint Kitts and Nevis">
  <option value="Saint Lucia">
  <option value="Saint Vincent and the Grenadines">
  <option value="Samoa">
  <option value="San Marino">
  <option value="Sao Tome and Principe">
  <option value="Saudi Arabia">
  <option value="Senegal">
  <option value="Serbia">
  <option value="Seychelles">
  <option value="Sierra Leone">
  <option value="Singapore">
  <option value="Slovakia">
  <option value="Slovenia">
  <option value="Solomon Islands">
  <option value="Somalia">
  <option value="South Africa">
  <option value="South Korea">
  <option value="South Sudan">
  <option value="Spain">
  <option value="Sri Lanka">
  <option value="Sudan">
  <option value="Suriname">
  <option value="Sweden">
  <option value="Switzerland">
  <option value="Syria">
  <option value="Taiwan">
  <option value="Tajikistan">
  <option value="Tanzania">
  <option value="Thailand">
  <option value="Togo">
  <option value="Tonga">
  <option value="Trinidad and Tobago">
  <option value="Tunisia">
  <option value="Turkey">
  <option value="Turkmenistan">
  <option value="Tuvalu">
  <option value="Uganda">
  <option value="Ukraine">
  <option value="United Arab Emirates">
  <option value="United Kingdom">
  <option value="United States">
  <option value="Uruguay">
  <option value="Uzbekistan">
  <option value="Vanuatu">
  <option value="Vatican City">
  <option value="Venezuela">
  <option value="Vietnam">
  <option value="Yemen">
  <option value="Zambia">
  <option value="Zimbabwe">
</datalist>
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="membership_type">Membership Type</label>
                    <select id="membership_type" name="membership_type" required>
                        <option value="">-- Select Membership Type --</option>
                        <option value="life member">Life Member</option>
                        <option value="executive member">Executive Member</option>
                        <option value="associate member">Associate Member</option>
                        <option value="student member">Student Member</option>
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
                    <!-- <div class="form-group">
                        <label for="edit_zip_code">Zip Code</label>
                        <input type="text" id="edit_zip_code" name="zip_code">
                    </div> -->
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
