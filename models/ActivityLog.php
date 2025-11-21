<?php
/**
 * Activity Log Model
 * Handles activity logging and tracking
 */

class ActivityLog {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Get activity logs with pagination and filters
     */
    public function getActivityLogs($page = 1, $limit = ITEMS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereClause = '';
        $params = [];
        
        $conditions = [];
        
        if (!empty($filters['user_id'])) {
            $conditions[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $conditions[] = "al.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $conditions[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT al.*, u.username, u.first_name, u.last_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                {$whereClause}
                ORDER BY al.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results ? $results : [];
        } catch (PDOException $e) {
            error_log("Error fetching activity logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total activity logs count
     */
    public function getTotalActivityLogs($filters = []) {
        $whereClause = '';
        $params = [];
        
        $conditions = [];
        
        if (!empty($filters['user_id'])) {
            $conditions[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $conditions[] = "action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $conditions[] = "entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        try {
            $sql = "SELECT COUNT(*) FROM activity_logs {$whereClause}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            return $count ? (int)$count : 0;
        } catch (PDOException $e) {
            error_log("Error counting activity logs: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 20) {
        $sql = "SELECT al.*, u.username, u.first_name, u.last_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user activities
     */
    public function getUserActivities($user_id, $limit = 10) {
        try {
            $sql = "SELECT al.*, u.username, u.first_name, u.last_name 
                    FROM activity_logs al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    WHERE al.user_id = ? 
                    ORDER BY al.created_at DESC 
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results ? $results : [];
        } catch (PDOException $e) {
            error_log("Error fetching user activities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get entity activities
     */
    public function getEntityActivities($entity_type, $entity_id, $limit = 10) {
        try {
            $sql = "SELECT al.*, u.username, u.first_name, u.last_name 
                    FROM activity_logs al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    WHERE al.entity_type = ? AND al.entity_id = ? 
                    ORDER BY al.created_at DESC 
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$entity_type, $entity_id, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results ? $results : [];
        } catch (PDOException $e) {
            error_log("Error fetching entity activities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats($filters = []) {
        $whereClause = '';
        $params = [];
        
        $conditions = [];
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(' AND ', $conditions);
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_activities,
                    SUM(CASE WHEN action = 'created' THEN 1 ELSE 0 END) as created_count,
                    SUM(CASE WHEN action = 'updated' THEN 1 ELSE 0 END) as updated_count,
                    SUM(CASE WHEN action = 'deleted' THEN 1 ELSE 0 END) as deleted_count,
                    SUM(CASE WHEN entity_type = 'project' THEN 1 ELSE 0 END) as project_activities,
                    SUM(CASE WHEN entity_type = 'task' THEN 1 ELSE 0 END) as task_activities,
                    SUM(CASE WHEN entity_type = 'user' THEN 1 ELSE 0 END) as user_activities
                FROM activity_logs {$whereClause}";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : [
                'total_activities' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'deleted_count' => 0,
                'project_activities' => 0,
                'task_activities' => 0,
                'user_activities' => 0
            ];
        } catch (PDOException $e) {
            error_log("Error fetching activity stats: " . $e->getMessage());
            return [
                'total_activities' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'deleted_count' => 0,
                'project_activities' => 0,
                'task_activities' => 0,
                'user_activities' => 0
            ];
        }
    }
    
    /**
     * Get daily activity counts
     */
    public function getDailyActivityCounts($days = 30) {
        $sql = "SELECT 
                    DATE(created_at) as activity_date,
                    COUNT(*) as count
                FROM activity_logs 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY activity_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get most active users
     */
    public function getMostActiveUsers($limit = 10) {
        try {
            $sql = "SELECT 
                        u.username, u.first_name, u.last_name,
                        COUNT(al.id) as activity_count
                    FROM activity_logs al 
                    JOIN users u ON al.user_id = u.id 
                    GROUP BY al.user_id, u.username, u.first_name, u.last_name
                    ORDER BY activity_count DESC 
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results ? $results : [];
        } catch (PDOException $e) {
            error_log("Error fetching most active users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old activity logs
     */
    public function cleanOldLogs($days = 365) {
        try {
            $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$days]);
            
            if ($result) {
                logActivity('cleanup', 'system', 0, "Cleaned old activity logs older than {$days} days");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to clean old activity logs: " . $e->getMessage());
            return false;
        }
    }
}
?>
