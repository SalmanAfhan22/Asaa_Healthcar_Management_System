<?php
require_once __DIR__ . '/../config/config.php';

class AppointmentRepository
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function isSlotBooked($doctorId, $date, $timeSlot)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND time_slot = ? 
            AND status NOT IN ('CANCELLED')
        ");
        $stmt->execute([$doctorId, $date, $timeSlot]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO appointments (patient_id, doctor_id, appointment_date, time_slot, 
                                    status, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $data['patient_id'],
            $data['doctor_id'],
            $data['appointment_date'],
            $data['time_slot'],
            $data['status'],
            $data['notes'],
            $data['created_by']
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    public function findById($appointmentId)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                   CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                   s.specialization_name,
                   dd.consultation_fee
            FROM appointments a
            JOIN users p ON a.patient_id = p.user_id
            JOIN users d ON a.doctor_id = d.user_id
            LEFT JOIN doctor_details dd ON d.user_id = dd.user_id
            LEFT JOIN specializations s ON dd.specialization_id = s.specialization_id
            WHERE a.appointment_id = ?
        ");
        $stmt->execute([$appointmentId]);
        return $stmt->fetch();
    }

    public function getDoctorFee($doctorId)
    {
        $stmt = $this->db->prepare("
            SELECT consultation_fee 
            FROM doctor_details 
            WHERE user_id = ?
        ");
        $stmt->execute([$doctorId]);
        $result = $stmt->fetch();
        return $result ? $result['consultation_fee'] : 0;
    }

    public function getByPatient($patientId)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                   s.specialization_name,
                   dd.consultation_fee,
                   p.payment_status
            FROM appointments a
            JOIN users d ON a.doctor_id = d.user_id
            LEFT JOIN doctor_details dd ON d.user_id = dd.user_id
            LEFT JOIN specializations s ON dd.specialization_id = s.specialization_id
            LEFT JOIN payments p ON a.appointment_id = p.appointment_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date ASC, a.time_slot ASC
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    public function getByDoctor($doctorId)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   CONCAT(pt.first_name, ' ', pt.last_name) as patient_name,
                   pt.mobile_number,
                   p.payment_status
            FROM appointments a
            JOIN users pt ON a.patient_id = pt.user_id
            LEFT JOIN payments p ON a.appointment_id = p.appointment_id
            WHERE a.doctor_id = ?
            ORDER BY a.appointment_date DESC, a.time_slot DESC
        ");
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll();
    }

    public function updateStatus($appointmentId, $status, $userId)
    {
        $stmt = $this->db->prepare("
            UPDATE appointments 
            SET status = ?, updated_at = NOW()
            WHERE appointment_id = ?
        ");
        return $stmt->execute([$status, $appointmentId]);
    }

    public function getAllDoctors()
    {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.first_name, u.last_name, 
                   s.specialization_id, s.specialization_name, 
                   dd.consultation_fee, dd.experience_years, dd.qualifications
            FROM users u
            JOIN doctor_details dd ON u.user_id = dd.user_id
            JOIN specializations s ON dd.specialization_id = s.specialization_id
            WHERE u.role_id = 3 AND u.status = 'ACTIVE'
            ORDER BY u.first_name, u.last_name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllSpecializations()
    {
        $stmt = $this->db->prepare("
            SELECT specialization_id, specialization_name
            FROM specializations 
            ORDER BY specialization_name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
