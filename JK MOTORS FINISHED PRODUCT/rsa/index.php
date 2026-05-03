<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $status = sanitizeInput($_POST['status']);
    $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
    
    if ($status === 'completed') {
        $stmt = $conn->prepare("UPDATE rsa_requests SET status = ?, assigned_to = ?, admin_notes = ?, date_completed = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE rsa_requests SET status = ?, assigned_to = ?, admin_notes = ? WHERE id = ?");
    }
    
    if ($assigned_to) {
        $stmt->bind_param("sisi", $status, $assigned_to, $admin_notes, $request_id);
    } else {
        $stmt->bind_param("sssi", $status, $assigned_to, $admin_notes, $request_id);
    }
    
    if ($stmt->execute()) {
        // Update response time when status changes to assigned or in_progress
        if (in_array($status, ['assigned', 'in_progress'])) {
            $received_time = date('Y-m-d H:i:s');
            $stmt2 = $conn->prepare("UPDATE rsa_response_times SET received_time = ? WHERE request_id = ?");
            $stmt2->bind_param("si", $received_time, $request_id);
            $stmt2->execute();
            $stmt2->close();
        }
        
        logActivity($conn, $_SESSION['user_id'], 'rsa_update', "Updated RSA request ID $request_id to status: $status");
        $_SESSION['success'] = 'RSA request updated successfully!';
        $stmt->close();
        closeDBConnection($conn);
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error'] = 'Error updating RSA request.';
    }
    $stmt->close();
}

// Get all RSA requests
$status_filter = $_GET['status'] ?? 'all';
$allowed_statuses = ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'];

