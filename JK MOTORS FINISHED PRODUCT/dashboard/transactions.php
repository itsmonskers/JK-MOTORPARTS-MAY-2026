<?php
require_once '../config/config.php';
requireCustomer();

$conn = getDBConnection();

$transactions = $conn->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as item_count
    FROM transactions t 
    WHERE t.user_id = " . $_SESSION['user_id'] . " 
    ORDER BY t.transaction_date DESC
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transactions - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-history"></i> My Transactions</h1>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="color: black;">Transaction #</th>
                                        <th style="color: black;">Date</th>
                                        <th style="color: black;">Items</th>
                                        <th style="color: black;">Total Amount</th>
                                        <th style="color: black;">Payment Method</th>
                                        <th style="color: black;">Points Earned</th>
                                        <th style="color: black;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No transactions found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><strong><?php echo $transaction['transaction_number']; ?></strong></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo $transaction['item_count']; ?> item(s)</td>
                                                <td>₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                                <td><span class="badge bg-info"><?php echo ucfirst($transaction['payment_method']); ?></span></td>
                                                <td><span class="badge bg-success"><?php echo number_format($transaction['points_earned']); ?> points</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary view-details-btn" 
                                                            data-transaction-id="<?php echo $transaction['id']; ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewModal">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
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
    
    <!-- Single Modal Container -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Transaction Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading transaction details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Handle view button clicks
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.view-details-btn');
            const modalContent = document.getElementById('modalContent');
            const modalTitle = document.getElementById('viewModalLabel');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const transactionId = this.getAttribute('data-transaction-id');
                    
                    // Show loading state
                    modalContent.innerHTML = `
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading transaction details...</p>
                        </div>
                    `;
                    
                    // Fetch transaction details
                    fetch('fetch_transaction_details.php?id=' + transactionId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                modalTitle.textContent = 'Transaction Details - ' + data.transaction_number;
                                modalContent.innerHTML = data.details;
                            } else {
                                modalContent.innerHTML = `
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i> 
                                        ${data.message || 'Failed to load transaction details.'}
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            modalContent.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> 
                                    An error occurred while loading transaction details.
                                </div>
                            `;
                        });
                });
            });
        });
    </script>
</body>
</html>