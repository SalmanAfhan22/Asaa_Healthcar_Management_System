<nav class="navbar navbar-expand-lg asaa-navbar">
    <div class="container-fluid">
        <!-- Brand Logo -->
        <a class="navbar-brand asaa-brand" href="<?= BASE_URL ?>">
            <div class="brand-container">
                <div class="brand-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">ASAA</span>
                    <span class="brand-tagline">Healthcare</span>
                </div>
            </div>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler custom-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Navigation Links -->
            <ul class="navbar-nav me-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $dashboardLinks = [
                        'admin' => ['url' => '/views/dashboard/admin.php', 'icon' => 'fas fa-tachometer-alt', 'title' => 'Admin Dashboard'],
                        'staff' => ['url' => '/views/dashboard/staff.php', 'icon' => 'fas fa-clipboard-list', 'title' => 'Staff Dashboard'],
                        'doctor' => ['url' => '/views/dashboard/doctor.php', 'icon' => 'fas fa-user-md', 'title' => 'Doctor Portal'],
                        'patient' => ['url' => '/views/dashboard/patient.php', 'icon' => 'fas fa-user', 'title' => 'Patient Portal']
                    ];

                    if (isset($dashboardLinks[$_SESSION['user_role']])):
                        $dashboard = $dashboardLinks[$_SESSION['user_role']];
                    ?>
                        <li class="nav-item">
                            <a class="nav-link dashboard-link" href="<?= BASE_URL ?><?= $dashboard['url'] ?>">
                                <i class="<?= $dashboard['icon'] ?>"></i>
                                <span><?= $dashboard['title'] ?></span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($_SESSION['user_role'] === 'patient'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/views/appointments/list.php">
                                <i class="fas fa-calendar-plus"></i>
                                <span>My Appointments</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- User Menu -->
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Notifications -->
                    <li class="nav-item dropdown notification-dropdown">
                        <a class="nav-link notification-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </a>
                        <ul class="dropdown-menu notification-menu">
                            <li class="notification-header">
                                <h6>Notifications</h6>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li class="notification-item">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-calendar-check text-success"></i>
                                    <div class="notification-content">
                                        <span class="notification-title">Appointment Confirmed</span>
                                        <span class="notification-time">2 hours ago</span>
                                    </div>
                                </a>
                            </li>
                            <li class="notification-item">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user-md text-primary"></i>
                                    <div class="notification-content">
                                        <span class="notification-title">Dr. Silva is available</span>
                                        <span class="notification-time">4 hours ago</span>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li class="notification-footer">
                                <a class="dropdown-item text-center" href="#">View All Notifications</a>
                            </li>
                        </ul>
                    </li>

                    <!-- User Profile -->
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle user-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                                <span class="user-role"><?= ucfirst($_SESSION['user_role']) ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu user-menu">
                            <li class="user-info-header">
                                <div class="user-avatar-large">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="user-details">
                                    <h6><?= htmlspecialchars($_SESSION['user_name']) ?></h6>
                                    <small class="text-muted"><?= ucfirst($_SESSION['user_role']) ?></small>
                                </div>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/views/users/profile.php">
                                    <i class="fas fa-user"></i>
                                    <span>My Profile</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/views/settings/preferences.php">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            <?php if ($_SESSION['user_role'] === 'patient'): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/views/appointments/history.php">
                                        <i class="fas fa-history"></i>
                                        <span>Appointment History</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item logout-btn" href="<?= BASE_URL ?>/controllers/AuthController.php?action=logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Guest Menu -->
                    <li class="nav-item">
                        <a class="nav-link login-btn" href="<?= BASE_URL ?>/views/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link register-btn" href="<?= BASE_URL ?>/views/auth/register.php">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    :root {
        --asaa-primary:rgb(0, 98, 109);
        --asaa-primary-dark: #1a6b75;
        --asaa-primary-light:rgb(6, 147, 165);
        --asaa-accent: #b2ebf2;
        --asaa-accent-light: #e6f2f5;
        --asaa-gradient: linear-gradient(45deg, #207d87 10%, #4dd0e1 50%,rgb(7, 112, 125) 100%);
        --navbar-height: 80px;
        --shadow-elegant: 0 4px 20px rgba(32, 125, 135, 0.15);
        --shadow-hover: 0 6px 30px rgba(32, 125, 135, 0.25);
    }

    /* Main Navbar Styling */
    .asaa-navbar {
        background: var(--asaa-gradient);
        backdrop-filter: blur(20px);
        box-shadow: var(--shadow-elegant);
        min-height: var(--navbar-height);
        padding: 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: sticky;
        top: 0;
        z-index: 1050;
        transition: all 0.3s ease;
    }

    .asaa-navbar::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: shimmer 3s infinite;
        pointer-events: none;
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    /* Brand Styling */
    .asaa-brand {
        padding: 1rem 1.5rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .asaa-brand:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

    .brand-container {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .brand-icon {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .brand-icon i {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    .brand-text {
        display: flex;
        flex-direction: column;
    }

    .brand-name {
        font-size: 28px;
        font-weight: 800;
        color: white;
        line-height: 1;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .brand-tagline {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
        margin-top: -2px;
    }

    /* Custom Mobile Toggle */
    .custom-toggler {
        border: none;
        padding: 8px 12px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .custom-toggler:focus {
        box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
    }

    .custom-toggler span {
        display: block;
        width: 22px;
        height: 2px;
        background: white;
        margin: 4px 0;
        transition: 0.3s;
        border-radius: 2px;
    }

    .custom-toggler:hover span {
        background: var(--asaa-accent);
    }

    /* Navigation Links */
    .navbar-nav .nav-link {
        color: white !important;
        font-weight: 500;
        padding: 1rem 1.5rem !important;
        border-radius: 8px;
        margin: 0 4px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
    }

    .navbar-nav .nav-link:hover {
        background: rgba(255, 255, 255, 0.5);
        color: var(--asaa-primary-dark) !important;
        transform: translateY(-1px);
    }

    .navbar-nav .nav-link i {
        font-size: 16px;
        width: 20px;
        text-align: center;
    }

    .dashboard-link {
        background: rgba(255, 255, 255, 0.35);
        color: var(--asaa-primary-dark) !important;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* User Avatar and Info */
    .user-avatar,
    .user-avatar-large {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        border: 2px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    .user-avatar-large {
        width: 50px;
        height: 50px;
        font-size: 22px;
    }

    .user-toggle {
        gap: 12px !important;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .user-name {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.2;
    }

    .user-role {
        font-size: 12px;
        opacity: 0.8;
        font-weight: 400;
    }

    /* Notification Bell */
    .notification-toggle {
        position: relative;
    }

    .notification-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #ff4757;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {

        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-4px);
        }

        60% {
            transform: translateY(-2px);
        }
    }

    /* Dropdown Menus */
    .dropdown-menu {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(32, 125, 135, 0.1);
        border-radius: 12px;
        box-shadow: var(--shadow-hover);
        padding: 0;
        margin-top: 8px;
        min-width: 280px;
        animation: dropdownFadeIn 0.3s ease;
    }

    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .user-info-header {
        padding: 1.5rem;
        background: var(--asaa-gradient);
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 12px 12px 0 0;
    }

    .user-info-header h6 {
        margin: 0;
        font-weight: 600;
    }

    .notification-header {
        padding: 1rem 1.5rem 0.5rem;
    }

    .notification-header h6 {
        margin: 0;
        color: var(--asaa-primary);
        font-weight: 600;
    }

    .dropdown-item {
        padding: 0.75rem 1.5rem;
        color: #333;
        font-weight: 500;
        border-radius: 0;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .dropdown-item:hover {
        background: rgba(32, 125, 135, 0.1);
        color: var(--asaa-primary);
        transform: translateX(4px);
    }

    .dropdown-item i {
        width: 20px;
        text-align: center;
        font-size: 16px;
    }

    .logout-btn:hover {
        background: rgba(220, 53, 69, 0.1) !important;
        color: #dc3545 !important;
    }

    /* Notification Items */
    .notification-item .dropdown-item {
        padding: 1rem 1.5rem;
        align-items: flex-start;
    }

    .notification-content {
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .notification-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 2px;
    }

    .notification-time {
        font-size: 12px;
        color: #666;
    }

    .notification-footer .dropdown-item {
        background: var(--asaa-accent-light);
        color: var(--asaa-primary);
        font-weight: 600;
        justify-content: center;
        border-radius: 0 0 12px 12px;
    }

    /* Login/Register Buttons for Guests */
    .login-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 25px !important;
        padding: 0.5rem 1.5rem !important;
        margin: 0 4px;
    }

    .register-btn {
        background: rgba(255, 255, 255, 0.9);
        color: var(--asaa-primary) !important;
        border-radius: 25px !important;
        padding: 0.5rem 1.5rem !important;
        margin: 0 4px;
        font-weight: 600;
    }

    .register-btn:hover {
        background: white !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    /* Responsive Design */
    @media (max-width: 991px) {
        .asaa-navbar {
            min-height: 70px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            font-size: 20px;
        }

        .brand-name {
            font-size: 24px;
        }

        .brand-tagline {
            font-size: 12px;
        }

        .navbar-collapse {
            background: rgba(32, 125, 135, 0.98);
            backdrop-filter: blur(20px);
            margin: 1rem -1rem -1rem -1rem;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-nav {
            padding: 1rem;
        }

        .nav-link {
            margin: 0.25rem 0 !important;
        }

        .user-info {
            align-items: flex-start !important;
        }
    }

    @media (max-width: 576px) {
        .user-info {
            display: none;
        }

        .dropdown-menu {
            min-width: 250px;
        }
    }

    /* Smooth scrolling for anchor links */
    html {
        scroll-behavior: smooth;
    }

    /* Add subtle glow effect on focus */
    .nav-link:focus,
    .dropdown-item:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
    }
</style>