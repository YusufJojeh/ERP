<?php
require_once 'config/config.php';

$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Updating password for admin user...\n";
echo "New hash: $hash\n";

$sql = "UPDATE users SET password = ? WHERE username = 'admin'";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$hash]);

if ($result) {
    echo "Password updated successfully!\n";
    
    // Verify the update
    $sql = "SELECT password FROM users WHERE username = 'admin'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $user = $stmt->fetch();
    
    echo "Stored hash: " . $user['password'] . "\n";
    
    if (password_verify($password, $user['password'])) {
        echo "Verification: SUCCESS\n";
    } else {
        echo "Verification: FAILED\n";
    }
} else {
    echo "Failed to update password\n";
}
?>
