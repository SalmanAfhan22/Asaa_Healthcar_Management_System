<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../repositories/AppointmentRepository.php';

class AppointmentService {
    private $appointmentRepo;
    
    public function __construct() {
        $this->appointmentRepo = new AppointmentRepository();
        // Set timezone to Sri Lankan time
        date_default_timezone_set('Asia/Colombo');
    }
    
    public function getAvailableSlots($doctorId, $date) {
        // Set timezone to Sri Lankan time
        date_default_timezone_set('Asia/Colombo');
        
        // Generate all possible slots (1-hour intervals)
        $startTime = new DateTime('08:00', new DateTimeZone('Asia/Colombo'));
        $endTime = new DateTime('22:00', new DateTimeZone('Asia/Colombo'));
        $lunchStart = new DateTime('12:00', new DateTimeZone('Asia/Colombo'));
        $lunchEnd = new DateTime('13:00', new DateTimeZone('Asia/Colombo'));
        
        $slots = [];
        $current = clone $startTime;
        $now = new DateTime('now', new DateTimeZone('Asia/Colombo'));
        
        while ($current < $endTime) {
            $timeStr = $current->format('H:i:s');
            
            // Skip lunch break (12:00 PM - 1:00 PM)
            if ($current >= $lunchStart && $current < $lunchEnd) {
                $current->add(new DateInterval('PT1H'));
                continue;
            }
            
            // Check if slot is already booked
            $isBooked = $this->appointmentRepo->isSlotBooked($doctorId, $date, $timeStr);
            
            // FIXED: Proper logic for past and too_soon with Sri Lankan timezone
            $isPast = false;
            $isTooSoon = false;
            
            if ($date === $now->format('Y-m-d')) {
                $slotDateTime = new DateTime($date . ' ' . $timeStr, new DateTimeZone('Asia/Colombo'));
                
                // Check if time has already passed
                $isPast = $slotDateTime <= $now;
                
                // FIXED: Only check 3-hour rule for future slots that haven't passed
                if (!$isPast) {
                    $hourDiff = ($slotDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
                    $isTooSoon = $hourDiff < 3;
                }
            }
            
            $slots[] = [
                'time' => $timeStr,
                'display' => $current->format('g:i A'),
                'available' => !$isBooked && !$isPast && !$isTooSoon,
                'booked' => $isBooked,
                'past' => $isPast,
                'too_soon' => $isTooSoon
            ];
            
            $current->add(new DateInterval('PT1H')); // 1-hour intervals
        }
        
        return $slots;
    }
    
    public function bookAppointment($patientId, $doctorId, $date, $timeSlot, $notes = null) {
        global $db;
        
        // Set timezone to Sri Lankan time
        date_default_timezone_set('Asia/Colombo');
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // FIXED: Validate 3-hour minimum advance booking for same-day appointments with Sri Lankan timezone
            $now = new DateTime('now', new DateTimeZone('Asia/Colombo'));
            
            if ($date === $now->format('Y-m-d')) {
                $slotDateTime = new DateTime($date . ' ' . $timeSlot, new DateTimeZone('Asia/Colombo'));
                
                // Check if the time has already passed
                if ($slotDateTime <= $now) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Cannot book appointments for past times'];
                }
                
                // Check 3-hour minimum advance booking
                $hourDiff = ($slotDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
                if ($hourDiff < 3) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Same-day appointments must be booked at least 3 hours in advance'];
                }
            }
            
            // Double-check availability with lock
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? AND time_slot = ? 
                AND status NOT IN ('CANCELLED')
                FOR UPDATE
            ");
            $stmt->execute([$doctorId, $date, $timeSlot]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Time slot no longer available'];
            }
            
            // Create appointment with correct column names
            $appointmentId = $this->appointmentRepo->create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'appointment_date' => $date,
                'time_slot' => $timeSlot,
                'status' => 'REVIEW',
                'payment_status' => 'PENDING',
                'notes' => $notes,
                'created_by' => $patientId
            ]);
            
            if (!$appointmentId) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Failed to create appointment'];
            }
            
            // Get consultation fee
            $fee = $this->appointmentRepo->getDoctorFee($doctorId);
            
            // Create payment record
            $paymentId = $this->createPaymentRecord($appointmentId, $patientId, $doctorId, $fee);
            
            if (!$paymentId) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Failed to create payment record'];
            }
            
            $db->commit();
            
            return [
                'success' => true, 
                'appointment_id' => $appointmentId,
                'message' => 'Appointment booked successfully'
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Appointment booking error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Booking failed. Please try again.'];
        }
    }
    
    private function createPaymentRecord($appointmentId, $patientId, $doctorId, $amount) {
        global $db;
        
        $stmt = $db->prepare("
            INSERT INTO payments (appointment_id, patient_id, doctor_id, amount, 
                                payment_status, created_at)
            VALUES (?, ?, ?, ?, 'PENDING', NOW())
        ");
        
        $result = $stmt->execute([$appointmentId, $patientId, $doctorId, $amount]);
        return $result ? $db->lastInsertId() : false;
    }
    
    public function updateAppointmentStatus($appointmentId, $status, $userId) {
        try {
            $result = $this->appointmentRepo->updateStatus($appointmentId, $status, $userId);
            
            if ($result) {
                return ['success' => true, 'message' => 'Status updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update status'];
            }
        } catch (Exception $e) {
            error_log("Status update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Update failed'];
        }
    }
    
    public function getAppointmentById($appointmentId) {
        return $this->appointmentRepo->findById($appointmentId);
    }
    
    public function getAppointmentsByPatient($patientId) {
        return $this->appointmentRepo->getByPatient($patientId);
    }
    
    public function getAppointmentsByDoctor($doctorId) {
        return $this->appointmentRepo->getByDoctor($doctorId);
    }
}
?>
