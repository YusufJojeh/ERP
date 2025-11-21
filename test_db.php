<?php
require_once 'config/config.php';

try {
    echo "Testing database connection...\n";
    
    // Test basic connection
    $sql = "SELECT COUNT(*) as count FROM users";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "Database connection successful!\n";
    echo "User count: " . $result['count'] . "\n";
    
    // Test if we can create a new User instance
    $user = new User();
    echo "User model instantiated successfully!\n";
    
    // Test if we can create a new Project instance
    $project = new Project();
    echo "Project model instantiated successfully!\n";
    
    // Test if we can create a new Task instance
    $task = new Task();
    echo "Task model instantiated successfully!\n";
    
    // Test if we can create a new ActivityLog instance
    $activityLog = new ActivityLog();
    echo "ActivityLog model instantiated successfully!\n";
    
    echo "All tests passed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
