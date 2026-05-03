# JK Motorparts - Smart Solutions System

A comprehensive web-based system integrating Point-of-Sale (POS), Roadside Assistance (RSA) Ticketing, and Customer Rewards Program for JK Motorparts.

## 🚀 Features

### 1. Point-of-Sale (POS) System
- Product management (add, edit, delete)
- Inventory tracking with automatic stock deduction
- Barcode and search functionality
- Cash and GCash payment methods
- Real-time cart management
- Transaction receipts
- Daily, weekly, and monthly sales reports

### 2. Roadside Assistance (RSA) Module
- Customer request submission
- Unique ticket number generation
- Status tracking (Pending, Assigned, In Progress, Completed)
- Technician assignment and updates
- Real-time notification system
- Admin and technician dashboards

### 3. Customer Rewards Program
- Points earned from purchases (1 point per peso)
- Rewards catalog management
- Reward redemption system
- Points balance tracking
- Redemption history

### 4. User Roles
- **Admin**: Full system access, manage products, users, RSA requests, and rewards
- **Customer**: Purchase products, request RSA, redeem rewards, view transactions
- **Technician**: View assigned tickets, update status, add notes

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP (Procedural)
- **Database**: MySQL
- **Charts**: Chart.js
- **Icons**: Font Awesome

## 📋 Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- phpMyAdmin
- Web browser (Chrome, Firefox, Edge)

### Setup Instructions

1. **Clone or Download the Project**
   ```bash
   Place the project folder in: C:\xampp\htdocs\jk-motorparts
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema from `database/schema.sql`
   - The database `jk_motorparts` will be created with all tables and sample data

3. **Configuration**
   - Open `config/database.php`
   - Update database credentials if needed (default: root, no password)
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'jk_motorparts');
   ```

4. **Start XAMPP**
   - Start Apache and MySQL services from XAMPP Control Panel

5. **Access the System**
   - Open browser and navigate to: `http://localhost/jk-motorparts`

## 🔐 Default Login Credentials

### Admin
- **Email**: admin@jkmotorparts.com
- **Password**: admin123

### Technician
- **Email**: technician@jkmotorparts.com
- **Password**: admin123

### Customer
- Register a new account or use the registration page

## 📁 Project Structure

```
jk-motorparts/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/
│   ├── config.php
│   └── database.php
├── dashboard/
│   ├── index.php
│   ├── profile.php
│   ├── reports.php
│   ├── transactions.php
│   └── users.php
├── database/
│   └── schema.sql
├── includes/
│   ├── header.php
│   └── sidebar.php
├── pos/
│   ├── index.php
│   ├── checkout.php
│   ├── products.php
│   └── receipt.php
├── rsa/
│   ├── index.php
│   ├── request.php
│   ├── my_requests.php
│   └── technician.php
├── rewards/
│   ├── index.php
│   └── redeem.php
├── index.php
└── README.md
```

## 🔒 Security Features

- Password hashing using PHP `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`
- Session-based authentication
- Role-based access control (RBAC)
- Input validation and sanitization
- Activity logging

## 📊 Database Schema

### Main Tables
- `users` - User accounts and authentication
- `products` - Product inventory
- `transactions` - Sales transactions
- `transaction_items` - Transaction line items
- `rewards_catalog` - Available rewards
- `rewards_redemptions` - Reward redemption records
- `rsa_requests` - Roadside assistance requests
- `activity_logs` - System activity logs

## 🎯 Key Functionalities

### Admin Features
- Manage products and inventory
- Process POS transactions
- Manage RSA requests and assign technicians
- Manage rewards catalog
- View comprehensive reports and analytics
- Manage users and roles

### Customer Features
- Register and manage profile
- View products (through POS)
- Request roadside assistance
- Track RSA request status
- View reward points and redeem rewards
- View transaction history

### Technician Features
- View assigned RSA tickets
- Update ticket status
- Add technician notes
- Mark requests as completed

## 📈 Reports and Analytics

- Daily sales trends (Chart.js)
- RSA request status distribution
- Top-selling products
- Points earned and redeemed statistics
- Transaction history with date filters

## 🚀 Deployment

### For Production (Hostinger/GoDaddy)

1. **Upload Files**
   - Upload all project files to your web hosting via FTP

2. **Database Setup**
   - Create MySQL database in hosting control panel
   - Import `database/schema.sql`
   - Update `config/database.php` with production credentials

3. **Configuration**
   - Update `config/config.php` with your domain URL
   - Set proper file permissions (644 for files, 755 for directories)

4. **Security**
   - Change default admin password
   - Enable HTTPS/SSL
   - Set up daily database backups
   - Implement additional security measures

## 🧪 Testing

- Test all user roles (Admin, Customer, Technician)
- Test POS transactions with various payment methods
- Test RSA request flow from creation to completion
- Test reward redemption system
- Test inventory management
- Verify reports and charts

## 📝 Notes

- Default password for all users is hashed using PHP `password_hash()`
- All transactions are logged in `activity_logs` table
- Points are automatically calculated (1 point per peso spent)
- Stock is automatically deducted after successful transactions
- RSA tickets are auto-generated with unique ticket numbers

## 🤝 Support

For issues or questions, please contact the development team.

## 📄 License

This project is developed for JK Motorparts. All rights reserved.

---

**Developed with ❤️ for JK Motorparts**

