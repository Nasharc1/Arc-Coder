// login.php
<?php
require_once 'config/config.php'; 
require_once 'includes/auth.php';

$error = '';
if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Umoja Junior Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border: none;
            border-bottom: 2px solid #eee;
            border-radius: 0;
            padding: 1rem 0;
            background: transparent;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-bottom-color: #667eea;
            box-shadow: none;
            background: transparent;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: transform 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
        .input-group {
            position: relative;
            margin-bottom: 2rem;
        }
        .input-group i {
            position: absolute;
            left: 0;
            top: 1rem;
            color: #667eea;
            z-index: 10;
        }
        .input-group input {
            padding-left: 2rem;
        }
        .school-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="school-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3 class="mb-0">Umoja Junior Academy</h3>
                <p class="mb-0 opacity-75">School Management System</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                    </small>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Demo Accounts:<br>
                        <strong>Admin:</strong> admin / admin123<br>
                        <strong>Teacher:</strong> mary.wanjiku / teacher123<br>
                        <strong>Student:</strong> grace.mwangi / student123
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>