<?php
/**
 * Comment Controller
 * Handles comment management operations
 */

class CommentController {
    private $commentModel;
    
    public function __construct() {
        $this->commentModel = new Comment();
    }
    
    /**
     * Add comment
     */
    public function add() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $entity_type = sanitizeInput($_POST['entity_type'] ?? '');
                $entity_id = (int)($_POST['entity_id'] ?? 0);
                $content = sanitizeInput($_POST['content'] ?? '');
                $is_internal = isset($_POST['is_internal']) ? (bool)$_POST['is_internal'] : false;
                
                if (empty($entity_type) || $entity_id <= 0 || empty($content)) {
                    throw new Exception('All fields are required');
                }
                
                $user_id = $_SESSION['user_id'];
                $comment_id = $this->commentModel->addComment($entity_type, $entity_id, $user_id, $content, $is_internal);
                
                if ($comment_id) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'comment_id' => $comment_id]);
                    } else {
                        // Redirect back to the entity
                        $redirect_url = $this->getEntityUrl($entity_type, $entity_id);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to add comment');
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
     * Edit comment
     */
    public function edit() {
        requireLogin();
        
        $comment_id = (int)($_GET['id'] ?? 0);
        $comment = $this->commentModel->getCommentById($comment_id);
        
        if (!$comment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if user can edit this comment
        if ($comment['user_id'] != $_SESSION['user_id'] && !hasRole(['admin', 'project_manager'])) {
            header('HTTP/1.1 403 Forbidden');
            include 'views/errors/403.php';
            exit();
        }
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $content = sanitizeInput($_POST['content'] ?? '');
                
                if (empty($content)) {
                    throw new Exception('Comment content is required');
                }
                
                $result = $this->commentModel->updateComment($comment_id, $content);
                
                if ($result) {
                    $success = 'Comment updated successfully!';
                    echo "<script>setTimeout(function(){ window.location.href = '" . $this->getEntityUrl($comment['entity_type'], $comment['entity_id']) . "'; }, 2000);</script>";
                } else {
                    throw new Exception('Failed to update comment');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        include 'views/comments/edit.php';
    }
    
    /**
     * Delete comment
     */
    public function delete() {
        requireLogin();
        
        $comment_id = (int)($_GET['id'] ?? 0);
        $comment = $this->commentModel->getCommentById($comment_id);
        
        if (!$comment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if user can delete this comment
        if ($comment['user_id'] != $_SESSION['user_id'] && !hasRole(['admin', 'project_manager'])) {
            header('HTTP/1.1 403 Forbidden');
            include 'views/errors/403.php';
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
                
                $result = $this->commentModel->deleteComment($comment_id);
                
                if ($result) {
                    $success = 'Comment deleted successfully!';
                    echo "<script>setTimeout(function(){ window.location.href = '" . $this->getEntityUrl($comment['entity_type'], $comment['entity_id']) . "'; }, 2000);</script>";
                } else {
                    throw new Exception('Failed to delete comment');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        include 'views/comments/delete.php';
    }
    
    /**
     * Get comments for entity (AJAX)
     */
    public function getComments() {
        requireLogin();
        
        $entity_type = $_GET['entity_type'] ?? '';
        $entity_id = (int)($_GET['entity_id'] ?? 0);
        
        if (empty($entity_type) || $entity_id <= 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid parameters']);
            exit();
        }
        
        $comments = $this->commentModel->getCommentsByEntity($entity_type, $entity_id);
        
        header('Content-Type: application/json');
        echo json_encode($comments);
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
 * Comment Controller
 * Handles comment management operations
 */

class CommentController {
    private $commentModel;
    
    public function __construct() {
        $this->commentModel = new Comment();
    }
    
    /**
     * Add comment
     */
    public function add() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $entity_type = sanitizeInput($_POST['entity_type'] ?? '');
                $entity_id = (int)($_POST['entity_id'] ?? 0);
                $content = sanitizeInput($_POST['content'] ?? '');
                $is_internal = isset($_POST['is_internal']) ? (bool)$_POST['is_internal'] : false;
                
                if (empty($entity_type) || $entity_id <= 0 || empty($content)) {
                    throw new Exception('All fields are required');
                }
                
                $user_id = $_SESSION['user_id'];
                $comment_id = $this->commentModel->addComment($entity_type, $entity_id, $user_id, $content, $is_internal);
                
                if ($comment_id) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'comment_id' => $comment_id]);
                    } else {
                        // Redirect back to the entity
                        $redirect_url = $this->getEntityUrl($entity_type, $entity_id);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to add comment');
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
     * Edit comment
     */
    public function edit() {
        requireLogin();
        
        $comment_id = (int)($_GET['id'] ?? 0);
        $comment = $this->commentModel->getCommentById($comment_id);
        
        if (!$comment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if user can edit this comment
        if ($comment['user_id'] != $_SESSION['user_id'] && !hasRole(['admin', 'project_manager'])) {
            header('HTTP/1.1 403 Forbidden');
            include 'views/errors/403.php';
            exit();
        }
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $content = sanitizeInput($_POST['content'] ?? '');
                
                if (empty($content)) {
                    throw new Exception('Comment content is required');
                }
                
                $result = $this->commentModel->updateComment($comment_id, $content);
                
                if ($result) {
                    $success = 'Comment updated successfully!';
                    echo "<script>setTimeout(function(){ window.location.href = '" . $this->getEntityUrl($comment['entity_type'], $comment['entity_id']) . "'; }, 2000);</script>";
                } else {
                    throw new Exception('Failed to update comment');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        include 'views/comments/edit.php';
    }
    
    /**
     * Delete comment
     */
    public function delete() {
        requireLogin();
        
        $comment_id = (int)($_GET['id'] ?? 0);
        $comment = $this->commentModel->getCommentById($comment_id);
        
        if (!$comment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if user can delete this comment
        if ($comment['user_id'] != $_SESSION['user_id'] && !hasRole(['admin', 'project_manager'])) {
            header('HTTP/1.1 403 Forbidden');
            include 'views/errors/403.php';
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
                
                $result = $this->commentModel->deleteComment($comment_id);
                
                if ($result) {
                    $success = 'Comment deleted successfully!';
                    echo "<script>setTimeout(function(){ window.location.href = '" . $this->getEntityUrl($comment['entity_type'], $comment['entity_id']) . "'; }, 2000);</script>";
                } else {
                    throw new Exception('Failed to delete comment');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        include 'views/comments/delete.php';
    }
    
    /**
     * Get comments for entity (AJAX)
     */
    public function getComments() {
        requireLogin();
        
        $entity_type = $_GET['entity_type'] ?? '';
        $entity_id = (int)($_GET['entity_id'] ?? 0);
        
        if (empty($entity_type) || $entity_id <= 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid parameters']);
            exit();
        }
        
        $comments = $this->commentModel->getCommentsByEntity($entity_type, $entity_id);
        
        header('Content-Type: application/json');
        echo json_encode($comments);
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
 * Comment Controller
 * Handles comment management operations
 */

class CommentController {
    private $commentModel;
    
    public function __construct() {
        $this->commentModel = new Comment();
    }
    
    /**
     * Add comment
     */
    public function add() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $entity_type = sanitizeInput($_POST['entity_type'] ?? '');
                $entity_id = (int)($_POST['entity_id'] ?? 0);
                $content = sanitizeInput($_POST['content'] ?? '');
                $is_internal = isset($_POST['is_internal']) ? (bool)$_POST['is_internal'] : false;
                
                if (empty($entity_type) || $entity_id <= 0 || empty($content)) {
                    throw new Exception('All fields are required');
                }
                
                $user_id = $_SESSION['user_id'];
                $comment_id = $this->commentModel->addComment($entity_type, $entity_id, $user_id, $content, $is_internal);
                
                if ($comment_id) {
                    if (isset($_POST['format']) && $_POST['format'] === 'json') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'comment_id' => $comment_id]);
                    } else {
                        // Redirect back to the entity
                        $redirect_url = $this->getEntityUrl($entity_type, $entity_id);
                        redirect($redirect_url);
                    }
                } else {
                    throw new Exception('Failed to add comment');
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
     * Edit comment
     */
    public function edit() {
        requireLogin();
        
        $comment_id = (int)($_GET['id'] ?? 0);
        $comment = $this->commentModel->getCommentById($comment_id);
        
        if (!$comment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if user can edit this comment
        if ($comment['user_id'] != $_SESSION['user_id'] && !hasRole(['admin', 'project_manager'])) {
            header('HTTP/1.1 403 Forbidden');
            include 'views/errors/403.php';
            exit();
        }
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $content = sanitizeInput($_POST['content'] ?? '');
                
                if (empty($content)) {
                    throw new Exception('Comment content is required');
                }
                
                $result = $this->commentModel->updateComment($comment_id, $content);
                
                if ($result) {
                    $success = 'Comment updated successfully!';
                    echo "<script>setTimeout(function(){ window.location.href = '" . $this->getEntityUrl($comment['entity_type'], $comment['entity_id']) . "'; }, 2000);</script>";
                } else {
                    throw new Exception('Failed to update comment');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        include 'views/comments/edit.php';
    }
    
    /**
     * Delete comment
     */
    public function delete() {
        requireLogin();
        
        $comment_id = (int)($_GET['id'] ?? 0);
        $comment = $this->commentModel->getCommentById($comment_id);
        
        if (!$comment) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        // Check if user can delete this comment
        if ($comment['user_id'] != $_SESSION['user_id'] && !hasRole(['admin', 'project_manager'])) {
            header('HTTP/1.1 403 Forbidden');
            include 'views/errors/403.php';
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
                
                $result = $this->commentModel->deleteComment($comment_id);
                
                if ($result) {
                    $success = 'Comment deleted successfully!';
                    echo "<script>setTimeout(function(){ window.location.href = '" . $this->getEntityUrl($comment['entity_type'], $comment['entity_id']) . "'; }, 2000);</script>";
                } else {
                    throw new Exception('Failed to delete comment');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        include 'views/comments/delete.php';
    }
    
    /**
     * Get comments for entity (AJAX)
     */
    public function getComments() {
        requireLogin();
        
        $entity_type = $_GET['entity_type'] ?? '';
        $entity_id = (int)($_GET['entity_id'] ?? 0);
        
        if (empty($entity_type) || $entity_id <= 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Invalid parameters']);
            exit();
        }
        
        $comments = $this->commentModel->getCommentsByEntity($entity_type, $entity_id);
        
        header('Content-Type: application/json');
        echo json_encode($comments);
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
