<?php
require_once 'config/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 ASAA Booking Form Debug</h2>";

// Test 1: Check if form data is being submitted
if ($_POST) {
    echo "<h3>✅ POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Check for common form issues
    $issues = [];
    
    if (empty($_POST['doctor_id'])) {
        $issues[] = "❌ doctor_id is missing";
    }
    
    if (empty($_POST['appointment_date'])) {
        $issues[] = "❌ appointment_date is missing";
    }
    
    if (empty($_POST['time_slot']) && empty($_POST['selected_time_slot'])) {
        $issues[] = "❌ time_slot/selected_time_slot is missing";
    }
    
    if (!empty($issues)) {
        echo "<h3>🚨 Issues Found:</h3>";
        foreach ($issues as $issue) {
            echo "<p>{$issue}</p>";
        }
    } else {
        echo "<h3>✅ All required fields present!</h3>";
    }
} else {
    echo "<p>No POST data received yet.</p>";
}

// Test 2: Test form with manual data
echo "<h3>🧪 Manual Test Form:</h3>";
?>
<form method="POST" style="background: #f8f9fa; padding: 2rem; border-radius: 8px; margin: 2rem 0;">
    <h4>Quick Booking Test</h4>
    
    <div class="mb-3">
        <label>Doctor ID:</label>
        <select name="doctor_id" class="form-control">
            <option value="">-- Select Doctor --</option>
            <option value="22">Dr. Sarah Johnson (ID: 22)</option>
            <option value="23">Dr. Michael Chen (ID: 23)</option>
            <option value="24">Dr. Emily Rodriguez (ID: 24)</option>
        </select>
    </div>
    
    <div class="mb-3">
        <label>Date:</label>
        <input type="date" name="appointment_date" class="form-control" 
               value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d') ?>">
    </div>
    
    <div class="mb-3">
        <label>Time Slot:</label>
        <select name="time_slot" class="form-control">
            <option value="">-- Select Time --</option>
            <option value="08:00:00">8:00 AM</option>
            <option value="09:00:00">9:00 AM</option>
            <option value="10:00:00">10:00 AM</option>
            <option value="14:00:00">2:00 PM</option>
            <option value="15:00:00">3:00 PM</option>
        </select>
    </div>
    
    <div class="mb-3">
        <label>Notes:</label>
        <textarea name="notes" class="form-control">Test booking via debug form</textarea>
    </div>
    
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="book_appointment" value="1">
    
    <button type="submit" class="btn btn-primary">
        🧪 Test Book Appointment
    </button>
</form>

<style>
.form-control { margin-bottom: 1rem; padding: 0.5rem; width: 100%; max-width: 300px; }
</style>
<?php
?>
