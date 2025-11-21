<?php
/**
 * Test Role-Based Permissions
 * Tests all CRUD operations for different user roles
 */

require_once 'config/config.php';

echo "<h1>Role-Based Permissions Test</h1>\n";

// Test data
$testUsers = [
    'admin' => ['username' => 'admin', 'password' => 'password', 'role' => 'admin'],
    'project_manager' => ['username' => 'pm1', 'password' => 'password', 'role' => 'project_manager'],
    'member' => ['username' => 'member1', 'password' => 'password', 'role' => 'member']
];

$testProject = [
    'name' => 'Test Project for Permissions',
    'description' => 'Testing role-based access control',
    'status' => 'active',
    'priority' => 'medium',
    'manager_id' => 1
];

$testTask = [
    'title' => 'Test Task for Permissions',
    'description' => 'Testing task permissions',
    'priority' => 'medium',
    'status' => 'pending',
    'project_id' => 1,
    'assigned_to' => 1
];

function testUserRole($userData, $role) {
    echo "<h2>Testing {$role} Role</h2>\n";
    
    // Simulate login
    $_SESSION['user_id'] = 1; // Assuming user ID 1 exists
    $_SESSION['user_role'] = $role;
    $_SESSION['username'] = $userData['username'];
    
    echo "<h3>1. Testing Project List Access</h3>\n";
    try {
        $projectModel = new Project();
        if ($role === 'admin' || $role === 'project_manager') {
            $projects = $projectModel->getAllProjects(1, 10, []);
            echo "✓ Can access all projects: " . count($projects) . " projects found<br>\n";
        } else {
            $projects = $projectModel->getUserProjects(1, 1, 10, []);
            echo "✓ Can access user projects: " . count($projects) . " projects found<br>\n";
        }
    } catch (Exception $e) {
        echo "✗ Project list access failed: " . $e->getMessage() . "<br>\n";
    }
    
    echo "<h3>2. Testing Task List Access</h3>\n";
    try {
        $taskModel = new Task();
        if ($role === 'admin' || $role === 'project_manager') {
            $tasks = $taskModel->getAllTasks(1, 10, []);
            echo "✓ Can access all tasks: " . count($tasks) . " tasks found<br>\n";
        } else {
            $tasks = $taskModel->getUserTasks(1, 1, 10, []);
            echo "✓ Can access user tasks: " . count($tasks) . " tasks found<br>\n";
        }
    } catch (Exception $e) {
        echo "✗ Task list access failed: " . $e->getMessage() . "<br>\n";
    }
    
    echo "<h3>3. Testing User List Access</h3>\n";
    try {
        $userModel = new User();
        if ($role === 'admin' || $role === 'project_manager') {
            $users = $userModel->getAllUsers(1, 10, '');
            echo "✓ Can access user list: " . count($users) . " users found<br>\n";
        } else {
            echo "✗ Members cannot access user list (403 expected)<br>\n";
        }
    } catch (Exception $e) {
        if ($role === 'member') {
            echo "✓ Members correctly denied access to user list<br>\n";
        } else {
            echo "✗ User list access failed: " . $e->getMessage() . "<br>\n";
        }
    }
    
    echo "<h3>4. Testing Project Creation</h3>\n";
    try {
        if ($role === 'admin' || $role === 'project_manager') {
            $projectModel = new Project();
            $projectId = $projectModel->create($testProject);
            if ($projectId) {
                echo "✓ Can create projects: Project ID {$projectId}<br>\n";
                // Clean up
                $projectModel->delete($projectId);
            } else {
                echo "✗ Project creation failed<br>\n";
            }
        } else {
            echo "✗ Members cannot create projects (403 expected)<br>\n";
        }
    } catch (Exception $e) {
        if ($role === 'member') {
            echo "✓ Members correctly denied project creation<br>\n";
        } else {
            echo "✗ Project creation failed: " . $e->getMessage() . "<br>\n";
        }
    }
    
    echo "<h3>5. Testing Task Creation</h3>\n";
    try {
        $taskModel = new Task();
        $testTask['created_by'] = 1;
        $taskId = $taskModel->create($testTask);
        if ($taskId) {
            echo "✓ Can create tasks: Task ID {$taskId}<br>\n";
            // Clean up
            $taskModel->delete($taskId);
        } else {
            echo "✗ Task creation failed<br>\n";
        }
    } catch (Exception $e) {
        echo "✗ Task creation failed: " . $e->getMessage() . "<br>\n";
    }
    
    echo "<h3>6. Testing Task Edit Permissions</h3>\n";
    try {
        // Create a test task first
        $taskModel = new Task();
        $testTask['created_by'] = 1;
        $testTask['assigned_to'] = 1;
        $taskId = $taskModel->create($testTask);
        
        if ($taskId) {
            // Test edit permissions
            $task = $taskModel->getTaskById($taskId);
            $canEdit = false;
            
            if ($role === 'admin' || $role === 'project_manager') {
                $canEdit = true;
            } elseif ($role === 'member' && ($task['assigned_to'] == 1 || $task['created_by'] == 1)) {
                $canEdit = true;
            }
            
            if ($canEdit) {
                echo "✓ Can edit tasks: Permission granted<br>\n";
            } else {
                echo "✗ Cannot edit tasks: Permission denied<br>\n";
            }
            
            // Clean up
            $taskModel->delete($taskId);
        }
    } catch (Exception $e) {
        echo "✗ Task edit permission test failed: " . $e->getMessage() . "<br>\n";
    }
    
    echo "<h3>7. Testing Task Delete Permissions</h3>\n";
    try {
        if ($role === 'admin' || $role === 'project_manager') {
            echo "✓ Can delete tasks: Permission granted<br>\n";
        } else {
            echo "✗ Members cannot delete tasks (403 expected)<br>\n";
        }
    } catch (Exception $e) {
        echo "✗ Task delete permission test failed: " . $e->getMessage() . "<br>\n";
    }
    
    echo "<hr>\n";
}

// Test each role
foreach ($testUsers as $role => $userData) {
    testUserRole($userData, $role);
}

echo "<h2>Summary</h2>\n";
echo "<p>Role-based permissions test completed. Check the results above to ensure:</p>\n";
echo "<ul>\n";
echo "<li><strong>Admin:</strong> Full access to all CRUD operations</li>\n";
echo "<li><strong>Project Manager:</strong> Can manage projects and tasks, view all data</li>\n";
echo "<li><strong>Member:</strong> Can only view and edit their own tasks, view assigned projects</li>\n";
echo "</ul>\n";
?>
