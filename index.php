<?php
/**
 * Main Entry Point
 * Advanced Project & Task Management System
 */

require_once 'config/config.php';

// Handle landing page - only show if no controller/action specified and user is not logged in
if (!isset($_GET['controller']) && !isset($_GET['action'])) {
    if (!isLoggedIn()) {
        // Show landing page for non-logged-in users
        $pageTitle = 'Welcome';
        include __DIR__ . '/views/landing/index.php';
        exit;
    } else {
        // Redirect logged-in users to dashboard
        redirect(APP_URL . '/index.php?controller=Dashboard&action=dashboard');
    }
}

// Handle routing
$action = $_GET['action'] ?? 'dashboard';
$controller = $_GET['controller'] ?? 'Dashboard';

// Route to appropriate controller
switch ($controller) {
    case 'Auth':
        $authController = new AuthController();
        switch ($action) {
            case 'login':
                $authController->login();
                break;
            case 'register':
                $authController->register();
                break;
            case 'logout':
                $authController->logout();
                break;
            case 'changePassword':
                $authController->changePassword();
                break;
            case 'updateProfile':
                $authController->updateProfile();
                break;
            default:
                $authController->login();
        }
        break;
        
    case 'Dashboard':
        $dashboardController = new DashboardController();
        switch ($action) {
            case 'getDashboardData':
                $dashboardController->getDashboardData();
                break;
            case 'exportData':
                $dashboardController->exportData();
                break;
            default:
                $dashboardController->index();
        }
        break;
        
    case 'Project':
        require_once 'controllers/ProjectController.php';
        $projectController = new ProjectController();
        switch ($action) {
            case 'list':
                $projectController->list();
                break;
            case 'create':
                $projectController->create();
                break;
            case 'view':
                $projectController->view();
                break;
            case 'edit':
                $projectController->edit();
                break;
            case 'delete':
                $projectController->delete();
                break;
            case 'addMember':
                $projectController->addMember();
                break;
            case 'removeMember':
                $projectController->removeMember();
                break;
            default:
                $projectController->list();
        }
        break;
        
    case 'Task':
        require_once 'controllers/TaskController.php';
        $taskController = new TaskController();
        switch ($action) {
            case 'list':
                $taskController->list();
                break;
            case 'create':
                $taskController->create();
                break;
            case 'view':
                $taskController->view();
                break;
            case 'edit':
                $taskController->edit();
                break;
            case 'delete':
                $taskController->delete();
                break;
            case 'updateStatus':
                $taskController->updateStatus();
                break;
            default:
                $taskController->list();
        }
        break;
        
    case 'User':
        require_once 'controllers/UserController.php';
        $userController = new UserController();
        switch ($action) {
            case 'list':
                $userController->list();
                break;
            case 'profile':
                $userController->profile();
                break;
            case 'updateRole':
                $userController->updateRole();
                break;
            case 'activate':
                $userController->activate();
                break;
            case 'deactivate':
                $userController->deactivate();
                break;
            default:
                $userController->list();
        }
        break;
        
    case 'Notification':
        require_once 'controllers/NotificationController.php';
        $notificationController = new NotificationController();
        switch ($action) {
            case 'index':
                $notificationController->index();
                break;
            case 'markAsRead':
                $notificationController->markAsRead();
                break;
            case 'markAllAsRead':
                $notificationController->markAllAsRead();
                break;
            case 'delete':
                $notificationController->delete();
                break;
            case 'getUnreadCount':
                $notificationController->getUnreadCount();
                break;
            default:
                $notificationController->index();
        }
        break;
        
    case 'ActivityLog':
        require_once 'controllers/ActivityLogController.php';
        $activityLogController = new ActivityLogController();
        switch ($action) {
            case 'index':
                $activityLogController->index();
                break;
            case 'getRecent':
                $activityLogController->getRecent();
                break;
            case 'getUserActivities':
                $activityLogController->getUserActivities();
                break;
            case 'getEntityActivities':
                $activityLogController->getEntityActivities();
                break;
            case 'getStats':
                $activityLogController->getStats();
                break;
            case 'clean':
                $activityLogController->clean();
                break;
            default:
                $activityLogController->index();
        }
        break;
        
    default:
        // Default to dashboard
        $dashboardController = new DashboardController();
        $dashboardController->index();
}
?>
