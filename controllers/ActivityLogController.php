<?php
/**
 * Activity Log Controller
 * Handles activity log management operations
 */

class ActivityLogController {
    private $activityLogModel;
    
    public function __construct() {
        $this->activityLogModel = new ActivityLog();
    }
    
    /**
     * List activity logs
     */
    public function index() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        
        // Get filters
        $filters = [
            'user_id' => $_GET['user_id'] ?? '',
            'action' => $_GET['action'] ?? '',
            'entity_type' => $_GET['entity_type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return !empty($value);
        });
        
        $activities = $this->activityLogModel->getActivityLogs($page, $limit, $filters);
        $totalActivities = $this->activityLogModel->getTotalActivityLogs($filters);
        $totalPages = ceil($totalActivities / $limit);
        
        // Get statistics
        $stats = $this->activityLogModel->getActivityStats($filters);
        
        // Get filter options
        $userModel = new User();
        $users = $userModel->getAllUsers(1, 1000, '');
        
        $actions = ['created', 'updated', 'deleted', 'login', 'logout', 'registered', 'commented', 'attached'];
        $entityTypes = ['project', 'task', 'user', 'comment', 'attachment'];
        
        // Set page variables for view
        $pageTitle = 'Activity Logs';
        $currentPage = 'activity_logs';
        $pageDescription = 'View system activity logs and audit trail';
        
        // Include header
        include 'views/includes/header.php';
        
        // Include the view (variables are already set)
        include 'views/activity_logs/index.php';
        
        // Include footer
        include 'views/includes/footer.php';
    }
    
    /**
     * Get recent activities (AJAX)
     */
    public function getRecent() {
        requireLogin();
        
        $limit = (int)($_GET['limit'] ?? 10);
        $activities = $this->activityLogModel->getRecentActivities($limit);
        
        header('Content-Type: application/json');
        echo json_encode($activities);
    }
    
    /**
     * Get user activities (AJAX)
     */
    public function getUserActivities() {
        requireLogin();
        
        $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
        $limit = (int)($_GET['limit'] ?? 10);
        
        $activities = $this->activityLogModel->getUserActivities($user_id, $limit);
        
        header('Content-Type: application/json');
        echo json_encode($activities);
    }
    
    /**
     * Get entity activities (AJAX)
     */
    public function getEntityActivities() {
        requireLogin();
        
        $entity_type = $_GET['entity_type'] ?? '';
        $entity_id = $_GET['entity_id'] ?? 0;
        $limit = (int)($_GET['limit'] ?? 10);
        
        $activities = $this->activityLogModel->getEntityActivities($entity_type, $entity_id, $limit);
        
        header('Content-Type: application/json');
        echo json_encode($activities);
    }
    
    /**
     * Get activity statistics (AJAX)
     */
    public function getStats() {
        requireLogin();
        requireRole(['admin', 'project_manager']);
        
        $filters = [
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        $filters = array_filter($filters, function($value) {
            return !empty($value);
        });
        
        $stats = $this->activityLogModel->getActivityStats($filters);
        $dailyCounts = $this->activityLogModel->getDailyActivityCounts(30);
        $mostActiveUsers = $this->activityLogModel->getMostActiveUsers(10);
        
        header('Content-Type: application/json');
        echo json_encode([
            'stats' => $stats,
            'daily_counts' => $dailyCounts,
            'most_active_users' => $mostActiveUsers
        ]);
    }
    
    /**
     * Clean old logs
     */
    public function clean() {
        requireLogin();
        requireRole(['admin']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $days = (int)($_POST['days'] ?? 365);
            
            $result = $this->activityLogModel->cleanOldLogs($days);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => "Cleaned logs older than {$days} days"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clean logs']);
            }
        }
    }
}
?>
