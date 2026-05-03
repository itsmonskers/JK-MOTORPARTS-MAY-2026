<?php
require_once __DIR__ . '/../config/config.php';
?>
<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <!-- NAVBAR BRAND WITH LOGO -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo isCustomer() ? '../customer/home.php' : '../dashboard/index.php'; ?>">
            <img src="../jk motors.png" 
                 alt="Logo" 
                 style="height: 40px; width: auto; margin-right: 10px;">
            <span><i class="fas fa-motorcycle"></i> <?php echo SITE_NAME; ?></span>
        </a>
        <div class="d-flex">
            <span class="navbar-text text-white me-3">
                <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?> 
                <span class="badge bg-secondary"><?php echo ucfirst($_SESSION['role']); ?></span>
            </span>
            <a class="btn btn-outline-light btn-sm" href="../dashboard/profile.php">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a class="btn btn-outline-danger btn-sm ms-2" href="../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

