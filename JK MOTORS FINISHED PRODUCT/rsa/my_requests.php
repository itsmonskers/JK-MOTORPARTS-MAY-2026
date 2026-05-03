<?php
require_once '../config/config.php';
requireCustomer();

$conn = getDBConnection();

// Get customer RSA requests
$requests = $conn->query("
    SELECT r.*, t.name as technician_name 
    FROM rsa_requests r 
    LEFT JOIN users t ON r.assigned_to = t.id 
    WHERE r.user_id = " . $_SESSION['user_id'] . " 
    ORDER BY r.date_requested DESC
")->fetch_all(MYSQLI_ASSOC);

// Get customer media for requests
$customer_media = [];
if (!empty($requests)) {
    $request_ids = array_column($requests, 'id');
    $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
    
    $stmt = $conn->prepare("
        SELECT request_id, media_type, file_path 
        FROM rsa_request_media 
        WHERE request_id IN ($placeholders) 
        ORDER BY uploaded_at
    ");
    
    $types = str_repeat('i', count($request_ids));
    $stmt->bind_param($types, ...$request_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $customer_media[$row['request_id']][] = $row;
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
    
    $types = str_repeat('i', count($request_ids));
    $stmt->bind_param($types, ...$request_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $diagnostics[$row['request_id']] = $row;
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
    <title>My RSA Requests - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .status-pending { background-color: #ffc107; color: #000; }
        .status-in_progress { background-color: #0d6efd; color: #fff; }
        .status-completed { background-color: #198754; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            overflow: hidden;
            z-index: 1050;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            width: 80%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 0.5rem 0.5rem;
        }
        
        .btn-close {
            background: transparent url('data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\' fill=\'%23fff\'%3e%3cpath d=\'M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z\'/%3e%3c/svg%3e') center/1em auto no-repeat;
            opacity: 0.5;
            transition: opacity 0.15s ease-in-out;
        }
        
        .btn-close:hover {
            opacity: 0.75;
        }
        
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 1rem;
            }
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .cancel-reason-select {
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .cancel-note {
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .map-container {
            height: 250px;
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
        
        .media-preview {
            max-width: 150px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin: 5px;
            cursor: pointer;
        }
        
        .media-preview-video {
            max-width: 150px;
            max-height: 100px;
            border-radius: 8px;
            margin: 5px;
        }
        
        .diagnostic-badge {
            background-color: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 10px;
        }
        
        .customer-media-section {
            background-color: #f0f8ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .diagnostic-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
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
                    <h1 class="h2"><i class="fas fa-list"></i> My RSA Requests</h1>
                    <a href="request.php" class="btn btn-warning">
                        <i class="fas fa-plus"></i> New Request
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="color: black;">Ticket #</th>
                                        <th style="color: black;">Issue Type</th>
                                        <th style="color: black;">Location</th>
                                        <th style="color: black;">Status</th>
                                        <th style="color: black;">Technician</th>
                                        <th style="color: black;">Date Requested</th>
                                        <th style="color: black;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requests)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No RSA requests found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $request['ticket_number']; ?></strong>
                                                    <?php if (isset($customer_media[$request['id']])): ?>
                                                        <span class="diagnostic-badge"><i class="fas fa-camera"></i> Media</span>
                                                    <?php endif; ?>
                                                    <?php if (isset($diagnostics[$request['id']])): ?>
                                                        <span class="diagnostic-badge"><i class="fas fa-stethoscope"></i> Diagnosed</span>
                                                    <?php endif; ?>
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
                                                <td><?php echo date('M d, Y h:i A', strtotime($request['date_requested'])); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-primary view-btn" data-request-id="<?php echo $request['id']; ?>">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <?php if (in_array($request['status'], ['pending', 'in_progress'])): ?>
                                                            <button class="btn btn-sm btn-danger cancel-btn" data-request-id="<?php echo $request['id']; ?>" data-request-ticket="<?php echo $request['ticket_number']; ?>">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
                                                        <?php endif; ?>
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
    
    <div id="rsaModalsContainer"></div>
    
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel RSA Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="cancelForm" method="POST">
                    <input type="hidden" name="cancel_request_id" id="cancelRequestId">
                    <input type="hidden" name="cancel_ticket_number" id="cancelTicketNumber">
                    
                    <div class="modal-body text-black">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong style=color:black;>Warning: Are you sure you want to cancel this RSA request? This action cannot be undone. </strong> 
                        </div>
                        
                        <div class="mb-3">
                            <label for="cancelReason" class="form-label" style=color:black;>Reason for Cancellation <span class="text-danger">*</span></label>
                            <select class="form-select cancel-reason-select" id="cancelReason" name="cancel_reason" required>
                                <option value="">Select a reason...</option>
                                <option value="no_longer_needed">No longer needed</option>
                                <option value="issue_resolved">Issue resolved</option>
                                <option value="wrong_information">Provided wrong information</option>
                                <option value="schedule_conflict">Schedule conflict</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cancelNote" class="form-label" style=color:black;>Additional Notes (Optional)</label>
                            <textarea class="form-control cancel-note" id="cancelNote" name="cancel_note" rows="3" placeholder="Please provide any additional details about why you're canceling this request..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Request</button>
                        <button type="submit" class="btn btn-danger">Cancel Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalsContainer = document.getElementById('rsaModalsContainer');
            const viewButtons = document.querySelectorAll('.view-btn');
            const cancelButtons = document.querySelectorAll('.cancel-btn');
            const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
            const cancelForm = document.getElementById('cancelForm');
            
            // Media data passed from PHP
            const customerMedia = <?php echo json_encode($customer_media); ?>;
            const diagnostics = <?php echo json_encode($diagnostics); ?>;
            
            // Function to create modal HTML
            function createModalHTML(request) {
                let mediaHTML = '';
                let diagnosticHTML = '';
                
                // Add customer media section
                if (customerMedia[request.id] && customerMedia[request.id].length > 0) {
                    // Define the new web-accessible path for customer media
                    const customerMediaPath = '../uploads/customer/';
                    
                    mediaHTML = `
                        <div class="customer-media-section">
    <h6 class="text-dark"> <i class="fas fa-camera"></i> Your Media Files</h6>
    <div class="mt-2">
                                ${customerMedia[request.id].map(media => {
                                    const fileExt = media.file_path.split('.').pop().toLowerCase();
                                    const isVideo = ['mp4', 'mov', 'avi'].includes(fileExt);
                                    // *** MODIFICATION START ***
                                    // Use the constructed path for the media file
                                    const mediaUrl = customerMediaPath + media.file_path.substring(media.file_path.lastIndexOf('/') + 1); // Assuming file_path might contain extra path info, so we try to get just the filename. If file_path is already the relative path, this might need adjustment based on what's stored in the DB.
                                    // A safer assumption is that media.file_path contains the file name or a path relative to the old 'uploads/customer/'. If 'file_path' is already a fully correct URL, use:
                                    // const mediaUrl = media.file_path;
                                    // *** MODIFICATION END ***
                                    
                                    if (isVideo) {
                                        return `<video class="media-preview-video" controls>
                                            <source src="${mediaUrl}" type="video/${fileExt}">
                                        </video>`;
                                    } else {
                                        return `<img src="${mediaUrl}" class="media-preview" alt="Customer uploaded image" onclick="window.open('${mediaUrl}', '_blank')">`;
                                    }
                                }).join('')}
                            </div>
                        </div>
                    `;
                }
                
                // Add diagnostic section if available
                if (diagnostics[request.id]) {
                    const diag = diagnostics[request.id];
                    diagnosticHTML = `
                        <div class="diagnostic-section text-dark">
                            <h6><i class="fas fa-stethoscope"></i> Technician Diagnosis</h6>
                            
                            ${diag.issue_confirmed ? `<p><strong>Issue Confirmed:</strong> ${diag.issue_confirmed}</p>` : ''}
                            
                            ${diag.problem_description ? `<p><strong>Problem Description:</strong> ${diag.problem_description.replace(/\n/g, '<br>')}</p>` : ''}
                            
                            ${diag.parts_needed ? `<p><strong>Parts Needed:</strong> ${diag.parts_needed}</p>` : ''}
                            
                            ${diag.estimated_resolution ? `<p><strong>Estimated Resolution:</strong> ${diag.estimated_resolution}</p>` : ''}
                            
                            ${diag.diagnostic_notes ? `<p><strong>Diagnostic Notes:</strong> ${diag.diagnostic_notes.replace(/\n/g, '<br>')}</p>` : ''}
                            
                            ${diag.media_files ? `
                                <div>
                                    <strong>Technician Media:</strong>
                                    <div class="mt-2">
                                        ${JSON.parse(diag.media_files).map(media => {
                                            const fileExt = media.split('.').pop().toLowerCase();
                                            const isVideo = ['mp4', 'mov', 'avi'].includes(fileExt);
                                            const mediaUrl = `../uploads/diagnostic/${media}`;
                                            
                                            if (isVideo) {
                                                return `<video class="media-preview-video" controls>
                                                    <source src="${mediaUrl}" type="video/${fileExt}">
                                                </video>`;
                                            } else {
                                                return `<img src="${mediaUrl}" class="media-preview" alt="Diagnostic image" onclick="window.open('${mediaUrl}', '_blank')">`;
                                            }
                                        }).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            
                            <small class="text-muted">Diagnosed on: ${new Date(diag.created_at).toLocaleString()}</small>
                        </div>
                    `;
                }
                
                return `
                    <div class="modal fade" id="rsaModal${request.id}" tabindex="-1" aria-labelledby="rsaModalLabel${request.id}" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="rsaModalLabel${request.id}">RSA Request Details</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row">
                                        <dt class="col-sm-3 text-dark">Ticket Number:</dt>
                                        <dd class="col-sm-9 text-dark"><strong>${request.ticket_number}</strong></dd>
                                        
                                        <dt class="col-sm-3 text-dark">Issue Type:</dt>
                                        <dd class="col-sm-9 text-dark">${request.issue_type}</dd>
                                        
                                        <dt class="col-sm-3 text-dark">Description:</dt>
                                        <dd class="col-sm-9 text-dark">${request.description.replace(/\n/g, '<br>')}</dd>
                                        
                                        <dt class="col-sm-3 text-dark">Location:</dt>
                                        <dd class="col-sm-9 text-dark">
                                            ${request.location.replace(/\n/g, '<br>')}
                                            <div class="mt-3">
                                                <div id="map-${request.id}" class="map-container"></div>
                                                <div class="location-actions">
                                                    <button class="btn btn-sm btn-info refresh-map-btn" 
                                                            data-location="${request.location}"
                                                            data-map-id="map-${request.id}">
                                                        <i class="fas fa-sync-alt"></i> Refresh Map
                                                    </button>
                                                    <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(request.location)}" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-external-link-alt"></i> Open in Google Maps
                                                    </a>
                                                    <a href="https://www.openstreetmap.org/search?query=${encodeURIComponent(request.location)}" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-map"></i> Open in OpenStreetMap
                                                    </a>
                                                </div>
                                            </div>
                                        </dd>
                                        
                                        <dt class="col-sm-3 text-dark">Contact Number:</dt>
                                        <dd class="col-sm-9 text-dark">${request.contact_number}</dd>
                                        
                                        <dt class="col-sm-3 text-dark">Status:</dt>
                                        <dd class="col-sm-9 text-dark">
                                            <span class="badge status-${request.status}">
                                                ${ucfirst(strReplace('_', ' ', request.status))}
                                            </span>
                                        </dd>
                                        
                                        <dt class="col-sm-3 text-dark">Technician:</dt>
                                        <dd class="col-sm-9 text-dark">${request.technician_name || 'Not assigned yet'}</dd>
                                        
                                        <dt class="col-sm-3 text-dark">Date Requested:</dt>
                                        <dd class="col-sm-9 text-dark">${formatDate(request.date_requested)}</dd>
                                        
                                        ${request.date_completed ? `
                                            <dt class="col-sm-3 text-dark">Date Completed:</dt>
                                            <dd class="col-sm-9 text-dark">${formatDate(request.date_completed)}</dd>
                                        ` : ''}
                                        
                                        ${request.technician_notes ? `
                                            <dt class="col-sm-3 text-dark">Technician Notes:</dt>
                                            <dd class="col-sm-9 text-dark">${request.technician_notes.replace(/\n/g, '<br>')}</dd>
                                        ` : ''}
                                    </dl>
                                    
                                    ${mediaHTML}
                                    
                                    ${diagnosticHTML}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Format date helper
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric', 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
            }
            
            // String replace helper
            function strReplace(search, replace, subject) {
                return subject.split(search).join(replace);
            }
            
            // Capitalize first letter
            function ucfirst(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
            
            // Initialize modals for all requests
            viewButtons.forEach(button => {
                const requestId = button.getAttribute('data-request-id');
                
                // Find the request data
                const request = <?php echo json_encode($requests); ?>.find(r => r.id == requestId);
                
                if (request) {
                    // Create modal HTML
                    const modalHTML = createModalHTML(request);
                    
                    // Add to container
                    modalsContainer.insertAdjacentHTML('beforeend', modalHTML);
                    
                    // Initialize modal
                    const modalElement = document.getElementById(`rsaModal${requestId}`);
                    const modal = new bootstrap.Modal(modalElement);
                    
                    // Store modal reference
                    button.modal = modal;
                }
            });
            
            // Handle view button clicks
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.modal) {
                        this.modal.show();
                    }
                });
            });
            
            // Handle cancel button clicks
            cancelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    const ticketNumber = this.getAttribute('data-request-ticket');
                    
                    // Set form values
                    document.getElementById('cancelRequestId').value = requestId;
                    document.getElementById('cancelTicketNumber').value = ticketNumber;
                    
                    // Show cancel modal
                    cancelModal.show();
                });
            });
            
            // Handle cancel form submission
            cancelForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const requestId = formData.get('cancel_request_id');
                const ticketNumber = formData.get('cancel_ticket_number');
                const reason = formData.get('cancel_reason');
                const note = formData.get('cancel_note');
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                // Submit cancellation request
                fetch('cancel_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('RSA request #' + data.ticket_number + ' has been successfully cancelled.');
                        
                        // Hide cancel modal
                        cancelModal.hide();
                        
                        // Reload page to show updated status
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                        
                    } else {
                        // Show error message
                        alert('Error: ' + (data.message || 'Failed to cancel request. Please try again.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
            
            // Handle modal close events
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-close') || 
                    e.target.classList.contains('btn-secondary') && 
                    e.target.closest('.modal')) {
                    
                    const modal = e.target.closest('.modal');
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            });
            
            // Handle click outside modal to close
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    const modalInstance = bootstrap.Modal.getInstance(e.target);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            });
            
            // Handle Escape key to close modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        const modalInstance = bootstrap.Modal.getInstance(openModal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                }
            });
            
            // Handle map initialization for modals
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('shown.bs.modal', function() {
                    const modalId = modal.id;
                    
                    // Only initialize maps for view modals
                    if (modalId.startsWith('rsaModal')) {
                        const requestId = modalId.replace('rsaModal', '');
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
    </script>
</body>
</html>