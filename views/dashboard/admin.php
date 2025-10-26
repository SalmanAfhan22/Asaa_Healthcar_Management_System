<?php
require_once '../../config/config.php';
require_once '../../services/AppointmentService.php';

requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/access-denied.php');
    exit;
}

// Fetch comprehensive admin statistics
try {
    global $db;

    // Total active users
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'ACTIVE'");
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch()['count'];

    // Today's appointments
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()");
    $stmt->execute();
    $stats['today_appointments'] = $stmt->fetch()['count'];

    // Total doctors
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = 3 AND status = 'ACTIVE'");
    $stmt->execute();
    $stats['total_doctors'] = $stmt->fetch()['count'];

    // Total patients
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = 4 AND status = 'ACTIVE'");
    $stmt->execute();
    $stats['total_patients'] = $stmt->fetch()['count'];

    // Weekly appointments
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $stats['week_appointments'] = $stmt->fetch()['count'];

    // Pending appointments
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = 'REVIEW'");
    $stmt->execute();
    $stats['pending_appointments'] = $stmt->fetch()['count'];

    // Revenue this month
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(dd.consultation_fee), 0) as revenue
        FROM appointments a
        JOIN doctor_details dd ON a.doctor_id = dd.user_id
        WHERE MONTH(a.appointment_date) = MONTH(CURDATE()) 
        AND YEAR(a.appointment_date) = YEAR(CURDATE())
        AND a.payment_status = 'PAID'
    ");
    $stmt->execute();
    $stats['monthly_revenue'] = $stmt->fetch()['revenue'];

    // Recent appointments
    $stmt = $db->prepare("
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               s.specialization_name,
               dd.consultation_fee
        FROM appointments a
        JOIN users p ON a.patient_id = p.user_id
        JOIN users d ON a.doctor_id = d.user_id
        JOIN doctor_details dd ON a.doctor_id = dd.user_id
        JOIN specializations s ON dd.specialization_id = s.specialization_id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentAppointments = $stmt->fetchAll();
} catch (Exception $e) {
    $stats = [
        'total_users' => 0,
        'today_appointments' => 0,
        'total_doctors' => 0,
        'total_patients' => 0,
        'week_appointments' => 0,
        'pending_appointments' => 0,
        'monthly_revenue' => 0
    ];
    $recentAppointments = [];
    error_log("Admin dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js">
    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: #b2ebf2;
            --navbar-dark: #207d87;
            --navbar-light: #e6f2f5;
            --header-gradient: linear-gradient(135deg, #207d87 0%, #4dd0e1 50%, #b2ebf2 100%);
            --admin-gradient: linear-gradient(135deg, rgb(2, 59, 65) 0%, rgb(8, 69, 82) 50%, rgb(4, 91, 101) 100%);
            --card-shadow: 0 8px 32px rgba(32, 125, 135, 0.12);
            --card-shadow-hover: 0 12px 40px rgba(32, 125, 135, 0.18);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            background: linear-gradient(135deg, var(--navbar-light) 0%, #f0f8ff 50%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Modern Admin Sidebar */
        .admin-sidebar {
            background: var(--admin-gradient);
            min-height: 100vh;
            padding: 0;
            box-shadow: 4px 0 20px rgba(111, 66, 193, 0.15);
            position: fixed;
            left: 0;
            top: 0;
            width: 300px;
            z-index: 1000;
            flex-direction: column;
            overflow-y: auto;
        }

        .admin-brand {
            padding: 2.5rem 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }

        .admin-brand-icon {
            font-size: 3rem;
            color: white;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .admin-brand h3 {
            color: white;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .admin-brand small {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .admin-nav {
            flex-grow: 1;
            padding: 1rem 0 0.5rem 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .admin-nav .nav-main {
            flex-grow: 1;
        }

        .admin-nav .nav-footer {
            margin-top: auto;
            /* FIXED: Pushes logout to bottom */
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-nav .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 2rem;
            margin: 0.25rem 1rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .admin-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .admin-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.4);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }

        .admin-nav .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.75rem;
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

        /* Main Content */
        .admin-content {
            margin-left: 300px;
            padding: 0;
            min-height: 100vh;
        }

        .admin-header {
            background: white;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(32, 125, 135, 0.08);
            border-bottom: 1px solid rgba(32, 125, 135, 0.1);
            margin-bottom: 2rem;
        }

        .admin-welcome {
            background: var(--admin-gradient);
            color: white;
            padding: 3rem 2rem;
            margin: 0 2rem 2rem 2rem;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .admin-welcome::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-25px) rotate(180deg);
            }
        }

        /* Enhanced Stat Cards */
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            background: var(--header-gradient);
            border-radius: 18px 18px 0 0;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--primary-color);
        }

        .stat-card.revenue::before {
            background: var(--success-color);
        }

        .stat-card.appointments::before {
            background: var(--info-color);
        }

        .stat-card.users::before {
            background: var(--warning-color);
        }

        .stat-card.pending::before {
            background: var(--danger-color);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            padding: 1.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(32, 125, 135, 0.1), rgba(178, 235, 242, 0.2));
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(10deg);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin: 1rem 0;
            background: var(--header-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-title {
            font-size: 1.1rem;
            color: var(--navbar-dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem;
        }

        .action-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fdfe 100%);
            border: 1px solid rgba(32, 125, 135, 0.1);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            text-align: center;
            text-decoration: none;
            color: var(--navbar-dark);
            transition: all 0.4s ease;
            box-shadow: 0 4px 20px rgba(32, 125, 135, 0.08);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
            color: var(--navbar-dark);
            border-color: var(--primary-color);
        }

        .action-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        /* Recent Appointments Table */
        .appointments-table {
            background: white;
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            border: none;
            margin: 0 2rem;
        }

        .appointments-table .card-header {
            background: var(--admin-gradient);
            color: white;
            border-radius: 18px 18px 0 0;
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
            font-size: 0.95rem;
        }

        .table-modern tbody td {
            padding: 1rem;
            border-bottom: 1px solid rgba(32, 125, 135, 0.1);
            vertical-align: middle;
        }

        .table-modern tbody tr:hover {
            background: linear-gradient(135deg, rgba(32, 125, 135, 0.02), rgba(178, 235, 242, 0.05));
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-review {
            background: linear-gradient(135deg, #ffc107, #ffca28);
            color: #856404;
        }

        .status-confirmed {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
        }

        .status-completed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                position: relative;
            }

            .admin-content {
                margin-left: 0;
            }

            .admin-welcome,
            .appointments-table {
                margin: 0 1rem 1rem 1rem;
            }

            .admin-stats,
            .quick-actions {
                margin: 1rem;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Modern Admin Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-brand">
            <div class="admin-brand-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>ASAA Healthcare</h3>
            <small>Administrator Portal</small>
        </div>

        <nav class="admin-nav">
            <!-- Main Navigation Items -->
            <ul class="nav flex-column nav-main">
                <li class="nav-item">
                    <a class="nav-link active">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard Overview</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../appointments/manage.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Manage Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../users/manage.php">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../doctors/manage.php">
                        <i class="fas fa-user-md"></i>
                        <span>Doctor Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../reports/analytics.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics & Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../payments/manage.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Payment Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../settings/system.php">
                        <i class="fas fa-cog"></i>
                        <span>System Settings</span>
                    </a>
                </li>
            </ul>

            <!-- FIXED: Footer Navigation with Logout -->
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


    <!-- Admin Main Content -->
    <div class="admin-content">
        <!-- Top Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1" style="color: var(--navbar-dark);">
                        <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
                    </h2>
                    <p class="mb-0 text-muted">Complete system overview and management</p>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3">
                        <strong style="color: var(--navbar-dark);"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                        <br>
                        <small class="text-muted"><?= date('l, F j, Y - g:i A') ?></small>
                    </div>
                    <div class="user-avatar" style="background: var(--admin-gradient); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Welcome Section -->
        <div class="admin-welcome">
            <div style="position: relative; z-index: 2;">
                <h1 class="mb-3">
                    <i class="fas fa-crown me-2"></i>
                    Welcome, Administrator <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>
                </h1>
                <p class="lead mb-4">Manage and oversee the entire ASAA Healthcare system operations</p>
                <div class="row text-center">
                    <div class="col-md-4">
                        <h3 class="mb-1"><?= $stats['today_appointments'] ?></h3>
                        <small>Today's Appointments</small>
                    </div>
                    <div class="col-md-4">
                        <h3 class="mb-1"><?= $stats['pending_appointments'] ?></h3>
                        <small>Pending Reviews</small>
                    </div>
                    <div class="col-md-4">
                        <h3 class="mb-1">LKR <?= number_format($stats['monthly_revenue'], 2) ?></h3>
                        <small>This Month's Revenue</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="admin-stats">
            <div class="stat-card users">
                <div class="stat-icon text-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-title">Total System Users</div>
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <small class="text-muted">Active accounts</small>
            </div>

            <div class="stat-card appointments">
                <div class="stat-icon text-info">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-title">This Week</div>
                <div class="stat-number"><?= $stats['week_appointments'] ?></div>
                <small class="text-muted">Appointments scheduled</small>
            </div>

            <div class="stat-card revenue">
                <div class="stat-icon text-success">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-title">Monthly Revenue</div>
                <div class="stat-number">LKR <?= number_format($stats['monthly_revenue'] / 1000, 0) ?>K</div>
                <small class="text-muted">Revenue generated</small>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon text-warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-title">Pending Reviews</div>
                <div class="stat-number"><?= $stats['pending_appointments'] ?></div>
                <small class="text-muted">Need attention</small>
            </div>
        </div>

        <!-- Quick Admin Actions -->
        <div class="quick-actions">
            <a href="../users/manage.php" class="action-card">
                <i class="fas fa-user-cog"></i>
                <h6>Manage Users</h6>
                <small class="text-muted">Add, edit, or deactivate users</small>
            </a>

            <a href="../doctors/manage.php" class="action-card">
                <i class="fas fa-user-md"></i>
                <h6>Doctor Management</h6>
                <small class="text-muted">Manage doctor profiles & schedules</small>
            </a>

            <a href="../appointments/approve.php" class="action-card">
                <i class="fas fa-clipboard-check"></i>
                <h6>Approve Appointments</h6>
                <small class="text-muted">Review pending appointments</small>
            </a>

            <a href="../reports/analytics.php" class="action-card">
                <i class="fas fa-chart-bar"></i>
                <h6>System Analytics</h6>
                <small class="text-muted">View detailed reports</small>
            </a>

            <a href="../payments/transactions.php" class="action-card">
                <i class="fas fa-money-bill-wave"></i>
                <h6>Payment Reports</h6>
                <small class="text-muted">Track financial transactions</small>
            </a>

            <a href="../settings/system.php" class="action-card">
                <i class="fas fa-tools"></i>
                <h6>System Settings</h6>
                <small class="text-muted">Configure system parameters</small>
            </a>
        </div>

        <!-- Recent Appointments Table -->
        <div class="appointments-table card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Appointments
                    <span class="badge bg-light text-dark ms-2"><?= count($recentAppointments) ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentAppointments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-plus fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No appointments yet</h4>
                        <p class="text-muted">Recent appointment activity will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar me-1"></i>Date & Time</th>
                                    <th><i class="fas fa-user me-1"></i>Patient</th>
                                    <th><i class="fas fa-user-md me-1"></i>Doctor</th>
                                    <th><i class="fas fa-stethoscope me-1"></i>Specialization</th>
                                    <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                    <th><i class="fas fa-money-bill me-1"></i>Fee</th>
                                    <th><i class="fas fa-cogs me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAppointments as $appointment): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--navbar-dark);">
                                                <?= date('M j, Y', strtotime($appointment['appointment_date'])) ?>
                                            </strong>
                                            <br>
                                            <span style="color: var(--primary-color);">
                                                <?= date('g:i A', strtotime($appointment['time_slot'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($appointment['patient_name']) ?></strong>
                                        </td>
                                        <td>
                                            <strong>Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: rgba(32, 125, 135, 0.1); color: var(--navbar-dark);">
                                                <?= htmlspecialchars($appointment['specialization_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
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
                                                <i class="fas fa-eye"></i> View
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
            // Add loading animations to action cards
            document.querySelectorAll('.action-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    const icon = this.querySelector('i');
                    const originalClass = icon.className;

                    icon.className = 'fas fa-spinner fa-spin';
                    this.style.opacity = '0.7';

                    setTimeout(() => {
                        if (icon) {
                            icon.className = originalClass;
                            this.style.opacity = '1';
                        }
                    }, 2000);
                });
            });

            // Add hover effects to stat cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Show admin welcome toast
            setTimeout(() => {
                showToast('🏥 Welcome to ASAA Healthcare Admin Portal', 'success');
            }, 1000);
        });

        function showToast(message, type = 'info') {
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
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


        document.addEventListener('DOMContentLoaded', function() {
            // Ensure logout button is always visible
            const logoutBtn = document.querySelector('.logout-link');
            if (logoutBtn) {
                logoutBtn.style.display = 'flex';
                logoutBtn.style.visibility = 'visible';
                logoutBtn.style.position = 'relative';
                logoutBtn.style.zIndex = '1002';

                // Add click animation
                logoutBtn.addEventListener('click', function(e) {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><span>Logging Out...</span>';
                    this.style.background = 'linear-gradient(135deg, #6c757d, #5a6268)';

                    // Allow the logout to proceed after visual feedback
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 500);

                    e.preventDefault();
                    return false;
                });
            }

            // Ensure sidebar scrolls to show logout if needed
            const sidebar = document.querySelector('.admin-sidebar');
            if (sidebar) {
                sidebar.style.overflowY = 'auto';
                sidebar.style.maxHeight = '100vh';
            }
        });
    </script>
</body>

</html>