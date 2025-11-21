<?php
/**
 * Attachment Controller
 * Handles file attachment management operations
 */

class AttachmentController {
    private $attachmentModel;
    
    public function __construct() {
        $this->attachmentModel = new Attachment();
    }
    
    /**
     * Upload file
     */
    public function upload() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $entity_type = sanitizeInput($_POST['entity_type'] ?? '');
                $entity_id = (int)($_POST['entity_id'] ?? 0);
                
                if (empty($entity_type) || $entity_id <= 0) {
                    throw new Exception('Invalid entity parameters');
                }
                
                if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No file uploaded or upload error');
                }
                
                $user_id = $_SESSION['user_id'];
                $attachment_id = $this->attachmentModel->upload($entity_type, $entity_id, $_FILES['file'], $user_id);
                
                if ($attachment_id) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'attachment_id' => $attachment_id]);
                    } else {
                        // Redirect back to the entity
                        $redirect_url = $this->getEntityUrl($entity_type, $entity_id);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to upload file');
                }
            } catch (Exception $e) {
                if (isset($_POST['format']) && $_POST['format'] === 'json') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                } else {
                    $_SESSION['error'] = $e->getMessage();
                    $redirect_url = $this->getEntityUrl($_POST['entity_type'] ?? '', $_POST['entity_id'] ?? 0);
                    redirect($redirect_url);
                }
            }
        }
    }
    
    /**
     * Download file
     */
    public function download() {
        requireLogin();
        
        $attachment_id = (int)($_GET['id'] ?? 0);
        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
        
        if (!$attachment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if file exists
        if (!file_exists($attachment['file_path'])) {
            header('HTTP/1.1 404 Not Found');
            echo 'File not found on server';
            exit();
        }
        
        // Set headers for file download
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Output file
        readfile($attachment['file_path']);
        exit();
    }
    
    /**
     * View file
     */
    public function view() {
        requireLogin();
        
        $attachment_id = (int)($_GET['id'] ?? 0);
        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
        
        if (!$attachment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if file exists
        if (!file_exists($attachment['file_path'])) {
            header('HTTP/1.1 404 Not Found');
            echo 'File not found on server';
            exit();
        }
        
        // Set headers for file viewing
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: inline; filename="' . $attachment['original_name'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Output file
        readfile($attachment['file_path']);
        exit();
    }
    
    /**
     * Delete attachment
     */
    public function delete() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $attachment_id = (int)($_POST['id'] ?? 0);
                $user_id = $_SESSION['user_id'];
                
                $result = $this->attachmentModel->delete($attachment_id, $user_id);
                
                if ($result) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        // Redirect back to the entity
                        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
                        $redirect_url = $this->getEntityUrl($attachment['entity_type'], $attachment['entity_id']);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to delete attachment');
                }
            } catch (Exception $e) {
                if (isset($_POST['format']) && $_POST['format'] === 'json') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                } else {
                    $_SESSION['error'] = $e->getMessage();
                    redirect(APP_URL . '/views/dashboard/dashboard.php');
                }
            }
        }
    }
    
    /**
     * Get attachments for entity (AJAX)
     */
    public function getAttachments() {
        requireLogin();
        
        $entity_type = $_GET['entity_type'] ?? '';
        $entity_id = (int)($_GET['entity_id'] ?? 0);
        
        if (empty($entity_type) || $entity_id <= 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid parameters']);
            exit();
        }
        
        $attachments = $this->attachmentModel->getEntityAttachments($entity_type, $entity_id);
        
        header('Content-Type: application/json');
        echo json_encode($attachments);
    }
    
    /**
     * Get entity URL for redirects
     */
    private function getEntityUrl($entity_type, $entity_id) {
        switch ($entity_type) {
            case 'task':
                return APP_URL . '/views/tasks/view.php?id=' . $entity_id;
            case 'project':
                return APP_URL . '/views/projects/view.php?id=' . $entity_id;
            default:
                return APP_URL . '/views/dashboard/dashboard.php';
        }
    }
}
?>
/**
 * Attachment Controller
 * Handles file attachment management operations
 */

