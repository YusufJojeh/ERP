<?php
require_once 'config/config.php';

echo "Starting ERP System...\n";

// Test basic routing
$action = $_GET['action'] ?? 'dashboard';
$controller = $_GET['controller'] ?? 'Dashboard';

echo "Controller: $controller\n";
echo "Action: $action\n";

// Test if we can create controllers
try {
    if ($controller === 'Auth') {
        $authController = new AuthController();
        echo "AuthController created successfully!\n";
    } elseif ($controller === 'Dashboard') {
        $dashboardController = new DashboardController();
        echo "DashboardController created successfully!\n";
    } elseif ($controller === 'Project') {
        $projectController = new ProjectController();
        echo "ProjectController created successfully!\n";
    } elseif ($controller === 'Task') {
        $taskController = new TaskController();
        echo "TaskController created successfully!\n";
    } elseif ($controller === 'User') {
        $userController = new UserController();
        echo "UserController created successfully!\n";
    }
} catch (Exception $e) {
    echo "Error creating controller: " . $e->getMessage() . "\n";
}

echo "System initialized successfully!\n";
?>
