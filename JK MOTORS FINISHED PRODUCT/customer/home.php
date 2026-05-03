<?php
require_once '../config/config.php';
requireCustomer();

$conn = getDBConnection();

// Get customer data
$user = $conn->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetch_assoc();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        /* Make all text white for readability */
        main, main *, 
        .card, .card *, 
        .card-header, .card-header *,
        .card-body, .card-body *,
        .card-title, .card-text,
        h1, h2, h3, h4, h5, h6,
        p, span, div, strong, small,
        .section-title,
        .section-content,
        .homepage-section {
            color: #ffffff !important;
        }
        
        .homepage-hero {
            text-align: center;
            padding: 4rem 2rem;
            margin-bottom: 3rem;
        }
        
        .homepage-hero h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 2rem;
            line-height: 1.2;
        }
        
        .homepage-section {
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        
        .homepage-section h2 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        
        .homepage-section p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 1rem;
        }
        
        .hero-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            margin: 2rem 0;
        }
        
        .section-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }
        
        .action-buttons {
            margin: 2rem 0;
        }
        
        .action-buttons .btn {
            margin: 0.5rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .rewards-section {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, rgba(255, 138, 0, 0.1) 0%, rgba(232, 87, 25, 0.1) 100%);
            border-radius: 20px;
            margin: 3rem 0;
        }
        
        .rewards-section h2 {
            margin-bottom: 1.5rem;
        }
        
        .rewards-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .rewards-btn {
            padding: 1rem 3rem;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        /* Footer Styles */
        .homepage-footer {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(6, 11, 25, 0.98) 100%);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem 2rem 2rem;
            margin-top: 4rem;
            position: relative;
        }
        
        .homepage-footer * {
            color: #ffffff !important;
        }
        
        .footer-contact {
            margin-bottom: 2rem;
        }
        
        .footer-contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .footer-contact-item i {
            margin-right: 1rem;
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }
        
        .footer-contact-item a {
            text-decoration: underline;
            color: #ffffff !important;
        }
        
        .footer-icons {
            text-align: right;
            margin-bottom: 2rem;
        }
        
        .footer-icons i {
            font-size: 2rem;
            margin: 0 0.5rem;
            opacity: 0.8;
        }
        
        .footer-icons .fa-motorcycle {
            font-size: 3rem;
            margin-left: 1rem;
        }
        
        .footer-links {
            text-align: right;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-links a {
            color: #ffffff !important;
            text-decoration: none;
            margin-left: 1.5rem;
            font-size: 0.95rem;
            cursor: pointer;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
            color: #ffb347 !important;
        }
        
        /* Terms Modal Styles */
        .terms-modal .modal-content {
            background: rgba(15, 23, 42, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .terms-modal .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .terms-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
            line-height: 1.8;
        }
        
        .terms-modal .modal-body h3 {
            color: #ffb347 !important;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .terms-modal .modal-body h4 {
            color: #ffffff !important;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .terms-modal .modal-body p,
        .terms-modal .modal-body ul,
        .terms-modal .modal-body li {
            color: #e2e8f0 !important;
        }
        
        .terms-modal .modal-body ul {
            margin-left: 2rem;
            margin-bottom: 1rem;
        }
        
        .terms-modal .modal-body strong {
            color: #ffffff !important;
        }
        
        .terms-modal .btn-close {
            filter: invert(1);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Hero Section -->
                <div class="homepage-hero">
                    <h1>Your one-stop solution for<br>quality motor parts and emergency roadside assistance</h1>
                    
                    <div class="action-buttons">
                        <a href="../dashboard/index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="../rsa/request.php" class="btn btn-danger btn-lg">
                            <i class="fas fa-exclamation-circle"></i> Request Assistance
                        </a>
                        <a href="#contact-footer" class="btn btn-success btn-lg" onclick="document.getElementById('contact-footer').scrollIntoView({behavior: 'smooth'}); return false;">
                            <i class="fas fa-phone"></i> Contact Us
                        </a>
                    </div>
                    
                    <img src="../jk motor 1.png" alt="JK Motorparts" class="hero-image">
                </div>
                
                <!-- About Us Section -->
                <div class="homepage-section">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2>About Us</h2>
                            <p>Jk Motorparts is a trusted provider of high-quality motorcycle parts and accessories, serving both regular customers and motorbike enthusiasts in the Philippines. We also offer quick-response roadside assistance services within Metro Manila.</p>
                            <p>Our mission is to deliver reliable services, maintain accurate inventory, and build long-term customer relationships through innovation and convenience.</p>
                        </div>
                        <div class="col-md-6">
                            <img src="../jk motor 2.png" alt="Motorcycle Parts" class="section-image">
                        </div>
                    </div>
                </div>
                
                <!-- Emergency Roadside Assistance Section -->
                <div class="homepage-section">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <img src="../jk motor 3.jpg" alt="Roadside Assistance" class="section-image">
                        </div>
                        <div class="col-md-6">
                            <h2>Emergency Roadside Assistance</h2>
                            <p>Stuck on the road? Our Emergency Assistance Module allows you to request help instantly by submitting a quick form. Our team will receive the request and dispatch support in real time.</p>
                            
                            <div class="mt-4">
                                <h4 class="mb-3">Common Issues We Assist With:</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li class="mb-2"><i class="fas fa-car-side fa-fw text-warning"></i> Flat Tire</li>
                                            <li class="mb-2"><i class="fas fa-exclamation-triangle fa-fw text-warning"></i> Brake Failure</li>
                                            <li class="mb-2"><i class="fas fa-link fa-fw text-warning"></i> Chain</li>
                                            <li class="mb-2"><i class="fas fa-battery-half fa-fw text-warning"></i> Dead Battery</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li class="mb-2"><i class="fas fa-gas-pump fa-fw text-warning"></i> Fuel Delivery</li>
                                            <li class="mb-2"><i class="fas fa-ellipsis-h fa-fw text-warning"></i> Others</li>
                                            <li class="mb-2"><i class="fas fa-cog fa-fw text-warning"></i> Engine</li>
                                            <li class="mb-2"><i class="fas fa-thermometer-half fa-fw text-warning"></i> Overheating</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Earn Rewards Section -->
                <div class="rewards-section">
                    <h2>Earn Rewards Every Time You Purchase</h2>
                    <p>With our customer rewards system, you earn points for every transaction.<br>These points can be used for discounts or freebies!</p>
                    <a href="../rewards/redeem.php" class="btn btn-success btn-lg rewards-btn">
                        <i class="fas fa-gift"></i> View my Rewards
                    </a>
                </div>
                
                <!-- Footer Section -->
                <footer id="contact-footer" class="homepage-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="footer-contact">
                                <div class="footer-contact-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>1000 int 2 bohol st. sampaloc, Manila, Philippines, 1008</span>
                                </div>
                                <div class="footer-contact-item">
                                    <i class="fas fa-phone"></i>
                                    <span>+63 95 67 447 531</span>
                                </div>
                                <div class="footer-contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <a href="mailto:jpaul071891@gmail.com">jpaul071891@gmail.com</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="footer-icons">
                                <i class="fas fa-bicycle"></i>
                                <i class="fas fa-bicycle"></i>
                                <i class="fas fa-bicycle"></i>
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div class="footer-links">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </div>
    
    <!-- Terms and Conditions Modal -->
    <div class="modal fade terms-modal" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="termsModalLabel">Terms and Conditions</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                    
                    <p>Welcome to JK MOTOR PARTS ("we", "our", "us"). By accessing or using our website, you agree to be bound by these Terms and Conditions. Please read them carefully. If you do not agree with any part of these Terms, you must discontinue using the website.</p>
                    
                    <h3>1. General Information</h3>
                    <p>This website is operated by JK MOTOR PARTS, located at:</p>
                    <p><strong>1000 Int 2 Bohol St., Sampaloc, Manila, Philippines, 1008</strong></p>
                    <p>For inquiries or support, you may contact us at:</p>
                    <p><strong>Phone:</strong> +63 95 67 447 531</p>
                    <p><strong>Email:</strong> jpaul071891@gmail.com</p>
                    
                    <h3>2. Use of the Website</h3>
                    <p>By using our website, you agree that you will:</p>
                    <ul>
                        <li>Access and use the site only for lawful purposes.</li>
                        <li>Not engage in any activity that disrupts or damages the website.</li>
                        <li>Not attempt to gain unauthorized access to any portion of the website, servers, or systems connected to it.</li>
                    </ul>
                    <p>We reserve the right to refuse service or access to any user who violates these Terms.</p>
                    
                    <h3>3. Products and Services</h3>
                    <p>JK MOTOR PARTS specializes in:</p>
                    <ul>
                        <li>Selling motorcycle parts, tools, and accessories</li>
                        <li>Motorcycle repair, maintenance, and troubleshooting services</li>
                    </ul>
                    <p>All product descriptions, pricing, images, and availability posted on the website are subject to change at any time without prior notice. Although we aim for accuracy, we do not guarantee that product images or descriptions are perfectly precise due to variations in lighting, screen display, and supplier updates.</p>
                    
                    <h3>4. Orders and Payments</h3>
                    <p>When placing an order on the website:</p>
                    <ul>
                        <li>You agree to provide accurate, complete, and current information.</li>
                        <li>We reserve the right to accept or decline any order for any reason.</li>
                        <li>Prices are displayed in Philippine Peso (PHP).</li>
                        <li>In case of errors in pricing or product information, we reserve the right to correct or cancel orders before shipment.</li>
                        <li>Payment methods may vary and can include cash on delivery, bank transfer, e-wallet payments, or other available channels.</li>
                        <li>Orders are considered confirmed only after you receive a formal confirmation notification from us.</li>
                    </ul>
                    
                    <h3>5. Service Appointments</h3>
                    <p>For motorcycle repair or maintenance services:</p>
                    <ul>
                        <li>Schedules are subject to availability and may change due to workload or unforeseen circumstances.</li>
                        <li>Customers must provide accurate motorcycle details and necessary documents (e.g., OR/CR) when required.</li>
                        <li>JK MOTOR PARTS is not responsible for delays caused by weather, transport issues, or supplier delays.</li>
                    </ul>
                    
                    <h3>6. Shipping, Delivery, and Pickup</h3>
                    <h4>For product orders:</h4>
                    <ul>
                        <li>Delivery timelines depend on the partnered couriers and are not fully controlled by JK MOTOR PARTS.</li>
                        <li>We are not liable for delays, damage, or loss caused by courier mishandling.</li>
                        <li>Incorrect delivery information provided by customers may result in delays or failed delivery.</li>
                    </ul>
                    <h4>For store pickups:</h4>
                    <ul>
                        <li>Items must be claimed within business hours at our Sampaloc, Manila location.</li>
                        <li>Bring your valid ID and order confirmation during pickup.</li>
                    </ul>
                    
                    <h3>7. Warranty and Returns</h3>
                    <p>Warranty terms depend on the product manufacturer and the nature of the service performed.</p>
                    <p>We accept returns or exchanges only if:</p>
                    <ul>
                        <li>The item delivered is defective or damaged upon arrival.</li>
                        <li>The incorrect item was sent.</li>
                        <li>The customer notifies us within 7 days upon receiving the product.</li>
                        <li>The item is unused, complete, and in its original packaging.</li>
                    </ul>
                    <p>Labor fees for motorcycle repairs and maintenance are non-refundable.</p>
                    
                    <h3>8. Intellectual Property Rights</h3>
                    <p>All website content — including text, photos, logos, product images, graphics, and layout — belongs to JK MOTOR PARTS or is used with permission.</p>
                    <p>Any unauthorized reproduction, distribution, or use of website content is strictly prohibited.</p>
                    
                    <h3>9. User Accounts</h3>
                    <p>If the website offers account creation, users agree to:</p>
                    <ul>
                        <li>Keep their login details confidential.</li>
                        <li>Notify us immediately of any unauthorized access to their account.</li>
                        <li>Accept full responsibility for all actions performed under their account.</li>
                    </ul>
                    <p>We may suspend or remove accounts that violate these Terms.</p>
                    
                    <h3>10. Limitation of Liability</h3>
                    <p>JK MOTOR PARTS is not liable for:</p>
                    <ul>
                        <li>Any direct or indirect damages arising from the use or inability to use the website.</li>
                        <li>Loss of data, profits, or business opportunities.</li>
                        <li>Website errors, downtime, or security breaches beyond our control.</li>
                    </ul>
                    <p>All services and products are provided "as is" and "as available."</p>
                    
                    <h3>11. Privacy Policy</h3>
                    <p>Your use of the website is also governed by our Privacy Policy, which is designed to comply with the Data Privacy Act of 2012 (RA 10173).</p>
                    <p>We ensure that any personal information collected is stored and processed securely.</p>
                    
                    <h3>12. Changes to These Terms</h3>
                    <p>JK MOTOR PARTS may update or modify these Terms at any time.</p>
                    <p>Changes take effect immediately upon being posted on the website.</p>
                    
                    <h3>13. Governing Law</h3>
                    <p>These Terms and Conditions are governed by the laws of the Republic of the Philippines, and any disputes will be handled through the appropriate Philippine courts.</p>
                    
                    <h3>14. Contact Information</h3>
                    <p>For questions or concerns regarding these Terms, please contact:</p>
                    <p><strong>JK MOTOR PARTS</strong><br>
                    1000 Int 2 Bohol St., Sampaloc, Manila, Philippines, 1008<br>
                    <strong>Phone:</strong> +63 95 67 447 531<br>
                    <strong>Email:</strong> jpaul071891@gmail.com</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Privacy Policy Modal (Basic) -->
    <div class="modal fade terms-modal" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="privacyModalLabel">Privacy Policy</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                    <p>JK MOTOR PARTS respects your privacy and is committed to protecting your personal information in accordance with the Data Privacy Act of 2012 (RA 10173).</p>
                    <p>For questions or concerns regarding our Privacy Policy, please contact us at:</p>
                    <p><strong>JK MOTOR PARTS</strong><br>
                    1000 Int 2 Bohol St., Sampaloc, Manila, Philippines, 1008<br>
                    <strong>Phone:</strong> +63 95 67 447 531<br>
                    <strong>Email:</strong> jpaul071891@gmail.com</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
