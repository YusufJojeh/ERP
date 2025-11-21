# Error Handling & Toast Notifications System

## Overview

This document describes the comprehensive error handling and toast notification system implemented in the ERP project.

## Features

### 1. Error Handling System

#### Components:
- **`config/error_handler.php`** - Main error handling system
- **Error Logging** - All errors logged to `logs/errors.log`
- **Error Rotation** - Automatic log rotation when file exceeds 10MB
- **Environment-Based Display** - Different error display for development/production

#### Functions:
- `customErrorHandler()` - Handles PHP errors
- `customExceptionHandler()` - Handles exceptions
- `customShutdownHandler()` - Handles fatal errors
- `logError()` - Log custom errors
- `handleApiError()` - Handle API errors
- `handleApiSuccess()` - Handle API success

#### Usage:
```php
// Log custom error
logError('Custom error message', ['context' => 'data']);

// Handle API error
handleApiError('Error message', 500, $data);

// Handle API success
handleApiSuccess($data, 'Success message');
```

### 2. Toast Notification System

#### Components:
- **`assets/css/toast.css`** - Toast styling (Glass Morphism)
- **`assets/js/toast.js`** - Toast JavaScript functionality
- **Flash Messages** - Session-based message passing

#### Toast Types:
- **Success** - Green toast for successful operations
- **Error** - Red toast for errors
- **Warning** - Yellow toast for warnings
- **Info** - Blue toast for informational messages

#### Usage in PHP:
```php
// Set flash message
setSuccessMessage('Title', 'Message');
setErrorMessage('Title', 'Message');
setWarningMessage('Title', 'Message');
setInfoMessage('Title', 'Message');

// Redirect with message
redirectWithSuccess($url, 'Title', 'Message');
redirectWithError($url, 'Title', 'Message');
redirectWithWarning($url, 'Title', 'Message');
redirectWithInfo($url, 'Title', 'Message');
```

#### Usage in JavaScript:
```javascript
// Show toast
showToast('success', 'Title', 'Message', 5000);
showSuccess('Title', 'Message', 5000);
showError('Title', 'Message', 5000);
showWarning('Title', 'Message', 5000);
showInfo('Title', 'Message', 5000);
```

### 3. Integration

#### Controllers Updated:
- ✅ `AuthController` - Login, register, logout, password change, profile update
- ✅ `ProjectController` - Create, edit, delete, add/remove members
- ✅ `TaskController` - Create, edit, delete, update status

#### Features:
- All operations show toast notifications
- All errors are logged
- All errors show user-friendly messages
- Activity logging for all operations
- Automatic error handling

## Configuration

### Environment Setup

In `config/config.php`:
```php
// Set environment
define('ENVIRONMENT', 'development'); // or 'production'
```

### Error Log Location

Errors are logged to: `logs/errors.log`

### Toast Settings

Default duration: 5000ms (5 seconds)
Max toasts: 5
Position: Top right (responsive: full width on mobile)

## Examples

### Example 1: Create Project with Toast
```php
try {
    $project_id = $this->projectModel->create($data);
    if ($project_id) {
        logActivity('created', 'project', $project_id, 'Project created');
        redirectWithSuccess(
            APP_URL . '/index.php?controller=Project&action=view&id=' . $project_id,
            'Project Created',
            'Project has been created successfully.'
        );
    }
} catch (Exception $e) {
    logError('Project creation failed: ' . $e->getMessage(), ['data' => $data]);
    setErrorMessage('Project Creation Failed', $e->getMessage());
}
```

### Example 2: Handle API Error
```php
try {
    // API operation
    $result = performOperation();
    handleApiSuccess($result, 'Operation successful');
} catch (Exception $e) {
    handleApiError($e->getMessage(), 500);
}
```

### Example 3: JavaScript Toast
```javascript
// After AJAX success
showSuccess('Task Updated', 'Task has been updated successfully.');

// After AJAX error
showError('Update Failed', 'Failed to update task. Please try again.');
```

## Benefits

1. **User Experience**: Clear, non-intrusive notifications
2. **Error Tracking**: All errors logged for debugging
3. **Consistency**: Uniform error handling across the application
4. **Security**: No sensitive information exposed in production
5. **Maintainability**: Centralized error handling

## Testing

To test the system:

1. **Test Toast Notifications**:
   - Perform any CRUD operation
   - Check for toast appearance
   - Verify message content

2. **Test Error Handling**:
   - Trigger an error (e.g., invalid input)
   - Check error log file
   - Verify error toast appears

3. **Test Logging**:
   - Check `logs/errors.log` file
   - Verify errors are logged with context

## Notes

- Toast notifications automatically disappear after 5 seconds
- Users can manually close toasts
- Maximum 5 toasts displayed at once
- Error logs rotate automatically when exceeding 10MB
- Production mode hides detailed error messages

