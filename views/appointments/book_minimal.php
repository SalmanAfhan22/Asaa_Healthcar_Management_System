<?php
// Minimal book.php for testing - NO INCLUDES
session_start();
date_default_timezone_set('Asia/Colombo');

// Base configuration
define('BASE_URL', 'http://localhost/asaa_healthcare');

// Manual database connection (avoid config.php for now)
$db_config = [
    'host' => 'localhost',
    'dbname' => 'asaa_healthcare',
    'username' => 'root',
    'password' => ''
];

try {
    $db = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Simple login check
if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$success = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['book_appointment'])) {
    file_put_contents('minimal_debug.log', "[" . date('Y-m-d H:i:s') . "] Form submitted\n", FILE_APPEND);
    file_put_contents('minimal_debug.log', "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
    
    if (empty($_POST['doctor_id']) || empty($_POST['appointment_date']) || empty($_POST['time_slot'])) {
        $error = 'Please fill all required fields.';
        file_put_contents('minimal_debug.log', "Missing fields\n", FILE_APPEND);
    } else {
        try {
            // Simple appointment creation
            $stmt = $db->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, status, payment_status, notes, created_by, created_at)
                VALUES (?, ?, ?, ?, 'REVIEW', 'PENDING', ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $_SESSION['user_id'],
                $_POST['doctor_id'],
                $_POST['appointment_date'],
                $_POST['time_slot'],
                $_POST['notes'] ?? null,
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                $appointmentId = $db->lastInsertId();
                $paymentUrl = BASE_URL . '/views/payments/pay.php?id=' . $appointmentId;
                
                file_put_contents('minimal_debug.log', "Appointment created: ID $appointmentId\n", FILE_APPEND);
                file_put_contents('minimal_debug.log', "Redirecting to: $paymentUrl\n", FILE_APPEND);
                
                // Test redirect
                header('Location: ' . $paymentUrl);
                exit();
            } else {
                $error = 'Failed to create appointment';
                file_put_contents('minimal_debug.log', "Database insert failed\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            file_put_contents('minimal_debug.log', "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

// Get doctors for dropdown (simple query)
$stmt = $db->prepare("
    SELECT u.user_id, u.first_name, u.last_name, dd.consultation_fee, s.specialization_name
    FROM users u
    JOIN doctor_details dd ON u.user_id = dd.user_id
    JOIN specializations s ON dd.specialization_id = s.specialization_id
    WHERE u.role_id = 3 AND u.status = 'ACTIVE'
    ORDER BY u.first_name
");
$stmt->execute();
$doctors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Book Appointment - Minimal Test</h2>
        
        <!-- Debug Info -->
        <div class="alert alert-info">
            <strong>Debug Info:</strong><br>
            Session User ID: <?= $_SESSION['user_id'] ?? 'Not set' ?><br>
            Session Role: <?= $_SESSION['user_role'] ?? 'Not set' ?><br>
            Current Time: <?= date('Y-m-d H:i:s') ?><br>
            Available Doctors: <?= count($doctors) ?>
        </div>
        
        <!-- Error Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Simple Form -->
        <form method="POST">
            <input type="hidden" name="book_appointment" value="1">
            
            <div class="mb-3">
                <label for="doctor_id" class="form-label">Select Doctor *</label>
                <select class="form-select" name="doctor_id" id="doctor_id" required>
                    <option value="">Choose a doctor...</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= $doctor['user_id'] ?>">
                            Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                            - <?= htmlspecialchars($doctor['specialization_name']) ?>
                            (LKR <?= number_format($doctor['consultation_fee'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="appointment_date" class="form-label">Date *</label>
                <input type="date" class="form-control" name="appointment_date" id="appointment_date" 
                       required min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+3 months')) ?>">
            </div>
            
            <div class="mb-3">
                <label for="time_slot" class="form-label">Time Slot *</label>
                <select class="form-select" name="time_slot" id="time_slot" required>
                    <option value="">Choose time...</option>
                    <option value="08:00:00">8:00 AM</option>
                    <option value="09:00:00">9:00 AM</option>
                    <option value="10:00:00">10:00 AM</option>
                    <option value="11:00:00">11:00 AM</option>
                    <option value="13:00:00">1:00 PM</option>
                    <option value="14:00:00">2:00 PM</option>
                    <option value="15:00:00">3:00 PM</option>
                    <option value="16:00:00">4:00 PM</option>
                    <option value="17:00:00">5:00 PM</option>
                    <option value="18:00:00">6:00 PM</option>
                    <option value="19:00:00">7:00 PM</option>
                    <option value="20:00:00">8:00 PM</option>
                    <option value="21:00:00">9:00 PM</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Optional notes..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Book Appointment & Go to Payment</button>
        </form>
        
        <hr>
        <small class="text-muted">Check minimal_debug.log file for detailed logs</small>
    </div>
</body>
</html>
