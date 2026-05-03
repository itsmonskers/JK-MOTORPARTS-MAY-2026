<?php
require_once '../config/config.php';
requireTechnician();

$conn = getDBConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $status = sanitizeInput($_POST['status']);
    $technician_notes = sanitizeInput($_POST['technician_notes'] ?? '');
    
    // --- START: CRITICAL UPDATE FOR date_completed & BINDING FIX ---
    $sql = "UPDATE rsa_requests SET status = ?, technician_notes = ?";
    $params = "ss";
    
    if ($status === 'completed') {
        // Only set date_completed when the status is explicitly set to 'completed'
        $sql .= ", date_completed = NOW()";
    }
    
    $sql .= " WHERE id = ?";
    $params .= "i";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters explicitly based on whether date_completed is included
    if ($status === 'completed') {
        // Prepare to bind status, notes, and request_id
        $stmt->bind_param($params, $status, $technician_notes, $request_id);
    } else {
        // Prepare to bind status, notes, and request_id
        $stmt->bind_param($params, $status, $technician_notes, $request_id);
    }
    // --- END: CRITICAL UPDATE FOR date_completed & BINDING FIX ---

    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'rsa_update', "Updated RSA request ID $request_id to status: $status");
        $_SESSION['success'] = 'RSA request updated successfully! The dashboard stats will be updated on the next load.';
        $stmt->close();
        closeDBConnection($conn);
        
        // Redirect back to technician.php to show the updated table and success message
        header('Location: technician.php'); 
        exit();
    } else {
        $_SESSION['error'] = 'Error updating RSA request.';
    }
    $stmt->close();
}

// Handle diagnostic submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_diagnostic'])) {
    $request_id = intval($_POST['request_id']);
    $issue_confirmed = sanitizeInput($_POST['issue_confirmed'] ?? '');
    $problem_description = sanitizeInput($_POST['problem_description'] ?? '');
    $parts_needed = sanitizeInput($_POST['parts_needed'] ?? '');
    $estimated_resolution = sanitizeInput($_POST['estimated_resolution'] ?? '');
    $diagnostic_notes = sanitizeInput($_POST['diagnostic_notes'] ?? '');
    
    // Handle file uploads
    $media_files = [];
    if (isset($_FILES['diagnostic_media']) && !empty($_FILES['diagnostic_media']['name'][0])) {
        // --- CRITICAL FIX: Use the absolute path constant for server operations ---
        $upload_dir = DIAGNOSTIC_UPLOAD_DIR; 
        
        // Ensure the directory exists using the absolute path
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach ($_FILES['diagnostic_media']['name'] as $i => $name) {
            if ($_FILES['diagnostic_media']['error'][$i] == 0) {
                $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
                
                if (in_array($file_ext, $allowed_exts)) {
                    $file_name = uniqid() . '_' . $name;
                    // Use the absolute upload directory for the destination
                    $file_path = $upload_dir . $file_name; 
                    
                    if (move_uploaded_file($_FILES['diagnostic_media']['tmp_name'][$i], $file_path)) {
                        $media_files[] = $file_name;
                    }
                }
            }
        }
    }
    
    // Save diagnostic information
    $media_json = json_encode($media_files);
    $stmt = $conn->prepare("
        INSERT INTO rsa_diagnostics 
        (request_id, issue_confirmed, problem_description, parts_needed, estimated_resolution, diagnostic_notes, media_files, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("issssssi", $request_id, $issue_confirmed, $problem_description, $parts_needed, $estimated_resolution, $diagnostic_notes, $media_json, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'rsa_diagnostic', "Added diagnostic for RSA request ID $request_id");
        $_SESSION['success'] = 'Diagnostic information saved successfully!';
    } else {
        $_SESSION['error'] = 'Error saving diagnostic information.';
    }
    
    $stmt->close();
    closeDBConnection($conn);
    header('Location: technician.php');
    exit();
}

