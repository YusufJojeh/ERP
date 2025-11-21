<?php
if (!isLoggedIn()) {
    redirect(rtrim(APP_URL, '/') . '/index.php?controller=Auth&action=login');
}

$unreadNotifications = getUnreadNotifications($_SESSION['user_id']);

// Initialize $currentPage if not set
if (!isset($currentPage)) {
    $currentPage = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="<?php echo rtrim(APP_URL, '/'); ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Toast CSS -->
    <link href="<?php echo rtrim(APP_URL, '/'); ?>/assets/css/toast.css" rel="stylesheet">
    
    <!-- API JavaScript -->
    <script>
        // Set APP_URL for JavaScript
        window.APP_URL = '<?php echo rtrim(APP_URL, '/'); ?>';
    </script>
    <script src="<?php echo rtrim(APP_URL, '/'); ?>/assets/js/api.js"></script>
    
    <!-- Toast JavaScript -->
    <script src="<?php echo rtrim(APP_URL, '/'); ?>/assets/js/toast.js"></script>
    
    <!-- Additional CSS for specific pages -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand fw-bold" href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Dashboard&action=dashboard">
                <i class="fas fa-tasks me-2"></i>
                <?php echo APP_NAME; ?>
            </a>

            <!-- Mobile toggle buttons -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" 
                           href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Dashboard&action=dashboard">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>" 
                           href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Project&action=list">
                            <i class="fas fa-project-diagram me-1"></i>Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'tasks' ? 'active' : ''; ?>" 
                           href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Task&action=list">
                            <i class="fas fa-tasks me-1"></i>Tasks
                        </a>
                    </li>
                    <?php if (hasRole(['admin', 'project_manager'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>" 
                           href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=User&action=list">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'notifications' ? 'active' : ''; ?>" 
                           href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Notification&action=index">
                            <i class="fas fa-bell me-1"></i>Notifications
                            <?php if (getUnreadNotifications($_SESSION['user_id'] ?? 0) > 0): ?>
                                <span class="badge bg-danger notification-badge"><?php echo getUnreadNotifications($_SESSION['user_id']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (hasRole(['admin', 'project_manager'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'activity_logs' ? 'active' : ''; ?>" 
                           href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=ActivityLog&action=index">
                            <i class="fas fa-history me-1"></i>Activity Logs
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'reports' ? 'active' : ''; ?>" 
                           href="<?php echo rtrim(APP_URL, '/'); ?>/views/reports/index.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>

                <!-- Right side items -->
                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                    <?php echo $unreadNotifications; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationsDropdown">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Notification&action=index">
                                    <i class="fas fa-bell me-2"></i>View all notifications
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- User dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>
                            <span class="d-none d-md-inline">
                                <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=User&action=profile">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/views/users/change_password.php">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Auth&action=logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content wrapper -->
    <div class="main-wrapper">
        <!-- Main content -->
        <main class="main-content">
            <!-- Page header -->
            <div class="page-header bg-white border-bottom">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                            <?php if (isset($pageDescription)): ?>
                                <p class="text-muted mb-0"><?php echo $pageDescription; ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <?php if (isset($pageActions)): ?>
                                <?php echo $pageActions; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Flash messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['warning'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['warning']); unset($_SESSION['warning']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['info'])): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
