<?php
/**
 * Database Installation Script
 * This script helps set up the database with correct password hashes
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - JK Motorparts</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; padding: 10px; background: #f0fff0; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #fff0f0; border-left: 4px solid red; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #f0f0ff; border-left: 4px solid blue; margin: 10px 0; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 JK Motorparts - Database Setup</h1>
    <hr>";

// Check if database exists
try {
    $conn = getDBConnection();
    
    if ($conn) {
        echo "<div class='success'>✅ Database connection successful!</div>";
        
        // Check if admin user exists
        $result = $conn->query("SELECT id FROM users WHERE email = 'admin@jkmotorparts.com'");
        
        if ($result->num_rows > 0) {
            echo "<div class='info'>ℹ️ Admin user already exists.</div>";
            
            if (isset($_POST['reset_password'])) {
                $new_password = $_POST['password'] ?? 'admin123';
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@jkmotorparts.com'");
                $stmt->bind_param("s", $hash);
                
                if ($stmt->execute()) {
                    echo "<div class='success'>✅ Admin password updated successfully!</div>";
                    echo "<div class='info'>New password: <strong>$new_password</strong></div>";
                } else {
                    echo "<div class='error'>❌ Error updating password.</div>";
                }
            } else {
                echo "<form method='POST'>";
                echo "<h3>Reset Admin Password</h3>";
                echo "<p>Current password might not work. Reset it here:</p>";
                echo "<input type='text' name='password' value='admin123' placeholder='New Password' required>";
                echo "<button type='submit' name='reset_password'>Reset Password</button>";
                echo "</form>";
            }
            
        } else {
            echo "<div class='error'>❌ Admin user not found. Please import database/schema.sql first.</div>";
        }
        
        closeDBConnection($conn);
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Please ensure:</div>";
    echo "<ul>";
    echo "<li>MySQL is running</li>";
    echo "<li>Database 'jk_motorparts' exists</li>";
    echo "<li>Database credentials are correct in config/database.php</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='verify.php'>Run Installation Verification</a> | <a href='../auth/login.php'>Go to Login</a></p>";
echo "</body></html>";
?>

