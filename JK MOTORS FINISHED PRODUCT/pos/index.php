<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Initialize cart if not exists
if (!isset($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}
$cart = $_SESSION['pos_cart'];

// Initialize discount session variables
if (!isset($_SESSION['pos_discount'])) {
    $_SESSION['pos_discount'] = [
        'type' => 'percentage',
        'value' => 0,
        'max_stackable' => 1,
        'applied_discounts' => []
    ];
}
$discount = $_SESSION['pos_discount'];

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_archived = 0");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product && $product['stock'] >= $quantity) {
        if (isset($cart[$product_id])) {
            $cart[$product_id]['quantity'] += $quantity;
        } else {
            $cart[$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'stock' => $product['stock']
            ];
        }
        $_SESSION['pos_cart'] = $cart;
    }
    header('Location: index.php');
    exit();
}

// Remove from cart
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    unset($cart[$product_id]);
    $_SESSION['pos_cart'] = $cart;
    header('Location: index.php');
    exit();
}

// Update cart quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0 && $cart[$product_id]['stock'] >= $quantity) {
        $cart[$product_id]['quantity'] = $quantity;
    } elseif ($quantity <= 0) {
        unset($cart[$product_id]);
    }
    $_SESSION['pos_cart'] = $cart;
    header('Location: index.php');
    exit();
}

// Clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['pos_cart'] = [];
    $_SESSION['pos_discount'] = [
        'type' => 'percentage',
        'value' => 0,
        'max_stackable' => 1,
        'applied_discounts' => []
    ];
    header('Location: index.php');
    exit();
}

// Apply discount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_discount'])) {
    $discount_type = sanitizeInput($_POST['discount_type']);
    $discount_value = floatval($_POST['discount_value']);
    
    // Validate discount
    if ($discount_type === 'percentage' && ($discount_value < 0 || $discount_value > 100)) {
        $_SESSION['error'] = 'Percentage discount must be between 0 and 100';
    } elseif ($discount_type === 'fixed' && $discount_value < 0) {
        $_SESSION['error'] = 'Fixed discount cannot be negative';
    } else {
        // Check if discount can be stacked
        $current_stack_count = count($discount['applied_discounts']);
        if ($current_stack_count >= $discount['max_stackable']) {
            $_SESSION['error'] = 'Maximum discount stacking reached';
        } else {
            // Apply discount
            $discount['type'] = $discount_type;
            $discount['value'] = $discount_value;
            
            // Add to applied discounts
            $discount['applied_discounts'][] = [
                'type' => $discount_type,
                'value' => $discount_value,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $_SESSION['pos_discount'] = $discount;
        }
    }
    header('Location: index.php');
    exit();
}

// Remove discount
if (isset($_GET['remove_discount'])) {
    $discount_index = intval($_GET['remove_discount']);
    
    if (isset($discount['applied_discounts'][$discount_index])) {
        unset($discount['applied_discounts'][$discount_index]);
        $discount['applied_discounts'] = array_values($discount['applied_discounts']); // Reindex array
        
        // Reset if no discounts applied
        if (empty($discount['applied_discounts'])) {
            $discount['value'] = 0;
            $discount['type'] = 'percentage';
        }
        
        $_SESSION['pos_discount'] = $discount;
    }
    header('Location: index.php');
    exit();
}

// Clear all discounts
if (isset($_GET['clear_discounts'])) {
    $discount['value'] = 0;
    $discount['type'] = 'percentage';
    $discount['applied_discounts'] = [];
    $_SESSION['pos_discount'] = $discount;
    header('Location: index.php');
    exit();
}

// Get products for search
$search = $_GET['search'] ?? '';
$products_query = "SELECT * FROM products WHERE stock > 0 AND is_archived = 0";
if (!empty($search)) {
    $search_term = "%$search%";
    $products_query .= " AND (name LIKE ? OR barcode LIKE ? OR description LIKE ?)";
}

$products_query .= " ORDER BY name LIMIT 50";

if (!empty($search)) {
    $stmt = $conn->prepare($products_query);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $products = $conn->query($products_query)->fetch_all(MYSQLI_ASSOC);
}

// Calculate cart totals with discounts
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Calculate total discount amount
$total_discount_amount = 0;
foreach ($discount['applied_discounts'] as $applied_discount) {
    if ($applied_discount['type'] === 'percentage') {
        $total_discount_amount += ($subtotal * $applied_discount['value']) / 100;
    } else {
        $total_discount_amount += $applied_discount['value'];
    }
}

// Ensure discount doesn't exceed total
$total_discount_amount = min($total_discount_amount, $subtotal);

