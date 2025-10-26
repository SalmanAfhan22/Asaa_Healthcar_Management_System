<?php
require_once '../../config/config.php';
require_once '../../services/AppointmentService.php';

requireLogin();

if ($_SESSION['user_role'] !== 'patient') {
    header('Location: ' . BASE_URL . '/access-denied.php');
    exit;
}

$appointmentService = new AppointmentService();
$appointments = $appointmentService->getAppointmentsByPatient($_SESSION['user_id']);

// Calculate statistics - PHP 5.3+ compatible
$totalAppointments = count($appointments);
$pendingCount = 0;
$completedCount = 0;
$confirmedCount = 0;
$cancelledCount = 0;

foreach ($appointments as $appointment) {
    switch ($appointment['status']) {
        case 'REVIEW':
            $pendingCount++;
            break;
        case 'COMPLETED':
            $completedCount++;
            break;
        case 'CONFIRMED':
            $confirmedCount++;
            break;
        case 'CANCELLED':
            $cancelledCount++;
            break;
    }
}

// Get next appointment
$nextAppointment = null;
$upcomingAppointments = array_filter($appointments, function($apt) {
    return in_array($apt['status'], ['REVIEW', 'CONFIRMED']) && 
           strtotime($apt['appointment_date'] . ' ' . $apt['time_slot']) > time();
});

