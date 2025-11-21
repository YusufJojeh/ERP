<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Load project data if not already loaded by controller
$project_id = (int)($_GET['id'] ?? 0);

if (!isset($project) && $project_id > 0) {
    $projectModel = new Project();
    $taskModel = new Task();
    $userModel = new User();
    
    $project = $projectModel->getProjectById($project_id);
    
    if (!$project) {
        header('HTTP/1.1 404 Not Found');
        include '../errors/404.php';
        exit();
    }
    
    // Load related data
    $tasks = $taskModel->getProjectTasks($project_id);
    $members = $projectModel->getProjectMembers($project_id);
    $stats = $projectModel->getProjectStats($project_id);
}

if (!isset($project) || !$project) {
    header('HTTP/1.1 404 Not Found');
    include '../errors/404.php';
    exit();
}

$pageTitle = $project['name'];
$currentPage = 'projects';
$pageDescription = 'Project details and management';

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <!-- Project Overview -->
    <div class="col-xl-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Project Overview</h6>
                <div>
                    <span class="badge badge-<?php echo getStatusBadgeClass($project['status']); ?> me-2">
                        <?php echo ucfirst($project['status']); ?>
                    </span>
                    <span class="badge badge-<?php echo getPriorityBadgeClass($project['priority']); ?>">
                        <?php echo ucfirst($project['priority']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h4 class="text-primary mb-3"><?php echo htmlspecialchars($project['name']); ?></h4>
                
                <?php if ($project['description']): ?>
                    <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Project Manager:</strong>
                            <span class="text-muted">
                                <?php 
                                if (!empty($project['manager_id']) && !empty($project['first_name']) && !empty($project['last_name'])) {
                                    echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']);
                                    if (!empty($project['manager_name'])) {
                                        echo ' (' . htmlspecialchars($project['manager_name']) . ')';
                                    }
                                } elseif (!empty($project['manager_name'])) {
                                    echo htmlspecialchars($project['manager_name']);
                                } else {
                                    echo '<span class="text-danger">Unassigned - Please assign a manager</span>';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Start Date:</strong>
                            <span class="text-muted">
                                <?php echo $project['start_date'] ? formatDate($project['start_date'], 'M d, Y') : 'Not set'; ?>
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>End Date:</strong>
                            <span class="text-muted">
                                <?php echo $project['end_date'] ? formatDate($project['end_date'], 'M d, Y') : 'Not set'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Budget:</strong>
                            <span class="text-muted">
                                <?php echo $project['budget'] ? '$' . number_format($project['budget'], 2) : 'Not set'; ?>
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Created:</strong>
                            <span class="text-muted">
                                <?php echo formatDate($project['created_at'], 'M d, Y H:i'); ?>
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Last Updated:</strong>
                            <span class="text-muted">
                                <?php echo formatDate($project['updated_at'], 'M d, Y H:i'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Progress</strong>
                        <span class="text-muted"><?php echo $project['progress']; ?>%</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: <?php echo $project['progress']; ?>%"
                             aria-valuenow="<?php echo $project['progress']; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?php echo $project['progress']; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Tasks -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Project Tasks</h6>
                <a href="../tasks/create.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Task
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($tasks)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tasks fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No tasks assigned to this project yet.</p>
                        <a href="../tasks/create.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create First Task
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Assigned To</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <a href="../tasks/view.php?id=<?php echo $task['id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($task['assigned_username'])): ?>
                                            <?php 
                                            $firstName = $task['assigned_first_name'] ?? '';
                                            $lastName = $task['assigned_last_name'] ?? '';
                                            $fullName = trim($firstName . ' ' . $lastName);
                                            echo htmlspecialchars($fullName ?: $task['assigned_username']); 
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getPriorityBadgeClass($task['priority']); ?>">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getTaskStatusBadgeClass($task['status']); ?>">
                                            <?php echo ucfirst($task['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($task['due_date']): ?>
                                            <?php 
                                            $dueDate = new DateTime($task['due_date']);
                                            $now = new DateTime();
                                            $isOverdue = $dueDate < $now && $task['status'] !== 'completed';
                                            ?>
                                            <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo formatDate($task['due_date'], 'M d, Y'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../tasks/view.php?id=<?php echo $task['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Project Statistics & Members -->
    <div class="col-xl-4">
        <!-- Project Statistics -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Project Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border-end">
                            <div class="h4 text-primary"><?php echo $stats['total_tasks']; ?></div>
                            <div class="text-muted">Total Tasks</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-success"><?php echo $stats['completed_tasks']; ?></div>
                        <div class="text-muted">Completed</div>
                    </div>
                    <div class="col-6">
                        <div class="border-end">
                            <div class="h4 text-warning"><?php echo $stats['in_progress_tasks']; ?></div>
                            <div class="text-muted">In Progress</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="h4 text-danger"><?php echo $stats['overdue_tasks']; ?></div>
                        <div class="text-muted">Overdue</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Members -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Project Members</h6>
                <?php if (hasRole(['admin', 'project_manager'])): ?>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="fas fa-plus me-2"></i>Add Member
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($members)): ?>
                    <p class="text-muted text-center">No members assigned to this project.</p>
                <?php else: ?>
                    <?php foreach ($members as $member): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-bold"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($member['username']); ?></div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge badge-info"><?php echo ucfirst($member['role']); ?></span>
                            <?php if (hasRole(['admin', 'project_manager'])): ?>
                                <a href="remove_member.php?project_id=<?php echo $project['id']; ?>&user_id=<?php echo $member['user_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger ms-2" 
                                   onclick="return confirm('Remove this member from the project?')">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<?php if (hasRole(['admin', 'project_manager'])): ?>
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Member to Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="add_member.php">
                <div class="modal-body">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Choose a user...</option>
                            <?php
                            // Load users if not already loaded
                            if (!isset($allUsers)) {
                                if (!isset($userModel)) {
                                    $userModel = new User();
                                }
                                $allUsers = $userModel->getAllUsers(1, 1000, '');
                            }
                            foreach ($allUsers as $user) {
                                // Skip if user is already a member
                                $isMember = false;
                                if (!empty($members)) {
                                    foreach ($members as $member) {
                                        if ($member['user_id'] == $user['id']) {
                                            $isMember = true;
                                            break;
                                        }
                                    }
                                }
                                if (!$isMember) {
                                    echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ' (' . htmlspecialchars($user['username']) . ')</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="developer">Developer</option>
                            <option value="designer">Designer</option>
                            <option value="tester">Tester</option>
                            <option value="observer">Observer</option>
                        </select>
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
<?php endif; ?>

<?php
// Helper functions
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active': return 'success';
        case 'completed': return 'primary';
        case 'on_hold': return 'warning';
        case 'planning': return 'info';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPriorityBadgeClass($priority) {
    switch ($priority) {
        case 'low': return 'info';
        case 'medium': return 'primary';
        case 'high': return 'warning';
        case 'critical': return 'danger';
        default: return 'secondary';
    }
}

function getTaskStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'secondary';
        case 'in_progress': return 'warning';
        case 'review': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

// Include footer
include __DIR__ . '/../includes/footer.php';
?>
