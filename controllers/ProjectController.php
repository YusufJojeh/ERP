<?php
/**
 * Project Controller
 * Handles project management operations
 */

class ProjectController {
    private $projectModel;
    private $userModel;
    private $taskModel;
    
    public function __construct() {
        $this->projectModel = new Project();
        $this->userModel = new User();
        $this->taskModel = new Task();
    }
    
    /**
     * List all projects
     */
    public function list() {
        requireLogin();
        
        $pageTitle = 'Projects';
        $currentPage = 'projects';
        $pageDescription = 'Manage your projects and track progress';
        
        // Get pagination parameters
        $page = (int)($_GET['page'] ?? 1);
        $limit = ITEMS_PER_PAGE;
        
        // Get filters
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'manager_id' => $_GET['manager_id'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return !empty($value);
        });
        
        // Get projects based on user role
        if (hasRole(['admin', 'project_manager'])) {
        $projects = $this->projectModel->getAllProjects($page, $limit, $filters);
        $totalProjects = $this->projectModel->getTotalProjects($filters);
        } else {
            // Members can only see projects they're assigned to
            $filters['user_id'] = $_SESSION['user_id'];
            $projects = $this->projectModel->getUserProjects($_SESSION['user_id'], $page, $limit, $filters);
            $totalProjects = $this->projectModel->getUserProjectCount($_SESSION['user_id'], $filters);
        }
        
        $totalPages = ceil($totalProjects / $limit);
        
        // Get managers for filter
        $managers = $this->userModel->getUsersByRole('project_manager');
        
        // Page actions
        $pageActions = '';
        if (hasRole(['admin', 'project_manager'])) {
            $pageActions = '<a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Project</a>';
        }
        
