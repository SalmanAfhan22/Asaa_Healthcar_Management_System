<?php
require_once '../config/config.php';
require_once '../services/AppointmentService.php';

// Set timezone to Sri Lankan time
date_default_timezone_set('Asia/Colombo');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    // Validate parameters
    if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
        echo json_encode(['error' => 'Missing required parameters: doctor_id and date']);
        exit;
    }

    $doctorId = intval($_GET['doctor_id']);
    $date = $_GET['date'];

    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }

    // Validate date is not in the past (using Sri Lankan timezone)
    $now = new DateTime('now', new DateTimeZone('Asia/Colombo'));
    if ($date < $now->format('Y-m-d')) {
        echo json_encode(['error' => 'Cannot book appointments for past dates']);
        exit;
    }

    // Validate doctor exists and is active
    global $db;
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM users u
        JOIN doctor_details dd ON u.user_id = dd.user_id
        WHERE u.user_id = ? AND u.role_id = 3 AND u.status = 'ACTIVE'
    ");
    $stmt->execute([$doctorId]);
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo json_encode(['error' => 'Invalid doctor selected']);
        exit;
    }

    // Get available time slots with 3-hour advance booking logic
    $appointmentService = new AppointmentService();
    $slots = $appointmentService->getAvailableSlots($doctorId, $date);
    
    echo json_encode($slots);

} catch (Exception $e) {
    error_log("Error in get_time_slots.php: " . $e->getMessage());
    echo json_encode(['error' => 'Unable to load time slots. Please try again later.']);
}
?>