// Get assigned RSA requests
$requests = $conn->query("
    SELECT r.*, u.name as customer_name, u.contact as customer_contact 
    FROM rsa_requests r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.assigned_to = {$_SESSION['user_id']} 
    ORDER BY r.date_requested DESC
")->fetch_all(MYSQLI_ASSOC);

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
    
    $types = str_repeat('i', count(array_keys($request_ids)));
    // Bind parameters explicitly
    $stmt->bind_param($types, ...$request_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response_times[$row['request_id']] = $row;
    }
    
    $stmt->close();
}

// Get diagnostic data for requests
$diagnostics = [];
if (!empty($requests)) {
    $request_ids = array_column($requests, 'id');
    $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
    
    $stmt = $conn->prepare("
        SELECT * FROM rsa_diagnostics 
        WHERE request_id IN ($placeholders) 
        ORDER BY created_at DESC
    ");
    
    $types = str_repeat('i', count(array_keys($request_ids)));
    // Bind parameters explicitly
    $stmt->bind_param($types, ...$request_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $diagnostics[$row['request_id']] = $row;
    }
    
    $stmt->close();
}

// === START: New Customer Media Data Fetching ===
$customer_media = [];
if (!empty($requests)) {
    $request_ids = array_column($requests, 'id');
    $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
    
    // We fetch all media records for the requests in one query
    $stmt = $conn->prepare("
        SELECT * FROM rsa_request_media 
        WHERE request_id IN ($placeholders) 
        ORDER BY uploaded_at ASC
    ");
    
    $types = str_repeat('i', count(array_keys($request_ids)));
    // Bind parameters explicitly
    $stmt->bind_param($types, ...$request_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Group media by request_id
        $customer_media[$row['request_id']][] = $row; 
    }
    
    $stmt->close();
}
// === END: New Customer Media Data Fetching ===

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
        }
        .media-preview {
            max-width: 200px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin: 5px;
            cursor: pointer;
        }
        .diagnostic-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .diagnostic-badge {
            background-color: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 10px;
        }
        .media-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin: 5px;
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
                    <h1 class="h2"><i class="fas fa-tools"></i> Technician Dashboard</h1>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
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
                                        <th style="color: black;">Response Time</th>
                                        <th style="color: black;">Date Requested</th>
                                        <th style="color: black;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requests)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No RSA requests assigned to you</td>
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
                                                <td>
                                                    <strong><?php echo $request['ticket_number']; ?></strong>
                                                    <?php if (isset($diagnostics[$request['id']])): ?>
                                                        <span class="diagnostic-badge"><i class="fas fa-stethoscope"></i> Diagnosed</span>
                                                    <?php endif; ?>
                                                </td>
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
                                                    <span class="badge status-<?php echo $request['status']; ?> status-badge">
                                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                    </span>
                                                </td>
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
                                                    <?php if ($request['status'] !== 'completed'): ?>
                                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#diagnosticModal<?php echo $request['id']; ?>">
                                                            <i class="fas fa-stethoscope"></i> Diagnostic
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
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
                        
                        // Get diagnostic data for this request
                        $diagnostic_data = $diagnostics[$request['id']] ?? null;
                        $media_files = [];
                        if ($diagnostic_data && $diagnostic_data['media_files']) {
                            $media_files = json_decode($diagnostic_data['media_files'], true);
                        }
                        // Get customer media for this request
                        $customer_media_list = $customer_media[$request['id']] ?? []; 
                        ?>
                        
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
                                                <span class="badge status-<?php echo $request['status']; ?> status-badge">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                </span>
                                            </dd>
                                            
                                            <dt class="col-sm-3 text-dark">Response Time:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo $response_time_display; ?></dd>
                                            
                                            <dt class="col-sm-3 text-dark">Date Requested:</dt>
                                            <dd class="col-sm-9 text-dark"><?php echo date('F d, Y h:i A', strtotime($request['date_requested'])); ?></dd>
                                            
                                            <?php if ($request['technician_notes']): ?>
                                                <dt class="col-sm-3 text-dark">Technician Notes:</dt>
                                                <dd class="col-sm-9 text-dark"><?php echo nl2br(htmlspecialchars($request['technician_notes'])); ?></dd>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($customer_media_list)): ?>
                                                <dt class="col-sm-3 text-dark">Customer Media:</dt>
                                                <dd class="col-sm-9 text-dark">
                                                    <div class="customer-media-section">
                                                        <h6 class="text-dark"><i class="fas fa-camera"></i> Uploaded by Customer</h6>
                                                        <div class="mt-2">
                                                            <?php foreach ($customer_media_list as $media_item): ?>
                                                                <?php
                                                                // --- CRITICAL FIX START (Customer Media) ---
                                                                // 1. Extract ONLY the filename from the path stored in the database
                                                                $filename = basename($media_item['file_path']); 
                                                                
                                                                // 2. The URL path for the browser (uses the relative constant from config.php)
                                                                $media_url = CUSTOMER_URL_PATH . $filename; 
                                                                
                                                                // 3. The ABSOLUTE server path for file_exists check (uses the absolute constant from config.php)
                                                                $absolute_file_path = CUSTOMER_UPLOAD_DIR . $filename;
                                                                
                                                                // 4. Use file_exists with the absolute path
                                                                $file_exists = file_exists($absolute_file_path);
                                                                // --- CRITICAL FIX END (Customer Media) ---
                                                                
                                                                $is_video = $media_item['media_type'] === 'video';
                                                                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                                                ?>
                                                                <?php if ($is_video): ?>
                                                                    <?php if ($file_exists): ?>
                                                                        <video class="media-preview" controls>
                                                                            <source src="<?php echo htmlspecialchars($media_url); ?>" type="video/<?php echo $file_ext; ?>">
                                                                        </video>
                                                                    <?php else: ?>
                                                                        <div class="media-error">
                                                                            <i class="fas fa-exclamation-triangle"></i> 
                                                                            Video not found: <?php echo htmlspecialchars($filename); ?> (Path: <?php echo htmlspecialchars($media_url); ?>)
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <?php if ($file_exists): ?>
                                                                        <img src="<?php echo htmlspecialchars($media_url); ?>" 
                                                                             class="media-preview" 
                                                                             alt="Customer image" 
                                                                             onclick="window.open('<?php echo htmlspecialchars($media_url); ?>', '_blank')"
                                                                             loading="lazy">
                                                                    <?php else: ?>
                                                                        <div class="media-error">
                                                                            <i class="fas fa-exclamation-triangle"></i> 
                                                                            Image not found: <?php echo htmlspecialchars($filename); ?> (Path: <?php echo htmlspecialchars($media_url); ?>)
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </dd>
                                            <?php endif; ?>
                                            
                                            <?php if ($diagnostic_data): ?>
                                                <dt class="col-sm-3 text-dark">Diagnostic:</dt>
                                                <dd class="col-sm-9 text-dark">
                                                    <div class="diagnostic-section">
                                                        <h6><i class="fas fa-stethoscope"></i> Diagnostic Information</h6>
                                                        
                                                        <?php if ($diagnostic_data['issue_confirmed']): ?>
                                                            <p><strong>Issue Confirmed:</strong> <?php echo htmlspecialchars($diagnostic_data['issue_confirmed']); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($diagnostic_data['problem_description']): ?>
                                                            <p><strong>Problem Description:</strong> <?php echo nl2br(htmlspecialchars($diagnostic_data['problem_description'])); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($diagnostic_data['parts_needed']): ?>
                                                            <p><strong>Parts Needed:</strong> <?php echo htmlspecialchars($diagnostic_data['parts_needed']); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($diagnostic_data['estimated_resolution']): ?>
                                                            <p><strong>Estimated Resolution:</strong> <?php echo htmlspecialchars($diagnostic_data['estimated_resolution']); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($diagnostic_data['diagnostic_notes']): ?>
                                                            <p><strong>Diagnostic Notes:</strong> <?php echo nl2br(htmlspecialchars($diagnostic_data['diagnostic_notes'])); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($media_files)): ?>
                                                            <div>
                                                                <strong>Media Files:</strong>
                                                                <div class="mt-2">
                                                                    <?php foreach ($media_files as $media_file): ?>
                                                                        <?php
                                                                        $file_ext = strtolower(pathinfo($media_file, PATHINFO_EXTENSION));
                                                                        $is_video = in_array($file_ext, ['mp4', 'mov', 'avi']);
                                                                        
                                                                        // --- CRITICAL FIX START (Diagnostic Media) ---
                                                                        $media_url = DIAGNOSTIC_URL_PATH . $media_file;
                                                                        $absolute_diag_path = DIAGNOSTIC_UPLOAD_DIR . $media_file;
                                                                        $file_exists = file_exists($absolute_diag_path);
                                                                        // --- CRITICAL FIX END (Diagnostic Media) ---
                                                                        ?>
                                                                        <?php if ($is_video): ?>
                                                                            <?php if ($file_exists): ?>
                                                                                <video class="media-preview" controls>
                                                                                    <source src="<?php echo htmlspecialchars($media_url); ?>" type="video/<?php echo $file_ext; ?>">
                                                                                </video>
                                                                            <?php else: ?>
                                                                                <div class="media-error">
                                                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                                                    Diagnostic video not found: <?php echo htmlspecialchars($media_file); ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <?php if ($file_exists): ?>
                                                                                <img src="<?php echo htmlspecialchars($media_url); ?>" 
                                                                                     class="media-preview" 
                                                                                     alt="Diagnostic image" 
                                                                                     onclick="window.open('<?php echo htmlspecialchars($media_url); ?>', '_blank')"
                                                                                     loading="lazy">
                                                                            <?php else: ?>
                                                                                <div class="media-error">
                                                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                                                    Diagnostic image not found: <?php echo htmlspecialchars($media_file); ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <small class="text-muted">Diagnosed on: <?php echo date('F d, Y h:i A', strtotime($diagnostic_data['created_at'])); ?></small>
                                                    </div>
                                                </dd>
                                            <?php endif; ?>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal fade" id="updateModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="updateModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="updateModalLabel<?php echo $request['id']; ?>">Update RSA Request</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="technician.php">
                                        <div class="modal-body">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="assigned" <?php echo $request['status'] === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                    <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Technician Notes</label>
                                                <textarea class="form-control" name="technician_notes" rows="3"><?php echo htmlspecialchars($request['technician_notes'] ?? ''); ?></textarea>
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
                        
                        <?php if ($request['status'] !== 'completed'): ?>
                        <div class="modal fade" id="diagnosticModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="diagnosticModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="diagnosticModalLabel<?php echo $request['id']; ?>">
                                            <i class="fas fa-stethoscope"></i> Add Diagnostic Information
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="technician.php" enctype="multipart/form-data">
                                        <div class="modal-body">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Issue Confirmed</label>
                                                <select class="form-select" name="issue_confirmed">
                                                    <option value="">Select the confirmed issue</option>
                                                    <option value="Battery Issue">Battery Issue</option>
                                                    <option value="Tire Problem">Tire Problem</option>
                                                    <option value="Engine Trouble">Engine Trouble</option>
                                                    <option value="Lockout/Key Issue">Lockout/Key Issue</option>
                                                    <option value="Fuel Delivery">Fuel Delivery</option>
                                                    <option value="Winch/Towing">Winch/Towing</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Problem Description</label>
                                                <textarea class="form-control" name="problem_description" rows="3" placeholder="Describe the problem in detail..."></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Parts Needed</label>
                                                <input type="text" class="form-control" name="parts_needed" placeholder="List any parts needed for repair">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Estimated Resolution Time</label>
                                                <select class="form-select" name="estimated_resolution">
                                                    <option value="">Select estimated time</option>
                                                    <option value="15-30 minutes">15-30 minutes</option>
                                                    <option value="30-60 minutes">30-60 minutes</option>
                                                    <option value="1-2 hours">1-2 hours</option>
                                                    <option value="2-4 hours">2-4 hours</option>
                                                    <option value="4+ hours">4+ hours</option>
                                                    <option value="Towing Required">Towing Required</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Diagnostic Notes</label>
                                                <textarea class="form-control" name="diagnostic_notes" rows="3" placeholder="Additional notes about the diagnosis..."></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Upload Media (Images/Videos)</label>
                                                <input type="file" class="form-control" name="diagnostic_media[]" multiple accept="image/*,video/*">
                                                <small class="form-text text-muted">Upload photos or videos of the issue to help with diagnosis</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" name="submit_diagnostic">
                                                <i class="fas fa-save"></i> Save Diagnostic
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
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