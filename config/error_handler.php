<?php
/**
 * Comprehensive Error Handling System
 * Advanced Project & Task Management System
 */

// Error logging directory
define('ERROR_LOG_DIR', __DIR__ . '/../logs/');
define('ERROR_LOG_FILE', ERROR_LOG_DIR . 'errors.log');
define('ERROR_LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Create logs directory if it doesn't exist
if (!file_exists(ERROR_LOG_DIR)) {
    mkdir(ERROR_LOG_DIR, 0755, true);
}

/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline, $errcontext = null) {
    // Don't handle errors that are suppressed with @
    if (error_reporting() === 0) {
        return false;
    }

    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];

    $errorType = $errorTypes[$errno] ?? 'UNKNOWN';
    
    // Log error
    $errorMessage = sprintf(
        "[%s] %s: %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $errorType,
        $errstr,
        $errfile,
        $errline
    );

    error_log($errorMessage, 3, ERROR_LOG_FILE);

    // Rotate log if too large
    if (file_exists(ERROR_LOG_FILE) && filesize(ERROR_LOG_FILE) > ERROR_LOG_MAX_SIZE) {
        rotateErrorLog();
    }

    // Display error based on environment
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        // Show detailed error in development
        displayError($errorType, $errstr, $errfile, $errline);
    } else {
        // Show generic error in production
        if ($errno === E_ERROR || $errno === E_USER_ERROR) {
            displayGenericError();
        }
    }

    // Don't execute PHP internal error handler
    return true;
}

/**
 * Custom exception handler
 */
function customExceptionHandler($exception) {
    $errorMessage = sprintf(
        "[%s] EXCEPTION: %s in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );

    error_log($errorMessage, 3, ERROR_LOG_FILE);

    // Rotate log if too large
    if (file_exists(ERROR_LOG_FILE) && filesize(ERROR_LOG_FILE) > ERROR_LOG_MAX_SIZE) {
        rotateErrorLog();
    }

    // Display exception
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        displayException($exception);
    } else {
        displayGenericError();
    }
}

/**
 * Shutdown handler for fatal errors
 */
function customShutdownHandler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $errorMessage = sprintf(
            "[%s] FATAL ERROR: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );

        error_log($errorMessage, 3, ERROR_LOG_FILE);

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            displayError('FATAL ERROR', $error['message'], $error['file'], $error['line']);
        } else {
            displayGenericError();
        }
    }
}

/**
 * Display detailed error (development)
 */
function displayError($type, $message, $file, $line) {
    if (php_sapi_name() === 'cli') {
        echo "\n[$type] $message\nFile: $file\nLine: $line\n\n";
        return;
    }

    http_response_code(500);
    
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Error - ' . htmlspecialchars($type) . '</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .error-box { background: #2a2a2a; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; }
        .error-type { color: #ef4444; font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        .error-message { color: #fbbf24; margin: 10px 0; }
        .error-file { color: #60a5fa; margin: 5px 0; }
        .error-line { color: #a78bfa; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-type">' . htmlspecialchars($type) . '</div>
        <div class="error-message">' . htmlspecialchars($message) . '</div>
        <div class="error-file">File: ' . htmlspecialchars($file) . '</div>
        <div class="error-line">Line: ' . htmlspecialchars($line) . '</div>
    </div>
</body>
</html>';
    exit;
}

/**
 * Display exception (development)
 */
function displayException($exception) {
    if (php_sapi_name() === 'cli') {
        echo "\nEXCEPTION: " . $exception->getMessage() . "\n";
        echo "File: " . $exception->getFile() . "\n";
        echo "Line: " . $exception->getLine() . "\n";
        echo "Stack trace:\n" . $exception->getTraceAsString() . "\n\n";
        return;
    }

    http_response_code(500);
    
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Exception</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .error-box { background: #2a2a2a; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; }
        .error-type { color: #ef4444; font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        .error-message { color: #fbbf24; margin: 10px 0; }
        .error-file { color: #60a5fa; margin: 5px 0; }
        .error-line { color: #a78bfa; }
        .stack-trace { background: #1a1a1a; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 12px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-type">EXCEPTION</div>
        <div class="error-message">' . htmlspecialchars($exception->getMessage()) . '</div>
        <div class="error-file">File: ' . htmlspecialchars($exception->getFile()) . '</div>
        <div class="error-line">Line: ' . htmlspecialchars($exception->getLine()) . '</div>
        <div class="stack-trace"><pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre></div>
    </div>
</body>
</html>';
    exit;
}

/**
 * Display generic error (production)
 */
function displayGenericError() {
    if (php_sapi_name() === 'cli') {
        echo "An error occurred. Please check the error log.\n";
        return;
    }

    // Check if custom error page exists
    $errorPage = __DIR__ . '/../views/errors/500.php';
    if (file_exists($errorPage)) {
        http_response_code(500);
        include $errorPage;
    } else {
        http_response_code(500);
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #ef4444; }
    </style>
</head>
<body>
    <h1>500 Internal Server Error</h1>
    <p>An error occurred. Please try again later.</p>
</body>
</html>';
    }
    exit;
}

/**
 * Rotate error log
 */
function rotateErrorLog() {
    if (file_exists(ERROR_LOG_FILE)) {
        $backupFile = ERROR_LOG_DIR . 'errors_' . date('Y-m-d_His') . '.log';
        rename(ERROR_LOG_FILE, $backupFile);
        
        // Keep only last 5 backups
        $backups = glob(ERROR_LOG_DIR . 'errors_*.log');
        if (count($backups) > 5) {
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            foreach (array_slice($backups, 0, -5) as $oldBackup) {
                unlink($oldBackup);
            }
        }
    }
}

/**
 * Set error handlers
 */
function setupErrorHandling() {
    // Set error handler
    set_error_handler('customErrorHandler');
    
    // Set exception handler
    set_exception_handler('customExceptionHandler');
    
    // Set shutdown handler
    register_shutdown_function('customShutdownHandler');
    
    // Set error reporting based on environment
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', ERROR_LOG_FILE);
    }
}

// Initialize error handling
setupErrorHandling();

/**
 * Helper function to log custom errors
 */
function logError($message, $context = []) {
    $logMessage = sprintf(
        "[%s] CUSTOM ERROR: %s\nContext: %s\n",
        date('Y-m-d H:i:s'),
        $message,
        json_encode($context, JSON_PRETTY_PRINT)
    );
    
    error_log($logMessage, 3, ERROR_LOG_FILE);
}

/**
 * Helper function to handle API errors
 */
function handleApiError($message, $code = 500, $data = null) {
    http_response_code($code);
    header('Content-Type: application/json');
    
    $response = [
        'success' => false,
        'error' => $message,
        'code' => $code
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    // Log error
    logError($message, ['code' => $code, 'data' => $data]);
    
    echo json_encode($response);
    exit;
}

/**
 * Helper function to handle API success
 */
function handleApiSuccess($data = null, $message = 'Success') {
    http_response_code(200);
    header('Content-Type: application/json');
    
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

