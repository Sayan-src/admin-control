# Billing Software - Admin Panel

A comprehensive billing software with admin panel for managing members, invoices, and generating reports. Built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### 🔐 Secure Admin Authentication
- Password-protected admin login
- Session-based authentication
- Secure password hashing
- Admin role management

### 👥 Member Management
- Add, edit, and delete member profiles
- Complete member information storage
- Membership type classification (Basic, Premium, Enterprise)
- Member status tracking (Active, Inactive, Suspended)
- Unique member ID generation

### 📄 Invoice Management
- Create and manage invoices
- Automatic invoice number generation
- Tax calculation support
- Multiple payment statuses (Pending, Paid, Overdue, Cancelled)
- Professional invoice printing

### 📊 Advanced Filtering & Reports
- Date-based filtering for member registrations
- Calendar-based date range selection
- Real-time search functionality
- Comprehensive analytics and reporting
- Export capabilities for data analysis

### 🎨 Modern User Interface
- Responsive design for all devices
- Modern gradient-based styling
- Interactive modals and forms
- Professional dashboard with statistics
- Chart.js integration for data visualization

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

### 1. Database Setup
```sql
-- Import the database schema
mysql -u root -p < database.sql
```

### 2. Configuration
Edit `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'billing_software');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Web Server Setup
- Place all files in your web server directory
- Ensure PHP has write permissions for session handling
- Configure your web server to serve PHP files

### 4. Create Admin User

**Option A: Use Setup Script (Recommended)**
1. Visit `http://your-domain.com/setup_admin.php`
2. Fill in the admin details
3. The script will automatically disable itself after creation

**Option B: Manual Database Creation**
```sql
-- Generate password hash using PHP
<?php echo password_hash('your_secure_password', PASSWORD_DEFAULT); ?>

-- Insert admin user
INSERT INTO admin_users (username, password, email, full_name, role) VALUES 
('your_admin_username', '$2y$10$YOUR_HASHED_PASSWORD', 'admin@yourcompany.com', 'Your Name', 'super_admin');
```

**Security Best Practices:**
- Use a strong password (minimum 8 characters)
- Include uppercase, lowercase, numbers, and symbols
- Never share admin credentials
- Change password regularly

## File Structure

```
billing-software/
├── admin/
│   ├── dashboard.php          # Main admin dashboard
│   ├── members.php           # Member management
│   ├── invoices.php          # Invoice management
│   ├── reports.php           # Analytics and reports
│   └── view_invoice.php      # Invoice viewing/printing
├── auth/
│   ├── login.php             # Admin login
│   └── logout.php            # Logout functionality
├── assets/
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   └── js/
│       └── admin.js          # Admin JavaScript functions
├── config/
│   └── database.php          # Database configuration
├── database.sql              # Database schema
├── index.php                 # Main entry point
└── README.md                 # This file
```

## Usage

### Admin Dashboard
- View system statistics
- Quick access to all features
- Recent activity overview

### Member Management
1. **Add Member:** Click "Add New Member" button
2. **Edit Member:** Click "Edit" button on any member row
3. **Delete Member:** Click "Delete" button (with confirmation)
4. **Filter Members:** Use date range, status, and search filters

### Invoice Management
1. **Create Invoice:** Click "Create Invoice" button
2. **Select Member:** Choose from existing members
3. **Select Service:** Choose from available services
4. **Set Amount:** Enter amount and tax (auto-calculated)
5. **Set Dates:** Invoice date and due date
6. **View/Print:** Click "View" to see and print invoice

### Reports & Analytics
- **Member Registrations:** View registration trends
- **Invoice Analytics:** Revenue and payment statistics
- **Service Usage:** Service popularity and revenue
- **Date Filtering:** Filter by any date range
- **Export Data:** Export reports for external analysis

## Security Features

- **Password Hashing:** All passwords are securely hashed using PHP's password_hash()
- **SQL Injection Protection:** Prepared statements for all database queries
- **XSS Protection:** All output is properly escaped
- **Session Security:** Secure session handling
- **Input Validation:** Server-side validation for all forms

## Customization

### Adding New Services
1. Insert new service in the `services` table
2. Services will automatically appear in invoice creation

### Modifying Invoice Templates
- Edit `admin/view_invoice.php` for invoice layout
- Modify CSS in `assets/css/style.css` for styling

### Adding New Report Types
1. Add new report logic in `admin/reports.php`
2. Create corresponding chart configurations
3. Add export functionality if needed

## Troubleshooting

### Common Issues

**Database Connection Error:**
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check database exists and is accessible

**Login Issues:**
- Verify admin user exists in database
- Check PHP session configuration
- Ensure cookies are enabled

**Permission Errors:**
- Ensure web server has read/write permissions
- Check PHP file permissions
- Verify session directory permissions

### Performance Optimization

1. **Database Indexing:**
```sql
-- Add indexes for better performance
CREATE INDEX idx_members_registration_date ON members(registration_date);
CREATE INDEX idx_invoices_date ON invoices(invoice_date);
CREATE INDEX idx_invoices_status ON invoices(status);
```

2. **Caching:**
- Consider implementing Redis/Memcached for session storage
- Add query result caching for reports

3. **File Optimization:**
- Minify CSS and JavaScript files
- Enable GZIP compression
- Use CDN for external libraries

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support and questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Ensure all requirements are met

## Changelog

### Version 1.0.0
- Initial release
- Complete admin panel functionality
- Member and invoice management
- Advanced filtering and reporting
- Professional invoice generation
- Modern responsive design 