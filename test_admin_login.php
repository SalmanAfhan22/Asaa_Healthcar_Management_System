<?php
require_once 'config/config.php';

echo "<h2>🔍 Admin Login Debug Test</h2>";

// Test admin user details
$adminEmail = 'admin@asaahealthcare.com';

try {
    $stmt = $db->prepare("
        SELECT u.*, r.role_name, r.role_slug 
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE u.email = ?
    ");
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<h3>✅ Admin User Found:</h3>";
        echo "<strong>Email:</strong> {$admin['email']}<br>";
        echo "<strong>Name:</strong> {$admin['first_name']} {$admin['last_name']}<br>";
        echo "<strong>Role:</strong> {$admin['role_slug']} (ID: {$admin['role_id']})<br>";
        echo "<strong>Status:</strong> {$admin['status']}<br>";
        echo "<strong>Password Hash:</strong> " . substr($admin['password_hash'], 0, 20) . "...<br>";
        
        // Test password
        $testPassword = 'password123';
        $passwordWorks = password_verify($testPassword, $admin['password_hash']);
        echo "<strong>Password 'password123' works:</strong> " . ($passwordWorks ? '✅ YES' : '❌ NO') . "<br>";
        
        if (!$passwordWorks) {
            echo "<hr>";
            echo "<h3>🔧 Fixing Admin Password:</h3>";
            
            // Update admin password
            $newHash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $updateStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $updated = $updateStmt->execute([$newHash, $adminEmail]);
            
            if ($updated) {
                echo "✅ Admin password updated successfully!<br>";
                
                // Test new password
                $stmt->execute([$adminEmail]);
                $updatedAdmin = $stmt->fetch();
                $newPasswordWorks = password_verify($testPassword, $updatedAdmin['password_hash']);
                echo "<strong>New password test:</strong> " . ($newPasswordWorks ? '✅ Works!' : '❌ Still broken') . "<br>";
            } else {
                echo "❌ Failed to update admin password<br>";
            }
        }
        
    } else {
        echo "<h3>❌ Admin User Not Found</h3>";
        echo "<p>Creating admin user...</p>";
        
        // Create admin user
        $adminData = [
            'role_id' => 1, // Admin role
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => $adminEmail,
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]),
            'mobile_number' => '+94771234567',
            'nic' => '123456789V',
            'gender' => 'Other',
            'status' => 'ACTIVE'
        ];
        
        $createStmt = $db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, password_hash, 
                             mobile_number, nic, gender, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $created = $createStmt->execute([
            $adminData['role_id'],
            $adminData['first_name'],
            $adminData['last_name'],
            $adminData['email'],
            $adminData['password_hash'],
            $adminData['mobile_number'],
            $adminData['nic'],
            $adminData['gender'],
            $adminData['status']
        ]);
        
        if ($created) {
            echo "✅ Admin user created successfully!<br>";
        } else {
            echo "❌ Failed to create admin user<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🧪 Test Admin Login Process:</h3>";
    
    // Simulate login process
    $stmt = $db->prepare("
        SELECT u.*, r.role_name, r.role_slug 
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE u.email = ?
    ");
    $stmt->execute([$adminEmail]);
    $loginUser = $stmt->fetch();
    
    if ($loginUser && password_verify('password123', $loginUser['password_hash'])) {
        echo "✅ Login simulation successful!<br>";
        echo "✅ User would be redirected to: " . BASE_URL . "/views/dashboard/admin.php<br>";
        
        // Test role and permissions
        echo "<strong>Role check:</strong> " . ($loginUser['role_slug'] === 'admin' ? '✅ Is Admin' : '❌ Not Admin') . "<br>";
        echo "<strong>Status check:</strong> " . ($loginUser['status'] === 'ACTIVE' ? '✅ Active' : '❌ Inactive') . "<br>";
        
    } else {
        echo "❌ Login simulation failed<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='views/auth/login.php'>Try Admin Login</a> (admin@asaahealthcare.com / password123)</li>";
echo "<li><a href='views/dashboard/admin.php'>Direct Admin Dashboard Access</a></li>";
echo "<li><a href='test_connection.php'>Test Database Connection</a></li>";
echo "</ol>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
