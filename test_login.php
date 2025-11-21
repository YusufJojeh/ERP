<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing login process...\n";

try {
    require_once 'config/config.php';
    echo "Config loaded successfully\n";
    
    // Test user login
    $user = new User();
    echo "User model created\n";
    
    // Test login with admin user
    $result = $user->login('admin', 'password');
    echo "Login result: " . ($result ? 'Success' : 'Failed') . "\n";
    
    if ($result) {
        echo "User logged in successfully!\n";
        echo "User ID: " . $_SESSION['user_id'] . "\n";
        echo "Username: " . $_SESSION['username'] . "\n";
        echo "Role: " . $_SESSION['user_role'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
