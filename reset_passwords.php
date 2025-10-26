<?php
require_once 'config/config.php';

echo "<h2>🔄 Resetting User Passwords</h2>";

// New password hash for 'password123'
$correctPassword = 'password123';
$newHash = password_hash($correctPassword, PASSWORD_BCRYPT, ['cost' => 12]);

echo "<p><strong>New Hash:</strong> " . substr($newHash, 0, 30) . "...</p>";
echo "<p><strong>Verification Test:</strong> " . (password_verify($correctPassword, $newHash) ? '✅ Works' : '❌ Failed') . "</p>";

echo "<hr>";

// Users to update
$users = [
    ['email' => 'admin@asaahealthcare.com', 'role' => 'Admin'],
    ['email' => 'sarah.johnson@asaahealthcare.com', 'role' => 'Doctor'],
    ['email' => 'michael.chen@asaahealthcare.com', 'role' => 'Doctor'],
    ['email' => 'emily.rodriguez@asaahealthcare.com', 'role' => 'Doctor']
];

try {
    // Update each user's password
    foreach ($users as $user) {
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $result = $stmt->execute([$newHash, $user['email']]);
        
        if ($result) {
            echo "✅ Updated password for {$user['role']}: {$user['email']}<br>";
        } else {
            echo "❌ Failed to update {$user['email']}<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🧪 Testing Updated Passwords:</h3>";
    
    // Test each updated password
    foreach ($users as $user) {
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE email = ?");
        $stmt->execute([$user['email']]);
        $result = $stmt->fetch();
        
        if ($result) {
            $isValid = password_verify($correctPassword, $result['password_hash']);
            echo "<strong>{$user['email']}:</strong> " . ($isValid ? '✅ Password works' : '❌ Still broken') . "<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🎉 Password Reset Complete!</h3>";
    echo "<p>All users can now login with:</p>";
    echo "<strong>Password:</strong> password123<br>";
    echo "<br>";
    echo "<a href='views/auth/login.php' class='btn btn-primary'>Test Login Now</a>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 10px;
}

.btn:hover {
    background: #0056b3;
}
</style>
