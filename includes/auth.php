<?php
/**
 * Authentication Helper Functions
 * Provides session management, login verification, and role-based access control
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Require user to be logged in
 * Redirects to login page if not authenticated
 * @param string $redirect_url Optional URL to redirect after login
 */
function requireLogin($redirect_url = null) {
    if (!isLoggedIn()) {
        // Store intended destination
        if ($redirect_url) {
            $_SESSION['redirect_after_login'] = $redirect_url;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        
        // Redirect to login page
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Require user to have specific role(s)
 * Redirects to appropriate dashboard if user doesn't have required role
 * @param array $allowed_roles Array of allowed roles (e.g., ['patient', 'doctor'])
 */
function requireRole($allowed_roles) {
    // First ensure user is logged in
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    
    // Check if user has one of the allowed roles
    $user_role = $_SESSION['role'];
    
    if (!in_array($user_role, $allowed_roles)) {
        // User doesn't have required role, redirect to their appropriate dashboard
        switch ($user_role) {
            case 'patient':
                header('Location: ' . BASE_URL . '/views/dashboard/patient.php');
                break;
            case 'doctor':
                header('Location: ' . BASE_URL . '/views/dashboard/doctor.php');
                break;
            case 'staff':
                header('Location: ' . BASE_URL . '/views/dashboard/staff.php');
                break;
            case 'admin':
                header('Location: ' . BASE_URL . '/views/dashboard/admin.php');
                break;
            default:
                header('Location: ' . BASE_URL . '/index.php');
                break;
        }
        exit;
    }
}

/**
 * Check if user has specific role
 * @param string $role Role to check
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 * @param array $roles Array of roles to check
 * @return bool
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return in_array($_SESSION['role'], $roles);
}

/**
 * Get current user's ID
 * @return int|null
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get current user's role
 * @return string|null
 */
function getCurrentUserRole() {
    return isLoggedIn() ? $_SESSION['role'] : null;
}

/**
 * Get current user's name
 * @return string|null
 */
function getCurrentUserName() {
    return isLoggedIn() ? $_SESSION['name'] : null;
}

/**
 * Get current user's email
 * @return string|null
 */
function getCurrentUserEmail() {
    return isLoggedIn() ? $_SESSION['email'] : null;
}

/**
 * Logout user
 * Clears session and redirects to login page
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

/**
 * Check if current user owns a resource
 * @param int $resource_user_id The user ID associated with the resource
 * @return bool
 */
function isOwner($resource_user_id) {
    return isLoggedIn() && $_SESSION['user_id'] == $resource_user_id;
}

/**
 * Require user to be owner of resource or have specific role
 * @param int $resource_user_id The user ID associated with the resource
 * @param array $allowed_roles Roles that can access regardless of ownership
 */
function requireOwnerOrRole($resource_user_id, $allowed_roles = []) {
    requireLogin();
    
    $user_role = $_SESSION['role'];
    $is_owner = $_SESSION['user_id'] == $resource_user_id;
    $has_role = in_array($user_role, $allowed_roles);
    
    if (!$is_owner && !$has_role) {
        $_SESSION['error'] = 'You do not have permission to access this resource';
        
        // Redirect to appropriate dashboard
        switch ($user_role) {
            case 'patient':
                header('Location: ' . BASE_URL . '/views/dashboard/patient.php');
                break;
            case 'doctor':
                header('Location: ' . BASE_URL . '/views/dashboard/doctor.php');
                break;
            case 'staff':
                header('Location: ' . BASE_URL . '/views/dashboard/staff.php');
                break;
            case 'admin':
                header('Location: ' . BASE_URL . '/views/dashboard/admin.php');
                break;
            default:
                header('Location: ' . BASE_URL . '/index.php');
                break;
        }
        exit;
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token
 * Terminates script if token is invalid
 */
function requireCSRFToken() {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}
