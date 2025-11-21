<?php
/**
 * Authentication Controller
 * Handles user login, registration, and session management
 */

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Handle user login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = sanitizeInput($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    throw new Exception('Username and password are required');
                }
                
                $user = $this->userModel->login($username, $password);
                
                if ($user) {
                    // Log activity
                    logActivity('logged_in', 'user', $user['id'], 'User logged in');
                    
                    // Redirect based on role
                    $redirectUrl = $this->getRedirectUrl($user['role']);
                    redirectWithSuccess($redirectUrl, 'Welcome Back!', 'You have successfully logged in.');
                } else {
                    throw new Exception('Invalid username or password');
                }
            } catch (Exception $e) {
                logError('Login failed: ' . $e->getMessage(), ['username' => $username ?? '']);
                setErrorMessage('Login Failed', $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        // Show login form
        include 'views/auth/login.php';
    }
    
    /**
     * Handle user registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                
                // Validate input
                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception('Username, email, and password are required');
                }
                
                if ($password !== $confirm_password) {
                    throw new Exception('Passwords do not match');
                }
                
                if (strlen($password) < PASSWORD_MIN_LENGTH) {
                    throw new Exception('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format');
                }
                
                $user_id = $this->userModel->register($username, $email, $password, $first_name, $last_name);
                
                if ($user_id) {
                    logActivity('registered', 'user', $user_id, 'New user registered');
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=Auth&action=login',
                        'Registration Successful!',
                        'Your account has been created. Please login to continue.'
                    );
                }
            } catch (Exception $e) {
                logError('Registration failed: ' . $e->getMessage(), ['username' => $username ?? '', 'email' => $email ?? '']);
                setErrorMessage('Registration Failed', $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        // Show registration form
        include 'views/auth/register.php';
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        if (isLoggedIn()) {
            logActivity('logged_out', 'user', $_SESSION['user_id'], 'User logged out');
        }
        $this->userModel->logout();
        redirectWithInfo(APP_URL . '/index.php', 'Logged Out', 'You have been successfully logged out.');
    }
    
    /**
     * Check if user is logged in
     */
    public function checkAuth() {
        if (!isLoggedIn()) {
            redirect(APP_URL . '/index.php?controller=Auth&action=login');
        }
    }
    
    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl($role) {
        switch ($role) {
            case 'admin':
                return APP_URL . '/index.php?controller=Dashboard&action=dashboard';
            case 'project_manager':
                return APP_URL . '/index.php?controller=Dashboard&action=dashboard';
            case 'member':
                return APP_URL . '/index.php?controller=Dashboard&action=dashboard';
            default:
                return APP_URL . '/index.php?controller=Dashboard&action=dashboard';
        }
    }
    
    /**
     * Handle password change
     */
    public function changePassword() {
        if (!isLoggedIn()) {
            redirect(APP_URL . '/index.php?controller=Auth&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password)) {
                    throw new Exception('All password fields are required');
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception('New passwords do not match');
                }
                
                if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
                    throw new Exception('New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long');
                }
                
                $result = $this->userModel->changePassword($_SESSION['user_id'], $current_password, $new_password);
                
                if ($result) {
                    logActivity('password_changed', 'user', $_SESSION['user_id'], 'Password changed');
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=User&action=profile',
                        'Password Changed',
                        'Your password has been successfully changed.'
                    );
                }
            } catch (Exception $e) {
                logError('Password change failed: ' . $e->getMessage(), ['user_id' => $_SESSION['user_id']]);
                setErrorMessage('Password Change Failed', $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        include 'views/users/change_password.php';
    }
    
    /**
     * Handle profile update
     */
    public function updateProfile() {
        if (!isLoggedIn()) {
            redirect(APP_URL . '/index.php?controller=Auth&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                    'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                    'email' => sanitizeInput($_POST['email'] ?? '')
                ];
                
                $result = $this->userModel->updateProfile($_SESSION['user_id'], $data);
                
                if ($result) {
                    // Update session data
                    $_SESSION['first_name'] = $data['first_name'];
                    $_SESSION['last_name'] = $data['last_name'];
                    $_SESSION['email'] = $data['email'];
                    
                    logActivity('profile_updated', 'user', $_SESSION['user_id'], 'Profile updated');
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=User&action=profile',
                        'Profile Updated',
                        'Your profile has been successfully updated.'
                    );
                }
            } catch (Exception $e) {
                logError('Profile update failed: ' . $e->getMessage(), ['user_id' => $_SESSION['user_id']]);
                setErrorMessage('Profile Update Failed', $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        // Get current user data
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        include 'views/users/profile.php';
    }
}
?>
