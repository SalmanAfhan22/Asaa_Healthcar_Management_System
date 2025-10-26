<?php
session_start();
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Get current user information
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASAA Healthcare - Booking Debug Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .debug-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #667eea;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .debug-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.8em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .debug-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            line-height: 1.8;
        }

        .debug-info strong {
            color: #2c3e50;
            display: inline-block;
            min-width: 150px;
        }

        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: #000;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            display: inline-block;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-active {
            background: #28a745;
        }

        .status-inactive {
            background: #dc3545;
        }

        .status-pending {
            background: #ffc107;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏥 ASAA Healthcare</h1>
            <p>Comprehensive Appointment Booking Diagnostics</p>
        </div>

        <div class="content">
            <!-- Section 1: Database Connection Status -->
            <div class="debug-section">
                <h2>📊 Database Connection Status</h2>
                <?php
                if ($pdo) {
                    echo '<div class="success-box">';
                    echo '<strong>✓ Database Connected Successfully</strong><br>';
                    echo 'Connection Type: PDO<br>';
                    echo 'Database: asaa_healthcare';
                    echo '</div>';
                } else {
                    echo '<div class="error-box">';
                    echo '<strong>✗ Database Connection Failed</strong><br>';
                    echo 'Please check your database configuration';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Section 2: Session & User Information -->
            <div class="debug-section">
                <h2>👤 Session & User Information</h2>
                <?php
                if (isset($_SESSION['user_id'])) {
                    echo '<div class="success-box">';
                    echo '<strong>✓ User Logged In</strong><br>';
                    echo 'User ID: ' . htmlspecialchars($user_id) . '<br>';
                    echo 'Email: ' . htmlspecialchars($_SESSION['email'] ?? 'Not set') . '<br>';
                    echo 'Role: ' . htmlspecialchars($_SESSION['role'] ?? 'Not set') . '<br>';
                    echo 'Name: ' . htmlspecialchars($_SESSION['name'] ?? 'Not set');
                    echo '</div>';
                } else {
                    echo '<div class="warning-box">';
                    echo '<strong>⚠ No User Logged In</strong><br>';
                    echo 'Please <a href="views/auth/login.php" style="color: #207d87; font-weight: bold;">login</a> to test booking functionality';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Section 3: Patient Record Verification -->
            <div class="debug-section">
                <h2>📋 Patient Record Verification</h2>
                <?php
                $patientRecordExists = false;

                if (isset($_SESSION['user_id'])) {
                    try {
                        // Query to check if patient record exists using users table with role_id for patient
                        $stmt = $pdo->prepare("
                            SELECT u.user_id, u.first_name, u.last_name, u.email, u.mobile_number, 
                                   u.nic, u.gender, u.position, u.role_id, u.status,
                                   up.profile_image, up.date_of_birth, up.address_line_1, up.address_line_2, 
                                   up.city, up.postal_code, up.emergency_contact_name, 
                                   up.emergency_contact_phone, up.blood_group, up.allergies, up.medical_notes,
                                   r.role_name
                            FROM users u
                            LEFT JOIN user_profiles up ON u.user_id = up.user_id
                            LEFT JOIN roles r ON u.role_id = r.role_id
                            WHERE u.user_id = ?
                        ");
                        $stmt->execute([$user_id]);
                        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($patient) {
                            // Check if user has patient role (assuming role_id = 4 for patients)
                            if ($patient['role_id'] == 4) {
                                echo '<div class="success-box">';
                                echo '<strong>✓ Patient Record Found</strong><br>';
                                echo 'User ID: ' . htmlspecialchars($patient['user_id']) . '<br>';
                                echo 'Name: ' . htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) . '<br>';
                                echo 'Email: ' . htmlspecialchars($patient['email']) . '<br>';
                                echo 'Mobile: ' . htmlspecialchars($patient['mobile_number']) . '<br>';
                                echo 'NIC: ' . htmlspecialchars($patient['nic']) . '<br>';
                                echo 'Gender: ' . htmlspecialchars($patient['gender']) . '<br>';
                                echo 'Role: ' . htmlspecialchars($patient['role_name']) . '<br>';
                                echo 'Status: ' . htmlspecialchars($patient['status']) . '<br>';

                                // Display user profile information if available
                                if ($patient['date_of_birth']) {
                                    echo '<br><strong>Profile Information:</strong><br>';
                                    echo 'Date of Birth: ' . htmlspecialchars($patient['date_of_birth']) . '<br>';
                                    echo 'Blood Group: ' . htmlspecialchars($patient['blood_group'] ?? 'Not set') . '<br>';
                                    echo 'Address: ' . htmlspecialchars(($patient['address_line_1'] ?? '') . ' ' . ($patient['address_line_2'] ?? '')) . '<br>';
                                    echo 'City: ' . htmlspecialchars($patient['city'] ?? 'Not set') . '<br>';
                                    echo 'Postal Code: ' . htmlspecialchars($patient['postal_code'] ?? 'Not set') . '<br>';
                                    echo 'Emergency Contact: ' . htmlspecialchars($patient['emergency_contact_name'] ?? 'Not set') . '<br>';
                                    echo 'Emergency Phone: ' . htmlspecialchars($patient['emergency_contact_phone'] ?? 'Not set') . '<br>';

                                    if ($patient['allergies']) {
                                        echo 'Allergies: ' . htmlspecialchars($patient['allergies']) . '<br>';
                                    }
                                    if ($patient['medical_notes']) {
                                        echo 'Medical Notes: ' . htmlspecialchars($patient['medical_notes']) . '<br>';
                                    }
                                } else {
                                    echo '<br><em>No additional profile information available.</em>';
                                }

                                echo '</div>';
                                $patientRecordExists = true;
                            } else {
                                echo '<div class="error-box">';
                                echo '<strong>✗ User is not a Patient</strong><br>';
                                echo 'This user does not have patient role. Current role: ' . htmlspecialchars($patient['role_name']) . '<br>';
                                echo 'Role ID: ' . htmlspecialchars($patient['role_id']) . ' (Expected: 4 for Patient)';
                                echo '</div>';
                                $patientRecordExists = false;
                            }
                        } else {
                            echo '<div class="error-box">';
                            echo '<strong>✗ User Record Not Found</strong><br>';
                            echo 'No user found with ID: ' . htmlspecialchars($user_id);
                            echo '</div>';
                            $patientRecordExists = false;
                        }
                    } catch (Exception $e) {
                        echo '<div class="error-box">';
                        echo '<strong>✗ Error Checking Patient Record</strong><br>';
                        echo 'Error: ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                        $patientRecordExists = false;
                    }
                } else {
                    echo '<div class="warning-box">';
                    echo '⚠️ Please login to verify patient record';
                    echo '</div>';
                }
                ?>
            </div>

            <?php

            // Section 4: Direct Database Booking Test
            if (isset($_POST['test_booking'])) {
                echo '<div class="debug-section">';
                echo '<h2>📝 Booking Test Results</h2>';

                try {
                    $pdo = $database->getConnection();

                    // Get form data
                    $doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
                    $appointment_date = $_POST['appointment_date'] ?? '';
                    $appointment_time = $_POST['appointment_time'] ?? '';
                    $notes = $_POST['notes'] ?? '';

                    // Display received data
                    echo '<div class="debug-info">';
                    echo '<strong>📥 Form Data Received:</strong><br>';
                    echo '<pre>' . print_r($_POST, true) . '</pre>';
                    echo '</div>';

                    // Validate patient
                    $patient_id = $_SESSION['user_id'];

                    echo '<div class="debug-info">';
                    echo '<strong>📊 Data to be inserted:</strong><br>';
                    echo 'Patient ID (User ID): ' . $patient_id . '<br>';
                    echo 'Doctor Detail ID: ' . $doctor_id . '<br>';
                    echo 'Date: ' . htmlspecialchars($appointment_date) . '<br>';
                    echo 'Time: ' . htmlspecialchars($appointment_time) . '<br>';
                    echo 'Notes: ' . htmlspecialchars($notes);
                    echo '</div>';

                    // Validate required fields
                    if (empty($doctor_id)) {
                        throw new Exception("Doctor ID is required");
                    }

                    if (empty($appointment_date)) {
                        throw new Exception("Appointment date is required");
                    }

                    if (empty($appointment_time)) {
                        throw new Exception("Appointment time is required");
                    }

                    // Check if doctor exists using JOIN query
                    $stmt = $pdo->prepare("
SELECT 
    dd.doctor_detail_id, 
    dd.user_id,
    u.first_name,
    u.last_name,
    s.specialization_name,
    dd.consultation_fee
FROM doctor_details dd
INNER JOIN users u ON dd.user_id = u.user_id
INNER JOIN specializations s ON dd.specialization_id = s.specialization_id
WHERE dd.doctor_detail_id = ? AND u.status = 'ACTIVE' AND u.role_id = 3
");
                    $stmt->execute([$doctor_id]);
                    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$doctor) {
                        throw new Exception("Doctor ID $doctor_id does not exist or is not active");
                    }

                    // *** IMPORTANT: Get the user_id from the doctor record ***
                    $doctor_user_id = $doctor['user_id'];

                    echo '<div class="debug-info success-box">';
                    echo '<strong>✅ Doctor Verified</strong><br>';
                    echo 'Doctor Detail ID: ' . htmlspecialchars($doctor['doctor_detail_id']) . '<br>';
                    echo 'Doctor User ID: ' . htmlspecialchars($doctor_user_id) . '<br>';
                    echo 'Doctor Name: ' . htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) . '<br>';
                    echo 'Specialization: ' . htmlspecialchars($doctor['specialization_name']) . '<br>';
                    echo 'Consultation Fee: Rs. ' . htmlspecialchars($doctor['consultation_fee']);
                    echo '</div>';

                    // Check for existing appointment at the same time
                    // *** Use doctor_user_id instead of doctor_id ***
                    $stmt = $pdo->prepare("
SELECT a.appointment_id 
FROM appointments a
WHERE a.doctor_id = ? 
AND a.appointment_date = ? 
AND a.time_slot = ? 
AND a.status NOT IN ('CANCELLED', 'REJECTED', 'COMPLETED')
");
                    $stmt->execute([$doctor_user_id, $appointment_date, $appointment_time]);

                    if ($stmt->fetch()) {
                        throw new Exception("This time slot is already booked for this doctor");
                    }

                    echo '<div class="debug-info success-box">';
                    echo '<strong>✅ Time Slot Available</strong>';
                    echo '</div>';

                    // Insert appointment
                    // *** Use doctor_user_id instead of doctor_id ***
                    $stmt = $pdo->prepare("
INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, status, notes, created_at, created_by)
VALUES (?, ?, ?, ?, 'REVIEW', ?, NOW(), ?)
");

                    $success = $stmt->execute([
                        $patient_id,
                        $doctor_user_id,  // *** Changed from $doctor_id to $doctor_user_id ***
                        $appointment_date,
                        $appointment_time,
                        $notes,
                        $patient_id
                    ]);

                    if ($success) {
                        $appointment_id = $pdo->lastInsertId();

                        echo '<div class="debug-info success-box">';
                        echo '<strong>✅ BOOKING SUCCESSFUL!</strong><br>';
                        echo 'Appointment ID: ' . $appointment_id . '<br>';
                        echo 'Patient ID: ' . $patient_id . '<br>';
                        echo 'Doctor User ID: ' . $doctor_user_id . '<br>';
                        echo 'Doctor Detail ID: ' . $doctor_id . '<br>';
                        echo 'Date: ' . htmlspecialchars($appointment_date) . '<br>';
                        echo 'Time: ' . htmlspecialchars($appointment_time) . '<br>';
                        echo 'Status: REVIEW<br>';
                        echo 'Notes: ' . htmlspecialchars($notes);
                        echo '</div>';
                    } else {
                        throw new Exception("Failed to insert appointment");
                    }
                } catch (Exception $e) {
                    echo '<div class="debug-info error-box">';
                    echo '<strong>❌ ERROR</strong><br>';
                    echo '<strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
                    echo '<strong>File:</strong> ' . __FILE__ . '<br>';
                    echo '<strong>Line:</strong> ' . $e->getLine();
                    echo '</div>';
                }

                echo '</div>';
            }


            // ============================================
            // SECTION 5: Available Test Accounts
            // ============================================
            if (!isset($_SESSION['user_id'])) {
                echo '<div class="debug-section info">';
                echo '<h2>🔑 Available Test Accounts</h2>';

                try {
                    $stmt = $pdo->prepare("
                        SELECT u.user_id, u.email, u.first_name, u.last_name, u.mobile_number, u.role_id,
                               up.date_of_birth, up.blood_group,
                               r.role_name
                        FROM users u
                        LEFT JOIN user_profiles up ON u.user_id = up.user_id
                        LEFT JOIN roles r ON u.role_id = r.role_id
                        WHERE u.role_id = 4
                        ORDER BY u.created_at DESC
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($accounts) > 0) {
                        echo '<div class="debug-info">';
                        echo '<strong>Available Patient Accounts for Testing:</strong><br><br>';

                        foreach ($accounts as $account) {
                            $hasProfile = !empty($account['date_of_birth']);
                            $badge = $hasProfile ? 'badge-success' : 'badge-warning';
                            $status = $hasProfile ? 'Has Profile' : 'Basic Profile';

                            echo '<strong>Email:</strong> ' . htmlspecialchars($account['email']) . ' ';
                            echo '<span class="badge ' . $badge . '">' . $status . '</span><br>';
                            echo '<strong>Name:</strong> ' . htmlspecialchars($account['first_name'] . ' ' . $account['last_name']) . '<br>';
                            echo '<strong>User ID:</strong> ' . htmlspecialchars($account['user_id']) . '<br>';
                            echo '<strong>Role:</strong> ' . htmlspecialchars($account['role_name']) . '<br>';

                            if ($hasProfile) {
                                echo '<strong>Mobile:</strong> ' . htmlspecialchars($account['mobile_number'] ?? 'Not set') . '<br>';
                                echo '<strong>DOB:</strong> ' . htmlspecialchars($account['date_of_birth']) . '<br>';
                                echo '<strong>Blood Group:</strong> ' . htmlspecialchars($account['blood_group'] ?? 'Not set') . '<br>';
                            }

                            echo '<hr style="margin: 10px 0; border: none; border-top: 1px solid #ddd;">';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="debug-info warning-box">';
                        echo '⚠️ No patient accounts found in database';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="debug-info error-box">';
                    echo '❌ Error fetching test accounts: ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }

                echo '</div>';
            }

            // ============================================
            // SECTION 6: Booking Test Form
            // ============================================
            if (isset($_SESSION['user_id']) && $patientRecordExists) {
                echo '<div class="debug-section">';
                echo '<h2>📝 Test Appointment Booking Form</h2>';
            ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="doctor_id">Select Doctor:</label>
                        <select name="doctor_id" id="doctor_id" class="form-control" required>
                            <option value="">-- Select Doctor --</option>
                            <?php
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT 
    dd.doctor_detail_id, 
    u.user_id,
    u.first_name, 
    u.last_name, 
    s.specialization_name,
    dd.consultation_fee,
    dd.experience_years
FROM doctor_details dd
INNER JOIN users u ON dd.user_id = u.user_id
INNER JOIN specializations s ON dd.specialization_id = s.specialization_id
WHERE u.status = 'ACTIVE' AND u.role_id = 3
ORDER BY u.first_name ASC, u.last_name ASC

                                ");
                                $stmt->execute();
                                $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($doctors) > 0) {
                                    foreach ($doctors as $doctor) {
                                        echo '<option value="' . htmlspecialchars($doctor['doctor_detail_id']) . '">';
                                        echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) . ' - ';
                                        echo htmlspecialchars($doctor['specialization_name']) . ' ';
                                        echo '(Fee: $' . htmlspecialchars($doctor['consultation_fee']) . ')';
                                        echo '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No active doctors available</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option value="" disabled>Error loading doctors: ' . htmlspecialchars($e->getMessage()) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="appointment_date">Appointment Date:</label>
                        <input
                            type="date"
                            name="appointment_date"
                            id="appointment_date"
                            class="form-control"
                            min="<?php echo date('Y-m-d'); ?>"
                            max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>"
                            value="<?php echo date('Y-m-d', strtotime('tomorrow')); ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="appointment_time">Appointment Time:</label>
                        <select name="appointment_time" id="appointment_time" class="form-control" required>
                            <option value="">-- Select Time --</option>
                            <?php
                            // Generate time slots from 8 AM to 10 PM, excluding 1-2 PM (lunch)
                            for ($hour = 8; $hour <= 22; $hour++) {
                                // Skip lunch hour (1 PM)
                                if ($hour == 13) {
                                    continue;
                                }

                                $time24 = sprintf("%02d:00:00", $hour);
                                $time12 = date("g:i A", strtotime($time24));

                                echo '<option value="' . $time24 . '">' . $time12 . '</option>';

                                // Add 30-minute slot
                                if ($hour < 22) {
                                    $time24_half = sprintf("%02d:30:00", $hour);
                                    $time12_half = date("g:i A", strtotime($time24_half));

                                    // Skip 1:30 PM as well
                                    if (!($hour == 13)) {
                                        echo '<option value="' . $time24_half . '">' . $time12_half . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (Optional):</label>
                        <textarea
                            name="notes"
                            id="notes"
                            class="form-control"
                            rows="4"
                            placeholder="Enter any symptoms, concerns, or special requests..."></textarea>
                    </div>

                    <button type="submit" name="test_booking" class="btn">
                        🧪 Test Book Appointment
                    </button>
                </form>

            <?php
                echo '</div>';
            } else {
                echo '<div class="debug-section warning">';
                echo '<h2>📝 Booking Form Not Available</h2>';
                echo '<div class="warning-box">';
                if (!isset($_SESSION['user_id'])) {
                    echo '⚠️ Please <a href="views/auth/login.php" style="color: #207d87; font-weight: bold;">login</a> to test booking';
                } else {
                    echo '⚠️ Cannot display form: No patient record found for this user';
                }
                echo '</div>';
                echo '</div>';
            }
            ?>

            <!-- System Information -->
            <div class="debug-section">
                <h2>⚙️ System Information</h2>
                <div class="debug-info">
                    <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
                    <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                    <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?><br>
                    <strong>Script Filename:</strong> <?php echo __FILE__; ?><br>
                    <strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                    <strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?>
                </div>
            </div>

        </div>
    </div>
</body>

</html>