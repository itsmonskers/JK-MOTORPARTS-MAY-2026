<?php
require_once '../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../dashboard/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $contact = sanitizeInput($_POST['contact'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    if (empty($name) || empty($email) || empty($password) || empty($contact)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $conn = getDBConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists. Please use a different email.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, contact, address) VALUES (?, ?, ?, 'customer', ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $contact, $address);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                logActivity($conn, $user_id, 'registration', 'New user registered');
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at top, #192a56 0%, #0f172a 55%, #0b1120 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #e2e8f0;
        }
        .register-container {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 15px;
            box-shadow: 0 25px 65px rgba(4, 6, 14, 0.65);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .register-header {
            background: linear-gradient(135deg, #ffb347 0%, #ff7300 60%, #e85719 100%);
            color: #0f172a;
            padding: 35px 30px 25px;
            text-align: center;
        }
        .register-header img {
            max-width: 140px;
            margin-bottom: 15px;
        }
        .register-body {
            padding: 30px;
        }
        .form-control, .input-group-text, textarea {
            background: rgba(15,23,42,0.75);
            border: 1px solid rgba(255,255,255,0.08);
            color: #f8fafc;
        }
        .input-group-text {
            color: #ffb347;
        }
        .form-control:focus, textarea:focus {
            border-color: #ff8a00;
            box-shadow: 0 0 0 0.2rem rgba(255,138,0,0.25);
            background-color: rgba(15,23,42,0.85);
        }
        .btn-primary {
            background: linear-gradient(120deg, #ffb347 0%, #ff7300 50%, #e85719 100%);
            border: none;
            color: #0f172a;
            font-weight: 600;
            box-shadow: 0 15px 35px rgba(233,87,25,0.35);
        }
        .btn-primary:hover {
            background: linear-gradient(120deg, #ffd56f 0%, #ff8a00 45%, #f25c05 100%);
        }
        .alert {
            border: none;
        }
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.15);
            color: #fecdd3;
        }
        .alert-success {
            background-color: rgba(34, 197, 94, 0.18);
            color: #bbf7d0;
        }
        a {
            color: #ffb347;
        }
        a:hover {
            color: #ffd56f;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <img src="../jk%20motors.png" alt="JK Motors logo">
            <h2><i class="fas fa-user-plus"></i> Register</h2>
            <p class="mb-0">Create your account</p>
        </div>
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" name="contact" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

