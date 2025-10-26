<?php
require_once '../config/config.php';

// Set timezone to Sri Lankan time
date_default_timezone_set('Asia/Colombo');

// PayHere notification handler
if ($_POST) {
    $merchant_id = $_POST['merchant_id'];
    $order_id = $_POST['order_id'];
    $payhere_amount = $_POST['payhere_amount'];
    $payhere_currency = $_POST['payhere_currency'];
    $status_code = $_POST['status_code'];
    $md5sig = $_POST['md5sig'];
    
    $merchant_secret = "MjUxMDEyMjE2MTMyMDk5ODY1NTMxOTMyNzQxNDMxNzA0MzMwNjky"; // Your sandbox secret
    
    $local_md5sig = strtoupper(
        md5(
            $merchant_id . 
            $order_id . 
            $payhere_amount . 
            $payhere_currency . 
            $status_code . 
            strtoupper(md5($merchant_secret))
        )
    );
    
    if (($local_md5sig === $md5sig) && ($status_code == 2)) {
        // Payment success
        try {
            global $db;
            
            // Extract appointment ID from order ID
            if (preg_match('/ASAA_(\d+)_/', $order_id, $matches)) {
                $appointmentId = $matches[1];
                
                // Update payment status
                $stmt = $db->prepare("
                    UPDATE appointments 
                    SET payment_status = 'PAID', updated_at = NOW()
                    WHERE appointment_id = ?
                ");
                $stmt->execute([$appointmentId]);
                
                // Log successful payment
                error_log("PayHere payment successful: Order ID $order_id, Appointment ID $appointmentId");
            }
        } catch (Exception $e) {
            error_log("PayHere notification processing error: " . $e->getMessage());
        }
    } else {
        // Payment failed or invalid
        error_log("PayHere payment failed or invalid signature: Order ID $order_id");
    }
}
?>