class AttachmentController {
    private $attachmentModel;
    
    public function __construct() {
        $this->attachmentModel = new Attachment();
    }
    
    /**
     * Upload file
     */
    public function upload() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $entity_type = sanitizeInput($_POST['entity_type'] ?? '');
                $entity_id = (int)($_POST['entity_id'] ?? 0);
                
                if (empty($entity_type) || $entity_id <= 0) {
                    throw new Exception('Invalid entity parameters');
                }
                
                if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No file uploaded or upload error');
                }
                
                $user_id = $_SESSION['user_id'];
                $attachment_id = $this->attachmentModel->upload($entity_type, $entity_id, $_FILES['file'], $user_id);
                
                if ($attachment_id) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'attachment_id' => $attachment_id]);
                    } else {
                        // Redirect back to the entity
                        $redirect_url = $this->getEntityUrl($entity_type, $entity_id);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to upload file');
                }
            } catch (Exception $e) {
                if (isset($_POST['format']) && $_POST['format'] === 'json') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                } else {
                    $_SESSION['error'] = $e->getMessage();
                    $redirect_url = $this->getEntityUrl($_POST['entity_type'] ?? '', $_POST['entity_id'] ?? 0);
                    redirect($redirect_url);
                }
            }
        }
    }
    
    /**
     * Download file
     */
    public function download() {
        requireLogin();
        
        $attachment_id = (int)($_GET['id'] ?? 0);
        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
        
        if (!$attachment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if file exists
        if (!file_exists($attachment['file_path'])) {
            header('HTTP/1.1 404 Not Found');
            echo 'File not found on server';
            exit();
        }
        
        // Set headers for file download
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Output file
        readfile($attachment['file_path']);
        exit();
    }
    
    /**
     * View file
     */
    public function view() {
        requireLogin();
        
        $attachment_id = (int)($_GET['id'] ?? 0);
        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
        
        if (!$attachment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if file exists
        if (!file_exists($attachment['file_path'])) {
            header('HTTP/1.1 404 Not Found');
            echo 'File not found on server';
            exit();
        }
        
        // Set headers for file viewing
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: inline; filename="' . $attachment['original_name'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Output file
        readfile($attachment['file_path']);
        exit();
    }
    
    /**
     * Delete attachment
     */
    public function delete() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $attachment_id = (int)($_POST['id'] ?? 0);
                $user_id = $_SESSION['user_id'];
                
                $result = $this->attachmentModel->delete($attachment_id, $user_id);
                
                if ($result) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        // Redirect back to the entity
                        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
                        $redirect_url = $this->getEntityUrl($attachment['entity_type'], $attachment['entity_id']);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to delete attachment');
                }
            } catch (Exception $e) {
                if (isset($_POST['format']) && $_POST['format'] === 'json') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                } else {
                    $_SESSION['error'] = $e->getMessage();
                    redirect(APP_URL . '/views/dashboard/dashboard.php');
                }
            }
        }
    }
    
    /**
     * Get attachments for entity (AJAX)
     */
    public function getAttachments() {
        requireLogin();
        
        $entity_type = $_GET['entity_type'] ?? '';
        $entity_id = (int)($_GET['entity_id'] ?? 0);
        
        if (empty($entity_type) || $entity_id <= 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid parameters']);
            exit();
        }
        
        $attachments = $this->attachmentModel->getEntityAttachments($entity_type, $entity_id);
        
        header('Content-Type: application/json');
        echo json_encode($attachments);
    }
    
    /**
     * Get entity URL for redirects
     */
    private function getEntityUrl($entity_type, $entity_id) {
        switch ($entity_type) {
            case 'task':
                return APP_URL . '/views/tasks/view.php?id=' . $entity_id;
            case 'project':
                return APP_URL . '/views/projects/view.php?id=' . $entity_id;
            default:
                return APP_URL . '/views/dashboard/dashboard.php';
        }
    }
}
?>
/**
 * Attachment Controller
 * Handles file attachment management operations
 */

