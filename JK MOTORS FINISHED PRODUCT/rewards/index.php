<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';

// Pagination settings
$items_per_page = 6; // Number of rewards per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_reward'])) {
        $reward_name = sanitizeInput($_POST['reward_name']);
        $description = sanitizeInput($_POST['description']);
        $required_points = intval($_POST['required_points']);
        $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
        
        $stmt = $conn->prepare("INSERT INTO rewards_catalog (reward_name, description, required_points, discount_percentage) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssid", $reward_name, $description, $required_points, $discount_percentage);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'reward_add', "Added reward: $reward_name");
            $message = '<div class="alert alert-success">Reward added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error adding reward.</div>';
        }
        
    }

    
    
    if (isset($_POST['update_reward'])) {
        $id = intval($_POST['reward_id']);
        $reward_name = sanitizeInput($_POST['reward_name']);
        $description = sanitizeInput($_POST['description']);
        $required_points = intval($_POST['required_points']);
        $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE rewards_catalog SET reward_name = ?, description = ?, required_points = ?, discount_percentage = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssidii", $reward_name, $description, $required_points, $discount_percentage, $is_active, $id);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'reward_update', "Updated reward: $reward_name");
            $message = '<div class="alert alert-success">Reward updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating reward.</div>';
        }
    }

    if (isset($_POST['archive_reward'])) {
        $id = intval($_POST['reward_id']);
        $stmt = $conn->prepare("UPDATE rewards_catalog SET is_archived = 1, archived_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'reward_archive', "Archived reward ID: $id");
            $message = '<div class="alert alert-success">Reward moved to archive.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error archiving reward.</div>';
        }
    }

    if (isset($_POST['restore_reward'])) {
        $id = intval($_POST['reward_id']);
        $stmt = $conn->prepare("UPDATE rewards_catalog SET is_archived = 0, archived_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'reward_restore', "Restored reward ID: $id");
            $message = '<div class="alert alert-success">Reward restored successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error restoring reward.</div>';
        }
    }

    if (isset($_POST['delete_reward'])) {
        $id = intval($_POST['reward_id']);
        $stmt = $conn->prepare("DELETE FROM rewards_catalog WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'reward_delete', "Deleted reward ID: $id");
            $message = '<div class="alert alert-success">Reward deleted permanently.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error deleting reward.</div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM rewards_catalog WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'reward_delete', "Deleted reward ID: $id");
        $message = '<div class="alert alert-success">Reward deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting reward.</div>';
    }
}

// Get reward for editing
$edit_reward = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM rewards_catalog WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_reward = $stmt->get_result()->fetch_assoc();
}

// Get all rewards with pagination
$total_rewards = $conn->query("SELECT COUNT(*) FROM rewards_catalog WHERE is_archived = 0")->fetch_row()[0];
$total_pages = ceil($total_rewards / $items_per_page);

