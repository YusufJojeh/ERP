<?php
/**
 * API Controller
 * Handles AJAX API requests
 */

class ApiController {
    
    public function __construct() {
        // Set JSON header for all API responses
        header('Content-Type: application/json');
    }
    
    /**
     * Handle API requests
     */
    public function handle() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'notifications':
                $this->handleNotifications();
                break;
            case 'tasks':
                $this->handleTasks();
                break;
            case 'projects':
                $this->handleProjects();
                break;
            case 'users':
                $this->handleUsers();
                break;
            case 'comments':
                $this->handleComments();
                break;
            case 'attachments':
                $this->handleAttachments();
                break;
            case 'activity':
                $this->handleActivity();
                break;
            default:
                $this->sendError('Invalid API action', 400);
        }
    }
    
    /**
     * Handle notification API requests
     */
    private function handleNotifications() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getNotifications();
                break;
            case 'POST':
                $this->updateNotification();
                break;
            case 'DELETE':
                $this->deleteNotification();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle task API requests
     */
    private function handleTasks() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getTasks();
                break;
            case 'POST':
                $this->createTask();
                break;
            case 'PUT':
                $this->updateTask();
                break;
            case 'DELETE':
                $this->deleteTask();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle project API requests
     */
    private function handleProjects() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getProjects();
                break;
            case 'POST':
                $this->createProject();
                break;
            case 'PUT':
                $this->updateProject();
                break;
            case 'DELETE':
                $this->deleteProject();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle user API requests
     */
    private function handleUsers() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getUsers();
                break;
            case 'POST':
                $this->createUser();
                break;
            case 'PUT':
                $this->updateUser();
                break;
            case 'DELETE':
                $this->deleteUser();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle comment API requests
     */
    private function handleComments() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getComments();
                break;
            case 'POST':
                $this->createComment();
                break;
            case 'PUT':
                $this->updateComment();
                break;
            case 'DELETE':
                $this->deleteComment();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle attachment API requests
     */
    private function handleAttachments() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getAttachments();
                break;
            case 'POST':
                $this->uploadAttachment();
                break;
            case 'DELETE':
                $this->deleteAttachment();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Handle activity API requests
     */
    private function handleActivity() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getActivity();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    // Notification methods
    private function getNotifications() {
        requireLogin();
        
        $notificationController = new NotificationController();
        $user_id = $_SESSION['user_id'];
        $limit = (int)($_GET['limit'] ?? 10);
        $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';
        
        $notifications = $this->notificationModel->getUserNotifications($user_id, $limit, $unread_only);
        $unread_count = $this->notificationModel->getUnreadCount($user_id);
        
        $this->sendSuccess([
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
    }
    
    private function updateNotification() {
        requireLogin();
        
        $notificationController = new NotificationController();
        $notificationController->markAsRead();
    }
    
    private function deleteNotification() {
        requireLogin();
        
        $notificationController = new NotificationController();
        $notificationController->delete();
    }
    
    // Task methods
    private function getTasks() {
        requireLogin();
        
        $taskModel = new Task();
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? ''
        ];
        
        $tasks = $taskModel->getAllTasks($page, $limit, $filters);
        $total = $taskModel->getTotalTasks($filters);
        
        $this->sendSuccess([
            'tasks' => $tasks,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    private function createTask() {
        requireLogin();
        
        $taskController = new TaskController();
        $taskController->create();
    }
    
    private function updateTask() {
        requireLogin();
        
        $taskController = new TaskController();
        $taskController->updateStatus();
    }
    
    private function deleteTask() {
        requireLogin();
        
        $taskController = new TaskController();
        $taskController->delete();
    }
    
    // Project methods
    private function getProjects() {
        requireLogin();
        
        $projectModel = new Project();
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'manager_id' => $_GET['manager_id'] ?? ''
        ];
        
        $projects = $projectModel->getAllProjects($page, $limit, $filters);
        $total = $projectModel->getTotalProjects($filters);
        
        $this->sendSuccess([
            'projects' => $projects,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    private function createProject() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $projectController = new ProjectController();
        $projectController->create();
    }
    
    private function updateProject() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $projectController = new ProjectController();
        $projectController->edit();
    }
    
    private function deleteProject() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $projectController = new ProjectController();
        $projectController->delete();
    }
    
    // User methods
    private function getUsers() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $userModel = new User();
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $search = $_GET['search'] ?? '';
        
        $users = $userModel->getAllUsers($page, $limit, $search);
        $total = $userModel->getTotalUsers($search);
        
        $this->sendSuccess([
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    private function createUser() {
        $userController = new UserController();
        $userController->register();
    }
    
    private function updateUser() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $userController = new UserController();
        $userController->edit();
    }
    
    private function deleteUser() {
        requireLogin();
        requireRole(['admin']);
        
        $userController = new UserController();
        $userController->delete();
    }
    
    // Comment methods
    private function getComments() {
        requireLogin();
        
        $commentController = new CommentController();
        $commentController->getComments();
    }
    
    private function createComment() {
        requireLogin();
        
        $commentController = new CommentController();
        $commentController->add();
    }
    
    private function updateComment() {
        requireLogin();
        
        $commentController = new CommentController();
        $commentController->edit();
    }
    
    private function deleteComment() {
        requireLogin();
        
        $commentController = new CommentController();
        $commentController->delete();
    }
    
    // Attachment methods
    private function getAttachments() {
        requireLogin();
        
        $attachmentController = new AttachmentController();
        $attachmentController->getAttachments();
    }
    
    private function uploadAttachment() {
        requireLogin();
        
        $attachmentController = new AttachmentController();
        $attachmentController->upload();
    }
    
    private function deleteAttachment() {
        requireLogin();
        
        $attachmentController = new AttachmentController();
        $attachmentController->delete();
    }
    
    // Activity methods
    private function getActivity() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $activityController = new ActivityLogController();
        $activityController->getStats();
    }
    
    /**
     * Send success response
     */
    private function sendSuccess($data = null, $message = 'Success') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }
    
    /**
     * Send error response
     */
    private function sendError($message = 'Error', $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit();
    }
}
?>
