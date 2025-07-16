<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit();
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$reportType = $_GET['report_type'] ?? 'members';

try {
    $pdo = getDBConnection();
    
    if($reportType === 'members') {
        $stmt = $pdo->prepare("
            SELECT member_id, first_name, last_name, email, phone, membership_type, status, registration_date
            FROM members 
            WHERE DATE(registration_date) BETWEEN ? AND ?
            ORDER BY registration_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
        
        $filename = "members_report_{$startDate}_to_{$endDate}.csv";
        $headers = ['Member ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Membership Type', 'Status', 'Registration Date'];
        
    } elseif($reportType === 'invoices') {
        $stmt = $pdo->prepare("
            SELECT i.invoice_number, m.first_name, m.last_name, m.member_id, s.service_name, 
                   i.amount, i.tax_amount, i.total_amount, i.status, i.invoice_date, i.due_date
            FROM invoices i 
            JOIN members m ON i.member_id = m.id 
            JOIN services s ON i.service_id = s.id 
            WHERE DATE(i.invoice_date) BETWEEN ? AND ?
            ORDER BY i.invoice_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
        
        $filename = "invoices_report_{$startDate}_to_{$endDate}.csv";
        $headers = ['Invoice #', 'First Name', 'Last Name', 'Member ID', 'Service', 'Amount', 'Tax', 'Total', 'Status', 'Invoice Date', 'Due Date'];
        
    } elseif($reportType === 'services') {
        $stmt = $pdo->prepare("
            SELECT s.service_name, COUNT(i.id) as usage_count, 
                   SUM(i.total_amount) as total_revenue, AVG(i.total_amount) as avg_amount
            FROM services s
            LEFT JOIN invoices i ON s.id = i.service_id 
                AND DATE(i.invoice_date) BETWEEN ? AND ?
            GROUP BY s.id, s.service_name
            ORDER BY usage_count DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
        
        $filename = "services_report_{$startDate}_to_{$endDate}.csv";
        $headers = ['Service Name', 'Usage Count', 'Total Revenue', 'Average Amount'];
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} catch(PDOException $e) {
    header('Content-Type: text/plain');
    echo "Export failed: " . $e->getMessage();
}
?> 