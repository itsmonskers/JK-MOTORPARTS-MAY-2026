<?php
// Redirect to login or dashboard based on authentication status
require_once 'config/config.php';

if (isLoggedIn()) {
    header('Location: dashboard/index.php');
} else {
    header('Location: auth/login.php');
}
exit();
?>

