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
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $manager_id = (int)($_POST['manager_id'] ?? 0);
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        $status = sanitizeInput($_POST['status'] ?? 'planning');
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $budget = (float)($_POST['budget'] ?? 0);
        
        // Validate input
        if (empty($name)) {
            throw new Exception('Project name is required');
        }
        
        if ($manager_id <= 0) {
            throw new Exception('Please select a project manager');
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'manager_id' => $manager_id,
            'priority' => $priority,
            'status' => $status,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'budget' => $budget
        ];
        
        $result = $projectModel->update($project_id, $data);
        
        if ($result) {
            $success = 'Project updated successfully!';
            // Redirect to project view
            echo "<script>setTimeout(function(){ window.location.href = 'view.php?id=" . $project_id . "'; }, 2000);</script>";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get users for manager dropdown
$userModel = new User();
$managers = $userModel->getUsersByRole('project_manager');
$allUsers = $userModel->getAllUsers(1, 1000, '');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Edit Project</h1>
                    <p class="text-muted">Update project details and settings</p>
                </div>
                <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Project
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
                                    <label for="name" class="form-label">Project Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($project['name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="manager_id" class="form-label">Project Manager *</label>
                                            <select class="form-select" id="manager_id" name="manager_id" required>
                                                <option value="">Select Manager</option>
                                                <?php foreach ($managers as $manager): ?>
                                                    <option value="<?php echo $manager['id']; ?>" 
                                                            <?php echo $project['manager_id'] == $manager['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority</label>
                                            <select class="form-select" id="priority" name="priority">
                                                <option value="low" <?php echo $project['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                                <option value="medium" <?php echo $project['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="high" <?php echo $project['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                                <option value="critical" <?php echo $project['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="planning" <?php echo $project['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                                <option value="active" <?php echo $project['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="on_hold" <?php echo $project['status'] === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                                                <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $project['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="budget" class="form-label">Budget ($)</label>
                                            <input type="number" class="form-control" id="budget" name="budget" 
                                                   min="0" step="0.01" value="<?php echo $project['budget'] ?? 0; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                                   value="<?php echo $project['start_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                                   value="<?php echo $project['end_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="view.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Project
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
