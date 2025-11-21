<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting minimal test...\n";

try {
    require_once 'config/config.php';
    echo "Config loaded successfully\n";
    
    $user = new User();
    echo "User model created\n";
    
    $project = new Project();
    echo "Project model created\n";
    
    $task = new Task();
    echo "Task model created\n";
    
    $activityLog = new ActivityLog();
    echo "ActivityLog model created\n";
    
    $dashboardController = new DashboardController();
    echo "DashboardController created\n";
    
    echo "All models and controllers created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
