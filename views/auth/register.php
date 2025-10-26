<?php
require_once '../../config/config.php';

$error = '';
$success = '';
$autoRedirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed';
    } else {
        // Validate input
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $mobile = trim($_POST['mobile_number'] ?? '');
        $nic = trim($_POST['nic'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $dateOfBirth = $_POST['date_of_birth'] ?? '';

        // Enhanced server-side validation
        if (
            empty($firstName) || empty($lastName) || empty($email) || empty($password) ||
            empty($mobile) || empty($nic) || empty($gender)
        ) {
            $error = 'All required fields must be filled';
        } elseif (strlen($firstName) < 2 || strlen($lastName) < 2) {
            $error = 'First name and last name must be at least 2 characters';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } elseif (strlen($mobile) !== 10 || !ctype_digit($mobile) || !preg_match('/^07[0-8][0-9]{7}$/', $mobile)) {
            $error = 'Mobile number must be exactly 10 digits starting with 07 (e.g., 0771234567)';
        } elseif (!preg_match('/^([0-9]{9}[VX]|[0-9]{12})$/', strtoupper($nic))) {
            $error = 'Invalid NIC format (e.g., 123456789V or 199912345678)';
        } else {
            // Direct database registration
            try {
                global $db;

                // Check if email already exists
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $result = $stmt->fetch();

                if ($result['count'] > 0) {
                    $error = 'Email address already exists';
                } else {
                    // Check if NIC already exists
                    $nicStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE nic = ?");
                    $nicStmt->execute([strtoupper($nic)]);
                    $nicResult = $nicStmt->fetch();

                    if ($nicResult['count'] > 0) {
                        $error = 'NIC number already exists';
                    } else {
                        // Check if mobile already exists
                        $mobileStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE mobile_number = ?");
                        $mobileStmt->execute([$mobile]);
                        $mobileResult = $mobileStmt->fetch();

                        if ($mobileResult['count'] > 0) {
                            $error = 'Mobile number already exists';
                        } else {
                            // Create patient account
                            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                            $createStmt = $db->prepare("
                                INSERT INTO users (role_id, first_name, last_name, email, password_hash, 
                                                 mobile_number, nic, gender, status, created_at)
                                VALUES (4, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NOW())
                            ");

                            $created = $createStmt->execute([
                                $firstName,
                                $lastName,
                                $email,
                                $passwordHash,
                                $mobile,
                                strtoupper($nic),
                                $gender
                            ]);

                            if ($created) {
                                $userId = $db->lastInsertId();

                                // Create user profile if date of birth provided
                                if (!empty($dateOfBirth)) {
                                    try {
                                        $profileStmt = $db->prepare("
                                            INSERT INTO user_profiles (user_id, date_of_birth, created_at)
                                            VALUES (?, ?, NOW())
                                        ");
                                        $profileStmt->execute([$userId, $dateOfBirth]);
                                    } catch (Exception $e) {
                                        error_log("Profile creation failed: " . $e->getMessage());
                                    }
                                }

                                $success = 'Welcome to ASAA Healthcare! Your account has been created successfully.';
                                $autoRedirect = true;
                                $_POST = [];
                            } else {
                                $error = 'Registration failed. Please try again.';
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = 'Registration failed: ' . $e->getMessage();
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
    <title>Join ASAA Healthcare - Patient Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: #b2ebf2;
            --header-gradient: linear-gradient(135deg, #207d87 0%, #4dd0e1 50%, #b2ebf2 100%);
            --success-gradient: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            --card-shadow: 0 20px 60px rgba(32, 125, 135, 0.15);
            --navbar-dark: #207d87;
            --navbar-light: #e6f2f5;
        }

        body {
            background: var(--header-gradient);
            min-height: 100vh;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .register-container {
            display: flex;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            transition: all 0.4s ease;
        }

        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 80px rgba(32, 125, 135, 0.2);
        }

        .register-header {
            background: var(--success-gradient);
            color: white;
            text-align: center;
            padding: 2.5rem 2rem 2rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 4s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .register-body {
            padding: 2rem;
            background: white;
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
            outline: none;
        }

        .form-control-modern.is-valid {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8fff8, #f0fff0);
        }

        .form-control-modern.is-invalid {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff8f8, #ffe6e6);
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
            border-radius: 50%;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            background: rgba(32, 125, 135, 0.15);
            color: #145c64;
            transform: translateY(-50%) scale(1.1);
        }

        .with-toggle {
            padding-right: 50px;
        }

        .strength-meter {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-meter-fill {
            height: 100%;
            transition: all 0.4s ease;
            border-radius: 3px;
        }

        .strength-weak {
            background: linear-gradient(90deg, #dc3545, #e74c3c);
            width: 25%;
        }

        .strength-fair {
            background: linear-gradient(90deg, #fd7e14, #ff8c00);
            width: 50%;
        }

        .strength-good {
            background: linear-gradient(90deg, #ffc107, #ffca28);
            width: 75%;
        }

        .strength-strong {
            background: linear-gradient(90deg, #28a745, #20c997);
            width: 100%;
        }

        .btn-register {
            background: var(--success-gradient);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .btn-register:disabled {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            transform: none;
            box-shadow: none;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .success-message {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            margin: 1rem 0;
        }

        .validation-message {
            font-size: 0.85rem;
            margin-top: 6px;
            font-weight: 500;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .register-card {
                border-radius: 20px;
                margin: 10px;
            }

            .register-header {
                padding: 2rem 1.5rem 1.5rem 1.5rem;
            }

            .register-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="register-card">
                        <div class="register-header">
                            <h3><i class="fas fa-user-plus me-2"></i>Join ASAA Healthcare</h3>
                            <p class="mb-0 opacity-90">Create Your Patient Account Today</p>
                        </div>

                        <div class="register-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert"
                                    style="border-radius: 12px; border: none; background: linear-gradient(135deg, #ffebee, #ffcdd2);">
                                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                    <strong><?= htmlspecialchars($error) ?></strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="success-message">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h4 class="text-success"><?= htmlspecialchars($success) ?></h4>
                                    <div class="countdown" id="countdown">
                                        <i class="fas fa-clock me-2"></i>Redirecting to login in <span id="timer">5</span> seconds
                                    </div>
                                    <div class="mt-3">
                                        <a href="login.php" class="btn btn-success btn-lg me-2">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login Now
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary" onclick="cancelRedirect()">
                                            <i class="fas fa-times me-1"></i>Stay Here
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!$success): ?>
                                <form method="POST" id="registrationForm" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                                    <!-- Name Fields -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">
                                                    <i class="fas fa-user me-1"></i>First Name *
                                                </label>
                                                <input type="text" class="form-control form-control-modern" id="first_name"
                                                    name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                                    placeholder="Enter first name" required minlength="2" maxlength="50">
                                                <div class="validation-message" id="first_name_feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">
                                                    <i class="fas fa-user me-1"></i>Last Name *
                                                </label>
                                                <input type="text" class="form-control form-control-modern" id="last_name"
                                                    name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                                    placeholder="Enter last name" required minlength="2" maxlength="50">
                                                <div class="validation-message" id="last_name_feedback"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email Field -->
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Email Address *
                                        </label>
                                        <input type="email" class="form-control form-control-modern" id="email"
                                            name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                            placeholder="your.email@example.com" required maxlength="100">
                                        <div class="validation-message" id="email_feedback"></div>
                                    </div>

                                    <!-- Password Fields -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">
                                                    <i class="fas fa-lock me-1"></i>Create Password *
                                                </label>
                                                <div class="password-container">
                                                    <input type="password" class="form-control form-control-modern with-toggle"
                                                        id="password" name="password" placeholder="Create secure password"
                                                        minlength="6" required>
                                                    <i class="fas fa-eye password-toggle" id="togglePassword"
                                                        onclick="togglePasswordVisibility('password', 'togglePassword')"
                                                        title="Show Password"></i>
                                                </div>
                                                <div class="strength-meter">
                                                    <div class="strength-meter-fill" id="passwordStrength"></div>
                                                </div>
                                                <div class="validation-message" id="password_feedback">
                                                    <small class="text-muted">Minimum 6 characters</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">
                                                    <i class="fas fa-shield-alt me-1"></i>Confirm Password *
                                                </label>
                                                <div class="password-container">
                                                    <input type="password" class="form-control form-control-modern with-toggle"
                                                        id="confirm_password" name="confirm_password"
                                                        placeholder="Confirm your password" minlength="6" required>
                                                    <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"
                                                        onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPassword')"
                                                        title="Show Password"></i>
                                                </div>
                                                <div class="validation-message" id="confirm_password_feedback"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact & ID Fields -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="mobile_number" class="form-label">
                                                    <i class="fas fa-phone me-1"></i>Mobile Number *
                                                </label>
                                                <input type="tel" class="form-control form-control-modern" id="mobile_number"
                                                    name="mobile_number" value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>"
                                                    placeholder="0771234567" required maxlength="10"
                                                    oninput="formatMobileNumber(this)">
                                                <div class="validation-message" id="mobile_feedback">
                                                    <small class="text-muted">10 digits starting with 07 (e.g., 0771234567)</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nic" class="form-label">
                                                    <i class="fas fa-id-card me-1"></i>NIC Number *
                                                </label>
                                                <input type="text" class="form-control form-control-modern" id="nic"
                                                    name="nic" value="<?= htmlspecialchars($_POST['nic'] ?? '') ?>"
                                                    placeholder="123456789V" required maxlength="12"
                                                    oninput="formatNIC(this)">
                                                <div class="validation-message" id="nic_feedback">
                                                    <small class="text-muted">Old: 123456789V | New: 199912345678</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Personal Info Fields -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="gender" class="form-label">
                                                    <i class="fas fa-venus-mars me-1"></i>Gender *
                                                </label>
                                                <select class="form-control form-control-modern" id="gender" name="gender" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                    <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                                    <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                                </select>
                                                <div class="validation-message" id="gender_feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="date_of_birth" class="form-label">
                                                    <i class="fas fa-birthday-cake me-1"></i>Date of Birth
                                                    <small class="text-muted">(Optional)</small>
                                                </label>
                                                <input type="date" class="form-control form-control-modern" id="date_of_birth"
                                                    name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>"
                                                    max="<?= date('Y-m-d', strtotime('-13 years')) ?>">
                                                <div class="validation-message" id="dob_feedback">
                                                    <small class="text-muted">Must be 13+ years old</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Terms & Submit -->
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="terms" required
                                                style="border-color: var(--primary-color); transform: scale(1.2);">
                                            <label class="form-check-label" for="terms" style="margin-left: 8px;">
                                                I agree to ASAA Healthcare's
                                                <a href="#" class="text-decoration-none fw-bold" style="color: var(--primary-color);">Terms & Conditions</a>
                                                and
                                                <a href="#" class="text-decoration-none fw-bold" style="color: var(--primary-color);">Privacy Policy</a>
                                            </label>
                                            <div class="validation-message" id="terms_feedback"></div>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-register" id="submitBtn" disabled>
                                            <i class="fas fa-user-plus me-2"></i>
                                            <span class="btn-text">Create My Account</span>
                                        </button>
                                    </div>

                                    <div class="progress mt-3" style="height: 4px; border-radius: 2px; display: none;" id="progressBar">
                                        <div class="progress-bar" role="progressbar"
                                            style="background: var(--success-gradient); border-radius: 2px;"></div>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <div class="text-center mt-4">
                                <p class="mb-0">Already have an account?
                                    <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--primary-color);">
                                        <i class="fas fa-sign-in-alt me-1"></i>Sign In Here
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-redirect functionality
        <?php if ($autoRedirect): ?>
            let redirectTimer = 5;
            let redirectInterval;

            function startRedirectCountdown() {
                redirectInterval = setInterval(() => {
                    redirectTimer--;
                    const timerElement = document.getElementById('timer');
                    if (timerElement) {
                        timerElement.textContent = redirectTimer;
                    }

                    if (redirectTimer <= 0) {
                        clearInterval(redirectInterval);
                        window.location.href = 'login.php';
                    }
                }, 1000);
            }

            function cancelRedirect() {
                clearInterval(redirectInterval);
                const countdown = document.getElementById('countdown');
                if (countdown) {
                    countdown.innerHTML = '<i class="fas fa-check me-2"></i>Auto-redirect cancelled';
                    countdown.style.color = '#6c757d';
                }
            }

            document.addEventListener('DOMContentLoaded', startRedirectCountdown);
        <?php endif; ?>

        // Enhanced password toggle with animation
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    icon.title = 'Hide Password';
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    icon.title = 'Show Password';
                }

                // Add bounce animation
                icon.style.transform = 'translateY(-50%) scale(0.8)';
                setTimeout(() => {
                    icon.style.transform = 'translateY(-50%) scale(1)';
                }, 150);
            }
        }

        // Mobile number formatting (10 digits only)
        function formatMobileNumber(input) {
            // Remove all non-digits
            let value = input.value.replace(/[^0-9]/g, '');

            // Limit to 10 digits
            if (value.length > 10) {
                value = value.slice(0, 10);
            }

            // Auto-add 07 prefix if user starts with 7
            if (value.length === 1 && value === '7') {
                value = '07';
            }

            // Set the cleaned value
            input.value = value;

            // Validate and provide feedback
            const feedback = document.getElementById('mobile_feedback');
            if (feedback) {
                if (value.length === 0) {
                    feedback.innerHTML = '<small class="text-muted">10 digits starting with 07 (e.g., 0771234567)</small>';
                    input.classList.remove('is-valid', 'is-invalid');
                } else if (value.length < 10) {
                    feedback.innerHTML = `<small class="text-info"><i class="fas fa-info-circle me-1"></i>${10 - value.length} more digits needed</small>`;
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                } else if (!value.startsWith('07')) {
                    feedback.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Must start with 07</small>';
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                } else {
                    feedback.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Valid Sri Lankan mobile</small>';
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }
            }

            checkFormValid();
        }

        // NIC formatting
        function formatNIC(input) {
            let value = input.value.replace(/[^0-9VXvx]/g, '').toUpperCase();

            // Limit length based on format
            if (value.includes('V') || value.includes('X')) {
                value = value.slice(0, 10); // 9 digits + V/X
            } else {
                value = value.slice(0, 12); // 12 digits only
            }

            input.value = value;

            const feedback = document.getElementById('nic_feedback');
            if (feedback) {
                const isOldNIC = /^[0-9]{9}[VX]$/.test(value);
                const isNewNIC = /^[0-9]{12}$/.test(value);

                if (value.length === 0) {
                    feedback.innerHTML = '<small class="text-muted">Old: 123456789V | New: 199912345678</small>';
                    input.classList.remove('is-valid', 'is-invalid');
                } else if (isOldNIC) {
                    feedback.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Valid old NIC format</small>';
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                } else if (isNewNIC) {
                    feedback.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Valid new NIC format</small>';
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                } else {
                    feedback.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Invalid NIC format</small>';
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                }
            }

            checkFormValid();
        }

        // Form validation (only if form exists)
        const form = document.getElementById('registrationForm');
        if (form) {
            const submitBtn = document.getElementById('submitBtn');

            // Password strength checker
            function checkPasswordStrength(password) {
                const strengthMeter = document.getElementById('passwordStrength');
                const feedback = document.getElementById('password_feedback');

                if (!strengthMeter || !feedback) return password.length >= 6;

                let strength = 0;
                if (password.length >= 6) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;

                strengthMeter.className = 'strength-meter-fill';

                switch (strength) {
                    case 0:
                    case 1:
                        strengthMeter.classList.add('strength-weak');
                        feedback.innerHTML = '<small class="text-danger"><i class="fas fa-shield-alt me-1"></i>Weak password</small>';
                        break;
                    case 2:
                        strengthMeter.classList.add('strength-fair');
                        feedback.innerHTML = '<small class="text-warning"><i class="fas fa-shield-alt me-1"></i>Fair password</small>';
                        break;
                    case 3:
                    case 4:
                        strengthMeter.classList.add('strength-good');
                        feedback.innerHTML = '<small class="text-info"><i class="fas fa-shield-alt me-1"></i>Good password</small>';
                        break;
                    case 5:
                        strengthMeter.classList.add('strength-strong');
                        feedback.innerHTML = '<small class="text-success"><i class="fas fa-shield-alt me-1"></i>Excellent password!</small>';
                        break;
                }

                return strength >= 1;
            }

            // SIMPLIFIED FORM VALIDATION CHECK (Fixed for mobile)
            function checkFormValid() {
                if (!submitBtn) return;

                // Get all field values
                const firstName = document.getElementById('first_name')?.value.trim() || '';
                const lastName = document.getElementById('last_name')?.value.trim() || '';
                const email = document.getElementById('email')?.value.trim() || '';
                const password = document.getElementById('password')?.value || '';
                const confirmPassword = document.getElementById('confirm_password')?.value || '';
                const mobile = document.getElementById('mobile_number')?.value.trim() || '';
                const nic = document.getElementById('nic')?.value.trim() || '';
                const gender = document.getElementById('gender')?.value || '';
                const terms = document.getElementById('terms')?.checked || false;

                // Updated validation rules - STRICT 10-digit mobile
                const emailValid = email.includes('@') && email.includes('.') && email.length > 5;
                const mobileValid = mobile.length === 10 && /^07[0-8][0-9]{7}$/.test(mobile);
                const nicValid = /^([0-9]{9}[VX]|[0-9]{12})$/.test(nic.toUpperCase());

                const isValid =
                    firstName.length >= 2 &&
                    lastName.length >= 2 &&
                    emailValid &&
                    password.length >= 6 &&
                    password === confirmPassword &&
                    mobileValid && // Exactly 10 digits, starts with 07, valid operator
                    nicValid &&
                    gender !== '' &&
                    terms;

                // Update submit button with animation
                if (isValid) {
                    submitBtn.disabled = false;
                    submitBtn.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                    submitBtn.style.cursor = 'pointer';
                    submitBtn.style.opacity = '1';
                    submitBtn.style.transform = 'scale(1)';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.style.background = 'linear-gradient(135deg, #6c757d, #5a6268)';
                    submitBtn.style.cursor = 'not-allowed';
                    submitBtn.style.opacity = '0.6';
                    submitBtn.style.transform = 'scale(0.98)';
                }
            }

            // Add event listeners to all form fields
            const fieldIds = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'mobile_number', 'nic', 'gender', 'terms'];

            fieldIds.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    if (field.type === 'checkbox') {
                        field.addEventListener('change', checkFormValid);
                    } else {
                        field.addEventListener('input', checkFormValid);
                        field.addEventListener('keyup', checkFormValid);
                        field.addEventListener('change', checkFormValid);
                        field.addEventListener('blur', checkFormValid);
                    }
                }
            });

            // Individual field validation (visual feedback)
            const addFieldValidation = (fieldId, validator) => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', validator);
                    field.addEventListener('blur', validator);
                }
            };

            addFieldValidation('password', function() {
                checkPasswordStrength(this.value);

                // Re-check confirm password
                const confirmPassword = document.getElementById('confirm_password');
                if (confirmPassword && confirmPassword.value) {
                    const feedback = document.getElementById('confirm_password_feedback');
                    if (feedback) {
                        if (confirmPassword.value === this.value) {
                            feedback.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match!</small>';
                            confirmPassword.classList.remove('is-invalid');
                            confirmPassword.classList.add('is-valid');
                        } else {
                            feedback.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Passwords must match</small>';
                            confirmPassword.classList.remove('is-valid');
                            confirmPassword.classList.add('is-invalid');
                        }
                    }
                }
            });

            addFieldValidation('confirm_password', function() {
                const password = document.getElementById('password');
                const feedback = document.getElementById('confirm_password_feedback');

                if (password && feedback) {
                    if (this.value === password.value && this.value.length >= 6) {
                        feedback.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match!</small>';
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else if (this.value.length > 0) {
                        feedback.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Passwords must match</small>';
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                }
            });

            // Terms checkbox feedback
            const termsField = document.getElementById('terms');
            if (termsField) {
                termsField.addEventListener('change', function() {
                    const feedback = document.getElementById('terms_feedback');
                    if (feedback) {
                        if (this.checked) {
                            feedback.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Terms accepted</small>';
                        } else {
                            feedback.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Please accept terms to continue</small>';
                        }
                    }
                });
            }

            // Enhanced form submission
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                const mobile = document.getElementById('mobile_number');

                // Final validation before submit
                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('❌ Passwords do not match!');
                    return false;
                }

                if (mobile && (mobile.value.length !== 10 || !mobile.value.startsWith('07'))) {
                    e.preventDefault();
                    alert('❌ Please enter a valid 10-digit mobile number starting with 07');
                    return false;
                }

                if (submitBtn) {
                    const btnText = submitBtn.querySelector('.btn-text');
                    if (btnText) {
                        btnText.textContent = 'Creating Your Account...';
                    }
                    submitBtn.insertAdjacentHTML('afterbegin', '<i class="fas fa-spinner fa-spin me-2"></i>');
                    submitBtn.disabled = true;
                    submitBtn.style.background = 'linear-gradient(135deg, #17a2b8, #138496)';

                    // Show progress bar animation
                    const progressBar = document.getElementById('progressBar');
                    if (progressBar) {
                        progressBar.style.display = 'block';
                        const progress = progressBar.querySelector('.progress-bar');
                        let width = 0;
                        const interval = setInterval(() => {
                            width += 12;
                            progress.style.width = Math.min(width, 95) + '%';
                            if (width >= 95) clearInterval(interval);
                        }, 100);
                    }
                }
            });

            // Initial form validation check
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(checkFormValid, 300);
            });
        }
    </script>
</body>

</html>