<?php
require_once '../config/config.php';
require_once '../services/AppointmentService.php';
require_once '../repositories/AppointmentRepository.php';

// Start output buffering to prevent header issues
ob_start();

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'book':
        handleBooking();
        break;
    case 'cancel':
        handleCancelation();
        break;
    case 'update_status':
        handleStatusUpdate();
        break;
    case 'get_slots':
        handleGetSlots();
        break;
    default:
        // Redirect to dashboard based on role
        requireLogin();
        $redirectUrl = getDashboardUrl($_SESSION['user_role']);
        header('Location: ' . $redirectUrl);
        exit;
}

function handleBooking() {
    requireLogin();
    
    // Only patients can book appointments
    if ($_SESSION['user_role'] !== 'patient') {
        $_SESSION['error_message'] = 'Access denied. Only patients can book appointments.';
        header('Location: ' . BASE_URL . '/access-denied.php');
        exit;
    }
    
    if ($_POST && isset($_POST['book_appointment'])) {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Security validation failed. Please try again.';
            header('Location: ' . BASE_URL . '/views/appointments/book.php');
            exit;
        }
        
        // Validate required fields
        $requiredFields = ['doctor_id', 'appointment_date', 'time_slot'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            $_SESSION['error_message'] = 'Please fill all required fields: ' . implode(', ', $missingFields);
            header('Location: ' . BASE_URL . '/views/appointments/book.php');
            exit;
        }
        
        try {
            $appointmentService = new AppointmentService();
            $result = $appointmentService->bookAppointment(
                $_SESSION['user_id'],
                intval($_POST['doctor_id']),
                $_POST['appointment_date'],
                $_POST['time_slot'],
                $_POST['notes'] ?? null
            );
            
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
                
                // Redirect to payment page
                header('Location: ' . BASE_URL . '/views/payments/pay.php?id=' . $result['appointment_id']);
                exit;
            } else {
                $_SESSION['error_message'] = $result['message'];
                header('Location: ' . BASE_URL . '/views/appointments/book.php');
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Booking error: " . $e->getMessage());
            $_SESSION['error_message'] = 'An error occurred while booking your appointment. Please try again.';
            header('Location: ' . BASE_URL . '/views/appointments/book.php');
            exit;
        }
    }
    
    // If not POST request, redirect to booking page
    header('Location: ' . BASE_URL . '/views/appointments/book.php');
    exit;
}

function handleCancelation() {
    requireLogin();
    
    if (!isset($_POST['appointment_id'])) {
        $_SESSION['error_message'] = 'Invalid appointment ID';
        redirectToDashboard();
        return;
    }
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Security validation failed';
        redirectToDashboard();
        return;
    }
    
    try {
        $appointmentService = new AppointmentService();
        $appointmentId = intval($_POST['appointment_id']);
        
        // Verify user has permission to cancel this appointment
        $appointment = $appointmentService->getAppointmentById($appointmentId);
        
        if (!$appointment) {
            $_SESSION['error_message'] = 'Appointment not found';
            redirectToDashboard();
            return;
        }
        
        // Check permissions
        $canCancel = false;
        if ($_SESSION['user_role'] === 'patient' && $appointment['patient_id'] == $_SESSION['user_id']) {
            $canCancel = true;
        } elseif (in_array($_SESSION['user_role'], ['admin', 'staff', 'doctor'])) {
            $canCancel = true;
        }
        
        if (!$canCancel) {
            $_SESSION['error_message'] = 'You do not have permission to cancel this appointment';
            redirectToDashboard();
            return;
        }
        
        $result = $appointmentService->updateAppointmentStatus($appointmentId, 'CANCELLED', $_SESSION['user_id']);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Appointment cancelled successfully';
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
    } catch (Exception $e) {
        error_log("Cancellation error: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while cancelling the appointment';
    }
    
    redirectToDashboard();
}

function handleStatusUpdate() {
    requireLogin();
    
    // Only doctors, staff, and admin can update appointment status
    if (!in_array($_SESSION['user_role'], ['admin', 'staff', 'doctor'])) {
        $_SESSION['error_message'] = 'Access denied';
        redirectToDashboard();
        return;
    }
    
    if (!isset($_POST['appointment_id']) || !isset($_POST['status'])) {
        $_SESSION['error_message'] = 'Missing required parameters';
        redirectToDashboard();
        return;
    }
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Security validation failed';
        redirectToDashboard();
        return;
    }
    
    try {
        $appointmentService = new AppointmentService();
        $appointmentId = intval($_POST['appointment_id']);
        $status = $_POST['status'];
        
        // Validate status
        $validStatuses = ['REVIEW', 'ACCEPTED', 'CONSULTING', 'COMPLETED', 'CANCELLED'];
        if (!in_array($status, $validStatuses)) {
            $_SESSION['error_message'] = 'Invalid status';
            redirectToDashboard();
            return;
        }
        
        $result = $appointmentService->updateAppointmentStatus($appointmentId, $status, $_SESSION['user_id']);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Appointment status updated successfully';
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
    } catch (Exception $e) {
        error_log("Status update error: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while updating appointment status';
    }
    
    redirectToDashboard();
}

function handleGetSlots() {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }
        
        $appointmentService = new AppointmentService();
        $slots = $appointmentService->getAvailableSlots($_GET['doctor_id'], $_GET['date']);
        echo json_encode($slots);
        
    } catch (Exception $e) {
        error_log("Get slots error: " . $e->getMessage());
        echo json_encode(['error' => 'Unable to load time slots']);
    }
    exit;
}

function redirectToDashboard() {
    $dashboardUrl = getDashboardUrl($_SESSION['user_role'] ?? 'patient');
    header('Location: ' . $dashboardUrl);
    exit;
}

function getDashboardUrl($role) {
    switch ($role) {
        case 'admin':
            return BASE_URL . '/views/dashboard/admin.php';
        case 'staff':
            return BASE_URL . '/views/dashboard/staff.php';
        case 'doctor':
            return BASE_URL . '/views/dashboard/doctor.php';
        case 'patient':
            return BASE_URL . '/views/dashboard/patient.php';
        default:
            return BASE_URL . '/index.php';
    }
}

// Clean output buffer and exit
ob_end_flush();
?>
