<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Get project ID from URL
$project_id = (int)($_GET['id'] ?? 0);

if (!$project_id) {
    header('HTTP/1.1 404 Not Found');
    include '../errors/404.php';
    exit();
}

// Load project data
$projectModel = new Project();
$project = $projectModel->getProjectById($project_id);

if (!$project) {
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
        
        $result = $projectModel->delete($project_id);
        
        if ($result) {
            $success = 'Project deleted successfully!';
            echo "<script>setTimeout(function(){ window.location.href = 'list.php'; }, 2000);</script>";
        } else {
            throw new Exception('Failed to delete project');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get project statistics
$taskModel = new Task();
$projectTasks = $taskModel->getProjectTasks($project_id);
$taskCount = count($projectTasks);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-danger">Delete Project</h1>
                    <p class="text-muted">This action cannot be undone</p>
                </div>
                <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Project
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Confirm Project Deletion
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
                                <p class="mb-0">You are about to permanently delete this project. This action cannot be undone and will also delete:</p>
                                <ul class="mb-0 mt-2">
                                    <li>All tasks associated with this project (<?php echo $taskCount; ?> tasks)</li>
                                    <li>All comments on project tasks</li>
                                    <li>All file attachments</li>
                                    <li>All activity logs related to this project</li>
                                    <li>All project memberships</li>
                                </ul>
                            </div>

                            <!-- Project Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Project to be deleted</h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($project['name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?php echo $project['status'] === 'completed' ? 'success' : ($project['status'] === 'active' ? 'primary' : ($project['status'] === 'on_hold' ? 'warning' : 'secondary')); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                        </span>
                                    </p>
                                    <p class="card-text">
                                        <strong>Priority:</strong> 
                                        <span class="badge bg-<?php echo $project['priority'] === 'critical' ? 'danger' : ($project['priority'] === 'high' ? 'warning' : ($project['priority'] === 'medium' ? 'info' : 'secondary')); ?>">
                                            <?php echo ucfirst($project['priority']); ?>
                                        </span>
                                    </p>
                                    <p class="card-text">
                                        <strong>Tasks:</strong> <?php echo $taskCount; ?> tasks
                                    </p>
                                    <?php if ($project['description']): ?>
                                        <p class="card-text">
                                            <strong>Description:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
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
                                    <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                        <i class="fas fa-trash me-2"></i>Delete Project
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
    if (!confirm('Are you absolutely sure you want to delete this project? This action cannot be undone!')) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Get project ID from URL
$project_id = (int)($_GET['id'] ?? 0);

if (!$project_id) {
    header('HTTP/1.1 404 Not Found');
    include '../errors/404.php';
    exit();
}

// Load project data
$projectModel = new Project();
$project = $projectModel->getProjectById($project_id);

if (!$project) {
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
        
        $result = $projectModel->delete($project_id);
        
        if ($result) {
            $success = 'Project deleted successfully!';
            echo "<script>setTimeout(function(){ window.location.href = 'list.php'; }, 2000);</script>";
        } else {
            throw new Exception('Failed to delete project');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get project statistics
$taskModel = new Task();
$projectTasks = $taskModel->getProjectTasks($project_id);
$taskCount = count($projectTasks);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-danger">Delete Project</h1>
                    <p class="text-muted">This action cannot be undone</p>
                </div>
                <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Project
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Confirm Project Deletion
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
                                <p class="mb-0">You are about to permanently delete this project. This action cannot be undone and will also delete:</p>
                                <ul class="mb-0 mt-2">
                                    <li>All tasks associated with this project (<?php echo $taskCount; ?> tasks)</li>
                                    <li>All comments on project tasks</li>
                                    <li>All file attachments</li>
                                    <li>All activity logs related to this project</li>
                                    <li>All project memberships</li>
                                </ul>
                            </div>

                            <!-- Project Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Project to be deleted</h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($project['name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?php echo $project['status'] === 'completed' ? 'success' : ($project['status'] === 'active' ? 'primary' : ($project['status'] === 'on_hold' ? 'warning' : 'secondary')); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                        </span>
                                    </p>
                                    <p class="card-text">
                                        <strong>Priority:</strong> 
                                        <span class="badge bg-<?php echo $project['priority'] === 'critical' ? 'danger' : ($project['priority'] === 'high' ? 'warning' : ($project['priority'] === 'medium' ? 'info' : 'secondary')); ?>">
                                            <?php echo ucfirst($project['priority']); ?>
                                        </span>
                                    </p>
                                    <p class="card-text">
                                        <strong>Tasks:</strong> <?php echo $taskCount; ?> tasks
                                    </p>
                                    <?php if ($project['description']): ?>
                                        <p class="card-text">
                                            <strong>Description:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
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
                                    <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                        <i class="fas fa-trash me-2"></i>Delete Project
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
    if (!confirm('Are you absolutely sure you want to delete this project? This action cannot be undone!')) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Get project ID from URL
$project_id = (int)($_GET['id'] ?? 0);

if (!$project_id) {
    header('HTTP/1.1 404 Not Found');
    include '../errors/404.php';
    exit();
}

// Load project data
$projectModel = new Project();
$project = $projectModel->getProjectById($project_id);

if (!$project) {
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
        
        $result = $projectModel->delete($project_id);
        
        if ($result) {
            $success = 'Project deleted successfully!';
            echo "<script>setTimeout(function(){ window.location.href = 'list.php'; }, 2000);</script>";
        } else {
            throw new Exception('Failed to delete project');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get project statistics
$taskModel = new Task();
$projectTasks = $taskModel->getProjectTasks($project_id);
$taskCount = count($projectTasks);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-danger">Delete Project</h1>
                    <p class="text-muted">This action cannot be undone</p>
                </div>
                <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Project
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Confirm Project Deletion
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
                                <p class="mb-0">You are about to permanently delete this project. This action cannot be undone and will also delete:</p>
                                <ul class="mb-0 mt-2">
                                    <li>All tasks associated with this project (<?php echo $taskCount; ?> tasks)</li>
                                    <li>All comments on project tasks</li>
                                    <li>All file attachments</li>
                                    <li>All activity logs related to this project</li>
                                    <li>All project memberships</li>
                                </ul>
                            </div>

                            <!-- Project Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Project to be deleted</h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($project['name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?php echo $project['status'] === 'completed' ? 'success' : ($project['status'] === 'active' ? 'primary' : ($project['status'] === 'on_hold' ? 'warning' : 'secondary')); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                        </span>
                                    </p>
                                    <p class="card-text">
                                        <strong>Priority:</strong> 
                                        <span class="badge bg-<?php echo $project['priority'] === 'critical' ? 'danger' : ($project['priority'] === 'high' ? 'warning' : ($project['priority'] === 'medium' ? 'info' : 'secondary')); ?>">
                                            <?php echo ucfirst($project['priority']); ?>
                                        </span>
                                    </p>
                                    <p class="card-text">
                                        <strong>Tasks:</strong> <?php echo $taskCount; ?> tasks
                                    </p>
                                    <?php if ($project['description']): ?>
                                        <p class="card-text">
                                            <strong>Description:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
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
                                    <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                        <i class="fas fa-trash me-2"></i>Delete Project
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
    if (!confirm('Are you absolutely sure you want to delete this project? This action cannot be undone!')) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
