<?php
/**
 * Attachment Model
 * Handles file attachment management operations
 */

class Attachment {
    private $pdo;
    private $uploadPath;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->uploadPath = __DIR__ . '/../uploads/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * Upload file attachment
     */
    public function upload($entity_type, $entity_id, $file, $user_id) {
        try {
            // Validate file
            if (!isset($file['error']) || is_array($file['error'])) {
                throw new Exception('Invalid file upload');
            }
            
            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('No file uploaded');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('File too large');
                default:
                    throw new Exception('Unknown upload error');
            }
            
            // Check file size (max 10MB)
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception('File too large (max 10MB)');
            }
            
            // Get file info
            $originalName = $file['name'];
            $fileSize = $file['size'];
            $mimeType = $file['type'];
            $tmpName = $file['tmp_name'];
            
            // Generate unique filename
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $this->uploadPath . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($tmpName, $filePath)) {
                throw new Exception('Failed to move uploaded file');
            }
            
            // Insert into database (using task_id or project_id based on entity_type)
            if ($entity_type === 'task') {
                $sql = "INSERT INTO attachments (task_id, user_id, original_name, filename, file_path, file_size, mime_type, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $params = [$entity_id, $user_id, $originalName, $filename, $filePath, $fileSize, $mimeType];
            } elseif ($entity_type === 'project') {
                $sql = "INSERT INTO attachments (project_id, user_id, original_name, filename, file_path, file_size, mime_type, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $params = [$entity_id, $user_id, $originalName, $filename, $filePath, $fileSize, $mimeType];
            } else {
                throw new Exception("Entity type '{$entity_type}' not supported");
            }
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                $attachment_id = $this->pdo->lastInsertId();
                logActivity('uploaded', 'attachment', $attachment_id, "File '{$originalName}' uploaded");
                
                // Send notification
                $this->notifyStakeholders($entity_type, $entity_id, $user_id, "New file uploaded: {$originalName}");
                
                return $attachment_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("File upload failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get attachments for entity
     */
    public function getEntityAttachments($entity_type, $entity_id) {
        // Use task_id or project_id based on entity_type
        if ($entity_type === 'task') {
            $sql = "SELECT a.*, u.username, u.first_name, u.last_name
                    FROM attachments a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.task_id = ?
                    ORDER BY a.created_at DESC";
        } elseif ($entity_type === 'project') {
            $sql = "SELECT a.*, u.username, u.first_name, u.last_name
                    FROM attachments a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.project_id = ?
                    ORDER BY a.created_at DESC";
        } else {
            return [];
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$entity_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get task attachments (backward compatibility)
     */
    public function getTaskAttachments($task_id) {
        return $this->getEntityAttachments('task', $task_id);
    }
    
    /**
     * Get attachment by ID
     */
    public function getAttachmentById($id) {
        $sql = "SELECT a.*, u.username, u.first_name, u.last_name
                FROM attachments a
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Delete attachment
     */
    public function delete($id, $user_id = null) {
        try {
            // Get attachment info
            $attachment = $this->getAttachmentById($id);
            if (!$attachment) {
                throw new Exception('Attachment not found');
            }
            
            // Check permissions
            if ($user_id && $attachment['user_id'] != $user_id && !hasRole(['admin', 'project_manager'])) {
                throw new Exception('Permission denied');
            }
            
            // Delete file from filesystem
            if (file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }
            
            // Delete from database
            $sql = "DELETE FROM attachments WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                logActivity('deleted', 'attachment', $id, "File '{$attachment['original_name']}' deleted");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Attachment deletion failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get attachment count for entity
     */
    public function getEntityAttachmentCount($entity_type, $entity_id) {
        // Use task_id or project_id based on entity_type
        if ($entity_type === 'task') {
            $sql = "SELECT COUNT(*) FROM attachments WHERE task_id = ?";
        } elseif ($entity_type === 'project') {
            $sql = "SELECT COUNT(*) FROM attachments WHERE project_id = ?";
        } else {
            return 0;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$entity_id]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get recent attachments
     */
    public function getRecentAttachments($limit = 10) {
        $sql = "SELECT a.*, u.username, u.first_name, u.last_name,
                       CASE 
                           WHEN a.task_id IS NOT NULL THEN t.title
                           WHEN a.project_id IS NOT NULL THEN p.name
                           ELSE 'Unknown'
                       END as entity_title,
                       CASE 
                           WHEN a.task_id IS NOT NULL THEN 'task'
                           WHEN a.project_id IS NOT NULL THEN 'project'
                           ELSE 'unknown'
                       END as entity_type,
                       COALESCE(a.task_id, a.project_id) as entity_id
                FROM attachments a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN tasks t ON a.task_id = t.id
                LEFT JOIN projects p ON a.project_id = p.id
                ORDER BY a.created_at DESC
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get file icon based on MIME type
     */
    public function getFileIcon($mimeType) {
        $icons = [
            'image/' => 'fas fa-image',
            'video/' => 'fas fa-video',
            'audio/' => 'fas fa-music',
            'application/pdf' => 'fas fa-file-pdf',
            'application/msword' => 'fas fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fas fa-file-word',
            'application/vnd.ms-excel' => 'fas fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fas fa-file-excel',
            'application/vnd.ms-powerpoint' => 'fas fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fas fa-file-powerpoint',
            'text/' => 'fas fa-file-alt',
            'application/zip' => 'fas fa-file-archive',
            'application/x-rar' => 'fas fa-file-archive',
        ];
        
        foreach ($icons as $mime => $icon) {
            if (strpos($mimeType, $mime) === 0) {
                return $icon;
            }
        }
        
        return 'fas fa-file';
    }
    
    /**
     * Notify stakeholders about attachment
     */
    private function notifyStakeholders($entity_type, $entity_id, $uploader_id, $message) {
        try {
            if ($entity_type === 'task') {
                $this->notifyTaskStakeholders($entity_id, $uploader_id, $message);
            } elseif ($entity_type === 'project') {
                $this->notifyProjectStakeholders($entity_id, $uploader_id, $message);
            }
        } catch (Exception $e) {
            error_log("Failed to notify stakeholders: " . $e->getMessage());
        }
    }
    
    /**
     * Notify task stakeholders about attachment
     */
    private function notifyTaskStakeholders($task_id, $uploader_id, $message) {
        try {
            // Get task details
            $sql = "SELECT assigned_to, created_by FROM tasks WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            
            // Notify assigned user
            if ($task['assigned_to'] && $task['assigned_to'] != $uploader_id) {
                sendNotification(
                    $task['assigned_to'],
                    'New Attachment',
                    $message,
                    'info',
                    'task',
                    $task_id
                );
            }
            
            // Notify task creator
            if ($task['created_by'] && $task['created_by'] != $uploader_id && $task['created_by'] != $task['assigned_to']) {
                sendNotification(
                    $task['created_by'],
                    'New Attachment',
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
     * Notify project stakeholders about attachment
     */
    private function notifyProjectStakeholders($project_id, $uploader_id, $message) {
        try {
            // Get project members
            $sql = "SELECT user_id FROM project_members WHERE project_id = ? AND user_id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$project_id, $uploader_id]);
            $members = $stmt->fetchAll();
            
            foreach ($members as $member) {
                sendNotification(
                    $member['user_id'],
                    'New Attachment',
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
