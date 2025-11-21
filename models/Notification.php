<?php
/**
 * Notification Model
 * Handles notification management operations
 */

class Notification {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new notification
     */
    public function create($user_id, $title, $message, $type = 'info', $entity_type = null, $entity_id = null) {
        try {
            $sql = "INSERT INTO notifications (user_id, title, message, type, entity_type, entity_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$user_id, $title, $message, $type, $entity_type, $entity_id]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$user_id];
        
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id = null) {
        try {
            $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?";
            $params = [$notification_id];
            
            if ($user_id) {
                $sql .= " AND user_id = ?";
                $params[] = $user_id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Failed to mark notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($user_id) {
        try {
            $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("Failed to mark all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete notification
     */
    public function delete($notification_id, $user_id = null) {
        try {
            $sql = "DELETE FROM notifications WHERE id = ?";
            $params = [$notification_id];
            
            if ($user_id) {
                $sql .= " AND user_id = ?";
                $params[] = $user_id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Failed to delete notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($user_id) {
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Clean old notifications
     */
    public function cleanOldNotifications($days = 30) {
        try {
            $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$days]);
        } catch (Exception $e) {
            error_log("Failed to clean old notifications: " . $e->getMessage());
            return false;
        }
    }
}
?>
