<?php
require_once 'config/config.php';

$sql = "SELECT id, username, email, password FROM users WHERE username = 'admin'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    echo "Admin user found:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Password hash: " . $user['password'] . "\n";
    
    // Test password verification
    if (password_verify('password', $user['password'])) {
        echo "Password verification: SUCCESS\n";
    } else {
        echo "Password verification: FAILED\n";
    }
} else {
    echo "Admin user not found\n";
}
?>
