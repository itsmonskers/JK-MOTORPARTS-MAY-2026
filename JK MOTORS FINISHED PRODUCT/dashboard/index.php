<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

// Get user stats based on role
if (isAdmin()) {
    // Admin dashboard stats
    $sales_today = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch_assoc();
    $rsa_pending = $conn->query("SELECT COUNT(*) as count FROM rsa_requests WHERE status = 'pending'")->fetch_assoc();
    $total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc();
    $total_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_archived = 0")->fetch_assoc();
    $low_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock < 10 AND is_archived = 0")->fetch_assoc();
    $rewards_redeemed = $conn->query("SELECT COUNT(*) as count FROM rewards_redemptions WHERE DATE(date_redeemed) = CURDATE()")->fetch_assoc();
} elseif (isCustomer()) {
    // Customer dashboard stats
    $user_points = $conn->query("SELECT points FROM users WHERE id = " . $_SESSION['user_id'])->fetch_assoc();
    $total_purchases = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id = " . $_SESSION['user_id'])->fetch_assoc();
    $active_rsa = $conn->query("SELECT COUNT(*) as count FROM rsa_requests WHERE user_id = " . $_SESSION['user_id'] . " AND status IN ('pending', 'assigned', 'in_progress')")->fetch_assoc();
    $rewards_count = $conn->query("SELECT COUNT(*) as count FROM rewards_redemptions WHERE user_id = " . $_SESSION['user_id'])->fetch_assoc();
} elseif (isTechnician()) {
    // Technician dashboard stats
    $assigned_tickets = $conn->query("SELECT COUNT(*) as count FROM rsa_requests WHERE assigned_to = " . $_SESSION['user_id'] . " AND status IN ('assigned', 'in_progress')")->fetch_assoc();
    $completed_today = $conn->query("SELECT COUNT(*) as count FROM rsa_requests WHERE assigned_to = " . $_SESSION['user_id'] . " AND status = 'completed' AND DATE(date_completed) = CURDATE()")->fetch_assoc();
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                </div>
                
                <?php if (isAdmin()): ?>
                    <!-- Admin Dashboard -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Sales Today</h6>
                                            <h3>₱<?php echo number_format($sales_today['total'], 2); ?></h3>
                                            <small><?php echo $sales_today['count']; ?> transactions</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-shopping-cart fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Pending RSA</h6>
                                            <h3><?php echo $rsa_pending['count']; ?></h3>
                                            <small>Requests waiting</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Customers</h6>
                                            <h3><?php echo $total_customers['count']; ?></h3>
                                            <small>Registered users</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Products</h6>
                                            <h3><?php echo $total_products['count']; ?></h3>
                                            <small><?php echo $low_stock['count']; ?> low stock</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-box fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <a href="../pos/index.php" class="btn btn-primary btn-lg w-100 mb-2">
                                        <i class="fas fa-cash-register"></i> Point of Sale
                                    </a>
                                    <a href="../pos/products.php" class="btn btn-success btn-lg w-100 mb-2">
                                        <i class="fas fa-box"></i> Manage Products
                                    </a>
                                    <a href="../rsa/index.php" class="btn btn-warning btn-lg w-100 mb-2">
                                        <i class="fas fa-road"></i> RSA Requests
                                    </a>
                                    <a href="../rewards/index.php" class="btn btn-info btn-lg w-100 mb-2">
                                        <i class="fas fa-gift"></i> Manage Rewards
                                    </a>
                                    <a href="reports.php" class="btn btn-secondary btn-lg w-100">
                                        <i class="fas fa-chart-bar"></i> View Reports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif (isCustomer()): ?>
                    <!-- Customer Dashboard -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Reward Points</h6>
                                            <h3><?php echo number_format($user_points['points']); ?></h3>
                                            <small>Available points</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-star fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Purchases</h6>
                                            <h3><?php echo $total_purchases['count']; ?></h3>
                                            <small>Transactions</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-shopping-bag fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Active RSA</h6>
                                            <h3><?php echo $active_rsa['count']; ?></h3>
                                            <small>Requests</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-road fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Rewards</h6>
                                            <h3><?php echo $rewards_count['count']; ?></h3>
                                            <small>Redeemed</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-gift fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <a href="../rsa/request.php" class="btn btn-warning btn-lg w-100 mb-2">
                                        <i class="fas fa-exclamation-circle"></i> Request Roadside Assistance
                                    </a>
                                    <a href="../rewards/redeem.php" class="btn btn-primary btn-lg w-100 mb-2">
                                        <i class="fas fa-gift"></i> Redeem Rewards
                                    </a>
                                    <a href="transactions.php" class="btn btn-success btn-lg w-100 mb-2">
                                        <i class="fas fa-history"></i> My Transactions
                                    </a>
                                    <a href="profile.php" class="btn btn-info btn-lg w-100">
                                        <i class="fas fa-user"></i> My Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif (isTechnician()): ?>
                    <!-- Technician Dashboard -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Assigned Tickets</h6>
                                            <h3><?php echo $assigned_tickets['count']; ?></h3>
                                            <small>Active requests</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-tasks fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Completed Today</h6>
                                            <h3><?php echo $completed_today['count']; ?></h3>
                                            <small>Finished requests</small>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">My Assigned Tickets</h5>
                                </div>
                                <div class="card-body">
                                    <a href="../rsa/technician.php" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-list"></i> View All Assigned Tickets
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>

