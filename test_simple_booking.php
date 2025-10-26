<?php
require_once 'config/config.php';
require_once 'services/AppointmentService.php';

session_start();

// Set test user session if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 21; // Test patient ID
    $_SESSION['user_role'] = 'patient';
    $_SESSION['user_name'] = 'Test Patient';
}

$message = '';
$success = false;

if ($_POST) {
    echo "<h3>📝 Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['book_appointment'])) {
        $appointmentService = new AppointmentService();
        
        $result = $appointmentService->bookAppointment(
            $_SESSION['user_id'],
            intval($_POST['doctor_id']),
            $_POST['appointment_date'],
            $_POST['time_slot'],
            $_POST['notes'] ?? null
        );
        
        echo "<h3>🏥 Booking Result:</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        if ($result['success']) {
            $message = "✅ SUCCESS: " . $result['message'];
            $success = true;
            echo "<p style='color: green; font-size: 1.2rem; font-weight: bold;'>{$message}</p>";
            echo "<p><a href='views/payments/pay.php?id={$result['appointment_id']}' class='btn btn-primary'>Continue to Payment</a></p>";
        } else {
            $message = "❌ ERROR: " . $result['message'];
            echo "<p style='color: red; font-size: 1.2rem; font-weight: bold;'>{$message}</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ASAA Booking Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h1>🧪 ASAA Healthcare Booking Test</h1>
    
    <form method="POST" class="mt-4">
        <div class="row">
            <div class="col-md-6">
                <h4>Quick Test Booking</h4>
                
                <div class="mb-3">
                    <label>Doctor:</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">-- Select Doctor --</option>
                        <option value="22">Dr. Sarah Johnson (ID: 22)</option>
                        <option value="23">Dr. Michael Chen (ID: 23)</option> 
                        <option value="24">Dr. Emily Rodriguez (ID: 24)</option>
                        <option value="25">Dr. David Kim (ID: 25)</option>
                        <option value="26">Dr. Lisa Wang (ID: 26)</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label>Date:</label>
                    <input type="date" name="appointment_date" class="form-select" 
                           value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label>Time Slot:</label>
                    <select name="time_slot" class="form-select" required>
                        <option value="">-- Select Time --</option>
                        <option value="08:00:00">8:00 AM</option>
                        <option value="09:00:00">9:00 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="11:00:00">11:00 AM</option>
                        <option value="13:00:00">1:00 PM</option>
                        <option value="14:00:00">2:00 PM</option>
                        <option value="15:00:00">3:00 PM</option>
                        <option value="16:00:00">4:00 PM</option>
                    </select>
                </div>
                
                <div class="mb-3">
                test_simple_booking.php             <label>Notes:</label>
                    <textarea name="notes" class="form-control">Test appointment booking</textarea>
                </div>
                
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <button type="submit" name="book_appointment" class="btn btn-success btn-lg">
                    🧪 Test Book Appointment
                </button>
            </div>
        </div>
    </form>
</body>
</html>
