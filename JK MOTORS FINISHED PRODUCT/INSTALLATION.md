# Installation Guide - JK Motorparts System

## Prerequisites

- **XAMPP** (or WAMP/MAMP) with PHP 7.4+ and MySQL 5.7+
- **Web Browser** (Chrome, Firefox, Edge, Safari)
- **Text Editor** (VS Code, Sublime Text, etc.)

## Step-by-Step Installation

### Step 1: Install XAMPP

1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to `C:\xampp` (Windows) or `/Applications/XAMPP` (Mac)
3. Start **Apache** and **MySQL** services from XAMPP Control Panel

### Step 2: Setup Project

1. Copy the entire project folder to:
   - **Windows**: `C:\xampp\htdocs\jk-motorparts`
   - **Mac/Linux**: `/Applications/XAMPP/htdocs/jk-motorparts`

2. Ensure the folder structure is:
   ```
   jk-motorparts/
   ├── assets/
   ├── auth/
   ├── config/
   ├── dashboard/
   ├── database/
   ├── includes/
   ├── pos/
   ├── rsa/
   ├── rewards/
   └── index.php
   ```

### Step 3: Create Database

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click on "New" to create a database
3. Database name: `jk_motorparts`
4. Collation: `utf8mb4_general_ci`
5. Click "Create"

### Step 4: Import Database Schema

1. In phpMyAdmin, select the `jk_motorparts` database
2. Click on "Import" tab
3. Click "Choose File" and select `database/schema.sql`
4. Click "Go" to import
5. Wait for success message

### Step 5: Configure Database Connection

1. Open `config/database.php`
2. Verify database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Leave empty if no password
   define('DB_NAME', 'jk_motorparts');
   ```

3. If you have a MySQL password, update `DB_PASS`:
   ```php
   define('DB_PASS', 'your_password');
   ```

### Step 6: Configure Site URL (Optional)

1. Open `config/config.php`
2. Update `SITE_URL` if needed:
   ```php
   define('SITE_URL', 'http://localhost/jk-motorparts');
   ```

### Step 7: Test Installation

1. Open your web browser
2. Navigate to: `http://localhost/jk-motorparts`
3. You should be redirected to the login page

### Step 8: Login

**Admin Account:**
- Email: `admin@jkmotorparts.com`
- Password: `admin123`

**Technician Account:**
- Email: `technician@jkmotorparts.com`
- Password: `admin123`

**Customer Account:**
- Register a new account from the registration page

## Troubleshooting

### Issue: "Connection failed" error

**Solution:**
1. Check if MySQL is running in XAMPP Control Panel
2. Verify database credentials in `config/database.php`
3. Ensure database `jk_motorparts` exists

### Issue: "Page not found" or 404 error

**Solution:**
1. Verify project folder is in `htdocs` directory
2. Check folder name is correct: `jk-motorparts`
3. Ensure Apache is running

### Issue: "Session error" or login not working

**Solution:**
1. Check PHP session directory permissions
2. Clear browser cookies and cache
3. Restart Apache server

### Issue: "Permission denied" errors

**Solution:**
1. Check file permissions (should be 644 for files, 755 for directories)
2. Ensure web server has read/write access

### Issue: Database import fails

**Solution:**
1. Check SQL file encoding (should be UTF-8)
2. Increase MySQL upload size limit in php.ini
3. Import SQL file section by section if too large

## Post-Installation

### Security Recommendations

1. **Change Default Passwords**
   - Change admin password immediately
   - Use strong passwords (8+ characters, mixed case, numbers, symbols)

2. **Update Database Credentials**
   - Use strong database password
   - Restrict database user privileges

3. **Enable HTTPS** (for production)
   - Install SSL certificate
   - Update `SITE_URL` to use `https://`

4. **File Permissions**
   - Set proper file permissions
   - Restrict access to config files

### Production Deployment

For hosting on Hostinger/GoDaddy:

1. **Upload Files**
   - Upload all files via FTP to `public_html` or `www` directory
   - Maintain folder structure

2. **Database Setup**
   - Create database in hosting control panel
   - Import `database/schema.sql`
   - Update `config/database.php` with production credentials

3. **Configuration**
   - Update `SITE_URL` in `config/config.php`
   - Set timezone in `config/config.php`
   - Configure email settings if needed

4. **Security**
   - Enable .htaccess protection
   - Set up daily database backups
   - Implement firewall rules
   - Monitor activity logs

## Support

For installation issues or questions:
- Check the README.md file
- Review error logs in `error_log` file
- Contact system administrator

---

**Installation completed successfully!** 🎉

Now you can start using the JK Motorparts system.

