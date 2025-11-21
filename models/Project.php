<?php
/**
 * Project Model
 * Handles project management operations
 */

class Project {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new project
     */
    public function create($data) {
        try {
            // Validate manager_id is required
            if (empty($data['manager_id']) || $data['manager_id'] <= 0) {
                throw new Exception('Project manager is required. Every project must have a project manager assigned.');
            }
            
            $sql = "INSERT INTO projects (name, description, start_date, end_date, status, priority, manager_id, budget, progress) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                $data['description'],
                $data['start_date'],
                $data['end_date'],
                $data['status'] ?? 'planning',
                $data['priority'] ?? 'medium',
                $data['manager_id'],
                $data['budget'] ?? 0,
                $data['progress'] ?? 0
            ]);
            
            if ($result) {
                $project_id = $this->pdo->lastInsertId();
                
                // Add manager as project member
                if (!empty($data['manager_id'])) {
                    $this->addMember($project_id, $data['manager_id'], 'manager');
                }
                
                logActivity('created', 'project', $project_id, "Project '{$data['name']}' created");
                return $project_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Project creation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get project by ID
     */
    public function getProjectById($id) {
        $sql = "SELECT p.*, u.username as manager_name, u.first_name, u.last_name 
                FROM projects p 
                LEFT JOIN users u ON p.manager_id = u.id 
                WHERE p.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all projects with pagination and filters
     */
    public function getAllProjects($page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereClause = '';
        $params = [];
        
        // Build where clause based on filters
        $conditions = [];
        
        if (!empty($filters['status'])) {
            $conditions[] = "p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "p.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['manager_id'])) {
            $conditions[] = "p.manager_id = ?";
            $params[] = $filters['manager_id'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT p.*, u.username as manager_name, u.first_name, u.last_name,
                       (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as task_count,
                       (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as completed_tasks
                FROM projects p 
                LEFT JOIN users u ON p.manager_id = u.id 
                {$whereClause}
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total projects count
     */
    public function getTotalProjects($filters = []) {
        $whereClause = '';
        $params = [];
        
        $conditions = [];
        
        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['manager_id'])) {
            $conditions[] = "manager_id = ?";
            $params[] = $filters['manager_id'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(name LIKE ? OR description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT COUNT(*) FROM projects {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Update project
     */
    public function update($id, $data) {
        try {
            // Validate manager_id if it's being updated
            if (isset($data['manager_id'])) {
                if (empty($data['manager_id']) || $data['manager_id'] <= 0) {
                    throw new Exception('Project manager is required. Every project must have a project manager assigned.');
                }
            }
            
            $allowedFields = ['name', 'description', 'start_date', 'end_date', 'status', 'priority', 'manager_id', 'budget', 'progress'];
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
            
            $params[] = $id;
            $sql = "UPDATE projects SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                logActivity('updated', 'project', $id, "Project updated");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Project update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete project
     */
    public function delete($id) {
        try {
            // Get project name for logging
            $project = $this->getProjectById($id);
            $projectName = $project['name'] ?? 'Unknown';
            
            $sql = "DELETE FROM projects WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity('deleted', 'project', $id, "Project '{$projectName}' deleted");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Project deletion failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add member to project
     */
    public function addMember($project_id, $user_id, $role = 'developer') {
        try {
            $sql = "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE role = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$project_id, $user_id, $role, $role]);
            
            if ($result) {
                logActivity('member_added', 'project', $project_id, "User added to project");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Add member failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Remove member from project
     */
    public function removeMember($project_id, $user_id) {
        try {
            $sql = "DELETE FROM project_members WHERE project_id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$project_id, $user_id]);
            
            if ($result) {
                logActivity('member_removed', 'project', $project_id, "User removed from project");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Remove member failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get project members
     */
    public function getProjectMembers($project_id) {
        $sql = "SELECT pm.*, u.username, u.email, u.first_name, u.last_name, u.avatar 
                FROM project_members pm 
                JOIN users u ON pm.user_id = u.id 
                WHERE pm.project_id = ? 
                ORDER BY pm.role, u.first_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$project_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user's projects with pagination and filters
     */
    public function getUserProjects($user_id, $page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereClause = '';
        $params = [$user_id];
        
        // Build where clause based on filters
        $conditions = ['pm.user_id = ?'];
        
        if (!empty($filters['status'])) {
            $conditions[] = "p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "p.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT p.*, pm.role as user_role, u.username as manager_name, u.first_name, u.last_name,
                       (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as task_count,
                       (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as completed_tasks
                FROM projects p 
                JOIN project_members pm ON p.id = pm.project_id 
                LEFT JOIN users u ON p.manager_id = u.id 
                {$whereClause}
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user's project count with filters
     */
    public function getUserProjectCount($user_id, $filters = []) {
        $whereClause = '';
        $params = [$user_id];
        
        $conditions = ['pm.user_id = ?'];
        
        if (!empty($filters['status'])) {
            $conditions[] = "p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "p.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT COUNT(*) 
                FROM projects p 
                JOIN project_members pm ON p.id = pm.project_id 
                {$whereClause}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get project statistics
     */
    public function getProjectStats($project_id) {
        $sql = "SELECT 
                    COUNT(t.id) as total_tasks,
                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN t.priority = 'critical' THEN 1 ELSE 0 END) as critical_tasks,
                    SUM(CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
                FROM tasks t 
                WHERE t.project_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$project_id]);
        return $stmt->fetch();
    }
    
    /**
     * Get project progress
     */
    public function getProjectProgress($project_id) {
        $stats = $this->getProjectStats($project_id);
        
        if ($stats['total_tasks'] > 0) {
            return round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 2);
        }
        
        return 0;
    }
    
    /**
     * Get all project statistics for dashboard
     */
    public function getAllProjectStats() {
        $sql = "SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
                    SUM(CASE WHEN status = 'on_hold' THEN 1 ELSE 0 END) as on_hold_projects,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_projects,
                    SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_projects,
                    AVG(progress) as average_progress
                FROM projects";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get recent projects
     */
    public function getRecentProjects($limit = 5) {
        $sql = "SELECT p.*, u.username as manager_name 
                FROM projects p 
                LEFT JOIN users u ON p.manager_id = u.id 
                ORDER BY p.updated_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
}
?>
