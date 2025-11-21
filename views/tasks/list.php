<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}
include __DIR__ . '/../includes/header.php';

// Load tasks data
$taskModel = new Task();

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$project_id = $_GET['project_id'] ?? '';
$assigned_to = $_GET['assigned_to'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get tasks with filters
$filters = [
    'search' => $search,
    'status' => $status,
    'priority' => $priority,
    'project_id' => $project_id,
    'assigned_to' => $assigned_to
];
$tasks = $taskModel->getAllTasks($page, $limit, $filters);
$totalTasks = $taskModel->getTotalTasks($filters);
$totalPages = ceil($totalTasks / $limit);

// Get projects and users for filter dropdowns
$projectModel = new Project();
$userModel = new User();
$projects = $projectModel->getAllProjects(1, 1000, []); // Get all projects for dropdown
$users = $userModel->getAllUsers(1, 1000, ''); // Get all users for dropdown
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Tasks</h1>
                    <p class="text-muted">Manage your tasks and track progress</p>
                </div>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create New Task
                </a>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Filter Tasks</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                       placeholder="Search tasks...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo ($status ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo ($status ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="review" <?php echo ($status ?? '') === 'review' ? 'selected' : ''; ?>>Review</option>
                                    <option value="completed" <?php echo ($status ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($status ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="">All Priority</option>
                                    <option value="low" <?php echo ($priority ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo ($priority ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo ($priority ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="critical" <?php echo ($priority ?? '') === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Project</label>
                                <select class="form-select" name="project_id">
                                    <option value="">All Projects</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>" 
                                                <?php echo ($project_id ?? '') == $project['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Assigned To</label>
                                <select class="form-select" name="assigned_to">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo ($assigned_to ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tasks List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Tasks List</h6>
                    <span class="badge bg-secondary">Total: <?php echo $totalTasks; ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <h5>No tasks found</h5>
                            <p class="text-muted">Try adjusting your filters or create a new task.</p>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create New Task
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Assigned To</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                                    <?php if ($task['description']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?>...</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($task['project_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($task['assigned_user'] ?? 'Unassigned'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $task['priority'] === 'critical' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : ($task['priority'] === 'medium' ? 'info' : 'secondary')); ?>">
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $task['status'] === 'completed' ? 'success' : ($task['status'] === 'in_progress' ? 'primary' : ($task['status'] === 'review' ? 'warning' : 'secondary')); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($task['due_date']): ?>
                                                    <?php echo formatDate($task['due_date'], 'M d, Y'); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No due date</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="width: 100px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $task['progress'] ?? 0; ?>%">
                                                        <?php echo $task['progress'] ?? 0; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view.php?id=<?php echo $task['id']; ?>" 
                                                       class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $task['id']; ?>" 
                                                       class="btn btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $task['id']; ?>" 
                                                       class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Tasks pagination">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&priority=<?php echo urlencode($priority ?? ''); ?>&project_id=<?php echo urlencode($project_id ?? ''); ?>&assigned_to=<?php echo urlencode($assigned_to ?? ''); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
