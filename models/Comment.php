<?php
/**
 * Comment Model
 * Handles task comments and discussions
 */

class Comment {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Add comment to entity (task, project, etc.)
     */
    public function addComment($entity_type, $entity_id, $user_id, $comment, $is_internal = false) {
        try {
            // For now, only support tasks (backward compatibility with current DB structure)
            if ($entity_type === 'task') {
                $sql = "INSERT INTO comments (task_id, user_id, comment, is_internal, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([$entity_id, $user_id, $comment, $is_internal ? 1 : 0]);
            } else {
                // For projects and other entities, we need to update the DB structure first
                throw new Exception("Entity type '{$entity_type}' not yet supported. Please update database schema.");
            }
            
            if ($result) {
                $comment_id = $this->pdo->lastInsertId();
                logActivity('commented', $entity_type, $entity_id, 'Comment added');
                
                // Send notification to relevant stakeholders
                $this->notifyStakeholders($entity_type, $entity_id, $user_id, 'New comment added');
                
                return $comment_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Comment creation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add comment to task (backward compatibility)
     */
    public function addTaskComment($task_id, $user_id, $comment, $is_internal = false) {
        return $this->addComment('task', $task_id, $user_id, $comment, $is_internal);
    }
    
    /**
     * Get comments for an entity
     */
    public function getCommentsByEntity($entity_type, $entity_id) {
        // For now, only support tasks (backward compatibility with current DB structure)
        if ($entity_type === 'task') {
            $sql = "SELECT c.*, c.comment as content, u.username, u.first_name, u.last_name, u.avatar 
                    FROM comments c 
                    JOIN users u ON c.user_id = u.id 
                    WHERE c.task_id = ? 
                    ORDER BY c.created_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$entity_id]);
            return $stmt->fetchAll();
        } else {
            // For projects and other entities, return empty array for now
            return [];
        }
    }
    
    /**
     * Get comments for a task (backward compatibility)
     */
    public function getTaskComments($task_id) {
        return $this->getCommentsByEntity('task', $task_id);
    }
    
    /**
     * Get comment by ID
     */
    public function getCommentById($id) {
        $sql = "SELECT c.*, c.comment as content, u.username, u.first_name, u.last_name, u.avatar 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Update comment
     */
    public function updateComment($id, $comment) {
        try {
            $sql = "UPDATE comments SET comment = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$comment, $id]);
            
            if ($result) {
                logActivity('updated', 'comment', $id, 'Comment updated');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Comment update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete comment
     */
    public function deleteComment($id) {
        try {
            $sql = "DELETE FROM comments WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity('deleted', 'comment', $id, 'Comment deleted');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Comment deletion failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get recent comments
     */
    public function getRecentComments($limit = 10) {
        $sql = "SELECT c.*, c.comment as content, u.username, u.first_name, u.last_name, u.avatar, 
                       t.title as entity_title, 'task' as entity_type, c.task_id as entity_id
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                LEFT JOIN tasks t ON c.task_id = t.id
                ORDER BY c.created_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get comment count for entity
     */
    public function getEntityCommentCount($entity_type, $entity_id) {
        // For now, only support tasks (backward compatibility with current DB structure)
        if ($entity_type === 'task') {
            $sql = "SELECT COUNT(*) FROM comments WHERE task_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$entity_id]);
            return $stmt->fetchColumn();
        } else {
            return 0;
        }
    }
    
    /**
     * Get comment count for task (backward compatibility)
     */
    public function getTaskCommentCount($task_id) {
        return $this->getEntityCommentCount('task', $task_id);
    }
    
    /**
     * Notify stakeholders about comment
     */
    private function notifyStakeholders($entity_type, $entity_id, $commenter_id, $message) {
        try {
            if ($entity_type === 'task') {
                $this->notifyTaskStakeholders($entity_id, $commenter_id, $message);
            } elseif ($entity_type === 'project') {
                $this->notifyProjectStakeholders($entity_id, $commenter_id, $message);
            }
        } catch (Exception $e) {
            error_log("Failed to notify stakeholders: " . $e->getMessage());
        }
    }
    
    /**
     * Notify task stakeholders about comment
     */
    private function notifyTaskStakeholders($task_id, $commenter_id, $message) {
        try {
            // Get task details
            $sql = "SELECT assigned_to, created_by FROM tasks WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            
            // Notify assigned user
            if ($task['assigned_to'] && $task['assigned_to'] != $commenter_id) {
                sendNotification(
                    $task['assigned_to'],
                    'New Comment',
                    $message,
                    'info',
                    'task',
                    $task_id
                );
            }
            
            // Notify task creator
            if ($task['created_by'] && $task['created_by'] != $commenter_id && $task['created_by'] != $task['assigned_to']) {
                sendNotification(
                    $task['created_by'],
                    'New Comment',
                    $message,
                    'info',
                    'task',
                    $task_id
                );
            }
        } catch (Exception $e) {
            error_log("Failed to notify task stakeholders: " . $e->getMessage());
        }
    }
    
    /**
     * Notify project stakeholders about comment
     */
    private function notifyProjectStakeholders($project_id, $commenter_id, $message) {
        try {
            // Get project members
            $sql = "SELECT user_id FROM project_members WHERE project_id = ? AND user_id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$project_id, $commenter_id]);
            $members = $stmt->fetchAll();
            
            foreach ($members as $member) {
                sendNotification(
                    $member['user_id'],
                    'New Comment',
                    $message,
                    'info',
                    'project',
                    $project_id
                );
            }
        } catch (Exception $e) {
            error_log("Failed to notify project stakeholders: " . $e->getMessage());
        }
    }
}
?>
