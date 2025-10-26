<?php
require_once '../../config/config.php';
require_once '../../services/AppointmentService.php';

requireLogin();

if ($_SESSION['user_role'] !== 'doctor') {
    header('Location: ' . BASE_URL . '/access-denied.php');
    exit;
}

$appointmentService = new AppointmentService();
$doctorId = $_SESSION['user_id'];

// Get doctor's appointments
$appointments = $appointmentService->getAppointmentsByDoctor($doctorId);

// Get doctor's profile information
try {
    global $db;
    $stmt = $db->prepare("
        SELECT u.first_name, u.last_name, u.email, u.mobile_number,
               dd.specialization_id, dd.consultation_fee, dd.experience_years, 
               dd.qualifications, dd.bio,
               s.specialization_name
        FROM users u
        JOIN doctor_details dd ON u.user_id = dd.user_id
        JOIN specializations s ON dd.specialization_id = s.specialization_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$doctorId]);
    $doctorProfile = $stmt->fetch();
} catch (Exception $e) {
    $doctorProfile = null;
    error_log("Doctor profile error: " . $e->getMessage());
}

// Calculate statistics
$todayAppointments = 0;
$upcomingAppointments = 0;
$completedAppointments = 0;
$pendingAppointments = 0;
$todayRevenue = 0;

foreach ($appointments as $appointment) {
    if ($appointment['appointment_date'] === date('Y-m-d')) {
        $todayAppointments++;
        if (isset($appointment['consultation_fee'])) {
            $todayRevenue += $appointment['consultation_fee'];
        }
    }

    if ($appointment['appointment_date'] > date('Y-m-d')) {
        $upcomingAppointments++;
    }

    if ($appointment['status'] === 'COMPLETED') {
        $completedAppointments++;
    }

    if ($appointment['status'] === 'REVIEW') {
        $pendingAppointments++;
    }
}

// Get today's appointments
$todayAppointmentsList = array_filter($appointments, function ($appointment) {
    return $appointment['appointment_date'] === date('Y-m-d');
});

