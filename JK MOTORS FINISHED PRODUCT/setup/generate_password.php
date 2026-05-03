<?php
/**
 * Password Hash Generator
 * Use this script to generate password hashes for the database
 */

if (php_sapi_name() === 'cli' || isset($_GET['generate'])) {
    $password = $_GET['password'] ?? 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "Password: $password\n";
    echo "Hash: $hash\n";
    echo "\n";
    echo "SQL INSERT statement:\n";
    echo "INSERT INTO users (name, email, password, role, contact) VALUES\n";
    echo "('User Name', 'user@example.com', '$hash', 'customer', '09123456789');\n";
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Hash Generator</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            form { max-width: 500px; }
            input, button { padding: 10px; margin: 5px 0; width: 100%; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>Password Hash Generator</h1>
        <form method="GET">
            <label>Password:</label>
            <input type="text" name="password" value="admin123" required>
            <button type="submit" name="generate">Generate Hash</button>
        </form>
        
        <?php if (isset($_GET['generate'])): ?>
            <?php
            $password = $_GET['password'];
            $hash = password_hash($password, PASSWORD_DEFAULT);
            ?>
            <h2>Result:</h2>
            <pre>
Password: <?php echo htmlspecialchars($password); ?>

Hash: <?php echo $hash; ?>

SQL INSERT:
INSERT INTO users (name, email, password, role, contact) VALUES
('User Name', 'user@example.com', '<?php echo $hash; ?>', 'customer', '09123456789');
            </pre>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
?>