$final_total = $subtotal - $total_discount_amount;
// POINTS ARE CALCULATED BASED ON SUBTOTAL (BEFORE DISCOUNTS)
$points_earned = floor($subtotal);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-cash-register"></i> Point of Sale</h1>
                </div>
                
                <div class="row">
                    <!-- Product Selection -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Select Products</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" id="barcode_input" placeholder="Search by name or barcode..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="color: black;">Name</th>
                                                <th style="color: black;">Price</th>
                                                <th style="color: black;">Stock</th>
                                                <th style="color: black;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($products)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No products found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($products as $product): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($product['barcode'] ?? 'N/A'); ?></small>
                                                        </td>
                                                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                                        <td><?php echo $product['stock']; ?></td>
                                                        <td>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                                <div class="input-group" style="width: 150px;">
                                                                    <input type="number" class="form-control form-control-sm" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                                                                    <button class="btn btn-sm btn-primary" type="submit" name="add_to_cart">
                                                                        <i class="fas fa-plus"></i>
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shopping Cart -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5 class="mb-0">Shopping Cart</h5>
                                <?php if (!empty($cart)): ?>
                                    <a href="?clear_cart=1" class="btn btn-sm btn-danger" onclick="return confirm('Clear cart?')">
                                        <i class="fas fa-trash"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body text-white">
                                <?php if (empty($cart)): ?>
                                    <p class="text-center text-muted">Cart is empty</p>
                                <?php else: ?>
                                    <div class="cart-items">
                                        <?php foreach ($cart as $item): ?>
                                            <div class="cart-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                                        <small>₱<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></small>
                                                    </div>
                                                    <div>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                            <div class="input-group input-group-sm" style="width: 100px;">
                                                                <input type="number" class="form-control" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                                                                <button class="btn btn-sm btn-primary" type="submit" name="update_cart">
                                                                    <i class="fas fa-sync"></i>
                                                                </button>
                                                            </div>
                                                        </form>
                                                        
                                                    </div>
                                                </div>
                                                <div class="text-end mt-1">
                                                    <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <hr>
                                    
                                    <!-- Discount Section -->
                                    <div class="discount-section mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">Discounts</h6>
                                            <?php if (!empty($discount['applied_discounts'])): ?>
                                                <a href="?clear_discounts=1" class="btn btn-sm btn-warning" onclick="return confirm('Clear all discounts?')">
                                                    <i class="fas fa-times"></i> Clear All
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($discount['applied_discounts'])): ?>
                                            <div class="discounts-list">
                                                <?php foreach ($discount['applied_discounts'] as $index => $applied_discount): ?>
                                                    <div class="discount-item d-flex justify-content-between align-items-center mb-1">
                                                        <span>
                                                            <?php 
                                                            echo ($applied_discount['type'] === 'percentage' ? 
                                                                $applied_discount['value'] . '%' : 
                                                                '₱' . number_format($applied_discount['value'], 2));
                                                            ?>
                                                        </span>
                                                        <a href="?remove_discount=<?php echo $index; ?>" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="discount-total d-flex justify-content-between mb-2">
                                                <span>Total Discount:</span>
                                                <span class="text-danger">-₱<?php echo number_format($total_discount_amount, 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="mt-2">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <select class="form-select form-select-sm" name="discount_type" required>
                                                        <option value="percentage" <?php echo $discount['type'] === 'percentage' ? 'selected' : ''; ?>>%</option>
                                                        <option value="fixed" <?php echo $discount['type'] === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                                                    </select>
                                                </div>
                                                <div class="col-4">
                                                    <input type="number" class="form-control form-control-sm" name="discount_value" 
                                                           value="<?php echo $discount['value']; ?>" 
                                                           min="0" 
                                                           max="<?php echo $discount['type'] === 'percentage' ? 100 : 99999; ?>" 
                                                           step="0.01" 
                                                           required>
                                                </div>
                                                <div class="col-2">
                                                    <button type="submit" name="apply_discount" class="btn btn-sm btn-success w-100">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="cart-summary">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span>₱<?php echo number_format($subtotal, 2); ?></span>
                                        </div>
                                        <?php if ($total_discount_amount > 0): ?>
                                            <div class="d-flex justify-content-between mb-2 text-danger">
                                                <span>Discount:</span>
                                                <span>-₱<?php echo number_format($total_discount_amount, 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Total:</strong>
                                            <strong>₱<?php echo number_format($final_total, 2); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span>Points to Earn:</span>
                                            <span class="text-primary"><strong><?php echo number_format($points_earned); ?></strong> points</span>
                                            <small class="text-muted">(based on subtotal)</small>
                                        </div>
                                        
                                        <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                                            <i class="fas fa-check"></i> Checkout
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Checkout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="checkout.php" id="checkoutForm">
                    <input type="hidden" name="discount_data" value='<?php echo base64_encode(json_encode($discount)); ?>'>
                    <input type="hidden" name="total_discount" value="<?php echo $total_discount_amount; ?>">
                    <input type="hidden" name="final_total" value="<?php echo $final_total; ?>">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-black">Select Customer</label>
                            <select class="form-select" name="customer_id" required>
                                <option value="">Select a customer...</option>
                                <?php
                                $conn = getDBConnection();
                                $customers = $conn->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY name");
                                while ($customer = $customers->fetch_assoc()) {
                                    echo "<option value='{$customer['id']}'>{$customer['name']} ({$customer['email']})</option>";
                                }
                                closeDBConnection($conn);
                                ?>
                            </select>
                            <div class="invalid-feedback">Please select a customer.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-black">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-black">Amount Received</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="amount_received" 
                                       id="amountReceived" 
                                       min="<?php echo $final_total; ?>" 
                                       step="0.01" 
                                       value="<?php echo $final_total; ?>" 
                                       required>
                                <span class="input-group-text" id="changeDue">₱0.00</span>
                            </div>
                            <div class="invalid-feedback">Please enter a valid amount.</div>
                            <div class="invalid-feedback" id="insufficientPayment">Amount received must be at least ₱<?php echo number_format($final_total, 2); ?></div>
                        </div>
                        
                        <div class="alert alert-info text-black">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span>₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <?php if ($total_discount_amount > 0): ?>
                                <div class="d-flex justify-content-between text-danger">
                                    <span>Discount:</span>
                                    <span>-₱<?php echo number_format($total_discount_amount, 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between">
                                <strong>Total Amount:</strong>
                                <strong>₱<?php echo number_format($final_total, 2); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Points to Earn:</span>
                                <span class="text-primary"><strong><?php echo number_format($points_earned); ?></strong> points</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Complete Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
// AUTOSCANNING BARCODE HANDLER
document.getElementById("barcode_input").addEventListener("input", function () {
    let code = this.value.trim();

    // Scanners input quickly, usually > 5 characters
    if (code.length > 5) {

        fetch("fetch_product.php?barcode=" + code)
            .then(res => res.json())
            .then(data => {

                if (data.found) {
                    
                    // Auto add to cart WITHOUT clicking (+)
                    addToCart(data.id);

                } else {
                    alert("❌ No product found for barcode: " + code);
                }

                this.value = "";
                this.focus();
            });
    }
});
</script>

<script>
function addToCart(productId) {

    const formData = new FormData();
    formData.append("add_to_cart", "1");
    formData.append("product_id", productId);
    formData.append("quantity", "1");

    fetch("index.php", {
        method: "POST",
        body: formData
    })
    .then(() => {
        // Reload the page so the cart updates
        window.location.reload();
    });
}
</script>

<script>
// Real-time discount calculation
document.addEventListener('DOMContentLoaded', function() {
    const discountType = document.querySelector('select[name="discount_type"]');
    const discountValue = document.querySelector('input[name="discount_value"]');
    
    if (discountType && discountValue) {
        discountType.addEventListener('change', function() {
            if (this.value === 'percentage') {
                discountValue.max = 100;
                discountValue.placeholder = '0-100';
            } else {
                discountValue.max = 99999;
                discountValue.placeholder = '0-99999';
            }
        });
    }
});
</script>

<script>
// Payment amount calculation
document.addEventListener('DOMContentLoaded', function() {
    const amountReceivedInput = document.getElementById('amountReceived');
    const changeDueElement = document.getElementById('changeDue');
    const finalTotal = <?php echo $final_total; ?>;
    
    if (amountReceivedInput && changeDueElement) {
        amountReceivedInput.addEventListener('input', function() {
            const amountReceived = parseFloat(this.value) || 0;
            const changeDue = amountReceived - finalTotal;
            
            // Update change display
            changeDueElement.textContent = '₱' + changeDue.toFixed(2);
            
            // Update change color based on amount
            if (changeDue < 0) {
                changeDueElement.classList.remove('text-success');
                changeDueElement.classList.add('text-danger');
            } else {
                changeDueElement.classList.remove('text-danger');
                changeDueElement.classList.add('text-success');
            }
        });
        
        // Trigger initial calculation
        amountReceivedInput.dispatchEvent(new Event('input'));
    }
});
</script>

<script>
// Form submission handler for checkout
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(event) {
            // Validate form
            const customerSelect = this.querySelector('select[name="customer_id"]');
            const amountReceived = this.querySelector('input[name="amount_received"]');
            
            let isValid = true;
            
            // Reset validation states
            customerSelect.classList.remove('is-invalid');
            amountReceived.classList.remove('is-invalid');
            document.getElementById('insufficientPayment').classList.remove('d-block');
            
            if (!customerSelect.value) {
                customerSelect.classList.add('is-invalid');
                isValid = false;
            }
            
            const amountValue = parseFloat(amountReceived.value) || 0;
            if (amountValue < <?php echo $final_total; ?>) {
                amountReceived.classList.add('is-invalid');
                document.getElementById('insufficientPayment').classList.add('d-block');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                return;
            }
            
            // Store cart and discount data in session variables
            fetch('checkout.php', {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data); // Debug log
                if (data.success) {
                    // Use window.location.href instead of redirect
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Transaction failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during checkout');
            });
            
            event.preventDefault();
        });
    }
});
</script>

</body>
</html>