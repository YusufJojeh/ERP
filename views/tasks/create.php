<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}
include __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Get project_id from URL parameter
$selected_project_id = (int)($_GET['project_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $project_id = (int)($_POST['project_id'] ?? $selected_project_id);
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        $status = sanitizeInput($_POST['status'] ?? 'pending');
        $due_date = $_POST['due_date'] ?? null;
        $progress = (int)($_POST['progress'] ?? 0);
        
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
        
        $task = new Task();
        $task_id = $task->create($title, $description, $project_id, $assigned_to, $priority, $status, $due_date, $progress);
        
        if ($task_id) {
            $success = 'Task created successfully!';
            // Redirect to project view or task list
            if ($project_id > 0) {
                echo "<script>setTimeout(function(){ window.location.href = '../projects/view.php?id=" . $project_id . "'; }, 2000);</script>";
            } else {
                echo "<script>setTimeout(function(){ window.location.href = 'list.php'; }, 2000);</script>";
            }
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

// Get project details if project_id is provided
$selected_project = null;
if ($selected_project_id > 0) {
    $selected_project = $projectModel->getProjectById($selected_project_id);
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Create New Task</h1>
                    <p class="text-muted">
                        Create a new task and assign it to a team member
                        <?php if ($selected_project): ?>
                            for <strong><?php echo htmlspecialchars($selected_project['name']); ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Tasks
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
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="project_id" class="form-label">Project *</label>
                                            <select class="form-select" id="project_id" name="project_id" required>
                                                <option value="">Select Project</option>
                                                <?php foreach ($projects as $project): ?>
                                                    <option value="<?php echo $project['id']; ?>" 
                                                            <?php echo ($_POST['project_id'] ?? $selected_project_id) == $project['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($project['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="assigned_to" class="form-label">Assigned To *</label>
                                            <div class="input-group">
                                                <select class="form-select" id="assigned_to" name="assigned_to" required>
                                                    <option value="">Select User</option>
                                                    <?php foreach ($users as $user): ?>
                                                        <option value="<?php echo $user['id']; ?>" 
                                                                <?php echo ($_POST['assigned_to'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                                    <i class="fas fa-plus"></i> Add Member
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority</label>
                                            <select class="form-select" id="priority" name="priority">
                                                <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                                                <option value="medium" <?php echo ($_POST['priority'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                                                <option value="critical" <?php echo ($_POST['priority'] ?? '') === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="pending" <?php echo ($_POST['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="in_progress" <?php echo ($_POST['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="review" <?php echo ($_POST['status'] ?? '') === 'review' ? 'selected' : ''; ?>>Review</option>
                                                <option value="completed" <?php echo ($_POST['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="progress" class="form-label">Progress (%)</label>
                                            <input type="number" class="form-control" id="progress" name="progress" 
                                                   min="0" max="100" value="<?php echo $_POST['progress'] ?? 0; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" 
                                           value="<?php echo $_POST['due_date'] ?? ''; ?>">
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="list.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Task
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

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">Add New Team Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addMemberForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="new_first_name" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="new_last_name" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="new_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="new_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_role" class="form-label">Role</label>
                        <select class="form-select" id="new_role" name="role">
                            <option value="member">Member</option>
                            <option value="project_manager">Project Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="new_password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addMemberForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_member');
    
    fetch('../../index.php?controller=User&action=register', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new user to the dropdown
            const select = document.getElementById('assigned_to');
            const option = document.createElement('option');
            option.value = data.user_id;
            option.textContent = formData.get('first_name') + ' ' + formData.get('last_name');
            select.appendChild(option);
            select.value = data.user_id;
            
            // Close modal and reset form
            const modal = bootstrap.Modal.getInstance(document.getElementById('addMemberModal'));
            modal.hide();
            this.reset();
            
            // Show success message
            alert('Member added successfully!');
        } else {
            alert('Error adding member: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding member. Please try again.');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
