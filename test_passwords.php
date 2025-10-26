<?php
require_once 'config/config.php';

// Test the actual passwords in database
$stmt = $db->prepare("
    SELECT user_id, email, password_hash, role_slug
    FROM users u 
    JOIN roles r ON u.role_id = r.role_id 
    WHERE email IN (?, ?, ?, ?)
");

$emails = [
    'admin@asaahealthcare.com',
    'sarah.johnson@asaahealthcare.com', 
    'michael.chen@asaahealthcare.com',
    'emily.rodriguez@asaahealthcare.com'
];

$stmt->execute($emails);
$users = $stmt->fetchAll();

echo "<h2>Database Users & Password Hashes:</h2>";
foreach ($users as $user) {
    echo "<strong>Email:</strong> {$user['email']}<br>";
    echo "<strong>Role:</strong> {$user['role_slug']}<br>";
    echo "<strong>Hash:</strong> " . substr($user['password_hash'], 0, 20) . "...<br>";
    
    // Test password verification
    $testPassword = 'password123';
    $isValid = password_verify($testPassword, $user['password_hash']);
    echo "<strong>Password 'password123' works:</strong> " . ($isValid ? '✅ YES' : '❌ NO') . "<br>";
    echo "<hr>";
}

// Also test creating a new hash
echo "<h3>Create New Hash Test:</h3>";
$newHash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
echo "New hash for 'password123': " . substr($newHash, 0, 30) . "...<br>";
echo "Verification test: " . (password_verify('password123', $newHash) ? '✅ Works' : '❌ Failed') . "<br>";
?>