class AttachmentController {
    private $attachmentModel;
    
    public function __construct() {
        $this->attachmentModel = new Attachment();
    }
    
    /**
     * Upload file
     */
    public function upload() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $entity_type = sanitizeInput($_POST['entity_type'] ?? '');
                $entity_id = (int)($_POST['entity_id'] ?? 0);
                
                if (empty($entity_type) || $entity_id <= 0) {
                    throw new Exception('Invalid entity parameters');
                }
                
                if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No file uploaded or upload error');
                }
                
                $user_id = $_SESSION['user_id'];
                $attachment_id = $this->attachmentModel->upload($entity_type, $entity_id, $_FILES['file'], $user_id);
                
                if ($attachment_id) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'attachment_id' => $attachment_id]);
                    } else {
                        // Redirect back to the entity
                        $redirect_url = $this->getEntityUrl($entity_type, $entity_id);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to upload file');
                }
            } catch (Exception $e) {
                if (isset($_POST['format']) && $_POST['format'] === 'json') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                } else {
                    $_SESSION['error'] = $e->getMessage();
                    $redirect_url = $this->getEntityUrl($_POST['entity_type'] ?? '', $_POST['entity_id'] ?? 0);
                    redirect($redirect_url);
                }
            }
        }
    }
    
    /**
     * Download file
     */
    public function download() {
        requireLogin();
        
        $attachment_id = (int)($_GET['id'] ?? 0);
        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
        
        if (!$attachment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if file exists
        if (!file_exists($attachment['file_path'])) {
            header('HTTP/1.1 404 Not Found');
            echo 'File not found on server';
            exit();
        }
        
        // Set headers for file download
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Output file
        readfile($attachment['file_path']);
        exit();
    }
    
    /**
     * View file
     */
    public function view() {
        requireLogin();
        
        $attachment_id = (int)($_GET['id'] ?? 0);
        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
        
        if (!$attachment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if file exists
        if (!file_exists($attachment['file_path'])) {
            header('HTTP/1.1 404 Not Found');
            echo 'File not found on server';
            exit();
        }
        
        // Set headers for file viewing
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: inline; filename="' . $attachment['original_name'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Output file
        readfile($attachment['file_path']);
        exit();
    }
    
    /**
     * Delete attachment
     */
    public function delete() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $attachment_id = (int)($_POST['id'] ?? 0);
                $user_id = $_SESSION['user_id'];
                
                $result = $this->attachmentModel->delete($attachment_id, $user_id);
                
                if ($result) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        // Redirect back to the entity
                        $attachment = $this->attachmentModel->getAttachmentById($attachment_id);
                        $redirect_url = $this->getEntityUrl($attachment['entity_type'], $attachment['entity_id']);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to delete attachment');
                }
            } catch (Exception $e) {
                if (isset($_POST['format']) && $_POST['format'] === 'json') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                } else {
                    $_SESSION['error'] = $e->getMessage();
                    redirect(APP_URL . '/views/dashboard/dashboard.php');
                }
            }
        }
    }
    
    /**
     * Get attachments for entity (AJAX)
     */
    public function getAttachments() {
        requireLogin();
        
        $entity_type = $_GET['entity_type'] ?? '';
        $entity_id = (int)($_GET['entity_id'] ?? 0);
        
        if (empty($entity_type) || $entity_id <= 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid parameters']);
            exit();
        }
        
        $attachments = $this->attachmentModel->getEntityAttachments($entity_type, $entity_id);
        
        header('Content-Type: application/json');
        echo json_encode($attachments);
    }
    
    /**
     * Get entity URL for redirects
     */
    private function getEntityUrl($entity_type, $entity_id) {
        switch ($entity_type) {
            case 'task':
                return APP_URL . '/views/tasks/view.php?id=' . $entity_id;
            case 'project':
                return APP_URL . '/views/projects/view.php?id=' . $entity_id;
            default:
                return APP_URL . '/views/dashboard/dashboard.php';
        }
    }
}
?>
