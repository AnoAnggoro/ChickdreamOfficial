<?php
require_once 'functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_POST) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (login($email, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Email atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modern-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="modern-login-container">
        <!-- Left Side - Login Form -->
        <div class="login-section">
            <div class="login-content">
                <div class="login-header">
                    <div class="header-with-logo">
                        <!-- Logo Section -->
                        <div class="login-logo">
                            <div class="logo-circle-small">
                                <img src="logo/IMG_2615.JPG" alt="PT. CHICKDREAM Logo" class="logo-image-small">
                            </div>
                        </div>
                        
                        <div class="header-text">
                            <h1>Selamat Datang</h1>
                            <p>Masuk ke sistem manajemen </p>
                            <p>PT. CHICKDREAM</p>
                        </div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label>Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password-input" name="password" class="form-control" placeholder="Masukkan password Anda" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <span>MASUK</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <div class="login-footer">
                        <p>&copy; 2025 PT. CHICKDREAM. All rights reserved.</p>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Right Side - Simple Background -->
        <div class="logo-section">
            <div class="logo-content">
                <div class="company-branding">
                    <h2>PT. CHICKDREAM</h2>
                    <p>Sistem Manajemen Karyawan</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password-input');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput && passwordIcon) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    passwordIcon.classList.remove('fa-eye');
                    passwordIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    passwordIcon.classList.remove('fa-eye-slash');
                    passwordIcon.classList.add('fa-eye');
                }
            }
        }
        
        // Add loading animation to login button
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.querySelector('.login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    const button = document.querySelector('.btn-login');
                    if (button) {
                        const originalContent = button.innerHTML;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                        button.disabled = true;
                        
                        // Reset button if there's an error (form doesn't actually submit)
                        setTimeout(() => {
                            if (button.disabled) {
                                button.innerHTML = originalContent;
                                button.disabled = false;
                            }
                        }, 5000);
                    }
                });
            }
        });
    </script>
</body>
</html>