$rewards = $conn->query("SELECT * FROM rewards_catalog WHERE is_archived = 0 ORDER BY required_points LIMIT $items_per_page OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
$archived_rewards = $conn->query("SELECT * FROM rewards_catalog WHERE is_archived = 1 ORDER BY archived_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get redemption stats
$redemptions = $conn->query("
    SELECT r.*, u.name as customer_name, rc.reward_name 
    FROM rewards_redemptions r 
    JOIN users u ON r.user_id = u.id 
    JOIN rewards_catalog rc ON r.reward_id = rc.id 
    ORDER BY r.date_redeemed DESC 
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .reward-card {
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
        }
        .reward-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .reward-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            position: relative;
        }
        .reward-points {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .reward-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }
        .pagination {
            margin-top: 2rem;
        }
        .pagination .page-link {
            color: #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
        }
        .pagination .page-item.active .page-link {
            background-color: #667eea;
            border-color: #667eea;
        }
        .pagination .page-link:hover {
            background-color: #f8f9fa;
            color: #667eea;
        }
        .pagination .page-item.disabled .page-link {
            background-color: #e9ecef;
            color: #6c757d;
        }
        
        /* Search bar styling */
        .search-container {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-input {
            flex-grow: 1;
            padding-left: 2.5rem;
            border-radius: 0.5rem 0 0 0.5rem;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .search-icon {
            position: absolute;
            left: 0.75rem;
            color: #6c757d;
            z-index: 10;
        }
        .clear-search-btn {
            border-radius: 0 0.5rem 0.5rem 0;
            border: 1px solid #ced4da;
            border-left: none;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .clear-search-btn:hover {
            background-color: #e9ecef;
        }
        .search-results-message {
            padding: 2rem;
            text-align: center;
            font-style: italic;
            color: #6c757d;
        }
        .search-highlight {
            background-color: #fff3cd;
            padding: 0 2px;
            border-radius: 2px;
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
                    <h1 class="h2"><i class="fas fa-gift"></i> Rewards Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rewardModal" id="addRewardBtn">
                        <i class="fas fa-plus"></i> Add Reward
                    </button>
                </div>
                
                <?php echo $message; ?>

                <!-- Search Bar -->
                <div class="search-container">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="form-control search-input" id="searchRewards" placeholder="Search rewards by name or description...">
                        <button class="btn clear-search-btn" type="button" id="clearSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="searchResults" class="search-results-message" style="display: none;"></div>
                </div>
                
                <!-- Rewards Catalog - Card Grid -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Rewards Catalog</h5>
                        <small class="text-muted"><?php echo $total_rewards; ?> total rewards</small>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rewards)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-gift fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No rewards found</h5>
                                <p class="text-muted">Add your first reward to get started</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-4" id="rewardsGrid">
                                <?php foreach ($rewards as $reward): ?>
                                    <div class="col-lg-4 col-md-6 reward-card" data-reward-id="<?php echo $reward['id']; ?>">
                                        <div class="reward-card-inner">
                                            <div class="reward-header">
                                                <span class="status-badge <?php echo $reward['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $reward['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                                <h5 class="mb-1 reward-name"><?php echo htmlspecialchars($reward['reward_name']); ?></h5>
                                                <p class="mb-0 reward-description opacity-75"><?php echo htmlspecialchars($reward['description']); ?></p>
                                                <div class="reward-actions">
                                                    <a href="?edit=<?php echo $reward['id']; ?>" class="btn btn-sm btn-light">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirmDelete('Archive this reward?')" style="display: inline;">
                                                        <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" name="archive_reward">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div>
                                                        <div class="reward-points text-primary">
                                                            <i class="fas fa-coins"></i> <?php echo number_format($reward['required_points']); ?>
                                                        </div>
                                                        <small class="text-muted">points required</small>
                                                    </div>
                                                    <?php if ($reward['discount_percentage'] > 0): ?>
                                                        <div class="text-end">
                                                            <div class="h5 text-success mb-0">
                                                                <?php echo number_format($reward['discount_percentage'], 1); ?>%
                                                            </div>
                                                            <small class="text-muted">discount</small>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-muted">
                                                            <i class="fas fa-tag"></i> No discount
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-primary flex-fill" onclick="viewRewardDetails(<?php echo $reward['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="viewRedemptions(<?php echo $reward['id']; ?>)">
                                                        <i class="fas fa-chart-line"></i> Stats
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pagination Controls -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Rewards pagination">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php 
                                        // Show page numbers with ellipsis for large page counts
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        
                                        if ($start_page > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                            if ($start_page > 2) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        }
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                            echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                                        }
                                        
                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                        }
                                        ?>
                                        
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                
                                <div class="text-center mt-3">
                                    <span class="text-muted">
                                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Archived Rewards -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Archived Rewards</h5>
                        <span class="badge bg-secondary"><?php echo count($archived_rewards); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style=color:black;>Reward Name</th>
                                        <th style=color:black;>Required Points</th>
                                        <th style=color:black;>Discount</th>
                                        <th style=color:black;>Archived At</th>
                                        <th style=color:black;>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($archived_rewards)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No archived rewards</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($archived_rewards as $reward): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reward['reward_name']); ?></td>
                                                <td><span class="badge bg-primary"><?php echo number_format($reward['required_points']); ?> points</span></td>
                                                <td>
                                                    <?php if ($reward['discount_percentage'] > 0): ?>
                                                        <?php echo number_format($reward['discount_percentage'], 1); ?>%
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $reward['archived_at'] ? date('M d, Y h:i A', strtotime($reward['archived_at'])) : '—'; ?></td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <form method="POST" onsubmit="return confirmDelete('Restore this reward?')">
                                                            <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                                            <button type="submit" name="restore_reward" class="btn btn-sm btn-success">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" onsubmit="return confirmDelete('Delete this reward permanently? This cannot be undone.')">
                                                            <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                                            <button type="submit" name="delete_reward" class="btn btn-sm btn-danger">
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

                <!-- Recent Redemptions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Redemptions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style=color:black;>Customer</th>
                                        <th style=color:black;>Reward</th>
                                        <th style=color:black;>Points Used</th>
                                        <th style=color:black;>Status</th>
                                        <th style=color:black;>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($redemptions)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No redemptions found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($redemptions as $redemption): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($redemption['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($redemption['reward_name']); ?></td>
                                                <td><?php echo number_format($redemption['points_used']); ?> points</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $redemption['status'] === 'approved' ? 'success' : ($redemption['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($redemption['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($redemption['date_redeemed'])); ?></td>
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
    
    <!-- Reward Modal -->
    <div class="modal fade" id="rewardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Reward</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="rewardForm">
                    <div class="modal-body">
                        <input type="hidden" name="reward_id" id="rewardIdInput">
                        
                        <div class="mb-3">
                            <label class="form-label text-dark">Reward Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="reward_name" id="rewardNameInput" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-dark">Description</label>
                            <textarea class="form-control" name="description" id="descriptionInput" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Required Points <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="required_points" id="requiredPointsInput" required min="1">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Discount Percentage (%)</label>
                                <input type="number" class="form-control" name="discount_percentage" id="discountPercentageInput" step="0.1" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="mb-3" id="isActiveDiv" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveInput">
                                <label class="form-check-label" for="isActiveInput">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Add Reward</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Real-time search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchRewards');
            const clearSearchBtn = document.getElementById('clearSearch');
            const rewardsGrid = document.getElementById('rewardsGrid');
            const searchResults = document.getElementById('searchResults');
            let searchTimeout;
            
            // Store original content and state
            const originalContent = rewardsGrid.innerHTML;
            let isSearching = false;
            
            // Function to perform search with debouncing
            function performSearch() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // Set timeout to avoid excessive filtering while typing
                searchTimeout = setTimeout(function() {
                    if (searchTerm === '') {
                        // Reset to original state
                        resetSearch();
                    } else {
                        // Filter rewards based on search term
                        filterRewards(searchTerm);
                    }
                }, 150); // 150ms delay for optimal performance
            }
            
            // Filter rewards based on search term
            function filterRewards(searchTerm) {
                isSearching = true;
                let visibleCount = 0;
                const rewardCards = rewardsGrid.querySelectorAll('.col-lg-4');
                
                // Reset to original content first
                rewardsGrid.innerHTML = originalContent;
                const currentCards = rewardsGrid.querySelectorAll('.col-lg-4');
                
                currentCards.forEach(card => {
                    const rewardName = card.querySelector('.reward-name').textContent.toLowerCase();
                    const rewardDesc = card.querySelector('.reward-description').textContent.toLowerCase();
                    
                    // Check if search term matches reward name or description
                    if (rewardName.includes(searchTerm) || rewardDesc.includes(searchTerm)) {
                        card.style.display = '';
                        visibleCount++;
                        
                        // Highlight matching text
                        highlightMatch(card, searchTerm);
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show search results message
                if (visibleCount === 0) {
                    rewardsGrid.innerHTML = '';
                    searchResults.innerHTML = `<i class="fas fa-search fa-2x mb-3"></i><p>No rewards found matching "<strong>${searchTerm}</strong>"</p>`;
                    searchResults.style.display = 'block';
                } else {
                    searchResults.style.display = 'none';
                }
            }
            
            // Highlight matching text
            function highlightMatch(card, searchTerm) {
                const nameElement = card.querySelector('.reward-name');
                const descElement = card.querySelector('.reward-description');
                
                // Highlight in name
                highlightText(nameElement, searchTerm);
                // Highlight in description
                highlightText(descElement, searchTerm);
            }
            
            // Highlight text in element
            function highlightText(element, searchTerm) {
                const text = element.textContent;
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                const highlightedText = text.replace(regex, '<span class="search-highlight">$1</span>');
                element.innerHTML = highlightedText;
            }
            
            // Reset search to original state
            function resetSearch() {
                isSearching = false;
                rewardsGrid.innerHTML = originalContent;
                searchInput.value = '';
                searchResults.style.display = 'none';
                
                // Restore any pagination that might have been hidden
                const pagination = document.querySelector('.pagination');
                if (pagination) {
                    pagination.style.display = '';
                }
            }
            
            // Event listeners
            searchInput.addEventListener('input', performSearch);
            clearSearchBtn.addEventListener('click', resetSearch);
            
            // Add keyboard shortcuts
            searchInput.addEventListener('keydown', function(e) {
                // Escape key clears search
                if (e.key === 'Escape') {
                    resetSearch();
                }
                // Ctrl/Cmd + K focuses search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                }
            });
            
            // Focus search input when user presses Ctrl/Cmd + K
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                }
            });
        });
        
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('rewardModal');
            const modalTitle = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitBtn');
            const rewardIdInput = document.getElementById('rewardIdInput');
            const isActiveDiv = document.getElementById('isActiveDiv');
            const isActiveInput = document.getElementById('isActiveInput');
            const addRewardBtn = document.getElementById('addRewardBtn');
            
            // Clear edit parameter when modal is closed
            modal.addEventListener('hidden.bs.modal', function () {
                // Remove the edit parameter from URL
                const url = new URL(window.location.href);
                if (url.searchParams.has('edit')) {
                    url.searchParams.delete('edit');
                    window.history.replaceState({}, '', url);
                }
                
                // Reset modal to add state
                resetModalToAdd();
            });
            
            // Handle Add Reward button click
            addRewardBtn.addEventListener('click', function() {
                resetModalToAdd();
            });
            
            // Reset modal to add state
            function resetModalToAdd() {
                modalTitle.textContent = 'Add Reward';
                submitBtn.textContent = 'Add Reward';
                submitBtn.name = 'add_reward';
                rewardIdInput.value = '';
                isActiveDiv.style.display = 'none';
                isActiveInput.checked = false;
                
                // Clear form fields
                document.getElementById('rewardNameInput').value = '';
                document.getElementById('descriptionInput').value = '';
                document.getElementById('requiredPointsInput').value = '';
                document.getElementById('discountPercentageInput').value = '0';
            }
            
            // Handle edit link clicks
            document.querySelectorAll('a[href*="?edit="]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const rewardId = this.getAttribute('href').split('=')[1];
                    
                    // Fetch reward data and populate modal
                    fetchRewardData(rewardId);
                    
                    // Update URL
                    const url = new URL(window.location.href);
                    url.searchParams.set('edit', rewardId);
                    window.history.pushState({}, '', url);
                    
                    // Open modal
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                });
            });
            
            // Fetch reward data
            function fetchRewardData(rewardId) {
                // In a real application, you would fetch this data from the server
                // For now, we'll simulate it with the existing data
                const rewardCard = document.querySelector(`[data-reward-id="${rewardId}"]`);
                if (rewardCard) {
                    const name = rewardCard.querySelector('.reward-name').textContent;
                    const description = rewardCard.querySelector('.reward-description').textContent;
                    const points = rewardCard.querySelector('.reward-points').textContent.replace(/[^0-9]/g, '');
                    
                    // Populate modal
                    modalTitle.textContent = 'Edit Reward';
                    submitBtn.textContent = 'Update Reward';
                    submitBtn.name = 'update_reward';
                    rewardIdInput.value = rewardId;
                    
                    document.getElementById('rewardNameInput').value = name;
                    document.getElementById('descriptionInput').value = description;
                    document.getElementById('requiredPointsInput').value = points;
                    
                    // Show active checkbox (assuming it's active)
                    isActiveDiv.style.display = 'block';
                    isActiveInput.checked = true;
                }
            }
        });
        
        // Helper functions
        function viewRewardDetails(rewardId) {
            // Implement reward details view
            alert('Viewing details for reward ID: ' + rewardId);
        }
        
        function viewRedemptions(rewardId) {
            // Implement redemption statistics
            alert('Viewing redemption stats for reward ID: ' + rewardId);
        }
    </script>
</body>
</html>