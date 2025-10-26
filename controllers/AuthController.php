<?php
// Don't start session here - config.php handles it
require_once '../config/config.php';
require_once '../services/AuthService.php';

// Get the action from URL or POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'register':
        handleRegister();
        break;
    default:
        // Redirect to login if no valid action
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit;
}

function handleLogin() {
    // This should only be called via POST from a form, not AJAX
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/views/auth/login.php?error=invalid_request');
        exit;
    }
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        header('Location: ' . BASE_URL . '/views/auth/login.php?error=csrf_failed');
        exit;
    }
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        header('Location: ' . BASE_URL . '/views/auth/login.php?error=missing_fields');
        exit;
    }
    
    try {
        $authService = new AuthService();
        $result = $authService->login($email, $password);
        
        if ($result['success']) {
            // Redirect to appropriate dashboard
            header('Location: ' . $result['redirect']);
            exit;
        } else {
            header('Location: ' . BASE_URL . '/views/auth/login.php?error=' . urlencode($result['message']));
            exit;
        }
    } catch (Exception $e) {
        error_log("Login controller error: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/views/auth/login.php?error=system_error');
        exit;
    }
}

function handleLogout() {
    try {
        // Log the logout event
        if (isset($_SESSION['user_email'])) {
            error_log("User logged out: " . $_SESSION['user_email']);
        }
        
        // Clear all session variables
        $_SESSION = array();
        
        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login with success message
        header('Location: ' . BASE_URL . '/views/auth/login.php?logout=success');
        exit;
        
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        // Still redirect to login even if logout has issues
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit;
    }
}

function handleRegister() {
    // This should only be called via POST from a form
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/views/auth/register.php?error=invalid_request');
        exit;
    }
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        header('Location: ' . BASE_URL . '/views/auth/register.php?error=csrf_failed');
        exit;
    }
    
    try {
        $authService = new AuthService();
        $result = $authService->register([
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'mobile_number' => trim($_POST['mobile_number'] ?? ''),
            'nic' => trim($_POST['nic'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'date_of_birth' => $_POST['date_of_birth'] ?? null
        ]);
        
        if ($result['success']) {
            header('Location: ' . BASE_URL . '/views/auth/register.php?success=' . urlencode($result['message']));
            exit;
        } else {
            header('Location: ' . BASE_URL . '/views/auth/register.php?error=' . urlencode($result['message']));
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Registration controller error: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/views/auth/register.php?error=system_error');
        exit;
    }
}
?>
