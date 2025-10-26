<?php
require_once 'config/config.php';

echo "<h2>🔧 Registration System Debug</h2>";

// Test the exact data from your form
$testData = [
    'first_name' => 'Test',
    'last_name' => 'Patient', 
    'email' => 'test@gmail.com',
    'password' => 'Test123',
    'mobile_number' => '0772044123',
    'nic' => '123456789V',
    'gender' => 'Male',
    'date_of_birth' => '2002-02-07'
];

echo "<h3>📋 Test Data:</h3>";
foreach ($testData as $key => $value) {
    echo "<strong>{$key}:</strong> {$value}<br>";
}

echo "<hr>";

try {
    echo "<h3>🧪 Step 1: Database Connection Test</h3>";
    global $db;
    if ($db) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "<h3>🧪 Step 2: Check Email Exists</h3>";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->execute([$testData['email']]);
    $result = $stmt->fetch();
    echo "Email '{$testData['email']}' exists: " . ($result['count'] > 0 ? '❌ YES (will fail)' : '✅ NO (good)') . "<br>";
    
    if ($result['count'] > 0) {
        echo "<p style='color: red;'>⚠️ This email already exists! Try a different email like: test" . rand(100,999) . "@gmail.com</p>";
    }
    
    echo "<h3>🧪 Step 3: Test Password Hash</h3>";
    $passwordHash = password_hash($testData['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    echo "Password hash generated: ✅ " . substr($passwordHash, 0, 20) . "...<br>";
    echo "Hash verification test: " . (password_verify($testData['password'], $passwordHash) ? '✅ Works' : '❌ Failed') . "<br>";
    
    echo "<h3>🧪 Step 4: Test Direct Database Insert</h3>";
    
    // Use a unique email for testing
    $uniqueEmail = 'test' . rand(1000,9999) . '@gmail.com';
    
    $insertStmt = $db->prepare("
        INSERT INTO users (role_id, first_name, last_name, email, password_hash, 
                         mobile_number, nic, gender, date_of_birth, status, created_at)
        VALUES (4, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NOW())
    ");
    
    $insertData = [
        $testData['first_name'],
        $testData['last_name'],
        $uniqueEmail,
        $passwordHash,
        $testData['mobile_number'],
        $testData['nic'],
        $testData['gender'],
        $testData['date_of_birth']
    ];
    
    echo "<strong>Attempting to insert user with email:</strong> {$uniqueEmail}<br>";
    
    $inserted = $insertStmt->execute($insertData);
    
    if ($inserted) {
        echo "✅ Direct database insert successful!<br>";
        $userId = $db->lastInsertId();
        echo "✅ New User ID: {$userId}<br>";
    } else {
        echo "❌ Direct database insert failed<br>";
        $errorInfo = $insertStmt->errorInfo();
        echo "Error details: " . print_r($errorInfo, true) . "<br>";
    }
    
    echo "<h3>🧪 Step 5: Test AuthService Class</h3>";
    
    if (file_exists('services/AuthService.php')) {
        echo "✅ AuthService.php file exists<br>";
        
        try {
            require_once 'services/AuthService.php';
            $authService = new AuthService();
            echo "✅ AuthService class instantiated<br>";
            
            // Test with another unique email
            $testEmail2 = 'test' . rand(5000,9999) . '@gmail.com';
            $testData['email'] = $testEmail2;
            
            echo "<strong>Testing registration with:</strong> {$testEmail2}<br>";
            
            $result = $authService->register($testData);
            
            if ($result['success']) {
                echo "✅ AuthService registration successful!<br>";
                echo "Message: " . $result['message'] . "<br>";
            } else {
                echo "❌ AuthService registration failed<br>";
                echo "Error: " . $result['message'] . "<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ AuthService error: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ AuthService.php file not found<br>";
    }
    
    echo "<h3>🧪 Step 6: Check UserRepository</h3>";
    
    if (file_exists('repositories/UserRepository.php')) {
        echo "✅ UserRepository.php file exists<br>";
        
        try {
            require_once 'repositories/UserRepository.php';
            $userRepo = new UserRepository();
            echo "✅ UserRepository class instantiated<br>";
        } catch (Exception $e) {
            echo "❌ UserRepository error: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ UserRepository.php file not found<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>❌ Critical Error:</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li>If direct database insert works but AuthService fails, check the AuthService.php file</li>";
echo "<li>If email exists, try registration with a different email</li>";
echo "<li>Check error logs in XAMPP for more details</li>";
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
</style>
