<?php
/**
 * User Model
 * Handles user authentication, registration, and user management
 */

class User {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Register a new user
     */
    public function register($username, $email, $password, $first_name = '', $last_name = '') {
        try {
            // Validate input
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception('All fields are required');
            }
            
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                throw new Exception('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Check if username or email already exists
            if ($this->usernameExists($username)) {
                throw new Exception('Username already exists');
            }
            
            if ($this->emailExists($email)) {
                throw new Exception('Email already exists');
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password, first_name, last_name, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$username, $email, $hashedPassword, $first_name, $last_name]);
            
            if ($result) {
                $user_id = $this->pdo->lastInsertId();
                logActivity('registered', 'user', $user_id, 'User registered successfully');
                return $user_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("User registration failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Authenticate user login
     */
    public function login($username, $password) {
        try {
            // Get user by username or email
            $sql = "SELECT id, username, email, password, role, first_name, last_name, is_active 
                    FROM users 
                    WHERE (username = ? OR email = ?) AND is_active = 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('Invalid credentials');
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                throw new Exception('Invalid credentials');
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['login_time'] = time();
            
            // Log activity
            logActivity('login', 'user', $user['id'], 'User logged in successfully');
            
            return $user;
        } catch (Exception $e) {
            error_log("User login failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity('logout', 'user', $_SESSION['user_id'], 'User logged out');
        }
        
        // Destroy session
        session_destroy();
        return true;
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $sql = "SELECT id, username, email, role, first_name, last_name, avatar, is_active, created_at 
                FROM users WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all users with pagination
     */
    public function getAllUsers($page = 1, $limit = ITEMS_PER_PAGE, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        $sql = "SELECT id, username, email, role, first_name, last_name, avatar, is_active, created_at 
                FROM users {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total users count
     */
    public function getTotalUsers($search = '') {
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        $sql = "SELECT COUNT(*) FROM users {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($id, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'email'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception('No valid fields to update');
            }
            
            // Check email uniqueness if email is being updated
            if (isset($data['email'])) {
                $sql = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$data['email'], $id]);
                
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Email already exists');
                }
            }
            
            $params[] = $id;
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                logActivity('updated', 'user', $id, 'User profile updated');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Profile update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($id, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                throw new Exception('New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long');
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$hashedPassword, $id]);
            
            if ($result) {
                logActivity('password_changed', 'user', $id, 'Password changed successfully');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Password change failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update user role
     */
    public function updateRole($id, $role) {
        try {
            $allowedRoles = ['admin', 'project_manager', 'member'];
            
            if (!in_array($role, $allowedRoles)) {
                throw new Exception('Invalid role');
            }
            
            $sql = "UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$role, $id]);
            
            if ($result) {
                logActivity('role_updated', 'user', $id, "User role changed to {$role}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Role update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Deactivate user
     */
    public function deactivateUser($id) {
        try {
            $sql = "UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity('deactivated', 'user', $id, 'User account deactivated');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("User deactivation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Activate user
     */
    public function activateUser($id) {
        try {
            $sql = "UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity('activated', 'user', $id, 'User account activated');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("User activation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole($role) {
        $sql = "SELECT id, username, email, first_name, last_name, avatar 
                FROM users 
                WHERE role = ? AND is_active = 1 
                ORDER BY first_name, last_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats() {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                    SUM(CASE WHEN role = 'project_manager' THEN 1 ELSE 0 END) as manager_count,
                    SUM(CASE WHEN role = 'member' THEN 1 ELSE 0 END) as member_count
                FROM users";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'email', 'role', 'status'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception('No valid fields to update');
            }
            
            // Check email uniqueness if email is being updated
            if (isset($data['email'])) {
                $sql = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$data['email'], $id]);
                
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Email already exists');
                }
            }
            
            $params[] = $id;
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                logActivity('updated', 'user', $id, 'User updated');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("User update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete user
     */
    public function delete($id) {
        try {
            // Get user name for logging
            $user = $this->getUserById($id);
            $userName = $user['first_name'] . ' ' . $user['last_name'] ?? 'Unknown';
            
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity('deleted', 'user', $id, "User '{$userName}' deleted");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("User deletion failed: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
