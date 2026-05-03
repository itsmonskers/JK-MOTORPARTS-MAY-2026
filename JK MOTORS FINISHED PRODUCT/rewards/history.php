<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';

$allowed_statuses = ['pending', 'approved', 'redeemed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $redemption_id = intval($_POST['redemption_id']);
        $status = in_array($_POST['status'], $allowed_statuses, true) ? $_POST['status'] : 'pending';

        $stmt = $conn->prepare("UPDATE rewards_redemptions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $redemption_id);

        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'reward_status_update', "Updated redemption #$redemption_id to $status");
            $message = '<div class="alert alert-success">Redemption status updated.</div>';
        } else {
            $message = '<div class="alert alert-danger">Unable to update redemption status.</div>';
        }
    }

    if (isset($_POST['adjust_points'])) {
        $user_id = intval($_POST['user_id']);
        $points_change = intval($_POST['points_change']);
        $direction = $_POST['adjust_action'] === 'deduct' ? -1 : 1;
        $note = sanitizeInput($_POST['note'] ?? '');

        $user_stmt = $conn->prepare("SELECT points, name FROM users WHERE id = ? AND role = 'customer'");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user = $user_stmt->get_result()->fetch_assoc();

        if (!$user) {
            $message = '<div class="alert alert-danger">Customer not found.</div>';
        } elseif ($points_change <= 0) {
            $message = '<div class="alert alert-danger">Points change must be greater than zero.</div>';
        } else {
            $new_points = $user['points'] + ($points_change * $direction);
            if ($new_points < 0) {
                $new_points = 0;
            }

            $update_stmt = $conn->prepare("UPDATE users SET points = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_points, $user_id);

            if ($update_stmt->execute()) {
                $action = $direction > 0 ? 'added to' : 'deducted from';
                $description = sprintf(
                    '%d points %s customer %s. %s',
                    $points_change,
                    $action,
                    $user['name'],
                    $note ? "Note: $note" : ''
                );
                logActivity($conn, $_SESSION['user_id'], 'reward_points_adjust', $description);
                $message = '<div class="alert alert-success">Customer points updated.</div>';
            } else {
                $message = '<div class="alert alert-danger">Unable to update customer points.</div>';
            }
        }
    }
}

$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter_status = $_GET['status'] ?? '';
$filter_status = in_array($filter_status, $allowed_statuses, true) ? $filter_status : '';

$query = "
    SELECT r.*, u.name AS customer_name, u.email AS customer_email, rc.reward_name, rc.description
    FROM rewards_redemptions r
    JOIN users u ON r.user_id = u.id
    JOIN rewards_catalog rc ON r.reward_id = rc.id
";

$conditions = [];
$params = [];
$types = '';

if ($filter_user > 0) {
    $conditions[] = 'r.user_id = ?';
    $params[] = $filter_user;
    $types .= 'i';
}

if ($filter_status !== '') {
    $conditions[] = 'r.status = ?';
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($conditions)) {
    $query .= ' WHERE ' . implode(' AND ', $conditions);
}

$query .= ' ORDER BY r.date_redeemed DESC LIMIT 200';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$redemptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$customers = $conn->query("SELECT id, name, email, points FROM users WHERE role = 'customer' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards History - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2"><i class="fas fa-history"></i> Rewards History</h1>
                </div>

                <?php echo $message; ?>

                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Filter Redemptions</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Customer</label>
                                        <select name="user_id" class="form-select">
                                            <option value="">All customers</option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?php echo $customer['id']; ?>" <?php echo $filter_user == $customer['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($customer['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All statuses</option>
                                            <?php foreach ($allowed_statuses as $status_option): ?>
                                                <option value="<?php echo $status_option; ?>" <?php echo $filter_status === $status_option ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($status_option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter"></i> Apply
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Adjust Customer Points</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Customer</label>
                                        <select name="user_id" class="form-select" required>
                                            <option value="">Select customer</option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?php echo $customer['id']; ?>">
                                                    <?php echo htmlspecialchars($customer['name']); ?> (<?php echo number_format($customer['points']); ?> pts)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Points</label>
                                        <div class="input-group">
                                            <select name="adjust_action" class="form-select" style="max-width: 120px;">
                                                <option value="add">Add</option>
                                                <option value="deduct">Deduct</option>
                                            </select>
                                            <input type="number" name="points_change" class="form-control" min="1" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Note (optional)</label>
                                        <textarea name="note" class="form-control" rows="2" placeholder="Reason for adjustment"></textarea>
                                    </div>
                                    <button type="submit" name="adjust_points" class="btn btn-success w-100">
                                    <i class="fas fa-sync"></i> Update Points
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Redemption History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="color: black;">ID</th>
                                        <th style="color: black;">Customer</th>
                                        <th style="color: black;">Reward</th>
                                        <th style="color: black;">Redemption Code</th>
                                        <th style="color: black;">Points Used</th>
                                        <th style="color: black;">Status</th>
                                        <th style="color: black;">Date</th>
                                        <th style="color: black;">Manage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($redemptions)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No redemptions found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($redemptions as $redemption): ?>
                                            <tr>
                                                <td>#<?php echo $redemption['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($redemption['customer_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($redemption['customer_email']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($redemption['reward_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($redemption['description']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($redemption['redemption_code'])): ?>
                                                        <code style="background-color: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-weight: bold;">
                                                            <?php echo htmlspecialchars($redemption['redemption_code']); ?>
                                                        </code>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo number_format($redemption['points_used']); ?> pts</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $redemption['status'] === 'approved' ? 'success' : ($redemption['status'] === 'pending' ? 'warning' : ($redemption['status'] === 'redeemed' ? 'primary' : 'secondary')); ?>">
                                                        <?php echo ucfirst($redemption['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($redemption['date_redeemed'])); ?></td>
                                                <td>
                                                    <form method="POST" class="d-flex gap-2 align-items-center">
                                                        <input type="hidden" name="redemption_id" value="<?php echo $redemption['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm">
                                                            <?php foreach ($allowed_statuses as $status_option): ?>
                                                                <option value="<?php echo $status_option; ?>" <?php echo $redemption['status'] === $status_option ? 'selected' : ''; ?>>
                                                                    <?php echo ucfirst($status_option); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-save"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted mb-0"><small>Showing up to 200 most recent records.</small></p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>




