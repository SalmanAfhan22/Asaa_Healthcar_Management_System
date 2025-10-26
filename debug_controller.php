<?php
require_once 'config/config.php';
require_once 'services/AppointmentService.php';

session_start();

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 ASAA Appointment Controller Debug</h2>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check session
echo "<h3>👤 Session Data:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['user_name'] . " (ID: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['user_role'] . ")";
} else {
    echo "❌ No user session found";
}

// Check POST data
if ($_POST) {
    echo "<h3>📋 POST Data Received:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 1rem; border-radius: 8px;'>";
    print_r($_POST);
    echo "</pre>";
    
    // Validate each required field
    echo "<h3>🧪 Field Validation:</h3>";
    
    $validations = [
        'csrf_token' => isset($_POST['csrf_token']) && !empty($_POST['csrf_token']),
        'doctor_id' => isset($_POST['doctor_id']) && !empty($_POST['doctor_id']),
        'appointment_date' => isset($_POST['appointment_date']) && !empty($_POST['appointment_date']),
        'time_slot' => isset($_POST['time_slot']) && !empty($_POST['time_slot']),
        'selected_time_slot' => isset($_POST['selected_time_slot']) && !empty($_POST['selected_time_slot'])
    ];
    
    foreach ($validations as $field => $isValid) {
        $status = $isValid ? "✅" : "❌";
        $value = $_POST[$field] ?? 'NOT SET';
        echo "<p>{$status} <strong>{$field}:</strong> {$value}</p>";
    }
    
    // Test CSRF token validation
    echo "<h3>🔐 CSRF Validation:</h3>";
    try {
        $isValidCSRF = validateCSRFToken($_POST['csrf_token'] ?? '');
        echo $isValidCSRF ? "✅ CSRF token valid" : "❌ CSRF token invalid";
    } catch (Exception $e) {
        echo "❌ CSRF validation error: " . $e->getMessage();
    }
    
    // Test appointment service
    echo "<h3>🏥 Appointment Service Test:</h3>";
    try {
        $appointmentService = new AppointmentService();
        echo "✅ AppointmentService initialized successfully";
        
        // Test with the received data
        if (isset($_POST['doctor_id']) && isset($_POST['appointment_date'])) {
            $doctorId = intval($_POST['doctor_id']);
            $date = $_POST['appointment_date'];
            $timeSlot = $_POST['time_slot'] ?? $_POST['selected_time_slot'] ?? '';
            
            echo "<h4>📝 Attempting to book with:</h4>";
            echo "<p><strong>Patient ID:</strong> " . $_SESSION['user_id'] . "</p>";
            echo "<p><strong>Doctor ID:</strong> {$doctorId}</p>";
            echo "<p><strong>Date:</strong> {$date}</p>";
            echo "<p><strong>Time Slot:</strong> {$timeSlot}</p>";
            
            if (!empty($timeSlot)) {
                $result = $appointmentService->bookAppointment(
                    $_SESSION['user_id'],
                    $doctorId,
                    $date,
                    $timeSlot,
                    $_POST['notes'] ?? ''
                );
                
                echo "<h4>📊 Booking Result:</h4>";
                echo "<pre style='background: " . ($result['success'] ? '#d4edda' : '#f8d7da') . "; padding: 1rem; border-radius: 8px;'>";
                print_r($result);
                echo "</pre>";
                
                if ($result['success']) {
                    echo "<p style='color: green; font-size: 1.2rem; font-weight: bold;'>
                        🎉 BOOKING SUCCESSFUL! Appointment ID: {$result['appointment_id']}
                    </p>";
                    echo "<p><a href='views/payments/pay.php?id={$result['appointment_id']}' class='btn btn-success'>
                        Continue to Payment
                    </a></p>";
                } else {
                    echo "<p style='color: red; font-size: 1.2rem; font-weight: bold;'>
                        ❌ BOOKING FAILED: {$result['message']}
                    </p>";
                }
            } else {
                echo "<p style='color: orange; font-weight: bold;'>⚠️ No time slot provided</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ AppointmentService error: " . $e->getMessage();
        echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
    }
    
} else {
    echo "<p>ℹ️ No POST data received. This page will show debug info when form is submitted.</p>";
}

// Show current database connection status
echo "<h3>💾 Database Connection:</h3>";
try {
    global $db;
    if ($db) {
        echo "✅ Database connected successfully";
        
        // Test query
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>📊 Total appointments in database: " . $result['count'] . "</p>";
    } else {
        echo "❌ No database connection";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}

?>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 2rem; }
h2, h3, h4 { color: #207d87; }
.btn { padding: 0.5rem 1rem; margin: 0.5rem; text-decoration: none; display: inline-block; border-radius: 6px; }
.btn-success { background: #28a745; color: white; }
</style>

<p><a href="views/appointments/book.php" class="btn btn-success">← Back to Booking Form</a></p>
