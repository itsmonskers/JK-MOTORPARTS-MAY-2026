<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jk_motorparts');

// Initialize database tables
function initializeDatabase($conn) {
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    $tablesExist = $result->num_rows > 0;
    
    if (!$tablesExist) {
        // Temporarily disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    }
    
    // Create all tables
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'customer', 'technician') DEFAULT 'customer',
        contact VARCHAR(20),
        address TEXT,
        points INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        stock INT DEFAULT 0,
        barcode VARCHAR(50) UNIQUE,
        category VARCHAR(50),
        is_archived BOOLEAN DEFAULT FALSE,
        archived_at TIMESTAMP NULL,
        date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Ensure archive columns exist for products
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_archived BOOLEAN DEFAULT FALSE");
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL");
    $conn->query("UPDATE products SET is_archived = 0 WHERE is_archived IS NULL");
    
    $conn->query("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        transaction_number VARCHAR(50) UNIQUE NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        payment_method ENUM('cash', 'gcash') DEFAULT 'cash',
        points_earned INT DEFAULT 0,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    $conn->query("CREATE TABLE IF NOT EXISTS transaction_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        subtotal DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    
    $conn->query("CREATE TABLE IF NOT EXISTS rewards_catalog (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reward_name VARCHAR(200) NOT NULL,
        description TEXT,
        required_points INT NOT NULL,
        discount_percentage DECIMAL(5, 2) DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        is_archived BOOLEAN DEFAULT FALSE,
        archived_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Ensure archive columns exist for rewards
    $conn->query("ALTER TABLE rewards_catalog ADD COLUMN IF NOT EXISTS is_archived BOOLEAN DEFAULT FALSE");
    $conn->query("ALTER TABLE rewards_catalog ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL");
    $conn->query("UPDATE rewards_catalog SET is_archived = 0 WHERE is_archived IS NULL");
    
    $conn->query("CREATE TABLE IF NOT EXISTS rewards_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reward_id INT NOT NULL,
        points_used INT NOT NULL,
        status ENUM('pending', 'approved', 'redeemed', 'cancelled') DEFAULT 'pending',
        redemption_code VARCHAR(50) UNIQUE,
        date_redeemed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reward_id) REFERENCES rewards_catalog(id) ON DELETE CASCADE
    )");
    
    // Add redemption_code column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM rewards_redemptions LIKE 'redemption_code'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE rewards_redemptions ADD COLUMN redemption_code VARCHAR(50) UNIQUE");
    }
    
    $conn->query("CREATE TABLE IF NOT EXISTS rsa_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ticket_number VARCHAR(50) UNIQUE NOT NULL,
        issue_type VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        location TEXT NOT NULL,
        contact_number VARCHAR(20) NOT NULL,
        status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        assigned_to INT,
        admin_notes TEXT,
        technician_notes TEXT,
        date_requested TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_completed TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    $conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Insert sample products
    $products = [
        ['Motor Oil 1L', 'High-quality motor oil for all vehicle types', 350.00, 100, 'PROD001', 'Lubricants'],
        ['Brake Pad Set', 'Premium brake pad set front and rear', 1200.00, 50, 'PROD002', 'Brake System'],
        ['Air Filter', 'Standard air filter replacement', 450.00, 75, 'PROD003', 'Filters'],
        ['Spark Plug', 'Iridium spark plug set of 4', 800.00, 60, 'PROD004', 'Ignition'],
        ['Battery 12V', 'Car battery 12V 60Ah', 3500.00, 30, 'PROD005', 'Electrical']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO products (name, description, price, stock, barcode, category) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($products as $product) {
        $stmt->bind_param("ssdiss", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5]);
        $stmt->execute();
    }
    $stmt->close();
    
    // Handle rewards - only add if needed and enforce 10 reward limit
    handleRewards($conn);
    
    if (!$tablesExist) {
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    // Always ensure default users exist with correct passwords
    ensureDefaultUsers($conn);
}

// Handle rewards to ensure we have exactly 10
function handleRewards($conn) {
    // Sample rewards to add if needed
    $sampleRewards = [
        ['5% Discount', 'Get 5% discount on your next purchase', 100, 5.00],
        ['10% Discount', 'Get 10% discount on your next purchase', 200, 10.00],
        ['Free Oil Change', 'Free motor oil change service', 500, 0],
        ['15% Discount', 'Get 15% discount on your next purchase', 300, 15.00],
        ['Free Towing Service', 'Free roadside towing service', 1000, 0]
    ];
    
    // Count current active rewards
    $result = $conn->query("SELECT COUNT(*) as count FROM rewards_catalog WHERE is_archived = 0");
    $row = $result->fetch_assoc();
    $currentCount = $row['count'];
    
    // If no rewards exist, add sample ones
    if ($currentCount == 0) {
        $stmt = $conn->prepare("INSERT INTO rewards_catalog (reward_name, description, required_points, discount_percentage, is_active) VALUES (?, ?, ?, ?, TRUE)");
        foreach ($sampleRewards as $reward) {
            $stmt->bind_param("ssid", $reward[0], $reward[1], $reward[2], $reward[3]);
            $stmt->execute();
        }
        $stmt->close();
        return;
    }
    
    // If we have more than 10 rewards, archive the extra ones
    if ($currentCount > 10) {
        // Archive all rewards except the 10 most recent
        $conn->query("UPDATE rewards_catalog SET is_archived = 1, archived_at = NOW() WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM rewards_catalog 
                WHERE is_archived = 0 
                ORDER BY id DESC 
                LIMIT 10
            ) AS temp
        )");
    }
    
    // If we have less than 10 rewards, add missing sample ones
    if ($currentCount < 10) {
        // Check which sample rewards are missing
        $existingRewards = [];
        $result = $conn->query("SELECT reward_name FROM rewards_catalog WHERE is_archived = 0");
        while ($row = $result->fetch_assoc()) {
            $existingRewards[] = $row['reward_name'];
        }
        
        // Add missing sample rewards
        $stmt = $conn->prepare("INSERT INTO rewards_catalog (reward_name, description, required_points, discount_percentage, is_active) VALUES (?, ?, ?, ?, TRUE)");
        foreach ($sampleRewards as $reward) {
            if (!in_array($reward[0], $existingRewards)) {
                $stmt->bind_param("ssid", $reward[0], $reward[1], $reward[2], $reward[3]);
                $stmt->execute();
            }
        }
        $stmt->close();
    }
}

// Ensure default admin and technician users exist with correct passwords
function ensureDefaultUsers($conn) {
    // Insert or update default admin user (password: admin123)
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $email = 'admin@jkmotorparts.com';
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing admin
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, name = ?, role = ?, contact = ? WHERE email = ?");
        $name = 'Admin User';
        $role = 'admin';
        $contact = '09123456789';
        $updateStmt->bind_param("sssss", $adminPassword, $name, $role, $contact, $email);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert new admin
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, contact) VALUES (?, ?, ?, ?, ?)");
        $name = 'Admin User';
        $role = 'admin';
        $contact = '09123456789';
        $stmt->bind_param("sssss", $name, $email, $adminPassword, $role, $contact);
        $stmt->execute();
        $stmt->close();
    }
    $checkStmt->close();
    
    // Insert or update default technician (password: admin123)
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $email = 'technician@jkmotorparts.com';
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing technician
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, name = ?, role = ?, contact = ? WHERE email = ?");
        $name = 'John Technician';
        $role = 'technician';
        $contact = '09123456790';
        $updateStmt->bind_param("sssss", $adminPassword, $name, $role, $contact, $email);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert new technician
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, contact) VALUES (?, ?, ?, ?, ?)");
        $name = 'John Technician';
        $role = 'technician';
        $contact = '09123456790';
        $stmt->bind_param("sssss", $name, $email, $adminPassword, $role, $contact);
        $stmt->execute();
        $stmt->close();
    }
    $checkStmt->close();
}

// Create database connection
function getDBConnection() {
    // First, connect without selecting a database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Check if database exists, if not create it
    $result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($result->num_rows == 0) {
        // Create the database
        $createDbQuery = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
        if ($conn->query($createDbQuery)) {
            // Database created successfully
        } else {
            die("Error creating database: " . $conn->error);
        }
    }
    
    // Now select the database
    $conn->select_db(DB_NAME);
    
    // Initialize tables if they don't exist
    initializeDatabase($conn);
    
    return $conn;
}

// Close database connection
function closeDBConnection($conn) {
    $conn->close();
}
?>