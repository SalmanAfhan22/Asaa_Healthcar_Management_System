<?php
require_once '../../config/config.php';

// Set timezone to Sri Lankan time
date_default_timezone_set('Asia/Colombo');

requireLogin();

$_SESSION['error_message'] = 'Payment was cancelled. Your appointment is still pending payment.';
header('Location: ' . BASE_URL . '/views/dashboard/patient.php');
exit;
?>
