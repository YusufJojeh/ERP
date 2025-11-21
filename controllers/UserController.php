<?php
/**
 * User Controller
 * Handles user management operations
 */

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * List all users
     */
    public function list() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        
        $users = $this->userModel->getAllUsers($page, ITEMS_PER_PAGE, $search);
        $totalUsers = $this->userModel->getTotalUsers($search);
        
        $totalPages = ceil($totalUsers / ITEMS_PER_PAGE);
        
        include 'views/users/list.php';
    }
    
    /**
     * View user profile
     */
    public function profile() {
        requireLogin();
        
        $user_id = $_GET['id'] ?? $_SESSION['user_id'];
        $user = $this->userModel->getUserById($user_id);
        
        if (!$user) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        include 'views/users/profile.php';
    }
    
    /**
     * Update user role
     */
    public function updateRole() {
        requireLogin();
        requireRole(['admin']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_POST['user_id'] ?? 0;
            $role = $_POST['role'] ?? '';
            
            $result = $this->userModel->updateRole($user_id, $role);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update role']);
            }
        }
    }
    
    /**
     * Activate user
     */
    public function activate() {
        requireLogin();
        requireRole(['admin']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_POST['user_id'] ?? 0;
            
            $result = $this->userModel->activateUser($user_id);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to activate user']);
            }
        }
    }
    
    /**
     * Deactivate user
     */
    public function deactivate() {
        requireLogin();
        requireRole(['admin']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_POST['user_id'] ?? 0;
            
            $result = $this->userModel->deactivateUser($user_id);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to deactivate user']);
            }
        }
    }
    
    /**
     * Edit user
     */
    public function edit() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $user_id = $_GET['id'] ?? 0;
        $user = $this->userModel->getUserById($user_id);
        
        if (!$user) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                    'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                    'email' => sanitizeInput($_POST['email'] ?? ''),
                    'role' => sanitizeInput($_POST['role'] ?? ''),
                    'status' => sanitizeInput($_POST['status'] ?? 'active')
                ];
                
                $result = $this->userModel->update($user_id, $data);
                
                if ($result) {
                    $success = 'User updated successfully!';
                    echo "<script>setTimeout(function(){ window.location.href = 'profile.php?id=" . $user_id . "'; }, 2000);</script>";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        include 'views/users/edit.php';
    }
    
    /**
     * Delete user
     */
    public function delete() {
        requireLogin();
        requireRole(['admin']);
        
        $user_id = $_GET['id'] ?? 0;
        $user = $this->userModel->getUserById($user_id);
        
        if (!$user) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $confirm = $_POST['confirm'] ?? '';
                
                if ($confirm !== 'DELETE') {
                    throw new Exception('Please type DELETE to confirm deletion');
                }
                
                $result = $this->userModel->delete($user_id);
                
                if ($result) {
                    $success = 'User deleted successfully!';
                    echo "<script>setTimeout(function(){ window.location.href = 'list.php'; }, 2000);</script>";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        include 'views/users/delete.php';
    }
    
    /**
     * Register new user
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $first_name = sanitizeInput($_POST['first_name'] ?? '');
                $last_name = sanitizeInput($_POST['last_name'] ?? '');
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = sanitizeInput($_POST['role'] ?? 'member');
                
                $user_id = $this->userModel->register($username, $email, $password, $first_name, $last_name);
                
                if ($user_id) {
                    // Set role if provided
                    if ($role !== 'member') {
                        $this->userModel->updateRole($user_id, $role);
                    }
                    
                    if (isset($_POST['action']) && $_POST['action'] === 'add_member') {
                        // Return JSON for AJAX requests
                        echo json_encode(['success' => true, 'user_id' => $user_id]);
                        exit();
                    } else {
                        // Redirect for form submissions
                        redirect(APP_URL . '/views/auth/login.php');
                    }
                }
            } catch (Exception $e) {
                if (isset($_POST['action']) && $_POST['action'] === 'add_member') {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit();
                } else {
                    $error = $e->getMessage();
                }
            }
        }
        
        include 'views/users/register.php';
    }
}
?>
