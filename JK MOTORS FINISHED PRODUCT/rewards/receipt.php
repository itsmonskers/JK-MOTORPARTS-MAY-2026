<?php
require_once '../config/config.php';
requireCustomer();

$conn = getDBConnection();
$redemption_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get redemption details
$stmt = $conn->prepare("
    SELECT r.*, rc.reward_name, rc.description, u.name as customer_name, u.email as customer_email, u.contact as customer_contact
    FROM rewards_redemptions r 
    JOIN rewards_catalog rc ON r.reward_id = rc.id 
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->bind_param("ii", $redemption_id, $_SESSION['user_id']);
$stmt->execute();
$redemption = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$redemption) {
    header('Location: redeem.php');
    exit();
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redemption Receipt - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                margin: 0;
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
            .print-section {
                page-break-inside: avoid;
            }
        }
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .receipt-header h2 {
            color: #000;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .receipt-header p {
            color: #000;
            margin: 5px 0;
        }
        .receipt-body {
            color: #000;
        }
        .receipt-body .row {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .receipt-body .label {
            font-weight: bold;
            color: #000;
        }
        .receipt-body .value {
            color: #000;
        }
        .redemption-code-box {
            background-color: #f8f9fa;
            border: 2px dashed #000;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .redemption-code-box .code {
            font-family: 'Courier New', monospace;
            font-size: 28px;
            font-weight: bold;
            color: #000;
            letter-spacing: 3px;
        }
        .redemption-code-box .label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .receipt-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #000;
            text-align: center;
            color: #000;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            color: #fff;
        }
        .status-approved {
            background-color: #28a745;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-redeemed {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="receipt-container print-section">
        <div class="receipt-header">
            <h2><?php echo SITE_NAME; ?></h2>
            <p>Reward Redemption Receipt</p>
            <p style="font-size: 12px;">Date: <?php echo date('F d, Y h:i A', strtotime($redemption['date_redeemed'])); ?></p>
        </div>
        
        <div class="receipt-body">
            <div class="row">
                <div class="col-4 label">Customer Name:</div>
                <div class="col-8 value"><?php echo htmlspecialchars($redemption['customer_name']); ?></div>
            </div>
            
            <div class="row">
                <div class="col-4 label">Email:</div>
                <div class="col-8 value"><?php echo htmlspecialchars($redemption['customer_email']); ?></div>
            </div>
            
            <?php if (!empty($redemption['customer_contact'])): ?>
            <div class="row">
                <div class="col-4 label">Contact:</div>
                <div class="col-8 value"><?php echo htmlspecialchars($redemption['customer_contact']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-4 label">Reward:</div>
                <div class="col-8 value"><strong><?php echo htmlspecialchars($redemption['reward_name']); ?></strong></div>
            </div>
            
            <div class="row">
                <div class="col-4 label">Description:</div>
                <div class="col-8 value"><?php echo htmlspecialchars($redemption['description']); ?></div>
            </div>
            
            <div class="row">
                <div class="col-4 label">Points Used:</div>
                <div class="col-8 value"><?php echo number_format($redemption['points_used']); ?> points</div>
            </div>
            
            <div class="row">
                <div class="col-4 label">Status:</div>
                <div class="col-8 value">
                    <span class="status-badge status-<?php echo $redemption['status']; ?>">
                        <?php echo ucfirst($redemption['status']); ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($redemption['redemption_code'])): ?>
            <div class="redemption-code-box">
                <div class="label">REDEMPTION CODE</div>
                <div class="code"><?php echo htmlspecialchars($redemption['redemption_code']); ?></div>
                <div style="margin-top: 10px; font-size: 12px; color: #666;">
                    Present this code at the shop to claim your reward
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="receipt-footer">
            <p style="font-size: 12px; margin: 0;">Thank you for your loyalty!</p>
            <p style="font-size: 11px; margin: 5px 0 0 0; color: #666;">
                This receipt serves as proof of redemption.<br>
                Please keep this receipt for your records.
            </p>
        </div>
    </div>
    
    <div class="text-center mt-3 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <a href="redeem.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Rewards
        </a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-print when page loads (optional - can be removed if not desired)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>

