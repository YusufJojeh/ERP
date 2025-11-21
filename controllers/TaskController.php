<?php
/**
 * Task Controller
 * Handles task management operations
 */

class TaskController {
    private $taskModel;
    private $projectModel;
    private $userModel;
    
    public function __construct() {
        $this->taskModel = new Task();
        $this->projectModel = new Project();
        $this->userModel = new User();
    }
    
    /**
     * List all tasks
     */
    public function list() {
        requireLogin();
        
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $project_id = $_GET['project_id'] ?? '';
        $assigned_to = $_GET['assigned_to'] ?? '';
        
        $filters = [
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'project_id' => $project_id,
            'assigned_to' => $assigned_to
        ];
        
        // Filter tasks based on user role
        if (hasRole(['admin', 'project_manager'])) {
            $tasks = $this->taskModel->getAllTasks($page, ITEMS_PER_PAGE, $filters);
            $totalTasks = $this->taskModel->getTotalTasks($filters);
            $projects = $this->projectModel->getAllProjects(1, 1000, []);
        } else {
            // Members can only see tasks assigned to them or created by them
            $filters['user_id'] = $_SESSION['user_id'];
            $tasks = $this->taskModel->getUserTasks($_SESSION['user_id'], $page, ITEMS_PER_PAGE, $filters);
            $totalTasks = $this->taskModel->getUserTaskCount($_SESSION['user_id'], $filters);
            $projects = $this->projectModel->getUserProjects($_SESSION['user_id']);
        }
        
        $users = $this->userModel->getAllUsers(1, 1000, '');
        $totalPages = ceil($totalTasks / ITEMS_PER_PAGE);
        
        include 'views/tasks/list.php';
    }
    
