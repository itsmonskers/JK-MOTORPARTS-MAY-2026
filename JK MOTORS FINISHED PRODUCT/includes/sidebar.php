<?php
require_once __DIR__ . '/../config/config.php';
?>
<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <?php if (isCustomer()): ?>
                    <a class="nav-link <?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'customer' && basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active' : ''; ?>" href="../customer/home.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                <?php else: ?>
                    <a class="nav-link <?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'dashboard' && basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="../dashboard/index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                <?php endif; ?>
            </li>
            
            <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../pos/index.php">
                        <i class="fas fa-cash-register"></i> Point of Sale
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../pos/products.php">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../rsa/index.php">
                        <i class="fas fa-road"></i> RSA Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../rewards/index.php">
                        <i class="fas fa-gift"></i> Rewards
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../rewards/history.php">
                        <i class="fas fa-history"></i> Rewards History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../dashboard/reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../dashboard/users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
            <?php elseif (isCustomer()): ?>
                <!-- Customer Dashboard Button -->
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'dashboard') ? 'active' : ''; ?>" href="../dashboard/index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../rsa/request.php">
                        <i class="fas fa-exclamation-circle"></i> Request RSA
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../rsa/my_requests.php">
                        <i class="fas fa-list"></i> My RSA Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../rewards/redeem.php">
                        <i class="fas fa-gift"></i> Redeem Rewards
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../dashboard/transactions.php">
                        <i class="fas fa-history"></i> My Transactions
                    </a>
                </li>
            <?php elseif (isTechnician()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../rsa/technician.php">
                        <i class="fas fa-tasks"></i> My Tickets
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link" href="../dashboard/profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
        </ul>
    </div>
</nav>