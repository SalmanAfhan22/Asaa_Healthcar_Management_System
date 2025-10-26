<?php
class EmailService {
    private $fromEmail = 'noreply@asaahealthcare.com';
    private $fromName = 'ASAA Healthcare';
    
    /**
     * Send appointment confirmation email
     */
    public function sendAppointmentConfirmation($appointment, $patient, $doctor) {
        $subject = 'Appointment Confirmed - ASAA Healthcare';
        $message = $this->getConfirmationEmailTemplate($appointment, $patient, $doctor);
        
        return $this->sendEmail($patient['email'], $subject, $message);
    }
    
    /**
     * Send appointment reminder email
     */
    public function sendAppointmentReminder($appointment, $patient, $doctor) {
        $subject = 'Appointment Reminder - Tomorrow at ASAA Healthcare';
        $message = $this->getReminderEmailTemplate($appointment, $patient, $doctor);
        
        return $this->sendEmail($patient['email'], $subject, $message);
    }
    
    /**
     * Send appointment cancellation email
     */
    public function sendAppointmentCancellation($appointment, $patient, $doctor, $reason = '') {
        $subject = 'Appointment Cancelled - ASAA Healthcare';
        $message = $this->getCancellationEmailTemplate($appointment, $patient, $doctor, $reason);
        
        return $this->sendEmail($patient['email'], $subject, $message);
    }
    
    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation($appointment, $patient, $paymentDetails) {
        $subject = 'Payment Confirmation - ASAA Healthcare';
        $message = $this->getPaymentConfirmationTemplate($appointment, $patient, $paymentDetails);
        
        return $this->sendEmail($patient['email'], $subject, $message);
    }
    
