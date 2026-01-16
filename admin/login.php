<?php
// admin/login.php
session_start();
require_once "../config.php";

if (isset($_SESSION["admin_logged_in"])) {
    header("Location: index.php");
    exit();
}

// Default admin credentials (ganti dengan database di production)
$adminCredentials = [
    "admin" => password_hash("admin123", PASSWORD_DEFAULT),
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    // Verify credentials
    if (
        $username === "admin" &&
        password_verify($password, $adminCredentials["admin"])
    ) {
        $_SESSION["admin_logged_in"] = true;
        $_SESSION["admin_name"] = "Super Admin";
        $_SESSION["admin_role"] = "super_admin";
        $_SESSION["admin_id"] = "admin-001";

        header("Location: index.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Parfum Store</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #8A2BE2;
            --secondary-color: #FF6B8B;
            --accent-color: #4CC9F0;
            --dark-color: #2D3047;
            --light-color: #F8F9FA;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background elements */
        .bg-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            background: var(--secondary-color);
            top: 70%;
            right: 15%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            background: var(--accent-color);
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(10deg);
            }
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            perspective: 1000px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform-style: preserve-3d;
            transition: transform 0.5s ease, box-shadow 0.5s ease;
        }

        .login-card:hover {
            transform: translateY(-10px) rotateX(2deg);
            box-shadow: 0 35px 60px -15px rgba(138, 43, 226, 0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(138, 43, 226, 0.3);
        }

        .logo-icon i {
            font-size: 36px;
            color: white;
        }

        .logo h3 {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 28px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo p {
            color: #6B7280;
            font-weight: 400;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            z-index: 2;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            background-color: #F9FAFB;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.1);
            background-color: white;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9CA3AF;
            cursor: pointer;
            z-index: 3;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 10px 20px rgba(138, 43, 226, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(138, 43, 226, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            margin-right: 10px;
        }

        .error-alert {
            background: linear-gradient(135deg, #FECACA, #FCA5A5);
            border: none;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 25px;
            color: #7F1D1D;
            font-weight: 500;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-in-out;
            box-shadow: 0 5px 15px rgba(254, 202, 202, 0.5);
        }

        .error-alert i {
            margin-right: 10px;
            font-size: 18px;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
        }

        .login-footer p {
            color: #6B7280;
            font-size: 13px;
            margin-bottom: 0;
        }

        .hint-box {
            background-color: #F0F9FF;
            border-radius: 10px;
            padding: 12px 15px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            border-left: 4px solid var(--accent-color);
        }

        .hint-box i {
            color: var(--accent-color);
            margin-right: 10px;
            font-size: 18px;
        }

        .hint-box p {
            color: #1E40AF;
            font-size: 13px;
            margin: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-card {
                padding: 35px 25px;
            }

            .logo-icon {
                width: 70px;
                height: 70px;
            }

            .logo-icon i {
                font-size: 30px;
            }

            .logo h3 {
                font-size: 24px;
            }
        }

        /* Loading animation */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Background shapes -->
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-spray-can-sparkles"></i>
                </div>
                <h3>Parfum Store</h3>
                <p>Admin Dashboard</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span id="btnText">Login ke Dashboard</span>
                    <div class="spinner" id="loadingSpinner" style="display: none;"></div>
                </button>

                <div class="hint-box">
                    <i class="fas fa-lightbulb"></i>
                    <p>Gunakan username: <strong>admin</strong> dan password: <strong>admin123</strong> untuk login</p>
                </div>
            </form>

            <div class="login-footer">
                <p>&copy; <?= date(
                    "Y",
                ) ?> Parfum Store. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = togglePassword.querySelector('i');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle eye icon
                if (type === 'password') {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                } else {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                }
            });

            // Form submission with loading animation
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');

            loginForm.addEventListener('submit', function() {
                // Show loading animation
                btnText.style.display = 'none';
                loadingSpinner.style.display = 'inline-block';
                loginBtn.disabled = true;

                // Change button text temporarily
                setTimeout(() => {
                    btnText.textContent = 'Mengautentikasi...';
                    btnText.style.display = 'inline';
                    loadingSpinner.style.display = 'none';
                }, 500);
            });

            // Add focus effects to inputs
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                // Add focus class to parent
                input.addEventListener('focus', function() {
                    this.parentElement.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.parentElement.classList.remove('focused');
                    }
                });

                // Add floating label effect
                if (input.value !== '') {
                    input.parentElement.parentElement.classList.add('focused');
                }
            });

            // Add some interactive effects to the card
            const card = document.querySelector('.login-card');
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) rotateX(2deg)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) rotateX(0)';
            });
        });
    </script>
</body>
</html>
