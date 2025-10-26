<?php
require_once 'config/config.php';

echo "<h2>🔧 Registration Fix Test</h2>";

try {
    // Test data without date_of_birth
    $testData = [
        'first_name' => 'Jason',
        'last_name' => 'Patient',
        'email' => 'test' . rand(1000, 9999) . '@gmail.com',
        'password' => 'Test123',
        'mobile_number' => '0772044222',
        'nic' => '123456999V',
        'gender' => 'Male'
    ];

    echo "<h3>📋 Test Data (without date_of_birth):</h3>";
    foreach ($testData as $key => $value) {
        echo "<strong>{$key}:</strong> {$value}<br>";
    }

    echo "<hr>";

    // Hash password
    $passwordHash = password_hash($testData['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    // Insert user without date_of_birth
    $insertStmt = $db->prepare("
        INSERT INTO users (role_id, first_name, last_name, email, password_hash, 
                         mobile_number, nic, gender, status, created_at)
        VALUES (4, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NOW())
    ");

    $inserted = $insertStmt->execute([
        $testData['first_name'],
        $testData['last_name'],
        $testData['email'],
        $passwordHash,
        $testData['mobile_number'],
        $testData['nic'],
        $testData['gender']
    ]);

    if ($inserted) {
        $userId = $db->lastInsertId();
        echo "✅ User inserted successfully! User ID: {$userId}<br>";

        // Test creating user profile with date_of_birth
        echo "<h3>📅 Creating User Profile:</h3>";

        $profileStmt = $db->prepare("
            INSERT INTO user_profiles (user_id, date_of_birth, created_at)
            VALUES (?, ?, NOW())
        ");

        $profileInserted = $profileStmt->execute([$userId, '2002-02-07']);

        if ($profileInserted) {
            echo "✅ User profile created successfully!<br>";
        } else {
            echo "❌ User profile creation failed<br>";
            echo "Error: " . print_r($profileStmt->errorInfo(), true) . "<br>";
        }

        echo "<hr>";
        echo "<h3>🧪 Test AuthService Registration:</h3>";

        if (file_exists('services/AuthService.php')) {
            require_once 'services/AuthService.php';
            $authService = new AuthService();

            $testData2 = $testData;
            $testData2['email'] = 'test' . rand(5000, 9999) . '@gmail.com';
            $testData2['date_of_birth'] = '2002-02-07';

            $result = $authService->register($testData2);

            if ($result['success']) {
                echo "✅ AuthService registration successful!<br>";
                echo "Message: " . $result['message'] . "<br>";
            } else {
                echo "❌ AuthService registration failed<br>";
                echo "Error: " . $result['message'] . "<br>";
            }
        }
    } else {
        echo "❌ User insert failed<br>";
        echo "Error: " . print_r($insertStmt->errorInfo(), true) . "<br>";
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
echo "<li>If this works, try registration again with the new AuthService</li>";
echo "<li>Registration should work without date_of_birth issues</li>";
echo "</ol>";
