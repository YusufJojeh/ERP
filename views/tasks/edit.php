<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Get task ID from URL
$task_id = (int)($_GET['id'] ?? 0);

if (!$task_id) {
    header('HTTP/1.1 404 Not Found');
    include '../errors/404.php';
    exit();
}

// Load task data
$taskModel = new Task();
$task = $taskModel->getTaskById($task_id);

if (!$task) {
    header('HTTP/1.1 404 Not Found');
    include '../errors/404.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $project_id = (int)($_POST['project_id'] ?? 0);
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        $status = sanitizeInput($_POST['status'] ?? 'pending');
        $due_date = $_POST['due_date'] ?? null;
        $progress = (int)($_POST['progress'] ?? 0);
        $estimated_hours = (int)($_POST['estimated_hours'] ?? 0);
        $actual_hours = (int)($_POST['actual_hours'] ?? 0);
        
        // Validate input
        if (empty($title)) {
            throw new Exception('Task title is required');
        }
        
        if ($project_id <= 0) {
            throw new Exception('Please select a project');
        }
        
        if ($assigned_to <= 0) {
            throw new Exception('Please assign the task to a user');
        }
        
        $data = [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => $status,
            'assigned_to' => $assigned_to,
            'due_date' => $due_date,
            'progress' => $progress,
            'estimated_hours' => $estimated_hours,
            'actual_hours' => $actual_hours
        ];
        
        $result = $taskModel->update($task_id, $data);
        
        if ($result) {
            $success = 'Task updated successfully!';
            // Redirect to task view
            echo "<script>setTimeout(function(){ window.location.href = 'view.php?id=" . $task_id . "'; }, 2000);</script>";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get projects and users for dropdowns
$projectModel = new Project();
$userModel = new User();
$projects = $projectModel->getAllProjects(1, 1000, []);
$users = $userModel->getAllUsers(1, 1000, '');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Edit Task</h1>
                    <p class="text-muted">Update task details and assignments</p>
                </div>
                <a href="view.php?id=<?php echo $task_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Task
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Task Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($task['title']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="project_id" class="form-label">Project *</label>
                                            <select class="form-select" id="project_id" name="project_id" required>
                                                <option value="">Select Project</option>
                                                <?php foreach ($projects as $project): ?>
                                                    <option value="<?php echo $project['id']; ?>" 
                                                            <?php echo $task['project_id'] == $project['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($project['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="assigned_to" class="form-label">Assigned To *</label>
                                            <select class="form-select" id="assigned_to" name="assigned_to" required>
                                                <option value="">Select User</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>" 
                                                            <?php echo $task['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority</label>
                                            <select class="form-select" id="priority" name="priority">
                                                <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                                <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                                <option value="critical" <?php echo $task['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>Review</option>
                                                <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $task['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="progress" class="form-label">Progress (%)</label>
                                            <input type="number" class="form-control" id="progress" name="progress" 
                                                   min="0" max="100" value="<?php echo $task['progress'] ?? 0; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="due_date" class="form-label">Due Date</label>
                                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                                   value="<?php echo $task['due_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                                            <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" 
                                                   min="0" step="0.5" value="<?php echo $task['estimated_hours'] ?? 0; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="actual_hours" class="form-label">Actual Hours</label>
                                    <input type="number" class="form-control" id="actual_hours" name="actual_hours" 
                                           min="0" step="0.5" value="<?php echo $task['actual_hours'] ?? 0; ?>">
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="view.php?id=<?php echo $task_id; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Task
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
