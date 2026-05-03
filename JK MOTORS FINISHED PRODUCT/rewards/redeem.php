<?php
require_once '../config/config.php';
requireCustomer();

$conn = getDBConnection();
$error = '';
$success = '';

// Get user points
$user_stmt = $conn->prepare("SELECT points FROM users WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_points = $user['points'] ?? 0;

// Handle redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem'])) {
    $reward_id = intval($_POST['reward_id']);
    
    // Get reward details
    $reward_stmt = $conn->prepare("SELECT * FROM rewards_catalog WHERE id = ? AND is_active = 1 AND is_archived = 0");
    $reward_stmt->bind_param("i", $reward_id);
    $reward_stmt->execute();
    $reward = $reward_stmt->get_result()->fetch_assoc();
    
    if (!$reward) {
        $error = 'Reward not found or inactive.';
    } elseif ($user_points < $reward['required_points']) {
        $error = 'Insufficient points. You need ' . number_format($reward['required_points']) . ' points but only have ' . number_format($user_points) . ' points.';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Generate unique redemption code
            $redemption_code = generateRedemptionCode();
            
            // Ensure code is unique
            $check_stmt = $conn->prepare("SELECT id FROM rewards_redemptions WHERE redemption_code = ?");
            $check_stmt->bind_param("s", $redemption_code);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $check_stmt->close();
            
            // If code exists, generate a new one
            while ($result->num_rows > 0) {
                $redemption_code = generateRedemptionCode();
                $check_stmt = $conn->prepare("SELECT id FROM rewards_redemptions WHERE redemption_code = ?");
                $check_stmt->bind_param("s", $redemption_code);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $check_stmt->close();
            }
            
            // Create redemption record
            $redemption_stmt = $conn->prepare("INSERT INTO rewards_redemptions (user_id, reward_id, points_used, status, redemption_code) VALUES (?, ?, ?, 'approved', ?)");
            $redemption_stmt->bind_param("iiis", $_SESSION['user_id'], $reward_id, $reward['required_points'], $redemption_code);
            $redemption_stmt->execute();
            $redemption_id = $conn->insert_id;
            $redemption_stmt->close();
            
            // Deduct points from user
            $update_stmt = $conn->prepare("UPDATE users SET points = points - ? WHERE id = ?");
            $update_stmt->bind_param("ii", $reward['required_points'], $_SESSION['user_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            logActivity($conn, $_SESSION['user_id'], 'reward_redemption', "Redeemed reward: {$reward['reward_name']} for {$reward['required_points']} points. Code: $redemption_code");
            $success = "Reward redeemed successfully! Your redemption code is: <strong>$redemption_code</strong><br><a href='receipt.php?id=$redemption_id' class='btn btn-primary mt-2' target='_blank'><i class='fas fa-print'></i> Print Receipt</a>";
            
            // Refresh user points
            $user_points -= $reward['required_points'];
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error redeeming reward. Please try again.';
        }
    }
}

// Get available rewards
$rewards = $conn->query("SELECT * FROM rewards_catalog WHERE is_active = 1 AND is_archived = 0 ORDER BY required_points")->fetch_all(MYSQLI_ASSOC);

// Get user's redemption history
$redemptions_stmt = $conn->prepare("
    SELECT r.*, rc.reward_name, rc.description 
    FROM rewards_redemptions r 
    JOIN rewards_catalog rc ON r.reward_id = rc.id 
    WHERE r.user_id = ? 
    ORDER BY r.date_redeemed DESC
");
$redemptions_stmt->bind_param("i", $_SESSION['user_id']);
$redemptions_stmt->execute();
$redemptions = $redemptions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$redemptions_stmt->close();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Rewards - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        /* Make all text white for readability on dark background */
        main, main *,
        .card, .card *,
        .card-header, .card-header *,
        .card-body, .card-body *,
        .card-title, .card-text,
        h1, h2, h3, h4, h5, h6,
        p, span, div, strong, small,
        .alert, .alert *,
        .text-muted,
        .badge,
        label, .form-label {
            color: #ffffff !important;
        }
        /* Ensure button text is readable */
        .btn {
            color: #ffffff !important;
        }
        .btn-primary, .btn-success, .btn-secondary {
            color: #ffffff !important;
        }
        /* Keep badge backgrounds but make text white */
        .badge.bg-primary, .badge.bg-success, .badge.bg-warning, .badge.bg-secondary {
            color: #ffffff !important;
        }
        /* Make redemption history text black and readable on white background */
        .redemption-history .table,
        .redemption-history .table *,
        .redemption-history .table thead th,
        .redemption-history .table tbody td,
        .redemption-history .card-body,
        .redemption-history .card-body *,
        .redemption-history .card-header,
        .redemption-history .card-header * {
            color: #000000 !important;
        }
        .redemption-history .table thead th {
            background-color: #f8f9fa !important;
            color: #000000 !important;
        }
        .redemption-history .table tbody tr {
            background-color: #ffffff !important;
        }
        .redemption-history .table tbody td {
            color: #000000 !important;
        }
        .redemption-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1em;
            color: #000000 !important;
            background-color: #f0f0f0;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-gift"></i> Redeem Rewards</h1>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- User Points Card -->
                <div class="card mb-4">
    <div class="card-body text-center">
        <h3>Your Reward Points</h3>
        <h1><?php echo number_format($user_points); ?></h1>
        <p>Earn points with every purchase!</p>
    </div>
</div>
                
                <!-- Available Rewards -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Available Rewards</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (empty($rewards)): ?>
                                <div class="col-12 text-center">
                                    <p>No rewards available at the moment.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($rewards as $reward): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 <?php echo $user_points >= $reward['required_points'] ? 'border-success' : 'border-secondary'; ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($reward['reward_name']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($reward['description']); ?></p>
                                                <p class="card-text">
                                                    <strong>Required Points:</strong> 
                                                    <span class="badge bg-primary"><?php echo number_format($reward['required_points']); ?></span>
                                                </p>
                                                <?php if ($reward['discount_percentage'] > 0): ?>
                                                    <p class="card-text">
                                                        <strong>Discount:</strong> <?php echo number_format($reward['discount_percentage'], 1); ?>%
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($user_points >= $reward['required_points']): ?>
                                                    <form method="POST" onsubmit="return confirm('Redeem this reward for <?php echo number_format($reward['required_points']); ?> points?')">
                                                        <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                                        <button type="submit" name="redeem" class="btn btn-success w-100">
                                                            <i class="fas fa-gift"></i> Redeem
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary w-100" disabled>
                                                        Insufficient Points
                                                    </button>
                                                    <small class="text-muted">
                                                        You need <?php echo number_format($reward['required_points'] - $user_points); ?> more points
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Redemption History -->
                <div class="card redemption-history">
                    <div class="card-header">
                        <h5 class="mb-0">My Redemption History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Reward</th>
                                        <th>Redemption Code</th>
                                        <th>Points Used</th>
                                        <th>Status</th>
                                        <th>Date Redeemed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($redemptions)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No redemption history</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($redemptions as $redemption): ?>
                                            <tr>
                                                <td>
                                                    <strong style="color: #000000 !important;"><?php echo htmlspecialchars($redemption['reward_name']); ?></strong><br>
                                                    <small style="color: #666666 !important;"><?php echo htmlspecialchars($redemption['description']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($redemption['redemption_code'])): ?>
                                                        <span class="redemption-code"><?php echo htmlspecialchars($redemption['redemption_code']); ?></span>
                                                    <?php else: ?>
                                                        <span style="color: #666666 !important;">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="color: #000000 !important;"><?php echo number_format($redemption['points_used']); ?> points</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $redemption['status'] === 'approved' ? 'success' : ($redemption['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($redemption['status']); ?>
                                                    </span>
                                                </td>
                                                <td style="color: #000000 !important;"><?php echo date('M d, Y h:i A', strtotime($redemption['date_redeemed'])); ?></td>
                                                <td>
                                                    <?php if (!empty($redemption['redemption_code'])): ?>
                                                        <a href="receipt.php?id=<?php echo $redemption['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                            <i class="fas fa-print"></i> Print
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>

