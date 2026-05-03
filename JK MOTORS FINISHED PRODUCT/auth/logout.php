<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    $conn = getDBConnection();
    logActivity($conn, $_SESSION['user_id'], 'logout', 'User logged out');
    closeDBConnection($conn);
}

session_destroy();
header('Location: login.php');
exit();
?>