usort($todayAppointmentsList, function ($a, $b) {
    return strtotime($a['time_slot']) - strtotime($b['time_slot']);
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: rgb(85, 187, 200);
            --navbar-dark: #207d87;
            --navbar-light: #e6f2f5;
            --header-gradient: linear-gradient(135deg, #207d87 0%, #4dd0e1 50%, #b2ebf2 100%);
            --doctor-gradient: linear-gradient(45deg, rgb(2, 130, 32) 0%, #00A557 50%, rgb(6, 88, 101) 100%);
            --card-shadow: 0 8px 32px rgba(32, 125, 135, 0.12);
            --card-shadow-hover: 0 12px 40px rgba(32, 125, 135, 0.18);
        }

        body {
            background: linear-gradient(135deg, var(--navbar-light) 0%, #f0f8ff 50%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Modern Doctor Sidebar */
        .doctor-sidebar {
            background: var(--doctor-gradient);
            min-height: 100vh;
            padding: 0;
            box-shadow: 4px 0 20px rgba(40, 167, 69, 0.15);
            position: fixed;
            left: 0;
            top: 0;
            width: 300px;
            z-index: 1000;
            display: flex;
            /* FIXED: Added flex layout */
            flex-direction: column;
            overflow-y: auto;
        }

        .doctor-brand {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            /* FIXED: Prevents shrinking */
        }

        .doctor-brand-icon {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 1rem;
            animation: heartbeat 2s infinite;
        }

        @keyframes heartbeat {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .doctor-brand h3 {
            color: white;
            font-weight: bold;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .doctor-brand small {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        /* FIXED: Navigation Layout */
        .doctor-nav {
            flex-grow: 1;
            /* FIXED: Takes available space */
            display: flex;
            flex-direction: column;
            padding: 1rem 0 0.5rem 0;
        }

        .doctor-nav .nav-main {
            flex-grow: 1;
            /* Main nav items take most space */
        }

        .doctor-nav .nav-footer {
            margin-top: auto;
            /* FIXED: Pushes logout to bottom */
            padding: 1rem 0 1.5rem 0;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.1);
        }

        .doctor-nav .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .doctor-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .doctor-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }

        .doctor-nav .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        /* FIXED: Enhanced Logout Button Styling */
        .logout-link {
            background: var(--admin-gradient);
            color: var(--navbar-light);
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            font-weight: bold !important;
            font-size: 1rem !important;
            padding: 1rem 1.5rem !important;
            margin: 0.5rem 1rem !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            text-align: center !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }


        .logout-link i {
            color: white !important;
            margin-right: 0.75rem !important;
            animation: pulse 2s infinite !important;
        }

        .logout-link:hover {
            background: var(--navbar-light);
            color: white !important;
            transform: translateX(0) scale(1.05) !important;
            box-shadow: 0 6px 25px rgba(220, 53, 69, 0.4) !important;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Main Content */
        .doctor-content {
            margin-left: 300px;
            padding: 0;
            min-height: 100vh;
        }

        .doctor-header {
            background: white;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(32, 125, 135, 0.08);
            margin-bottom: 2rem;
        }

        .doctor-welcome {
            background: var(--doctor-gradient);
            color: white;
            padding: 3rem 2rem;
            margin: 0 2rem 2rem 2rem;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .doctor-welcome::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -15%;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        /* FIXED: Logout Button Styling */
        .logout-link {
            background: var(--admin-gradient);
            color: var(--navbar-light);
            border: 1px solid rgba(255, 193, 7, 0.3);
            font-weight: 600;
        }

        .logout-link:hover {
            background: var(--navbar-light);
            color: #fff !important;
            border-color: var(--accent-color);
            transform: translateX(8px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }

        .logout-link i {
            color: var(--navbar-light) !important;
        }

        .logout-link:hover i {
            color: #207d87 !important;
            background-color: white;

        }

        /* Stat Cards */
        .doctor-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin: 0 2rem 2rem 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fdfe 100%);
            border: 1px solid rgba(32, 125, 135, 0.1);
            border-radius: 18px;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s ease;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--doctor-gradient);
            border-radius: 18px 18px 0 0;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--card-shadow-hover);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            padding: 1.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 208, 151, 0.2));
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0;
            background: var(--doctor-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Today's Schedule */
        .schedule-card {
            background: white;
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            border: none;
            margin: 0 2rem;
        }

        .schedule-card .card-header {
            background: var(--doctor-gradient);
            color: white;
            border-radius: 18px 18px 0 0;
            padding: 1.5rem 2rem;
            border: none;
        }

        .appointment-item {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(32, 125, 135, 0.1);
            transition: all 0.3s ease;
        }

        .appointment-item:hover {
            background: linear-gradient(135deg, rgba(32, 125, 135, 0.02), rgba(178, 235, 242, 0.05));
            transform: translateX(5px);
        }

        .appointment-time {
            background: var(--doctor-gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .doctor-sidebar {
                width: 100%;
                position: relative;
            }

            .doctor-content {
                margin-left: 0;
            }

            .doctor-welcome,
            .schedule-card {
                margin: 0 1rem 1rem 1rem;
            }

            .doctor-stats {
                margin: 1rem;
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Modern Doctor Sidebar -->
    <div class="doctor-sidebar">
        <div class="doctor-brand">
            <div class="doctor-brand-icon">
                <i class="fas fa-stethoscope"></i>
            </div>
            <h3>ASAA Healthcare</h3>
            <small class="text-white">Doctor Portal</small>
            <?php if ($doctorProfile): ?>
                <div class="mt-2">
                    <small class="text-white opacity-75"><?= htmlspecialchars($doctorProfile['specialization_name']) ?></small>
                </div>
            <?php endif; ?>
        </div>

        <nav class="doctor-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../appointments/my_schedule.php">
                        <i class="fas fa-calendar-day"></i>
                        <span>My Schedule</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../appointments/manage.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Manage Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../patients/list.php">
                        <i class="fas fa-users"></i>
                        <span>My Patients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../medical_records/manage.php">
                        <i class="fas fa-file-medical-alt"></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../users/profile.php">
                        <i class="fas fa-user-circle"></i>
                        <span>My Profile</span>
                    </a>
                </li>
            </ul>
            <ul class="nav flex-column nav-footer">
                <li class="nav-item">
                    <a class="nav-link logout-link" href="../../controllers/AuthController.php?action=logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Secure Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Doctor Main Content -->
    <div class="doctor-content">
        <!-- Top Header -->
        <div class="doctor-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1" style="color: var(--navbar-dark);">
                        <i class="fas fa-stethoscope me-2"></i>Doctor Dashboard
                    </h2>
                    <p class="mb-0 text-muted">Manage your patients and appointments</p>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3">
                        <strong style="color: var(--navbar-dark);">
                            Dr. <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </strong>
                        <br>
                        <small class="text-muted"><?= date('l, F j, Y - g:i A') ?></small>
                    </div>
                    <div class="user-avatar" style="background: var(--doctor-gradient); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctor Welcome Section -->
        <div class="doctor-welcome">
            <div style="position: relative; z-index: 2;">
                <h1 class="mb-3">
                    <i class="fas fa-hand-holding-heart me-2"></i>
                    Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>,
                    Dr. <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[1]) ?>
                </h1>
                <p class="lead mb-4">Ready to provide excellent healthcare to your patients today</p>

                <?php if ($doctorProfile): ?>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h3 class="mb-1"><?= $todayAppointments ?></h3>
                            <small>Today's Appointments</small>
                        </div>
                        <div class="col-md-3">
                            <h3 class="mb-1"><?= $pendingAppointments ?></h3>
                            <small>Pending Reviews</small>
                        </div>
                        <div class="col-md-3">
                            <h3 class="mb-1"><?= $doctorProfile['experience_years'] ?> Years</h3>
                            <small>Experience</small>
                        </div>
                        <div class="col-md-3">
                            <h3 class="mb-1">LKR <?= number_format($todayRevenue, 0) ?></h3>
                            <small>Today's Revenue</small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="doctor-stats">
            <div class="stat-card">
                <div class="stat-icon text-primary">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-title">Today's Schedule</div>
                <div class="stat-number"><?= $todayAppointments ?></div>
                <small class="text-muted">Appointments scheduled</small>
            </div>

            <div class="stat-card">
                <div class="stat-icon text-info">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="stat-title">Upcoming</div>
                <div class="stat-number"><?= $upcomingAppointments ?></div>
                <small class="text-muted">Future appointments</small>
            </div>

            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-title">Completed</div>
                <div class="stat-number"><?= $completedAppointments ?></div>
                <small class="text-muted">Successful consultations</small>
            </div>

            <div class="stat-card">
                <div class="stat-icon text-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-title">Pending Review</div>
                <div class="stat-number"><?= $pendingAppointments ?></div>
                <small class="text-muted">Need confirmation</small>
            </div>
        </div>

        <!-- Today's Schedule -->
        <div class="schedule-card card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-day me-2"></i>Today's Schedule - <?= date('M j, Y') ?>
                    <?php if ($todayAppointments > 0): ?>
                        <span class="badge bg-light text-dark ms-2"><?= $todayAppointments ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todayAppointmentsList)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-check fa-4x text-success mb-3"></i>
                        <h4 style="color: var(--success-color);">No Appointments Today</h4>
                        <p class="text-muted">Enjoy your day off! Check your upcoming schedule for tomorrow.</p>
                        <a href="../appointments/my_schedule.php" class="btn btn-outline-success">
                            <i class="fas fa-calendar-week me-2"></i>View This Week's Schedule
                        </a>
                    </div>
                <?php else: ?>
                    <div class="p-3">
                        <?php foreach ($todayAppointmentsList as $appointment): ?>
                            <div class="appointment-item d-flex align-items-center">
                                <div class="appointment-time me-3">
                                    <?= date('g:i A', strtotime($appointment['time_slot'])) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1" style="color: var(--navbar-dark);">
                                        <i class="fas fa-user me-2"></i>
                                        <?= htmlspecialchars($appointment['patient_name']) ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i>
                                        <?= htmlspecialchars($appointment['mobile_number'] ?? 'N/A') ?>

                                        <?php if (!empty($appointment['notes'])): ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-notes-medical me-1"></i>
                                            <?= htmlspecialchars(substr($appointment['notes'], 0, 50)) ?>...
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
                                        <?= htmlspecialchars($appointment['status']) ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        Payment: <?= htmlspecialchars($appointment['payment_status'] ?? 'PENDING') ?>
                                    </small>
                                </div>
                                <div class="ms-3">
                                    <a href="../appointments/view.php?id=<?= $appointment['appointment_id'] ?>"
                                        class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add interactive effects
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Auto-refresh dashboard every 5 minutes
            setInterval(() => {
                if (document.hasFocus()) {
                    location.reload();
                }
            }, 300000);

            // Welcome message
            setTimeout(() => {
                showToast('👨‍⚕️ Welcome to your ASAA Healthcare dashboard, Dr. <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[1]) ?>!', 'success');
            }, 1000);
        });

        function showToast(message, type = 'info') {
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                info: 'var(--primary-color)'
            };

            const toastHtml = `
                <div class="toast align-items-center text-white border-0" role="alert" 
                     style="background: ${colors[type]}; border-radius: 12px;">
                    <div class="d-flex">
                        <div class="toast-body fw-bold">${message}</div>
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
            const toast = new bootstrap.Toast(document.querySelector('.toast:last-child'));
            toast.show();
        }
    </script>
</body>

</html>