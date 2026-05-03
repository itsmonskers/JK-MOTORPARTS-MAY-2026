<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $barcode = sanitizeInput($_POST['barcode']);
        $category = sanitizeInput($_POST['category']);
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, barcode, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $name, $description, $price, $stock, $barcode, $category);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'product_add', "Added product: $name");
            $message = '<div class="alert alert-success">Product added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error adding product.</div>';
        }
    }
    
    if (isset($_POST['update_product'])) {
        $id = intval($_POST['product_id']);
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $barcode = sanitizeInput($_POST['barcode']);
        $category = sanitizeInput($_POST['category']);
        
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, barcode = ?, category = ? WHERE id = ?");
        $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $barcode, $category, $id);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'product_update', "Updated product: $name");
            $message = '<div class="alert alert-success">Product updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating product.</div>';
        }
    }

    if (isset($_POST['archive_product'])) {
        $id = intval($_POST['product_id']);
        $stmt = $conn->prepare("UPDATE products SET is_archived = 1, archived_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'product_archive', "Archived product ID: $id");
            $message = '<div class="alert alert-success">Product archived successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error archiving product.</div>';
        }
    }

    if (isset($_POST['restore_product'])) {
        $id = intval($_POST['product_id']);
        $stmt = $conn->prepare("UPDATE products SET is_archived = 0, archived_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'product_restore', "Restored product ID: $id");
            $message = '<div class="alert alert-success">Product restored successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error restoring product.</div>';
        }
    }

    if (isset($_POST['delete_product'])) {
        $id = intval($_POST['product_id']);
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'product_delete', "Deleted product ID: $id");
            $message = '<div class="alert alert-success">Product deleted permanently.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error deleting product.</div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'product_delete', "Deleted product ID: $id");
        $message = '<div class="alert alert-success">Product deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting product.</div>';
    }
}

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
}

// Get all products
$products = $conn->query("SELECT * FROM products WHERE is_archived = 0 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$archived_products = $conn->query("SELECT * FROM products WHERE is_archived = 1 ORDER BY archived_at DESC")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-box"></i> Products Management</h1>        
                    <div class="d-flex gap-2">
                        <div class="input-group" style="width: 300px;">
                            <input type="text" class="form-control" id="productSearch" placeholder="Search by name or barcode...">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" id="addProductBtn">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>
                </div>
                
                <?php echo $message; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="productsTable">
                                <thead>
                                    <tr>
                                        <th style="color: black;">Name</th>
                                        <th style="color: black;">Barcode</th>
                                        <th style="color: black;">Category</th>
                                        <th style="color: black;">Price</th>
                                        <th style="color: black;">Stock</th>
                                        <th style="color: black;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No products found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['description']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['barcode'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $product['stock'] < 10 ? 'bg-danger' : 'bg-success'; ?>">
                                                        <?php echo $product['stock']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" onsubmit="return confirmDelete('Archive this product?')">
                                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                            <button type="submit" name="archive_product" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-archive"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Archived Products</h5>
                        <span class="badge bg-secondary"><?php echo count($archived_products); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="color: black;">Name</th>
                                        <th style="color: black;">Barcode</th>
                                        <th style="color: black;">Category</th>
                                        <th style="color: black;">Archived At</th>
                                        <th style="color: black;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($archived_products)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No archived products</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($archived_products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['barcode'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                                <td><?php echo $product['archived_at'] ? date('M d, Y h:i A', strtotime($product['archived_at'])) : '—'; ?></td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <form method="POST" onsubmit="return confirmDelete('Restore this product?')">
                                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                            <button type="submit" name="restore_product" class="btn btn-sm btn-success">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" onsubmit="return confirmDelete('Permanently delete this product? This cannot be undone.')">
                                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                            <button type="submit" name="delete_product" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
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
    
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_product ? 'Edit' : 'Add'; ?> Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if ($edit_product): ?>
                            <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label text-dark">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?php echo $edit_product['name'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-dark">Description</label>
                            <textarea class="form-control" name="description" rows="2"><?php echo $edit_product['description'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Price <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="price" step="0.01" value="<?php echo $edit_product['price'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Stock <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="stock" value="<?php echo $edit_product['stock'] ?? '0'; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Barcode</label>
                                <input type="text" class="form-control" name="barcode" value="<?php echo $edit_product['barcode'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Category</label>
                                <input type="text" class="form-control" name="category" value="<?php echo $edit_product['category'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>">
                            <?php echo $edit_product ? 'Update' : 'Add'; ?> Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Load edit product data into modal and auto-show if in edit mode
        <?php if ($edit_product): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // This will open the modal with the pre-filled data after the page reloads from the edit link click
            var modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        });
        <?php endif; ?>

        // FIX 3: Logic to reset the modal when clicking 'Add Product'
        document.addEventListener('DOMContentLoaded', function() {
            const addProductBtn = document.getElementById('addProductBtn');
            const productModal = document.getElementById('productModal');
            const modalForm = productModal ? productModal.querySelector('form') : null;

            if (addProductBtn && modalForm) {
                addProductBtn.addEventListener('click', function() {

                    // --- CORE RESET LOGIC ---

                    // 1. Reset the form to clear previous values
                    modalForm.reset();

                    // 2. Manually clear all relevant input fields (for browser persistence)
                    modalForm.querySelector('input[name="name"]').value = '';
                    modalForm.querySelector('textarea[name="description"]').value = '';
                    modalForm.querySelector('input[name="price"]').value = '';
                    modalForm.querySelector('input[name="stock"]').value = '0'; // Set default stock to 0
                    modalForm.querySelector('input[name="barcode"]').value = '';
                    modalForm.querySelector('input[name="category"]').value = '';

                    // 3. Clear hidden input for product_id (if it exists from a previous edit)
                    const productIdInput = modalForm.querySelector('input[name="product_id"]');
                    if (productIdInput) {
                        productIdInput.remove();
                    }

                    // 4. Reset Modal Title
                    productModal.querySelector('.modal-title').textContent = 'Add Product';

                    // 5. Reset Submit Button Name and Text
                    const submitButton = modalForm.querySelector('button[type="submit"]');
                    submitButton.setAttribute('name', 'add_product');
                    submitButton.textContent = 'Add Product';

                    // ------------------------
                });
            }
        });

        // Product search functionality (Your original search code)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('productSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const tableRows = document.querySelectorAll('#productsTable tbody tr');
                    let visibleRows = 0;

                    tableRows.forEach(row => {
                        const nameCell = row.querySelector('td:nth-child(1)');
                        const barcodeCell = row.querySelector('td:nth-child(2)');

                        if (nameCell && barcodeCell) {
                            const name = nameCell.textContent.toLowerCase();
                            const barcode = barcodeCell.textContent.toLowerCase();

                            if (name.includes(searchTerm) || barcode.includes(searchTerm)) {
                                row.style.display = '';
                                visibleRows++;
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });

                    // Show "No products found" message when search returns no results
                    const noResultsRow = document.querySelector('#productsTable tbody tr td[colspan="6"]');
                    if (noResultsRow) {
                        noResultsRow.parentElement.style.display = visibleRows > 0 ? 'none' : '';
                    }
                });
            }
        });
    </script>
</body>
</html>