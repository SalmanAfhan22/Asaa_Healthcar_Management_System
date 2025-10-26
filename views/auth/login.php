<?php
require_once '../../config/config.php';

$error = '';
$success = '';

// Handle logout success message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = 'You have been successfully logged out.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required';
        } else {
            // Direct login processing
            global $db;

            try {
                $stmt = $db->prepare("
                    SELECT u.*, r.role_name, r.role_slug 
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'Invalid email or password';
                } elseif (!password_verify($password, $user['password_hash'])) {
                    $error = 'Invalid email or password';
                } elseif ($user['status'] !== 'ACTIVE') {
                    $error = 'Account is not active';
                } else {
                    // Successful login
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_role'] = $user['role_slug'];
                    $_SESSION['role_id'] = $user['role_id'];

                    session_regenerate_id(true);

                    // Redirect based on role
                    switch ($user['role_slug']) {
                        case 'admin':
                            header('Location: ../dashboard/admin.php');
                            break;
                        case 'doctor':
                            header('Location: ../dashboard/doctor.php');
                            break;
                        case 'staff':
                            header('Location: ../dashboard/staff.php');
                            break;
                        case 'patient':
                            header('Location: ../dashboard/patient.php');
                            break;
                        default:
                            header('Location: ../../index.php');
                    }
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Login failed due to system error';
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: #b2ebf2;
            --header-gradient: linear-gradient(135deg, #207d87 0%, #b2ebf2 100%);
            --navbar-dark: #207d87;
            --navbar-light: #e6f2f5;
            --gradient-bg: linear-gradient(135deg, #207d87 0%, #4dd0e1 50%, #b2ebf2 100%);
            --card-shadow: 0 20px 60px rgba(32, 125, 135, 0.15);
            --card-shadow-hover: 0 25px 80px rgba(32, 125, 135, 0.25);
        }

        body {
            background: var(--gradient-bg);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: linear-gradient(135deg,
                    rgba(32, 125, 135, 0.9) 0%,
                    rgba(77, 208, 225, 0.8) 50%,
                    rgba(178, 235, 242, 0.7) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: all 0.4s ease;
            max-width: 420px;
            width: 100%;
        }

        .login-card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-5px);
        }

        .login-header {
            background: var(--header-gradient);
            color: white;
            text-align: center;
            padding: 2.5rem 2rem 2rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .brand-logo {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .login-body {
            padding: 2.5rem 2rem;
            background: white;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .form-control-modern {
            border-radius: 12px;
            border: 2px solid #e6f2f5;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            background: #fafcfc;
            transition: all 0.3s ease;
        }

        .form-control-modern:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(32, 125, 135, 0.1);
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--primary-color);
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            background: rgba(32, 125, 135, 0.1);
            color: #196872;
        }

        .btn-login {
            background: var(--header-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.875rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            width: 100%;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(32, 125, 135, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .quick-login-section {
            background: linear-gradient(135deg, #f8fdfe 0%, #e6f2f5 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid rgba(32, 125, 135, 0.1);
        }

        .demo-account {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: white;
            border: 1px solid rgba(32, 125, 135, 0.1);
            border-radius: 10px;
            margin: 0.5rem 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .demo-account:hover {
            border-color: var(--primary-color);
            background: var(--accent-color);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(32, 125, 135, 0.2);
        }

        .demo-account-info h6 {
            margin-bottom: 0.2rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .demo-account-email {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .quick-fill-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .quick-fill-btn:hover {
            background: #196872;
            transform: scale(1.05);
        }

        .register-link {
            background: linear-gradient(135deg, var(--primary-color), #4dd0e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 600;
            text-decoration: none;
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: #6c757d;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #dee2e6, transparent);
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            font-size: 0.9rem;
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .floating-elements::before,
        .floating-elements::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .floating-elements::before {
            width: 200px;
            height: 200px;
            top: -100px;
            right: -100px;
            animation: float 6s ease-in-out infinite;
        }

        .floating-elements::after {
            width: 150px;
            height: 150px;
            bottom: -75px;
            left: -75px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .form-control-modern.with-toggle {
            padding-right: 45px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .login-card {
                border-radius: 20px;
                margin: 10px;
            }

            .login-header {
                padding: 2rem 1.5rem 1.5rem 1.5rem;
            }

            .login-body {
                padding: 2rem 1.5rem;
            }

            .brand-logo {
                font-size: 2.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="floating-elements"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="login-card mx-auto">
                        <div class="login-header">
                            <div class="brand-logo">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h2 class="mb-2">ASAA Healthcare</h2>
                            <p class="mb-0 opacity-90">Caring for Your Health & Well-being</p>
                        </div>

                        <div class="login-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: linear-gradient(135deg, #ffebee, #ffcdd2);">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                    <strong><?= htmlspecialchars($error) ?></strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: linear-gradient(135deg, #e8f5e8, #c8e6c9);">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <strong><?= htmlspecialchars($success) ?></strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="loginForm">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" class="form-control form-control-modern" id="email"
                                        name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                        placeholder="Enter your email address" required autocomplete="email">
                                </div>

                                <div class="form-group">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i> Password
                                    </label>
                                    <div class="password-container">
                                        <input type="password" class="form-control form-control-modern with-toggle"
                                            id="password" name="password" placeholder="Enter your password"
                                            required autocomplete="current-password">
                                        <i class="fas fa-eye password-toggle" id="togglePassword"
                                            onclick="togglePasswordVisibility()" title="Show Password"></i>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-login" id="loginBtn">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        <span class="btn-text">Sign In</span>
                                    </button>
                                </div>
                            </form>

                            <div class="divider">
                                <span>or</span>
                            </div>

                            <div class="text-center">
                                <p class="mb-3">New to ASAA Healthcare?</p>
                                <a href="register.php" class="register-link text-decoration-none">
                                    <i class="fas fa-user-plus me-1"></i>
                                    <strong>Create Patient Account</strong>
                                </a>
                            </div>

                            <!-- Demo Accounts Section -->
                            <div class="quick-login-section">
                                <h6 class="text-center mb-3" style="color: var(--primary-color); font-weight: 600;">
                                    <i class="fas fa-rocket"></i> Quick Demo Access
                                </h6>

                                <div class="demo-account" onclick="fillLogin('admin@asaahealthcare.com', 'Admin Panel')">
                                    <div class="demo-account-info">
                                        <h6><i class="fas fa-user-shield text-success"></i> Admin Dashboard</h6>
                                        <div class="demo-account-email">admin@asaahealthcare.com</div>
                                    </div>
                                    <button type="button" class="quick-fill-btn">
                                        <i class="fas fa-magic-wand-sparkles"></i>
                                    </button>
                                </div>

                                <div class="demo-account" onclick="fillLogin('sarah.johnson@asaahealthcare.com', 'Doctor Portal')">
                                    <div class="demo-account-info">
                                        <h6><i class="fas fa-stethoscope text-success"></i> Doctor Portal</h6>
                                        <div class="demo-account-email">sarah.johnson@asaahealthcare.com</div>
                                    </div>
                                    <button type="button" class="quick-fill-btn">
                                        <i class="fas fa-magic-wand-sparkles"></i>
                                    </button>
                                </div>

                                <div class="text-center mt-3">
                                    <small style="color: var(--primary-color); font-weight: 500;">
                                        <i class="fas fa-key"></i> Demo Password: <code>password123</code>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility with enhanced animation
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                toggleIcon.title = 'Hide Password';
                toggleIcon.style.color = '#196872';
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleIcon.title = 'Show Password';
                toggleIcon.style.color = 'var(--primary-color)';
            }

            // Add a small bounce animation
            toggleIcon.style.transform = 'translateY(-50%) scale(0.9)';
            setTimeout(() => {
                toggleIcon.style.transform = 'translateY(-50%) scale(1)';
            }, 150);
        }

        // Enhanced demo account filling with animation
        function fillLogin(email, accountType) {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');

            // Smooth typing animation
            emailField.value = '';
            passwordField.value = '';

            let emailIndex = 0;
            const emailTyping = setInterval(() => {
                if (emailIndex < email.length) {
                    emailField.value += email[emailIndex];
                    emailIndex++;
                } else {
                    clearInterval(emailTyping);

                    // Start password typing
                    let passIndex = 0;
                    const password = 'password123';
                    const passwordTyping = setInterval(() => {
                        if (passIndex < password.length) {
                            passwordField.value += password[passIndex];
                            passIndex++;
                        } else {
                            clearInterval(passwordTyping);

                            // Flash success colors
                            emailField.style.background = 'linear-gradient(135deg, #e8f5e8, #c8e6c9)';
                            passwordField.style.background = 'linear-gradient(135deg, #e8f5e8, #c8e6c9)';

                            // Focus on login button with glow effect
                            loginBtn.style.boxShadow = '0 0 20px rgba(32, 125, 135, 0.5)';

                            setTimeout(() => {
                                emailField.style.background = '';
                                passwordField.style.background = '';
                                loginBtn.style.boxShadow = '';
                            }, 2000);
                        }
                    }, 50);
                }
            }, 80);

            // Show toast notification
            showToast(`✨ ${accountType} credentials loaded!`, 'success');
        }

        // Form submission with loading animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('.btn-text');

            btnText.innerHTML = 'Signing you in...';
            loginBtn.insertAdjacentHTML('afterbegin', '<i class="fas fa-spinner fa-spin me-2"></i>');
            loginBtn.disabled = true;
            loginBtn.style.background = 'linear-gradient(135deg, #196872, #4dd0e1)';
        });

        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();

            const toastId = 'toast_' + Date.now();
            const bgColor = type === 'success' ? 'var(--primary-color)' : '#6c757d';

            toastContainer.insertAdjacentHTML('beforeend', `
                <div class="toast align-items-center text-white border-0" role="alert" 
                     style="background: ${bgColor}; border-radius: 12px;" id="${toastId}">
                    <div class="d-flex">
                        <div class="toast-body fw-bold">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                                data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `);

            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();

            setTimeout(() => {
                document.getElementById(toastId)?.remove();
            }, 5000);
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1100';
            document.body.appendChild(container);
            return container;
        }

        // Keyboard shortcuts with feedback
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key === '1') {
                e.preventDefault();
                fillLogin('admin@asaahealthcare.com', 'Admin Panel');
            }
            if (e.altKey && e.key === '2') {
                e.preventDefault();
                fillLogin('sarah.johnson@asaahealthcare.com', 'Doctor Portal');
            }
            if (e.altKey && e.key === '3') {
                e.preventDefault();
                fillLogin('michael.chen@asaahealthcare.com', 'Doctor Portal');
            }
        });

        // Auto-dismiss alerts and focus management
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();

            // Auto-dismiss alerts after 6 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (bootstrap.Alert.getOrCreateInstance) {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                        bsAlert.close();
                    }
                });
            }, 6000);
        });

        // Add subtle parallax effect on mouse move
        document.addEventListener('mousemove', function(e) {
            const card = document.querySelector('.login-card');
            const x = (e.clientX / window.innerWidth - 0.5) * 20;
            const y = (e.clientY / window.innerHeight - 0.5) * 20;

            card.style.transform = `perspective(1000px) rotateY(${x * 0.1}deg) rotateX(${y * 0.1}deg)`;
        });

        // Reset transform when mouse leaves
        document.addEventListener('mouseleave', function() {
            const card = document.querySelector('.login-card');
            card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
        });
    </script>
</body>

</html>