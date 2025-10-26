<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session security settings BEFORE starting session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Base configuration
define('BASE_URL', 'http://localhost/asaa_healthcare');
define('SITE_NAME', 'ASAA Healthcare Center');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// PayHere Configuration (Sandbox)
define('PAYHERE_MERCHANT_ID', 'YOUR_MERCHANT_ID');
define('PAYHERE_MERCHANT_SECRET', 'YOUR_MERCHANT_SECRET');
define('PAYHERE_CURRENCY', 'LKR');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');

// Time slots configuration
define('CLINIC_START_TIME', '08:00');
define('CLINIC_END_TIME', '22:00');
define('SLOT_DURATION', 60); // minutes
define('LUNCH_START', '12:00');
define('LUNCH_END', '13:00');

// Include database
require_once 'database.php';
$db = (new Database())->getConnection();

// Helper functions
function hasPermission($userRole, $permission) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.permission_id
            JOIN roles r ON rp.role_id = r.role_id
            WHERE r.role_slug = ? AND p.permission_slug = ?
        ");
        
        $stmt->execute([$userRole, $permission]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit;
    }
}

// function requireRole($allowed_roles) {
//     if (!isset($_SESSION['role'])) {
//         $_SESSION['error'] = 'Access denied';
//         header('Location: ' . BASE_URL . '/index.php');
//         exit();
//     }
    
//     if (!is_array($allowed_roles)) {
//         $allowed_roles = array($allowed_roles);
//     }
    
//     if (!in_array($_SESSION['role'], $allowed_roles)) {
//         $_SESSION['error'] = 'Access denied. Insufficient permissions.';
//         header('Location: ' . BASE_URL . '/views/' . strtolower($_SESSION['role']) . '/dashboard.php');
//         exit();
//     }
// }

function requireRole($allowed_roles) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    // Make role comparison case-insensitive
    $user_role = strtolower($_SESSION['role'] ?? '');
    $allowed_roles = array_map('strtolower', $allowed_roles);
    
    if (!in_array($user_role, $allowed_roles)) {
        // Fix: redirect to appointments page instead of non-existent patient folder
        header('Location: ' . BASE_URL . '/views/appointments/book_minimal.php');
        exit();
    }
}


function requirePermission($permission) {
    requireLogin();
    
    // Admin bypass - admins have all permissions
    if ($_SESSION['user_role'] === 'admin') {
        return true;
    }
    
    if (!hasPermission($_SESSION['user_role'], $permission)) {
        header('Location: ' . BASE_URL . '/access-denied.php');
        exit;
    }
    
    return true;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
