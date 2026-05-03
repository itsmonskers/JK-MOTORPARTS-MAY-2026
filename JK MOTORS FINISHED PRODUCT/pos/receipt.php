<?php
require_once '../config/config.php';
requireAdmin();

$transaction_id = intval($_GET['id'] ?? 0);

if ($transaction_id <= 0) {
    $_SESSION['error'] = 'Invalid transaction ID.';
    header('Location: index.php');
    exit();
}

$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT t.*, u.name as customer_name, u.email as customer_email 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    $_SESSION['error'] = 'Transaction not found.';
    header('Location: index.php');
    exit();
}

$items_stmt = $conn->prepare("
    SELECT ti.*, p.name as product_name 
    FROM transaction_items ti 
    JOIN products p ON ti.product_id = p.id 
    WHERE ti.transaction_id = ?
");
$items_stmt->bind_param("i", $transaction_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get discount details
$discounts_stmt = $conn->prepare("
    SELECT td.* 
    FROM transaction_discounts td 
    WHERE td.transaction_id = ?
    ORDER BY td.id
");
$discounts_stmt->bind_param("i", $transaction_id);
$discounts_stmt->execute();
$discounts = $discounts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
        }
        .receipt {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }
        .points-note {
            font-size: 0.8em;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="receipt">
            <div class="text-center mb-4">
                
                <div class="logo-container" style="margin-bottom: 10px;">
                    <img src="../jk motors.png" alt="<?php echo SITE_NAME; ?> Logo" style="max-width: 120px; height: auto;">
                </div>
                
                <h3><?php echo SITE_NAME; ?></h3>
                <p class="mb-0">Smart Solutions System</p>
                
                <div class="company-contact-info" style="font-size: 13px; margin-top: 5px;">
                    <p class="mb-0">1000 Int 2 Bohol St. Sampaloc, Manila, Philippines, 1008</p>
                    <p class="mb-0">Tel: +63 95 67 447 531</p>
                </div>
                
                <small>Transaction Receipt</small>
            </div>
            
            <hr>
            
            <div class="mb-3">
                <strong>Transaction #:</strong> <?php echo $transaction['transaction_number']; ?><br>
                <strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($transaction['transaction_date'])); ?><br>
                <strong>Customer:</strong> <?php echo htmlspecialchars($transaction['customer_name']); ?><br>
                <strong>Payment Method:</strong> <?php echo ucfirst($transaction['payment_method']); ?>
            </div>
            
            <hr>
            
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="text-end"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Subtotal:</th>
                        <th class="text-end">₱<?php echo number_format($transaction['subtotal'], 2); ?></th>
                    </tr>
                    <?php if (!empty($discounts)): ?>
                        <?php foreach ($discounts as $discount): ?>
                            <tr>
                                <td colspan="3" class="text-danger">
                                    <?php 
                                    echo ($discount['discount_type'] === 'percentage' ? 
                                        $discount['discount_value'] . '% Discount' : 
                                        '₱' . number_format($discount['discount_value'], 2) . ' Discount');
                                    ?>
                                </td>
                                <td class="text-end text-danger">-₱<?php echo number_format($discount['discount_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr>
                        <th colspan="3">Total Amount:</th>
                        <th class="text-end">₱<?php echo number_format($transaction['total_amount'], 2); ?></th>
                    </tr>
                    <tr>
                        <th colspan="3">Amount Received:</th>
                        <th class="text-end">₱<?php echo number_format($transaction['amount_received'] ?? 0, 2); ?></th>
                    </tr>
                    <tr>
                        <th colspan="3">Change Due:</th>
                        <th class="text-end text-success">₱<?php echo number_format($transaction['change_due'] ?? 0, 2); ?></th>
                    </tr>
                    <tr>
                        <td colspan="3">
                            Points Earned:
                            <div class="points-note">(based on subtotal before discounts)</div>
                        </td>
                        <td class="text-end text-primary"><strong><?php echo number_format($transaction['points_earned']); ?> points</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <hr>
            
            <div class="text-center">
                <p class="mb-0">Thank you for your purchase!</p>
                <small>Points have been added to your account.</small>
            </div>
            
            <div class="text-center mt-4 no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to POS
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = function() {
            // Auto print on load (optional)
            // window.print();
        }
    </script>
</body>
</html>