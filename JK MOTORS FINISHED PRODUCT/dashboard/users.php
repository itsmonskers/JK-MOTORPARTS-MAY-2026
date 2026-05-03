<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Handle user role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $role = sanitizeInput($_POST['role']);
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $user_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'user_update', "Updated user ID $user_id role to $role");
        $_SESSION['success'] = 'User role updated successfully!';
    } else {
        $_SESSION['error'] = 'Error updating user role.';
    }
}

// Get all users
$role_filter = $_GET['role'] ?? 'all';
$where_clause = "1=1";
if ($role_filter !== 'all') {
    $where_clause = "role = '$role_filter'";
}

$users = $conn->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) as transaction_count,
           (SELECT COUNT(*) FROM rsa_requests WHERE user_id = u.id) as rsa_count
    FROM users u 
    WHERE $where_clause 
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-users"></i> Users Management</h1>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <!-- Role Filter -->
                <div class="mb-3">
                    <a href="?role=all" class="btn btn-sm btn-outline-secondary <?php echo $role_filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?role=admin" class="btn btn-sm btn-outline-danger <?php echo $role_filter === 'admin' ? 'active' : ''; ?>">Admins</a>
                    <a href="?role=customer" class="btn btn-sm btn-outline-primary <?php echo $role_filter === 'customer' ? 'active' : ''; ?>">Customers</a>
                    <a href="?role=technician" class="btn btn-sm btn-outline-warning <?php echo $role_filter === 'technician' ? 'active' : ''; ?>">Technicians</a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="color: black;">Name</th>
                                        <th style="color: black;">Email</th>
                                        <th style="color: black;">Contact</th>
                                        <th style="color: black;">Role</th>
                                        <th style="color: black;">Points</th>
                                        <th style="color: black;">Transactions</th>
                                        <th style="color: black;">RSA Requests</th>
                                        <th style="color: black;">Joined</th>
                                        <th style="color: black;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No users found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['contact'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'technician' ? 'warning' : 'primary'); ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($user['points']); ?></td>
                                                <td><?php echo $user['transaction_count']; ?></td>
                                                <td><?php echo $user['rsa_count']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal<?php echo $user['id']; ?>">
                                                        <i class="fas fa-edit"></i> Change Role
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Role Modal -->
                                            <div class="modal fade" id="roleModal<?php echo $user['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Change User Role</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">User</label>
                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)" disabled>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Role</label>
                                                                    <select class="form-select" name="role" required>
                                                                        <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                                                        <option value="technician" <?php echo $user['role'] === 'technician' ? 'selected' : ''; ?>>Technician</option>
                                                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary" name="update_role">Update Role</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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

