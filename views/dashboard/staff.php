<?php
require_once '../../config/config.php';
require_once '../../services/AppointmentService.php';

requireLogin();

if ($_SESSION['user_role'] !== 'staff') {
    header('Location: ' . BASE_URL . '/access-denied.php');
    exit;
}

$appointmentService = new AppointmentService();

// Get today's appointments for staff management
try {
    global $db;

    // Today's appointments
    $stmt = $db->prepare("
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               p.mobile_number as patient_mobile,
               s.specialization_name,
               dd.consultation_fee
        FROM appointments a
        JOIN users p ON a.patient_id = p.user_id
        JOIN users d ON a.doctor_id = d.user_id
        JOIN doctor_details dd ON a.doctor_id = dd.user_id
        JOIN specializations s ON dd.specialization_id = s.specialization_id
        WHERE a.appointment_date = CURDATE()
        ORDER BY a.time_slot ASC
    ");
    $stmt->execute();
    $todayAppointments = $stmt->fetchAll();

    // Pending appointments needing staff attention
    $stmt = $db->prepare("
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               p.mobile_number as patient_mobile
        FROM appointments a
        JOIN users p ON a.patient_id = p.user_id
        JOIN users d ON a.doctor_id = d.user_id
        WHERE a.status = 'REVIEW'
        ORDER BY a.created_at ASC
        LIMIT 10
    ");
    $stmt->execute();
    $pendingAppointments = $stmt->fetchAll();

    // Statistics
    $stats = [
        'today_total' => count($todayAppointments),
        'pending_reviews' => count($pendingAppointments),
        'today_revenue' => array_sum(array_column($todayAppointments, 'consultation_fee')),
        'completed_today' => count(array_filter($todayAppointments, function ($a) {
            return $a['status'] === 'COMPLETED';
        }))
    ];
} catch (Exception $e) {
    $todayAppointments = [];
    $pendingAppointments = [];
    $stats = ['today_total' => 0, 'pending_reviews' => 0, 'today_revenue' => 0, 'completed_today' => 0];
    error_log("Staff dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: #b2ebf2;
            --navbar-dark: #207d87;
            --navbar-light: #e6f2f5;
            --header-gradient: linear-gradient(135deg, #207d87 0%, #4dd0e1 50%, #b2ebf2 100%);
            --staff-gradient: linear-gradient(135deg, rgb(42, 199, 7) 0%, rgb(3, 114, 40) 50%, rgb(19, 217, 141) 100%);
            --card-shadow: 0 8px 32px rgba(32, 125, 135, 0.12);
            --card-shadow-hover: 0 12px 40px rgba(32, 125, 135, 0.18);
        }

        body {
            background: linear-gradient(135deg, var(--navbar-light) 0%, #f0f8ff 50%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Modern Staff Sidebar */
        .staff-sidebar {
            background: var(--staff-gradient);
            min-height: 100vh;
            padding: 0;
            box-shadow: 4px 0 20px rgba(253, 126, 20, 0.15);
            position: fixed;
            left: 0;
            top: 0;
            width: 300px;
            z-index: 1000;
        }

        .staff-brand {
            padding: 2.5rem 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.1);
        }

        .staff-brand-icon {
            font-size: 3rem;
            color: white;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .staff-nav {
            padding: 1.5rem 0;
        }

        .staff-nav .nav-link {
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

        .staff-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .staff-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.4);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }


        /* FIXED: Logout Button Styling */
        .logout-link {
            background: var(--staff-gradient);
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

        /* Main Content - Same structure as admin/doctor */
        .staff-content {
            margin-left: 300px;
            padding: 0;
            min-height: 100vh;
        }

        .staff-header {
            background: white;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(32, 125, 135, 0.08);
            margin-bottom: 2rem;
        }

        .staff-welcome {
            background: var(--staff-gradient);
            color: white;
            padding: 3rem 2rem;
            margin: 0 2rem 2rem 2rem;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .staff-welcome::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: rotate 10s linear infinite;
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Use similar stat card styling as admin */
        .staff-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin: 0 2rem 2rem 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .staff-sidebar {
                width: 100%;
                position: relative;
            }

            .staff-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Staff Sidebar -->
    <div class="staff-sidebar">
        <div class="staff-brand">
            <div class="staff-brand-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <h3 class="text-white">ASAA Healthcare</h3>
            <small class="text-white">Staff Portal</small>
        </div>

        <nav class="staff-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active">
                        <i class="fas fa-chart-bar"></i>
                        &nbsp;<span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../appointments/manage.php">
                        <i class="fas fa-calendar-check"></i>
                        &nbsp;<span>Appointment Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../appointments/schedule.php">
                        <i class="fas fa-calendar-day"></i>
                        &nbsp;<span>Daily Schedule</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../patients/register.php">
                        <i class="fas fa-user-plus"></i>
                        &nbsp;<span>Register Patient</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../payments/process.php">
                        <i class="fas fa-cash-register"></i>
                        &nbsp;<span>Process Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../reports/daily.php">
                        <i class="fas fa-file-alt"></i>
                        &nbsp;<span>Daily Reports</span>
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link logout-link" href="../../controllers/AuthController.php?action=logout">
                        <i class="fas fa-sign-out-alt"></i>
                        &nbsp;<span>Secure Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Staff Main Content -->
    <div class="staff-content">
        <!-- Top Header -->
        <div class="staff-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1" style="color: var(--navbar-dark);">
                        <i class="fas fa-clipboard-list me-2"></i>Staff Dashboard
                    </h2>
                    <p class="mb-0 text-muted">Manage daily operations and patient services</p>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3">
                        <strong style="color: var(--navbar-dark);"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                        <br>
                        <small class="text-muted"><?= date('l, F j, Y - g:i A') ?></small>
                    </div>
                    <div class="user-avatar" style="background: var(--staff-gradient); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                        <i class="fas fa-clipboard-user"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Welcome Section -->
        <div class="staff-welcome">
            <div style="position: relative; z-index: 2;">
                <h1 class="mb-3">
                    <i class="fas fa-hands-helping me-2"></i>
                    Hello, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>!
                </h1>
                <p class="lead mb-4">Supporting excellent patient care and smooth healthcare operations</p>
                <div class="row text-center">
                    <div class="col-md-3">
                        <h3 class="mb-1"><?= $stats['today_total'] ?></h3>
                        <small>Today's Appointments</small>
                    </div>
                    <div class="col-md-3">
                        <h3 class="mb-1"><?= $stats['pending_reviews'] ?></h3>
                        <small>Pending Reviews</small>
                    </div>
                    <div class="col-md-3">
                        <h3 class="mb-1"><?= $stats['completed_today'] ?></h3>
                        <small>Completed Today</small>
                    </div>
                    <div class="col-md-3">
                        <h3 class="mb-1">LKR <?= number_format($stats['today_revenue'], 0) ?></h3>
                        <small>Today's Revenue</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Grid -->
        <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem;">
            <a href="../appointments/approve.php" class="action-card" style="background: white; border: 1px solid rgba(32, 125, 135, 0.1); border-radius: 16px; padding: 2rem 1.5rem; text-decoration: none; color: var(--navbar-dark); transition: all 0.3s ease;">
                <i class="fas fa-clipboard-check" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h6>Approve Appointments</h6>
                <small class="text-muted">Review and confirm bookings</small>
            </a>

            <a href="../patients/register.php" class="action-card" style="background: white; border: 1px solid rgba(32, 125, 135, 0.1); border-radius: 16px; padding: 2rem 1.5rem; text-decoration: none; color: var(--navbar-dark); transition: all 0.3s ease;">
                <i class="fas fa-user-plus" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h6>Register New Patient</h6>
                <small class="text-muted">Add patient to system</small>
            </a>

            <a href="../payments/process.php" class="action-card" style="background: white; border: 1px solid rgba(32, 125, 135, 0.1); border-radius: 16px; padding: 2rem 1.5rem; text-decoration: none; color: var(--navbar-dark); transition: all 0.3s ease;">
                <i class="fas fa-cash-register" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h6>Process Payments</h6>
                <small class="text-muted">Handle payment transactions</small>
            </a>

            <a href="../reports/daily.php" class="action-card" style="background: white; border: 1px solid rgba(32, 125, 135, 0.1); border-radius: 16px; padding: 2rem 1.5rem; text-decoration: none; color: var(--navbar-dark); transition: all 0.3s ease;">
                <i class="fas fa-file-alt" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h6>Generate Reports</h6>
                <small class="text-muted">Daily operational reports</small>
            </a>
        </div>

        <!-- Today's Schedule - Similar to doctor dashboard -->
        <div class="schedule-card card" style="background: white; border-radius: 18px; box-shadow: var(--card-shadow); margin: 0 2rem;">
            <div class="card-header" style="background: var(--staff-gradient); color: white; border-radius: 18px 18px 0 0; padding: 1.5rem 2rem;">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-day me-2"></i>Today's Appointment Schedule
                    <?php if ($stats['today_total'] > 0): ?>
                        <span class="badge bg-light text-dark ms-2"><?= $stats['today_total'] ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todayAppointments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-check fa-4x text-success mb-3"></i>
                        <h4 style="color: var(--primary-color);">No Appointments Scheduled Today</h4>
                        <p class="text-muted">All quiet on the healthcare front!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead style="background: var(--navbar-light); color: var(--navbar-dark);">
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayAppointments as $appointment): ?>
                                    <tr style="transition: all 0.3s ease;" onmouseover="this.style.background='linear-gradient(135deg, rgba(32, 125, 135, 0.02), rgba(178, 235, 242, 0.05))'" onmouseout="this.style.background=''">
                                        <td>
                                            <strong style="color: var(--primary-color);">
                                                <?= date('g:i A', strtotime($appointment['time_slot'])) ?>
                                            </strong>
                                        </td>
                                        <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                                        <td>Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></td>
                                        <td><?= htmlspecialchars($appointment['specialization_name']) ?></td>
                                        <td>
                                            <span class="badge status-<?= strtolower($appointment['status']) ?>">
                                                <?= htmlspecialchars($appointment['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong style="color: var(--success-color);">
                                                LKR <?= number_format($appointment['consultation_fee'], 2) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="../appointments/view.php?id=<?= $appointment['appointment_id'] ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Welcome message for staff
            setTimeout(() => {
                showToast('👥 Welcome to ASAA Healthcare Staff Portal!', 'success');
            }, 1000);
        });

        function showToast(message, type = 'info') {
            // Same toast implementation as admin
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                info: 'var(--primary-color)'
            };
            const toastHtml = `<div class="toast align-items-center text-white border-0" role="alert" style="background: ${colors[type]}; border-radius: 12px;"><div class="d-flex"><div class="toast-body fw-bold">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;

            if (!document.getElementById('toastContainer')) {
                document.body.insertAdjacentHTML('beforeend', '<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>');
            }

            document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);
            const toast = new bootstrap.Toast(document.querySelector('.toast:last-child'));
            toast.show();
        }
    </script>
</body>

</html>