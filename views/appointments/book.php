<?php
require_once '../../config/config.php';
require_once '../../services/AppointmentService.php';
require_once '../../repositories/AppointmentRepository.php';

// Set timezone to Sri Lankan time
date_default_timezone_set('Asia/Colombo');

requireLogin();

// Restrict to patients only
if ($_SESSION['user_role'] !== 'patient') {
    header('Location: ' . BASE_URL . '/access-denied.php');
    exit;
}

$appointmentService = new AppointmentService();
$appointmentRepository = new AppointmentRepository();
$doctors = $appointmentRepository->getAllDoctors();
$specializations = $appointmentRepository->getAllSpecializations();

$success = '';
$error = '';
$debugInfo = ''; // ADD THIS LINE

// Handle form submission
if ($_POST && isset($_POST['book_appointment'])) {
    $debugInfo .= "Form submitted at: " . date('Y-m-d H:i:s') . "\n";
    $debugInfo .= "POST data: " . print_r($_POST, true) . "\n";
    $debugInfo .= "Session user_id: " . $_SESSION['user_id'] . "\n";
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please try again.';
        $debugInfo .= "CSRF validation failed\n";
    } else {
        $debugInfo .= "CSRF validation passed\n";
        
        // Validate required fields
        if (empty($_POST['doctor_id']) || empty($_POST['appointment_date']) || empty($_POST['time_slot'])) {
            $error = 'Please fill all required fields.';
            $debugInfo .= "Missing required fields\n";
        } else {
            $debugInfo .= "All required fields present\n";
            $debugInfo .= "About to call bookAppointment...\n";
            
            try {
                $result = $appointmentService->bookAppointment(
                    $_SESSION['user_id'],
                    $_POST['doctor_id'],
                    $_POST['appointment_date'],
                    $_POST['time_slot'],
                    $_POST['notes'] ?? null
                );
                
                $debugInfo .= "bookAppointment result: " . print_r($result, true) . "\n";
                
                if ($result['success']) {
                    $debugInfo .= "Booking successful! Appointment ID: " . $result['appointment_id'] . "\n";
                    // Set success message in session
                    $_SESSION['success_message'] = $result['message'];
                    
                    // FIXED: Use absolute path instead of relative path
                    $paymentUrl = BASE_URL . '/views/payments/pay.php?id=' . $result['appointment_id'];
                    $debugInfo .= "Redirecting to: " . $paymentUrl . "\n";
                    
                    // IMPORTANT: Write debug info to a file before redirect
                    file_put_contents('debug_booking.log', $debugInfo, FILE_APPEND);
                    
                    // Redirect to payment page
                    header('Location: ' . $paymentUrl);
                    exit;
                } else {
                    $error = $result['message'];
                    $debugInfo .= "Booking failed: " . $result['message'] . "\n";
                }
            } catch (Exception $e) {
                $error = 'An error occurred: ' . $e->getMessage();
                $debugInfo .= "Exception caught: " . $e->getMessage() . "\n";
                $debugInfo .= "Stack trace: " . $e->getTraceAsString() . "\n";
            }
        }
    }
    
    // Write debug info to file
    file_put_contents('debug_booking.log', $debugInfo, FILE_APPEND);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: #b2ebf2;
            --navbar-dark: #207d87;
            --navbar-light: #e6f2f5;
            --header-gradient: linear-gradient(135deg, #207d87 0%, #4dd0e1 50%, #b2ebf2 100%);
            --success-gradient: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            --card-shadow: 0 20px 60px rgba(32, 125, 135, 0.15);
            --card-shadow-hover: 0 25px 80px rgba(32, 125, 135, 0.25);
        }

        body {
            background: linear-gradient(135deg, var(--navbar-light) 0%, #ffffff 50%, var(--accent-color) 100%);
            min-height: 100vh;
            padding: 2rem 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Modern Appointment Card */
        .appointment-booking-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .booking-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .booking-card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-5px);
        }

        .booking-header {
            background: var(--header-gradient);
            color: white;
            text-align: center;
            padding: 3rem 2rem 2.5rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .booking-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .booking-header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .booking-header p {
            font-size: 1.2rem;
            margin-bottom: 0;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        .booking-body {
            padding: 2.5rem 2rem;
        }

        /* Debug Section */
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            font-family: monospace;
            font-size: 0.9rem;
        }

        /* Step Indicator */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 2rem;
            position: relative;
        }

        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(32, 125, 135, 0.4);
        }

        .step span {
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        /* Form Sections */
        .form-section {
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(32, 125, 135, 0.1);
        }

        .form-section h4 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(32, 125, 135, 0.25);
        }

        /* Select2 Styling */
        .select2-container--default .select2-selection--single {
            height: 48px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            background: white;
            padding: 0 12px;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 44px;
            padding-left: 0;
            font-size: 1rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
            right: 12px;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(32, 125, 135, 0.25);
        }

        /* Doctor Info Card */
        .doctor-info-card {
            background: rgba(32, 125, 135, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            display: none;
        }

        /* Time Slots Grid */
        .time-slots-container {
            margin-top: 1rem;
        }

        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .time-slot {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .time-slot.available:hover {
            border-color: var(--primary-color);
            background: rgba(32, 125, 135, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(32, 125, 135, 0.3);
        }

        .time-slot.selected {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(32, 125, 135, 0.4);
        }

        .time-slot.booked {
            background: #dc3545;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .time-slot.past {
            background: #6c757d;
            color: white;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .time-slot.too-soon {
            background: #ffc107;
            color: #856404;
            cursor: not-allowed;
            opacity: 0.8;
        }

        /* Submit Button */
        .btn-book-appointment {
            background: var(--success-gradient);
            border: none;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-book-appointment:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
            color: white;
        }

        .btn-book-appointment:disabled {
            background: #6c757d;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Loading States */
        .loading-slots {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .booking-body {
                padding: 2rem 1.5rem;
            }

            .time-slots-grid {
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
                gap: 0.75rem;
            }

            .time-slot {
                padding: 0.75rem;
                min-height: 60px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class=" mt-3 container">
        <!-- Back Navigation -->
        <!-- <div class="row mb-3">
            <div class="col-12">
                <a href="../dashboard/patient.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div> -->

        <!-- Debug Information (remove in production) -->
        <?php if (isset($_POST['book_appointment'])): ?>
            <div class="debug-info">
                <h6><i class="fas fa-bug me-2"></i>Debug Information</h6>
                <p><strong>Form Submitted:</strong> YES</p>
                <p><strong>CSRF Token Valid:</strong> <?= validateCSRFToken($_POST['csrf_token'] ?? '') ? 'YES' : 'NO' ?></p>
                <p><strong>Doctor ID:</strong> <?= htmlspecialchars($_POST['doctor_id'] ?? 'NOT SET') ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($_POST['appointment_date'] ?? 'NOT SET') ?></p>
                <p><strong>Time Slot:</strong> <?= htmlspecialchars($_POST['time_slot'] ?? 'NOT SET') ?></p>
                <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
                <p><strong>Session User ID:</strong> <?= $_SESSION['user_id'] ?? 'NOT SET' ?></p>
                <p><strong>Session Role:</strong> <?= $_SESSION['user_role'] ?? 'NOT SET' ?></p>
            </div>
        <?php endif; ?>

        <div class="appointment-booking-container">
            <div class="booking-card">
                <div class="booking-header">
                    <h1><i class="fas fa-calendar-plus me-3"></i>Book Your Appointment</h1>
                    <p>Schedule your visit with our expert medical professionals</p>
                </div>

                <div class="booking-body">
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" id="step1">
                            <div class="step-number">1</div>
                            <span>Choose Doctor</span>
                        </div>
                        <div class="step" id="step2">
                            <div class="step-number">2</div>
                            <span>Select Date & Time</span>
                        </div>
                        <div class="step" id="step3">
                            <div class="step-number">3</div>
                            <span>Confirm Details</span>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($debugInfo): ?>
                        <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 1rem; margin: 1rem; border-radius: 5px; font-family: monospace; white-space: pre-line; font-size: 12px;">
                            <strong>DEBUG INFO:</strong><br>
                            <?= htmlspecialchars($debugInfo) ?>
                        </div>
                    <?php endif; ?>


                    <!-- Booking Form -->
                    <form method="POST" id="bookingForm" class="booking-form">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="book_appointment" value="1">

                        <!-- Step 1: Doctor Selection -->
                        <div class="form-section">
                            <h4><i class="fas fa-user-md me-2"></i>Choose Your Healthcare Provider</h4>

                            <!-- Specialization Filter -->
                            <div class="form-group mb-3">
                                <label for="specialization" class="form-label">
                                    <i class="fas fa-stethoscope me-2"></i>Medical Specialization
                                </label>
                                <select class="form-select" id="specialization" name="specialization">
                                    <option value="">🏥 Browse All Specializations</option>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?= $spec['specialization_id'] ?>">
                                            <?= htmlspecialchars($spec['specialization_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Filter doctors by medical specialty</small>
                            </div>

                            <!-- Doctor Selection with Select2 -->
                            <div class="form-group mb-3">
                                <label for="doctor_id" class="form-label required">
                                    <i class="fas fa-user-md me-2"></i>Select Doctor <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="doctor_id" name="doctor_id" required>
                                    <option value="">🔍 Search for a doctor by name or specialization...</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?= $doctor['user_id'] ?>"
                                            data-specialization-id="<?= $doctor['specialization_id'] ?>"
                                            data-fee="<?= $doctor['consultation_fee'] ?>"
                                            data-experience="<?= $doctor['experience_years'] ?? 0 ?>"
                                            data-qualifications="<?= htmlspecialchars($doctor['qualifications'] ?? '') ?>"
                                            data-specialization="<?= htmlspecialchars($doctor['specialization_name']) ?>">
                                            Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> -
                                            <?= htmlspecialchars($doctor['specialization_name']) ?>
                                            (LKR <?= number_format($doctor['consultation_fee'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Doctor Info Display -->
                            <div id="doctorInfo" class="doctor-info-card">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>

                        <!-- Step 2: Date & Time Selection -->
                        <div class="form-section">
                            <h4><i class="fas fa-calendar-alt me-2"></i>Select Date & Time</h4>

                            <!-- Date Input -->
                            <div class="form-group mb-3">
                                <label for="appointment_date" class="form-label required">
                                    <i class="fas fa-calendar me-2"></i>Preferred Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date"
                                    required min="<?= date('Y-m-d') ?>"
                                    max="<?= date('Y-m-d', strtotime('+3 months')) ?>">
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Same-day appointments require 3-hour advance booking
                                </small>
                            </div>

                            <!-- Time Slots Container -->
                            <div class="form-group mb-3">
                                <label class="form-label required">
                                    <i class="fas fa-clock me-2"></i>Available Time Slots <span class="text-danger">*</span>
                                </label>
                                <div id="time-slots-container" class="time-slots-container">
                                    <div class="loading-slots">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Please select a doctor and date to view available time slots
                                    </div>
                                </div>
                                <input type="hidden" name="time_slot" id="selected_time_slot" required>
                            </div>
                        </div>

                        <!-- Step 3: Additional Information -->
                        <div class="form-section">
                            <h4><i class="fas fa-notes-medical me-2"></i>Additional Information</h4>

                            <div class="form-group mb-4">
                                <label for="notes" class="form-label">
                                    <i class="fas fa-comment-medical me-2"></i>Symptoms & Notes
                                </label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"
                                    placeholder="Describe your symptoms, concerns, or reason for visit..."></textarea>
                                <small class="form-text text-muted">
                                    Detailed information helps provide better healthcare (optional)
                                </small>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center">
                            <button type="submit" name="book_appointment" class="btn btn-book-appointment" id="submitBtn" disabled>
                                <i class="fas fa-calendar-check me-2"></i>
                                <span class="btn-text">Book Appointment & Proceed to Payment</span>
                            </button>
                            <p class="mt-2 text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your appointment will be confirmed after secure payment processing
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Set Sri Lankan timezone for JavaScript
        function getSriLankanDateTime() {
            const now = new Date();
            const sriLankanTime = new Date(now.toLocaleString("en-US", {
                timeZone: "Asia/Colombo"
            }));
            return sriLankanTime;
        }

        // Get current Sri Lankan date in YYYY-MM-DD format
        function getSriLankanDate() {
            const sriLankanTime = getSriLankanDateTime();
            return sriLankanTime.toISOString().split('T')[0];
        }

        // FIXED: Store all doctors data with proper structure
        const allDoctors = <?= json_encode($doctors) ?>;

        $(document).ready(function() {
            console.log('🏥 Available doctors loaded:', allDoctors.length, 'doctors');
            console.log('🕐 Current Sri Lankan time:', getSriLankanDateTime().toLocaleString());
            console.log('📍 Base URL:', '<?= BASE_URL ?>');

            // Initialize Select2 for doctor selection
            initializeDoctorSelect();

            // Set minimum date using Sri Lankan timezone
            $('#appointment_date').attr('min', getSriLankanDate());

            // FIXED: Specialization filtering (when user selects specialization first)
            $('#specialization').on('change', function() {
                const specId = $(this).val();
                console.log('🔍 Specialization selected:', specId);

                filterDoctorsBySpecialization(specId);
                hideDoctorInfo();
                clearTimeSlots();
                updateStepIndicator(1);
            });

            // FIXED: Auto-select specialization when doctor is chosen (bidirectional sync)
            $('#doctor_id').on('change', function() {
                const doctorId = $(this).val();

                if (doctorId) {
                    // Find the doctor in our data array
                    const selectedDoctor = allDoctors.find(doctor => doctor.user_id == doctorId);
                    console.log('👨‍⚕️ Doctor selected:', doctorId, 'Doctor data:', selectedDoctor);

                    if (selectedDoctor && selectedDoctor.specialization_id) {
                        // FIXED: Auto-select specialization WITHOUT triggering filtering
                        $('#specialization').off('change');
                        $('#specialization').val(selectedDoctor.specialization_id);
                        $('#specialization').on('change', function() {
                            const specId = $(this).val();
                            filterDoctorsBySpecialization(specId);
                            hideDoctorInfo();
                            clearTimeSlots();
                            updateStepIndicator(1);
                        });

                        // Show doctor info with all details
                        showDoctorInfo(selectedDoctor);
                        updateStepIndicator(2);
                        loadTimeSlots();
                    }
                } else {
                    hideDoctorInfo();
                    updateStepIndicator(1);
                    clearTimeSlots();
                }
            });

            // Date change handler
            $('#appointment_date').on('change', function() {
                if ($('#doctor_id').val() && $(this).val()) {
                    loadTimeSlots();
                }
            });
        });

        function initializeDoctorSelect() {
            $('#doctor_id').select2({
                placeholder: "🔍 Search for a doctor by name or specialization...",
                allowClear: true,
                width: '100%'
            });
        }

        // FIXED: Filter doctors by specialization
        function filterDoctorsBySpecialization(specId) {
            const doctorSelect = $('#doctor_id');

            console.log('🔍 Filtering doctors for specialization ID:', specId);

            // Clear current selection
            doctorSelect.val(null).trigger('change');

            // Destroy Select2 to rebuild options
            doctorSelect.select2('destroy');

            // Remove all options except placeholder
            doctorSelect.find('option:not(:first)').remove();

            // FIXED: Add filtered doctors based on specialization_id match
            const filteredDoctors = specId ?
                allDoctors.filter(doctor => doctor.specialization_id == specId) :
                allDoctors;

            console.log('🏥 Doctors after filtering:', filteredDoctors.length);

            filteredDoctors.forEach(doctor => {
                const optionText = `Dr. ${doctor.first_name} ${doctor.last_name} - ${doctor.specialization_name} (LKR ${parseFloat(doctor.consultation_fee).toLocaleString()})`;
                const option = new Option(optionText, doctor.user_id);

                // Set all required data attributes
                $(option).attr('data-specialization-id', doctor.specialization_id);
                $(option).attr('data-specialization', doctor.specialization_name);
                $(option).attr('data-fee', doctor.consultation_fee);
                $(option).attr('data-experience', doctor.experience_years);
                $(option).attr('data-qualifications', doctor.qualifications);

                doctorSelect.append(option);
            });

            // Reinitialize Select2
            doctorSelect.select2({
                placeholder: "🔍 Search for a doctor by name or specialization...",
                allowClear: true,
                width: '100%'
            });
        }

        // Show doctor information
        function showDoctorInfo(doctor) {
            const doctorInfo = $('#doctorInfo');

            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-user-md me-2"></i>Dr. ${doctor.first_name} ${doctor.last_name}</h5>
                        <p><i class="fas fa-stethoscope me-2"></i><strong>Specialization:</strong> ${doctor.specialization_name}</p>
                        ${doctor.qualifications ? `<p><i class="fas fa-graduation-cap me-2"></i><strong>Qualifications:</strong> ${doctor.qualifications}</p>` : ''}
                    </div>
                    <div class="col-md-6 text-end">
                        <h5 class="text-success">LKR ${parseFloat(doctor.consultation_fee).toLocaleString()}</h5>
                        <small class="text-muted">Consultation Fee</small>
                        ${doctor.experience_years ? `<br><small class="text-muted">${doctor.experience_years} years experience</small>` : ''}
                    </div>
                </div>
            `;

            doctorInfo.html(html).fadeIn();
        }

        // Hide doctor information
        function hideDoctorInfo() {
            $('#doctorInfo').fadeOut();
        }

        // Load available time slots
        function loadTimeSlots() {
            const doctorId = $('#doctor_id').val();
            const date = $('#appointment_date').val();
            const container = $('#time-slots-container');
            const submitBtn = $('#submitBtn');

            console.log('🕒 Loading time slots for:', {
                doctor: doctorId,
                date: date
            });

            if (!doctorId || !date) {
                container.html(`
                    <div class="loading-slots">
                        <i class="fas fa-info-circle me-2"></i>
                        Please select a doctor and date to view available time slots
                    </div>
                `);
                submitBtn.prop('disabled', true);
                return;
            }

            // Show loading state
            container.html(`
                <div class="loading-slots">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Loading available time slots...
                </div>
            `);

            // AJAX call to get time slots
            $.ajax({
                url: '../../api/get_time_slots.php',
                method: 'GET',
                data: {
                    doctor_id: doctorId,
                    date: date
                },
                dataType: 'json',
                success: function(response) {
                    console.log('📅 Time slots response:', response);

                    if (response.error) {
                        container.html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${response.error}
                            </div>
                        `);
                        return;
                    }

                    let html = '<div class="time-slots-grid">';

                    if (response.length === 0) {
                        html = `
                            <div class="alert alert-warning">
                                <i class="fas fa-calendar-times me-2"></i>
                                No available time slots for the selected date.
                            </div>
                        `;
                    } else {
                        response.forEach(slot => {
                            const classes = ['time-slot'];
                            let status = '';

                            if (slot.available) {
                                classes.push('available');
                            } else if (slot.booked) {
                                classes.push('booked');
                                status = 'Booked';
                            } else if (slot.past) {
                                classes.push('past');
                                status = 'Past';
                            } else if (slot.too_soon) {
                                classes.push('too-soon');
                                status = 'Too Soon<br><small>(3hr min)</small>';
                            }

                            html += `
                                <div class="${classes.join(' ')}" 
                                     data-time="${slot.time}" 
                                     ${slot.available ? '' : 'style="cursor: not-allowed;"'}>
                                    <strong>${slot.display}</strong>
                                    ${status ? `<br><small>${status}</small>` : ''}
                                </div>
                            `;
                        });
                        html += '</div>';
                    }

                    container.html(html);

                    // Add click handlers for available slots
                    $('.time-slot.available').click(function() {
                        $('.time-slot').removeClass('selected');
                        $(this).addClass('selected');
                        $('#selected_time_slot').val($(this).data('time'));
                        submitBtn.prop('disabled', false);
                        updateStepIndicator(3);
                        console.log('⏰ Time slot selected:', $(this).data('time'));
                    });
                },
                error: function(xhr, status, error) {
                    console.error('❌ AJAX error:', xhr, status, error);
                    container.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading time slots. Please try again.
                        </div>
                    `);
                }
            });
        }

        function clearTimeSlots() {
            $('#time-slots-container').html(`
                <div class="loading-slots">
                    <i class="fas fa-info-circle me-2"></i>
                    Please select a doctor and date to view available time slots
                </div>
            `);
            $('#selected_time_slot').val('');
            $('#submitBtn').prop('disabled', true);
        }

        function updateStepIndicator(activeStep) {
            $('.step').removeClass('active');
            for (let i = 1; i <= activeStep; i++) {
                $(`#step${i}`).addClass('active');
            }
        }

        // ENHANCED DEBUGGING: Form validation and submission with detailed logging
        $('#bookingForm').on('submit', function(e) {
            const formData = {
                doctor_id: $('#doctor_id').val(),
                appointment_date: $('#appointment_date').val(),
                time_slot: $('#selected_time_slot').val(),
                notes: $('#notes').val(),
                csrf_token: $('input[name="csrf_token"]').val()
            };

            console.log('🚀 Form submission attempted');
            console.log('📋 Form data:', formData);

            // Validate required fields
            if (!formData.doctor_id) {
                e.preventDefault();
                alert('Please select a doctor');
                console.log('❌ Form submission stopped: No doctor selected');
                return false;
            }

            if (!formData.appointment_date) {
                e.preventDefault();
                alert('Please select an appointment date');
                console.log('❌ Form submission stopped: No date selected');
                return false;
            }

            if (!formData.time_slot) {
                e.preventDefault();
                alert('Please select a time slot');
                console.log('❌ Form submission stopped: No time slot selected');
                return false;
            }

            // Show loading state
            const submitBtn = $(this).find('#submitBtn');
            const btnText = submitBtn.find('.btn-text');

            btnText.text('Processing Your Appointment...');
            submitBtn.prepend('<i class="fas fa-spinner fa-spin me-2"></i>');
            submitBtn.prop('disabled', true);

            console.log('✅ Form validation passed, submitting form...');
            console.log('🔄 Redirecting to payment page after successful booking...');

            // Allow form submission
            return true;
        });
    </script>
</body>

</html>