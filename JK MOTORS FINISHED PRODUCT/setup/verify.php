<?php
/**
 * Installation Verification Script
 * Run this script to verify your JK Motorparts installation
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Installation Verification - JK Motorparts</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        h1 { color: #333; }
        .check { margin: 10px 0; padding: 10px; border-left: 4px solid #ccc; }
        .check.success { border-color: green; background: #f0fff0; }
        .check.error { border-color: red; background: #fff0f0; }
        .check.warning { border-color: orange; background: #fff8f0; }
    </style>
</head>
<body>
    <h1>🔍 JK Motorparts - Installation Verification</h1>
    <hr>";

$checks = [];
$all_passed = true;

// Check PHP Version
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4.0', '>=');
$checks[] = [
    'name' => 'PHP Version',
    'status' => $php_ok ? 'success' : 'error',
    'message' => $php_ok ? "PHP $php_version (OK)" : "PHP $php_version (Requires 7.4+)"
];
if (!$php_ok) $all_passed = false;

// Check MySQL Extension
$mysql_ok = extension_loaded('mysqli');
$checks[] = [
    'name' => 'MySQL Extension',
    'status' => $mysql_ok ? 'success' : 'error',
    'message' => $mysql_ok ? 'MySQLi extension loaded' : 'MySQLi extension not found'
];
if (!$mysql_ok) $all_passed = false;

// Check Session Support
$session_ok = function_exists('session_start');
$checks[] = [
    'name' => 'Session Support',
    'status' => $session_ok ? 'success' : 'error',
    'message' => $session_ok ? 'Session support available' : 'Session support not available'
];
if (!$session_ok) $all_passed = false;

// Check Required Files
$required_files = [
    'config/database.php',
    'config/config.php',
    'auth/login.php',
    'dashboard/index.php',
    'pos/index.php',
    'rsa/index.php',
    'rewards/index.php',
    'database/schema.sql'
];

$files_ok = true;
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $files_ok = false;
        break;
    }
}

$checks[] = [
    'name' => 'Required Files',
    'status' => $files_ok ? 'success' : 'error',
    'message' => $files_ok ? 'All required files present' : 'Some required files missing'
];
if (!$files_ok) $all_passed = false;

// Check Database Connection
$db_ok = false;
$db_message = '';

if (file_exists('config/database.php')) {
    require_once 'config/database.php';
    
    try {
        $conn = getDBConnection();
        if ($conn) {
            // Check if database exists and has tables
            $tables = $conn->query("SHOW TABLES")->fetch_all();
            $table_count = count($tables);
            
            $required_tables = ['users', 'products', 'transactions', 'rewards_catalog', 'rsa_requests'];
            $tables_found = [];
            
            foreach ($required_tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows > 0) {
                    $tables_found[] = $table;
                }
            }
            
            if (count($tables_found) === count($required_tables)) {
                $db_ok = true;
                $db_message = "Database connected. All tables present.";
            } else {
                $missing = array_diff($required_tables, $tables_found);
                $db_message = "Database connected but missing tables: " . implode(', ', $missing);
            }
            
            closeDBConnection($conn);
        } else {
            $db_message = "Could not connect to database";
        }
    } catch (Exception $e) {
        $db_message = "Database error: " . $e->getMessage();
    }
} else {
    $db_message = "Database config file not found";
}

$checks[] = [
    'name' => 'Database Connection',
    'status' => $db_ok ? 'success' : 'error',
    'message' => $db_message
];
if (!$db_ok) $all_passed = false;

// Check Write Permissions
$writable_dirs = ['assets', 'dashboard'];
$write_ok = true;
$write_message = '';

foreach ($writable_dirs as $dir) {
    if (!is_writable($dir)) {
        $write_ok = false;
        $write_message .= "$dir not writable. ";
    }
}

$checks[] = [
    'name' => 'Directory Permissions',
    'status' => $write_ok ? 'success' : 'warning',
    'message' => $write_ok ? 'Directories writable' : $write_message
];

// Display Results
foreach ($checks as $check) {
    echo "<div class='check {$check['status']}'>";
    echo "<strong>{$check['name']}:</strong> ";
    echo "<span class='{$check['status']}'>{$check['message']}</span>";
    echo "</div>";
}

echo "<hr>";

if ($all_passed) {
    echo "<h2 class='success'>✅ Installation Verification Passed!</h2>";
    echo "<p>Your JK Motorparts system is properly installed and configured.</p>";
    echo "<p><a href='../auth/login.php'>Go to Login Page</a></p>";
} else {
    echo "<h2 class='error'>❌ Installation Verification Failed</h2>";
    echo "<p>Please fix the issues above before using the system.</p>";
    echo "<p>See INSTALLATION.md for detailed setup instructions.</p>";
}

echo "</body></html>";
?>

