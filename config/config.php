<?php
/**
 * Application Configuration
 * Advanced Project & Task Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'ERP Task Management System');
define('APP_VERSION', '1.0.0');
// Auto-detect APP_URL based on current request
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $relativePath = '';
    
    // Method 1: Try to get from SCRIPT_NAME (most reliable for web requests)
    if (isset($_SERVER['SCRIPT_NAME']) && !empty($_SERVER['SCRIPT_NAME'])) {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        $scriptDir = str_replace('\\', '/', $scriptDir);
        $scriptDir = rtrim($scriptDir, '/');
        
        // Check if the path contains /projects/projects/ERP
        if (strpos($scriptDir, '/projects/projects/ERP') !== false) {
            $relativePath = '/projects/projects/ERP';
        } elseif (!empty($scriptDir) && $scriptDir !== '/' && $scriptDir !== '.') {
            $relativePath = $scriptDir;
        }
    }
    
    // Method 2: Try to get from DOCUMENT_ROOT if Method 1 didn't work
    if (empty($relativePath) && isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $projectRoot = str_replace('\\', '/', dirname(__DIR__));
        
        if (strpos($projectRoot, $docRoot) === 0) {
            $relativePath = substr($projectRoot, strlen($docRoot));
            $relativePath = str_replace('\\', '/', $relativePath);
            $relativePath = rtrim($relativePath, '/');
        }
    }
    
    // Method 3: Fallback to known path structure
    if (empty($relativePath)) {
        // Check if we're in the expected location
        $projectRoot = dirname(__DIR__);
        if (strpos($projectRoot, 'ERP') !== false || strpos($projectRoot, 'projects') !== false) {
            $relativePath = '/projects/projects/ERP';
        }
    }
    
    // Clean up the path
    if ($relativePath === '.' || $relativePath === '/') {
        $relativePath = '';
    }
    
    // Build the full URL
    $appUrl = $protocol . '://' . $host . $relativePath;
    define('APP_URL', $appUrl);
}
define('APP_TIMEZONE', 'UTC');

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
define('UPLOAD_PATH', 'uploads/');

// Pagination settings
define('ITEMS_PER_PAGE', 10);
define('MAX_ITEMS_PER_PAGE', 50);

// Date and time settings
date_default_timezone_set(APP_TIMEZONE);

// Define environment (development or production)
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Change to 'production' for production
}

// Include error handler
require_once __DIR__ . '/error_handler.php';

// Include database connection
require_once __DIR__ . '/database.php';

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/views/auth/login.php');
        exit();
    }
}

function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($role)) {
        return in_array($_SESSION['user_role'], $role);
    }
    
    return $_SESSION['user_role'] === $role;
}

function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        header('HTTP/1.1 403 Forbidden');
        include 'views/errors/403.php';
        exit();
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) {
        return '';
    }
    
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function logActivity($action, $entity_type, $entity_id, $description = '') {
    global $pdo;
    
    try {
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $entity_type,
            $entity_id,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

function sendNotification($user_id, $title, $message, $type = 'info', $entity_type = null, $entity_id = null) {
    try {
        $notification = new Notification();
        return $notification->create($user_id, $title, $message, $type, $entity_type, $entity_id);
    } catch (Exception $e) {
        error_log("Notification sending failed: " . $e->getMessage());
        return false;
    }
}

function getUnreadNotifications($user_id) {
    global $pdo;
    
    try {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'];
    } catch (Exception $e) {
        error_log("Failed to get unread notifications: " . $e->getMessage());
        return 0;
    }
}



/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Flash message system for toast notifications
 */
function setFlashMessage($type, $title, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'timestamp' => time()
    ];
}

function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function setSuccessMessage($title, $message) {
    setFlashMessage('success', $title, $message);
}

function setErrorMessage($title, $message) {
    setFlashMessage('error', $title, $message);
}

function setWarningMessage($title, $message) {
    setFlashMessage('warning', $title, $message);
}

function setInfoMessage($title, $message) {
    setFlashMessage('info', $title, $message);
}

/**
 * Redirect with flash message
 */
function redirectWithMessage($url, $type, $title, $message) {
    $url .= (strpos($url, '?') !== false ? '&' : '?') . $type . '=' . urlencode($message);
    redirect($url);
}

function redirectWithSuccess($url, $title, $message) {
    redirectWithMessage($url, 'success', $title, $message);
}

function redirectWithError($url, $title, $message) {
    redirectWithMessage($url, 'error', $title, $message);
}

function redirectWithWarning($url, $title, $message) {
    redirectWithMessage($url, 'warning', $title, $message);
}

function redirectWithInfo($url, $title, $message) {
    redirectWithMessage($url, 'info', $title, $message);
}

// Auto-load classes
spl_autoload_register(function ($class) {
    $directories = [
        'models/',
        'controllers/',
        'includes/'
    ];
    
    foreach ($directories as $directory) {
        $file = __DIR__ . '/../' . $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>
