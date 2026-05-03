<?php
require_once '../config/config.php';
requireCustomer();

$conn = getDBConnection();
$error = '';
$success = '';

// Get current user's location details for display
$user_location = '';
if (isset($_SESSION['latitude']) && isset($_SESSION['longitude'])) {
    $lat = $_SESSION['latitude'];
    $lng = $_SESSION['longitude'];
    $geocode = file_get_contents("https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&accept-language=en");
    if ($geocode) {
        $location_data = json_decode($geocode, true);
        if (isset($location_data['display_name'])) {
            $user_location = $location_data['display_name'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_type = sanitizeInput($_POST['issue_type']);
    $description = sanitizeInput($_POST['description']);
    $location = sanitizeInput($_POST['location']);
    $contact_number = sanitizeInput($_POST['contact_number']);
    $latitude = sanitizeInput($_POST['latitude'] ?? '');
    $longitude = sanitizeInput($_POST['longitude'] ?? '');
    
    if (empty($issue_type) || empty($description) || empty($location) || empty($contact_number)) {
        $error = 'Please fill in all fields.';
    } else {
        $ticket_number = generateTicketNumber();
        $user_id = $_SESSION['user_id'];
        
        // Insert request with coordinates
        $stmt = $conn->prepare("INSERT INTO rsa_requests (user_id, ticket_number, issue_type, description, location, contact_number, latitude, longitude, date_requested) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssssdd", $user_id, $ticket_number, $issue_type, $description, $location, $contact_number, $latitude, $longitude);
        
        if ($stmt->execute()) {
            logActivity($conn, $user_id, 'rsa_request', "Created RSA request: $ticket_number");
            $request_id = $stmt->insert_id;
            
            // Handle media uploads
            $media_files = [];
            if (isset($_FILES['request_media']) && !empty($_FILES['request_media']['name'][0])) {
                $upload_dir = CUSTOMER_UPLOAD_DIR;

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['request_media']['name'] as $i => $name) {
                    if ($_FILES['request_media']['error'][$i] == 0) {
                        $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
                        
                        if (in_array($file_ext, $allowed_exts)) {
                            $file_name = uniqid() . '_' . $name;
                            $public_path = '/uploads/customer/' . $file_name;

                            
                            if (move_uploaded_file($_FILES['request_media']['tmp_name'][$i], $upload_dir . $file_name)) {
                                $media_type = (in_array($file_ext, ['mp4', 'mov', 'avi'])) ? 'video' : 'image';
                                $stmt2 = $conn->prepare("
    INSERT INTO rsa_request_media (request_id, media_type, file_path, uploaded_by)
    VALUES (?, ?, ?, ?)
");
$stmt2->bind_param("issi", $request_id, $media_type, $public_path, $user_id);

                                $stmt2->execute();
                                $stmt2->close();
                            }
                        }
                    }
                }
            }
            
            // Calculate response time metrics
            $submission_time = date('Y-m-d H:i:s');
            
            // Store submission time for response tracking
            $stmt2 = $conn->prepare("INSERT INTO rsa_response_times (request_id, submission_time) VALUES (?, ?)");
            $stmt2->bind_param("is", $request_id, $submission_time);
            $stmt2->execute();
            $stmt2->close();
            
            $success = "RSA request submitted successfully! Your ticket number is: <strong>$ticket_number</strong><br>";
            $success .= "<small class='text-muted'>Estimated Time of Arrival (ETA): 30–45 minutes</small>";
            
            // Clear form and location data
            $_POST = [];
            unset($_SESSION['latitude'], $_SESSION['longitude']);
        } else {
            $error = 'Error submitting request. Please try again.';
        }
        $stmt->close();
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request RSA - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
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
        .user-location-info {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .response-time-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .location-loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .location-loading i {
            animation: spin 1s linear infinite;
        }
        .media-upload-section {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
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
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                    <h1 class="h2"><i class="fas fa-exclamation-circle"></i> Request Roadside Assistance</h1>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Emergency Roadside Assistance Request</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Issue Type <span class="text-danger">*</span></label>
                                        <select class="form-select" name="issue_type" required>
                                            <option value="">-- Select Issue Type --</option>
                                            <option value="Flat Tire">Flat Tire</option>
                                            <option value="Battery Dead">Battery Dead</option>
                                            <option value="Engine Overheating">Engine Overheating</option>
                                            <option value="Out of Fuel">Out of Fuel</option>
                                            <option value="Locked Out">Locked Out</option>
                                            <option value="Towing Service">Towing Service</option>
                                            <option value="Jump Start">Jump Start</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="description" rows="4" required placeholder="Please describe your issue in detail..."></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Location <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="location" rows="3" required placeholder="Please provide your exact location (address, landmarks, etc.)..."></textarea>
                                    </div>
                                    
                                    <div class="media-upload-section">
                                        <h6><i class="fas fa-camera"></i> Upload Photos/Videos (Optional)</h6>
                                        <p class="text-muted mb-3">Help us understand your issue better by uploading photos or videos of the problem</p>
                                        <input type="file" class="form-control" name="request_media[]" multiple accept="image/*,video/*">
                                        <div class="mt-2">
                                            <small class="text-muted">Supported formats: JPG, PNG, GIF, MP4, MOV, AVI (Max 10MB each)</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Location on Map</label>
                                        <div id="location-map" class="map-container"></div>
                                        <div class="location-loading" id="locationLoading">
                                            <i class="fas fa-spinner fa-2x"></i>
                                            <p class="mt-2">Getting your location...</p>
                                        </div>
                                        <div class="location-actions">
                                            <button type="button" class="btn btn-sm btn-info refresh-map-btn" id="refreshLocationMap">
                                                <i class="fas fa-sync-alt"></i> Refresh Map
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="getCurrentLocation">
                                                <i class="fas fa-crosshairs"></i> Use My Location
                                            </button>
                                        </div>
                                        <input type="hidden" name="latitude" id="latitude" value="">
                                        <input type="hidden" name="longitude" id="longitude" value="">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="contact_number" value="<?php echo $_SESSION['contact'] ?? ''; ?>" required placeholder="09123456789">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning btn-lg">
                                        <i class="fas fa-paper-plane"></i> Submit Request
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0 text-white"><i class="fas fa-info-circle"></i> Important Information</h5>
                            </div>
                            <div class="card-body">
                                <ul style="color: white;">
                                    <li>Please provide accurate location details - MANILA AREA ONLY</li>
                                    <li>Keep your phone accessible</li>
                                    <li>A technician will be assigned to your request</li>
                                    <li>You can track your request status in "My RSA Requests"</li>
                                    <li>For emergencies, call our hotline: 0912-345-6789</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Response Time Information -->
                        <div class="card mt-3">
                            <div class="card-header bg-info">
                                <h5 class="mb-0 text-white"><i class="fas fa-clock"></i> Response Time</h5>
                            </div>
                            <div class="card-body">
                                <div class="response-time-info">
                                    <h6>How We Measure Response Time</h6>
                                    <p class="mb-2">Response time is measured from when you submit your request until it's received by our admin team:</p>
                                    <ul class="mb-0">
                                        <li><strong>Submission:</strong> When you click "Submit Request"</li>
                                        <li><strong>Received:</strong> When admin first views your request</li>
                                        <li><strong>Target:</strong> Under 5 minutes for urgent requests</li>
                                    </ul>
                                    <div class="mt-3 p-2 bg-white rounded">
                                        <small class="text-muted">Your response time will appear in the My RSA Request Tab. Kindly check there for more details.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
    // Map initialization
    let map;
    let marker;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        map = L.map('location-map').setView([14.5995, 120.9842], 13); // Default to Manila
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add click event to map
        map.on('click', function(e) {
            updateLocation(e.latlng.lat, e.latlng.lng);
        });
        
        // Handle refresh map button
        document.getElementById('refreshLocationMap').addEventListener('click', function() {
            map.setView([14.5995, 120.9842], 13);
            if (marker) {
                map.removeLayer(marker);
            }
        });
        
        // Handle current location button
        document.getElementById('getCurrentLocation').addEventListener('click', function() {
            const loadingDiv = document.getElementById('locationLoading');
            loadingDiv.style.display = 'block';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        updateLocation(lat, lng);
                        map.setView([lat, lng], 15);
                        
                        // Reverse geocode to get address
                        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=en`)
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.display_name) {
                                    // Auto-fill location field with address
                                    document.querySelector('textarea[name="location"]').value = data.display_name;
                                    loadingDiv.style.display = 'none';
                                } else {
                                    loadingDiv.innerHTML = '<div class="alert alert-warning">Location found but address not available. Please enter your location manually.</div>';
                                }
                            })
                            .catch(error => {
                                console.error('Reverse geocoding error:', error);
                                loadingDiv.innerHTML = '<div class="alert alert-warning">Location found but address not available. Please enter your location manually.</div>';
                            });
                    },
                    function(error) {
                        loadingDiv.style.display = 'none';
                        alert('Error getting your location: ' + error.message);
                    }
                );
            } else {
                loadingDiv.style.display = 'none';
                alert('Geolocation is not supported by your browser.');
            }
        });
        
        // Handle location input change
        const locationInput = document.querySelector('textarea[name="location"]');
        locationInput.addEventListener('input', function() {
            const location = this.value.trim();
            if (location) {
                // Geocode the location
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(location)}&limit=1`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);
                            updateLocation(lat, lng);
                            map.setView([lat, lng], 15);
                        }
                    })
                    .catch(error => {
                        console.error('Geocoding error:', error);
                    });
            }
        });
    });
    
    function updateLocation(lat, lng) {
        // Update hidden inputs
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Update or create marker
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
    }
    </script>
</body>
</html>