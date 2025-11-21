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
        $confirm = $_POST['confirm'] ?? '';
        
        if ($confirm !== 'DELETE') {
            throw new Exception('Please type DELETE to confirm deletion');
        }
        
        $result = $taskModel->delete($task_id);
        
        if ($result) {
            $success = 'Task deleted successfully!';
            echo "<script>setTimeout(function(){ window.location.href = 'list.php'; }, 2000);</script>";
        } else {
            throw new Exception('Failed to delete task');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get project info for context
$projectModel = new Project();
$project = $projectModel->getProjectById($task['project_id']);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-danger">Delete Task</h1>
                    <p class="text-muted">This action cannot be undone</p>
                </div>
                <a href="view.php?id=<?php echo $task_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Task
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Confirm Task Deletion
                            </h6>
                        </div>
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

                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Warning!
                                </h6>
                                <p class="mb-0">You are about to permanently delete this task. This action cannot be undone and will also delete:</p>
                                <ul class="mb-0 mt-2">
                                    <li>All comments associated with this task</li>
                                    <li>All file attachments</li>
                                    <li>All activity logs related to this task</li>
                                </ul>
                            </div>

                            <!-- Task Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Task to be deleted</h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($task['title']); ?></h5>
                                    <p class="card-text">
                                        <strong>Project:</strong> 
                                        <a href="../projects/view.php?id=<?php echo $task['project_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($project['name'] ?? 'Unknown Project'); ?>
                                        </a>
                                    </p>
                                    <p class="card-text">
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?php echo $task['status'] === 'completed' ? 'success' : ($task['status'] === 'in_progress' ? 'primary' : ($task['status'] === 'review' ? 'warning' : 'secondary')); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                        </span>
                                    </p>
                                    <p class="card-text">
                                        <strong>Priority:</strong> 
                                        <span class="badge bg-<?php echo $task['priority'] === 'critical' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : ($task['priority'] === 'medium' ? 'info' : 'secondary')); ?>">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    </p>
                                    <?php if ($task['description']): ?>
                                        <p class="card-text">
                                            <strong>Description:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="confirm" class="form-label">
                                        Type <strong>DELETE</strong> to confirm deletion
                                    </label>
                                    <input type="text" class="form-control" id="confirm" name="confirm" 
                                           placeholder="Type DELETE here" required>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="view.php?id=<?php echo $task_id; ?>" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                        <i class="fas fa-trash me-2"></i>Delete Task
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

<script>
document.getElementById('confirm').addEventListener('input', function() {
    const confirmInput = this.value;
    const deleteBtn = document.getElementById('deleteBtn');
    
    if (confirmInput === 'DELETE') {
        deleteBtn.disabled = false;
        deleteBtn.classList.remove('btn-outline-danger');
        deleteBtn.classList.add('btn-danger');
    } else {
        deleteBtn.disabled = true;
        deleteBtn.classList.remove('btn-danger');
        deleteBtn.classList.add('btn-outline-danger');
    }
});

// Double confirmation
document.getElementById('deleteBtn').addEventListener('click', function(e) {
    if (!confirm('Are you absolutely sure you want to delete this task? This action cannot be undone!')) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
