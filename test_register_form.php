<?php
require_once 'config/config.php';
require_once 'services/AuthService.php';

$error = '';
$success = '';

// Test the exact data you tried to register
$testData = [
    'first_name' => 'Jason',
    'last_name' => 'TestPatient',
    'email' => 'jason' . rand(100,999) . '@test.com',  // New unique email
    'password' => 'Test123',
    'mobile_number' => '0771234567',
    'nic' => 'T' . rand(100000000,999999999) . 'V',    // New unique NIC
    'gender' => 'Male',
    'date_of_birth' => '1995-05-15'
];

echo "<h2>🧪 Testing Registration Form Process</h2>";

echo "<h3>📋 Test Data:</h3>";
foreach ($testData as $key => $value) {
    echo "<strong>{$key}:</strong> {$value}<br>";
}

echo "<hr>";

try {
    echo "<h3>🔧 Step 1: Test AuthService Directly</h3>";
    
    $authService = new AuthService();
    $result = $authService->register($testData);
    
    if ($result['success']) {
        echo "✅ AuthService registration successful!<br>";
        echo "Message: " . $result['message'] . "<br>";
        $success = $result['message'];
    } else {
        echo "❌ AuthService registration failed<br>";
        echo "Error: " . $result['message'] . "<br>";
        $error = $result['message'];
    }
    
    echo "<hr>";
    
    echo "<h3>🔧 Step 2: Test Form Processing Logic</h3>";
    
    // Simulate form submission processing
    if ($success) {
        echo "✅ Form would show success message<br>";
        echo "✅ User can now login with: {$testData['email']} / {$testData['password']}<br>";
    } else {
        echo "❌ Form would show error: {$error}<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>❌ Critical Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🎯 Test Registration Form:</h3>";
?>

<form method="POST" action="views/auth/register.php" style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
    <h4>Quick Test Registration</h4>
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    
    <div style="margin-bottom: 10px;">
        <label>First Name:</label><br>
        <input type="text" name="first_name" value="TestUser<?= rand(100,999) ?>" style="width: 200px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Last Name:</label><br>
        <input type="text" name="last_name" value="Patient" style="width: 200px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Email:</label><br>
        <input type="email" name="email" value="user<?= rand(1000,9999) ?>@test.com" style="width: 200px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Password:</label><br>
        <input type="password" name="password" value="Test123" style="width: 200px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Confirm Password:</label><br>
        <input type="password" name="confirm_password" value="Test123" style="width: 200px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Mobile:</label><br>
        <input type="tel" name="mobile_number" value="077<?= rand(1000000,9999999) ?>" style="width: 200px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>NIC:</label><br>
        <input type="text" name="nic" value="<?= rand(100000000,999999999) ?>V" style="width: 200px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Gender:</label><br>
        <select name="gender" style="width: 200px; padding: 5px;">
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label>Date of Birth:</label><br>
        <input type="date" name="date_of_birth" value="1995-05-15" style="width: 200px; padding: 5px;">
    </div>
    
    <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
        Test Registration
    </button>
</form>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
}
</style>
