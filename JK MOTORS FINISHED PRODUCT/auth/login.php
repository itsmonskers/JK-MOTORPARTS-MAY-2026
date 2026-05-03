<?php
require_once '../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../dashboard/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, name, email, password, role, contact FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['contact'] = $user['contact'];
                
                logActivity($conn, $user['id'], 'login', 'User logged in');
                
                header('Location: ../dashboard/index.php');
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
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
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at top, #192a56 0%, #0f172a 55%, #0b1120 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
        }
        .login-container {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 15px;
            box-shadow: 0 25px 65px rgba(4, 6, 14, 0.65);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .login-header {
            background: linear-gradient(135deg, #ffb347 0%, #ff7300 60%, #e85719 100%);
            color: #0f172a;
            padding: 35px 30px 25px;
            text-align: center;
        }
        .login-header img {
            max-width: 140px;
            margin-bottom: 15px;
        }
        .login-body {
            padding: 30px;
        }
        .form-control, .input-group-text {
            background: rgba(15,23,42,0.75);
            border: 1px solid rgba(255,255,255,0.08);
            color: #f8fafc;
        }
        .input-group-text {
            color: #ffb347;
        }
        .form-control:focus {
            border-color: #ff8a00;
            box-shadow: 0 0 0 0.2rem rgba(255,138,0,0.25);
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
        .btn-outline-secondary {
            border-color: rgba(255,255,255,0.35);
            color: #f8fafc;
            background: rgba(15,23,42,0.65);
        }
        .btn-outline-secondary:hover {
            background: rgba(255,255,255,0.1);
            color: #ffb347;
            border-color: #ffb347;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../jk%20motors.png" alt="JK Motors logo">
            <h2><?php echo SITE_NAME; ?></h2>
            <p class="mb-0">Smart Solutions System</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="text-center">
                    <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>
            
            <hr class="my-4">
            <div class="text-center text-muted">
                <small>Default Admin: admin@jkmotorparts.com / admin123</small><br>
                <small>Default Technician: technician@jkmotorparts.com / admin123</small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>

