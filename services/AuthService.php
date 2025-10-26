<?php
require_once __DIR__ . '/../config/config.php';

class AuthService {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function register($data) {
        try {
            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'password', 'mobile_number', 'nic', 'gender'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '{$field}' is required"];
                }
            }
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Check if NIC already exists
            $nicStmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE nic = ?");
            $nicStmt->execute([$data['nic']]);
            $nicResult = $nicStmt->fetch();
            
            if ($nicResult['count'] > 0) {
                return ['success' => false, 'message' => 'NIC number already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Insert new user (WITHOUT date_of_birth)
            $insertStmt = $this->db->prepare("
                INSERT INTO users (role_id, first_name, last_name, email, password_hash, 
                                 mobile_number, nic, gender, status, created_at)
                VALUES (4, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NOW())
            ");
            
            $inserted = $insertStmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $passwordHash,
                $data['mobile_number'],
                $data['nic'],
                $data['gender']
            ]);
            
            if ($inserted) {
                $userId = $this->db->lastInsertId();
                
                // Create user profile entry WITH date_of_birth (if provided)
                if (!empty($data['date_of_birth'])) {
                    try {
                        $profileStmt = $this->db->prepare("
                            INSERT INTO user_profiles (user_id, date_of_birth, created_at)
                            VALUES (?, ?, NOW())
                        ");
                        $profileStmt->execute([$userId, $data['date_of_birth']]);
                    } catch (Exception $e) {
                        // Log profile error but don't fail registration
                        error_log("Profile creation failed: " . $e->getMessage());
                    }
                }
                
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                $errorInfo = $insertStmt->errorInfo();
                error_log("Database insert failed: " . print_r($errorInfo, true));
                return ['success' => false, 'message' => 'Database insert failed'];
            }
            
        } catch (Exception $e) {
            error_log("AuthService registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.role_slug 
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            if ($user['status'] !== 'ACTIVE') {
                return ['success' => false, 'message' => 'Account is not active'];
            }
            
            // Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role_slug'];
            $_SESSION['role_id'] = $user['role_id'];
            
            session_regenerate_id(true);
            
            return [
                'success' => true, 
                'redirect' => $this->getRedirectURL($user['role_slug'])
            ];
            
        } catch (Exception $e) {
            error_log("AuthService login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    private function getRedirectURL($role) {
        switch ($role) {
            case 'admin':
                return BASE_URL . '/views/dashboard/admin.php';
            case 'doctor':
                return BASE_URL . '/views/dashboard/doctor.php';
            case 'staff':
                return BASE_URL . '/views/dashboard/staff.php';
            case 'patient':
                return BASE_URL . '/views/dashboard/patient.php';
            default:
                return BASE_URL . '/index.php';
        }
    }
}
?>
