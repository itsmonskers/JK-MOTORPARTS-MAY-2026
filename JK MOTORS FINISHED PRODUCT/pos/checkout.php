<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

$customer_id = intval($_POST['customer_id'] ?? 0);
$payment_method = sanitizeInput($_POST['payment_method'] ?? 'cash');
$cart = $_SESSION['pos_cart'] ?? [];
$discount_data = [];

// Safely decode discount data
if (!empty($_POST['discount_data'])) {
    $decoded = base64_decode($_POST['discount_data']);
    if ($decoded !== false) {
        $discount_data = json_decode($decoded, true);
        if (!is_array($discount_data)) {
            $discount_data = [];
        }
    }
}

$total_discount = floatval($_POST['total_discount'] ?? 0);
$final_total = floatval($_POST['final_total'] ?? 0);
$amount_received = floatval($_POST['amount_received'] ?? 0);

// Debug logging
error_log("Checkout Debug - Customer ID: $customer_id");
error_log("Checkout Debug - Cart count: " . count($cart));
error_log("Checkout Debug - Amount received: $amount_received");
error_log("Checkout Debug - Final total: $final_total");

if (empty($cart) || $customer_id <= 0) {
    // Return JSON response for AJAX request
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid checkout data. Cart is empty or customer not selected.'
    ]);
    exit();
}

// Validate payment amount
if ($amount_received < $final_total) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Insufficient payment. Amount received must be at least ₱' . number_format($final_total, 2)
    ]);
    exit();
}

// Calculate change due
$change_due = $amount_received - $final_total;

// Calculate subtotal (without discounts)
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// POINTS ARE CALCULATED BASED ON SUBTOTAL (BEFORE DISCOUNTS)
$points_earned = floor($subtotal);
$transaction_number = generateTransactionNumber();

// Start transaction
$conn->begin_transaction();

try {
    // Create transaction
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, transaction_number, total_amount, payment_method, points_earned, subtotal, discount_amount, amount_received, change_due) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdddddi", $customer_id, $transaction_number, $final_total, $payment_method, $points_earned, $subtotal, $total_discount, $amount_received, $change_due);
    $stmt->execute();
    $transaction_id = $conn->insert_id;
    
    // Add transaction items and update inventory
    foreach ($cart as $item) {
        // Insert transaction item
        $item_stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $item_subtotal = $item['price'] * $item['quantity'];
        $item_stmt->bind_param("iiidd", $transaction_id, $item['id'], $item['quantity'], $item['price'], $item_subtotal);
        $item_stmt->execute();
        
        // Update product stock
        $update_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $update_stmt->bind_param("ii", $item['quantity'], $item['id']);
        $update_stmt->execute();
    }
    
    // Store discount details if any
    if (!empty($discount_data['applied_discounts'])) {
        foreach ($discount_data['applied_discounts'] as $discount) {
            $discount_stmt = $conn->prepare("INSERT INTO transaction_discounts (transaction_id, discount_type, discount_value, discount_amount) VALUES (?, ?, ?, ?)");
            
            // Calculate discount amount
            if ($discount['type'] === 'percentage') {
                $discount_amount = ($subtotal * $discount['value']) / 100;
            } else {
                $discount_amount = $discount['value'];
            }
            
            $discount_stmt->bind_param("isdd", $transaction_id, $discount['type'], $discount['value'], $discount_amount);
            $discount_stmt->execute();
        }
    }
    
    // Update customer points based on subtotal (before discounts)
    $points_stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $points_stmt->bind_param("ii", $points_earned, $customer_id);
    $points_stmt->execute();
    
    // Log activity
    logActivity($conn, $_SESSION['user_id'], 'transaction', "Processed transaction #$transaction_number for customer ID $customer_id");
    
    // Commit transaction
    $conn->commit();
    
    // Clear cart and discounts
    $_SESSION['pos_cart'] = [];
    $_SESSION['pos_discount'] = [
        'type' => 'percentage',
        'value' => 0,
        'max_stackable' => 1,
        'applied_discounts' => []
    ];
    
    $_SESSION['success'] = "Transaction completed successfully! Transaction #$transaction_number";
    
    // Return JSON response for AJAX request with redirect
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'redirect' => 'receipt.php?id=' . $transaction_id
    ]);
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    // Return JSON response for AJAX request
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Transaction failed: ' . $e->getMessage()
    ]);
    exit();
}

closeDBConnection($conn);
?>