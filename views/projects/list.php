<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

$pageTitle = 'Projects';
$currentPage = 'projects';
$pageDescription = 'Manage your projects and track progress';

// Load projects data
$projectModel = new Project();

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$manager_id = $_GET['manager_id'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get projects with filters
$filters = [
    'search' => $search,
    'status' => $status,
    'priority' => $priority,
    'manager_id' => $manager_id
];
$projects = $projectModel->getAllProjects($page, $limit, $filters);
$totalProjects = $projectModel->getTotalProjects($filters);
$totalPages = ceil($totalProjects / $limit);

// Get managers for filter dropdown
$userModel = new User();
$managers = $userModel->getUsersByRole('project_manager');

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Filter Projects</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                               placeholder="Search projects...">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="planning" <?php echo ($_GET['status'] ?? '') === 'planning' ? 'selected' : ''; ?>>Planning</option>
                            <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="on_hold" <?php echo ($_GET['status'] ?? '') === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                            <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">All Priority</option>
                            <option value="low" <?php echo ($_GET['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo ($_GET['priority'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo ($_GET['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="critical" <?php echo ($_GET['priority'] ?? '') === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    <?php if (hasRole(['admin', 'project_manager'])): ?>
                    <div class="col-md-3">
                        <label for="manager_id" class="form-label">Manager</label>
                        <select class="form-select" id="manager_id" name="manager_id">
                            <option value="">All Managers</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager['id']; ?>" 
                                        <?php echo ($_GET['manager_id'] ?? '') == $manager['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Projects List</h6>
                <div>
                    <span class="badge badge-info">Total: <?php echo $totalProjects; ?></span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($projects)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No projects found</h5>
                        <p class="text-muted">Try adjusting your filters or create a new project.</p>
                        <?php if (hasRole(['admin', 'project_manager'])): ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create New Project
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Manager</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Progress</th>
                                    <th>Tasks</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <a href="view.php?id=<?php echo $project['id']; ?>" 
                                               class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($project['name']); ?>
                                            </a>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>
                                            <?php if (strlen($project['description']) > 100): ?>...<?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($project['manager_name']): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle me-2 text-muted"></i>
                                                <span><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getStatusBadgeClass($project['status']); ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo getPriorityBadgeClass($project['priority']); ?>">
                                            <?php echo ucfirst($project['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar progress-bar-striped" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $project['progress']; ?>%"
                                                 aria-valuenow="<?php echo $project['progress']; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo $project['progress']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo $project['completed_tasks']; ?>/<?php echo $project['task_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $project['start_date'] ? formatDate($project['start_date'], 'M d, Y') : '-'; ?>
                                    </td>
                                    <td>
                                        <?php echo $project['end_date'] ? formatDate($project['end_date'], 'M d, Y') : '-'; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view.php?id=<?php echo $project['id']; ?>" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (hasRole(['admin', 'project_manager'])): ?>
                                                <a href="edit.php?id=<?php echo $project['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $project['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this project?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Projects pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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

// Include footer
include __DIR__ . '/../includes/footer.php';
?>
