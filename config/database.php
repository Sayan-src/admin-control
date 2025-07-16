<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'billing_software');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Generate unique invoice number
function generateInvoiceNumber() {
    $prefix = 'INV';
    $year = date('Y');
    $month = date('m');
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . $year . $month . $random;
}

// Generate unique member ID
function generateMemberId() {
    $prefix = 'MEM';
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . $random;
}
?> 