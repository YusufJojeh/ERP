<?php
/**
 * Notification Controller
 * Handles notification management operations
 */

class NotificationController {
    private $notificationModel;
    
    public function __construct() {
        $this->notificationModel = new Notification();
    }
    
    /**
     * Get user notifications
     */
    public function index() {
        requireLogin();
        
        $user_id = $_SESSION['user_id'];
        $limit = (int)($_GET['limit'] ?? 50);
        $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';
        
        $notifications = $this->notificationModel->getUserNotifications($user_id, $limit, $unread_only);
        $unread_count = $this->notificationModel->getUnreadCount($user_id);
        
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json');
            echo json_encode([
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]);
            exit();
        }
        
        // If accessed via controller, include the view
        if (strpos($_SERVER['PHP_SELF'], 'index.php') !== false || strpos($_SERVER['PHP_SELF'], 'NotificationController') !== false) {
            $pageTitle = 'Notifications';
            $currentPage = 'notifications';
            include 'views/notifications/index.php';
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notification_id = $_POST['notification_id'] ?? 0;
            $user_id = $_SESSION['user_id'];
            
            $result = $this->notificationModel->markAsRead($notification_id, $user_id);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
            }
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            
            $result = $this->notificationModel->markAllAsRead($user_id);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark all notifications as read']);
            }
        }
    }
    
    /**
     * Delete notification
     */
    public function delete() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notification_id = $_POST['notification_id'] ?? 0;
            $user_id = $_SESSION['user_id'];
            
            $result = $this->notificationModel->delete($notification_id, $user_id);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
            }
        }
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount() {
        requireLogin();
        
        $user_id = $_SESSION['user_id'];
        $count = $this->notificationModel->getUnreadCount($user_id);
        
        echo json_encode(['count' => $count]);
    }
}
?>
