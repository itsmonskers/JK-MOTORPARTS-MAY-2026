<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// --- ABSOLUTE FILE PATH CONFIGURATION (CRITICAL FOR FILE UPLOADS/CHECKS) ---
// This dynamically sets the path to the main project directory 
// (e.g., 'C:/xampp/htdocs/JK MOTORS FINISHED PRODUCT/')
// The __DIR__ is the 'config' directory. '/../' navigates up one level to the project root.
define('ABSOLUTE_APP_ROOT', __DIR__ . '/../'); 

// Define the absolute directories for server-side file_exists() and move_uploaded_file()
// Use these constants when performing file/folder operations (mkdir, move_uploaded_file, file_exists)
define('CUSTOMER_UPLOAD_DIR', ABSOLUTE_APP_ROOT . 'uploads/customer/');
define('DIAGNOSTIC_UPLOAD_DIR', ABSOLUTE_APP_ROOT . 'uploads/diagnostic/');

// Define the relative URLs for browser display (used for <img> and <video> src)
// Use these constants in HTML <src> attributes
define('CUSTOMER_URL_PATH', '../uploads/customer/');
define('DIAGNOSTIC_URL_PATH', '../uploads/diagnostic/');
// --- END ABSOLUTE FILE PATH CONFIGURATION ---


// Site configuration
define('SITE_NAME', 'JK Motorparts');
define('SITE_URL', 'http://localhost/jk-motorparts');
define('POINTS_PER_PESO', 1); // 1 point per peso spent
define('TIMEZONE', 'Asia/Manila');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function isTechnician() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'technician';
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Determine correct path based on current directory
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        if ($current_dir === 'dashboard' || $current_dir === 'pos' || $current_dir === 'rsa' || $current_dir === 'rewards') {
            header('Location: ../auth/login.php');
        } else {
            header('Location: auth/login.php');
        }
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        if ($current_dir !== 'dashboard') {
            header('Location: ../dashboard/index.php');
        } else {
            header('Location: index.php');
        }
        exit();
    }
}

function requireCustomer() {
    requireLogin();
    if (!isCustomer()) {
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        if ($current_dir !== 'dashboard') {
            header('Location: ../dashboard/index.php');
        } else {
            header('Location: index.php');
        }
        exit();
    }
}

function requireTechnician() {
    requireLogin();
    if (!isTechnician()) {
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        if ($current_dir !== 'dashboard') {
            header('Location: ../dashboard/index.php');
        } else {
            header('Location: index.php');
        }
        exit();
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateTicketNumber() {
    return 'RSA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function generateTransactionNumber() {
    return 'TXN-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
}

function generateRedemptionCode() {
    return 'RWD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
}

function logActivity($conn, $user_id, $action, $description) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently fail - don't break the application if logging fails
        error_log("Activity log error: " . $e->getMessage());
    }
}