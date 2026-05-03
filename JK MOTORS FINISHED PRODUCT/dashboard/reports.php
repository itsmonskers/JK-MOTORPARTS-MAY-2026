<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Get date range
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Sales Statistics
$sales_stats = $conn->query("
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(total_amount), 0) as total_sales,
        COALESCE(SUM(points_earned), 0) as total_points
    FROM transactions 
    WHERE DATE(transaction_date) BETWEEN '$date_from' AND '$date_to'
")->fetch_assoc();

// Daily sales for chart
$daily_sales = $conn->query("
    SELECT DATE(transaction_date) as date, SUM(total_amount) as amount
    FROM transactions 
    WHERE DATE(transaction_date) BETWEEN '$date_from' AND '$date_to'
    GROUP BY DATE(transaction_date)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// RSA Statistics
$rsa_stats = $conn->query("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM rsa_requests 
    WHERE DATE(date_requested) BETWEEN '$date_from' AND '$date_to'
")->fetch_assoc();

// Top Products
$top_products = $conn->query("
    SELECT p.name, SUM(ti.quantity) as total_sold, SUM(ti.subtotal) as revenue
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE(t.transaction_date) BETWEEN '$date_from' AND '$date_to'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Rewards Statistics
$rewards_stats = $conn->query("
    SELECT 
        COUNT(*) as total_redemptions,
        COALESCE(SUM(points_used), 0) as total_points_redeemed
    FROM rewards_redemptions 
    WHERE DATE(date_redeemed) BETWEEN '$date_from' AND '$date_to'
")->fetch_assoc();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h6 class="card-title">Total Sales</h6>
                                <h3>₱<?php echo number_format($sales_stats['total_sales'], 2); ?></h3>
                                <small><?php echo $sales_stats['total_transactions']; ?> transactions</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h6 class="card-title">Points Earned</h6>
                                <h3><?php echo number_format($sales_stats['total_points']); ?></h3>
                                <small>Total points awarded</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h6 class="card-title">RSA Requests</h6>
                                <h3><?php echo $rsa_stats['total_requests']; ?></h3>
                                <small><?php echo $rsa_stats['completed']; ?> completed</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h6 class="card-title">Rewards Redeemed</h6>
                                <h3><?php echo $rewards_stats['total_redemptions']; ?></h3>
                                <small><?php echo number_format($rewards_stats['total_points_redeemed']); ?> points</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Daily Sales Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="100"></canvas>
                    </div>
                </div>
                
                <!-- RSA Status Chart -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">RSA Request Status</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="rsaChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Top Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th style="color: black;">Product</th>
                                                <th style="color: black;">Sold</th>
                                                <th style="color: black;">Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $product): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                    <td><?php echo $product['total_sold']; ?></td>
                                                    <td>₱<?php echo number_format($product['revenue'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Sales Chart
        const salesData = <?php echo json_encode($daily_sales); ?>;
        const salesLabels = salesData.map(item => item.date);
        const salesAmounts = salesData.map(item => parseFloat(item.amount));
        
        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'Sales (₱)',
                    data: salesAmounts,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // RSA Chart
        new Chart(document.getElementById('rsaChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Assigned', 'In Progress', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $rsa_stats['pending']; ?>,
                        <?php echo $rsa_stats['assigned']; ?>,
                        <?php echo $rsa_stats['in_progress']; ?>,
                        <?php echo $rsa_stats['completed']; ?>
                    ],
                    backgroundColor: [
                        'rgb(255, 206, 86)',
                        'rgb(54, 162, 235)',
                        'rgb(75, 192, 192)',
                        'rgb(75, 192, 192)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>

