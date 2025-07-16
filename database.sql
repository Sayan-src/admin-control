-- Billing Software Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS billing_software;
USE billing_software;

-- Admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Members table
CREATE TABLE members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    zip_code VARCHAR(10),
    country VARCHAR(50) DEFAULT 'USA',
    membership_type ENUM('basic', 'premium', 'enterprise') DEFAULT 'basic',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_months INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices table
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(20) UNIQUE NOT NULL,
    member_id INT NOT NULL,
    service_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('credit_card', 'bank_transfer', 'cash', 'check') NULL,
    payment_date TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Insert sample services
INSERT INTO services (service_name, description, price, duration_months) VALUES 
('Basic Membership', 'Access to basic features and support', 29.99, 1),
('Premium Membership', 'Access to premium features and priority support', 59.99, 1),
('Enterprise Membership', 'Full access to all features and dedicated support', 99.99, 1);

-- Insert sample members
INSERT INTO members (member_id, first_name, last_name, email, phone, address, city, state, zip_code, membership_type) VALUES 
('MEM001', 'John', 'Doe', 'john.doe@email.com', '+1-555-0101', '123 Main St', 'New York', 'NY', '10001', 'premium'),
('MEM002', 'Jane', 'Smith', 'jane.smith@email.com', '+1-555-0102', '456 Oak Ave', 'Los Angeles', 'CA', '90210', 'basic'),
('MEM003', 'Mike', 'Johnson', 'mike.johnson@email.com', '+1-555-0103', '789 Pine Rd', 'Chicago', 'IL', '60601', 'enterprise'),
('MEM004', 'Sarah', 'Williams', 'sarah.williams@email.com', '+1-555-0104', '321 Elm St', 'Houston', 'TX', '77001', 'premium'),
('MEM005', 'David', 'Brown', 'david.brown@email.com', '+1-555-0105', '654 Maple Dr', 'Phoenix', 'AZ', '85001', 'basic');

-- IMPORTANT: Create admin user manually after installation
-- Use the following SQL command to create your first admin user:
-- INSERT INTO admin_users (username, password, email, full_name, role) VALUES 
-- ('your_admin_username', '$2y$10$YOUR_HASHED_PASSWORD', 'admin@yourcompany.com', 'Your Name', 'super_admin');
-- 
-- To generate a password hash, use PHP's password_hash() function:
-- <?php echo password_hash('your_secure_password', PASSWORD_DEFAULT); ?> 