<?php
/**
 * Task Model
 * Handles task management operations
 */

class Task {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new task
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO tasks (project_id, title, description, priority, status, assigned_to, created_by, due_date, estimated_hours) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['project_id'],
                $data['title'],
                $data['description'],
                $data['priority'] ?? 'medium',
                $data['status'] ?? 'pending',
                $data['assigned_to'],
                $data['created_by'],
                $data['due_date'],
                $data['estimated_hours'] ?? 0
            ]);
            
            if ($result) {
                $task_id = $this->pdo->lastInsertId();
                logActivity('created', 'task', $task_id, "Task '{$data['title']}' created");
                
                // Send notification to assigned user
                if ($data['assigned_to']) {
                    sendNotification(
                        $data['assigned_to'],
                        'New Task Assigned',
                        "You have been assigned a new task: {$data['title']}",
                        'info',
                        'task',
                        $task_id
                    );
                }
                
                return $task_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Task creation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get task by ID
     */
    public function getTaskById($id) {
        $sql = "SELECT t.*, p.name as project_name, 
                       u1.username as assigned_username, u1.first_name as assigned_first_name, u1.last_name as assigned_last_name,
                       u2.username as created_username, u2.first_name as created_first_name, u2.last_name as created_last_name
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u1 ON t.assigned_to = u1.id 
                LEFT JOIN users u2 ON t.created_by = u2.id 
                WHERE t.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all tasks with pagination and filters
     */
    public function getAllTasks($page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereClause = '';
        $params = [];
        
        $conditions = [];
        
        if (!empty($filters['project_id'])) {
            $conditions[] = "t.project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = "t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "t.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $conditions[] = "t.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['due_date_from'])) {
            $conditions[] = "t.due_date >= ?";
            $params[] = $filters['due_date_from'];
        }
        
        if (!empty($filters['due_date_to'])) {
            $conditions[] = "t.due_date <= ?";
            $params[] = $filters['due_date_to'];
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT t.*, p.name as project_name, 
                       u1.username as assigned_username, u1.first_name as assigned_first_name, u1.last_name as assigned_last_name,
                       u2.username as created_username, u2.first_name as created_first_name, u2.last_name as created_last_name
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u1 ON t.assigned_to = u1.id 
                LEFT JOIN users u2 ON t.created_by = u2.id 
                {$whereClause}
                ORDER BY t.priority DESC, t.due_date ASC, t.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total tasks count
     */
    public function getTotalTasks($filters = []) {
        $whereClause = '';
        $params = [];
        
        $conditions = [];
        
        if (!empty($filters['project_id'])) {
            $conditions[] = "project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $conditions[] = "assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(title LIKE ? OR description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['due_date_from'])) {
            $conditions[] = "due_date >= ?";
            $params[] = $filters['due_date_from'];
        }
        
        if (!empty($filters['due_date_to'])) {
            $conditions[] = "due_date <= ?";
            $params[] = $filters['due_date_to'];
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT COUNT(*) FROM tasks {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Update task
     */
    public function update($id, $data) {
        try {
            $allowedFields = ['title', 'description', 'priority', 'status', 'assigned_to', 'due_date', 'estimated_hours', 'actual_hours'];
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
            
            // Set completed_at if status is being changed to completed
            if (isset($data['status']) && $data['status'] === 'completed') {
                $updateFields[] = "completed_at = NOW()";
            } elseif (isset($data['status']) && $data['status'] !== 'completed') {
                $updateFields[] = "completed_at = NULL";
            }
            
            $params[] = $id;
            $sql = "UPDATE tasks SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                logActivity('updated', 'task', $id, "Task updated");
                
                // Send notification if assigned user changed
                if (isset($data['assigned_to']) && $data['assigned_to']) {
                    $task = $this->getTaskById($id);
                    sendNotification(
                        $data['assigned_to'],
                        'Task Assigned',
                        "You have been assigned to task: {$task['title']}",
                        'info',
                        'task',
                        $id
                    );
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Task update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete task
     */
    public function delete($id) {
        try {
            // Get task title for logging
            $task = $this->getTaskById($id);
            $taskTitle = $task['title'] ?? 'Unknown';
            
            $sql = "DELETE FROM tasks WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity('deleted', 'task', $id, "Task '{$taskTitle}' deleted");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Task deletion failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get user's tasks with pagination and filters
     */
    public function getUserTasks($user_id, $page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereClause = '';
        $params = [$user_id];
        
        // Build where clause based on filters
        $conditions = ['(t.assigned_to = ? OR t.created_by = ?)'];
        $params[] = $user_id; // Add user_id again for created_by condition
        
        if (!empty($filters['status'])) {
            $conditions[] = "t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "t.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['project_id'])) {
            $conditions[] = "t.project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['due_date_from'])) {
            $conditions[] = "t.due_date >= ?";
            $params[] = $filters['due_date_from'];
        }
        
        if (!empty($filters['due_date_to'])) {
            $conditions[] = "t.due_date <= ?";
            $params[] = $filters['due_date_to'];
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT t.*, p.name as project_name, 
                       u1.username as assigned_username, u1.first_name as assigned_first_name, u1.last_name as assigned_last_name,
                       u2.username as created_username, u2.first_name as created_first_name, u2.last_name as created_last_name
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u1 ON t.assigned_to = u1.id 
                LEFT JOIN users u2 ON t.created_by = u2.id 
                {$whereClause}
                ORDER BY t.priority DESC, t.due_date ASC, t.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user's task count with filters
     */
    public function getUserTaskCount($user_id, $filters = []) {
        $whereClause = '';
        $params = [$user_id, $user_id];
        
        $conditions = ['(assigned_to = ? OR created_by = ?)'];
        
        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['project_id'])) {
            $conditions[] = "project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(title LIKE ? OR description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['due_date_from'])) {
            $conditions[] = "due_date >= ?";
            $params[] = $filters['due_date_from'];
        }
        
        if (!empty($filters['due_date_to'])) {
            $conditions[] = "due_date <= ?";
            $params[] = $filters['due_date_to'];
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT COUNT(*) FROM tasks {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get project tasks
     */
    public function getProjectTasks($project_id, $status = null) {
        $sql = "SELECT t.*, u.username as assigned_username, u.first_name as assigned_first_name, u.last_name as assigned_last_name 
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.project_id = ?";
        
        $params = [$project_id];
        
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY t.priority DESC, t.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get task statistics
     */
    public function getTaskStats($filters = []) {
        $whereClause = '';
        $params = [];
        
        $conditions = [];
        
        if (!empty($filters['project_id'])) {
            $conditions[] = "project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $conditions[] = "assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = 'review' THEN 1 ELSE 0 END) as review_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_priority_tasks,
                    SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium_priority_tasks,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_tasks,
                    SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_tasks,
                    SUM(CASE WHEN due_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
                FROM tasks {$whereClause}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get overdue tasks
     */
    public function getOverdueTasks($user_id = null) {
        $sql = "SELECT t.*, p.name as project_name, u.username as assigned_username 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.due_date < CURDATE() AND t.status != 'completed'";
        
        $params = [];
        
        if ($user_id) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $user_id;
        }
        
        $sql .= " ORDER BY t.due_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent tasks
     */
    public function getRecentTasks($limit = 10) {
        $sql = "SELECT t.*, p.name as project_name, u.username as assigned_username 
                FROM tasks t 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.assigned_to = u.id 
                ORDER BY t.updated_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get task completion rate
     */
    public function getTaskCompletionRate($filters = []) {
        $stats = $this->getTaskStats($filters);
        
        if ($stats['total_tasks'] > 0) {
            return round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 2);
        }
        
        return 0;
    }
}
?>
