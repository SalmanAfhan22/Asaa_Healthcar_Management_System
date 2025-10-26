<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=asaa_healthcare;charset=utf8mb4",
        "root", // Your MySQL username
        "",     // Your MySQL password
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "✅ Database connection successful!<br>";

    // Test sample data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Found {$result['count']} users in database<br>";

    // Test doctors
    $stmt = $pdo->query("
        SELECT u.first_name, u.last_name, s.specialization_name 
        FROM users u 
        JOIN doctor_details dd ON u.user_id = dd.user_id 
        JOIN specializations s ON dd.specialization_id = s.specialization_id 
        LIMIT 3
    ");

    echo "✅ Available Doctors:<br>";
    while ($doctor = $stmt->fetch()) {
        echo "- Dr. {$doctor['first_name']} {$doctor['last_name']} ({$doctor['specialization_name']})<br>";
    }
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
