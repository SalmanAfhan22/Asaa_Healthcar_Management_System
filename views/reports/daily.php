<?php
require_once '../../config/config.php';
requireLogin();

// Generate daily report
try {
    global $db;
    
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Daily appointments
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
        WHERE a.appointment_date = ?
        ORDER BY a.time_slot ASC
    ");
    $stmt->execute([$date]);
    $appointments = $stmt->fetchAll();
    
    // FIXED: PHP 7.3 Compatible statistics calculation
    $totalAppointments = count($appointments);
    
    // Count completed appointments
    $completedCount = 0;
    foreach ($appointments as $appointment) {
        if ($appointment['status'] === 'COMPLETED') {
            $completedCount++;
        }
    }
    
    // Count cancelled appointments
    $cancelledCount = 0;
    foreach ($appointments as $appointment) {
        if ($appointment['status'] === 'CANCELLED') {
            $cancelledCount++;
        }
    }
    
    // Calculate revenue from paid appointments
    $totalRevenue = 0;
    foreach ($appointments as $appointment) {
        if ($appointment['payment_status'] === 'PAID') {
            $totalRevenue += $appointment['consultation_fee'];
        }
    }
    
    $dailyStats = [
        'total_appointments' => $totalAppointments,
        'completed' => $completedCount,
        'cancelled' => $cancelledCount,
        'revenue' => $totalRevenue
    ];
    
} catch (Exception $e) {
    $appointments = [];
    $dailyStats = [
        'total_appointments' => 0, 
        'completed' => 0, 
        'cancelled' => 0, 
        'revenue' => 0
    ];
    error_log("Daily report error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #207d87;
            --accent-color: #b2ebf2;
            --navbar-dark: #207d87;
            --navbar-light: #e6f2f5;
            --header-gradient: linear-gradient(135deg, #207d87 0%, #4dd0e1 50%, #b2ebf2 100%);
            --card-shadow: 0 8px 32px rgba(32, 125, 135, 0.12);
            --card-shadow-hover: 0 12px 40px rgba(32, 125, 135, 0.18);
        }
        
        body {
            background: linear-gradient(135deg, var(--navbar-light) 0%, #f0f8ff 50%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem 0;
            min-height: 100vh;
        }
        
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .report-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(32, 125, 135, 0.1);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .report-header {
            background: var(--header-gradient);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .report-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 4s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        
        .report-header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }
        
        .date-selector {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-top: 1rem;
            position: relative;
            z-index: 2;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fdfe 100%);
            border: 1px solid rgba(32, 125, 135, 0.1);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s ease;
            box-shadow: 0 4px 20px rgba(32, 125, 135, 0.08);
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--card-shadow-hover);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 50%;
            display: inline-block;
        }
        
        .stat-icon.total { color: #17a2b8; background: rgba(23, 162, 184, 0.1); }
        .stat-icon.completed { color: #28a745; background: rgba(40, 167, 69, 0.1); }
        .stat-icon.cancelled { color: #dc3545; background: rgba(220, 53, 69, 0.1); }
        .stat-icon.revenue { color: #ffc107; background: rgba(255, 193, 7, 0.1); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0 0.5rem 0;
            color: var(--navbar-dark);
        }
        
        .stat-title {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .appointments-table {
            background: white;
            border-radius: 16px;
            margin: 0 2rem 2rem 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .table-header {
            background: var(--navbar-light);
            color: var(--navbar-dark);
            padding: 1.5rem 2rem;
            border-radius: 16px 16px 0 0;
        }
        
        .table-modern {
            margin-bottom: 0;
        }
        
        .table-modern thead th {
            background: var(--navbar-light);
            color: var(--navbar-dark);
            font-weight: 600;
            padding: 1rem;
            border: none;
        }
        
        .table-modern tbody td {
            padding: 1rem;
            border-bottom: 1px solid rgba(32, 125, 135, 0.1);
            vertical-align: middle;
        }
        
        .table-modern tbody tr:hover {
            background: linear-gradient(135deg, rgba(32, 125, 135, 0.02), rgba(178, 235, 242, 0.05));
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .status-cancelled {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .status-confirmed {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
        }
        
        .status-review {
            background: linear-gradient(135deg, #ffc107, #ffca28);
            color: #856404;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                padding: 1rem;
            }
            
            .appointments-table {
                margin: 0 1rem 1rem 1rem;
            }
            
            .report-header {
                padding: 2rem 1rem;
            }
            
            .report-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="../dashboard/<?= $_SESSION['user_role'] ?>.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <div class="report-card">
            <div class="report-header">
                <h1><i class="fas fa-chart-bar me-3"></i>Daily Operations Report</h1>
                <p style="font-size: 1.2rem; opacity: 0.9; position: relative; z-index: 2;">
                    <?= date('l, F j, Y', strtotime($date)) ?>
                </p>
                
                <div class="date-selector">
                    <form method="GET" class="d-flex justify-content-center align-items-center">
                        <label for="date" class="me-3 mb-0" style="font-weight: 600;">Select Date:</label>
                        <input type="date" id="date" name="date" value="<?= $date ?>" 
                               class="form-control me-2" style="max-width: 200px; background: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.3);">
                        <button type="submit" class="btn btn-light">
                            <i class="fas fa-search me-1"></i>View Report
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Daily Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?= $dailyStats['total_appointments'] ?></div>
                    <div class="stat-title">Total Appointments</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $dailyStats['completed'] ?></div>
                    <div class="stat-title">Completed</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon cancelled">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number"><?= $dailyStats['cancelled'] ?></div>
                    <div class="stat-title">Cancelled</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-number">LKR <?= number_format($dailyStats['revenue'], 0) ?></div>
                    <div class="stat-title">Revenue Generated</div>
                </div>
            </div>
            
            <!-- Appointments Table -->
            <?php if (!empty($appointments)): ?>
                <div class="appointments-table">
                    <div class="table-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Detailed Appointment Schedule
                            <span class="badge bg-primary ms-2"><?= count($appointments) ?></span>
                        </h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-clock me-1"></i>Time</th>
                                    <th><i class="fas fa-user me-1"></i>Patient</th>
                                    <th><i class="fas fa-user-md me-1"></i>Doctor</th>
                                    <th><i class="fas fa-stethoscope me-1"></i>Department</th>
                                    <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                    <th><i class="fas fa-credit-card me-1"></i>Payment</th>
                                    <th><i class="fas fa-rupee-sign me-1"></i>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--primary-color); font-size: 1.1rem;">
                                                <?= date('g:i A', strtotime($appointment['time_slot'])) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($appointment['patient_name']) ?></strong>
                                        </td>
                                        <td>
                                            <strong>Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: rgba(32, 125, 135, 0.1); color: var(--navbar-dark); font-size: 0.9rem;">
                                                <?= htmlspecialchars($appointment['specialization_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
                                                <?= htmlspecialchars($appointment['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($appointment['payment_status'] === 'PAID'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>PAID
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-clock me-1"></i>PENDING
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($appointment['payment_status'] === 'PAID'): ?>
                                                <strong style="color: #28a745; font-size: 1.1rem;">
                                                    LKR <?= number_format($appointment['consultation_fee'], 2) ?>
                                                </strong>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    LKR <?= number_format($appointment['consultation_fee'], 2) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot style="background: linear-gradient(135deg, rgba(32, 125, 135, 0.05), rgba(178, 235, 242, 0.1));">
                                <tr>
                                    <td colspan="6" class="text-end fw-bold" style="padding: 1.5rem; color: var(--navbar-dark);">
                                        <i class="fas fa-calculator me-2"></i>Total Daily Revenue:
                                    </td>
                                    <td style="padding: 1.5rem;">
                                        <strong style="color: #28a745; font-size: 1.3rem;">
                                            LKR <?= number_format($dailyStats['revenue'], 2) ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5" style="margin: 2rem;">
                    <i class="fas fa-calendar-times fa-4x mb-3" style="color: var(--primary-color);"></i>
                    <h3 style="color: var(--navbar-dark);">No Appointments on <?= date('F j, Y', strtotime($date)) ?></h3>
                    <p class="text-muted">Select a different date to view appointment data</p>
                    
                    <div class="mt-4">
                        <a href="?date=<?= date('Y-m-d', strtotime('-1 day', strtotime($date))) ?>" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-chevron-left me-1"></i>Previous Day
                        </a>
                        <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-primary me-2">
                            <i class="fas fa-calendar-day me-1"></i>Today
                        </a>
                        <a href="?date=<?= date('Y-m-d', strtotime('+1 day', strtotime($date))) ?>" class="btn btn-outline-secondary">
                            Next Day<i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Report Summary -->
            <div class="p-4" style="background: linear-gradient(135deg, rgba(32, 125, 135, 0.05), rgba(178, 235, 242, 0.1)); border-top: 1px solid rgba(32, 125, 135, 0.1);">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h6 style="color: var(--navbar-dark);">Completion Rate</h6>
                        <strong style="color: #28a745; font-size: 1.2rem;">
                            <?= $dailyStats['total_appointments'] > 0 ? round(($dailyStats['completed'] / $dailyStats['total_appointments']) * 100, 1) : 0 ?>%
                        </strong>
                    </div>
                    <div class="col-md-3">
                        <h6 style="color: var(--navbar-dark);">Cancellation Rate</h6>
                        <strong style="color: #dc3545; font-size: 1.2rem;">
                            <?= $dailyStats['total_appointments'] > 0 ? round(($dailyStats['cancelled'] / $dailyStats['total_appointments']) * 100, 1) : 0 ?>%
                        </strong>
                    </div>
                    <div class="col-md-3">
                        <h6 style="color: var(--navbar-dark);">Average Fee</h6>
                        <strong style="color: var(--primary-color); font-size: 1.2rem;">
                            LKR <?= $dailyStats['total_appointments'] > 0 ? number_format($dailyStats['revenue'] / $dailyStats['total_appointments'], 2) : '0.00' ?>
                        </strong>
                    </div>
                    <div class="col-md-3">
                        <h6 style="color: var(--navbar-dark);">Report Generated</h6>
                        <strong style="color: var(--navbar-dark); font-size: 1.2rem;">
                            <?= date('g:i A') ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="text-center mt-4">
            <a href="export.php?date=<?= $date ?>" class="btn btn-success me-2">
                <i class="fas fa-file-excel me-1"></i>Export to Excel
            </a>
            <a href="print.php?date=<?= $date ?>" class="btn btn-info" target="_blank">
                <i class="fas fa-print me-1"></i>Print Report
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add hover effects to stat cards
            $('.stat-card').hover(
                function() {
                    $(this).css('transform', 'translateY(-5px) scale(1.02)');
                },
                function() {
                    $(this).css('transform', 'translateY(0) scale(1)');
                }
            );
            
            // Auto-refresh every 30 minutes
            setTimeout(function() {
                if (document.hasFocus()) {
                    location.reload();
                }
            }, 1800000);
            
            // Date change auto-submit
            $('#date').change(function() {
                $(this).closest('form').submit();
            });
            
            console.log('📊 Daily report loaded for: <?= $date ?>');
        });
    </script>
</body>
</html>