if ($status_filter !== 'all' && in_array($status_filter, $allowed_statuses)) {
    $stmt = $conn->prepare("
        SELECT r.*, u.name as customer_name, u.contact as customer_contact, 
               t.name as technician_name 
        FROM rsa_requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN users t ON r.assigned_to = t.id 
        WHERE r.status = ?
        ORDER BY r.date_requested DESC
    ");
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $requests = $conn->query("
        SELECT r.*, u.name as customer_name, u.contact as customer_contact, 
               t.name as technician_name 
        FROM rsa_requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN users t ON r.assigned_to = t.id 
        ORDER BY r.date_requested DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

// Get technicians
$technicians = $conn->query("SELECT id, name FROM users WHERE role = 'technician'")->fetch_all(MYSQLI_ASSOC);

// Get response time data for all requests
$response_times = [];
if (!empty($requests)) {
    $request_ids = array_column($requests, 'id');
    $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
    
    $stmt = $conn->prepare("
        SELECT 
            request_id,
            submission_time,
            received_time,
            TIMESTAMPDIFF(SECOND, submission_time, received_time) as response_seconds
        FROM rsa_response_times 
        WHERE request_id IN ($placeholders)
    ");
    
    $types = str_repeat('i', count($request_ids));
    $stmt->bind_param($types, ...$request_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response_times[$row['request_id']] = $row;
    }
    
    $stmt->close();
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSA Requests - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Ensure modals work properly when outside table */
        .modal {
            z-index: 1055;
        }
        .modal-backdrop {
            z-index: 1050;
        }
        .modal-dialog {
            z-index: 1056;
        }
        .modal-content {
            z-index: 1057;
        }
        /* Map styling */
        .map-container {
            height: 300px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .location-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .location-actions .btn {
            font-size: 0.875rem;
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
                    <h1 class="h2"><i class="fas fa-road"></i> Roadside Assistance Requests</h1>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <!-- Status Filter -->
                <div class="mb-3">
                    <a href="?status=all" class="btn btn-sm btn-outline-secondary <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=pending" class="btn btn-sm btn-outline-warning <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=assigned" class="btn btn-sm btn-outline-info <?php echo $status_filter === 'assigned' ? 'active' : ''; ?>">Assigned</a>
                    <a href="?status=in_progress" class="btn btn-sm btn-outline-primary <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
                    <a href="?status=completed" class="btn btn-sm btn-outline-success <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="color: black;">Ticket #</th>
                                        <th style="color: black;">Customer</th>
                                        <th style="color: black;">Issue Type</th>
                                        <th style="color: black;">Location</th>
                                        <th style="color: black;">Status</th>
                                        <th style="color: black;">Technician</th>
                                        <th style="color: black;">Response Time</th>
                                        <th style="color: black;">Date</th>
                                        <th style="color: black;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requests)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No RSA requests found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($requests as $request): ?>
                                            <?php 
                                            // Get response time for this request
                                            $response_time_data = $response_times[$request['id']] ?? null;
                                            if ($response_time_data && $response_time_data['response_seconds'] !== null) {
                                                $minutes = floor($response_time_data['response_seconds'] / 60);
                                                $seconds = $response_time_data['response_seconds'] % 60;
                                                $response_time_display = $minutes . ' min ' . $seconds . ' sec';
                                            } else {
                                                $response_time_display = 'Pending';
                                            }
                                            ?>
                                            <tr>
                                                <td><strong><?php echo $request['ticket_number']; ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($request['customer_name']); ?><br>
                                                    <small class="text-muted"><?php echo $request['customer_contact']; ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['issue_type']); ?></td>
                                                <td>
                                                    <div class="location-info">
                                                        <div class="location-text"><?php echo htmlspecialchars($request['location']); ?></div>
                                                        <div class="location-actions">
                                                            <button class="btn btn-sm btn-info view-map-btn" 
                                                                    data-location="<?php echo htmlspecialchars($request['location']); ?>"
                                                                    data-ticket="<?php echo $request['ticket_number']; ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#viewModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-map-marker-alt"></i> View Map
                                                            </button>
                                                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($request['location']); ?>" 
                                                               target="_blank" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-external-link-alt"></i> Open in Maps
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge status-<?php echo $request['status']; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $request['technician_name'] ?? 'Not assigned'; ?></td>
                                                <td>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <span class="text-muted">Pending</span>
                                                    <?php else: ?>
                                                        <?php echo $response_time_display; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($request['date_requested'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $request['id']; ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $request['id']; ?>">
                                                        <i class="fas fa-edit"></i> Update
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
                
                <!-- Modals placed outside table to avoid click issues -->
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $request): ?>
                        <?php 
                        // Get response time for this request
                        $response_time_data = $response_times[$request['id']] ?? null;
                        if ($response_time_data && $response_time_data['response_seconds'] !== null) {
                            $minutes = floor($response_time_data['response_seconds'] / 60);
                            $seconds = $response_time_data['response_seconds'] % 60;
                            $response_time_display = $minutes . ' min ' . $seconds . ' sec';
                        } else {
                            $response_time_display = 'Not yet responded';
                        }
                        ?>
                        
                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal<?php echo $request['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">RSA Request Details - Ticket #<?php echo $request['ticket_number']; ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <dl class="row">
                                            <dt class="col-sm-3 text-dark">Ticket Number:</dt>
                                            <dd class="col-sm-9 text-dark"><strong><?php echo $request['ticket_number']; ?></strong></dd>
                                            
                                            <dt class="col-sm-3 text-dark">Customer:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo htmlspecialchars($request['customer_name']); ?></dd>
                                            
                                            <dt class="col-sm-3 text-dark">Contact:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo $request['contact_number']; ?></dd>
                                            
                                            <dt class="col-sm-3 text-dark">Issue Type:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo htmlspecialchars($request['issue_type']); ?></dd>
                                            
                                            <dt class="col-sm-3 text-dark">Description:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo nl2br(htmlspecialchars($request['description'])); ?></dd>
                                            
                                            <dt class="col-sm-3 text-dark">Location:</dt>
                                            <dd class="col-sm-9 text-dark">
                                                <?php echo nl2br(htmlspecialchars($request['location'])); ?>
                                                <div class="mt-3">
                                                    <div id="map-<?php echo $request['id']; ?>" class="map-container"></div>
                                                    <div class="location-actions">
                                                        <button class="btn btn-sm btn-info refresh-map-btn" 
                                                                data-location="<?php echo htmlspecialchars($request['location']); ?>"
                                                                data-map-id="map-<?php echo $request['id']; ?>">
                                                            <i class="fas fa-sync-alt"></i> Refresh Map
                                                        </button>
                                                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($request['location']); ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-external-link-alt"></i> Open in Google Maps
                                                        </a>
                                                        <a href="https://www.openstreetmap.org/search?query=<?php echo urlencode($request['location']); ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-map"></i> Open in OpenStreetMap
                                                        </a>
                                                    </div>
                                                </div>
                                            </dd>
                                            
                                            <dt class="col-sm-3 text-dark">Status:</dt>
                                            <dd class="col-sm-9 text-dark">
                                                <span class="badge status-<?php echo $request['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                </span>
                                            </dd>
                                            
                                            <dt class="col-sm-3 text-dark">Technician:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo $request['technician_name'] ?? 'Not assigned'; ?></dd>
                                            
                                            <dt class="col-sm-3 text-dark">Response Time:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo $response_time_display; ?></dd>
                                            
                                            <dt class="col-sm-3 text-dark">Date Requested:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo date('F d, Y h:i A', strtotime($request['date_requested'])); ?></dd>
                                            
                                            <?php if ($request['admin_notes']): ?>
                                                <dt class="col-sm-3 text-dark">Admin Notes:</dt>
                                                <dd class="col-sm-9 text-dark"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></dd>
                                            <?php endif; ?>
                                            
                                            <?php if ($request['technician_notes']): ?>
                                                <dt class="col-sm-3 text-dark">Technician Notes:</dt>
                                                <dd class="col-sm-9 text-dark"><?php echo nl2br(htmlspecialchars($request['technician_notes'])); ?></dd>
                                            <?php endif; ?>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Update Modal -->
                        <div class="modal fade" id="updateModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="updateModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="updateModalLabel<?php echo $request['id']; ?>">Update RSA Request</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="index.php">
                                        <div class="modal-body">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="assigned" <?php echo $request['status'] === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                    <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $request['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Assign Technician</label>
                                                <select class="form-select" name="assigned_to">
                                                    <option value="">-- Select Technician --</option>
                                                    <?php foreach ($technicians as $tech): ?>
                                                        <option value="<?php echo $tech['id']; ?>" <?php echo $request['assigned_to'] == $tech['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($tech['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Admin Notes</label>
                                                <textarea class="form-control" name="admin_notes" rows="3"><?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" name="update_status">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
    // Map initialization function
    function initializeMap(location, mapId) {
        // Clear previous map if exists
        const mapContainer = document.getElementById(mapId);
        mapContainer.innerHTML = '';
        
        // Try to parse coordinates from location (format: lat,lng)
        const coordPattern = /(-?\d+\.?\d*),\s*(-?\d+\.?\d*)/;
        const match = location.match(coordPattern);
        
        if (match) {
            // Direct coordinates
            const lat = parseFloat(match[1]);
            const lng = parseFloat(match[2]);
            createMap(mapId, [lat, lng], location);
        } else {
            // Geocode using Nominatim
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(location)}&limit=1`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        createMap(mapId, [lat, lng], data[0].display_name);
                    } else {
                        mapContainer.innerHTML = '<div class="alert alert-warning">Location not found. Please use the external map link.</div>';
                    }
                })
                .catch(error => {
                    console.error('Geocoding error:', error);
                    mapContainer.innerHTML = '<div class="alert alert-warning">Could not load map. Please use the external map link.</div>';
                });
        }
    }
    
    // Create map with marker
    function createMap(mapId, coords, locationName) {
        const map = L.map(mapId).setView(coords, 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        L.marker(coords).addTo(map)
            .bindPopup(`<strong>${locationName}</strong>`)
            .openPopup();
    }
    
    // Initialize maps when modals are shown
    document.addEventListener('DOMContentLoaded', function() {
        // Handle modal shown events
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const modalId = modal.id;
                
                // Only initialize maps for view modals
                if (modalId.startsWith('viewModal')) {
                    const requestId = modalId.replace('viewModal', '');
                    const mapId = `map-${requestId}`;
                    
                    // Find location in modal
                    const locationElement = modal.querySelector('.location-text');
                    if (locationElement) {
                        const location = locationElement.textContent.trim();
                        initializeMap(location, mapId);
                    }
                }
            });
        });
        
        // Handle refresh map buttons
        document.querySelectorAll('.refresh-map-btn').forEach(button => {
            button.addEventListener('click', function() {
                const location = this.getAttribute('data-location');
                const mapId = this.getAttribute('data-map-id');
                initializeMap(location, mapId);
            });
        });
    });
    </script>
</body>
</html>