        include 'views/projects/list.php';
    }
    
    /**
     * Create new project
     */
    public function create() {
        requireRole(['admin', 'project_manager']);
        
        $pageTitle = 'Create Project';
        $currentPage = 'projects';
        $pageDescription = 'Create a new project';
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'status' => $_POST['status'],
                    'priority' => $_POST['priority'],
                    'manager_id' => (int)($_POST['manager_id'] ?? 0),
                    'budget' => (float)$_POST['budget']
                ];
                
                if (empty($data['name'])) {
                    throw new Exception('Project name is required');
                }
                
                if (empty($data['manager_id']) || $data['manager_id'] <= 0) {
                    throw new Exception('Project manager is required. Every project must have a project manager assigned.');
                }
                
                // Verify the manager exists and has project_manager role
                $manager = $this->userModel->getUserById($data['manager_id']);
                if (!$manager || $manager['role'] !== 'project_manager') {
                    throw new Exception('Selected user must be a project manager.');
                }
                
                $project_id = $this->projectModel->create($data);
                
                if ($project_id) {
                    logActivity('created', 'project', $project_id, 'Project created: ' . $data['name']);
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=Project&action=view&id=' . $project_id,
                        'Project Created',
                        'Project "' . htmlspecialchars($data['name']) . '" has been created successfully.'
                    );
                }
            } catch (Exception $e) {
                logError('Project creation failed: ' . $e->getMessage(), ['data' => $data]);
                setErrorMessage('Project Creation Failed', $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        // Get managers
        $managers = $this->userModel->getUsersByRole('project_manager');
        
        include 'views/projects/create.php';
    }
    
    /**
     * View project details
     */
    public function view() {
        requireLogin();
        
        $project_id = $_GET['id'] ?? 0;
        $project = $this->projectModel->getProjectById($project_id);
        
        if (!$project) {
            logError('Project not found', ['project_id' => $project_id]);
            redirectWithError(
                APP_URL . '/index.php?controller=Project&action=list',
                'Project Not Found',
                'The requested project does not exist or you do not have permission to view it.'
            );
        }
        
        // Get project tasks
        $tasks = $this->taskModel->getProjectTasks($project_id);
        
        // Get project members
        $members = $this->projectModel->getProjectMembers($project_id);
        
        // Get project statistics
        $stats = $this->projectModel->getProjectStats($project_id);
        
        include 'views/projects/view.php';
    }
    
    /**
     * Edit project
     */
    public function edit() {
        requireRole(['admin', 'project_manager']);
        
        $project_id = $_GET['id'] ?? 0;
        $project = $this->projectModel->getProjectById($project_id);
        
        if (!$project) {
            header('HTTP/1.1 404 Not Found');
            include 'views/errors/404.php';
            exit();
        }
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'status' => $_POST['status'],
                    'priority' => $_POST['priority'],
                    'manager_id' => (int)($_POST['manager_id'] ?? 0),
                    'budget' => (float)$_POST['budget']
                ];
                
                if (empty($data['name'])) {
                    throw new Exception('Project name is required');
                }
                
                if (empty($data['manager_id']) || $data['manager_id'] <= 0) {
                    throw new Exception('Project manager is required. Every project must have a project manager assigned.');
                }
                
                // Verify the manager exists and has project_manager role
                $manager = $this->userModel->getUserById($data['manager_id']);
                if (!$manager || $manager['role'] !== 'project_manager') {
                    throw new Exception('Selected user must be a project manager.');
                }
                
                $result = $this->projectModel->update($project_id, $data);
                
                if ($result) {
                    logActivity('updated', 'project', $project_id, 'Project updated: ' . $data['name']);
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=Project&action=view&id=' . $project_id,
                        'Project Updated',
                        'Project "' . htmlspecialchars($data['name']) . '" has been updated successfully.'
                    );
                }
            } catch (Exception $e) {
                logError('Project update failed: ' . $e->getMessage(), ['project_id' => $project_id, 'data' => $data]);
                setErrorMessage('Project Update Failed', $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        // Get managers
        $managers = $this->userModel->getUsersByRole('project_manager');
        
        include 'views/projects/edit.php';
    }
    
    /**
     * Delete project
     */
    public function delete() {
        requireRole(['admin', 'project_manager']);
        
        $project_id = $_GET['id'] ?? 0;
        $project = $this->projectModel->getProjectById($project_id);
        
        if (!$project) {
            logError('Project not found for deletion', ['project_id' => $project_id]);
            redirectWithError(
                APP_URL . '/index.php?controller=Project&action=list',
                'Project Not Found',
                'The requested project does not exist.'
            );
        }
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $confirm = $_POST['confirm'] ?? '';
                
                if ($confirm !== 'DELETE') {
                    throw new Exception('Please type DELETE to confirm deletion');
                }
                
            $result = $this->projectModel->delete($project_id);
            
            if ($result) {
                logActivity('deleted', 'project', $project_id, 'Project deleted: ' . $project['name']);
                redirectWithSuccess(
                    APP_URL . '/index.php?controller=Project&action=list',
                    'Project Deleted',
                    'Project "' . htmlspecialchars($project['name']) . '" has been deleted successfully.'
                );
            }
        } catch (Exception $e) {
            logError('Project deletion failed: ' . $e->getMessage(), ['project_id' => $project_id]);
            setErrorMessage('Project Deletion Failed', $e->getMessage());
            $error = $e->getMessage();
        }
        }
        
        include 'views/projects/delete.php';
    }
    
    /**
     * Add member to project
     */
    public function addMember() {
        requireRole(['admin', 'project_manager']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $project_id = (int)$_POST['project_id'];
            $user_id = (int)$_POST['user_id'];
            $role = sanitizeInput($_POST['role']);
            
            try {
                $result = $this->projectModel->addMember($project_id, $user_id, $role);
                
                if ($result) {
                    logActivity('added_member', 'project', $project_id, 'Member added to project');
                    redirectWithSuccess(
                        APP_URL . '/index.php?controller=Project&action=view&id=' . $project_id,
                        'Member Added',
                        'Member has been added to the project successfully.'
                    );
                } else {
                    throw new Exception('Failed to add member to project');
                }
            } catch (Exception $e) {
                logError('Add member failed: ' . $e->getMessage(), ['project_id' => $project_id, 'user_id' => $user_id]);
                redirectWithError(
                    APP_URL . '/index.php?controller=Project&action=view&id=' . $project_id,
                    'Failed to Add Member',
                    $e->getMessage()
                );
            }
        }
    }
    
    /**
     * Remove member from project
     */
    public function removeMember() {
        requireRole(['admin', 'project_manager']);
        
        $project_id = (int)($_GET['project_id'] ?? 0);
        $user_id = (int)($_GET['user_id'] ?? 0);
        
        if (!$project_id || !$user_id) {
            redirectWithError(
                APP_URL . '/index.php?controller=Project&action=list',
                'Invalid Request',
                'Project ID and User ID are required.'
            );
        }
        
        try {
            $result = $this->projectModel->removeMember($project_id, $user_id);
            
            if ($result) {
                logActivity('removed_member', 'project', $project_id, 'Member removed from project');
                redirectWithSuccess(
                    APP_URL . '/index.php?controller=Project&action=view&id=' . $project_id,
                    'Member Removed',
                    'Member has been removed from the project successfully.'
                );
            } else {
                throw new Exception('Failed to remove member from project');
            }
        } catch (Exception $e) {
            logError('Remove member failed: ' . $e->getMessage(), ['project_id' => $project_id, 'user_id' => $user_id]);
            redirectWithError(
                APP_URL . '/index.php?controller=Project&action=view&id=' . $project_id,
                'Failed to Remove Member',
                $e->getMessage()
            );
        }
    }
}
?>
