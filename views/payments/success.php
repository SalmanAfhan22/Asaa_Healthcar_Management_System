<?php
require_once '../../config/config.php';

// Set timezone to Sri Lankan time
date_default_timezone_set('Asia/Colombo');

requireLogin();

// Update appointment payment status
if (isset($_GET['order_id'])) {
    try {
        global $db;
        
        $orderId = $_GET['order_id'];
        
        // Extract appointment ID from order ID
        if (preg_match('/ASAA_(\d+)_/', $orderId, $matches)) {
            $appointmentId = $matches[1];
            
            // Update payment status
            $stmt = $db->prepare("
                UPDATE appointments 
                SET payment_status = 'PAID', updated_at = NOW()
                WHERE appointment_id = ?
            ");
            $stmt->execute([$appointmentId]);
            
            $_SESSION['success_message'] = 'Payment successful! Your appointment has been confirmed.';
        }
    } catch (Exception $e) {
        error_log("Payment success processing error: " . $e->getMessage());
    }
}

header('Location: ' . BASE_URL . '/views/dashboard/patient.php');
exit;
?>
