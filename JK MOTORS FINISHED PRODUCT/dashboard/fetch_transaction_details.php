<?php
require_once '../config/config.php';
requireCustomer();

$transaction_id = intval($_GET['id'] ?? 0);

if ($transaction_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid transaction ID.'
    ]);
    exit();
}

$conn = getDBConnection();

// Get transaction details
$stmt = $conn->prepare("
    SELECT t.* 
    FROM transactions t 
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $transaction_id, $_SESSION['user_id']);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Transaction not found.'
    ]);
    exit();
}

// Get transaction items
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

// Generate modal content
$modal_content = '
    <dl class="row mb-3">
        <dt class="col-sm-3 text-dark">Transaction #:</dt>
        <dd class="col-sm-9 text-dark"><strong>' . htmlspecialchars($transaction['transaction_number']) . '</strong></dd>
        
        <dt class="col-sm-3 text-dark">Date:</dt>
        <dd class="col-sm-9 text-dark">' . date('F d, Y h:i A', strtotime($transaction['transaction_date'])) . '</dd>
        
        <dt class="col-sm-3 text-dark">Payment Method:</dt>
        <dd class="col-sm-9 text-dark">' . ucfirst($transaction['payment_method']) . '</dd>
        
        <dt class="col-sm-3 text-dark">Amount Received:</dt>
        <dd class="col-sm-9 text-dark">₱' . number_format($transaction['amount_received'] ?? 0, 2) . '</dd>
        
        <dt class="col-sm-3 text-dark">Change Due:</dt>
        <dd class="col-sm-9 text-dark">₱' . number_format($transaction['change_due'] ?? 0, 2) . '</dd>
    </dl>
    
    <table class="table table-sm">
        <thead>
            <tr>
                <th style="color: black;">Product</th>
                <th style="color: black;" class="text-end">Quantity</th>
                <th style="color: black;" class="text-end">Price</th>
                <th style="color: black;" class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
';

foreach ($items as $item) {
    $modal_content .= '
        <tr>
            <td>' . htmlspecialchars($item['product_name']) . '</td>
            <td class="text-end">' . $item['quantity'] . '</td>
            <td class="text-end">₱' . number_format($item['price'], 2) . '</td>
            <td class="text-end">₱' . number_format($item['subtotal'], 2) . '</td>
        </tr>
    ';
}

$modal_content .= '
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Subtotal:</th>
                <th class="text-end">₱' . number_format($transaction['subtotal'], 2) . '</th>
            </tr>
';

if (!empty($discounts)) {
    foreach ($discounts as $discount) {
        $modal_content .= '
            <tr>
                <td colspan="3" class="text-danger">
                    ' . ($discount['discount_type'] === 'percentage' ? 
                        $discount['discount_value'] . '% Discount' : 
                        '₱' . number_format($discount['discount_value'], 2) . ' Discount') . '
                </td>
                <td class="text-end text-danger">-₱' . number_format($discount['discount_amount'], 2) . '</td>
            </tr>
        ';
    }
}

$modal_content .= '
            <tr>
                <th colspan="3">Total Amount:</th>
                <th class="text-end">₱' . number_format($transaction['total_amount'], 2) . '</th>
            </tr>
            <tr>
                <td colspan="3">Points Earned:</td>
                <td class="text-end text-primary"><strong>' . number_format($transaction['points_earned']) . ' points</strong></td>
            </tr>
        </tfoot>
    </table>
';

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'transaction_number' => $transaction['transaction_number'],
    'details' => $modal_content
]);