if (!empty($upcomingAppointments)) {
    usort($upcomingAppointments, function($a, $b) {
        return strtotime($a['appointment_date'] . ' ' . $a['time_slot']) - 
               strtotime($b['appointment_date'] . ' ' . $b['time_slot']);
    });
    $nextAppointment = $upcomingAppointments[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: #b2ebf2;
            --navbar-dark: #207d87;
            --navbar-light: #e6f2f5;
            --header-gradient: linear-gradient(90deg, #207d87 0%, #4dd0e1 50%,rgb(34, 122, 134) 100%);
            --toast-gradient:  linear-gradient(135deg,rgb(16, 57, 61) 0%, #4dd0e1 90%,rgb(3, 101, 114) 100%);
            --card-gradient: linear-gradient(135deg, #ffffff 0%, #f8fdfe 100%);
            --shadow-primary: 0 8px 32px rgba(32, 125, 135, 0.15);
            --shadow-hover: 0 12px 40px rgba(32, 125, 135, 0.25);
        }

        body {
            background: var(--navbar-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Modern Sidebar */
        .sidebar {
            background: linear-gradient(180deg, var(--navbar-dark) 0%,rgb(2, 96, 107) 60%);
            min-height: 100vh;
            padding: 0;
            box-shadow: 4px 0 20px rgba(32, 125, 135, 0.1);
            position: fixed;
            left: 0;
            top: 0;
            width: 300px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }

        .brand-link {
            color: white !important;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.4rem;
            display: block;
            transition: all 0.3s ease;
        }

        .brand-link:hover {
            color: var(--accent-color) !important;
            transform: scale(1.05);
        }

        .brand-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--accent-color);
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: var(--accent-color);
            color: var(--navbar-dark);
            box-shadow: 0 4px 15px rgba(178, 235, 242, 0.3);
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.75rem;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 300px;
            padding: 0;
            min-height: 100vh;
            background: var(--navbar-light);
        }

        .top-header {
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 15px rgba(32, 125, 135, 0.08);
            border-bottom: 1px solid rgba(32, 125, 135, 0.1);
            margin-bottom: 2rem;
        }

        .welcome-section {
            background: var(--header-gradient);
            color: white;
            padding: 3rem 2rem;
            margin: 0 2rem 2rem 2rem;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-primary);
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        /* Modern Cards */
        .stat-card {
            background: var(--card-gradient);
            border: 1px solid rgba(32, 125, 135, 0.1);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            text-align: center;
            transition: all 0.4s ease;
            box-shadow: 0 4px 20px rgba(32, 125, 135, 0.08);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--header-gradient);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-color);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: inline-block;
            padding: 1rem;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(32, 125, 135, 0.1), rgba(178, 235, 242, 0.2));
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }

        /* Next Appointment Card */
        .next-appointment {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin: 0 2rem 2rem 2rem;
            box-shadow: var(--shadow-primary);
            position: relative;
            overflow: hidden;
        }

        .next-appointment::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }

        /* Appointments Table */
        .appointments-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-primary);
            border: none;
            margin: 0 2rem;
        }

        .appointments-card .card-header {
            background: var(--header-gradient);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 1.5rem 2rem;
            border: none;
        }

        .table-modern {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-modern thead th {
            background: var(--navbar-light);
            color: var(--navbar-dark);
            font-weight: 600;
            padding: 1rem;
            border: none;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .table-modern tbody td {
            padding: 1rem;
            border-bottom: 1px solid rgba(32, 125, 135, 0.1);
            vertical-align: middle;
        }

        .table-modern tbody tr {
            transition: all 0.3s ease;
        }

        .table-modern tbody tr:hover {
            background: linear-gradient(135deg, rgba(32, 125, 135, 0.02), rgba(178, 235, 242, 0.05));
            transform: scale(1.01);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-review {
            background: linear-gradient(135deg, #ffc107, #ffca28);
            color: #856404;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }

        .status-confirmed {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }

        .status-completed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .status-cancelled {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-pay {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-pay:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--primary-color), #4dd0e1);
            color: white;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #196872, #26bbd1);
            color: white;
            transform: translateY(-2px);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem;
        }

        .action-card {
            background: var(--card-gradient);
            border: 1px solid rgba(32, 125, 135, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: var(--navbar-dark);
            transition: all 0.4s ease;
            box-shadow: 0 4px 20px rgba(32, 125, 135, 0.08);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            color: var(--navbar-dark);
            border-color: var(--primary-color);
        }

        .action-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 2rem;
            color: var(--primary-color);
            opacity: 0.5;
        }

        .btn-primary-asaa {
            background: var(--header-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-primary-asaa:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(32, 125, 135, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .welcome-section {
                margin: 0 1rem 1rem 1rem;
                padding: 2rem 1.5rem;
            }
            
            .quick-actions {
                margin: 1rem;
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            
            .appointments-card {
                margin: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-heartbeat"></i>
            </div>
            <div class="brand-link">ASAA Healthcare</div>
            <small class="text-light opacity-75">Patient Portal</small>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../appointments/book.php">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Book Appointment</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../appointments/list.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>My Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../users/profile.php">
                        <i class="fas fa-user-circle"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../reports/medical_history.php">
                        <i class="fas fa-file-medical-alt"></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-light" href="../../controllers/AuthController.php?action=logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0" style="color: var(--navbar-dark);">
                        <i class="fas fa-chart-line me-2"></i>Patient Dashboard
                    </h4>
                    <small class="text-muted">Manage your healthcare journey</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="user-info text-end me-3">
                        <strong style="color: var(--navbar-dark);"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                        <br>
                        <small class="text-muted"><?= date('l, F j, Y') ?></small>
                    </div>
                    <div class="user-avatar" style="background: var(--header-gradient); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Hero Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1 class="mb-3">
                    <i class="fas fa-hand-sparkles me-2"></i>
                    Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>!
                </h1>
                <p class="lead mb-4">Your health is our priority. Let's take care of your well-being together.</p>
                
                <?php if ($nextAppointment): ?>
                    <div class="next-appointment-info p-3" style="background: rgba(255, 255, 255, 0.15); border-radius: 12px; display: inline-block;">
                        <h6 class="mb-1"><i class="fas fa-calendar-check me-2"></i>Your Next Appointment</h6>
                        <strong>
                            <?= date('M j, Y', strtotime($nextAppointment['appointment_date'])) ?> 
                            at <?= date('g:i A', strtotime($nextAppointment['time_slot'])) ?>
                        </strong>
                        <br>
                        <small>with Dr. <?= htmlspecialchars($nextAppointment['doctor_name']) ?></small>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light text-light d-inline-block" style="background: rgba(18, 15, 15, 0.5); border: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        No upcoming appointments. Book one today!
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row" style="margin: 0 1rem;">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h6 class="text-muted mb-1">Total Appointments</h6>
                    <div class="stat-number text-primary"><?= $totalAppointments ?></div>
                    <small class="text-muted">All time</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h6 class="text-muted mb-1">Pending Review</h6>
                    <div class="stat-number text-warning"><?= $pendingCount ?></div>
                    <small class="text-muted">Awaiting confirmation</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h6 class="text-muted mb-1">Completed</h6>
                    <div class="stat-number text-success"><?= $completedCount ?></div>
                    <small class="text-muted">Healthcare visits</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h6 class="text-muted mb-1">Confirmed</h6>
                    <div class="stat-number text-info"><?= $confirmedCount ?></div>
                    <small class="text-muted">Ready to visit</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="../appointments/book.php" class="action-card">
                <i class="fas fa-calendar-plus"></i>
                <h6 class="mb-0">Book Appointment</h6>
                <small class="text-muted">Schedule your visit</small>
            </a>
            
            <a href="../appointments/list.php" class="action-card">
                <i class="fas fa-list-ul"></i>
                <h6 class="mb-0">All Appointments</h6>
                <small class="text-muted">View appointment history</small>
            </a>
            
            <a href="../users/profile.php" class="action-card">
                <i class="fas fa-user-edit"></i>
                <h6 class="mb-0">Update Profile</h6>
                <small class="text-muted">Manage personal info</small>
            </a>
            
            <a href="../reports/medical_history.php" class="action-card">
                <i class="fas fa-file-medical"></i>
                <h6 class="mb-0">Medical History</h6>
                <small class="text-muted">View health records</small>
            </a>
        </div>

        <!-- Recent Appointments Table -->
        <div class="appointments-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Recent Appointments
                    <?php if ($totalAppointments > 0): ?>
                        <span class="badge bg-light text-dark ms-2"><?= $totalAppointments ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($appointments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus"></i>
                        <h4>Ready for Your First Appointment?</h4>
                        <p class="lead">Start your healthcare journey with ASAA Healthcare today.</p>
                        <a href="../appointments/book.php" class="btn btn-primary-asaa btn-lg">
                            <i class="fas fa-calendar-plus me-2"></i>Book Your First Appointment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar me-1"></i>Date & Time</th>
                                    <th><i class="fas fa-user-md me-1"></i>Doctor</th>
                                    <th><i class="fas fa-stethoscope me-1"></i>Department</th>
                                    <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                    <th><i class="fas fa-credit-card me-1"></i>Payment</th>
                                    <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($appointments, 0, 8) as $appointment): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong style="color: var(--navbar-dark);">
                                                    <?= date('M j, Y', strtotime($appointment['appointment_date'])) ?>
                                                </strong>
                                                <br>
                                                <span style="color: var(--primary-color); font-weight: 500;">
                                                    <?= date('g:i A', strtotime($appointment['time_slot'])) ?>
                                                </span>
                                                <br>
                                                <small class="text-muted"><?= date('l', strtotime($appointment['appointment_date'])) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: rgba(32, 125, 135, 0.1); color: var(--navbar-dark);">
                                                <?= htmlspecialchars($appointment['specialization_name'] ?? 'General Practice') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusIcon = '';
                                            switch ($appointment['status']) {
                                                case 'REVIEW':
                                                    $statusClass = 'status-review';
                                                    $statusIcon = 'fas fa-hourglass-half';
                                                    break;
                                                case 'CONFIRMED':
                                                    $statusClass = 'status-confirmed';
                                                    $statusIcon = 'fas fa-check';
                                                    break;
                                                case 'COMPLETED':
                                                    $statusClass = 'status-completed';
                                                    $statusIcon = 'fas fa-check-double';
                                                    break;
                                                case 'CANCELLED':
                                                    $statusClass = 'status-cancelled';
                                                    $statusIcon = 'fas fa-times-circle';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                                    $statusIcon = 'fas fa-question';
                                            }
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <i class="<?= $statusIcon ?>"></i>
                                                <?= htmlspecialchars($appointment['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $paymentStatus = $appointment['payment_status'] ?? 'PENDING';
                                            $paymentClass = $paymentStatus === 'PAID' ? 'bg-success' : 'bg-warning';
                                            $paymentIcon = $paymentStatus === 'PAID' ? 'fas fa-check-circle' : 'fas fa-credit-card';
                                            ?>
                                            <div>
                                                <span class="badge <?= $paymentClass ?>">
                                                    <i class="<?= $paymentIcon ?>"></i>
                                                    <?= htmlspecialchars($paymentStatus) ?>
                                                </span>
                                                <?php if (isset($appointment['consultation_fee']) && $appointment['consultation_fee'] > 0): ?>
                                                    <br>
                                                    <small style="color: var(--primary-color); font-weight: 600;">
                                                        LKR <?= number_format($appointment['consultation_fee'], 2) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <?php if (($appointment['payment_status'] ?? 'PENDING') === 'PENDING'): ?>
                                                    <a href="../payments/pay.php?id=<?= $appointment['appointment_id'] ?>" 
                                                       class="action-btn btn-pay">
                                                        <i class="fas fa-credit-card"></i>Pay Now
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($appointment['status'] === 'REVIEW'): ?>
                                                    <a href="../appointments/edit.php?id=<?= $appointment['appointment_id'] ?>" 
                                                       class="action-btn btn-edit">
                                                        <i class="fas fa-edit"></i>Edit
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($appointment['status'] === 'COMPLETED'): ?>
                                                    <a href="../reports/view.php?id=<?= $appointment['appointment_id'] ?>" 
                                                       class="action-btn" style="background: #17a2b8; color: white;">
                                                        <i class="fas fa-file-alt"></i>Report
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (count($appointments) > 8): ?>
                            <div class="text-center p-3" style="background: var(--navbar-light);">
                                <a href="../appointments/list.php" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-2"></i>View All <?= count($appointments) ?> Appointments
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Modern dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to action buttons
            document.querySelectorAll('.action-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const icon = this.querySelector('i');
                    const originalClass = icon.className;
                    
                    icon.className = 'fas fa-spinner fa-spin';
                    this.style.opacity = '0.7';
                    
                    // Reset after a delay (in case navigation fails)
                    setTimeout(() => {
                        if (icon) {
                            icon.className = originalClass;
                            this.style.opacity = '1';
                        }
                    }, 3000);
                });
            });
            
            // Add hover effects to stat cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
            
            // Add click animations to quick action cards
            document.querySelectorAll('.action-card').forEach(card => {
                card.addEventListener('mousedown', function() {
                    this.style.transform = 'translateY(-3px) scale(0.98)';
                });
                
                card.addEventListener('mouseup', function() {
                    this.style.transform = 'translateY(-5px) scale(1)';
                });
            });
            
            // Show welcome toast
            showWelcomeToast();
        });
        
        function showWelcomeToast() {
            const toastContainer = getToastContainer();
            const userName = "<?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>";
            
            const toastHtml = `
                <div class="toast align-items-center text-white border-0" role="alert" 
                     style="background: var(--toast-gradient); border-radius: 12px;">
                    <div class="d-flex">
                        <div class="toast-body fw-bold">
                            <i class="fas fa-heart me-2"></i>
                            Welcome back, ${userName}! Ready to take care of your health today?
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                                data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.innerHTML = toastHtml;
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
            toast.show();
        }
        
        function getToastContainer() {
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '1100';
                document.body.appendChild(container);
            }
            return container;
        }
        
        // Auto-refresh appointments every 2 minutes
        setInterval(() => {
            // Only refresh if user is still active (not idle)
            if (document.hasFocus()) {
                fetch(window.location.href, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(() => {
                    console.log('Dashboard refreshed silently');
                });
            }
        }, 120000); // 2 minutes
        
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
