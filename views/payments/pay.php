<?php
require_once '../../config/config.php';
require_once '../../config/payhere.php';
require_once '../../services/AppointmentService.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: ../dashboard/patient.php');
    exit;
}

$appointmentId = intval($_GET['id']);

// Get appointment details with proper error handling
try {
    global $db;
    $stmt = $db->prepare("
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               p.first_name, p.last_name,
               p.email as patient_email,
               p.mobile_number as patient_mobile,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               s.specialization_name,
               dd.consultation_fee
        FROM appointments a
        JOIN users p ON a.patient_id = p.user_id
        JOIN users d ON a.doctor_id = d.user_id
        JOIN doctor_details dd ON a.doctor_id = dd.user_id
        JOIN specializations s ON dd.specialization_id = s.specialization_id
        WHERE a.appointment_id = ? AND a.patient_id = ?
    ");
    $stmt->execute([$appointmentId, $_SESSION['user_id']]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $_SESSION['error_message'] = 'Appointment not found or unauthorized access';
        header('Location: ../dashboard/patient.php');
        exit;
    }

    if ($appointment['payment_status'] === 'PAID') {
        $_SESSION['success_message'] = 'This appointment has already been paid for';
        header('Location: ../dashboard/patient.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Payment page error: " . $e->getMessage());
    $_SESSION['error_message'] = 'System error occurred. Please try again.';
    header('Location: ../dashboard/patient.php');
    exit;
}

// PayHere payment preparation
$amount = number_format($appointment['consultation_fee'], 2, '.', '');
$orderId = "ASAA_" . $appointmentId . "_" . time();
$hash = PayHereConfig::generateHash($orderId, $amount);

// Prepare customer details
$firstName = $appointment['first_name'] ?? 'Patient';
$lastName = $appointment['last_name'] ?? '';
$email = $appointment['patient_email'] ?? 'patient@example.com';
$phone = $appointment['patient_mobile'] ?? '0771234567';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: #b2ebf2;
            --header-gradient: linear-gradient(135deg, #207d87 0%, #4dd0e1 50%, #b2ebf2 100%);
            --payment-gradient: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%);
            --card-shadow: 0 15px 50px rgba(32, 125, 135, 0.15);
        }

        body {
            background: linear-gradient(135deg, var(--navbar-light) 0%, #f0f8ff 50%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem 0;
            min-height: 100vh;
        }

        .payment-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .payment-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(32, 125, 135, 0.1);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .payment-header {
            background: var(--payment-gradient);
            color: white;
            text-align: center;
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .payment-header::before {
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

        .payment-header h1 {
            font-size: 2.8rem;
            font-weight: bold;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }

        .payment-body {
            padding: 3rem 2.5rem;
        }

        .appointment-summary {
            background: linear-gradient(135deg, rgba(32, 125, 135, 0.08), rgba(178, 235, 242, 0.15));
            border: 2px solid rgba(32, 125, 135, 0.2);
            border-radius: 18px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .appointment-summary::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 150px;
            height: 150px;
            background: rgba(32, 125, 135, 0.1);
            border-radius: 50%;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .payment-method {
            background: linear-gradient(135deg, #ffffff, #f8fdfe);
            border: 2px solid #e6f3ff;
            border-radius: 16px;
            padding: 2rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .payment-method::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .payment-method:hover,
        .payment-method.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, var(--accent-color), #f0f8ff);
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(32, 125, 135, 0.2);
        }

        .payment-method:hover::before,
        .payment-method.selected::before {
            transform: scaleX(1);
        }

        .payment-method i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .payment-method h6 {
            color: var(--navbar-dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .btn-pay-now {
            background: var(--payment-gradient);
            border: none;
            border-radius: 16px;
            padding: 1.5rem 2rem;
            font-size: 1.25rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
        }

        .btn-pay-now:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(40, 167, 69, 0.4);
        }

        .btn-pay-now:disabled {
            background: #6c757d;
            opacity: 0.6;
            transform: none;
            box-shadow: none;
        }

        .sandbox-notice {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .payment-body {
                padding: 2rem 1.5rem;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }

            .payment-header h1 {
                font-size: 2.2rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="../dashboard/patient.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <div class="payment-container">
            <div class="payment-card">
                <div class="payment-header">
                    <h1><i class="fas fa-shield-alt me-3"></i>Secure Payment Portal</h1>
                    <p style="font-size: 1.2rem; opacity: 0.9; position: relative; z-index: 2;">
                        Complete your ASAA Healthcare appointment payment
                    </p>
                </div>

                <div class="payment-body">
                    <!-- Sandbox Testing Notice -->
                    <div class="sandbox-notice">
                        <h6><i class="fas fa-flask me-2"></i>Sandbox Testing Mode</h6>
                        <p class="mb-0">
                            <strong>This is a test environment.</strong> Use PayHere test cards for payments.
                            No real money will be charged.
                        </p>
                    </div>

                    <!-- Appointment Summary -->
                    <div class="appointment-summary">
                        <div style="position: relative; z-index: 2;">
                            <h4 class="mb-3" style="color: var(--primary-color);">
                                <i class="fas fa-file-invoice-dollar me-2"></i>Appointment Summary
                            </h4>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <strong>Doctor:</strong> Dr. <?= htmlspecialchars($appointment['doctor_name']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Specialization:</strong> <?= htmlspecialchars($appointment['specialization_name']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Date:</strong> <?= date('l, F j, Y', strtotime($appointment['appointment_date'])) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Time:</strong> <?= date('g:i A', strtotime($appointment['time_slot'])) ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <strong>Patient:</strong> <?= htmlspecialchars($appointment['patient_name']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Appointment ID:</strong> #<?= $appointment['appointment_id'] ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Order ID:</strong> <?= $orderId ?>
                                    </div>
                                    <div class="mb-0">
                                        <strong style="color: #28a745; font-size: 1.3rem;">
                                            <i class="fas fa-tag me-2"></i>
                                            Total Amount: LKR <?= number_format($appointment['consultation_fee'], 2) ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PayHere Payment Form (Localhost Compatible) -->
                    <div class="text-center">
                        <h5 class="mb-4" style="color: var(--primary-color);">
                            <i class="fas fa-credit-card me-2"></i>Proceed with Secure Payment
                        </h5>
                        <!-- PayHere Button-->
                        <button type="button" id="payhere-payment" class="btn btn-pay-now btn-lg" onclick="initiatePayment()">
                            <span class="btn-text">
                                <i class="fas fa-lock me-2"></i>
                                Pay Securely - LKR <?= number_format($amount, 2) ?>
                            </span>
                        </button>

                    </div>

                    <!-- PayHere Test Cards Information -->
                    <div class="mt-4">
                        <div class="alert alert-info" style="border-radius: 12px;">
                            <h6><i class="fas fa-credit-card me-2"></i>PayHere Test Cards (Sandbox)</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Visa Test Card:</strong><br>
                                        <code>4916217501611292</code><br>
                                        <strong>Expiry:</strong> Any future date<br>
                                        <strong>CVV:</strong> Any 3 digits
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>MasterCard Test Card:</strong><br>
                                        <code>5307732125531191</code><br>
                                        <strong>Expiry:</strong> Any future date<br>
                                        <strong>CVV:</strong> Any 3 digits
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alternative Payment Options -->
                    <div class="mt-4">
                        <h6 style="color: var(--primary-color);">
                            <i class="fas fa-info-circle me-2"></i>Alternative Payment Methods
                        </h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-light" style="border: 1px solid rgba(32, 125, 135, 0.2); border-radius: 12px;">
                                    <h6><i class="fas fa-university me-2"></i>Bank Transfer</h6>
                                    <p class="mb-1"><strong>Bank:</strong> Commercial Bank PLC</p>
                                    <p class="mb-1"><strong>Account:</strong> ASAA Healthcare</p>
                                    <p class="mb-1"><strong>Number:</strong> 8001234567</p>
                                    <p class="mb-0"><strong>Reference:</strong> <?= $orderId ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-light" style="border: 1px solid rgba(32, 125, 135, 0.2); border-radius: 12px;">
                                    <h6><i class="fas fa-money-bill-wave me-2"></i>Cash Payment</h6>
                                    <p class="mb-1">Pay at our clinic reception</p>
                                    <p class="mb-1"><strong>Amount:</strong> LKR <?= number_format($appointment['consultation_fee'], 2) ?></p>
                                    <p class="mb-0"><small>Please arrive 15 minutes early</small></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-lock me-1"></i>
                            All payments are processed through PayHere's secure SSL-encrypted gateway
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>

    <script>
        // PayHere configuration object
        const paymentConfig = {
            sandbox: true,
            merchant_id: '<?= PayHereConfig::MERCHANT_ID ?>',
            return_url: '<?= PayHereConfig::RETURN_URL ?>',
            cancel_url: '<?= PayHereConfig::CANCEL_URL ?>',
            notify_url: '<?= PayHereConfig::NOTIFY_URL ?>',
            order_id: '<?= $orderId ?>',
            items: 'ASAA Healthcare - Medical Consultation',
            currency: '<?= PayHereConfig::CURRENCY ?>',
            amount: '<?= $amount ?>',
            first_name: '<?= htmlspecialchars($firstName, ENT_QUOTES) ?>',
            last_name: '<?= htmlspecialchars($lastName, ENT_QUOTES) ?>',
            email: '<?= htmlspecialchars($email, ENT_QUOTES) ?>',
            phone: '<?= htmlspecialchars($phone, ENT_QUOTES) ?>',
            address: 'Colombo, Sri Lanka',
            city: 'Colombo',
            country: 'Sri Lanka',
            hash: '<?= $hash ?>',
            custom_1: '<?= $appointmentId ?>',
            custom_2: '<?= $_SESSION['user_id'] ?>'
        };

        // Payment event handlers
        payhere.onCompleted = function onCompleted(orderId) {
            console.log("✅ Payment completed. OrderID:" + orderId);
            showToast('✅ Payment completed successfully! Redirecting...', 'success');

            // Redirect to return URL after brief delay
            setTimeout(function() {
                window.location.href = paymentConfig.return_url + '?order_id=' + orderId;
            }, 2000);
        };

        payhere.onDismissed = function onDismissed() {
            console.log("❌ Payment dismissed by user");
            showToast('⚠️ Payment cancelled. You can try again when ready.', 'warning');
        };

        payhere.onError = function onError(error) {
            console.log("🔴 Payment error:" + error);
            showToast('❌ Payment error occurred: ' + error, 'error');
        };

        // Initialize payment function
        function initiatePayment() {
            console.log('💳 Initiating PayHere payment...');
            console.log('Order ID:', paymentConfig.order_id);
            console.log('Amount:', paymentConfig.amount);

            // Show loading state
            const payBtn = document.getElementById('payhere-payment');
            const originalHtml = payBtn.innerHTML;
            payBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading PayHere...';
            payBtn.disabled = true;

            showToast('🔄 Loading secure payment gateway...', 'info');

            // Start payment after brief delay for UX
            setTimeout(function() {
                try {
                    payhere.startPayment(paymentConfig);

                    // Reset button after modal opens
                    setTimeout(function() {
                        payBtn.innerHTML = originalHtml;
                        payBtn.disabled = false;
                    }, 1000);
                } catch (error) {
                    console.error('Payment initialization error:', error);
                    showToast('❌ Failed to load payment gateway', 'error');
                    payBtn.innerHTML = originalHtml;
                    payBtn.disabled = false;
                }
            }, 500);
        }

        // Page initialization
        $(document).ready(function() {
            console.log('💳 PayHere payment page loaded (JavaScript SDK)');
            console.log('Order ID:', paymentConfig.order_id);
            console.log('Amount: LKR', paymentConfig.amount);

            // Payment method selection (for future enhancements)
            $('.payment-method').click(function() {
                $('.payment-method').removeClass('selected');
                $(this).addClass('selected');
                const method = $(this).data('method');
                console.log('Payment method selected:', method);
            });

            // Show welcome message
            setTimeout(() => {
                showToast('💳 Secure payment portal loaded successfully', 'success');
            }, 1000);
        });

        // Toast notification system (keep existing function)
        function showToast(message, type = 'info') {
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: 'var(--primary-color)'
            };
            const icons = {
                success: 'check-circle',
                error: 'exclamation-triangle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };

            const toastId = 'toast_' + Date.now();
            const toastHtml = `
        <div class="toast align-items-center text-white border-0" role="alert" 
             style="background: ${colors[type]}; border-radius: 12px; min-width: 350px;" id="${toastId}">
            <div class="d-flex">
                <div class="toast-body fw-bold">
                    <i class="fas fa-${icons[type]} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

            if (!document.getElementById('toastContainer')) {
                document.body.insertAdjacentHTML('beforeend',
                    '<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>');
            }

            document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);
            const toast = new bootstrap.Toast(document.getElementById(toastId), {
                delay: 4000
            });
            toast.show();
        }
    </script>


</body>

</html>