    /**
     * FIXED: Get appointment confirmation email template
     */
    private function getConfirmationEmailTemplate($appointment, $patient, $doctor) {
        $appointmentDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($appointment['time_slot']));
        $consultationFee = number_format($appointment['consultation_fee'] ?? 0, 2);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f8f9fa;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white; 
                    border-radius: 12px; 
                    overflow: hidden; 
                    box-shadow: 0 4px 20px rgba(32, 125, 135, 0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #207d87, #4dd0e1); 
                    color: white; 
                    padding: 2.5rem 2rem; 
                    text-align: center; 
                }
                .header h1 { 
                    margin: 0 0 0.5rem 0; 
                    font-size: 2rem; 
                    font-weight: bold; 
                }
                .header p { 
                    margin: 0; 
                    font-size: 1.1rem; 
                    opacity: 0.9; 
                }
                .content { 
                    padding: 2rem; 
                }
                .appointment-details { 
                    background: linear-gradient(135deg, #f0f8ff, #e6f3ff); 
                    padding: 2rem; 
                    border-radius: 12px; 
                    margin: 1.5rem 0; 
                    border-left: 4px solid #207d87;
                }
                .appointment-details h3 { 
                    color: #207d87; 
                    margin-top: 0; 
                    font-size: 1.3rem;
                }
                .detail-row { 
                    display: flex; 
                    justify-content: space-between; 
                    margin-bottom: 0.75rem; 
                    padding: 0.5rem 0;
                    border-bottom: 1px solid rgba(32, 125, 135, 0.1);
                }
                .detail-row:last-child {
                    border-bottom: none;
                }
                .detail-label { 
                    font-weight: 600; 
                    color: #207d87; 
                }
                .detail-value { 
                    color: #333; 
                    font-weight: 500;
                }
                .important-note { 
                    background: #fff3cd; 
                    border: 1px solid #ffeaa7; 
                    padding: 1rem; 
                    border-radius: 8px; 
                    margin: 1.5rem 0;
                }
                .footer { 
                    background: #f8f9fa; 
                    padding: 2rem; 
                    text-align: center; 
                    font-size: 0.9rem; 
                    color: #6c757d; 
                    border-top: 1px solid #e9ecef;
                }
                .btn-primary {
                    display: inline-block;
                    background: linear-gradient(135deg, #207d87, #4dd0e1);
                    color: white;
                    padding: 0.75rem 2rem;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    margin: 1rem 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏥 ASAA Healthcare</h1>
                    <p>Your Appointment is Confirmed!</p>
                </div>
                
                <div class='content'>
                    <h2>Dear " . htmlspecialchars($patient['first_name']) . ",</h2>
                    <p>We're pleased to confirm your medical appointment has been successfully scheduled.</p>
                    
                    <div class='appointment-details'>
                        <h3>📅 Appointment Details</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>Doctor:</span>
                            <span class='detail-value'>Dr. " . htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Specialization:</span>
                            <span class='detail-value'>" . htmlspecialchars($appointment['specialization_name'] ?? 'General') . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Date:</span>
                            <span class='detail-value'>{$appointmentDate}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Time:</span>
                            <span class='detail-value'>{$appointmentTime}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Consultation Fee:</span>
                            <span class='detail-value'>LKR {$consultationFee}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Appointment ID:</span>
                            <span class='detail-value'>#" . htmlspecialchars($appointment['appointment_id']) . "</span>
                        </div>
                    </div>
                    
                    <div class='important-note'>
                        <strong>⏰ Important Reminders:</strong>
                        <ul>
                            <li>Please arrive <strong>15 minutes before</strong> your scheduled time</li>
                            <li>Bring a valid ID and your payment confirmation</li>
                            <li>If you need to reschedule, please contact us at least 24 hours in advance</li>
                        </ul>
                    </div>
                    
                    <p>If you have any questions or need to make changes to your appointment, please don't hesitate to contact us.</p>
                    
                    <p><strong>Thank you for choosing ASAA Healthcare!</strong></p>
                </div>
                
                <div class='footer'>
                    <p><strong>ASAA Healthcare</strong></p>
                    <p>📧 Email: info@asaahealthcare.com | 📞 Phone: +94 11 123 4567</p>
                    <p>🏥 Address: No. 123, Healthcare Street, Colombo 03, Sri Lanka</p>
                    <p><small>This is an automated email. Please do not reply directly to this message.</small></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * FIXED: Get appointment reminder email template
     */
    private function getReminderEmailTemplate($appointment, $patient, $doctor) {
        $appointmentDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($appointment['time_slot']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f8f9fa;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white; 
                    border-radius: 12px; 
                    overflow: hidden; 
                    box-shadow: 0 4px 20px rgba(255, 193, 7, 0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #ffc107, #ffca28); 
                    color: #212529; 
                    padding: 2.5rem 2rem; 
                    text-align: center; 
                }
                .content { 
                    padding: 2rem; 
                }
                .reminder-box { 
                    background: linear-gradient(135deg, #fff3cd, #ffeaa7); 
                    border: 2px solid #ffc107; 
                    padding: 2rem; 
                    border-radius: 12px; 
                    margin: 1.5rem 0; 
                    text-align: center;
                }
                .appointment-info { 
                    background: #f0f8ff; 
                    padding: 1.5rem; 
                    border-radius: 8px; 
                    margin: 1rem 0;
                }
                .footer { 
                    background: #f8f9fa; 
                    padding: 2rem; 
                    text-align: center; 
                    font-size: 0.9rem; 
                    color: #6c757d;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⏰ Appointment Reminder</h1>
                    <p>ASAA Healthcare</p>
                </div>
                
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($patient['first_name']) . ",</h2>
                    
                    <div class='reminder-box'>
                        <h3>🔔 Your appointment is tomorrow!</h3>
                        <p>Don't forget your scheduled consultation at ASAA Healthcare.</p>
                    </div>
                    
                    <div class='appointment-info'>
                        <h4>📋 Appointment Details:</h4>
                        <p><strong>Doctor:</strong> Dr. " . htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) . "</p>
                        <p><strong>Date:</strong> {$appointmentDate}</p>
                        <p><strong>Time:</strong> {$appointmentTime}</p>
                        <p><strong>Appointment ID:</strong> #" . htmlspecialchars($appointment['appointment_id']) . "</p>
                    </div>
                    
                    <p><strong>📝 Preparation Checklist:</strong></p>
                    <ul>
                        <li>✅ Arrive 15 minutes early</li>
                        <li>✅ Bring valid identification</li>
                        <li>✅ Bring payment confirmation</li>
                        <li>✅ List any current medications</li>
                        <li>✅ Prepare questions for your doctor</li>
                    </ul>
                    
                    <p>We look forward to seeing you tomorrow!</p>
                </div>
                
                <div class='footer'>
                    <p><strong>ASAA Healthcare</strong></p>
                    <p>📞 +94 11 123 4567 | 📧 info@asaahealthcare.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * FIXED: Get appointment cancellation email template
     */
    private function getCancellationEmailTemplate($appointment, $patient, $doctor, $reason = '') {
        $appointmentDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($appointment['time_slot']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    background-color: #f8f9fa;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white; 
                    border-radius: 12px; 
                    overflow: hidden; 
                    box-shadow: 0 4px 20px rgba(220, 53, 69, 0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #dc3545, #c82333); 
                    color: white; 
                    padding: 2.5rem 2rem; 
                    text-align: center; 
                }
                .content { 
                    padding: 2rem; 
                }
                .cancellation-notice { 
                    background: #f8d7da; 
                    border: 1px solid #dc3545; 
                    padding: 1.5rem; 
                    border-radius: 8px; 
                    margin: 1.5rem 0;
                }
                .footer { 
                    background: #f8f9fa; 
                    padding: 2rem; 
                    text-align: center; 
                    font-size: 0.9rem; 
                    color: #6c757d;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>❌ Appointment Cancelled</h1>
                    <p>ASAA Healthcare</p>
                </div>
                
                <div class='content'>
                    <h2>Dear " . htmlspecialchars($patient['first_name']) . ",</h2>
                    
                    <div class='cancellation-notice'>
                        <h4>Your appointment has been cancelled</h4>
                        <p><strong>Doctor:</strong> Dr. " . htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) . "</p>
                        <p><strong>Date:</strong> {$appointmentDate}</p>
                        <p><strong>Time:</strong> {$appointmentTime}</p>" . 
                        ($reason ? "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>" : "") . "
                    </div>
                    
                    <p>We apologize for any inconvenience this may cause. If you would like to reschedule, please contact us or book a new appointment online.</p>
                    
                    <p>Thank you for your understanding.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>ASAA Healthcare</strong></p>
                    <p>📞 +94 11 123 4567 | 📧 info@asaahealthcare.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * FIXED: Get payment confirmation email template
     */
    private function getPaymentConfirmationTemplate($appointment, $patient, $paymentDetails) {
        $appointmentDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
        $amount = number_format($paymentDetails['amount'] ?? 0, 2);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    background-color: #f8f9fa;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white; 
                    border-radius: 12px; 
                    overflow: hidden; 
                    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #28a745, #20c997); 
                    color: white; 
                    padding: 2.5rem 2rem; 
                    text-align: center; 
                }
                .content { 
                    padding: 2rem; 
                }
                .payment-summary { 
                    background: #d4edda; 
                    border: 1px solid #28a745; 
                    padding: 1.5rem; 
                    border-radius: 8px; 
                    margin: 1.5rem 0;
                }
                .footer { 
                    background: #f8f9fa; 
                    padding: 2rem; 
                    text-align: center; 
                    font-size: 0.9rem; 
                    color: #6c757d;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>💳 Payment Confirmed</h1>
                    <p>ASAA Healthcare</p>
                </div>
                
                <div class='content'>
                    <h2>Dear " . htmlspecialchars($patient['first_name']) . ",</h2>
                    
                    <p>Your payment has been successfully processed!</p>
                    
                    <div class='payment-summary'>
                        <h4>💰 Payment Details</h4>
                        <p><strong>Amount:</strong> LKR {$amount}</p>
                        <p><strong>Payment Method:</strong> " . htmlspecialchars($paymentDetails['method'] ?? 'PayHere') . "</p>
                        <p><strong>Transaction ID:</strong> " . htmlspecialchars($paymentDetails['transaction_id'] ?? 'N/A') . "</p>
                        <p><strong>Date:</strong> " . date('F j, Y g:i A') . "</p>
                    </div>
                    
                    <p>Your appointment on {$appointmentDate} is now fully confirmed.</p>
                    
                    <p>Thank you for choosing ASAA Healthcare!</p>
                </div>
                
                <div class='footer'>
                    <p><strong>ASAA Healthcare</strong></p>
                    <p>📞 +94 11 123 4567 | 📧 info@asaahealthcare.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send email using PHP mail function
     */
    private function sendEmail($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>" . "\r\n";
        $headers .= "Reply-To: {$this->fromEmail}" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Log email attempt
        error_log("Sending email to: {$to}, Subject: {$subject}");
        
        try {
            $result = mail($to, $subject, $message, $headers);
            
            if ($result) {
                error_log("Email sent successfully to: {$to}");
                return true;
            } else {
                error_log("Failed to send email to: {$to}");
                return false;
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send bulk notification emails
     */
    public function sendBulkNotification($recipients, $subject, $message) {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->sendEmail($recipient, $subject, $message);
        }
        
        return $results;
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration($testEmail = null) {
        $testRecipient = $testEmail ?? $this->fromEmail;
        $subject = 'ASAA Healthcare - Email Test';
        $message = $this->getTestEmailTemplate();
        
        return $this->sendEmail($testRecipient, $subject, $message);
    }
    
    /**
     * Get test email template
     */
    private function getTestEmailTemplate() {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: linear-gradient(135deg, #207d87, #4dd0e1); color: white; padding: 2rem; text-align: center; }
                .content { padding: 2rem; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🏥 ASAA Healthcare</h1>
                <p>Email Configuration Test</p>
            </div>
            <div class='content'>
                <h2>Email System Working!</h2>
                <p>This is a test email to verify that the ASAA Healthcare email system is working correctly.</p>
                <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p>If you receive this email, the email configuration is successful.</p>
            </div>
        </body>
        </html>
        ";
    }
}
?>