    /**
     * Create new task
     */
    public function create() {
        requireLogin();
        requireRole(['admin', 'project_manager', 'member']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'project_id' => sanitizeInput($_POST['project_id'] ?? ''),
                    'title' => sanitizeInput($_POST['title'] ?? ''),
                    'description' => sanitizeInput($_POST['description'] ?? ''),
                    'priority' => sanitizeInput($_POST['priority'] ?? 'medium'),
                    'assigned_to' => sanitizeInput($_POST['assigned_to'] ?? ''),
                    'due_date' => sanitizeInput($_POST['due_date'] ?? ''),
                    'estimated_hours' => sanitizeInput($_POST['estimated_hours'] ?? '')
                ];
                
                $data['created_by'] = $_SESSION['user_id'];
                $task_id = $this->taskModel->create($data);
                
                if ($task_id) {
                    logActivity('created', 'task', $task_id, 'Task created: ' . $data['title']);
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=Task&action=list',
                        'Task Created',
                        'Task "' . htmlspecialchars($data['title']) . '" has been created successfully.'
                    );
                }
            } catch (Exception $e) {
                logError('Task creation failed: ' . $e->getMessage(), ['data' => $data]);
                setErrorMessage('Task Creation Failed', $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        // Get projects based on user role
        if (hasRole(['admin', 'project_manager'])) {
            $projects = $this->projectModel->getAllProjects(1, 1000, []);
        } else {
            // Members can only see projects they're assigned to
            $projects = $this->projectModel->getUserProjects($_SESSION['user_id']);
        }
        
        $users = $this->userModel->getAllUsers(1, 1000, '');
        
        include 'views/tasks/create.php';
    }
    
    /**
     * View task details
     */
    public function view() {
        requireLogin();
        
        $task_id = $_GET['id'] ?? 0;
        $task = $this->taskModel->getTaskById($task_id);
        
        if (!$task) {
            logError('Task not found', ['task_id' => $task_id]);
            redirectWithError(
                APP_URL . '/index.php?controller=Task&action=list',
                'Task Not Found',
                'The requested task does not exist or you do not have permission to view it.'
            );
        }
        
        $comments = $this->taskModel->getTaskComments($task_id);
        $attachments = $this->taskModel->getTaskAttachments($task_id);
        
        include 'views/tasks/view.php';
    }
    
    /**
     * Edit task
     */
    public function edit() {
        requireLogin();
        
        $task_id = $_GET['id'] ?? 0;
        $task = $this->taskModel->getTaskById($task_id);
        
        if (!$task) {
            logError('Task not found for edit', ['task_id' => $task_id]);
            redirectWithError(
                APP_URL . '/index.php?controller=Task&action=list',
                'Task Not Found',
                'The requested task does not exist.'
            );
        }
        
        // Check if user can edit this task
        $canEdit = false;
        if (hasRole(['admin', 'project_manager'])) {
            $canEdit = true;
        } elseif (hasRole(['member']) && ($task['assigned_to'] == $_SESSION['user_id'] || $task['created_by'] == $_SESSION['user_id'])) {
            $canEdit = true;
        }
        
        if (!$canEdit) {
            logError('Permission denied for task edit', ['task_id' => $task_id, 'user_id' => $_SESSION['user_id']]);
            redirectWithError(
                APP_URL . '/index.php?controller=Task&action=list',
                'Permission Denied',
                'You do not have permission to edit this task.'
            );
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'title' => sanitizeInput($_POST['title'] ?? ''),
                    'description' => sanitizeInput($_POST['description'] ?? ''),
                    'priority' => sanitizeInput($_POST['priority'] ?? 'medium'),
                    'status' => sanitizeInput($_POST['status'] ?? 'pending'),
                    'assigned_to' => sanitizeInput($_POST['assigned_to'] ?? ''),
                    'due_date' => sanitizeInput($_POST['due_date'] ?? ''),
                    'estimated_hours' => sanitizeInput($_POST['estimated_hours'] ?? ''),
                    'actual_hours' => sanitizeInput($_POST['actual_hours'] ?? '')
                ];
                
                $result = $this->taskModel->update($task_id, $data);
                
                if ($result) {
                    logActivity('updated', 'task', $task_id, 'Task updated: ' . $data['title']);
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=Task&action=view&id=' . $task_id,
                        'Task Updated',
                        'Task "' . htmlspecialchars($data['title']) . '" has been updated successfully.'
                    );
                }
            } catch (Exception $e) {
                logError('Task update failed: ' . $e->getMessage(), ['task_id' => $task_id, 'data' => $data]);
                setErrorMessage('Task Update Failed', $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        // Get projects based on user role
        if (hasRole(['admin', 'project_manager'])) {
            $projects = $this->projectModel->getAllProjects(1, 1000, []);
        } else {
            $projects = $this->projectModel->getUserProjects($_SESSION['user_id']);
        }
        
        $users = $this->userModel->getAllUsers(1, 1000, '');
        
        include 'views/tasks/edit.php';
    }
    
    /**
     * Delete task
     */
    public function delete() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $task_id = $_GET['id'] ?? 0;
        $task = $this->taskModel->getTaskById($task_id);
        
        if (!$task) {
            logError('Task not found for deletion', ['task_id' => $task_id]);
            redirectWithError(
                APP_URL . '/index.php?controller=Task&action=list',
                'Task Not Found',
                'The requested task does not exist.'
            );
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $result = $this->taskModel->delete($task_id);
                
                if ($result) {
                    logActivity('deleted', 'task', $task_id, 'Task deleted: ' . $task['title']);
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=Task&action=list',
                        'Task Deleted',
                        'Task "' . htmlspecialchars($task['title']) . '" has been deleted successfully.'
                    );
                } else {
                    throw new Exception('Failed to delete task');
                }
            } catch (Exception $e) {
                logError('Task deletion failed: ' . $e->getMessage(), ['task_id' => $task_id]);
                redirectWithError(
                    APP_URL . '/index.php?controller=Task&action=list',
                    'Task Deletion Failed',
                    $e->getMessage()
                );
            }
        }
        
        include 'views/tasks/delete.php';
    }
    
    /**
     * Update task status
     */
    public function updateStatus() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $task_id = $_POST['task_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            $data = ['status' => $status];
            $result = $this->taskModel->update($task_id, $data);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        }
    }
}
?>

            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        }
    }
}
?>

            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        }
    }
}
?>
