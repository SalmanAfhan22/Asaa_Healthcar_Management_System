<?php
require_once __DIR__ . '/../config/config.php';

class UserRepository {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT u.*, r.role_name, r.role_slug 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, r.role_name, r.role_slug 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, password_hash, 
                             mobile_number, nic, gender, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $data['role_id'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password_hash'],
            $data['mobile_number'],
            $data['nic'],
            $data['gender'],
            $data['status']
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    public function getAllUsers($role = null) {
        $sql = "
            SELECT u.*, r.role_name, r.role_slug 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
        ";
        
        if ($role) {
            $sql .= " WHERE r.role_slug = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$role]);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }
}
?>
